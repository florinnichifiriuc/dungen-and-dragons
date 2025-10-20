<?php

use App\Models\Campaign;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\Region;
use App\Models\TileTemplate;
use App\Models\User;
use App\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createGroupWithDungeonMaster(): array
{
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $dm = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $dm->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);

    return [$group, $dm, $owner];
}

it('returns fallback world ideas for group managers', function () {
    [$group, $dm] = createGroupWithDungeonMaster();

    $response = $this->actingAs($dm)->postJson(route('groups.ai.worlds', $group));

    $response->assertOk();

    $payload = $response->json();

    expect($payload['structured']['fields']['name'])->toBe('Radiant Expanse');
    expect($payload['structured']['fields']['default_turn_duration_hours'])->toBe(24);
    expect($payload['structured']['tips'])->toBeArray()->toHaveCount(3);
});

it('prevents non-members from requesting world ideas', function () {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('groups.ai.worlds', $group))->assertForbidden();
});

it('returns fallback region ideas for group managers', function () {
    [$group, $dm] = createGroupWithDungeonMaster();

    $response = $this->actingAs($dm)->postJson(route('groups.ai.regions', $group));

    $response->assertOk();

    $payload = $response->json();

    expect($payload['structured']['fields']['name'])->toBe('Auric Scriptorium Marches');
    expect($payload['structured']['fields']['turn_duration_hours'])->toBe(48);
    expect($payload['structured']['image_prompt'])->toContain($group->name);
});

it('returns fallback tile template ideas for group managers', function () {
    [$group, $dm] = createGroupWithDungeonMaster();

    $response = $this->actingAs($dm)->postJson(route('groups.ai.tile-templates', $group));

    $response->assertOk();

    $payload = $response->json();

    expect($payload['structured']['fields']['terrain_type'])->toBe('enchanted-thicket');
    expect($payload['structured']['fields']['movement_cost'])->toBe(3);
    expect($payload['structured']['fields']['edge_profile'])->toContain('"north"');
});

it('returns fallback map plan ideas for group managers', function () {
    [$group, $dm] = createGroupWithDungeonMaster();
    $region = Region::factory()->for($group)->create();
    $map = Map::factory()->for($group)->for($region)->create([
        'width' => 18,
        'height' => 12,
        'orientation' => 'pointy',
    ]);

    $response = $this->actingAs($dm)->postJson(route('groups.maps.ai.plan', [$group, $map]));

    $response->assertOk();

    $payload = $response->json();

    expect($payload['structured']['fields']['width'])->toBe(18);
    expect($payload['structured']['fields']['orientation'])->toBe('pointy');
    expect($payload['structured']['tips'])->toBeArray()->toHaveCount(2);
});

it('returns fallback campaign task ideas for managers', function () {
    [$group, $dm] = createGroupWithDungeonMaster();
    $campaign = Campaign::factory()->for($group)->create([
        'created_by' => $dm->id,
    ]);

    $response = $this->actingAs($dm)->postJson(route('campaigns.ai.tasks', $campaign));

    $response->assertOk();

    $payload = $response->json();

    expect($payload['structured']['fields']['title'])->toBe('Stabilize the frontier routes');
    expect($payload['structured']['fields']['description'])->toContain('obstacles');
    expect($payload['structured']['tips'])->toBeArray()->toHaveCount(3);
});

it('returns fallback lore ideas for managers', function () {
    [$group, $dm] = createGroupWithDungeonMaster();
    $campaign = Campaign::factory()->for($group)->create([
        'created_by' => $dm->id,
    ]);

    $response = $this->actingAs($dm)->postJson(route('campaigns.ai.lore', $campaign));

    $response->assertOk();

    $payload = $response->json();

    expect($payload['structured']['fields']['name'])->toBe('Archivist Seraphine');
    expect($payload['structured']['fields']['tags'])->toBeArray()->toContain('ally');
    expect($payload['structured']['image_prompt'])->toContain('Archivist Seraphine');
});

it('returns fallback quest ideas for managers', function () {
    [$group, $dm] = createGroupWithDungeonMaster();
    $campaign = Campaign::factory()->for($group)->create([
        'created_by' => $dm->id,
    ]);

    $response = $this->actingAs($dm)->postJson(route('campaigns.ai.quests', $campaign));

    $response->assertOk();

    $payload = $response->json();

    expect($payload['structured']['fields']['title'])->toBe('Calm the Whispering Leyline');
    expect($payload['structured']['fields']['objectives'])->toBeArray()->toHaveCount(3);
});

it('prevents non-managers from requesting campaign task ideas', function () {
    [$group] = createGroupWithDungeonMaster();
    $campaign = Campaign::factory()->for($group)->create();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('campaigns.ai.tasks', $campaign))->assertForbidden();
});

it('stores an uploaded tile template preview image', function () {
    [$group, $dm] = createGroupWithDungeonMaster();
    Storage::fake('public');

    $world = World::factory()->for($group)->create();

    $response = $this->actingAs($dm)->post(route('groups.tile-templates.store', $group), [
        'name' => 'Glittering Road',
        'key' => 'glittering-road',
        'terrain_type' => 'road',
        'movement_cost' => 1,
        'defense_bonus' => 0,
        'world_id' => $world->id,
        'image_upload' => UploadedFile::fake()->image('tile.png', 512, 512),
    ]);

    $response->assertRedirect(route('groups.show', $group));

    $template = TileTemplate::firstWhere('name', 'Glittering Road');

    expect($template)->not()->toBeNull();
    expect($template->image_path)->not()->toBeNull();
    Storage::disk('public')->assertExists($template->image_path);
});
