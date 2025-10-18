<?php

namespace App\Support\Ai;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AiResponseFixtureRepository
{
    /**
     * @var array<string, array{response: string, payload: array<string, mixed>}>
     */
    protected array $overrides = [];

    /**
     * @param  array<string, mixed>  $configFixtures
     */
    public function __construct(
        protected readonly Filesystem $files,
        protected readonly string $fixturePath,
        protected readonly array $configFixtures = []
    ) {
    }

    /**
     * @param  array<string, mixed>|string  $fixture
     */
    public function put(string $requestType, array|string $fixture): void
    {
        $this->overrides[$requestType] = $this->normalize($fixture, $requestType);
    }

    public function clear(string $requestType): void
    {
        unset($this->overrides[$requestType]);
    }

    /**
     * @return array{response: string, payload: array<string, mixed>}
     */
    public function responseFor(string $requestType, array $context = []): array
    {
        if (isset($this->overrides[$requestType])) {
            return $this->overrides[$requestType];
        }

        if (array_key_exists($requestType, $this->configFixtures)) {
            return $this->normalize($this->configFixtures[$requestType], $requestType);
        }

        $file = $this->fixturePath.'/'.$requestType.'.json';

        if ($this->files->exists($file)) {
            $decoded = json_decode($this->files->get($file), true);

            if (is_array($decoded)) {
                return $this->normalize($decoded, $requestType);
            }
        }

        $prompt = (string) ($context['prompt'] ?? '');
        $snippet = Str::limit(preg_replace('/\s+/', ' ', $prompt), 120, '…');

        return [
            'response' => sprintf('[mock:%s] %s', $requestType, $snippet ?: 'Fixture response unavailable'),
            'payload' => [
                'fixture' => 'auto-generated',
                'mocked' => true,
                'request_type' => $requestType,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|string  $fixture
     * @return array{response: string, payload: array<string, mixed>}
     */
    protected function normalize(array|string $fixture, string $requestType): array
    {
        if (is_string($fixture)) {
            return [
                'response' => $fixture,
                'payload' => [
                    'fixture' => 'config',
                    'mocked' => true,
                    'request_type' => $requestType,
                ],
            ];
        }

        $response = (string) Arr::get($fixture, 'response', '');

        if ($response === '') {
            $response = sprintf('[mock:%s] Fixture missing response content', $requestType);
        }

        $payload = Arr::get($fixture, 'payload', []);

        if (! is_array($payload)) {
            $payload = [];
        }

        $payload = array_merge([
            'fixture' => Arr::get($fixture, 'fixture', 'config'),
            'mocked' => true,
            'request_type' => $requestType,
        ], $payload);

        return [
            'response' => $response,
            'payload' => $payload,
        ];
    }
}
