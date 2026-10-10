<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regresi untuk daftar QA:
 * 1. Edit Categories (Admin)
 * 2. User Edit Profile - Email (admin edit user & edit peserta, email tidak berubah)
 * 3. Add Galleries Video - Local Upload (Admin)
 * 4. Add Galleries Video - Google Drive Link (Admin)
 */
class QaReportedFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($this->admin);
    }

    // ------------------------------------------------------------------
    // 1. Edit Categories (Admin)
    // ------------------------------------------------------------------

    public function test_admin_can_update_category_with_unchanged_slug(): void
    {
        $category = Category::factory()->create([
            'name' => 'Lari Pagi',
            'slug' => 'lari-pagi',
        ]);

        $this->put("/admin/categories/{$category->id}", [
            'name' => 'Lari Pagi (Updated)',
            'slug' => 'lari-pagi',
            'description' => 'Diperbarui',
            'distance_km' => 10,
            'sort_order' => 3,
            'icon' => 'fa-person-running',
        ])->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Lari Pagi (Updated)',
            'slug' => 'lari-pagi',
        ]);
    }

    public function test_admin_update_category_rejects_slug_used_by_other_category(): void
    {
        Category::factory()->create(['name' => 'Satu', 'slug' => 'satu']);
        $category = Category::factory()->create(['name' => 'Dua', 'slug' => 'dua']);

        $this->put("/admin/categories/{$category->id}", [
            'name' => 'Dua',
            'slug' => 'satu',
        ])->assertRedirect()
            ->assertSessionHasErrors('slug');
    }

    // ------------------------------------------------------------------
    // 2. User Edit Profile - Email
    // ------------------------------------------------------------------

    public function test_admin_can_update_user_with_unchanged_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Panitia Satu',
            'email' => 'panitia@example.com',
        ]);

        $this->put("/admin/users/{$user->id}", [
            'name' => 'Panitia Satu Updated',
            'email' => 'panitia@example.com',
            'role' => 'admin_laman',
        ])->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Panitia Satu Updated',
            'email' => 'panitia@example.com',
        ]);
    }

    public function test_admin_can_update_participant_with_unchanged_email(): void
    {
        $participant = Participant::factory()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
        ]);

        $this->put("/admin/participants/{$participant->id}", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '08123456789',
            'gender' => 'male',
            'membership_type' => 'none',
        ])->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
        ]);
    }

    // ------------------------------------------------------------------
    // 3. Add Galleries Video (Local Upload) (Admin)
    // ------------------------------------------------------------------

    public function test_admin_can_upload_video_gallery_from_local_file(): void
    {
        Storage::fake('public');

        $this->post('/admin/galleries', [
            'title' => 'Video Hash House',
            'description' => 'Rekaman lari',
            'source' => 'local',
            'type' => 'video',
            'sort_order' => 0,
            'file' => UploadedFile::fake()->create('video.mp4', 3000, 'video/mp4'),
        ])->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('galleries', [
            'title' => 'Video Hash House',
            'type' => 'video',
            'source' => 'local',
        ]);
    }

    public function test_local_gallery_upload_requires_file(): void
    {
        Storage::fake('public');

        $this->post('/admin/galleries', [
            'title' => 'Tanpa File',
            'source' => 'local',
            'type' => 'video',
        ])->assertSessionHasErrors('file');
    }

    // ------------------------------------------------------------------
    // 4. Add Galleries Video (Google Drive Link) (Admin)
    // ------------------------------------------------------------------

    public function test_admin_can_add_video_gallery_from_google_drive_link(): void
    {
        $this->post('/admin/galleries', [
            'title' => 'Video Drive',
            'description' => 'Dari Google Drive',
            'source' => 'gdrive',
            'type' => 'video',
            'google_drive_url' => 'https://drive.google.com/file/d/AbC123_-xYz/view?usp=sharing',
        ])->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('galleries', [
            'title' => 'Video Drive',
            'source' => 'gdrive',
            'type' => 'video',
            'google_drive_file_id' => 'AbC123_-xYz',
        ]);
    }

    public function test_google_drive_link_requires_drive_domain(): void
    {
        $this->post('/admin/galleries', [
            'title' => 'Link Sampah',
            'source' => 'gdrive',
            'type' => 'video',
            'google_drive_url' => 'https://example.com/file/123',
        ])->assertSessionHasErrors('google_drive_url');
    }

    public function test_google_drive_link_without_file_id_is_rejected(): void
    {
        $this->post('/admin/galleries', [
            'title' => 'Link Tanpa ID',
            'source' => 'gdrive',
            'type' => 'video',
            'google_drive_url' => 'https://drive.google.com/drive/my-drive',
        ])->assertSessionHasErrors('google_drive_url');
    }
}
