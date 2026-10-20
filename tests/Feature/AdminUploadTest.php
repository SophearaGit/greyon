<?php

namespace Tests\Feature;

use App\Models\Developer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUploadTest extends TestCase
{
    // No RefreshDatabase: the existing migrations use MySQL-only SQL (ALTER ... MODIFY)
    // that fails on the phpunit sqlite :memory: DB, and these tests need no DB rows.
    // Unsaved models are enough for the session guards.

    private const JSON = ['Accept' => 'application/json'];

    public function test_staff_can_upload_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs(Developer::factory()->make(['id' => 1]), 'developer')
            ->post('/admin/uploads', [
                'file' => UploadedFile::fake()->image('hero.jpg', 1200, 800),
                'folder' => 'hotels/demo',
            ], self::JSON);

        $response->assertCreated()
            ->assertJsonStructure(['url', 'path', 'width', 'height', 'size', 'mime'])
            ->assertJsonPath('width', 1200)
            ->assertJsonPath('height', 800);

        $path = $response->json('path');
        $this->assertStringStartsWith('uploads/hotels/demo/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_folder_traversal_is_neutralised(): void
    {
        Storage::fake('public');

        $response = $this->actingAs(Developer::factory()->make(['id' => 1]), 'developer')
            ->post('/admin/uploads', [
                'file' => UploadedFile::fake()->image('x.png'),
                'folder' => '../../etc',
            ], self::JSON);

        $response->assertCreated();
        $this->assertStringStartsWith('uploads/misc/', $response->json('path'));
    }

    public function test_rejects_non_image(): void
    {
        Storage::fake('public');

        $this->actingAs(Developer::factory()->make(['id' => 1]), 'developer')
            ->post('/admin/uploads', [
                'file' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            ], self::JSON)
            ->assertStatus(422);
    }

    public function test_rejects_image_over_10mb(): void
    {
        Storage::fake('public');

        $this->actingAs(Developer::factory()->make(['id' => 1]), 'developer')
            ->post('/admin/uploads', [
                'file' => UploadedFile::fake()->image('big.jpg')->size(11000),
            ], self::JSON)
            ->assertStatus(422);
    }

    public function test_guest_cannot_upload(): void
    {
        Storage::fake('public');

        $this->post('/admin/uploads', [
            'file' => UploadedFile::fake()->image('x.jpg'),
        ], self::JSON)->assertUnauthorized();
    }

    public function test_delete_removes_upload_and_guards_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/media/a.jpg', 'x');
        Storage::disk('public')->put('hotels/keep.jpg', 'x');
        $dev = Developer::factory()->make(['id' => 1]);

        $this->actingAs($dev, 'developer')
            ->deleteJson('/admin/uploads', ['path' => 'uploads/media/a.jpg'])
            ->assertOk()->assertJson(['ok' => true]);
        Storage::disk('public')->assertMissing('uploads/media/a.jpg');

        $this->actingAs($dev, 'developer')
            ->deleteJson('/admin/uploads', ['path' => 'hotels/keep.jpg'])
            ->assertStatus(422);
        $this->actingAs($dev, 'developer')
            ->deleteJson('/admin/uploads', ['path' => 'uploads/../hotels/keep.jpg'])
            ->assertStatus(422);
        Storage::disk('public')->assertExists('hotels/keep.jpg');
    }
}
