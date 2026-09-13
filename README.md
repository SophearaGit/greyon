# Greyon — Hotel Booking API (Auth & Role Setup)

Laravel 12 backend, JSON-only (no Blade views), meant to be driven from
Postman. This pass implements the auth & role architecture; rooms,
bookings, payments and reports come in a later pass.

## Architecture at a glance

- **Two separate account tables**: `users` (guest + manager) and
  `admins` (system administrators). There is no `role=admin` value
  anywhere, and no code path that lets a `users` row become an admin —
  see `App\Models\Admin`'s docblock.
- **Auth = plain Laravel/Breeze session guards**, not API tokens.
  `POST /login` (or `/admin/login`) authenticates and returns a session
  cookie; every request after that just needs to carry that cookie.
  This is the standard Breeze `AuthenticatedSessionController` /
  `LoginRequest` pattern, with every controller returning JSON instead
  of a view or a redirect (there's no Blade frontend here).
- **Two guards, two providers**: `web` → `users` table,
  `admin` → `admins` table (`config/auth.php`). `auth` / `guest`
  protect user routes; `auth:admin` / `guest:admin` protect admin
  routes — same convention as the sample `admin.php` you shared, just
  with the academy-specific stuff (blogs, courses, students,
  instructors, staff, interns, invoices, reports) stripped out, since
  none of that belongs to a hotel booking app.
- `role:manager` (`App\Http\Middleware\EnsureUserHasRole`) is the one
  bit of custom middleware — it gates manager-only routes on top of
  `auth`.
- **CSRF is turned off app-wide** (`bootstrap/app.php`). CSRF exists to
  stop a browser page from silently submitting a form using a victim's
  existing session cookie — since this app has no browser frontend at
  all (only Postman / a future mobile app talk to it directly), that
  attack doesn't apply, and turning it off means Postman just needs
  cookies-on, no CSRF-token dance. The session cookie itself is still
  the real authentication check — nothing works without logging in
  first.

## Setup (run these on your machine, inside this folder)

1. `composer install`
2. `.env` is already included with `APP_KEY` generated and `DB_*` set
   for a local MySQL database named `greyon` (Laragon defaults: host
   `127.0.0.1`, port `3306`, user `root`, no password). Create that
   database in HeidiSQL/phpMyAdmin (or `CREATE DATABASE greyon;`)
   before migrating. Adjust `.env` first if your setup differs.
3. `php artisan migrate --seed`
4. `php artisan serve` (or use the Laragon vhost for this folder, e.g.
   `http://greyon.info`)

## Google Sign-In setup

Guests can log in with a Google account instead of a password
(`app/Http/Controllers/Auth/GoogleController.php`). Admins don't get
this — there's no public admin registration at all, Google or
otherwise.

1. `composer require laravel/socialite`
2. In [Google Cloud Console](https://console.cloud.google.com/) →
   APIs & Services → Credentials, on your OAuth 2.0 Client ID, add
   this as an **Authorized redirect URI** (must match exactly):
   `http://greyon.info/auth/google/callback`
3. Put your Client ID/Secret in `.env` (already has the keys, just
   fill in the values — never commit `.env`, it's gitignored):
   ```
   GOOGLE_CLIENT_ID=your-client-id
   GOOGLE_CLIENT_SECRET=your-client-secret
   GOOGLE_REDIRECT_URI=http://greyon.info/auth/google/callback
   ```
4. `php artisan migrate` — adds a nullable, unique `google_id` column
   to `users` and relaxes `password` to nullable (a Google-only
   signup never sets one).
5. Open `http://greyon.info/auth/google/redirect` **in a real browser
   tab**, not Bruno — this is Google's own consent-screen redirect,
   which a single Bruno request can't drive. After you approve it,
   Google redirects back to `/auth/google/callback`, which logs you
   in and hands back the same session cookie every other endpoint
   here uses; from that point on Bruno's cookie jar (or the browser
   itself) is authenticated same as a normal `/login`.

Linking behavior: if the Google account's email already belongs to an
existing `users` row, that row gets `google_id` attached (no
duplicate account) rather than being blocked.

Seeded accounts (all password `password`):

| Role    | Email               | Login endpoint    |
| ------- | ------------------- | ----------------- |
| admin   | admin@greyon.test   | POST /admin/login |
| manager | manager@greyon.test | POST /login       |
| guest   | guest@greyon.test   | POST /login       |

## Testing from Postman

1. In Postman **Settings → General**, make sure **"Automatically follow
   redirects"** is on and cookies are enabled (they are by default).
2. `POST /login` (or `/admin/login`) with `email`/`password` as JSON
   body. Postman's cookie jar automatically stores the session cookie
   Laravel returns.
3. Every request after that — from the *same Postman cookie jar* — is
   authenticated automatically. Nothing else to set, no header to copy.
4. `POST /logout` (or `/admin/logout`) clears the session server-side.

A ready-to-import collection is at
`postman/greyon.postman_collection.json`, already pointed at
`http://greyon.info` — change the `base_url` collection variable if
your local URL differs.

## Endpoints

**User (`users` table, guard `web`)**

- `POST /register` — public. Always creates a `guest`; ignores any
  `role` the client sends.
- `POST /login` — public.
- `POST /logout`, `GET /user` — `auth`.
- `POST /forgot-password`, `POST /reset-password` — public.
- `GET /verify-email`, `GET /verify-email/{id}/{hash}`,
  `POST /email/verification-notification` — `auth`.
- `GET /confirm-password`, `POST /confirm-password`, `PUT /password`
  — `auth`.

**Admin (`admins` table, guard `admin`)** — no public registration,
ever. Same shape as above, prefixed `/admin` (`/admin/login`,
`/admin/logout`, `/admin/me`, `/admin/dashboard`,
`/admin/forgot-password`, `/admin/reset-password`,
`/admin/verify-email`, `/admin/confirm-password`, `/admin/password`).

**Google Sign-In (guests only, `web` guard)**

- `GET /auth/google/redirect` — public. Sends the browser to Google's
  consent screen. This is a browser redirect, not a plain JSON
  request — open it in an actual browser tab, not Bruno.
- `GET /auth/google/callback` — public. Google redirects back here;
  matches an existing account by `google_id`, then by email (linking
  it if found), or creates a new `guest` with no password set.

**App (placeholders for the next pass)**

- `GET /dashboard` — `auth` + `role:guest` (guest only).
- `GET /manager` — `auth` + `role:manager`.

## What's deliberately simplified for now

- Email verification and password-reset endpoints are fully wired, but
  nothing *enforces* verification yet (no `verified` middleware on the
  app routes) — that's one line to add once real mail sending is set
  up (`MAIL_MAILER` is `log` right now, so "sent" emails just land in
  `storage/logs/laravel.log`).
- Rooms, availability, bookings, rates, payments and reports are step 8+
  in the architecture doc's recommended development order — this pass
  covers steps 1–7 (auth, roles, admin account, guards, middleware).
  Placeholder routes and controllers are added back one at a time as
  each feature is actually built.
