<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\Group;
use App\Models\Region;
use App\Models\DiceRoll;
use App\Models\InitiativeEntry;
use App\Models\CampaignSession;
use App\Models\SessionNote;
use App\Models\TurnConfiguration;
use App\Policies\CampaignPolicy;
use App\Policies\DiceRollPolicy;
use App\Policies\GroupPolicy;
use App\Policies\InitiativeEntryPolicy;
use App\Policies\RegionPolicy;
use App\Policies\SessionNotePolicy;
use App\Policies\SessionPolicy;
use App\Policies\TurnConfigurationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Region::class, RegionPolicy::class);
        Gate::policy(TurnConfiguration::class, TurnConfigurationPolicy::class);
        Gate::policy(CampaignSession::class, SessionPolicy::class);
        Gate::policy(SessionNote::class, SessionNotePolicy::class);
        Gate::policy(DiceRoll::class, DiceRollPolicy::class);
        Gate::policy(InitiativeEntry::class, InitiativeEntryPolicy::class);
    }
}
