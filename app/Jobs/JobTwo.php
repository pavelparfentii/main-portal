<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Bet;
use App\Models\Round;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JobTwo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $totalAmount = $this->round->bets->sum('amount');
        if ($this->round->bets->count() > 0) {
            $winnerUserId = $this->getWinningUserId($this->round->bets);
            $this->round->update([
                'status' => 'winner_generated',
                'winner_id' => $winnerUserId,
                'round_finished_at' => now(),
                'total_amount'=>$totalAmount
            ]);

        } else {
            $this->round->update(['status' => 'no_winner']);
        }

        Bet::on('pgsql_telegrams')->truncate();

        MainJob::dispatch()->onQueue('game')->delay(now()->addSeconds(15));
//        JobThree::dispatch()->onQueue('game')->delay(now()->addSeconds(10));
    }

    private function getWinningUserId($bets)
    {
        // Вычислить общую сумму всех ставок
        $totalAmount = $bets->sum('amount');

        // Создать массив префиксов с накопленными суммами ставок
        $prefix = [];
        $prefix[0] = $bets[0]->amount;

        for ($i = 1; $i < count($bets); ++$i) {
            $prefix[$i] = $prefix[$i - 1] + $bets[$i]->amount;
        }

        // Генерация случайного числа от 0 до общей суммы ставок
        $random = mt_rand(0, $totalAmount * 1000) / 1000;

        // Поиск индекса случайного числа в префиксном массиве
        $index = $this->findRandomInPrefixArray($prefix, $random, 0, count($prefix) - 1);
        $winningBet = $bets[$index];

        Account::on('pgsql_telegrams')
            ->where('id', $winningBet->account_id)
            ->increment('total_points', $totalAmount);

        return $winningBet->account_id;  // Возвращает идентификатор пользователя
    }

    private function findRandomInPrefixArray($prefix, $random, $start, $end) {
        while ($start < $end) {
            $mid = intdiv($start + $end, 2);
            if ($random >= $prefix[$mid]) {
                $start = $mid + 1;
            } else {
                $end = $mid;
            }
        }

        return ($prefix[$start] >= $random) ? $start : -1;
    }
}
