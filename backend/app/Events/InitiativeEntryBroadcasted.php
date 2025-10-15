<?php

namespace App\Events;

use App\Models\CampaignSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InitiativeEntryBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function __construct(
        public CampaignSession $session,
        public string $action,
        public array $entry,
        public array $entries
    ) {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $base = "campaigns.{$this->session->campaign_id}.sessions.{$this->session->id}.workspace";

        return [new PrivateChannel($base)];
    }

    public function broadcastAs(): string
    {
        return "initiative-entry.{$this->action}";
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'entry' => $this->entry,
            'entries' => $this->entries,
        ];
    }
}
