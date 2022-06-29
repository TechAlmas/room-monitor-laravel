<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\AlarmsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['namespace'=>'Api','middleware' => 'App\Http\Middleware\GuestApi'], function(){
    Route::post('login',[LoginController::class,'login']);
    Route::post('signup', [LoginController::class,'SignUp']);
    Route::get('verify-account/{validate_string}', [LoginController::class,'VerifyAccount'])->name('verifyAccount');
    Route::get('datatable-test', [AlarmsController::class,'datatableTestData']);

});

Route::group(['namespace'=>'Api','middleware' => 'App\Http\Middleware\AuthApi'], function(){
    Route::post('create-report',[AlarmsController::class,'createReport']);
    Route::get('display-alarms', [AlarmsController::class,'displayAlarms']);
    

});
