<?php

namespace App\Events;

use App\Models\Account;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DailyRewardEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $account;
    public $loginTime;
    /**
     * Create a new event instance.
     */
    public function __construct(Account $account, $loginTime)
    {
        $this->account = $account;
        $this->loginTime = $loginTime;
    }

}
