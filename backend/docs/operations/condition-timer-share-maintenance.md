# Condition Timer Share Maintenance Digest

The share maintenance digest surfaces groups whose condition timer share links require follow-up so facilitators can keep transparency promises intact.

## Snapshot Sources
- **Share state:** `ConditionTimerShareMaintenanceService` wraps `ConditionTimerSummaryShareService::describeShareState()` to detect expiring or redacted links.
- **Consent health:** Pending player consent is pulled from `ConditionTimerShareConsentService::currentStatuses()` to prevent sharing recaps with revoked players.
- **Quiet-hour activity:** Access events within the last `condition-transparency.maintenance.access_window_days` are scanned for the `quiet_hour_suppressed` flag so we can spot nocturnal lurkers that may need messaging tweaks.

## Attention Criteria
- Expiring within `condition-transparency.maintenance.expiry_attention_hours`.
- Already expired or redacted.
- Quiet-hour access ratio exceeding `condition-transparency.maintenance.quiet_hour_attention_ratio`.
- Any player without granted consent for the current visibility level.

## Interfaces
- `GET /groups/{group}/condition-transparency/maintenance` returns the latest snapshot for Inertia dashboards.
- `php artisan condition-transparency:share-maintenance [groupId]` prints a table of attention items for operations reviews.
- `SendConditionTimerShareMaintenanceDigestJob` may be scheduled to log or notify when a group needs attention; hook it into your preferred notification channel.

## Configuration
Tweak thresholds inside `config/condition-transparency.php` under the `maintenance` key. Values default to a 7-day window, 40% quiet-hour ratio, and a 24-hour expiry warning.
