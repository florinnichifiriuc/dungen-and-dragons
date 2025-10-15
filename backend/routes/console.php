<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:milestones {milestone?}', function (?string $milestone = null) {
    $delay = (float) ($this->option('delay') ?? 1.1);
    $showAll = (bool) $this->option('all');
    $sourceOption = $this->option('source');

    $resolvePath = function (?string $path): string {
        if ($path === null) {
            return base_path('../PROGRESS_LOG.md');
        }

        if (Str::startsWith($path, ['/', '\\']) || preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    };

    $logPath = $resolvePath($sourceOption);

    if (! is_file($logPath)) {
        $this->error("Unable to locate progress log at [{$logPath}].");

        return static::FAILURE;
    }

    $lines = Collection::make(file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

    $entries = $lines
        ->filter(fn (string $line): bool => Str::startsWith($line, '| 20'))
        ->map(function (string $line): array {
            $parts = Collection::make(explode('|', $line))
                ->map(fn (string $segment): string => trim($segment))
                ->filter(fn (string $segment): bool => $segment !== '')
                ->values();

            return [
                'date' => $parts->get(0),
                'milestone' => $parts->get(1),
                'notes' => $parts->get(2),
            ];
        })
        ->values();

    if ($entries->isEmpty()) {
        $this->error('No milestone history was found in PROGRESS_LOG.md.');

        return static::FAILURE;
    }

    if ($milestone !== null) {
        $entries = $entries->filter(function (array $entry) use ($milestone): bool {
            return Str::contains(Str::lower($entry['milestone']), Str::lower($milestone));
        });

        if ($entries->isEmpty()) {
            $this->warn("No milestones matched '{$milestone}'. Available milestones: ".implode(', ', $lines
                ->filter(fn (string $line): bool => Str::startsWith($line, '| 20'))
                ->map(function (string $line): string {
                    $parts = array_values(array_filter(array_map('trim', explode('|', $line))));

                    return $parts[1] ?? '';
                })
                ->filter()
                ->unique()
                ->all()));

            return static::FAILURE;
        }
    } elseif (! $showAll) {
        $entries = Collection::make([$entries->last()]);
    }

    $pause = function () use ($delay): void {
        if ($delay > 0) {
            usleep((int) round($delay * 1_000_000));
        }
    };

    $this->components->info('🎬 Milestone Demo Flow');
    $pause();

    foreach ($entries as $entry) {
        $this->line('');
        $this->components->twoColumnDetail('Milestone', $entry['milestone']);
        $pause();
        $this->components->twoColumnDetail('Date (UTC)', $entry['date']);
        $pause();

        $highlights = Collection::make(preg_split('/\s*;\s*/', (string) $entry['notes']))
            ->filter()
            ->flatMap(function (string $note): array {
                if (Str::contains($note, '. ')) {
                    return preg_split('/(?<=\.)\s+(?=[A-Z])/', trim($note));
                }

                return [trim($note)];
            })
            ->filter()
            ->values();

        if ($highlights->isEmpty()) {
            $this->line('  • No additional notes captured for this milestone.');
            $pause();
        } else {
            $this->line('  • Highlights:');
            $pause();

            foreach ($highlights as $highlight) {
                $this->line('    ◦ '.Str::of($highlight)->trim());
                $pause();
            }
        }

        $this->line('  • Replay command: php artisan demo:milestones "'.$entry['milestone'].'"');
        $pause();
    }

    $this->line('');
    $this->components->info('✅ Demo flow complete.');

    return static::SUCCESS;
})->purpose('Run the narrated milestone demo flow for stakeholders')
    ->addOption('all', null, InputOption::VALUE_NONE, 'Replay every milestone entry instead of only the latest one.')
    ->addOption('delay', null, InputOption::VALUE_REQUIRED, 'Seconds to wait between narration beats (default: 1.1).')
    ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Optional alternate progress log path for testing or previews.');
