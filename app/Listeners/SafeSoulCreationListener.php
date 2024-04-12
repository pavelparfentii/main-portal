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
        $currentTotal = DB::table('weeks')->where('id', $event->weekId)->value('points');

        $currentTotalTotal = DB::table('weeks')->where('id', $event->weekId)->value('total_points');

        $currentTotalClaimPoints = DB::table('weeks')->where('id', $event->weekId)->value('claim_points');

        $accountId = DB::table('weeks')->where('id', $event->weekId)->value('account_id');
//
        $newTotal = $currentTotal + $event->safe->points;

        $newTotalTotal = $currentTotalTotal + $event->safe->total_points;

        $newTotalClaimPoints = $currentTotalClaimPoints + $event->safe->claim_points;

        $event->safe->update(['query_param'=>'admin_creation_points', 'account_id'=>$accountId]);
//
        DB::table('weeks')
            ->where('id', $event->weekId)
            ->update(['points'=>$newTotal, 'total_points'=> $newTotalTotal, 'claim_points'=>$newTotalClaimPoints, 'account_id'=>$accountId]);
    }
}
