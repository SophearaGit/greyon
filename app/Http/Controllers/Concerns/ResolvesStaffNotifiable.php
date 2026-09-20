<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Admin;
use App\Models\Developer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesStaffNotifiable
{
    /**
     * Active admin or developer from the request guard.
     */
    protected function staffNotifiable(Request $request): Model
    {
        $admin = $request->user('admin');
        if ($admin instanceof Admin) {
            return $admin;
        }

        $developer = $request->user('developer');
        if ($developer instanceof Developer) {
            return $developer;
        }

        throw new HttpException(401, 'Unauthenticated.');
    }
}
