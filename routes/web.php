<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\DemoController;
use App\Http\Controllers\Api\Auth\OtpController;

// Route::post('send-otp', [OTPController::class, 'sendOtp']); 
Route::post('verify-otp', (new OTPController())->verifyOtp(...));

Route::get('/', function (): never {
    dd('2');
});

Route::get('/log-test', function () {
    Log::info('Test log entry');
    return 'Log entry has been created';
});

Route::get('/mobile-verification', (new DemoController())->otpSend(...));


// Auth::routes();
