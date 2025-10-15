<?php

namespace App\Events;

use App\Models\CampaignSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionNoteBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $note
     */
    public function __construct(
        public CampaignSession $session,
        public string $action,
        public array $note
    ) {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $base = "campaigns.{$this->session->campaign_id}.sessions.{$this->session->id}.workspace";

        if (($this->note['visibility'] ?? null) === 'gm') {
            return [new PrivateChannel("{$base}.gms")];
        }

        return [new PrivateChannel($base)];
    }

    public function broadcastAs(): string
    {
        return "session-note.{$this->action}";
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'note' => $this->note,
        ];
    }
}
