export type QuestPreset = {
    id: string;
    title: string;
    summary: string;
    details: string;
    objectives: string[];
};

export const questPresets: QuestPreset[] = [
    {
        id: 'leyline-calm',
        title: 'Calm the Whispering Leyline',
        summary: 'A fractured leyline destabilizes nearby settlements with haunting echoes and storms.',
        details:
            'Travel to the Sapphire Vault, negotiate aid from the Verdant Chorus, and channel the ley surge into the warded obelisk before the next turn.',
        objectives: [
            'Secure an anchor relic from the Sapphire Vault custodians.',
            'Broker a pact with the Verdant Chorus druids for ritual support.',
            'Stabilize the ley current inside the warded obelisk at dusk.',
        ],
    },
    {
        id: 'shadow-market',
        title: 'Map the Shadow Market',
        summary: 'Rumors speak of a midnight bazaar that trades in secrets, memories, and stolen prophecies.',
        details:
            'Infiltrate the hidden market without alarming its curators, catalogue the factions at play, and recover the missing prophecy shard for the council.',
        objectives: [
            'Tail the Candle-Sworn couriers to their hidden entrance.',
            'Identify three influential vendors and what they barter.',
            'Recover the prophecy shard before the market folds at dawn.',
        ],
    },
    {
        id: 'bastion-aid',
        title: 'Reinforce Emberglass Bastion',
        summary: 'Emberglass Bastion faces a siege from planar marauders seeking its crystal wards.',
        details:
            'Rally local militias, fortify the crystal ramparts, and coordinate a counterstrike that frees captured refugees from the marauders’ encampment.',
        objectives: [
            'Convince the Sunforged cohort to deploy artillery support.',
            'Set rune traps along the bastion’s southern ridge.',
            'Rescue the captured refugees and escort them through the hidden tunnel.',
        ],
    },
];
