<?php

namespace Tests\Feature;

use App\Models\Developer;
use App\Models\Hotel;
use App\Models\Location;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Admin Services / Products catalogs + hotel serviceIds/productIds sync
 * + public hotel payload. Client requirements doc 2026-10-04, items 5/6.
 *
 * No RefreshDatabase: one existing migration (add_google_id_to_users)
 * uses MySQL-only SQL, so the full chain fails on the phpunit sqlite
 * :memory: DB. Instead every other migration is run explicitly per test
 * (fresh :memory: connection per test, so nothing persists).
 */
class AdminCatalogTest extends TestCase
{
    private const JSON = ['Accept' => 'application/json'];

    protected function setUp(): void
    {
        parent::setUp();

        $paths = collect(File::files(database_path('migrations')))
            ->map(fn ($f) => $f->getPathname())
            ->reject(fn ($p) => str_contains($p, 'add_google_id_to_users_table'))
            ->sort()
            ->values()
            ->all();

        Artisan::call('migrate', ['--path' => $paths, '--realpath' => true, '--force' => true]);
    }

    private function dev(): static
    {
        return $this->actingAs(Developer::factory()->make(['id' => 1]), 'developer');
    }

    private function publishedHotel(): Hotel
    {
        $location = Location::forceCreate([
            'name' => 'Kampot', 'slug' => 'kampot', 'status' => 'published',
        ]);

        return Hotel::forceCreate([
            'location_id' => $location->id, 'name' => 'Test Hotel',
            'slug' => 'test-hotel', 'status' => 'published',
        ]);
    }

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/admin/services')->assertUnauthorized();
        $this->getJson('/admin/products')->assertUnauthorized();
    }

    public function test_service_crud(): void
    {
        $created = $this->dev()->postJson('/admin/services', [
            'name' => 'Free WiFi', 'icon' => 'wifi', 'sortOrder' => 2,
        ])->assertCreated()
            ->assertJsonPath('service.slug', 'free-wifi')
            ->assertJsonPath('service.status', 'published')
            ->assertJsonPath('service.sortOrder', 2)
            ->assertJsonPath('service.hotelsCount', 0);

        $id = $created->json('service.id');

        // Duplicate name -> auto-suffixed slug; explicit duplicate slug -> 422.
        $this->dev()->postJson('/admin/services', ['name' => 'Free WiFi'])
            ->assertCreated()->assertJsonPath('service.slug', 'free-wifi-2');
        $this->dev()->postJson('/admin/services', ['name' => 'X', 'slug' => 'free-wifi'])
            ->assertStatus(422)->assertJsonValidationErrors('slug');
        $this->dev()->postJson('/admin/services', [])->assertStatus(422)->assertJsonValidationErrors('name');

        $this->dev()->patchJson("/admin/services/{$id}", ['status' => 'archived', 'sortOrder' => 0])
            ->assertOk()->assertJsonPath('service.status', 'archived')->assertJsonPath('service.sortOrder', 0);

        $this->dev()->getJson('/admin/services')->assertOk()->assertJsonCount(2, 'services');
        $this->dev()->getJson('/admin/services?status=archived')->assertOk()->assertJsonCount(1, 'services');
        $this->dev()->getJson("/admin/services/{$id}")->assertOk()->assertJsonPath('service.id', $id);

        $this->dev()->deleteJson("/admin/services/{$id}")->assertOk();
        $this->assertNull(Service::find($id));
    }

    public function test_product_crud_with_price(): void
    {
        $res = $this->dev()->postJson('/admin/products', [
            'name' => 'Honeymoon Package', 'price' => 120.5, 'currency' => 'usd',
            'imageUrl' => '/storage/uploads/products/x.jpg',
        ])->assertCreated()
            ->assertJsonPath('product.slug', 'honeymoon-package')
            ->assertJsonPath('product.currency', 'USD')
            ->assertJsonPath('product.imageUrl', '/storage/uploads/products/x.jpg');
        $this->assertEquals(120.5, $res->json('product.price'));

        $id = $res->json('product.id');
        $this->dev()->postJson('/admin/products', ['name' => 'Bad', 'price' => -1, 'currency' => 'US'])
            ->assertStatus(422)->assertJsonValidationErrors(['price', 'currency']);

        $this->dev()->patchJson("/admin/products/{$id}", ['price' => null])
            ->assertOk()->assertJsonPath('product.price', null);
        $this->dev()->deleteJson("/admin/products/{$id}")->assertOk();
        $this->assertNull(Product::find($id));
    }

    public function test_hotel_sync_and_public_payload(): void
    {
        $hotel = $this->publishedHotel();
        $wifi = Service::create(['name' => 'WiFi', 'slug' => 'wifi', 'sort_order' => 2]);
        $pool = Service::create(['name' => 'Pool', 'slug' => 'pool', 'sort_order' => 1]);
        $draft = Service::create(['name' => 'Hidden', 'slug' => 'hidden', 'status' => 'draft']);
        $pkg = Product::create(['name' => 'Pkg', 'slug' => 'pkg', 'price' => 10, 'currency' => 'USD']);

        $this->dev()->patchJson("/admin/hotels/{$hotel->id}", [
            'serviceIds' => [$wifi->id, $pool->id, $draft->id],
            'productIds' => [$pkg->id],
        ])->assertOk()
            ->assertJsonCount(3, 'hotel.services')
            ->assertJsonCount(1, 'hotel.products');

        $this->dev()->patchJson("/admin/hotels/{$hotel->id}", ['serviceIds' => [999]])
            ->assertStatus(422)->assertJsonValidationErrors('serviceIds.0');

        // Omitting the keys leaves selection untouched.
        $this->dev()->patchJson("/admin/hotels/{$hotel->id}", ['name' => 'Renamed'])->assertOk()
            ->assertJsonCount(3, 'hotel.services');

        // Public: published only, ordered by sortOrder, no hotelsCount.
        $public = $this->getJson('/hotels/test-hotel')->assertOk();
        $this->assertSame(['pool', 'wifi'], array_column($public->json('hotel.services'), 'slug'));
        $this->assertArrayNotHasKey('hotelsCount', $public->json('hotel.services.0'));
        $public->assertJsonPath('hotel.products.0.slug', 'pkg');

        // Empty array clears.
        $this->dev()->patchJson("/admin/hotels/{$hotel->id}", ['serviceIds' => [], 'productIds' => []])
            ->assertOk()->assertJsonCount(0, 'hotel.services')->assertJsonCount(0, 'hotel.products');

        // Deleting a catalog item detaches it.
        $this->dev()->patchJson("/admin/hotels/{$hotel->id}", ['productIds' => [$pkg->id]])->assertOk();
        $this->dev()->deleteJson("/admin/products/{$pkg->id}")->assertOk();
        $this->assertSame(0, $hotel->products()->count());
    }
}
