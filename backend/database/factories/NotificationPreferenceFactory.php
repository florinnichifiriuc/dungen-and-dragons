<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel_in_app' => true,
            'channel_push' => false,
            'channel_email' => true,
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
            'digest_delivery' => 'off',
            'digest_channel_in_app' => true,
            'digest_channel_email' => true,
            'digest_channel_push' => false,
        ];
    }

    public function withQuietHours(string $start, string $end): self
    {
        return $this->state([
            'quiet_hours_start' => $start,
            'quiet_hours_end' => $end,
        ]);
    }
}
