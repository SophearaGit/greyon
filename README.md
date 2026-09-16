# Greyon — Hotel Booking API (Auth & Role Setup)

Laravel 12 backend, JSON-only (no Blade views), meant to be driven from
Postman. This pass implements the auth & role architecture, the
Greyon_Backend_API handoff doc's dynamic packages/roles/features engine,
and locations/hotels/room types; rate plans, availability, bookings,
payments and reports come in a later pass.

## Spec alignment (read this if you're comparing against the handoff doc)

The handoff spec (`Greyon_Backend_API.docx`) describes Laravel + Breeze
API (Sanctum bearer tokens), UUID keys, and a dynamic `packages`/
`features` many-to-many RBAC engine. We went through **two** false
starts before landing here — both recorded in the project's
`architecture-pivot-2026-09-14.md` doc:

1. Built the spec literally (Sanctum, UUIDs, a single `users` table) —
   replaced this codebase with something unrecognizable from "our own
   project." Reverted.
2. Kept this app's `users`/`admins` tables and session-cookie auth, but
   hard-coded the spec's 5 roles as a flat `role` column instead of
   building the dynamic packages engine. **Turned out to be wrong** —
   after confirming with the doc's author, the dynamic packages/roles/
   features system genuinely is the requirement; it just doesn't need
   Sanctum or UUIDs to get there.

**What's actually implemented now (round 3):**

- **Auth stays session-cookie**, Breeze's own default pattern, not the
  API/Sanctum flavor — this was never in question, just the role model
  around it.
- **A third account table: `developers`** — a full Breeze duplicate of
  `admins`, its own `developer` guard, its own auth controller tree
  (`app/Http/Controllers/Developer/Auth/*`). Developers aren't an
  `admins.role` value; they're structurally separate, and they bypass
  the permission matrix entirely. This is the piece that was missing
  from round 2 — "developer" needs its own duplicate-of-Breeze table,
  same as `admins` is a duplicate of `users`.
- **`roles`, `features`, and `packages` are now real, developer-CRUD-able
  tables** (`app/Http/Controllers/Developer/{Role,Feature,Package}Controller`),
  not a fixed enum or hard-coded matrix. A package bundles a set of
  roles + a set of features; an admin can hold many packages
  (`admin_package`); their effective roles/features are the union
  across all of them — see `App\Services\AccessService`.
- **`App\Services\AccessService`** computes `effectiveRoles()`/
  `effectiveFeatureKeys()` from an admin's packages, `can()` = feature-
  key membership (no separate hard-coded role matrix on top — the
  package itself is the gate), and `isGlobal()`/`canAccessHotel()`/
  `canAccessLocation()` same as before, just sourced from packages
  instead of a flat column.
- **Error shape** matches spec section 10 —
  `{ statusCode, message, error }` — via a central exception renderer
  in `bootstrap/app.php`, applied app-wide.
- Endpoint paths follow this app's own guard-based routing rather than
  the spec's flat `/admin/*` namespace where the two conflict — admin-
  *account* management (assigning packages) is a `developer`-guard
  concern, so it lives at `/developer/admins`, not `/admin/users`.
- **Locations & Hotels (round 4)** — `locations`/`hotels` tables, admin
  CRUD (`App\Http\Controllers\Admin\{Location,Hotel}Controller`), and
  the public read-only endpoints spec section 9 describes. This is
  what flips `AccessService::canAccessHotel()`/`canAccessLocation()`
  from the safe no-ops described in round 3 into real scope checks —
  see "Endpoints" and `TESTING.md` section 8/9.
- **Schema-driven role scoping (round 5)** — `roles` gained a `scope`
  column (`none`|`location`|`hotel`, developer-CRUD-able same as
  everything else on the role). `AccessService` and every place that
  used to compare a role's *name* against `'manager'`/`'hotel_admin'`
  now reads `scope` instead. A developer can add a brand-new role, set
  its `scope`, and it's correctly enforced everywhere with no code
  change — see `TESTING.md` section 10 for a walkthrough that proves
  this with a role the codebase has never named.
- **Scope moved from the admin to the package assignment (round 6)** —
  `admins.location_ids`/`hotel_ids` are gone; those columns now live on
  the `admin_package` pivot (`App\Models\AdminPackage`), one pair per
  package an admin holds, not one flat pair for the whole account. An
  admin can now hold two differently-scoped packages at once (a
  location-scoped package for Phnom Penh *and* a hotel-scoped package
  for one hotel in Siem Reap, say) — impossible under the old
  flat-column design. `AdminResource` dropped its top-level
  `roles`/`featureKeys`/`locationIds`/`hotelIds` entirely: "an admin's
  only real attribute is which packages it holds," each `packages[]`
  entry now carrying its own `roles`, `featureKeys`, `locationIds`,
  `hotelIds`. See "Endpoints" → Admins for the new request body shape.
- **Room types + create made feature-gated, not scope-gated (round 7,
  current)** — `room_types` (belongs to a hotel, perm `rooms`, top of
  the `Hotel → RoomType → RatePlan/Availability/RateCalendar/Booking`
  chain) is a new resource with the same CRUD/scope shape as
  locations/hotels. Alongside it, `POST` (create) on locations, hotels,
  and room types dropped their extra global-seat/location-manager
  restrictions — a developer can always create, and an admin can
  create iff their packages grant that resource's feature, full stop.
  See "Endpoints" → "Create is feature-gated, not scope-gated" below.
- **Package quantity limits (round 10)** — a package can now also cap
  *how many* of a resource an admin holding it may create, via a new
  `package_limits` table and `AccessService::effectiveLimit()`.
  Enforced today on `POST /admin/locations` (total locations *this
  admin* has created) and `POST /admin/hotels` (hotels under *the
  target location*, regardless of creator). See "Package quantity
  limits" below.
- **Permissions — a real table under Features (round 11)** — the doc
  maker's sample: "package -> roles, package -> features, package ->
  permissions, feature -> permissions." A new `permissions` table
  (`App\Models\Permission`) formalizes what Round 8's
  `features.parent_key` sub-feature convention did informally: a
  feature is module-level visibility (unchanged — still the sole thing
  `permission:<key>` route middleware checks), a permission is a
  specific capability catalogued *under* a feature that a package can
  grant à la carte, independent of which other permissions or features
  it holds. The 5 `locations_*` keys from Round 8 are now real
  `permissions` rows instead of child features. See "Permissions"
  below.
- Not yet touched: availability, bookings, news, enquiries, media,
  settings, and the rest of the public API.

## Architecture at a glance

- **Three account tables, three guards**: `users` (guest + manager,
  guard `web`), `admins` (admin-panel accounts, guard `admin`),
  `developers` (platform developers, guard `developer`) —
  `config/auth.php`. No account row ever crosses tables; there's no
  code path from one guard's table into another.
- **Auth = plain Laravel/Breeze session guards**, not API tokens.
  `POST /login` / `/admin/login` / `/developer/login` all authenticate
  the same way and return a session cookie; every later request just
  needs to carry it. Every guard's controller tree is the standard
  Breeze `AuthenticatedSessionController` / `LoginRequest` pattern,
  returning JSON instead of a view or redirect (no Blade frontend).
- **What an admin can do is entirely determined by the packages
  they're assigned** (`admin_package`). A package
  (`App\Models\Package`) bundles `roles` + `features` (`package_role`/
  `package_feature`); an admin's effective roles/features are the
  union across every package they hold. Developers don't need any of
  this — they bypass `AccessService::can()` unconditionally by virtue
  of being on the `developer` guard at all.
- `role:manager` (`App\Http\Middleware\EnsureUserHasRole`) gates
  `users`/`web`-guard routes by role — untouched, unrelated to any of
  the above. `permission:<key>` (`App\Http\Middleware\EnsurePermission`)
  is the `admin`-guard equivalent, checking `AccessService::can()`.
  `developer`-guard routes need neither — being authenticated on that
  guard at all is the gate, since only a real `Developer` row can ever
  get a session there.
- **Every admin/developer JSON response goes through a Resource**
  (`AdminResource`/`DeveloperResource`/`RoleResource`/`FeatureResource`/
  `PackageResource`) — camelCase, computed live from the current
  package/role/feature assignments rather than stored redundantly.
- **CSRF is turned off app-wide** (`bootstrap/app.php`) — no browser
  frontend to protect a form submission on; the session cookie is still
  the real authentication check.

## Setup (run these on your machine, inside this folder)

1. `composer install`
2. `.env` is already included with `APP_KEY` generated and `DB_*` set
   for a local MySQL database named `greyon` (Laragon defaults: host
   `127.0.0.1`, port `3306`, user `root`, no password). Create that
   database in HeidiSQL/phpMyAdmin (or `CREATE DATABASE greyon;`)
   before migrating. Adjust `.env` first if your setup differs.
3. `php artisan migrate:fresh --seed` — **`:fresh`**: the `admins`
   table's `role` column from round 2 was dropped and replaced by the
   packages system, and `developers`/`roles`/`features`/`packages`/
   `package_role`/`package_feature`/`admin_package` are brand new
   tables — a plain `migrate` can't do that cleanly on a DB that ran
   round 2's migrations.
4. `php artisan serve` (or use the Laragon vhost for this folder, e.g.
   `http://greyon.info`)

## Google Sign-In setup

Guests can log in with a Google account instead of a password
(`app/Http/Controllers/Auth/GoogleController.php`). Admins and
developers don't get this — there's no public registration at all for
either, Google or otherwise.

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

| Table       | Email                | Login endpoint      | Packages / role          | Scope                     |
| ----------- | --------------------- | ------------------- | ------------------------- | -------------------------- |
| developers  | dev@greyon.com.kh     | POST /developer/login | — (bypasses everything) | global                     |
| admins      | admin@greyon.com.kh   | POST /admin/login   | Admin · Full Suite        | global (`admin` is_global) |
| admins      | pp@greyon.com.kh      | POST /admin/login   | Manager · Content+        | locationIds: [1] (PP)      |
| admins      | sr@greyon.com.kh      | POST /admin/login   | Manager · Content+        | locationIds: [2] (SR)      |
| admins      | hotel@greyon.com.kh   | POST /admin/login   | Hotel Admin · Core        | hotelIds: [1] (Riverside)  |
| admins      | angkor@greyon.com.kh  | POST /admin/login   | Hotel Admin · Core        | hotelIds: [2] (Angkor)     |
| users       | manager@greyon.test   | POST /login          | —                          | unrelated to the tables above |
| users       | guest@greyon.test     | POST /login          | —                          | customer / spec's `customer` role |

The location/hotel ids above are real foreign keys — `1` = Phnom
Penh / Riverside, `2` = Siem Reap / Angkor, seeded by `LocationSeeder`/
`HotelSeeder` (which run before `AdminSeeder`, see `DatabaseSeeder`) —
attached to each admin's specific package *assignment* on the
`admin_package` pivot, not stored on the admin row itself (see
"Scope moved from the admin to the package assignment" above).

## Testing from Postman

1. In Postman **Settings → General**, make sure **"Automatically follow
   redirects"** is on and cookies are enabled (they are by default).
2. `POST /login`, `/admin/login`, or `/developer/login` with
   `email`/`password` as JSON body. Postman's cookie jar automatically
   stores the session cookie Laravel returns.
3. Every request after that — from the *same Postman cookie jar* — is
   authenticated automatically. Nothing else to set, no header to copy.
   (Logging into a different guard replaces which account that cookie
   jar is authenticated as for *that* guard — the three guards don't
   interfere with each other.)
4. `POST /logout` / `/admin/logout` / `/developer/logout` clears that
   guard's session server-side.

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
`/admin/dashboard` is gated by `permission:dashboard`.

**Developer (`developers` table, guard `developer`)** — no public
registration, ever. Same shape again, prefixed `/developer`
(`/developer/login`, `/developer/logout`, `/developer/me`,
`/developer/dashboard`, `/developer/forgot-password`,
`/developer/reset-password`, `/developer/verify-email`,
`/developer/confirm-password`, `/developer/password`), plus:

- **Roles** — `GET /developer/roles`, `GET /developer/roles/:id`,
  `POST /developer/roles`, `PATCH /developer/roles/:id`,
  `DELETE /developer/roles/:id` (400 if still attached to a package).
  Body: `{ name, description?, isGlobal?, scope? }` — `scope` is
  `none` (default), `location`, or `hotel`, and is what
  `AccessService` actually reads to decide `canAccessLocation`/
  `canAccessHotel`/create-permission behavior for holders of this
  role — not the role's `name`. A brand-new `scope: "location"` role
  gets working location scoping immediately, no code change needed.
- **Features** — same 5 verbs at `/developer/features`. Body:
  `{ key, label, description?, category?, sortOrder? }` — no longer
  takes `parentKey` (removed round 11; see "Permissions" below).
  `index`/`show` eager-load `permissions`, so each feature's response
  includes the permissions catalogued under it — this is what the
  package-builder UI reads to render a feature's sub-checklist once
  expanded. `DELETE` blocks with `400` if attached to a package, or if
  any permission is still catalogued under it.
- **Permissions (round 11)** — same 5 verbs at `/developer/permissions`.
  Body: `{ key, label, description?, featureKey?, sortOrder? }` —
  `featureKey` is a feature *key* (not id), optional, resolved against
  `features.key` (`422` if it doesn't match a real feature). See
  "Permissions" below for what this table is and how it differs from
  `Features`. `DELETE` blocks with `400` if attached to a package
  (`package_permission`).
- **Packages** — same 5 verbs at `/developer/packages`, plus
  `POST /developer/packages/:id/duplicate`. Body:
  `{ name, description?, priceNote?, roles: string[], featureKeys: string[], permissionKeys?: string[], limits?: [{ resourceKey, maxCount? }] }`
  — `roles`/`featureKeys`/`permissionKeys` are names/keys, not ids
  (matches the spec's own example body); unknown ones → `400`.
  `permissionKeys` (round 11) is optional and independent of
  `featureKeys` — granting a feature does **not** also grant any
  permission catalogued under it, each has to be listed explicitly
  (same "à la carte" rule Round 8's sub-feature keys already followed).
  `limits` is optional; a `PATCH` that includes `featureKeys`/
  `permissionKeys`/`limits` **replaces** that whole set (doesn't
  merge), a duplicate `resourceKey` in `limits` → `400`, and
  `maxCount: null`/omitted means unlimited for that key. See "Package
  quantity limits" below for what `resourceKey` values actually do
  anything. `DELETE` blocks with `400` if `isSystem` or if any admin
  currently holds the package. `duplicate` copies roles, features,
  permissions, and limit rows all four.
- **Admins** — `GET /developer/admins`, `GET /developer/admins/:id`,
  `POST /developer/admins`, `PATCH /developer/admins/:id`,
  `DELETE /developer/admins/:id`,
  `PUT /developer/admins/:id/packages` (replaces the whole set).
  Body for create/update/`.../packages`:
  `{ name, email, password?, packages: [{ packageId, locationIds?, hotelIds? }] }`
  (`name`/`email`/`password` only apply to create/update, not
  `.../packages`) — an admin's *only* real attribute is which packages
  it holds, and each entry in `packages` is one assignment with its
  **own** scope: the same package can be granted to two different
  admins with two different `locationIds`/`hotelIds`, and one admin can
  hold several differently-scoped packages at once (e.g. a
  location-scoped package for Phnom Penh *and* a hotel-scoped package
  for one specific hotel elsewhere, in the same account). `locationIds`
  is required on an entry whose package grants a location-scoped role,
  `hotelIds` required if it grants a hotel-scoped role (422 if missing,
  same validation-failure shape as everything else; unknown package
  ids → 400). There's no top-level `roles`/`featureKeys`/`locationIds`/
  `hotelIds` on the admin response — each `packages[]` entry already
  carries its own `roles`, `featureKeys`, `locationIds`, `hotelIds`.

**Locations (`admin`/`developer`-authored, perm `locations`)**

- `GET /admin/locations` — list, scoped to what the calling admin can
  access (global sees all; manager sees their `locationIds`; hotel_admin
  sees locations that contain one of their `hotelIds`).
- `POST /admin/locations` — any admin whose effective packages grant
  the `locations` feature (already required just to reach this route,
  via `permission:locations`) — no further scope/global check (`403`
  only if the feature itself is missing). A developer can always
  create. See "Create is feature-gated, not scope-gated" below.
  Body: `{ name, slug, description?, heroImage?, gallery?, highlights?, status?, seoTitle?, seoDescription? }`.
- `GET /admin/locations/:id`, `PATCH /admin/locations/:id` — `404` (not
  `403`) if the location exists but is out of the calling admin's scope.
- `DELETE /admin/locations/:id` — global seats only; `400` if the
  location still has hotels under it.
- `PATCH /admin/locations/:id` is additionally field-gated by 3 of the
  5 permissions catalogued under the `locations` feature (see
  "Permissions" below): `name`/`slug`/`description`/`heroImage`/
  `gallery`/`highlights` need `locations_list`; `status` needs
  `locations_publish`; `seoTitle`/`seoDescription` need `locations_seo`.
  Missing one → `403` naming that specific permission key, checked
  before anything saves (a mixed request with one disallowed field
  fails atomically — nothing in the request is applied).

**Hotels (`admin`/`developer`-authored, perm `hotels`)**

- `GET /admin/hotels` — scoped the same way, via `canAccessHotel`.
- `POST /admin/hotels` — any admin whose effective packages grant the
  `hotels` feature — no location-manager/global restriction anymore
  (was global-seats-or-location-manager-only; see "Create is
  feature-gated, not scope-gated" below).
  Body: `{ locationId, name, slug, shortDescription?, description?, address?, coordinates?: {lat,lng}, phone?, email?, heroImage?, gallery?, amenities?, policies?, checkInTime?, checkOutTime?, featured?, status?, seoTitle?, seoDescription? }`.
- `GET /admin/hotels/:id`, `PATCH /admin/hotels/:id` — same 404-not-403
  out-of-scope rule as locations.
- `DELETE /admin/hotels/:id` — global seats only.

**Room types (`admin`/`developer`-authored, perm `rooms`)**

- `GET /admin/room-types` — scoped via `canAccessHotel` against the
  room type's `hotelId` (same rule as hotels).
- `POST /admin/room-types` — any admin whose effective packages grant
  the `rooms` feature — same feature-only rule as locations/hotels.
  Body: `{ hotelId, name, slug, description?, images?, bedType?, roomSize?, maxAdults?, maxChildren?, maxGuests?, amenities?, baseInventory?, status? }`.
  `slug` is unique per hotel, not globally (many hotels legitimately
  share room-type names like "Deluxe King").
- `GET /admin/room-types/:id`, `PATCH /admin/room-types/:id` — same
  404-not-403 out-of-scope rule as locations/hotels.
- `DELETE /admin/room-types/:id` — global seats only.

**Rate plans (`admin`/`developer`-authored, perm `rates`)**

- `GET /admin/rate-plans` — scoped via `canAccessHotel` against the
  rate plan's room type's `hotelId` (one level further down the chain
  than room types — `roomTypeId` isn't itself a hotel/location, so
  scope is resolved through the room type relation, `loadMissing`'d
  before the check).
- `POST /admin/rate-plans` — any admin whose effective packages grant
  the `rates` feature — same feature-only rule as locations/hotels/room
  types.
  Body: `{ roomTypeId, name, description?, mealBenefit?, cancellationPolicy?, basePrice, taxPercent?, serviceFeePercent?, status? }`.
  No `slug` — the spec doesn't define one for rate plans, and there's
  no public single-item lookup by rate plan. No name-uniqueness
  constraint either — a room type can hold two rate plans that happen
  to share a name.
- `GET /admin/rate-plans/:id`, `PATCH /admin/rate-plans/:id` — same
  404-not-403 out-of-scope rule as locations/hotels/room types.
- `DELETE /admin/rate-plans/:id` — global seats only.

**Create is feature-gated, not scope-gated (2026-09-15)** — on
locations, hotels, room types, and rate plans alike: "developer can add
location/hotel/room; admin can only add them if their packages have
that feature." A developer always can (bypasses the `permission:`
middleware entirely); an admin can iff their effective packages grant
the resource's feature key — the same `permission:<key>` gate that
already applies to every other action on that resource, so `store()`
adds no additional check of its own. This replaced two earlier,
narrower rules: locations/hotels' destroy stayed global-seats-only
(unaffected — only *create* moved to feature-only), but their old
create rules (locations: global-only; hotels: global-or-location-
manager-only) are gone. One consequence worth knowing: an admin can
now create a location/hotel/room type/rate plan outside their own
scope (e.g. a Phnom Penh manager creating a rate plan on Angkor's Deluxe
King), but afterward `GET`/`PATCH` on it still 404s for them the same
as any other out-of-scope record — only a global seat, or an admin
whose scope actually covers it, can manage it post-creation.

**Create-response defaults bug, found & fixed while building rate
plans (2026-09-15)** — `store()` on locations/hotels/room types/rate
plans calls `Model::create($data)`, but when a field is omitted from
the request its DB column default (e.g. `status: 'draft'`,
`taxPercent: 0`) was **not** showing up in the create response —
Eloquent's `create()` returns the in-memory instance built from exactly
the attributes you passed it, it doesn't re-fetch the row, so anything
left to a column default read back as `null` until the next `GET`.
Fixed by calling `->refresh()` (or `->fresh()`-then-load for the
relation) on the model right after `create()`, on all four
controllers, so the response always reflects what's actually in the
database. `update()` was never affected — route-model-binding already
fetches the full row before any partial update is applied.

**Permissions (2026-09-16)** — the doc maker's sample: "package ->
roles, package -> features, package -> permissions, feature ->
permissions." A new `permissions` table (`App\Models\Permission`,
CRUD via `Developer\PermissionController`) formalizes what Round 8's
`features.parent_key` sub-feature convention did informally, replacing
it outright (`parent_key` is gone). Two things now sit under `roles`/
`features` in the grant hierarchy:
- A **Feature** is still module-level visibility only — `key` is what
  `can()`/the `permission:<key>` route middleware check, unchanged.
- A **Permission** is a finer-grained capability, optionally catalogued
  under a feature (`Permission::feature_id`) — but that relation is
  *organizational only*. Holding a feature does **not** automatically
  grant any permission catalogued under it; a package has to list each
  permission it wants explicitly (`Package::permissions()`,
  `permissionKeys` in the API body), exactly the same "à la carte"
  selection `featureKeys` already uses. This is a deliberate design
  choice, not an oversight — Round 8's `locations_*` sub-feature keys
  already worked this way (`Manager · Content+` grants 2 of the 5
  seeded ones, `Admin · Full Suite` grants all 5), so formalizing the
  table kept the same selection model rather than switching to
  automatic inheritance.
- `App\Services\AccessService::hasPermission(admin, key)` /
  `effectivePermissionKeys(admin)` are the permission-level equivalent
  of `can()`/`effectiveFeatureKeys()` — a separate catalog, separate
  method, on purpose (a key like `locations_publish` reads like a
  child of the `locations` feature, but they're unrelated tables now,
  and nothing stops a permission key from colliding textually with a
  feature key elsewhere).
- The seeded catalog still has exactly the same 5 permissions under
  `locations` as Round 8 seeded as child features: `locations_list`,
  `locations_managers`, `locations_hotels`, `locations_publish`,
  `locations_seo` — same keys, same labels, same 3-of-5 wired to real
  field-level gates on `PATCH /admin/locations/:id` (see above), same
  2 intentionally unwired (`locations_hotels` has no corresponding
  Location-side action; `locations_managers` stays a developer-only
  capability, per the user's Round 8 decision). Admin-facing behavior
  is unchanged end to end — only the storage/API surface moved.
- `GET /developer/features` (and `:id`) now eager-load `permissions`,
  so a feature's response includes every permission catalogued under
  it — this is what a package-builder UI reads to render the
  sub-checklist once a developer expands a module.

**Package quantity limits (2026-09-16)** — separate axis from the
feature/scope gates above: a package can also say *how many* of a
resource an admin holding it is allowed to create, not just whether
they can at all. New `package_limits` table (`packageId`,
`resourceKey`, `maxCount` nullable = unlimited, unique per package+key)
plus `AccessService::effectiveLimit(admin, featureKey, resourceKey)`:
looks at every package the admin holds that grants `featureKey`, and if
*any* qualifying package has no `maxCount` row (or an explicit `null`)
for `resourceKey`, the admin is unlimited — otherwise the admin's
effective cap is the **highest** `maxCount` among their qualifying
packages ("most generous wins," same union philosophy as
`effectiveFeatureKeys()`). Two resource keys are wired today:
- `locations` (paired with the `locations` feature), checked in
  `LocationController::store()` — counts `locations` rows where
  `createdByAdminId` (new column, set on every location an admin
  creates) equals the acting admin. **Per-creating-admin**: two admins
  each holding a 3-location package can create 3 apiece, 6 total.
- `hotels_per_location` (paired with the `hotels` feature), checked in
  `HotelController::store()` — counts `hotels` rows where `locationId`
  equals the target location's id, **regardless of who created them**.
  This is deliberately **per-target-location, not per-admin**: the
  constraint models "this destination gets N hotels," a property of the
  location slot, not of whichever admin happens to be creating into it.

No existing package has either limit row by default (`Admin · Full
Suite`, `Manager · Content+`, `Hotel Admin · Core` are all still
unlimited — `effectiveLimit()` returns `null` for a package with no
matching row, and an admin is unlimited unless *every* qualifying
package caps the key). The new 4th seeded package, **`Admin ·
Starter`** (same full admin feature set as `Admin · Full Suite`, so the
limits are the *only* thing it demonstrates), is capped to
`locations: 3`, `hotels_per_location: 1` — seeded admin `starter@` /
`password` holds it. Hitting a cap returns `403` with a message naming
the current usage and the limit (e.g. `"Location limit reached (3/3)
for your package(s)."`). This is a quantity cap only — it doesn't
restrict *where* an admin can create (that's still the "create is
feature-gated, not scope-gated" rule above), just how many.

**Public — Locations & Hotels (no auth, spec section 9)**

- `GET /locations`, `GET /locations/:slug` — published only.
- `GET /hotels`, `GET /hotels/:slug` — published, and only under a
  published location. `GET /hotels` accepts `?locationSlug=` and
  `?featured=1` query filters.

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

See `TESTING.md` for a step-by-step Postman walkthrough of the whole
roles/features/packages engine, including the unknown-key validation
and the manager/hotel_admin scope-required checks.

## What's deliberately simplified for now

- Email verification and password-reset endpoints are fully wired for
  all three guards, but nothing *enforces* verification yet (no
  `verified` middleware on the app routes) — that's one line to add
  once real mail sending is set up (`MAIL_MAILER` is `log` right now,
  so "sent" emails just land in `storage/logs/laravel.log`).
- `canAccessHotel`/`canAccessLocation` in `AccessService` are live (the
  `Location`/`Hotel` controllers use them) and are fully schema-driven
  via `roles.scope` (`none`|`location`|`hotel`) — not role-name
  matching — read against each *package assignment's own*
  `locationIds`/`hotelIds` (on the `admin_package` pivot, not the
  admin). Renaming `manager`/`hotel_admin`, or adding an entirely new
  role with `scope: "location"`/`"hotel"`, works with zero code changes
  (see `TESTING.md` section 10, which proves this with a role created
  purely through the API). Note `canAccessLocation()` returns `true`
  for a hotel-scoped package that owns *any* hotel in that location —
  that's intentional for viewing/updating, but
  `HotelController::store()` deliberately doesn't use it for that
  reason (see its docblock; `AccessService::isLocationManagerOf()` is
  the narrower check it uses instead).
- Rate plans, availability, the rate calendar, bookings, payments and
  reports are still ahead in the architecture doc's recommended
  development order — this pass covers steps 1–7 (auth, roles, admin
  account, guards, middleware), the roles/features/packages engine, and
  now locations/hotels/room types. Placeholder routes and controllers
  are added back one at a time as each feature is actually built.
