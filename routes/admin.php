<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Admin\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Admin\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\VerifyEmailController;
use App\Http\Controllers\Admin\HotelController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\RatePlanController;
use App\Http\Controllers\Admin\RoomTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes (admins table only) — `admin` guard
|--------------------------------------------------------------------------
|
| Same shape as routes/auth.php but against the `admin` guard, and with
| no register route on purpose — see App\Models\Admin's docblock.
| Following the same prefix('admin')->as('admin.') + guest:admin /
| auth:admin convention as the sample admin.php you shared, minus
| everything specific to that other project (blogs, courses, students,
| instructors, staff, interns, invoices, reports...).
|
| Admin *account management* (create/edit admins, assign packages) now
| lives in routes/developer.php instead of here — only a developer can
| do that, and that's gated by an entirely separate guard (`developer`),
| not a role check on this one. See routes/developer.php's docblock.
*/

Route::group(['middleware' => 'guest:admin', 'prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::group(['middleware' => 'auth:admin', 'prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::get('/me', [AuthenticatedSessionController::class, 'show'])->name('me');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('permission:dashboard')
        ->name('dashboard');

    // Content & ops admin routes (locations, hotels, rooms, rates,
    // bookings, news, enquiries, media, settings, ...) go here as the
    // app grows, each behind its own `permission:<key>`.
    Route::middleware('permission:locations')->group(function () {
        Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::get('/locations/{location}', [LocationController::class, 'show'])->name('locations.show');
        Route::patch('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
    });

    Route::middleware('permission:hotels')->group(function () {
        Route::get('/hotels', [HotelController::class, 'index'])->name('hotels.index');
        Route::post('/hotels', [HotelController::class, 'store'])->name('hotels.store');
        Route::get('/hotels/{hotel}', [HotelController::class, 'show'])->name('hotels.show');
        Route::patch('/hotels/{hotel}', [HotelController::class, 'update'])->name('hotels.update');
        Route::delete('/hotels/{hotel}', [HotelController::class, 'destroy'])->name('hotels.destroy');
    });

    Route::middleware('permission:rooms')->group(function () {
        Route::get('/room-types', [RoomTypeController::class, 'index'])->name('room-types.index');
        Route::post('/room-types', [RoomTypeController::class, 'store'])->name('room-types.store');
        Route::get('/room-types/{roomType}', [RoomTypeController::class, 'show'])->name('room-types.show');
        Route::patch('/room-types/{roomType}', [RoomTypeController::class, 'update'])->name('room-types.update');
        Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'destroy'])->name('room-types.destroy');
    });

    Route::middleware('permission:rates')->group(function () {
        Route::get('/rate-plans', [RatePlanController::class, 'index'])->name('rate-plans.index');
        Route::post('/rate-plans', [RatePlanController::class, 'store'])->name('rate-plans.store');
        Route::get('/rate-plans/{ratePlan}', [RatePlanController::class, 'show'])->name('rate-plans.show');
        Route::patch('/rate-plans/{ratePlan}', [RatePlanController::class, 'update'])->name('rate-plans.update');
        Route::delete('/rate-plans/{ratePlan}', [RatePlanController::class, 'destroy'])->name('rate-plans.destroy');
    });
});
