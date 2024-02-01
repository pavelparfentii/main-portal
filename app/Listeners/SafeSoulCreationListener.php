<?php

namespace App\Listeners;

use App\Events\SafeSoulCreationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class SafeSoulCreationListener
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
    public function handle(SafeSoulCreationEvent $event): void
    {
        $currentTotal = DB::table('accounts')->where('id', $event->accountId)->value('total_points');

        $newTotal = $currentTotal + $event->safe->points;

        DB::table('accounts')->where('id', $event->accountId)->update(['total_points'=>$newTotal]);
    }
}
