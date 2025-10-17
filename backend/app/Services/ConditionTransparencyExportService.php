<?php

namespace App\Services;

use App\Jobs\ProcessConditionTransparencyExportJob;
use App\Models\ConditionTransparencyExport;
use App\Models\Group;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ConditionTransparencyExportService
{
    public function __construct(
        private readonly ConditionTimerSummaryProjector $projector,
        private readonly ConditionTimerChronicleService $chronicle,
        private readonly ConditionTimerAcknowledgementService $acknowledgements,
        private readonly ConditionTimerShareConsentService $consents,
        private readonly ConditionTimerSummaryShareService $shares
    ) {
    }

    public function requestExport(
        Group $group,
        User $requester,
        string $format,
        string $visibilityMode,
        array $filters = []
    ): ConditionTransparencyExport {
        $format = in_array($format, ['csv', 'json'], true) ? $format : config('condition-transparency.exports.default_format', 'csv');
        $visibilityMode = in_array($visibilityMode, ['counts', 'details'], true)
            ? $visibilityMode
            : config('condition-transparency.exports.default_visibility', 'counts');

        $export = ConditionTransparencyExport::query()->create([
            'group_id' => $group->id,
            'requested_by' => $requester->getAuthIdentifier(),
            'format' => $format,
            'visibility_mode' => $visibilityMode,
            'filters' => $filters,
            'status' => ConditionTransparencyExport::STATUS_PENDING,
        ]);

        ProcessConditionTransparencyExportJob::dispatch($export->id);

        return $export;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDataset(ConditionTransparencyExport $export): array
    {
        $group = $export->group;

        $summary = $this->projector->projectForGroup($group);

        if ($export->visibility_mode === 'counts') {
            $summary['entries'] = array_map(function (array $entry) {
                $entry['conditions'] = array_map(function (array $condition) {
                    unset($condition['timeline']);
                    unset($condition['detail']);

                    return $condition;
                }, $entry['conditions'] ?? []);

                return $entry;
            }, $summary['entries'] ?? []);
        }

        $since = $this->resolveSinceFilter($export);
        $allowedUsers = $this->consents->consentingUserIds($group, $export->visibility_mode);

        $acknowledgements = $this->acknowledgements->exportForGroup($group, [
            'since' => $since,
            'allowed_user_ids' => $allowedUsers,
            'visibility' => $export->visibility_mode,
        ]);

        $chronicle = $this->chronicle->exportChronicle(
            $group,
            $export->visibility_mode === 'details',
            100,
        );

        $activeShare = $this->shares->activeShareForGroup($group);
        $shareInsights = [
            'active_share' => $activeShare ? [
                'token_suffix' => substr($activeShare->token, -8),
                'visibility_mode' => $activeShare->visibility_mode,
                'state' => $this->shares->describeShareState($activeShare),
                'access_trend' => $this->shares->accessTrend($activeShare),
            ] : null,
            'trails' => $this->shares->exportAccessTrails($group),
        ];

        return [
            'generated_at' => CarbonImmutable::now('UTC')->toIso8601String(),
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'requested_by' => $export->requester?->only(['id', 'name']),
            'format' => $export->format,
            'visibility_mode' => $export->visibility_mode,
            'filters' => $export->filters,
            'summary' => $summary,
            'acknowledgements' => $acknowledgements,
            'chronicle' => $chronicle,
            'consents' => $this->consents->currentStatuses($group)->values()->all(),
            'share_access' => $shareInsights,
        ];
    }

    public function storePayload(ConditionTransparencyExport $export, array $payload): string
    {
        $disk = config('condition-transparency.exports.storage_disk', 'local');
        $path = trim(config('condition-transparency.exports.storage_path', 'exports/condition-transparency'), '/');

        if ($export->format === 'json') {
            $contents = json_encode($payload, JSON_PRETTY_PRINT);
            $filename = $this->generateFilename($export, 'json');
        } else {
            $contents = $this->renderCsv($payload['summary']['entries'] ?? []);
            $filename = $this->generateFilename($export, 'csv');
        }

        $fullPath = $path === '' ? $filename : $path.'/'.$filename;
        Storage::disk($disk)->put($fullPath, $contents ?? '');

        return $fullPath;
    }

    public function dispatchWebhooks(ConditionTransparencyExport $export, string $downloadUrl): void
    {
        $group = $export->group;
        $webhooks = $group->conditionTransparencyWebhooks()->where('active', true)->get();
        $minInterval = (int) config('condition-transparency.exports.webhook_min_interval_seconds', 60);
        $signatureHeader = config('condition-transparency.webhooks.signature_header', 'X-Condition-Transparency-Signature');

        foreach ($webhooks as $webhook) {
            if ($webhook->last_triggered_at && $webhook->last_triggered_at->diffInSeconds(now('UTC')) < $minInterval) {
                continue;
            }

            $body = [
                'export_id' => $export->id,
                'group_id' => $group->id,
                'format' => $export->format,
                'visibility_mode' => $export->visibility_mode,
                'file_path' => $export->file_path,
                'download_url' => $downloadUrl,
                'generated_at' => now('UTC')->toIso8601String(),
            ];

            $json = json_encode($body, JSON_THROW_ON_ERROR);
            $signature = hash_hmac('sha256', $json, $webhook->secret);

            $response = Http::timeout(10)
                ->withHeaders([$signatureHeader => $signature])
                ->acceptJson()
                ->post($webhook->url, $body);

            if ($response->successful()) {
                $webhook->forceFill([
                    'call_count' => (int) $webhook->call_count + 1,
                    'last_triggered_at' => now('UTC'),
                    'consecutive_failures' => 0,
                ])->save();
            } else {
                $webhook->forceFill([
                    'last_failed_at' => now('UTC'),
                    'consecutive_failures' => (int) $webhook->consecutive_failures + 1,
                ])->save();
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    protected function renderCsv(array $entries): string
    {
        $rows = [];
        $rows[] = ['Token', 'Condition', 'Urgency', 'Rounds Remaining', 'Summary'];

        foreach ($entries as $entry) {
            $token = Arr::get($entry, 'token.label', 'Unknown token');

            foreach (Arr::get($entry, 'conditions', []) as $condition) {
                $rows[] = [
                    $token,
                    Arr::get($condition, 'label', Arr::get($condition, 'key', 'Unknown condition')),
                    Arr::get($condition, 'urgency', 'unknown'),
                    Arr::get($condition, 'rounds', ''),
                    Arr::get($condition, 'summary', ''),
                ];
            }
        }

        $stream = fopen('php://temp', 'wb+');

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $csv;
    }

    protected function generateFilename(ConditionTransparencyExport $export, string $extension): string
    {
        $timestamp = now('UTC')->format('Ymd_His');

        return sprintf(
            'group-%d_export-%d_%s.%s',
            $export->group_id,
            $export->id,
            $timestamp,
            $extension,
        );
    }

    protected function resolveSinceFilter(ConditionTransparencyExport $export): ?CarbonImmutable
    {
        $since = Arr::get($export->filters, 'since');

        if (! $since) {
            return null;
        }

        try {
            return CarbonImmutable::parse($since, 'UTC');
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
