<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\ConstantValues;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted()
    {
        static::created(function ($account){
            if($account->role === ConstantValues::safesoul_og_patrol_role){
                SafeSoul::create([
                    'account_id'=>$account->id,
                    'points'=>ConstantValues::safesoul_OG_patrol_points,
                    'comment'=> 'Ог патрульный',
                    'query_param'=>ConstantValues::safesoul_og_patrol_role
                ]);
            }
            if($account->role === ConstantValues::safesoul_patrol_role){
                SafeSoul::create([
                    'account_id'=>$account->id,
                    'points'=>ConstantValues::safesoul_patrol_points,
                    'comment'=> 'Ог патрульный',
                    'query_param'=>ConstantValues::safesoul_patrol_role
                ]);
            }
        });

        static::updated(function ($account){

            $originalRole = $account->getOriginal('role');
            $currentRole = $account->role;

            if($originalRole === ConstantValues::safesoul_og_patrol_role && ($currentRole === 'patrol' || $currentRole ==='observer' || $currentRole ==='scout')) {
                SafeSoul::create([
                    'account_id' => $account->id,
                    'points' => -ConstantValues::safesoul_OG_patrol_points,
                    'comment' => 'понижена роль Ог патрульный',
                    'query_param' => ConstantValues::safesoul_og_patrol_role
                ]);
            }elseif ($originalRole === ConstantValues::safesoul_patrol_role && $currentRole === ConstantValues::safesoul_og_patrol_role){
                SafeSoul::create([
                    'account_id' => $account->id,
                    'points' => -ConstantValues::safesoul_patrol_points,
                    'comment' => 'получил роль Ог патрульный, потерял очки за роль патруль',
                    'query_param' => ConstantValues::safesoul_patrol_role
                ]);
                SafeSoul::create([
                    'account_id' => $account->id,
                    'points' => ConstantValues::safesoul_OG_patrol_points,
                    'comment' => 'получил роль Ог патрульный',
                    'query_param' => ConstantValues::safesoul_og_patrol_role
                ]);
            }
        });
    }

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


}
