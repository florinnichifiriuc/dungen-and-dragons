<?php

namespace App\Providers;

use App\Models\Group;
use App\Models\Region;
use App\Models\TurnConfiguration;
use App\Policies\GroupPolicy;
use App\Policies\RegionPolicy;
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
        Gate::policy(Region::class, RegionPolicy::class);
        Gate::policy(TurnConfiguration::class, TurnConfigurationPolicy::class);
    }
}
