<?php

namespace App\Providers;

use App\Events\DigitalAnimalsCreationEvent;
use App\Events\FarmingNFTUpdated;
use App\Events\SafeSoulCreationEvent;
use App\Events\TelegramPointsUpdated;
use App\Events\TwitterCreationEvent;
use App\Listeners\DigitalAnimalsListener;
use App\Listeners\SafeSoulCreationListener;
use App\Listeners\TelegramUpdateReferralIncome;
use App\Listeners\TwitterCreationListener;
use App\Listeners\UpdateAccountFarmingPoints;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        SafeSoulCreationEvent::class =>[
            SafeSoulCreationListener::class
        ],
//        TwitterCreationEvent::class =>[
//            TwitterCreationListener::class
//        ],
        DigitalAnimalsCreationEvent::class =>[
            DigitalAnimalsListener::class
        ],
        FarmingNFTUpdated::class => [
            UpdateAccountFarmingPoints::class
        ],
        TelegramPointsUpdated::class => [
            TelegramUpdateReferralIncome::class
        ]

    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
