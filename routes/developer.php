<?php

use App\Http\Controllers\Developer\AdminController;
use App\Http\Controllers\Developer\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Developer\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Developer\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Developer\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Developer\Auth\NewPasswordController;
use App\Http\Controllers\Developer\Auth\PasswordController;
use App\Http\Controllers\Developer\Auth\PasswordResetLinkController;
use App\Http\Controllers\Developer\Auth\VerifyEmailController;
use App\Http\Controllers\Developer\DeveloperDashboardController;
use App\Http\Controllers\Developer\FeatureController;
use App\Http\Controllers\Developer\PackageController;
use App\Http\Controllers\Developer\PermissionController;
use App\Http\Controllers\Developer\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Developer Routes (developers table only) — `developer` guard
|--------------------------------------------------------------------------
|
| Same Breeze-duplicate shape as routes/admin.php, one level up — see
| App\Models\Developer's docblock. A developer manages everything:
| roles, features, packages, and which packages each admin holds. None
| of these routes need a `permission:<key>` gate the way admin routes
| do — being authenticated on the `developer` guard at all *is* the
| gate, since only a real Developer account can ever get a session here.
*/

Route::group(['middleware' => 'guest:developer', 'prefix' => 'developer', 'as' => 'developer.'], function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::group(['middleware' => 'auth:developer', 'prefix' => 'developer', 'as' => 'developer.'], function () {
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

    Route::get('/dashboard', [DeveloperDashboardController::class, 'index'])->name('dashboard');

    // "crud role for developer to add"
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    // "crud features for developer to add"
    Route::get('/features', [FeatureController::class, 'index'])->name('features.index');
    Route::get('/features/{feature}', [FeatureController::class, 'show'])->name('features.show');
    Route::post('/features', [FeatureController::class, 'store'])->name('features.store');
    Route::patch('/features/{feature}', [FeatureController::class, 'update'])->name('features.update');
    Route::delete('/features/{feature}', [FeatureController::class, 'destroy'])->name('features.destroy');

    // "crud permissions for developer to add" — the doc maker's
    // "package -> permissions, feature -> permissions" sample (2026-09-16)
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('/permissions/{permission}', [PermissionController::class, 'show'])->name('permissions.show');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::patch('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    // "crud package for developer to add for user [admin]"
    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::get('/packages/{package}', [PackageController::class, 'show'])->name('packages.show');
    Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::patch('/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
    Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');
    Route::post('/packages/{package}/duplicate', [PackageController::class, 'duplicate'])->name('packages.duplicate');

    // Admin-account management — "user can have many packages"
    Route::get('/admins', [AdminController::class, 'index'])->name('admins.index');
    Route::get('/admins/{target}', [AdminController::class, 'show'])->name('admins.show');
    Route::post('/admins', [AdminController::class, 'store'])->name('admins.store');
    Route::patch('/admins/{target}', [AdminController::class, 'update'])->name('admins.update');
    Route::delete('/admins/{target}', [AdminController::class, 'destroy'])->name('admins.destroy');
    Route::put('/admins/{target}/packages', [AdminController::class, 'updatePackages'])->name('admins.packages');
});
