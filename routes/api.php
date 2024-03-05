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

Route::get('/points', [\App\Http\Controllers\PointsController::class, 'getPointsData']);

Route::get('/portal/info', [\App\Http\Controllers\PointsController::class, 'getInfo']);

Route::get('/invite/code', [\App\Http\Controllers\InviteController::class, 'activateCode']);

Route::get('/generate', [\App\Http\Controllers\MessageController::class, 'generateToken']);

//Broker
Route::post('/message', [\App\Http\Controllers\MessageController::class, 'message']);
Route::get('/test', [\App\Http\Controllers\FriendsController::class, 'getFriends']);

Route::get('/points/friends', [\App\Http\Controllers\FriendsController::class, 'getFriendsForAccount']);
//Route::get('/twitter', [\App\Http\Controllers\TemporaryController::class, 'getIgorProjectPosts']);
