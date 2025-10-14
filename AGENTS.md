# Agent Guidelines

## Mission Alignment
- This repository implements a Dungeon & Dragons campaign platform. Stay faithful to the established architectural plan and timeline documented in `README.md`, `TASK_PLAN.md`, and the `Tasks/` directory.
- Favor Laravel 12 with Inertia-powered React (TypeScript), Tailwind CSS, and shadcn/ui components for all product work. Do not introduce alternative stacks without an explicit update to the plan.
- Ensure all timestamps, scheduling, and logging features respect the UTC + 24-hour requirements described in project docs.

## Collaboration Principles
- Approach tasks as both a seasoned full-stack engineer and a creative D&D designer. Features should feel thematic, accessible, and extensible for multiple groups, tiles, AI-driven helpers, and turn-based pacing.
- When implementing mechanics (e.g., initiative, tile maps, AI DM/NPC helpers), seek solutions that balance usability for casual players with depth for power users.
- Maintain and update progress artifacts (`TASK_PLAN.md`, `PROGRESS_LOG.md`, and per-task notes under `Tasks/`) whenever work is completed or scope changes.

## Technical Conventions
- Follow Laravel best practices: Form Requests for validation, Policies for authorization, Pest for tests, and queue/real-time integrations through Laravel Reverb as planned.
- Frontend code should live inside `backend/resources/js` and use Inertia pages/components. Prefer composition, strong typing, and Tailwind utility classes aligned with the D&D aesthetic already established.
- Keep code modular and well-documented. Include inline comments when domain concepts might be non-obvious (e.g., tile adjacency rules, AI hand-off flows).

## Process Expectations
- Before starting a task, review relevant task files under `Tasks/Week X/` and update them with intent, subtasks, and status.
- After completing work, log results, blockers, and next steps in both the task file and `PROGRESS_LOG.md`.
- Ensure each commit references the task scope in its message when possible and keep diffs focused.

Adhering to these guidelines ensures consistency across agents and preserves the immersive, user-friendly vision for this D&D platform.
