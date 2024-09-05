<?php

namespace App\Jobs;

use App\Models\Bet;
use App\Models\Game;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $gameLabel = 'wheel_game';

        $game = Game::on('pgsql_telegrams')
            ->where('game_label', $gameLabel)
            ->first();
        $currentRound = $game->rounds()->where('status', 'waiting')->latest()->first();

        if (!$currentRound) {
            Bet::on('pgsql_telegrams')->truncate();
            $currentRound = $game->rounds()->create([]);

            JobOne::dispatch($currentRound)->onQueue('game')->delay(90);
        }


    }
}
