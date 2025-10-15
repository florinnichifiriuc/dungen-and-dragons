# Task 17 – Lore Codex

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 4 (Campaign Management), Task 6 (Session Workspace)
**Related Backlog Items:** Campaign entity codex & lore surfacing

## Intent
Introduce a campaign-level lore codex that lets facilitators chronicle characters, NPCs, monsters, relics, and locations tied to a campaign. Provide CRUD workflows, authorization, and Inertia pages so managers can curate lore while players can browse entries that match their permissions.

## Subtasks
- [x] Design the campaign entity schema, model, policy, and migrations.
- [x] Implement controller actions, routes, and validation requests for managing codex entries.
- [x] Build Inertia pages for listing, viewing, creating, and editing lore entries with stat block editors.
- [x] Wire codex access into the campaign dashboard with recent-entry highlights.
- [x] Cover entity lifecycle behaviours with Pest feature tests.

## Notes
- Campaign entities support five archetypes (character, npc, monster, item, location) with configurable visibility levels (GM, party, public).
- Stat blocks store structured label/value pairs for quick initiative or encounter reference.
- The policy leans on campaign authorization so only managers can create/update while members can browse per visibility rules.

## Log
- 2025-10-22 07:40 UTC – Finalized schema/UX plan for the lore codex, drafted migration and UI checklist.
- 2025-10-22 10:20 UTC – Delivered migrations, controller, Inertia pages, dashboard integration, and tests.
