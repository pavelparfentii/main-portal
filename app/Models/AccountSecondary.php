<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountSecondary extends Model
{
    use HasFactory;


    protected $connection = 'pgsql_telegrams'; // Підключення до вторинної бази даних
    protected $table = 'accounts';
    protected $guarded = []; // Поля, які можна масово заповнювати

    public function telegram()
    {
        return $this->hasOne(TelegramSecondary::class, 'account_id', 'id');
    }
}
