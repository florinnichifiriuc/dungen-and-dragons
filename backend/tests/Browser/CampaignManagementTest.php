<?php

namespace Tests\Browser;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CampaignManagementTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_create_campaign(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $group = Group::factory()->for($user, 'creator')->create(['name' => 'Azure Blades']);
        GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        $this->browse(function (Browser $browser) use ($user, $group): void {
            $browser->visit('/login')
                ->type('email', $user->email)
                ->type('password', 'password')
                ->press('Sign in')
                ->waitForText('Campaign control center')
                ->visit(route('campaigns.create'))
                ->select('group_id', (string) $group->id)
                ->type('title', 'Dusk Trial Campaign')
                ->type('synopsis', 'Browser-verified storyline launch.')
                ->type('start_date', '2025-10-15')
                ->type('turn_hours', '12')
                ->press('Create campaign')
                ->waitForText('Dusk Trial Campaign')
                ->assertSee('Dusk Trial Campaign')
                ->assertPathBeginsWith('/campaigns/');
        });

        $this->assertDatabaseHas('campaigns', [
            'title' => 'Dusk Trial Campaign',
        ]);
    }
}
