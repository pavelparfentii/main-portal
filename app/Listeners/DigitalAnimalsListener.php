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

        $currentTotal = DB::table('weeks')->where('id', $event->weekId)->value('points');

        $currentTotalTotal = DB::table('weeks')->where('id', $event->weekId)->value('total_points');

        $currentTotalClaimPoints = DB::table('weeks')->where('id', $event->weekId)->value('claim_points');

        $accountId = DB::table('weeks')->where('id', $event->weekId)->value('account_id');
//
        $newTotal = $currentTotal + $event->digital->points;

        $newTotalTotal = $currentTotalTotal + $event->digital->total_points;

        $newTotalClaimPoints = $currentTotalClaimPoints + $event->digital->claim_points;

        $event->digital->update(['query_param'=>'admin_creation_points', 'account_id'=>$accountId]);
//
        DB::table('weeks')->where('id', $event->weekId)->update(['points'=>$newTotal, 'total_points'=>$newTotalTotal, 'claim_points'=>$newTotalClaimPoints, 'account_id'=>$accountId]);
    }
}
