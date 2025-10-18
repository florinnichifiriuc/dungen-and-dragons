# Transparency Dashboard Components

The transparency initiative now exposes a lightweight component collection for reuse across future dashboards.

## Packages & Location
- **Directory:** `resources/js/components/transparency/`
- **Exports:** `InsightCard`, `InsightList`

## InsightCard
```tsx
import { InsightCard } from '@/components/transparency';

<InsightCard
    title="Seven-night traffic"
    value={<span>{visits}</span>}
    description="1–7 November"
    tone="success"
    footer="Counts combine all guest devices."
    analytics={{
        eventKey: 'condition_transparency.dashboard.weekly_total.view',
        groupId,
        payload: { surface: 'share_insights', card: 'weekly_total' },
    }}
>
    <InsightList items={trendItems} emptyLabel="No visits yet." />
</InsightCard>
```

Props:
- `title` (string): Upper label rendered in small caps.
- `value` (ReactNode): Highlighted metric.
- `description?` (ReactNode): Optional supporting copy.
- `tone?` (`default | success | warning | danger`): Quick styling ramp.
- `footer?` (ReactNode): Optional footer text.
- `className?` (string): Tailwind utility overrides for layout tweaks.
- `analytics?` ({ `eventKey`, `groupId?`, `payload?`, `trigger?`, `onReady?` }):
  - `eventKey` maps directly to the analytics helpers from Task 44 (`recordAnalyticsEventSync`).
  - `trigger` defaults to `mount`. Use `'manual'` when you plan to fire from an intersection observer or action handler via the provided `onReady` callback.
  - `onReady` receives a `fire()` function once the component mounts so callers can chain manual telemetry (e.g., when a card becomes visible).
- Children (ReactNode): Rendered inside the card body for contextual content.

Telemetry is automatically recorded on mount when `analytics.trigger` is omitted or set to `'mount'`. Provide a stable `payload` signature (e.g., `{ surface, card, share_id }`) to avoid duplicate emissions during re-renders.

## InsightList
```tsx
import { InsightList } from '@/components/transparency';

const items = [
    { id: 'ally', title: 'Facilitator', description: '3 extensions in the last week.' },
];

<InsightList items={items} emptyLabel="No actors recorded." />
```

Props:
- `items`: Array of `{ id, title, description?, icon? }` entries.
- `emptyLabel?`: Message rendered when the list is empty.
- `className?`: Optional layout overrides.

## Theming Tokens & Customization
- CSS custom properties declared in `resources/css/app.css` drive colors and tones:
  - Card tokens: `--transparency-card-border-*`, `--transparency-card-background-*`, `--transparency-card-foreground-*`, plus neutral variants like `--transparency-card-label-color` and `--transparency-card-body-color`.
  - List tokens: `--transparency-list-border-color`, `--transparency-list-background-color`, `--transparency-list-title-color`, `--transparency-list-description-color`, `--transparency-list-icon-color`, and `--transparency-list-empty-color`.
- Override them per surface by scoping variables on a container:
  ```tsx
  <section
      className="space-y-4"
      style={{
          '--transparency-card-background-default': 'rgba(12, 10, 25, 0.8)',
          '--transparency-card-label-color': 'rgba(196, 181, 253, 0.85)',
          '--transparency-list-icon-color': '#c4b5fd',
      }}
  >
      <InsightCard title="Arcane refreshes" value={<span>12</span>}>
          <InsightList items={items} emptyLabel="No casts recorded." />
      </InsightCard>
  </section>
  ```
- Tailwind utilities still handle layout, spacing, and typography, but color application defers to the tokens so themes beyond transparency can restyle cards quickly.

These components remain tree-shakable and typed for Storybook-style documentation. Future transparency surfaces should import them directly to maintain visual, accessibility, and telemetry consistency.
