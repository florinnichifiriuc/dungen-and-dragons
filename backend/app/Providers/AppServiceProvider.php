<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\CampaignInvitation;
use App\Models\CampaignQuest;
use App\Models\CampaignQuestUpdate;
use App\Models\CampaignTask;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Region;
use App\Models\DiceRoll;
use App\Models\InitiativeEntry;
use App\Models\CampaignSession;
use App\Models\CampaignEntity;
use App\Models\SessionNote;
use App\Models\TurnConfiguration;
use App\Models\World;
use App\Models\Map;
use App\Models\MapTile;
use App\Models\MapToken;
use App\Models\TileTemplate;
use App\Policies\CampaignPolicy;
use App\Policies\CampaignInvitationPolicy;
use App\Policies\CampaignQuestPolicy;
use App\Policies\CampaignQuestUpdatePolicy;
use App\Policies\CampaignTaskPolicy;
use App\Policies\DiceRollPolicy;
use App\Policies\GroupPolicy;
use App\Policies\GroupMembershipPolicy;
use App\Policies\InitiativeEntryPolicy;
use App\Policies\RegionPolicy;
use App\Policies\SessionNotePolicy;
use App\Policies\SessionPolicy;
use App\Policies\TurnConfigurationPolicy;
use App\Policies\WorldPolicy;
use App\Policies\MapPolicy;
use App\Policies\MapTilePolicy;
use App\Policies\MapTokenPolicy;
use App\Policies\TileTemplatePolicy;
use App\Policies\CampaignEntityPolicy;
use App\Events\AnalyticsEventDispatched;
use App\Listeners\PersistAnalyticsEvent;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Faker\Provider\Base as BaseProvider;
use Faker\Provider\Lorem as LoremProvider;
use Faker\Provider\en_US\Person as EnUsPersonProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FakerGenerator::class, function ($app): FakerGenerator {
            $locale = $app['config']->get('app.faker_locale', 'en_US');

            $faker = \Faker\Factory::create($locale);
            $faker->unique(true);

            $faker->addProvider(new BaseProvider($faker));
            $faker->addProvider(new LoremProvider($faker));
            $faker->addProvider(new EnUsPersonProvider($faker));

            return $faker;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(GroupMembership::class, GroupMembershipPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(CampaignTask::class, CampaignTaskPolicy::class);
        Gate::policy(Region::class, RegionPolicy::class);
        Gate::policy(TurnConfiguration::class, TurnConfigurationPolicy::class);
        Gate::policy(World::class, WorldPolicy::class);
        Gate::policy(CampaignSession::class, SessionPolicy::class);
        Gate::policy(SessionNote::class, SessionNotePolicy::class);
        Gate::policy(DiceRoll::class, DiceRollPolicy::class);
        Gate::policy(InitiativeEntry::class, InitiativeEntryPolicy::class);
        Gate::policy(Map::class, MapPolicy::class);
        Gate::policy(MapTile::class, MapTilePolicy::class);
        Gate::policy(MapToken::class, MapTokenPolicy::class);
        Gate::policy(TileTemplate::class, TileTemplatePolicy::class);
        Gate::policy(CampaignEntity::class, CampaignEntityPolicy::class);
        Gate::policy(CampaignInvitation::class, CampaignInvitationPolicy::class);
        Gate::policy(CampaignQuest::class, CampaignQuestPolicy::class);
        Gate::policy(CampaignQuestUpdate::class, CampaignQuestUpdatePolicy::class);

        Event::listen(AnalyticsEventDispatched::class, PersistAnalyticsEvent::class);

        $this->app->resolving(FakerGenerator::class, function (FakerGenerator $faker): void {
            $faker->addProvider(new BaseProvider($faker));
            $faker->addProvider(new LoremProvider($faker));
            $faker->addProvider(new EnUsPersonProvider($faker));
        });
    }
}
