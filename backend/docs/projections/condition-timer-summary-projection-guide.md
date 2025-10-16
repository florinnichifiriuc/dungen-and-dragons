# Condition Timer Summary Projection Guide

This guide documents the reusable projection pattern that powers player-safe condition timer summaries. It outlines how to reason about cache behaviour, invalidation hooks, telemetry, analytics, and narrative copy integration so future features can lean on the same building blocks without duplicating logic.

## Service Responsibilities
- **Source class:** `App\\Services\\ConditionTimerSummaryProjector` orchestrates cache lookups, summary hydration, broadcast fan-out, and logging.
- **Inputs:** a `Group` context, hydrated map token metadata (status conditions, durations, faction, visibility), and the narrative copy helper exposed via `App\\Support\\ConditionSummaryCopy`.
- **Outputs:** an associative array keyed by `group_id`, `generated_at`, and `entries` (each entry encapsulates the token, map, urgency, and sanitized duration hints) suitable for Inertia views, mobile recap widgets, exports, and offline caches.

```php
$summary = app(ConditionTimerSummaryProjector::class)->refreshForGroup($group);
```

## Data Flow Overview
1. **Trigger** – Any map token mutation that touches condition timers (create/update/delete) or a batch adjustment API request calls `refreshForGroup`. Turn processing does the same when automated decrements run.
2. **Cache lookup** – `projectForGroup` first checks the per-group cache key. A hit returns immediately; a miss logs `condition_timer_summary_cache_miss` and proceeds to rebuild.
3. **Hydration** – `buildSummary` fetches map tokens joined with their maps, filters active conditions, redacts GM-only intel, and applies urgency ordering.
4. **Narrative copy** – The helper `ConditionSummaryCopy::for` injects lore-friendly descriptions keyed by urgency and condition, ensuring player payloads remain thematic without exposing spoilers.
5. **Cache write & broadcast** – Summaries are cached for five minutes (`now()->addMinutes(5)`), logged via `condition_timer_summary_cache_rebuilt`, and optionally broadcast through the `ConditionTimerSummaryBroadcasted` event for realtime consumers.
6. **Consumer delivery** – Inertia pages (`Groups/ConditionTimerSummary`), dashboards, and mobile recap widgets subscribe to the broadcast channel and fall back to cached payloads on load.

### Sequence Diagram
```
Token Mutation -> Controller/FormRequest -> ConditionTimerSummaryProjector
      |                                         | (cache miss)
      |                                         v
      +------------------------------> buildSummary() -> ConditionSummaryCopy
                                            |
                                            v
                              Cache put & broadcast event
                                            |
                                            v
                                 Player clients + analytics
```

## Cache Strategy & Invalidation Hooks
- **Key format:** `condition_timer_summary:{group_id}`.
- **TTL:** Five minutes balances performance and freshness. Manual refreshes can be triggered any time a consumer needs the latest payload.
- **Invalidation triggers:**
  - `MapTokenController@store`, `@update`, and `@destroy` after timer changes.
  - `MapTokenConditionTimerBatchController` actions post-apply/cancel/clear.
  - `TurnScheduler` whenever automated countdowns complete during `processTurn`.
- **Manual overrides:** Call `forgetForGroup($group)` when building bulk admin workflows that should rehydrate later in a request lifecycle.
- **Edge cases:** When a token loses all timers the rebuild trims the entry set so empty payloads never surface stale data.

## Privacy & Redaction Rules
- Hidden or enemy faction tokens redact exact round counts (`rounds` becomes `null`) and emit qualitative descriptors via `rounds_hint`.
- Token labels pass through `resolveTokenLabel`, which swaps to neutral codenames for obscured entries while retaining faction-appropriate disposition metadata.
- Always rely on the projector output instead of ad hoc queries when rendering player-facing timers to guarantee these boundaries stay intact.

## Failure Modes & Telemetry Expectations
| Scenario | Behaviour | Telemetry / Alerting |
| --- | --- | --- |
| Cache backend unavailable | Falls back to rebuilding in-memory and logs via `condition_timer_summary_cache_miss`. Configure Laravel logging to route `condition_timer_summary_cache_miss` spikes to ops alerts. |
| Broadcast dispatch fails | Event dispatch exceptions bubble to the caller; controllers already wrap the refresh in user-friendly redirects. Surface alerts by watching `condition_timer_summary_refreshed` without a matching websocket emit in the telemetry pipeline. |
| Stale cache detected | Any consumer may call `refreshForGroup` (e.g., via admin button). Record the manual refresh in analytics (see Task 44 instrumentation) to track churn. |
| Narrative copy lookup missing | `ConditionSummaryCopy::for` falls back to a neutral string; log via `condition_summary_copy_missing` (emit from helper when coverage expands). |

## Analytics & Narrative Integration Points
- **Analytics hooks (Task 44):**
  - Emit `timer_summary.viewed` whenever a summary payload renders, including `entries_count`, `source` (`dashboard`, `mobile_recap`, `player_summary_panel`), and `staleness_ms` measured against `generated_at`.
  - Emit `timer_summary.refreshed` with `trigger` (`token_mutation`, `batch_adjustment`, `turn_process`, `manual`) inside the projector right after a successful refresh.
  - Wire both events through Laravel's event/listener pipeline so they can be queued without blocking UI threads.
- **Narrative hooks (Task 43 & 45):**
  - Reference the copy deck keys defined in `docs/narrative/condition-timer-copy-deck.md`. Each condition section provides urgency-tier snippets and spoiler guidance.
  - When adding new conditions, update both the copy deck and the `ConditionSummaryCopy` helper to keep the taxonomy aligned.
  - Regression guard: `tests/Unit/ConditionSummaryCopyTest` fails fast if any supported condition is missing calm/warning/critical copy.

## Testing & QA Checklist
- **Unit coverage:** Extend `tests/Unit/ConditionTimerSummaryProjectorTest` with new cases before altering summarization logic (e.g., hidden faction hint behaviour, cache TTL updates).
- **Feature coverage:** Map token controller tests should assert that refresh jobs dispatch for create/update/delete flows; batch controller tests cover optimistic syncing.
- **Manual QA:**
  - Verify cached payload timestamps tick forward after each batch adjustment.
  - Toggle between GM and Player roles to confirm redaction rules.
  - Simulate cache eviction (e.g., `php artisan cache:clear`) while viewing the dashboard to ensure the UI recovers gracefully.
- **Regression suite alignment:** Document new cases in the shared QA tracker so future projection consumers inherit the same acceptance gates.

## Onboarding Notes
- Form Requests that guard timer payloads: `MapTokenConditionTimerBatchRequest`, `MapTokenStoreRequest`, and `MapTokenUpdateRequest`. Review their validation rules before modifying timer schemas.
- Realtime consumers subscribe to `ConditionTimerSummaryBroadcasted`—consult `resources/js/Pages/Groups/ConditionTimerSummary.tsx` and the mobile recap widget before adding new channels.
- Future clients (mobile apps, exports) should ingest the cached JSON endpoint rather than recalculating timers to avoid leaking GM-only data.

## Extending the Pattern
- When building new projections (e.g., status effect histories or AI prompts), mirror this layout: cache facade + builder method + broadcast event + analytics hooks.
- Keep projection classes stateless; inject collaborators through the constructor so they can be swapped in tests.
- Document new projection utilities under `docs/projections/` and link them from the onboarding section of the root `README.md`.
