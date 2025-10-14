# Vision
Build a collaborative, browser-based Dungeon & Dragons campaign platform for distributed adventuring groups who want to co-create persistent worlds, manage region-based responsibilities, and coordinate time-sliced turns. Target users include tabletop RPG communities where multiple Dungeon Masters (DMs) and players collaborate asynchronously or live, with optional AI-driven NPC facilitation and cross-group world expansion.

## Top 10 Features
1. World & campaign hierarchy with configurable time turns (default 6h) and automation rules.
2. Multi-group world map expansion with regional DM assignments and shared lore.
3. Session planning/running workspace with agenda, notes, dice, initiative, and map embeds.
4. Real-time initiative tracker, dice log, chat via Laravel Reverb plus optional Discord relay.
5. Modular tile-based map board (Catan/Saboteur-style) with snap-fit terrain tiles, uploads, region boundaries, fog-of-war toggle, and AI NPC helpers.
6. Session export to Markdown/PDF with turn summaries and map deltas.
7. Permissions: GM, Player, Observer roles plus group-level roles; invitations for users or groups.
8. Global search/filter across campaigns, entities, tasks, and session notes.
9. AI integrations: Ollama Gemma3 DM fallback, NPC conversation assistant, automated summaries.
10. Localization-ready UI (en now, ro later) with dark/light modes using Tailwind + shadcn.

## Platform Workspace Setup (Week 1 Kickoff)
- **backend/** – Laravel 12 application hosting both the API and the React UI through Inertia.js. Includes Sail, Sanctum, Pest 4, Tailwind, shadcn-ready tokens, and Vite React tooling. Copy the env file (`cp backend/.env.example backend/.env`), install dependencies (`cd backend && composer install && npm install`), generate an app key, then compile assets with `npm run build` or run `npm run dev` for hot reload via Vite.
- Shared tooling: `.editorconfig`, root `.gitignore`, Tailwind design tokens for the brand palette, and progress/task logs under `Tasks/`.
- See `Tasks/Week 1` for detailed implementation notes and remaining checklist items (auth, group foundations next).

## Architectural Choice
**Laravel + Inertia React Monolith**: Recommended after weighing feedback from Laravel and React experts.
- *Laravel Expert POV*: Inertia keeps routing, middleware, validation, and authentication in a single Laravel codebase, simplifying Sanctum setup, SSR-friendly PDF exports, and queue-driven turn automation. It reduces deployment surface area and makes policy testing straightforward.
- *React Expert POV*: React via Inertia still delivers component-driven UI, Tailwind + shadcn styling, and gradual enhancement. Because Vite compiles assets inside Laravel, we avoid cross-repo synchronization while retaining TypeScript, Zustand, and modular feature bundles.
- *Decision*: adopt Inertia-first delivery now for speed, with the REST API kept in the same app (under `/routes/api.php`) so a future standalone SPA/mobile client can still be built if the project scales beyond Inertia.

## Domain Model & ERD
**Entities**: User, Profile, Group, Campaign, World, Region, Session, Character, NPC, Monster, Item, Location, Map, MapTile, TileTemplate, Token, Note, DiceRoll, InitiativeEntry, Invitation, RoleAssignment, GroupMembership, Attachment, Tag, AuditLog, Turn, Task, ChatMessage, AIRequest.

**Relationships**:
- World 1—* Campaign; World 1—* Region.
- Campaign *—* Group via RoleAssignment (role: gm/player/observer; scope region/campaign).
- Group *—* User via GroupMembership (role: dm/player/both).
- Session belongs to Campaign; Session 1—* Note, DiceRoll, InitiativeEntry, TaskUpdate.
- Region 1—* Map; Map 1—* MapTile & Token.
- Turn belongs to Campaign (records start/end, processed_by, summary, map_changes).
- Attachments & Tags polymorphic to entities.
- AIRequest polymorphic (NPC convo, DM takeover) referencing models and Ollama responses.
- TileTemplate can belong to World (shared palette) or global library; MapTile references template for consistent visuals and rules.

```
[World]1---* [Region]1---* [Campaign]
    |             |             *---* [Group]
    |             |                     |
    |             |                     *---* [User]
    |             *---* [Turn]
    |             *---* [Task]
[Campaign]1---* [Session]1---* [Note]
[Campaign]1---* [Map]1---* [MapTile] *--- [TileTemplate]
[Campaign]1---* [Map]1---* [Token]
[Campaign]1---* [EntityVariant]
```

## Database Schema
- `users` (uuid pk, name, email unique, password, avatar_path, locale default 'en', timezone, is_ai_proxy boolean, email_verified_at, remember_token, timestamps, softDeletes)
- `profiles` (user_id pk fk, bio, pronouns, gm_experience text, discord_handle)
- `oauth_providers` (id, user_id fk, provider, provider_id unique(provider, provider_id), tokens json, refresh_token, expires_at, timestamps)
- `groups` (uuid, name, description, visibility enum['private','shared'], owner_id fk users, default_role enum['player','dm','both'], timestamps, softDeletes)
- `group_memberships` (uuid, group_id fk, user_id fk, role enum['player','dm','both'], status enum['pending','active','suspended'], joined_at, timestamps, unique(group_id,user_id))
- `worlds` (uuid, owner_id fk users, title, description markdown, slug unique, visibility enum['private','shared'], default_turn_hours integer default 24, timestamps, softDeletes)
- `regions` (uuid, world_id fk, title, description, map_bounds json, assigned_group_id fk groups nullable, assigned_dm_id fk users nullable, status enum['active','vacant','ai'], timestamps)
- `campaigns` (uuid, world_id fk, region_id fk nullable, title, synopsis, slug unique, status enum['planning','active','completed','archived'], default_timezone, start_date, end_date, turn_hours integer nullable, timestamps, softDeletes, index world_id)
- `role_assignments` (uuid, campaign_id fk, group_id fk nullable, user_id fk nullable, role enum['gm','player','observer'], scope enum['campaign','region','world'], status enum['active','pending','revoked'], invited_by fk users nullable, timestamps, unique(campaign_id,coalesce(group_id,user_id)))
- `invitations` (uuid, campaign_id fk, group_id fk nullable, email nullable, role enum, token unique, expires_at indexed, invited_by fk users, accepted_at, timestamps)
- `turns` (uuid, campaign_id fk, number integer, window_start datetime(6) UTC, window_end datetime(6) UTC, processed_at datetime(6) nullable, processed_by fk users nullable, ai_processor_id fk users nullable, summary markdown, map_delta json, timestamps, unique(campaign_id, number))
- `sessions` (uuid, campaign_id fk, turn_id fk nullable, title, agenda markdown, session_date datetime(6) UTC index, duration_minutes, location, summary markdown, recording_url, export_markdown cached text, timestamps, softDeletes)
- `tasks` (uuid, campaign_id fk, assigned_group_id fk nullable, assigned_user_id fk nullable, title, description markdown, status enum['todo','in_progress','blocked','done'], due_turn_id fk turns nullable, created_by fk users, timestamps)
- `task_updates` (uuid, task_id fk, user_id fk, note markdown, status enum, created_at)
- `notes` (uuid, session_id fk nullable, campaign_id fk, author_id fk, visibility enum['gm','players','public'], content markdown, pinned boolean, created_turn_id fk turns nullable, timestamps, softDeletes, index (campaign_id, visibility))
- `dice_rolls` (uuid, session_id fk nullable, campaign_id fk, roller_id fk, expression, result_json json, total integer, turn_id fk nullable, created_at index)
- `initiative_entries` (uuid, session_id fk, entity_type string, entity_id uuid nullable, name, dex_mod tinyint, initiative integer, current boolean, order_index, timestamps)
- `maps` (uuid, campaign_id fk, region_id fk nullable, session_id fk nullable, title, base_layer enum['hex','square','image'], image_path nullable, width int, height int, fog_data json nullable, gm_only boolean, timestamps, softDeletes)
- `tile_templates` (uuid, world_id fk nullable, key string unique, name, terrain_type, movement_cost tinyint, defense_bonus tinyint, image_path, edge_profile json (for snap rules), created_by fk users, timestamps)
- `map_tiles` (uuid, map_id fk, tile_template_id fk, q int, r int, orientation enum['pointy','flat'], elevation smallint default 0, variant json nullable, locked boolean default false, timestamps, unique(map_id,q,r))
- `tokens` (uuid, map_id fk, entity_type, entity_id, name, x int, y int, color, size, hidden boolean, gm_note text, timestamps)
- `attachments` (uuid, attachable_type, attachable_id, file_path, mime_type, size int, uploaded_by fk users, meta json, created_at)
- `tags` (uuid, campaign_id fk, label, color, slug unique(campaign_id, label))
- `taggables` (tag_id fk, taggable_type, taggable_id, created_at, primary key composite)
- `campaign_entities` (uuid, campaign_id fk, entity_type enum['character','npc','monster','item','location'], group_id fk nullable, name, alias, description markdown, stats json, owner_id fk nullable, visibility enum['gm','players'], initiative_default int nullable, ai_controlled boolean default false, timestamps, softDeletes, indexes on (campaign_id, entity_type), fulltext on name+alias)
- `ai_requests` (uuid, request_type enum['dm_takeover','npc_dialogue','summary'], context_type, context_id, prompt text, response json, status enum['pending','completed','failed'], provider enum['ollama'], model string default 'gemma3', created_by fk users nullable, created_at, completed_at)
- `chat_messages` (uuid, channel_type enum['campaign','region','dm'], channel_id, user_id fk nullable, body markdown, source enum['user','ai'], turn_id fk nullable, created_at index)
- `audit_logs` (id, user_id fk nullable, auditable_type, auditable_id, action string, old_values json, new_values json, created_at index)

All timestamps stored UTC. Soft deletes applied to major entities.

## Auth & Roles
- Auth via Laravel Sanctum SPA tokens (CSRF cookie). Email/password + OAuth (Google, Discord) via Socialite.
- Roles: `gm`, `player`, `observer`, plus group membership role `player|dm|both`. Region-level DM assignments stored on `regions` referencing users or AI proxies.
- Invitation flow: GM invites group or individual; accepts via signed token -> activates `role_assignments` and optionally auto-creates `group_memberships`.
- DMs can reassign regions; vacancy triggers notification to other DMs and offers AI takeover using Ollama Gemma3 (creates `ai_requests` DM takeover entry, spawns proxy user flagged `is_ai_proxy`).

## API & Routes
Base `/api/v1` (Sanctum auth).

Auth:
- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `GET /auth/me`
- `POST /auth/oauth/{provider}/callback`

Groups:
- `GET /groups` (filters: search, membership_status)
- `POST /groups` {name, description, visibility, default_role}
- `POST /groups/{id}/members` {user_id?, email?, role}
- `PATCH /groups/{id}/members/{membershipId}` {role,status}
- `POST /groups/{id}/join` (self-join request)

Worlds & Regions:
- `GET /worlds` query {search, turn_hours}
- `POST /worlds`
- `GET /worlds/{id}` includes campaigns, regions
- `PUT /worlds/{id}`
- `DELETE /worlds/{id}`
- `POST /worlds/{id}/regions` {title, description, map_bounds}
- `PATCH /regions/{id}` {assigned_group_id, assigned_dm_id, status}
- `POST /regions/{id}/handover` triggers DM reassignment or AI takeover
- `POST /regions/{id}/chat` {body}

Campaigns & Turns:
- `GET /campaigns` filters: world_id, region_id, group_id, status, search
- `POST /campaigns` {title, region_id?, turn_hours?}
- `GET /campaigns/{id}`
- `PUT /campaigns/{id}`
- `DELETE /campaigns/{id}`
- `POST /campaigns/{id}/invite` {group_id?, email?, role}
- `POST /campaigns/{id}/accept-invite` {token}
- `GET /campaigns/{id}/turns`
- `POST /campaigns/{id}/turns/process` {turn_number?, force?}
- `PATCH /turns/{id}` {summary, processed_by}

Sessions & Tasks:
- `GET /campaigns/{id}/sessions`
- `POST /campaigns/{id}/sessions`
- `GET /sessions/{id}`
- `PUT /sessions/{id}`
- `DELETE /sessions/{id}`
- `POST /sessions/{id}/recordings` {file}
- `POST /sessions/{id}/export` {format: pdf|md}
- `GET /campaigns/{id}/tasks`
- `POST /campaigns/{id}/tasks`
- `PATCH /tasks/{id}`
- `POST /tasks/{id}/updates`

Realtime & Chat:
- `GET /campaigns/{id}/dice-rolls`
- `POST /campaigns/{id}/dice-rolls`
- `POST /sessions/{id}/initiative`
- `PATCH /initiative/{id}`
- `POST /sessions/{id}/initiative/next`
- `GET /campaigns/{id}/chat`
- `POST /campaigns/{id}/chat`

Maps & Tiles:
- `GET /maps/{id}` (include tiles?=true for batch load)
- `GET /maps/{id}/tiles`
- `POST /maps/{id}/tiles` {tile_template_id, q, r, orientation?, elevation?, variant?}
- `PATCH /map-tiles/{id}` {tile_template_id?, elevation?, locked?, variant?, orientation?}
- `DELETE /map-tiles/{id}`
- `GET /tile-templates` (global + world-specific)
- `POST /tile-templates` {key?, name, terrain_type, movement_cost, defense_bonus, image}
- `PATCH /tile-templates/{id}`

Maps & Entities: add tile palette endpoints and axial coordinate helpers for modular boards.

Validation: enforce ISO8601 UTC for datetime fields, `turn_hours` integer between 1 and 24, tasks require title <=150 chars, tile placements require valid axial coordinates within map bounds and allowed orientation for chosen template.

## React UI Map
Routing via React Router. State: React Query for server data; Zustand for local UI (map tools, initiative). Additional context for `TurnTimerContext` to display countdown to next turn.

Pages & Components:
- `AuthLayout` (Login, Register, AcceptInvite)
- `WorldsList`, `WorldDetail` (tabs: Overview, Regions, Campaigns, TurnSchedule)
- `RegionDashboard` (map, assigned DM/Group, chat, AI takeover controls)
- `GroupsDirectory`, `GroupDetail` (members, campaigns, tasks)
- `CampaignDashboard` (turn timeline, tasks, session recap, AI assistant panel)
- `TurnManager` (configure turn length, process turn, view backlog)
- `SessionWorkspace` (AgendaEditor, LiveNotes, InitiativeTracker, DiceRoller, MapBoard, RecordingUploader)
- `MapBoard` (MapCanvas, TileLayer, TokenLayer, RegionOverlay, FogControls)
- `TilePalette` (draggable templates, categorized by terrain/structure)
- `TileInspector` (edit orientation/elevation/locking, attach notes)
- `NPCConsole` (AI chat with Gemma3, conversation history)
- `DMRelayPanel` (Discord integration status, internal chat)
- `TaskBoard` (Kanban grouped by status/turn)
- `TileTemplateLibrary` (manage reusable tiles; GM can clone base templates)
- `SearchResults`
- `Settings` (profile, notifications, OAuth links)

Shared components: `TurnCountdown`, `RegionSelector`, `GroupInviteForm`, `AIStatusBadge`, `SessionExportButton`, `TimeConfigModal`, `PermissionBadge`.

## Real-time (Laravel Reverb)
- Presence channels: `presence-world.{worldId}` (participants viewing world), `presence-region.{regionId}` (DM coordination).
- Private channels: `private-campaign.{campaignId}`, `private-session.{sessionId}`, `private-task.{taskId}`, `private-map.{mapId}` for tile edits.
- Events:
  - `TurnProcessed` {campaign_id, turn_number, summary, map_delta}
  - `RegionAssignmentChanged` {region_id, dm_id, group_id}
  - `InitiativeUpdated`, `DiceRolled`, `NoteCreated`
  - `MapTileUpdated` {map_id, tile_id, q, r, tile_template_id, elevation, orientation, locked}
  - `ChatMessageCreated` {channel, message}
  - `AITaskCompleted` {ai_request_id, response}
- Discord relay via queued job when chat message flagged for external broadcast.

## Files & Media
- Maps stored in S3-compatible bucket `/maps/{worldId}/{uuid}.png`; thumbnails generated (256px, 1024px) via queued job. Tile template art stored alongside `/tiles/{key}/{variant}.png` and versioned for easy extension.
- Session recordings stored as large objects with lifecycle policy; metadata saved to `sessions.recording_url`.
- Token images max 2MB. Validate MIME (png,jpeg,webp). Use temporary signed URLs for sharing. Cache tile metadata client-side for offline drafting and faster palette loads.

## Tile System Design
- **Grid model**: default hex axial coordinates (`q`,`r`) with optional square grid fallback. Each tile stores orientation (`pointy` or `flat`) and elevation for simple height effects.
- **Template library**: ship with core terrain templates (grassland, mountain, water, road, settlement, dungeon entrance). Templates define edge profiles (e.g., road connectors) so adjacent tiles auto-snap when edges match. Users can clone templates, upload art, and adjust metadata without breaking existing boards.
- **Placement UX**: TilePalette lists templates grouped by terrain; drag-and-drop snaps to nearest axial cell, previewing edge compatibility (highlight mismatches). TileInspector allows rotating, changing elevation, locking tiles (for finalized regions), or swapping template while preserving notes/tokens.
- **Extensibility**: MapTiles reference templates so adding new art or rules is additive. Variant JSON stores optional module data (resource yields, hazards) enabling future mechanics. Batch APIs allow import/export of tile layouts for reuse across campaigns.

## Dice Engine
- Same parser approach with support for modifiers `kh`, `kl`, `dh`, `dl`, `!` (explosion), `rr` (re-roll), parentheses. Evaluate with deterministic seed support (store `seed` on `dice_rolls` for reproducibility). Provide breakdown per turn and session.
- Additional tests for `2d20kh1+5`, `3d10!`, `1d100rr1`.

## Search & Filters
- Full-text indexes on `campaign_entities`, `notes`, `tasks`.
- Add composite indexes for `turns (campaign_id, processed_at)`, `sessions (campaign_id, session_date)`, `regions (world_id, status)`.
- Add unique index `map_tiles (map_id, q, r)` to enforce snap grid and accelerate tile lookups.
- Filters by group, region, turn number, tile terrain type.

## Access Control
- Policies ensuring only assigned DMs/groups manage regions.
- Example `RegionPolicy@update` ensures user is active DM or GM with scope `region` or world owner.
- `TurnPolicy@process` restricts to region DM or GM; fallback AI user allowed if flagged.
- `TaskPolicy@update` allows assigned group, assigned user, or GM.
- `MapTilePolicy@update` permits region DM, delegated builders, or world owners; locked tiles require explicit override flag.

## Testing Plan
- Pest unit tests for dice parser, turn scheduler, AI request service.
- Feature tests: group invites, region reassignment, turn processing, AI takeover fallback, map tile CRUD and snapping rules.
- HTTP contract tests for campaigns, sessions, tasks.
- Frontend: Vitest component tests (TurnCountdown, MapBoard), Playwright flows (create group, assign region, process turn, run session, upload recording).
- Mock Reverb for realtime tests using broadcasting fake.

## Performance
- Turn processing runs queued job; cache upcoming turns per campaign (`cache()->tags(['turns:'.$campaignId])`).
- Use eager loading with `withCount('turns')` for dashboards.
- Paginate sessions, tasks, chat logs; stream session exports.
- Preload region map data when entering workspace; use CDN for static assets and memoize tile template spritesheets in IndexedDB.

## Security
- Sanctum CSRF protection, rate limit login/invite/dice (throttle: `60,1`).
- Validate turn durations (1-72 hours) to avoid abuse. Input sanitized via CommonMark/HTML Purifier.
- File uploads scanned (optional ClamAV) and stored with signed URLs. Limit AI prompts length; log usage.
- Discord webhook secrets stored in env with encrypted config; audit AI actions.

## Seeders & Factories
- Factories for User, Group, World, Region, Campaign, Turn, Session, Task, Entity, Map, Note, DiceRoll.
- Seed demo: 2 groups (Stormbreakers, Moonlarks), shared world with 3 regions, one AI DM region. Populate tasks, turns, session recordings placeholder, NPC AI conversations.

## Migration Plan
1. Users, profiles, oauth tables.
2. Groups & group_memberships.
3. Worlds & regions.
4. Campaigns, role_assignments, invitations.
5. Turns.
6. Sessions, notes.
7. Tasks & task_updates.
8. Campaign entities, tags/taggables.
9. Maps & tokens.
10. Dice rolls & initiative entries.
11. Attachments, chat_messages, ai_requests.
12. Audit logs.

## DevOps
- Local: Laravel Sail (PHP 8.3, MySQL/Postgres, Redis, Meilisearch optional, Ollama container with Gemma3 model). Run `pnpm install`, `pnpm dev` proxied via Vite. Configure Reverb + Horizon.
- Env vars: `TURN_DEFAULT_HOURS`, `OLLAMA_BASE_URL`, `DISCORD_WEBHOOK_URL`, `SANCTUM_STATEFUL_DOMAINS`, queue & broadcast keys.
- Queues: Redis for turn processing, AI requests, exports, thumbnails. Horizon dashboard.
- Mail: Mailpit dev, SES/SendGrid prod.
- Storage: S3 bucket with versioning; CloudFront CDN. Session recordings optionally in separate bucket.
- Monitoring: Laravel Telescope dev; prod log to ELK/Grafana Loki. Backups nightly (DB + S3). Alerts on failed turn jobs.
- Deployment: Docker (nginx + php-fpm), GitHub Actions pipeline (lint, tests, build, deploy). Use Envoy or Terraform for infrastructure.

## Timeline (6 Weeks)
1. **Week 1 – Foundations**: Project setup, auth, groups, base layout, migrations for users/groups/worlds. Acceptance: register/login, create group, join request, configure turn defaults.
2. **Week 2 – Worlds & Regions**: CRUD for worlds/regions, region assignment UI, policies, Discord integration scaffolding. Acceptance: DM assigns region, region chat works.
3. **Week 3 – Campaign Core & Turns**: Campaign CRUD, role assignments, turn scheduler service, turn processing UI. Acceptance: process turn, see summary/history.
4. **Week 4 – Sessions & Tasks**: Sessions workspace, tasks/kanban, notes, dice, initiative, AI NPC console integration stub. Acceptance: run session, dice roll stored, NPC AI response logged.
5. **Week 5 – Maps & Realtime**: Map uploads, modular tile editor (palette, snapping, CRUD), token management, Reverb events, session recordings upload, multi-DM chat. Acceptance: live initiative sync, tile placements broadcast, recording attached.
6. **Week 6 – AI & Polish**: AI DM takeover workflow, exports, search, localization scaffolding, Playwright tests, seeding, docs. Acceptance: AI takeover recorded, export available, tests passing.

## Risks & Cut Scope
- **Turn automation complexity**: mitigate with manual override; can limit to daily turns initially.
- **AI availability (Ollama/Gemma3)**: fallback to manual DM queue; abstract provider.
- **Discord integration**: optional; fallback to internal chat only.
- **Session recording storage costs**: allow external link instead of upload.
- **Map fog-of-war**: keep as nice-to-have.
- **Tile editor scope creep**: ship core hex templates first; defer advanced edge rules or per-player visibility if schedule slips.

## Nice-to-haves
- Fog-of-war editor with player visibility toggles.
- SRD monster import and bulk entity creation.
- Markdown notes with `@mentions`, notifications, and AI-suggested tags.
- Automated AI NPC scheduling per turn.
- Procedural tile pack importer (Catan-style resource balancing, Saboteur tunnel generators).

