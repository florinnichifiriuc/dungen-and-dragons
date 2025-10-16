# Condition Timer Interaction Wireframes

These annotated wireframes capture how condition timer transparency features behave across desktop, tablet, and mobile breakpoints. Each frame accounts for empty/loading/conflict/success states, accessibility, and the Tailwind + shadcn component primitives required for implementation.

## Audit Summary
| Surface | Existing Entry Point | Planned Insertion |
| --- | --- | --- |
| Condition Timer Dashboard | `Groups/ConditionTimers/Index` Inertia page | Replace current header with batch toolbar, add summary rail on the right. |
| Player Summary Panel | `Groups/ConditionTimerSummary` page + modal | Extend hero section with copy deck callouts and conflict banner slots. |
| Mobile Recap Widget | `resources/js/Components/ConditionRecapWidget.tsx` | Inject swipe-friendly batch actions and offline status indicators. |
| Session Workspace Sidebar | `Sessions/Show` | Toggleable drawer summarizing urgent conditions during encounters. |

## Desktop Frames (≥1280px)
### 1. Dashboard – Default View
- **Layout:** 3-column grid (token list, detail preview, summary rail).
- **Components:**
  - `Toolbar` (shadcn `Toolbar` + `DropdownMenu`) hosting filters, “Select All”, “Adjust” button.
  - `DataTable` (shadcn `Table`) for tokens with inline urgency badges and checkboxes.
  - `Detail Panel` (shadcn `Card`) showing selected timers, including copy deck snippet and batch delta form.
  - `Summary Rail` (shadcn `ScrollArea`) surfacing player projection payload with last refreshed timestamp.
- **States:**
  - *Empty:* muted illustration, CTA button linking to token placement.
  - *Loading:* skeleton rows and animated badge placeholders.
  - *Conflict:* sticky banner above the table with retry + conflict log link.
  - *Success:* toast anchored top-right summarizing adjustments (use `Toast` component variant “success”).

### 2. Dashboard – Multi-select Batch Adjustment
- Selected rows pin to the top with a `CommandBar` showing applied delta, expiration override, and preview count.
- Conflict state surfaces inline row badges (icon + tooltip) describing validation issues per token.
- Focus order: Toolbar → Table header → Row checkbox → CommandBar inputs → Apply button → Summary rail refresh link.

### 3. Dashboard – Player Summary Peek
- Hover/focus on summary rail entry reveals popover with urgency gradient background and copy deck text.
- Include quick “Share to Session Chat” button (icon button) wiring to existing broadcast actions.

## Tablet Frames (768–1279px)
### 1. Dashboard
- Collapse into two columns (token list + detail accordion).
- Batch toolbar wraps into two rows with icon-only buttons for filters.
- Summary rail becomes a collapsible drawer triggered via floating action button (FAB) bottom-right.
- Loading state uses shimmer bars; conflict banner becomes full-width toast stacked beneath the toolbar.

### 2. Session Workspace Sidebar
- Drawer slides over content using shadcn `Sheet` component.
- Gestures: swipe from right edge to open, drag handle to close; fallback accessible buttons for keyboard navigation.
- Include focus trap; initial focus on drawer header.

## Mobile Frames (≤767px)
### 1. Recap Widget Carousel
- Horizontal swipe between “Urgent”, “Warning”, and “All” tabs.
- Each card uses stacked layout: token label, urgency badge, copy deck snippet, CTA row (Adjust, Clear, Snooze).
- Offline mode indicator (icon + text) pinned to top, referencing cached timestamp.
- Loading state uses skeleton cards.

### 2. Batch Actions Flow
- Long-press on a card enters selection mode (checkboxes appear top-left of cards).
- Selection bar slides from bottom (shadcn `BottomSheet` pattern) with delta controls and “Apply” button.
- Conflict modal displays list of failures with “Retry individually” CTA.

### 3. Player Summary Modal
- Accessed via header button. Modal includes segmented control (Summary / History) and integrates analytics opt-in toggle.
- Provide accessible close button, escape key support, and maintain scroll locking.

## Component Handoff Notes
- **Tailwind tokens:**
  - Use `bg-crimson-500` → `bg-crimson-600` gradient for critical urgency.
  - Neutral surfaces rely on `bg-stone-950/60` for overlays to maintain D&D parchment vibe.
  - Text colors should meet 4.5:1 contrast (use `text-stone-100` on crimson backgrounds).
- **shadcn primitives:**
  - `Card`, `Badge`, `Popover`, `Sheet`, `Command`, `DropdownMenu`, `Toast`, `Tabs`, `ScrollArea`, `Tooltip`, `Checkbox`.
  - Compose the batch command bar with `Command` (searchable actions) and `Badge` for applied deltas.
- **Motion:**
  - Use 180ms ease-out transitions for drawers and sheets.
  - Carousel uses 240ms slide with slight overshoot (Tailwind `transition-transform` + `duration-200`).

## Accessibility Considerations
- All state changes announce via `aria-live="polite"` regions (e.g., success toast, conflict summary).
- Keyboard navigation order documented in each frame; ensure focus returns to triggering control after closing drawers/modals.
- Provide visible focus outlines (`outline-crimson-300`) for interactive elements, including swipe-invoked controls (triggered via `.focus-visible`).
- Gesture fallbacks: every swipe/long-press action has a button alternative.

## Review & Sign-off
1. Walk through annotated wireframes with the D&D Experience Lead, capturing notes in the meeting template (`Meetings/` directory).
2. Once approved, export frames to Figma project `Condition Timer Transparency` and attach share links in the Jira epic.
3. Handoff summary to engineering with component checklist and acceptance criteria appended to Task 38–44 implementation notes.

## QA Preview Checklist
- Confirm empty, loading, conflict, and success states appear at each breakpoint using browser dev tools.
- Validate screen reader order with VoiceOver/TalkBack on sample data.
- Test offline workflow by toggling dev tools network offline and ensuring cached summary messaging displays.
- Record a short Loom walkthrough for future onboarding reference.
