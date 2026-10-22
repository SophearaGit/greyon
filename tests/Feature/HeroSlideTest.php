<?php

namespace Tests\Feature;

use App\Models\Developer;
use App\Models\HeroSlide;
use Database\Seeders\HeroSlideSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroSlideTest extends TestCase
{
    // The full migration set uses MySQL-only SQL and can't run on the phpunit
    // sqlite :memory: DB (see AdminUploadTest), so only this feature's
    // migration is run here - a fresh in-memory DB per test.
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_10_22_000100_create_hero_slides_table.php'))->up();
        Storage::fake('public');
    }

    private function dev(): Developer
    {
        return Developer::factory()->make(['id' => 1]);
    }

    private function slide(array $attrs = []): HeroSlide
    {
        return HeroSlide::create([
            'title' => 'Slide',
            'image' => 'uploads/hero-slides/a.jpg',
            ...$attrs,
        ]);
    }

    public function test_public_lists_only_visible_slides_in_order(): void
    {
        $this->slide(['title' => 'B', 'sort_order' => 2]);
        $a = $this->slide(['title' => 'A', 'sort_order' => 1, 'cta_label' => 'Go', 'cta_url' => '/hotels']);
        $this->slide(['title' => 'Inactive', 'is_active' => false]);
        $this->slide(['title' => 'Future', 'starts_at' => now()->addDay()]);
        $this->slide(['title' => 'Expired', 'ends_at' => now()->subDay()]);
        $this->slide(['title' => 'Windowed', 'sort_order' => 3, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
        $this->slide(['title' => 'Url', 'sort_order' => 4, 'image' => 'https://cdn.example.com/x.jpg']);

        $res = $this->getJson('/hero-slides')->assertOk();

        $this->assertSame(['A', 'B', 'Windowed', 'Url'], array_column($res->json('heroSlides'), 'title'));
        $res->assertJsonPath('heroSlides.0', [
            'id' => $a->id,
            'title' => 'A',
            'subtitle' => null,
            'image' => Storage::disk('public')->url('uploads/hero-slides/a.jpg') === '/storage/uploads/hero-slides/a.jpg'
                ? rtrim(config('app.url'), '/').'/storage/uploads/hero-slides/a.jpg'
                : Storage::disk('public')->url('uploads/hero-slides/a.jpg'),
            'ctaLabel' => 'Go',
            'ctaUrl' => '/hotels',
            'sortOrder' => 1,
        ]);
        $this->assertStringStartsWith('http', $res->json('heroSlides.0.image'));
        $res->assertJsonPath('heroSlides.3.image', 'https://cdn.example.com/x.jpg');
    }

    public function test_admin_routes_require_auth(): void
    {
        $s = $this->slide();

        $this->getJson('/admin/hero-slides')->assertUnauthorized();
        $this->postJson('/admin/hero-slides', ['title' => 'x', 'image' => 'uploads/x.jpg'])->assertUnauthorized();
        $this->getJson("/admin/hero-slides/{$s->id}")->assertUnauthorized();
        $this->patchJson("/admin/hero-slides/{$s->id}", ['title' => 'y'])->assertUnauthorized();
        $this->deleteJson("/admin/hero-slides/{$s->id}")->assertUnauthorized();
        $this->postJson('/admin/hero-slides/reorder', ['ids' => [$s->id]])->assertUnauthorized();
        $this->assertSame(1, HeroSlide::count());
    }

    public function test_admin_crud(): void
    {
        $this->actingAs($this->dev(), 'developer');

        $created = $this->postJson('/admin/hero-slides', [
            'title' => 'Welcome',
            'subtitle' => 'Hello',
            'image' => 'uploads/hero-slides/w.jpg',
            'ctaLabel' => 'Hotels',
            'ctaUrl' => '/hotels',
            'startsAt' => '2026-01-01 00:00:00',
        ])->assertCreated()
            ->assertJsonPath('heroSlide.title', 'Welcome')
            ->assertJsonPath('heroSlide.isActive', true)
            ->assertJsonPath('heroSlide.sortOrder', 0)
            ->assertJsonPath('heroSlide.imagePath', 'uploads/hero-slides/w.jpg');
        $id = $created->json('heroSlide.id');

        $this->postJson('/admin/hero-slides', ['title' => 'Second', 'image' => 'https://cdn.example.com/s.jpg'])
            ->assertCreated()->assertJsonPath('heroSlide.sortOrder', 1);
        $this->slide(['title' => 'Hidden', 'is_active' => false, 'sort_order' => 2]);

        $this->assertSame(
            ['Welcome', 'Second', 'Hidden'],
            array_column($this->getJson('/admin/hero-slides')->assertOk()->json('heroSlides'), 'title')
        );

        $this->getJson("/admin/hero-slides/{$id}")->assertOk()->assertJsonPath('heroSlide.ctaUrl', '/hotels');

        $this->patchJson("/admin/hero-slides/{$id}", ['title' => 'Updated', 'isActive' => false, 'ctaUrl' => 'https://greyon.site/x'])
            ->assertOk()
            ->assertJsonPath('heroSlide.title', 'Updated')
            ->assertJsonPath('heroSlide.isActive', false)
            ->assertJsonPath('heroSlide.subtitle', 'Hello');

        $this->deleteJson("/admin/hero-slides/{$id}")->assertOk();
        $this->assertNull(HeroSlide::find($id));
        $this->getJson("/admin/hero-slides/{$id}")->assertNotFound();
    }

    public function test_validation_messages_are_friendly(): void
    {
        $this->actingAs($this->dev(), 'developer');

        $this->postJson('/admin/hero-slides', [])
            ->assertStatus(422)
            ->assertJsonPath('errors.title.0', 'Please give the slide a title.')
            ->assertJsonPath('errors.image.0', 'Please upload an image for the slide.');

        $this->postJson('/admin/hero-slides', [
            'title' => str_repeat('a', 121),
            'image' => '../../etc/passwd',
            'ctaUrl' => 'javascript:alert(1)',
        ])->assertStatus(422)
            ->assertJsonPath('errors.title.0', 'The title can be at most 120 characters.')
            ->assertJsonPath('errors.image.0', 'The image must be an uploaded file path or a full https:// link.')
            ->assertJsonPath('errors.ctaUrl.0', 'The button link must start with / (like /hotels) or be a full https:// link.');

        $this->postJson('/admin/hero-slides', ['title' => 'ok', 'image' => 'uploads/a.jpg', 'ctaUrl' => '/hotels'])
            ->assertStatus(422)->assertJsonPath('errors.ctaLabel.0', 'Please add button text for the link.');

        $this->postJson('/admin/hero-slides', [
            'title' => 'ok', 'image' => 'uploads/a.jpg', 'startsAt' => '2026-02-01', 'endsAt' => '2026-01-01',
        ])->assertStatus(422)->assertJsonPath('errors.endsAt.0', 'The end date must be after the start date.');
    }

    public function test_reorder(): void
    {
        $a = $this->slide(['title' => 'A', 'sort_order' => 0]);
        $b = $this->slide(['title' => 'B', 'sort_order' => 1]);
        $c = $this->slide(['title' => 'C', 'sort_order' => 2]);

        $this->actingAs($this->dev(), 'developer')
            ->postJson('/admin/hero-slides/reorder', ['ids' => [$c->id, $a->id, $b->id]])
            ->assertOk()
            ->assertJsonPath('heroSlides.0.title', 'C');

        $this->assertSame(['C', 'A', 'B'], HeroSlide::query()->ordered()->pluck('title')->all());
        $this->assertSame(['C', 'A', 'B'], array_column($this->getJson('/hero-slides')->json('heroSlides'), 'title'));

        $this->postJson('/admin/hero-slides/reorder', [])
            ->assertStatus(422)->assertJsonPath('errors.ids.0', 'Please send the slide ids in their new order.');
        $this->postJson('/admin/hero-slides/reorder', ['ids' => [$a->id, $a->id]])
            ->assertStatus(422)->assertJson(['errors' => ['ids.1' => ['Each slide can only appear once in the new order.']]]);
        $this->postJson('/admin/hero-slides/reorder', ['ids' => [999]])
            ->assertStatus(422)->assertJson(['errors' => ['ids.0' => ['One of the slides no longer exists. Please refresh and try again.']]]);
    }

    public function test_seeder_creates_three_slides_only_when_empty(): void
    {
        $this->seed(HeroSlideSeeder::class);
        $this->assertSame(3, HeroSlide::count());
        foreach (HeroSlide::pluck('image') as $path) {
            $this->assertFileExists(database_path('seeders/assets/'.$path));
        }

        $this->seed(HeroSlideSeeder::class);
        $this->assertSame(3, HeroSlide::count());
    }
}
