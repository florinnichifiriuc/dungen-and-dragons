# Dungeon & Dragons Campaign Platform Plan

## 1. Vision
Build a collaborative, persistent Dungeon & Dragons campaign platform where interconnected worlds evolve through scheduled and live sessions, turn-based time advances, and coordinated Dungeon Masters. Target users include tabletop RPG groups (remote or in-person), shared-world organizations managing multiple parties, and Game Masters seeking tooling for session prep, execution, and asynchronous storytelling.

## 2. Top 10 Features
1. Campaign/world hierarchy with granular permissions and cross-group collaboration.
2. Configurable world time engine advancing every configurable interval (6h/24h) to drive events.
3. Session planning, live session log, and export to PDF/Markdown with post-session map updates.
4. Character, NPC, monster, and item encyclopedias with custom fields and AI-assisted NPC control.
5. Interactive map board with regional assignments, pins, layers, fog-of-war (stretch), and cross-group contributions.
6. Initiative tracker with turn history, condition tracking, and time-engine integration.
7. Dice roller supporting advanced expressions and shared results with audit trail.
8. Real-time updates for initiative, dice, chat, and DM coordination via Laravel Reverb and optional Discord bridge.
9. Role-based access (GM/Player/Observer) with invitations for individuals or groups and DM regional handoff/AI takeover (Ollama Gemma3).
10. Task board and progress tracking for campaigns and development tasks, mirrored in repository files.

## 3. Domain Model
Entities & relationships:
- User (1:M) Membership (pivot linking users to Campaign with role(s), status, and group).
- Group aggregates users and may own multiple campaign memberships; supports join requests.
- Campaign (M:1) World; Campaign (1:M) Session, Note, Map, Item, Monster, Quest, TimeTurn.
- Session (1:M) SessionLogEntry, DiceRoll, InitiativeEntry, SessionRecording (file or link).
- Character belongs to Campaign; optional linked User or AI Controller (Gemma3 prompt config).
- Monster, Item, Location, NPC specialized Entities with tags and optional AI behavior configs.
- Map (1:M) MapLayer, MapPin, MapFogSegment, MapRegionAssignment (ties to DMs/groups).
- TimeTurn records world time increments, triggers events, and links to sessions or logs.
- DiceRoll references Session and Roller (User/Character/AI agent).
- Attachment polymorphic to Campaign, Session, Character, etc.
- AccessRule per resource; DMCommunicationThread for DM chat/notes.
- Task (backlog) linked to Campaign/World, progress statuses.

Text ERD:
```
World 1─* Campaign
Campaign 1─* Session
Campaign 1─* TimeTurn
Campaign 1─* Character / Item / Monster / Map / Task
User *─* Campaign through Membership (with role + group_id + region assignment)
User *─* Group (GroupMembership)
Group 1─* Membership
Session 1─* SessionLogEntry / DiceRoll / InitiativeEntry / SessionRecording
Map 1─* MapLayer / MapPin / MapFogSegment / MapRegionAssignment
Attachment (polymorphic) -> {Campaign, Session, Character, Item, Monster, Map}
DMCommunicationThread 1─* DMMessage (participants: DMs via memberships)
```

## 4. Database Schema
- `users`: id (PK), name, email (unique), password, avatar_url, timezone, locale, oauth_provider/id, email_verified_at, remember_token, allow_dm_roles boolean, timestamps, soft deletes.
- `groups`: id, name, slug (unique), description, owner_id FK users, visibility enum, default_role, timestamps.
- `group_memberships`: id, group_id FK, user_id FK, role (owner/admin/member), joined_at, timestamps, unique (group_id,user_id).
- `worlds`: id, owner_id FK users, name, description, visibility enum, time_increment_minutes (default 1440), last_advanced_at, timestamps, soft deletes.
- `campaigns`: id, world_id FK, slug unique, title, summary, status enum, default_timezone, start_date, allow_ai_dm boolean, timestamps, soft deletes.
- `memberships`: id, campaign_id FK, user_id FK nullable (for pending invites), group_id FK nullable, role enum (gm/player/observer/both), region_id nullable, invited_by, invite_token, accepted_at, last_seen_at, timestamps, unique (campaign_id,user_id) with null filter.
- `dm_regions`: id, world_id FK, name, description, map_id FK nullable, boundaries_geojson, timestamps.
- `map_region_assignments`: id, map_id FK, region_id FK nullable, membership_id FK nullable (assigned DM), group_id FK nullable, active boolean, assigned_at, unassigned_at.
- `sessions`: id, campaign_id, title, scheduled_for datetime UTC, duration_minutes, location, conducted_mode enum (in_app/in_person/hybrid), status enum, summary, gm_notes text, export_markdown longtext, requires_map_update boolean default true, timestamps, soft deletes.
- `session_recordings`: id, session_id, storage_path or external_url, type enum (audio/video/notes), recorded_at, uploaded_by, ai_summary json nullable.
- `session_log_entries`: id, session_id, author_id nullable, entry_type, content json, occurred_at, turn_id FK TimeTurn nullable, timestamps, index on (session_id, occurred_at).
- `time_turns`: id, campaign_id, world_time datetime, increment_minutes, summary, triggered_by enum (system/dm/session), notes json, created_by FK membership nullable, created_at.
- `characters`: id, campaign_id, user_id nullable, controlled_by enum (user/ai/group), ai_profile json, name, species, class, level, attributes json, backstory, portrait_path, is_npc boolean, timestamps, soft deletes, index (campaign_id,name).
- `monsters`: id, campaign_id nullable, world_id nullable, name, stat_block json, challenge_rating, source, tags, ai_profile json, timestamps.
- `items`: id, campaign_id nullable, world_id nullable, name, type, rarity, description, properties json, timestamps.
- `maps`: id, campaign_id, title, image_path, grid_size, width_px, height_px, fog_enabled boolean, shared_scope enum (campaign/world/global), timestamps.
- `map_layers`, `map_pins`, `map_fog_segments` as before with campaign visibility.
- `initiative_entries`: id, session_id, entity_type, entity_id nullable, name, initiative_value, hp_current, hp_max, conditions json, turn_order int, active boolean, linked_turn_id FK time_turns nullable, timestamps.
- `dice_rolls`: id, session_id nullable, campaign_id, roller_type (user/character/ai), roller_id, expression, result_total, breakdown json, visibility enum, rolled_at, turn_id nullable.
- `notes`: id, notable_type/id, author_id, title, body, is_private, tags, timestamps, soft deletes.
- `attachments`: id, attachable_type/id, uploader_id, file_path, mime_type, size, thumbnails json, visibility, timestamps.
- `access_rules`: id, rule_type, subject_type/id, membership_id nullable, group_id nullable, role nullable, permissions json, timestamps.
- `dm_communication_threads`: id, world_id FK, topic, created_by membership_id, channel enum (internal/discord), discord_channel_id nullable, timestamps.
- `dm_messages`: id, thread_id, membership_id, content text/json, sent_at, ai_generated boolean, timestamps.
- `tasks`: id, campaign_id nullable, world_id nullable, title, description, status enum (todo/in_progress/done/blocked), assignee_membership_id nullable, due_turn_id nullable, tags, timestamps.
- `activity_logs`: as before.

Add indexes for `time_turns` on `(campaign_id, world_time)` and `sessions` on `(campaign_id, scheduled_for)`.

## 5. Auth & Roles
- Roles: GM, Player, Observer, plus hybrid GM+Player; membership stores combined role. Users can toggle availability for DM assignments.
- Group invitations: allow DM/players to join as group or individual; on invite acceptance choose to create/join group.
- Auth stack: Laravel Breeze API + Sanctum; Socialite for OAuth (Google, Discord). Group invites use signed URLs; DM assignment requires GM role.
- DM reassignment flow: when DM leaves, campaign owners can transfer region to another DM or trigger AI DM (Gemma3 via Ollama) as fallback. Store AI DM configuration per region.

## 6. API & Routes
- REST API with `/api` prefix; React SPA consumes via React Query.
- Additional endpoints:
  - `POST /groups` / `GET /groups/{id}` / `POST /groups/{id}/members` (manage group joins).
  - `POST /worlds/{id}/advance-time` body `{increment_minutes, reason}` -> creates `time_turns` entry and broadcasts update.
  - `GET /campaigns/{id}/time-track` returns `time_turns`, next scheduled turn.
  - `POST /campaigns/{id}/dm-assignments` -> assign membership to region.
  - `POST /dm-threads` and `/dm-threads/{id}/messages` for DM communication.
  - `POST /sessions/{id}/recordings` upload + metadata; requires map update confirmation via `POST /sessions/{id}/complete` {map_updated:boolean}.
  - `POST /campaigns/{id}/ai/roll-npc` -> triggers AI NPC response.
  - `POST /campaigns/{id}/tasks` etc. Task endpoints for backlog tracking.
  - `POST /ai/dm/takeover` -> configure region for AI DM.
  - `POST /sessions/{id}/time-sync` -> align initiative round with `time_turns`.
- Validation includes ensuring increments multiples of world default, `conducted_mode` in allowed set, `group_id` exists for invites.

## 7. React UI Map
- Additional pages/components:
  - `/groups` (GroupDirectory, GroupDetail, GroupInviteWizard).
  - `/worlds/:id/time` (WorldTimeboard showing turns, next events, controls to advance time).
  - `DMRegionBoard` component for managing region assignments and AI fallback toggles.
  - `DMChatPanel` integrates internal chat (WebSocket) and optionally links to Discord channel if configured.
  - `TaskBoard` (kanban) for tasks/backlog.
  - `AIControlCenter` to configure Gemma3 prompts for NPCs/DM fallback.
- State management: React Query for server state, Zustand for UI state; integrate websocket store for DM chat/time updates.
- MapBoard ensures post-session update workflow (flag unsynced until DM confirms map update).
- Session view includes `TimeTurnTimeline` and `RecordingUpload` components.

## 8. Real-time (Laravel Reverb)
- Channels expanded:
  - `presence-world.{id}` for DMs to coordinate, share time-turn updates.
  - `private-world.{id}.time` for time advancement events.
  - `presence-group.{id}` for group chat/invites.
  - `private-region.{id}` for region-specific DM discussions and AI DM notifications.
- Events:
  - `WorldTimeAdvanced` {world_id, time_turn}.
  - `RegionAssignmentUpdated` {region_id, membership_id?, ai_active}.
  - `DMMessageSent` for DM chat.
  - `AIDMActionSuggested` when Gemma3 proposes outcomes for NPC control or DM takeover.
  - Others as previously defined (SessionLogCreated, InitiativeUpdated, DiceRolled, MapPinUpdated).
- Discord integration via scheduled sync or webhooks if enabled.

## 9. Files & Media
- Storage as before; include directories for session recordings (`recordings/{campaign_id}/`).
- AI assets (prompt templates) stored as json but not accessible publicly.
- Generate map change snapshots post-session (before/after) for audit.
- Enforce video/audio upload limits (2 GB) with chunked uploads; transcode via queue if needed.

## 10. Dice Engine
- Same parser approach; integrate optional AI explanation: after evaluation, optionally request Gemma3 to narrate outcome for NPC-controlled rolls.
- Additional test cases linking to time turns (e.g., ensure timestamp and turn association recorded).

## 11. Search & Filters
- Expand search to include groups, regions, time turns, and tasks.
- Index `groups.slug`, `dm_regions.name`, `tasks.status`, `time_turns.world_time`.
- Provide filters for world timeline (by date range, triggering event) and group membership.

## 12. Access Control
- Policies incorporate group-level overrides: group admins can manage their members within campaigns.
- Time advancement restricted to GMs assigned to regions or world admins.
- DM chat accessible only to GM-role memberships; AI DM operations require owner/admin role.
- Players can view but not edit map regions unless granted via AccessRule.

## 13. Testing Plan
- Pest unit tests for time engine service (advance increments, event generation) and AI integration stubs.
- Feature tests for group invitations, DM reassignment, AI DM fallback activation, and map update confirmation on session completion.
- API tests ensuring session recordings require `requires_map_update` resolution.
- Playwright flows: group joins campaign, DM advances time, AI NPC responds in session log.
- Contract tests for Ollama Gemma3 integration via HTTP mocks.

## 14. Performance
- Cache latest `time_turns` per campaign/world; stream updates via WebSockets.
- Batch load group memberships and region assignments to avoid N+1 queries.
- Paginate DM chat and tasks; use Redis for rate limiting AI requests.

## 15. Security
- CSRF via Sanctum as before.
- Rate limit AI endpoints and DM chat to prevent abuse.
- Validate time increments, ensure map uploads sanitized, restrict recording MIME types.
- Audit DM reassignment actions.

## 16. Seeders & Factories
- Extend seeders to create multiple groups, assign DMs to regions, and populate world time turns.
- Include sample AI DM configuration (placeholder prompts) and DM chat history.
- Demo sessions flagged as in_app/in_person with sample recordings (links) and map updates.

## 17. Migrations Plan
1. Core auth tables (users, groups, group_memberships).
2. Worlds, campaigns, dm_regions.
3. Memberships with group linkage and region assignments.
4. Time engine tables (`time_turns`).
5. Sessions, session_log_entries, session_recordings.
6. Characters, monsters, items with AI profiles.
7. Maps and related tables including map_region_assignments.
8. Initiative entries, dice rolls with turn linkage.
9. Notes, attachments, DM communication threads/messages.
10. Tasks, activity logs, indexes, soft-delete adjustments.

## 18. DevOps
- Local: Laravel Sail with services plus Ollama container (Gemma3) accessible via HTTP; env var `OLLAMA_ENDPOINT`.
- Configure Discord webhook integration optional via `DISCORD_BOT_TOKEN`.
- Queues for AI processing, exports, media transcoding; separate Horizon dashboard.
- Production: Dockerized services including dedicated Ollama instance (GPU optional); ensure scaling for Reverb and queue workers.
- Backups include recordings storage; implement lifecycle policies for large media. Logging includes time-turn and AI DM actions.

## 19. Timeline (6 Weeks)
- **Week 1 – Foundations & Groups:** Setup repo, CI/CD, auth, groups, worlds, campaigns, base plan files. Acceptance: users create group, world, campaign.
- **Week 2 – Time Engine & Memberships:** Implement time-turn service, memberships with roles/groups, DM region assignments. Acceptance: GM advances time and assigns region.
- **Week 3 – Core Gameplay Entities:** Sessions, characters, items, monsters with AI profile stubs, dice roller, initiative integration with time turns. Acceptance: session logs tie to turns.
- **Week 4 – Collaboration & Communication:** DM chat threads, Discord bridge, group invitations, AI NPC/DM fallback scaffolding. Acceptance: DMs coordinate via internal chat, AI DM respond stubbed.
- **Week 5 – Maps & Recording:** Map board enhancements, post-session update workflow, session recordings upload/export, world map expansion across groups. Acceptance: session recorded, map updated confirmation enforced.
- **Week 6 – Polish & Tracking:** Search, tasks board, progress tracking files, testing suite, i18n scaffold. Acceptance: tasks tracked, tests passing, docs updated.

## 20. Risks & Cut-Scope
- **AI integration complexity:** If Gemma3 setup delays, fall back to scripted NPC behavior while keeping interface.
- **Large media storage:** Limit recording duration, fallback to external links if storage not ready.
- **Cross-group coordination overhead:** Start with simple DM chat; defer Discord bridge if necessary.

Cut-scope: postpone AI DM takeover automation, advanced map fog-of-war, or group-based map editing workflow while keeping core time engine and collaboration features.

## 21. Nice-to-Haves
- Automated AI session summaries per turn.
- Shared calendar view merging time turns and scheduled sessions across campaigns.
- Integration with VTT exports and fog-of-war enhancements.
- AI NPC personality profiles with quick prompt presets.

## Testing
⚠️ No automated tests executed (planning-only document).
