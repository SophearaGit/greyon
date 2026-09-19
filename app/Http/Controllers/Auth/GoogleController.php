<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

/**
 * "Sign in with Google" — `users` table / `web` guard only.
 *
 * Browser redirect flow: /auth/google/redirect → Google → /auth/google/callback
 * → redirect to SPA with a one-time ticket → SPA POSTs /auth/google/exchange
 * (via same-origin proxy) to establish the session cookie.
 */
class GoogleController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    /**
     * Send the guest to Google's consent screen.
     */
    public function redirect(): mixed
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google sends the browser back here. We create/link the user, stash a
     * short-lived ticket, and redirect to the SPA to finish the session.
     */
    public function callback(Request $request): RedirectResponse|JsonResponse
    {
        $frontend = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:9400')), '/');

        if ($request->missing('code')) {
            return redirect("{$frontend}/sign-in?error=google_code");
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            return redirect("{$frontend}/sign-in?error=google_state");
        } catch (\Throwable $e) {
            return redirect("{$frontend}/sign-in?error=google_failed");
        }

        $googleEmail = strtolower(trim((string) $googleUser->getEmail()));

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::whereRaw('LOWER(email) = ?', [$googleEmail])->first();

            if ($user) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google User',
                    'email' => $googleEmail,
                    'google_id' => $googleUser->getId(),
                    'password' => null,
                    'role' => 'guest',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);
            }
        }

        if (! $user->isActive()) {
            return redirect("{$frontend}/sign-in?error=inactive");
        }

        $this->bookings->claimForUser($user);

        $ticket = Str::random(64);
        Cache::put($this->ticketKey($ticket), $user->id, now()->addMinutes(2));

        return redirect("{$frontend}/auth/google/complete?ticket={$ticket}");
    }

    /**
     * SPA exchanges the one-time ticket for a web-guard session cookie
     * (works through the Vite /engine proxy on localhost).
     */
    public function exchange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ticket' => ['required', 'string', 'min:20', 'max:128'],
        ]);

        $userId = Cache::pull($this->ticketKey($data['ticket']));

        if (! $userId) {
            return response()->json([
                'statusCode' => 419,
                'message' => 'Google sign-in expired. Please try again.',
                'error' => 'Page Expired',
            ], 419);
        }

        $user = User::query()->find($userId);

        if (! $user || ! $user->isActive()) {
            return response()->json([
                'statusCode' => 403,
                'message' => 'This account is not active.',
                'error' => 'Forbidden',
            ], 403);
        }

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();
        $this->bookings->claimForUser($user);

        return response()->json(['user' => $user]);
    }

    private function ticketKey(string $ticket): string
    {
        return "google_login_ticket:{$ticket}";
    }
}
