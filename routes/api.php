<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/invites', [\App\Http\Controllers\TemporaryController::class, 'dataAccamulation']);

Route::get('/points/account', [\App\Http\Controllers\PointsController::class, 'getPointsData']);

Route::get('/portal/info', [\App\Http\Controllers\PointsController::class, 'getInfo']);

Route::get('/generate', [\App\Http\Controllers\MessageController::class, 'generateToken']);

//Broker
Route::post('/message', [\App\Http\Controllers\MessageController::class, 'message']);
Route::get('/test', [\App\Http\Controllers\FriendsController::class, 'getFriends']);

Route::get('/points/friends', [\App\Http\Controllers\FriendsController::class, 'getFriendsForAccount']);

Route::post('team/create', [\App\Http\Controllers\TeamController::class, 'makeTeam']);

Route::post('team/{slug}/join', [\App\Http\Controllers\TeamController::class, 'joinTeam']);

Route::get('team/{slug}', [\App\Http\Controllers\TeamController::class, 'getTeamList']);

Route::get('/points/teams', [\App\Http\Controllers\TeamController::class, 'getTeamsList']);

Route::post('team/leave', [\App\Http\Controllers\TeamController::class, 'leaveTeam']);

Route::post('/team/check/name', [\App\Http\Controllers\TeamController::class, 'checkName']);

Route::post('/points/needshow', [\App\Http\Controllers\PointsController::class, 'needShow']);

Route::post('/points/claim', [\App\Http\Controllers\ClaimController::class, 'claim']);

Route::post('/invite/account', [\App\Http\Controllers\InviteController::class, 'inviteUser']);

Route::get('/referrals/list', [\App\Http\Controllers\ReferralsController::class, 'getReferralsData']);

Route::post('/referrals/claim', [\App\Http\Controllers\ReferralsController::class, 'claimIncome']);

Route::get('testE', [\App\Http\Controllers\TemporaryController::class, 'testEndp']);

Route::group(['middleware' => ['api.key']], function() {
    Route::get('/user/info', [\App\Http\Controllers\PointsController::class, 'getPersonalPointsForSafeSoul']);
});

//Route::post('/telegram/initiate', [\App\Http\Controllers\TelegramController::class, 'initiateSession']);

Route::post('/googlesheet', [\App\Http\Controllers\GooglesheetPartnerController::class, 'index']);

//Route::post('/telegram/points/store', [\App\Http\Controllers\TelegramController::class, 'updatePoints']);

//Route::get('/telegram/points1', [\App\Http\Controllers\TelegramController::class, 'getPoints2']);
//Route::post('/telegram/points/store1', [\App\Http\Controllers\TelegramController::class, 'updatePoints2']);
//Route::get('/telegram/points', [\App\Http\Controllers\TelegramController::class, 'getPoints2']);

Route::post('/telegram/points/store', [\App\Http\Controllers\TelegramController::class, 'updatePoints2']);

Route::group(['middleware'=>['custom.api']], function (){


});

Route::prefix('telegram')->group(function(){
    Route::post('initiate', [\App\Http\Controllers\TelegramController::class, 'initiateSessionSeparateDB']);
    Route::get('/portal/info', [\App\Http\Controllers\TelegramController::class, 'getInfoEndpoint']);
    Route::get('/points/account', [\App\Http\Controllers\TelegramController::class, 'getPointsDataEndpoint']);
    Route::get('/points/friends', [\App\Http\Controllers\TelegramController::class, 'getFriendsForAccountEndpoint']);

    Route::post('/points/store', [\App\Http\Controllers\TelegramController::class, 'updatePoints2']);

    Route::post('/team/create', [\App\Http\Controllers\TelegramController::class, 'makeTeamEndpoint']);
    Route::post('team/{slug}/join', [\App\Http\Controllers\TelegramController::class, 'joinTeamEndpoint']);
    Route::get('team/{slug}', [\App\Http\Controllers\TelegramController::class, 'getTeamListEndpoint']);

    Route::get('/points/teams', [\App\Http\Controllers\TelegramController::class, 'getTeamsListEndpoint']);
    Route::post('team/leave', [\App\Http\Controllers\TelegramController::class, 'leaveTeamEndpoint']);

    Route::post('/team/check/name', [\App\Http\Controllers\TelegramController::class, 'checkNameEndpoint']);

    Route::post('/invite/account', [\App\Http\Controllers\TelegramController::class, 'inviteUserEndpoint']);

    Route::get('/referrals/list', [\App\Http\Controllers\TelegramController::class, 'getReferralsDataEndpoint']);

    Route::get('/referrals/claim', [\App\Http\Controllers\TelegramController::class, 'claimIncomeEndpoint']);
//    Route::get('/points/friends', )
});
