<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    use HasFactory;

    protected $connection = 'pgsql_telegrams';

    protected $guarded = false;

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function bets()
    {
        return $this->hasMany(Bet::class);
    }

    public function getBetsEndTimeAttribute()
    {
        if($this->status === 'waiting'){
            return $this->created_at ? $this->created_at->addSeconds(60) : null;
        }elseif ($this->status === 'freeze'){
            return null;
        }

    }

    public function getNewGameStartAtAttribute()
    {
        if($this->status === 'no_winner' || $this->status === 'winner_generated'){
            return $this->updated_at ? $this->updated_at->addSeconds(15) : null;
        }else{
            return null;
        }
    }


}
