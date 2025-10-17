<?php

namespace App\Console\Commands;

use App\Models\ConditionTimerSummaryShare;
use App\Services\ConditionTimerSummaryShareService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ConditionTransparencyPingCommand extends Command
{
    protected $signature = 'condition-transparency:ping {--group= : Restrict monitoring to a specific group ID}';

    protected $description = 'Probe active condition transparency share links to verify uptime.';

    public function __construct(private readonly ConditionTimerSummaryShareService $shares)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $groupId = $this->option('group');

        $query = ConditionTimerSummaryShare::query()
            ->whereNull('deleted_at');

        if ($groupId) {
            $query->where('group_id', $groupId);
        }

        $shares = $query->get();

        if ($shares->isEmpty()) {
            $this->info('No active shares found to monitor.');

            return self::SUCCESS;
        }

        foreach ($shares as $share) {
            $url = route('shares.condition-timers.player-summary.show', $share->token);

            $started = microtime(true);

            try {
                $response = Http::timeout(5)
                    ->withHeaders(['Accept' => 'text/html'])
                    ->head($url);

                $duration = (microtime(true) - $started) * 1000;
                $successful = $response->successful();

                $this->shares->recordSyntheticPing($share, $successful, $duration, $response->status());

                $this->line(sprintf(
                    '%s • %s (%dms)',
                    $successful ? '<info>OK</info>' : '<error>FAIL</error>',
                    $url,
                    (int) $duration
                ));
            } catch (\Throwable $exception) {
                $duration = (microtime(true) - $started) * 1000;

                $this->shares->recordSyntheticPing($share, false, $duration);

                $this->error(sprintf('Exception pinging %s: %s', $url, $exception->getMessage()));
            }
        }

        return self::SUCCESS;
    }
}
