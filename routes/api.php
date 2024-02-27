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

Route::get('/generate', [\App\Http\Controllers\PointsController::class, 'generateToken']);
//Route::get('/twitter', [\App\Http\Controllers\TemporaryController::class, 'getIgorProjectPosts']);
