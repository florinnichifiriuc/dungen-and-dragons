# Task 48 – Condition Timer Chronicle Integration

## Intent
Ensure condition timer acknowledgement trails and adjustment timelines surface everywhere storytellers and players reference a session recap, including Markdown/PDF exports and facilitator briefing panels.

## Background & Alignment
- Extends Tasks 46–47 by pushing acknowledgement and chronicle data into downstream surfaces.
- Supports transparency initiative by keeping exported artefacts and briefing experiences synchronized with live dashboards.
- Must preserve player-safe masking rules while giving facilitators richer context.

## Success Criteria
- Session export service returns condition timer summaries with acknowledgement/timeline hydration for the requesting viewer role.
- Markdown export renders an “Active Condition Outlook” section with acknowledgement indicators, timelines, and condition narratives.
- PDF export mirrors the outlook plus chronicle details with facilitator-only context gated by permissions.
- Feature coverage verifies outlook + chronicle content is present in Markdown exports and respects acknowledgement detail.
- Progress artefacts updated.

## Implementation Notes
1. Inject summary projector and acknowledgement service into `SessionExportService`; hydrate summaries alongside chronicle exports.
2. Extend Markdown generator with helper formatters to print outlook entries, acknowledgements, and condensed timelines before the chronicle section.
3. Refresh Blade export template with new outlook + chronicle cards, including facilitator-only context dumps.
4. Add feature test seeding a token, adjustments, and acknowledgement to confirm Markdown output contains outlook and chronicle data.
5. Update documentation (TASK_PLAN, PROGRESS_LOG) to mark completion.

## Status
Completed
