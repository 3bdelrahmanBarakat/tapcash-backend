<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
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

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:3,1');
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('save-pincode', [AuthController::class, 'savePinCode']);
// Route::post('login', [LoginController::class, 'login'])->middleware('guest');
// Route::post('logout', [LoginController::class, 'logout'])->middleware('auth:api');
