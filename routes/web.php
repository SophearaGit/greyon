<?php

use App\Http\Controllers\ManagerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This app is JSON-only and driven from Postman, not a browser — there
| are no Blade views anywhere. Everything still goes through the `web`
| middleware group (session, cookies) because session-cookie login is
| exactly what we want here; CSRF is turned off app-wide in
| bootstrap/app.php since there's no browser frontend to protect.
|
*/

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
    ]);
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';

// Guest only — matches the architecture doc's route table (section 7),
// which lists /dashboard as guest-level access, not "any authenticated
// user." A manager hits /manager instead.
Route::middleware(['auth', 'role:guest'])->group(function () {
    Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
        return response()->json(['user' => $request->user()]);
    })->name('dashboard');
});

// Manager only.
Route::middleware(['auth', 'role:manager'])->prefix('manager')->group(function () {
    Route::get('/', [ManagerController::class, 'index']);
});
