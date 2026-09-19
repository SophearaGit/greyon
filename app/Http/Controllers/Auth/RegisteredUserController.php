<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    /**
     * Register a new hotel customer.
     *
     * `role` is always forced to "guest" here regardless of anything the
     * client sends — the only way an account becomes a "manager" is a
     * manager/admin changing it afterwards through an authorized
     * endpoint. That's what keeps public registration from being a
     * privilege-escalation path.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
            'phone' => $request->input('phone'),
            'role' => 'guest',
            'status' => 'active',
        ]);

        event(new Registered($user));

        Auth::guard('web')->login($user);
        $this->bookings->claimForUser($user);

        return response()->json(['user' => $user], 201);
    }
}
