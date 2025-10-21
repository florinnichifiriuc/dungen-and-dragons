<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class QaDashboardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qa:dashboard {--limit=5 : Number of historical entries to display for coverage and e2e runs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Summarize local QA automation runs replacing the removed GitHub Actions dashboards.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $this->line('<options=bold>Coverage Gate</>');
        $coverageLatest = $this->readJson(storage_path('qa/coverage/latest.json'));
        $coverageHistory = $this->readHistory(storage_path('qa/coverage/history.jsonl'), $limit);

        if ($coverageLatest) {
            $this->line(sprintf(
                '  Status: %s at %s (%.2f%%, threshold %.2f%%)',
                $this->formatStatus($coverageLatest['status'] ?? 'unknown'),
                $coverageLatest['timestamp'] ?? 'unknown',
                (float) ($coverageLatest['percentage'] ?? 0),
                (float) ($coverageLatest['threshold'] ?? 0)
            ));
            $this->line('  Report: storage/qa/coverage/html/index.html');
        } else {
            $this->warn('  No coverage runs recorded. Run ./bin/coverage-gate.sh to generate one.');
        }

        if (!empty($coverageHistory)) {
            $this->table(
                ['Timestamp', 'Status', 'Coverage %'],
                array_map(fn ($entry) => [
                    Arr::get($entry, 'timestamp', 'unknown'),
                    $this->formatStatus(Arr::get($entry, 'status', 'unknown')),
                    number_format((float) Arr::get($entry, 'percentage', 0), 2),
                ], array_slice($coverageHistory, -$limit))
            );
        }

        $this->newLine();
        $this->line('<options=bold>Playwright Regression Suite</>');
        $e2eLatest = $this->readJson(storage_path('qa/e2e/latest.json'));
        $e2eHistory = $this->readHistory(storage_path('qa/e2e/history.jsonl'), $limit);

        if ($e2eLatest) {
            $totals = Arr::get($e2eLatest, 'totals', []);
            $this->line(sprintf(
                '  Status: %s at %s (%d total • %d passed • %d failed • %d skipped • %0.2fs)',
                $this->formatStatus($e2eLatest['status'] ?? 'unknown'),
                $e2eLatest['timestamp'] ?? 'unknown',
                (int) Arr::get($totals, 'total', 0),
                (int) Arr::get($totals, 'passed', 0),
                (int) Arr::get($totals, 'failed', 0),
                (int) Arr::get($totals, 'skipped', 0),
                (float) Arr::get($totals, 'durationSeconds', 0)
            ));
            $this->line('  JSON Report: storage/qa/e2e/latest.json');
        } else {
            $this->warn('  No Playwright runs recorded. Run npm run test:e2e:report first.');
        }

        if (!empty($e2eHistory)) {
            $this->table(
                ['Timestamp', 'Status', 'Total', 'Passed', 'Failed', 'Skipped', 'Duration (s)'],
                array_map(fn ($entry) => {
                    $totals = Arr::get($entry, 'totals', []);

                    return [
                        Arr::get($entry, 'timestamp', 'unknown'),
                        $this->formatStatus(Arr::get($entry, 'status', 'unknown')),
                        (int) Arr::get($totals, 'total', 0),
                        (int) Arr::get($totals, 'passed', 0),
                        (int) Arr::get($totals, 'failed', 0),
                        (int) Arr::get($totals, 'skipped', 0),
                        number_format((float) Arr::get($totals, 'durationSeconds', 0), 2),
                    ];
                }, array_slice($e2eHistory, -$limit))
            );
        }

        if (!empty($e2eLatest['failures'] ?? [])) {
            $this->newLine();
            $this->line('<options=bold>Latest Failures</>');
            $this->table(
                ['Project', 'Title', 'Error'],
                array_map(fn ($failure) => [
                    Arr::get($failure, 'project', 'unknown'),
                    Str::limit(Arr::get($failure, 'title', 'unknown'), 80),
                    Str::limit(Arr::get($failure, 'error', 'n/a'), 120),
                ], $e2eLatest['failures'])
            );
        }

        return self::SUCCESS;
    }

    /**
     * @param  string  $path
     * @return array|null
     */
    private function readJson(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $decoded = json_decode($contents, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /**
     * @param  string  $path
     * @param  int  $limit
     * @return array<int, array>
     */
    private function readHistory(string $path, int $limit): array
    {
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $entries = [];
        foreach (array_slice($lines, -$limit) as $line) {
            $decoded = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return $entries;
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'passed', 'success', 'ok' => '<fg=green>passed</>',
            'failed', 'failure', 'error' => '<fg=red>failed</>',
            default => $status,
        };
    }
}

