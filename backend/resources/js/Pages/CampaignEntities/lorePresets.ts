export type LorePreset = {
    id: string;
    title: string;
    summary: string;
    fields: {
        name: string;
        alias?: string;
        entity_type: string;
        description: string;
        tags: string[];
        ai_controlled?: boolean;
    };
};

export const lorePresets: LorePreset[] = [
    {
        id: 'arcane-archivist',
        title: 'Arcane archivist patron',
        summary:
            'A benevolent keeper of living tomes who guides the party with cryptic visions and whispers about sealed histories.',
        fields: {
            name: 'Archivist Seraphine',
            alias: 'The Echoed Quill',
            entity_type: 'character',
            description:
                'A luminous scribe who records fate in sentient folios. She appears as needed to trade secrets for acts of compassion.',
            tags: ['ally', 'mystic', 'knowledge'],
            ai_controlled: true,
        },
    },
    {
        id: 'living-reliquary',
        title: 'Living reliquary',
        summary:
            'An artifact forged from starlit metal that houses a dormant spirit eager to bond with worthy adventurers.',
        fields: {
            name: 'Auric Reliquary',
            alias: 'Heart of the Silent Nova',
            entity_type: 'item',
            description:
                'A palm-sized vault that hums with ley energy. When attuned, it projects memories that shape the wielder’s destiny.',
            tags: ['artifact', 'mystery', 'cosmic'],
        },
    },
    {
        id: 'frontier-sanctum',
        title: 'Frontier sanctum',
        summary:
            'A reclaimed outpost on the edge of the wilds that now shelters refugees, scouts, and forgotten gods alike.',
        fields: {
            name: 'Sanctum of Emberglass',
            alias: 'The Beacon Bastion',
            entity_type: 'location',
            description:
                'Crystal ramparts grown from cooled lava protect a refuge for the displaced. Its hearth reveals paths through the frontier.',
            tags: ['haven', 'exploration', 'faction'],
        },
    },
];
