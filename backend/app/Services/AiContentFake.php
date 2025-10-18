<?php

namespace App\Services;

use App\Models\AiRequest;
use App\Support\Ai\AiResponseFixtureRepository;

class AiContentFake extends AiContentService
{
    public function __construct(
        ConditionMentorPromptManifest $mentorManifest,
        protected readonly AiResponseFixtureRepository $fixtures
    ) {
        parent::__construct($mentorManifest);
    }

    protected function dispatch(AiRequest $request, string $prompt, ?string $systemPrompt = null): string
    {
        $fixture = $this->fixtures->responseFor($request->request_type, [
            'prompt' => $prompt,
            'meta' => $request->meta ?? [],
        ]);

        $response = $fixture['response'];
        $payload = $fixture['payload'];

        if (! isset($payload['system_prompt']) && $systemPrompt) {
            $payload['system_prompt'] = $systemPrompt;
        }

        $request->markCompleted($response, $payload);

        return $response;
    }
}
