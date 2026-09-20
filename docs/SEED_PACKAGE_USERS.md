# Reference — Manager / Hotel package users

These accounts are **not** seeded by `AdminSeeder` anymore.
`migrate:fresh --seed` only creates org admins (`admin@`, `starter@`) plus
`dev@` from `DeveloperSeeder`, so testers can create Managers / Hotel desks
and assign locations/hotels in **People**.

Use this file as the recipe when you need demo package seats again
(local QA, Postman, docs). Password for all: `password`.

## Seeded today (default)

| Email | Name | Package | Scope |
| ----- | ---- | ------- | ----- |
| `dev@greyon.com.kh` | (DeveloperSeeder) | — | global |
| `admin@greyon.com.kh` | Sovann Meas | Admin · Full Suite | global |
| `starter@greyon.com.kh` | Admin Starter | Admin · Starter | global |

## Former package demos (reference only)

Resolve location/hotel **ids from slugs** after seed (ids can change):

| Email | Name | Package | Suggested scope |
| ----- | ---- | ------- | --------------- |
| `pp@greyon.com.kh` | Capital Coast Manager | Manager · Booking Pro | locations: `phnom-penh`, `sihanoukville` |
| `kampot@greyon.com.kh` | Kampot Manager | Manager · Content+ | location: `kampot` |
| `hotel@greyon.com.kh` | Riverside Front Desk | Hotel Admin · Core | hotel: `riverside` |
| `otres@greyon.com.kh` | Otres Front Desk | Hotel Admin · Booking Pro | hotel: `otres-bay` |
| `pepper@greyon.com.kh` | Pepper House Desk | Hotel Admin · Core | hotel: `pepper-house` |

## Optional PHP to re-seed (not wired into DatabaseSeeder)

Copy into a one-off seeder or paste into `AdminSeeder::run()` when you want demos back:

```php
$pp = Location::where('slug', 'phnom-penh')->firstOrFail();
$shv = Location::where('slug', 'sihanoukville')->firstOrFail();
$kampot = Location::where('slug', 'kampot')->firstOrFail();

$riverside = Hotel::where('slug', 'riverside')->firstOrFail();
$otres = Hotel::where('slug', 'otres-bay')->firstOrFail();
$pepper = Hotel::where('slug', 'pepper-house')->firstOrFail();

$this->makeAdmin(
    'pp@greyon.com.kh',
    'Capital Coast Manager',
    'Manager · Booking Pro',
    locationIds: [$pp->id, $shv->id],
);
$this->makeAdmin(
    'kampot@greyon.com.kh',
    'Kampot Manager',
    'Manager · Content+',
    locationIds: [$kampot->id],
);
$this->makeAdmin(
    'hotel@greyon.com.kh',
    'Riverside Front Desk',
    'Hotel Admin · Core',
    hotelIds: [$riverside->id],
);
$this->makeAdmin(
    'otres@greyon.com.kh',
    'Otres Front Desk',
    'Hotel Admin · Booking Pro',
    hotelIds: [$otres->id],
);
$this->makeAdmin(
    'pepper@greyon.com.kh',
    'Pepper House Desk',
    'Hotel Admin · Core',
    hotelIds: [$pepper->id],
);
```

`makeAdmin` needs the optional `locationIds` / `hotelIds` args and pivot
sync (`location_ids` / `hotel_ids`) as in git history for `AdminSeeder`.
