<?php

namespace App\Listeners;

use App\Events\DigitalAnimalsCreationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class DigitalAnimalsListener
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
    public function handle(DigitalAnimalsCreationEvent $event): void
    {
//        $currentTotal = DB::table('accounts')->where('id', $event->accountId)->value('total_points');
//
//        $newTotal = $currentTotal + $event->digital->points;
//
//        DB::table('accounts')->where('id', $event->accountId)->update(['total_points'=>$newTotal]);
    }
}
