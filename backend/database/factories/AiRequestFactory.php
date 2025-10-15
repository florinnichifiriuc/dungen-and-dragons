<?php

namespace Database\Factories;

use App\Models\AiRequest;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AiRequest>
 */
class AiRequestFactory extends Factory
{
    protected $model = AiRequest::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'request_type' => 'summary',
            'context_type' => Region::class,
            'context_id' => Region::factory(),
            'meta' => [],
            'prompt' => $this->faker->sentence(),
            'response_text' => $this->faker->paragraph(),
            'response_payload' => ['message' => ['content' => $this->faker->paragraph()]],
            'status' => AiRequest::STATUS_COMPLETED,
            'provider' => 'ollama',
            'model' => 'gemma3',
            'created_by' => null,
            'completed_at' => now('UTC'),
        ];
    }
}
