<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\KidWallet\KidsAccountController;
use App\Http\Controllers\Money\AddMoneyController;
use App\Http\Controllers\Money\TransferMoneyController;
use App\Http\Controllers\Pay\PayController;
use App\Http\Controllers\Smartcard\SmartCardController;
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

Route::middleware('auth:api')->group(function () {
    Route::post('/login/pin',[AuthController::class, 'loginByPin']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/pay', [PayController::class, 'pay']);
    Route::middleware('checkkid')->group(function () {

        Route::post('/add-money', [AddMoneyController::class, 'addMoney']);
        Route::post('/transfer-money', [TransferMoneyController::class, 'transfer']);
        Route::get('/view-smart-card', [SmartCardController::class, 'viewSmartCard']);
        Route::post('/generate-smart-card', [SmartCardController::class, 'generateSmartCard']);
        Route::post('/pay-by-card', [SmartCardController::class, 'processTransaction']);
        Route::post('/create-kid-account', [KidsAccountController::class, 'create']);
        Route::post('/enable-disable-kid-account', [KidsAccountController::class, 'enableOrDisable']);
        Route::post('/send-kid-money', [KidsAccountController::class, 'sendMoney']);
        Route::post('/select-forbidden-products', [KidsAccountController::class, 'selectForbiddenProducts']);
        Route::post('/delete-forbidden-products', [KidsAccountController::class, 'deleteForbiddenProducts']);

    });
});

