<?php

namespace Tests\Browser;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Region;
use App\Models\User;
use App\Services\TurnScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RegionTurnProcessingTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_manager_can_process_region_turn(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $group = Group::factory()->for($user, 'creator')->create(['name' => 'Silver Wardens']);
        GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        $region = Region::factory()->for($group)->create(['name' => 'Eldertide Vale']);

        app(TurnScheduler::class)->configure($region, 24, CarbonImmutable::now('UTC'));

        $this->browse(function (Browser $browser) use ($user, $group, $region): void {
            $browser->visit('/login')
                ->type('email', $user->email)
                ->type('password', 'password')
                ->press('Sign in')
                ->waitForText('Campaign control center')
                ->visit(route('groups.show', $group))
                ->waitForText($region->name)
                ->clickLink('Process turn')
                ->waitForText("Advance {$region->name}")
                ->type('summary', 'Dusk automation advanced the frontier.')
                ->press('Process turn')
                ->waitForText("Turn #1 for {$region->name} processed.");
        });

        $this->assertDatabaseHas('turns', [
            'region_id' => $region->id,
            'number' => 1,
        ]);
    }
}
