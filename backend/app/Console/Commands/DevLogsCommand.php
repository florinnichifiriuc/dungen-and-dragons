<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DevLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dev:logs';

    /**
     * The console command description.
     */
    protected $description = 'Stream application logs during local development with platform-aware fallbacks.';

    public function handle(): int
    {
        if (extension_loaded('pcntl')) {
            return $this->runProcess(['php', 'artisan', 'pail', '--timeout=0']);
        }

        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath)) {
            touch($logPath);
        }

        $this->warn('The pcntl extension is not available; using a platform-aware log follower.');

        if (PHP_OS_FAMILY === 'Windows') {
            $escapedPath = str_replace('"', '""', $logPath);

            return $this->runProcess([
                'powershell',
                '-NoLogo',
                '-NoProfile',
                '-Command',
                "Get-Content -Path \"{$escapedPath}\" -Wait -Encoding UTF8",
            ]);
        }

        return $this->runProcess(['tail', '-f', $logPath]);
    }

    /**
     * Run the given process and stream output to the current console.
     */
    protected function runProcess(array $command): int
    {
        $process = new Process($command, base_path());
        $process->setTimeout(null);

        if (Process::isTtySupported()) {
            $process->setTty(true);
        }

        $process->run(function ($type, $buffer): void {
            $this->output->write($buffer);
        });

        return $process->getExitCode() ?? self::SUCCESS;
    }
}
