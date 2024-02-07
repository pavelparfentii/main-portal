<?php

namespace App\Listeners;

use App\Events\TwitterCreationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class TwitterCreationListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TwitterCreationEvent $event): void
    {
        $currentTotal = DB::table('accounts')->where('id', $event->accountId)->value('total_points');

        $newTotal = $currentTotal + $event->twitter->points;

        DB::table('accounts')->where('id', $event->accountId)->update(['total_points'=>$newTotal]);
    }
}
