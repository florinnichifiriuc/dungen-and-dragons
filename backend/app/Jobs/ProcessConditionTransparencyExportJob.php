<?php

namespace App\Jobs;

use App\Models\ConditionTransparencyExport;
use App\Notifications\ConditionTransparencyExportFailed;
use App\Notifications\ConditionTransparencyExportReady;
use App\Services\ConditionTransparencyExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ProcessConditionTransparencyExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $exportId)
    {
    }

    public function handle(ConditionTransparencyExportService $exports): void
    {
        /** @var ConditionTransparencyExport|null $export */
        $export = ConditionTransparencyExport::query()->with(['group', 'requester'])->find($this->exportId);

        if (! $export) {
            return;
        }

        $export->markProcessing();

        try {
            $dataset = $exports->buildDataset($export);
            $path = $exports->storePayload($export, $dataset);
            $export->markCompleted($path);

            $downloadUrl = route('groups.condition-transparency.exports.download', [$export->group_id, $export->id]);

            $requester = $export->requester;

            if ($requester) {
                $requester->notify(new ConditionTransparencyExportReady($export->fresh(), $downloadUrl));
            }

            $slackWebhook = config('condition-transparency.exports.slack_webhook');

            if ($slackWebhook) {
                Notification::route('slack', $slackWebhook)->notify(new ConditionTransparencyExportReady($export->fresh(), $downloadUrl));
            }

            $exports->dispatchWebhooks($export->fresh(), $downloadUrl);
        } catch (Throwable $exception) {
            $export->increment('retry_attempts');
            $export->markFailed($exception->getMessage());

            $requester = $export->requester;

            if ($requester) {
                $requester->notify(new ConditionTransparencyExportFailed($export->fresh()));
            }

            throw $exception;
        }
    }
}
