<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Week extends Model
{
    use HasFactory;

    protected $guarded = [];

    //Where is called
//    protected static function booted()
//    {
//        static::created(function ($model) {
//            // Отримати стек викликів
//            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
//
//            // Форматування стека викликів для логування
//            $traceString = "";
//            foreach ($trace as $frame) {
//                if (isset($frame['file']) && isset($frame['line'])) {
//                    $traceString .= $frame['file'] . ':' . $frame['line'] . "\n";
//                }
//            }
//
//            Log::info('Created new Week record', [
//                'id' => $model->id,
//                'attributes' => $model->getAttributes(),
//                'trace' => $traceString
//            ]);
//        });
//    }

    public function animals()
    {
        return $this->hasMany(DigitalAnimal::class);
    }

    public function games()
    {
        return $this->hasMany(DigitalGame::class);
    }

    public function digitalSouls()
    {
        return $this->hasMany(DigitalSoul::class);
    }

    public function safeSouls()
    {
        return $this->hasMany(SafeSoul::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function generals()
    {
        return $this->hasMany(General::class);
    }

    public function twitters()
    {
        return $this->hasMany(Twitter::class);
    }

    public function isClaimPointsUnused()
    {

        return !$this->claimed && $this->claim_points > 0;
    }

    public static function getCurrentWeekForAccount(Account $account)
    {

        $connection = $account->getConnectionName();

        if($connection === 'pgsql'){

            $currentDate = Carbon::now();

            $startOfWeek = $currentDate->copy()->startOfWeek();
            $endOfWeek = $currentDate->copy()->endOfWeek();

            $currentWeek = static::where('account_id', $account->id)
                ->where('start_date', '<=', $startOfWeek)
                ->where('end_date', '>=', $endOfWeek)
                ->where('active', true)
                ->first();

            if (!$currentWeek) {
                $currentDate = Carbon::now();
                $currentWeek = $account->weeks()->create([
                    'week_number' => $currentDate->weekOfYear . '-' . $currentDate->year,
                    'start_date' => $startOfWeek->toDateString(),
                    'end_date' => $endOfWeek->toDateString(),
                    'active' => true,
                    'points' => 0,
                    'claim_points' => 0,
                    'claimed'=>false
                ]);
            }elseif(!$currentWeek->active){
                $currentWeek->update(['active'=>true]);
            }

            return $currentWeek;
        }

    }

    public static function getCurrentWeekForTelegramAccount(Account $account)
    {
        $currentDate = Carbon::now();

        $startOfWeek = $currentDate->copy()->startOfWeek();
        $endOfWeek = $currentDate->copy()->endOfWeek();

        $currentWeek = static::on('pgsql_telegrams')
            ->where('account_id', $account->id)
            ->where('start_date', '<=', $startOfWeek)
            ->where('end_date', '>=', $endOfWeek)
            ->where('active', true)
            ->first();

        if (!$currentWeek) {
            $currentWeek = $account->weeks()->create([
                'week_number' => $currentDate->weekOfYear . '-' . $currentDate->year,
                'start_date' => $startOfWeek->toDateString(),
                'end_date' => $endOfWeek->toDateString(),
                'active' => true,
                'points' => 0,
                'claim_points' => 0,
                'claimed' => false
            ]);
        }

        return $currentWeek;
    }

}
