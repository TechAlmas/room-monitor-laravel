<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\AlarmsController;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\Api\CustomersController;

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
    Route::get('get-report-details/{id}', [AlarmsController::class,'getReportDetails']);
    Route::post('start-report',[AlarmsController::class,'startReport']);
    Route::post('upload-report-files/{id}',[AlarmsController::class,'updateReportFiles']);
    Route::get('remove-uploaded-report-file/{fileId}',[AlarmsController::class,'removeUploadedFile']);
    Route::get('approve-report/{reportId}',[AlarmsController::class,'approveReport']);
    Route::post('reject-report/{reportId}',[AlarmsController::class,'rejectReport']);
    Route::get('dropdown-managers',[AlarmsController::class,'dropdownManagers']);
    Route::get('fetch-alarms',[AlarmsController::class,'fetchAlarms']);
    Route::post('add-alarm-item',[AlarmsController::class,'addAlarmItem']);
    Route::post('update-alarm-item',[AlarmsController::class,'updateAlarmItem']);
    Route::post('upload-alarm-file',[AlarmsController::class,'uploadAlarmFile']);
    Route::post('upload-user-image',[AlarmsController::class,'uploadUserImage']);
    Route::get('alarm-detail',[AlarmsController::class,'fetchAlarmDetail']);

    Route::get('fetch-users',[UsersController::class,'fetchUsers']);
    Route::post('add-user',[UsersController::class,'addUser']);
    Route::post('update-user',[UsersController::class,'updateUser']);
    Route::get('user-detail',[UsersController::class,'fetchUserDetail']);
    
    Route::get('fetch-customers',[CustomersController::class,'fetchCustomers']);
    Route::post('add-customer',[CustomersController::class,'addCustomer']);
    Route::post('update-customer',[CustomersController::class,'updateCustomer']);
    Route::get('customer-detail',[CustomersController::class,'fetchCustomerDetail']);

    Route::get('email-report',[AlarmsController::class,'emailReport']);

});
