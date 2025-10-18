# AI Mocking & Test Harness

The release candidate relies on deterministic AI responses so unit, feature, and end-to-end suites can run without reaching a live Ollama instance. This document outlines how to work with the mock harness introduced for Task 83.

## When Mocks Are Enabled

- **Automatic during automated tests:** `AppServiceProvider` swaps `AiContentService` for `AiContentFake` whenever `app()->runningUnitTests()` evaluates to `true`.
- **Local development / E2E runs:** set `AI_MOCKS_ENABLED=true` in `.env.testing` (or export before running Playwright). You can also override the path via `AI_MOCK_FIXTURE_PATH`.

```bash
# Example: run feature tests locally with mocks forced on
php artisan test --testsuite=Feature --filter=AiMocksTest

# Example: enable mocks for a local server used by Playwright
AI_MOCKS_ENABLED=true php artisan serve
```

## Fixture Resolution Order

`AiResponseFixtureRepository` looks for responses in the following order:

1. **Runtime overrides** registered with `$repository->put()` (used inside tests to simulate edge cases).
2. **Config fixtures** defined in `config/ai.php` under the `mocks.fixtures` key (string or `{ response, payload }` arrays).
3. **JSON fixtures on disk** inside `tests/Fixtures/ai/{request_type}.json`.
4. A generated fallback that echoes the prompt snippet, ensuring tests never fail because a fixture is missing.

Each resolved fixture annotates the payload with `mocked: true`, `fixture`, and `request_type` metadata so assertions can confirm the mock path executed.

## Overriding Responses in Tests

Use the repository contract to inject specific responses for a scenario:

```php
use App\Support\Ai\AiResponseFixtureRepository;

/** @var AiResponseFixtureRepository $repository */
$repository = app(AiResponseFixtureRepository::class);

$repository->put('mentor_briefing', [
    'response' => 'Override briefing for testing expectations.',
    'payload' => ['fixture' => 'override'],
]);

// ... run code that resolves AiContentService ...

$repository->clear('mentor_briefing');
```

This strategy powers `Tests\Feature\Ai\AiMocksTest` and can be reused for NPC dialogue, DM takeover, or future AI request types.

## Headers for Playwright / External Clients

`playwright.config.ts` attaches the `x-test-ai-mock: enabled` header to every request. Middleware can inspect this header if future endpoints need to force mock behaviour (e.g., turning on AI mocks for HTTP-triggered workflows).

## Extending the Harness

1. Add fixture JSON (or config entries) using the request type as the filename (`mentor_briefing.json`, `npc_dialogue.json`, etc.).
2. Update `config/ai.php` if a new request type should expose a default string fixture.
3. Consider documenting new prompts in `docs/ai-mentor-prompts.md` so localisation stays in sync.

## Troubleshooting

- **Unexpected live calls:** confirm `AI_MOCKS_ENABLED=true` (or run the suite via `php artisan test`) and check that the service container is binding `AiContentService` to `AiContentFake`.
- **Fixture mismatch:** run `php artisan test --testsuite=Feature --filter=AiMocksTest` to verify baseline fixtures and ensure config overrides return expected content.
- **New request type fails:** add a fixture via config or disk so the repository no longer falls back to prompt echoing.
