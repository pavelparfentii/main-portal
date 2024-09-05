<?php

namespace App\Jobs;

use App\Models\Round;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JobOne implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//    public static $attempted = false;
    public $tries = 3;
    protected $round;
    /**
     * Create a new job instance.
     */
    public function __construct(Round $round)
    {
        $this->round = $round;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //Freeze round
//        if (!self::$attempted) {
//            self::$attempted = true;
//            \Log::info('JobOne failed intentionally for testing.');
//            throw new \Exception('Intentional failure for testing.');
//        }
//
//        \Log::info('JobOne executed successfully.');
        $this->round->update(['status' => 'freeze']);
        JobTwo::dispatch($this->round)->onQueue('game')->delay(now()->addSeconds(6));
    }
}
