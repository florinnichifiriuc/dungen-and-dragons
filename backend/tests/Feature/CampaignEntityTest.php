<?php

use App\Models\Campaign;
use App\Models\CampaignEntity;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedCampaignWithMembers(): array
{
    $manager = User::factory()->create();
    $group = Group::factory()->for($manager, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $campaign = Campaign::factory()->for($group)->create([
        'created_by' => $manager->id,
    ]);

    $player = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    return [$manager, $campaign, $group, $player];
}

it('allows campaign managers to author lore with tags and stats', function () {
    [$manager, $campaign, $group, $player] = seedCampaignWithMembers();

    $response = $this->actingAs($manager)->post(route('campaigns.entities.store', [
        'campaign' => $campaign,
    ]), [
        'entity_type' => CampaignEntity::TYPE_NPC,
        'name' => 'Aveline Duskmantle',
        'alias' => 'Mistwarden',
        'pronunciation' => 'ah-veh-leen dusk-man-tul',
        'visibility' => CampaignEntity::VISIBILITY_PLAYERS,
        'group_id' => $group->id,
        'owner_id' => $player->id,
        'ai_controlled' => true,
        'initiative_default' => 18,
        'description' => 'An enigmatic guardian bound to the moonlit archives.',
        'stats' => [
            ['label' => 'Armor Class', 'value' => '17 (shadowplate)'],
            ['label' => 'Hit Points', 'value' => '142 (15d8 + 75)'],
        ],
        'tags' => ['Moon Court', 'Guardian'],
    ]);

    $response->assertRedirect();

    $entity = CampaignEntity::query()->where('campaign_id', $campaign->id)->first();

    expect($entity)->not->toBeNull();
    expect($entity->name)->toBe('Aveline Duskmantle');
    expect($entity->ai_controlled)->toBeTrue();
    expect($entity->initiative_default)->toBe(18);
    expect($entity->owner_id)->toBe($player->id);
    expect($entity->stats)->toHaveCount(2);
    expect($entity->tags)->toHaveCount(2);
    expect(Tag::query()->where('campaign_id', $campaign->id)->count())->toBe(2);
});

it('updates lore entries and syncs tags while regenerating slugs when renamed', function () {
    [$manager, $campaign] = array_slice(seedCampaignWithMembers(), 0, 2);

    $entity = CampaignEntity::factory()->for($campaign)->create([
        'name' => 'Velkan the Bold',
        'slug' => 'velkan-the-bold',
        'entity_type' => CampaignEntity::TYPE_MONSTER,
        'visibility' => CampaignEntity::VISIBILITY_GM,
        'stats' => [
            ['label' => 'Armor Class', 'value' => '15'],
        ],
    ]);

    $existingTag = Tag::create([
        'campaign_id' => $campaign->id,
        'label' => 'Legacy',
        'slug' => 'legacy',
    ]);

    $entity->tags()->attach($existingTag);

    $response = $this->actingAs($manager)->put(route('campaigns.entities.update', [
        'campaign' => $campaign,
        'entity' => $entity,
    ]), [
        'entity_type' => CampaignEntity::TYPE_MONSTER,
        'name' => 'Velkan the Redeemed',
        'alias' => 'Beacon of Dawn',
        'pronunciation' => 'vell-kahn',
        'visibility' => CampaignEntity::VISIBILITY_PUBLIC,
        'group_id' => '',
        'owner_id' => '',
        'ai_controlled' => false,
        'initiative_default' => 12,
        'description' => 'A titan who now defends the light.',
        'stats' => [
            ['label' => 'Armor Class', 'value' => '18'],
            ['label' => 'Speed', 'value' => '40 ft.'],
        ],
        'tags' => ['Beacon', 'Redeemed'],
    ]);

    $response->assertRedirect();

    $entity->refresh();

    expect($entity->name)->toBe('Velkan the Redeemed');
    expect($entity->slug)->toContain('velkan-the-redeemed');
    expect($entity->visibility)->toBe(CampaignEntity::VISIBILITY_PUBLIC);
    expect($entity->tags()->pluck('label')->all())->toMatchArray(['Beacon', 'Redeemed']);
    expect($entity->stats)->toHaveCount(2);
});

it('prevents players from creating lore entries', function () {
    [$manager, $campaign, $group, $player] = seedCampaignWithMembers();

    $this->actingAs($player)
        ->post(route('campaigns.entities.store', ['campaign' => $campaign]), [
            'entity_type' => CampaignEntity::TYPE_CHARACTER,
            'name' => 'Unseen Scribe',
            'visibility' => CampaignEntity::VISIBILITY_PLAYERS,
        ])
        ->assertForbidden();
});
