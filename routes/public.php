<?php

use App\Http\Controllers\Public\HotelController;
use App\Http\Controllers\Public\LocationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public (front-site) Routes — no guard, no auth
|--------------------------------------------------------------------------
|
| Spec section 9. These are the read-only endpoints the marketing site
| calls directly — published content only, nothing here ever reveals a
| draft/archived record's existence. Kept in their own file and their
| own `App\Http\Controllers\Public` namespace, separate from the
| admin-scoped controllers, since none of the admin ones' scope/
| permission logic applies to an anonymous visitor.
|
| More public endpoints (availability search, guest booking, enquiries,
| news, portfolios...) land here as those milestones are built.
*/

Route::get('/locations', [LocationController::class, 'index'])->name('public.locations.index');
Route::get('/locations/{slug}', [LocationController::class, 'show'])->name('public.locations.show');

Route::get('/hotels', [HotelController::class, 'index'])->name('public.hotels.index');
Route::get('/hotels/{slug}', [HotelController::class, 'show'])->name('public.hotels.show');
