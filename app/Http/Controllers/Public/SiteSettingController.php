<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteSettingResource;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

/**
 * Public site settings (SPA hydrate — no secrets beyond public analytics id).
 */
class SiteSettingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['settings' => new SiteSettingResource(SiteSetting::current())]);
    }
}
