# Condition Transparency Beta Readiness Checklist

_Last updated: 2025-11-10_

## Automated Coverage
- [x] Feature tests validate share access logging, extension stewardship, and redaction guardrails.
- [x] Aggregated trend payloads surface in the share manager panel and exports for the trailing seven-day window.
- [x] Offline acknowledgement queue regression suite runs after each `condition-timers` deployment.
- [x] `npm run lint` executes ESLint + jsx-a11y checks against timer transparency surfaces.

## Load & Performance Scripts
- [x] `php artisan condition-transparency:ping` synthetic monitor drives hourly share link probes.
- [ ] k6 scenario for digest batching warmed on staging (scheduled for Week 8).

## Manual QA Scenarios
- [x] Guest opens share link from mobile device, verifies etiquette copy and staleness cues.
- [x] Facilitator extends an active share and confirms audit log entry + access trend bump.
- [x] Expired share (>48h) renders cloaked payload with guidance banner.
- [x] Localization spot-check (Elvish + Romanian) for share manager tooltips.

## Accessibility & Privacy
- [x] Screen reader landmarks validated via axe-core for guest share screen.
- [x] IP and agent hashes salted with app key to preserve anonymity.
- [ ] Telemetry opt-out flow update pending analytics workstream.

## Release Sign-off
- [x] Synthetic monitors wired into incident channel with 3m alert threshold.
- [ ] Focus group beta debrief scheduled (Nov 09) to capture qualitative notes.
- [ ] Release readiness report drafted for leadership review.
