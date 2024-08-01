<?php


namespace App\Services;


use App\Models\Account;
use App\Models\AccountSecondary;
use Illuminate\Database\Eloquent\Model;

class AccountService
{
    public function getAllUsersWithRelations()
    {
        $usersFromSecondary = (new Account())->setConnection('pgsql_telegrams')->get();
        $usersFromSecondary = AccountSecondary::with('telegram')->get();
        // Об'єднайте результати
        return $usersFromSecondary;
    }
}
