<?php

namespace Tests\Browser;

use App\Models\Campaign;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SessionWorkspaceTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_manager_can_run_session_workspace_flow(): void
    {
        $manager = User::factory()->create([
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);

        $group = Group::factory()->for($manager, 'creator')->create(['name' => 'Obsidian Circle']);
        GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $manager->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        $campaign = Campaign::factory()->for($group)->create([
            'title' => 'Shard of Midnight',
            'created_by' => $manager->id,
        ]);

        $this->browse(function (Browser $browser) use ($manager, $campaign): void {
            $browser->visit('/login')
                ->type('email', $manager->email)
                ->type('password', 'password')
                ->press('Sign in')
                ->waitForText('Campaign control center')
                ->visit(route('campaigns.show', $campaign))
                ->waitForText('Session workspace')
                ->clickLink('Session workspace')
                ->waitForText('Schedule session')
                ->clickLink('Schedule session')
                ->waitForText('Schedule a session')
                ->type('title', 'Shadow Council')
                ->type('session_date', '2025-10-16T18:00')
                ->type('duration_minutes', '180')
                ->type('location', 'Vault 9')
                ->type('agenda', 'Opening gambit\nScout reports')
                ->type('summary', 'First council convened successfully.')
                ->type('recording_url', 'https://example.com/session')
                ->press('Save session')
                ->waitForText('Session scheduled.')
                ->waitForText('Shadow Council')
                ->type('note-content', 'Battle plan ready')
                ->press('Add note')
                ->waitForText('Note added to the chronicle.')
                ->type('dice-expression', '2d6+1')
                ->press('Roll')
                ->waitForText('Dice roll recorded.')
                ->type('initiative-name', 'Varyn the Swift')
                ->type('dexterity_mod', '3')
                ->press('Add to order')
                ->waitForText('Initiative entry added.')
                ->assertSee('Battle plan ready')
                ->assertSee('2D6+1')
                ->assertSee('Varyn the Swift');
        });
    }
}
