<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SafeSoulCreationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $weekId;
    public object $safe;

    public function __construct($safe, $weekId)
    {
        $this->weekId = $weekId;
        $this->safe = $safe;
    }

}
