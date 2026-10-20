# Admin Products & Services API

Client requirements doc (2026-10-04):

- **Item 5, Products**: a new "Products" section in the admin sidebar. Hotels select multiple products (many-to-many).
- **Item 6, Services**: a "Services" list, managed and selected the same way as Products. It replaces the free-text service field on the hotel form.

Both are flat catalogs shared by every hotel. They are not scoped to a location or hotel. Hotels link to them through the `hotel_service` and `hotel_product` pivots.

## Auth & permissions

- Every `/admin/*` route below uses `auth:admin,developer` and a session cookie, the same as the other CMS routes.
- Services routes need `permission:services` and products routes need `permission:products`. These are the feature keys in `FeatureSeeder` and `PackageSeeder`.
- A developer session always passes.
- `DELETE` also requires a global seat (`assertGlobalSeat`). A hotel- or location-scoped admin gets `403`.
- Hotel `serviceIds` / `productIds` use the existing `permission:hotels` plus hotel scope.

Errors:

| Status | Meaning |
|---|---|
| `401` | `{"message":"Unauthenticated."}` |
| `403` | Missing permission or global seat |
| `404` | Unknown id |
| `422` | Laravel validation: `{"message": "...", "errors": {"field": ["..."]}}` |

## Objects

### Service

```json
{
  "id": 1,
  "name": "Free WiFi",
  "slug": "free-wifi",
  "description": null,
  "icon": "wifi",
  "sortOrder": 0,
  "status": "published",
  "hotelsCount": 3
}
```

### Product

```json
{
  "id": 1,
  "name": "Honeymoon Package",
  "slug": "honeymoon-package",
  "description": "Flowers, cake and late checkout.",
  "icon": null,
  "imageUrl": "/storage/uploads/products/abc.jpg",
  "price": 120.5,
  "currency": "USD",
  "sortOrder": 0,
  "status": "published",
  "hotelsCount": 1
}
```

Field notes:

- `status` is one of `draft`, `published`, `archived`. It replaces an `is_active` flag and follows the convention of the other CMS resources. The default is `published`.
- `hotelsCount` appears only on admin catalog responses. It is not included in the public hotel payload or in the `services` / `products` arrays inside a hotel.
- For `imageUrl`, use the URL returned by `POST /admin/uploads`. A suggested folder is `products`.
- `price` is a number or `null`. `currency` is an ISO-4217 3-letter code or `null`, stored in uppercase.

## Endpoints

The Services and Products endpoints are identical. Replace `services`/`service` with `products`/`product`.

| Method | Path | Success |
|---|---|---|
| GET | `/admin/services` | `200 {"services": [Service, ...]}` |
| POST | `/admin/services` | `201 {"service": Service}` |
| GET | `/admin/services/{id}` | `200 {"service": Service}` |
| PATCH | `/admin/services/{id}` | `200 {"service": Service}` |
| DELETE | `/admin/services/{id}` | `200 {"message": "Service deleted."}` |

The same five routes exist under `/admin/products`. The response keys are `products` / `product`, and the delete message is `"Product deleted."`.

### List query params (optional)

- `status`: one of `draft`, `published`, `archived`.
- `q`: a substring match on the name.

Results are ordered by `sortOrder`, then by `name`. The list is not paginated.

### Create / update body (JSON, camelCase)

| Field | Create | Update | Rules |
|---|---|---|---|
| `name` | required | optional | string, max 255 |
| `slug` | optional | optional | lowercase kebab-case `^[a-z0-9]+(-[a-z0-9]+)*$`, unique, max 255. On create it is auto-generated from `name` if omitted (`-2`, `-3`, ... is added if taken). On update, an empty value or `null` is ignored. |
| `description` | optional | optional | string or `null` |
| `icon` | optional | optional | string or `null`, max 255. This is an icon key/name for the SPA's icon set. |
| `sortOrder` | optional | optional | integer 0–65535. `null` becomes `0`. |
| `status` | optional | optional | `draft`, `published` or `archived` |
| `imageUrl` | products only | products only | string or `null`, max 2048 |
| `price` | products only | products only | numeric ≥ 0 or `null` |
| `currency` | products only | products only | 3 letters or `null`, uppercased |

Example:

```http
POST /admin/products
{"name":"Airport Transfer Package","price":25,"currency":"usd","sortOrder":2}
→ 201 {"product":{"id":14,"name":"Airport Transfer Package","slug":"airport-transfer-package","description":null,"icon":null,"imageUrl":null,"price":25,"currency":"USD","sortOrder":2,"status":"published","hotelsCount":0}}
```

Deleting a service or product detaches it from every hotel (cascade on the pivot). No hotel is deleted or blocked. To retire an item but keep it in the data, use `status: "archived"`.

## Hotel payload changes

### Admin `POST /admin/hotels` and `PATCH /admin/hotels/{id}`

- Optional `serviceIds: number[]` and `productIds: number[]`. Each id must exist; an unknown id returns `422` on `serviceIds.N` / `productIds.N`.
- The list is a full replacement (sync). `[]` clears it. Omitting the key on PATCH leaves the current selection unchanged. On POST, omitting it means none.

### Admin hotel responses

These are `GET /admin/hotels`, `GET /admin/hotels/{id}`, and the POST/PATCH responses. They include:

- `services: Service[]` and `products: Product[]`, covering all selected items in any status, without `hotelsCount`.

The SPA builds the pickers' selected ids from `hotel.services.map(s => s.id)`.

### Public `GET /hotels` and `GET /hotels/{slug}`

- `hotel.services` and `hotel.products` have the same object shape. They contain only `published` items, ordered by `sortOrder` then `name`.
- A draft or archived item stays attached but is hidden.

### Existing fields

- `amenities` (a free-text string array) is unchanged and still returned.
- The SPA should replace the hotel form's free-text services input with a multi-select bound to `serviceIds`.

## Database

| Migration | Contents |
|---|---|
| `2026_10_05_000100..000400` | `services`, `hotel_service`, `products`, `hotel_product` |
| `2026_10_20_000100_add_catalog_fields_to_services_and_products` | Additive: `services.sort_order`, plus `products.image_url`, `price` `decimal(10,2)`, `currency` `char(3)`, `sort_order` |

Starter catalogs come from `ServiceSeeder` and `ProductSeeder` (12 each, `updateOrCreate` by slug). They are not attached to any real hotel.
