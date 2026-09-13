<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

/**
 * "Sign in with Google" — `users` table / `web` guard only. Admins never
 * get this; there's no public account creation for the `admins` table at
 * all, Google or otherwise.
 *
 * This is a browser redirect flow (Google's own consent screen sits in
 * the middle), so it can't be driven from a single Bruno/Postman request
 * the way the rest of the API can — open /auth/google/redirect in an
 * actual browser tab. See README "Google Sign-In" section.
 */
class GoogleController extends Controller
{
    /**
     * Send the guest to Google's consent screen.
     */
    public function redirect(): mixed
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google sends the browser back here with a code Socialite exchanges
     * for the user's profile. We match/create a `users` row and log them
     * in on the `web` guard, same as a normal /login would.
     */
    public function callback(Request $request): JsonResponse
    {
        if ($request->missing('code')) {
            return response()->json([
                'message' => 'No authorization code from Google. Did you open /auth/google/callback '
                    .'directly? Start at /auth/google/redirect instead and let Google send you back here.',
            ], 400);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            return response()->json([
                'message' => 'Google sign-in expired or was tampered with. Please try again.',
            ], 419);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Google sign-in failed.',
            ], 422);
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            // Not linked yet — match by email so an existing password
            // account and a Google login for the same address become one
            // account instead of two.
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google User',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => null,
                    'role' => 'guest',
                    // Explicit, not left to the `users` table's column
                    // default — create() never reads a DB-level default
                    // back into the in-memory model, so isActive() below
                    // would otherwise see `status` as null and reject a
                    // brand-new, genuinely active account.
                    'status' => 'active',
                    // Google has already verified this address.
                    'email_verified_at' => now(),
                ]);
            }
        }

        if (! $user->isActive()) {
            return response()->json(['message' => 'This account is not active.'], 403);
        }

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return response()->json(['user' => $user]);
    }
}
