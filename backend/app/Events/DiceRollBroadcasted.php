<?php

namespace App\Events;

use App\Models\CampaignSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiceRollBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $roll
     */
    public function __construct(
        public CampaignSession $session,
        public string $action,
        public array $roll
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
        return "dice-roll.{$this->action}";
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'roll' => $this->roll,
        ];
    }
}
