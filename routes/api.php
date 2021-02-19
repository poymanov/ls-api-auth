<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', [HomeController::class, 'index']);

Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
    Route::post('/registration', [AuthController::class, 'registration'])->name('registration');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset_password');

    Route::group(['middleware' => 'throttle:6,1'], function () {
        Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware(['signed:relative'])
            ->name('verify');

        Route::get('/resend-email-verification', [AuthController::class, 'resendEmailVerification'])
            ->name('resent_email_verification');

        Route::post('/login', [AuthController::class, 'login'])->name('login');

        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware(['auth:sanctum'])
            ->name('logout');

        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot_password');
    });
});

Route::get('/profile', [ProfileController::class, 'show'])->middleware(['auth:sanctum'])->name('profile');
