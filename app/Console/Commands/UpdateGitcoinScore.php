<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateGitcoinScore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-gitcoin-score';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $offset=0;
        $limit=100;

        do{
            $response = Http::withToken('h0MlK6nz.L7mHli1VDMKi1ynDpZ5SpqHH20LCEshK')
                ->get("https://api.scorer.gitcoin.co/registry/score/6539?limit=$limit&offset=$offset");

            $data = $response->json();

            $items = count($data['items']);
            $this->info($items);

            if (isset($data['items']) && is_array($data['items'])) {
                $addressScorePairs = collect($data['items'])->map(function ($item) {
                    return [
                        'address' => $item['address'] ?? null,
                        'score' => round($item['score'], 2)  ?? null
                    ];
                });

                foreach ($addressScorePairs as $pair) {
                    $this->info($pair['score']);
                    // Assuming 'address' corresponds to 'wallet' in your Account model
                    Account::where('wallet', $pair['address'])
                        ->update(['gitcoin_score' => $pair['score']]);
                }

            }

            $offset += $limit;
        }while($items>0);
    }
}
