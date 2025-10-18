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
- Children (ReactNode): Rendered inside the card body for contextual content.

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

These components are Tailwind-powered, tree-shakable, and typed for Storybook-style documentation. Future transparency surfaces should import them directly to maintain visual and accessibility consistency.
