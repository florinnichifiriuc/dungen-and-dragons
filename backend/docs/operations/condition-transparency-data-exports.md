# Condition Transparency Data Exports – Integration Guide

**Last updated:** 2025-11-11 (UTC)

## Overview

The condition transparency export pipeline allows facilitators to request CSV or JSON snapshots of timer summaries, acknowledgement trails, chronicle history, and share access telemetry. Requests are queued, hydrated by projection services, and stored on the configured disk before optional email, Slack, and webhook notifications are dispatched.

## Request Flow

1. Facilitators trigger an export via `POST /groups/{group}/condition-transparency/exports`. Validation restricts `format` to `csv|json`, `visibility_mode` to `counts|details`, and an optional ISO8601 `since` filter.
2. `ConditionTransparencyExportService::requestExport` persists the request, defaults missing values from configuration, and enqueues `ProcessConditionTransparencyExportJob`.
3. The job marks the export as processing, builds the dataset, writes it to storage (`CONDITION_TRANSPARENCY_EXPORT_DISK`/`PATH`), marks completion, and notifies the requester plus any configured Slack channel. Failures increment retry counters, capture a reason, notify the requester, and bubble the exception for retry.
4. Completed exports can be downloaded through `GET /groups/{group}/condition-transparency/exports/{export}/download`, which enforces group authorization and only serves completed files.

## Dataset Contents

`ConditionTransparencyExportService::buildDataset` assembles a structured payload:

- `summary.entries`: Projected timer summaries with urgency, rounds remaining, and narrative copy. In `counts` mode timelines and detailed notes are stripped before CSV rendering.
- `acknowledgements`: Filtered acknowledgement history scoped to consenting players and optional `since` timestamp.
- `chronicle`: Timer chronicle entries with optional detail expansion when `visibility_mode` is `details`.
- `consents`: Snapshot of current share consent statuses to help compliance teams validate export scope.
- `share_access`: Active share state, last eight characters of the token for traceability, rolling access trend data, and immutable access trails.

CSV exports render each token/condition row with headers `Token`, `Condition`, `Urgency`, `Rounds Remaining`, and `Summary`. JSON exports preserve the nested payload above.

## Webhooks

Groups may register webhook endpoints via `POST /groups/{group}/condition-transparency/webhooks`. Secrets are generated server-side and can be rotated or revoked. The service enforces a configurable minimum interval between invocations and signs each request with `HMAC-SHA256` using the shared secret in the `X-Condition-Transparency-Signature` header.

Webhook payload example:

```json
{
  "export_id": 42,
  "group_id": 7,
  "format": "csv",
  "visibility_mode": "counts",
  "file_path": "exports/condition-transparency/group-7_export-42_20251111_142000.csv",
  "download_url": "https://app.example.com/groups/7/condition-transparency/exports/42/download",
  "generated_at": "2025-11-11T14:20:00Z"
}
```

Receivers should verify the signature, persist the download URL, and fall back to polling if consecutive failures exceed configured tolerances. The webhook model tracks call counts, consecutive failures, and last trigger timestamps for operational insight.

## Governance & Access Controls

- Only facilitators (group `update` policy) can request or download exports. Authorization is enforced at the controller and form request layer.
- Export visibility defaults to consent-friendly `counts`. `details` mode requires all players to opt-in before sensitive notes are included; acknowledgement exports respect the consent roster.
- Storage disk, path, format, and webhook throttles are configurable via environment variables listed in `config/condition-transparency.php`. Document any overrides in ops runbooks when adjusting retention policies.

## Notifications

The requester receives an email with download CTA once processing completes; optional Slack notifications fire when `CONDITION_TRANSPARENCY_SLACK_WEBHOOK` is set. Failures trigger a separate email and preserve the exception message for debugging.

## Troubleshooting

1. **Webhook endpoint unavailable:** Consecutive failures are tracked; rotate secrets or deactivate the webhook to pause delivery, then replay by re-requesting the export.
2. **Missing files on download:** Ensure `CONDITION_TRANSPARENCY_EXPORT_DISK` has the export path. The download action checks file existence and returns 404 if storage drifted.
3. **Malformed `since` filter:** Invalid timestamps are ignored by `resolveSinceFilter`, so validation should catch format issues; logs note the omission without failing the export.

## Integration Checklist

- [ ] Confirm facilitator accounts have `update` access before wiring automation.
- [ ] Register webhooks with unique secrets per environment and store them in your secrets manager.
- [ ] Choose `counts` visibility for redacted datasets; upgrade to `details` only after confirming player consent coverage.
- [ ] Monitor the export queue; long-running jobs surface in the notification center if they fail.
- [ ] Archive exported files per your organization's retention policy; exports are immutable once generated.
