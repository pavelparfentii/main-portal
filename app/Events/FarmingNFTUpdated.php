<?php

namespace App\Events;

use App\Models\FarmingNFT;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FarmingNFTUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $farmingNFTId;

    public function __construct($farmingNFTId)
    {
        $this->farmingNFTId = $farmingNFTId;
    }

}
