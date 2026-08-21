<?php

namespace Tests\Feature\Admin;

use App\Models\GalleryAlbum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GalleryAlbumAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin_full_access']);
    }

    public function test_can_create_album_with_gdrive_folder_url(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/gallery-albums', [
            'title' => 'SH3 Anniversary Album',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUv',
        ])->assertRedirect(route('admin.gallery-albums.index'));

        $this->assertDatabaseHas('gallery_albums', [
            'title' => 'SH3 Anniversary Album',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUv',
        ]);
    }

    public function test_can_update_album_gdrive_folder_url(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Old Album',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/OLD_FOLDER_1',
        ]);
        $this->actingAs($this->admin());

        $this->put("/admin/gallery-albums/{$album->id}", [
            'title' => 'Old Album',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/NEW_FOLDER_2',
        ])->assertRedirect(route('admin.gallery-albums.index'));

        $this->assertDatabaseHas('gallery_albums', [
            'id' => $album->id,
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/NEW_FOLDER_2',
        ]);
    }

    public function test_gdrive_folder_url_rejects_non_drive_domain(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/gallery-albums', [
            'title' => 'Invalid Folder',
            'gdrive_folder_url' => 'https://example.com/some-folder',
        ])->assertSessionHasErrors('gdrive_folder_url');

        $this->assertDatabaseMissing('gallery_albums', ['title' => 'Invalid Folder']);
    }

    public function test_gdrive_folder_url_rejects_non_url(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/gallery-albums', [
            'title' => 'Invalid Folder',
            'gdrive_folder_url' => 'not-a-url',
        ])->assertSessionHasErrors('gdrive_folder_url');

        $this->assertDatabaseMissing('gallery_albums', ['title' => 'Invalid Folder']);
    }

    public function test_create_form_renders_gdrive_folder_url_field(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/gallery-albums/create')
            ->assertOk()
            ->assertSee('gdrive_folder_url')
            ->assertSee('Anyone with the link can view');
    }

    public function test_index_renders_drive_badge_for_album_with_folder_url(): void
    {
        GalleryAlbum::create([
            'title' => 'Album With Drive',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUv',
        ]);
        $this->actingAs($this->admin());

        $this->get('/admin/gallery-albums')
            ->assertOk()
            ->assertSee('Album With Drive')
            ->assertSee('href="https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUv"', false)
            ->assertSee('>Drive</a>', false);
    }

    public function test_index_does_not_render_drive_link_for_album_without_folder_url(): void
    {
        GalleryAlbum::create(['title' => 'Album Without Drive']);
        $this->actingAs($this->admin());

        $this->get('/admin/gallery-albums')
            ->assertOk()
            ->assertSee('Album Without Drive')
            ->assertDontSee('href="https://drive.google.com', false);
    }

    public function test_sync_now_creates_galleries_and_redirects_with_success(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Sync Album',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FOLDER',
        ]);
        $this->actingAs($this->admin());

        Http::fake(['www.googleapis.com/*' => Http::response([
            'files' => [['id' => 'img1', 'name' => 'foto.jpg', 'mimeType' => 'image/jpeg']],
            'nextPageToken' => null,
        ], 200)]);

        $this->post('/admin/gallery-albums/sync')
            ->assertRedirect(route('admin.gallery-albums.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('galleries', [
            'gallery_album_id' => $album->id,
            'google_drive_file_id' => 'img1',
            'source' => 'gdrive',
        ]);
    }

    public function test_sync_now_forbidden_for_bendahara(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'bendahara']));

        $this->post('/admin/gallery-albums/sync')->assertStatus(403);
    }

    public function test_sync_now_allowed_for_gallery_role(): void
    {
        GalleryAlbum::create([
            'title' => 'Sync Album',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FOLDER',
        ]);
        $this->actingAs(User::factory()->create(['role' => 'gallery']));

        Http::fake(['www.googleapis.com/*' => Http::response([
            'files' => [],
            'nextPageToken' => null,
        ], 200)]);

        $this->post('/admin/gallery-albums/sync')
            ->assertRedirect(route('admin.gallery-albums.index'))
            ->assertSessionHas('success');
    }

    public function test_sync_now_forbidden_for_sponsor(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'sponsor']));

        $this->post('/admin/gallery-albums/sync')->assertStatus(403);
    }

    public function test_index_renders_sync_button_and_status_column(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/gallery-albums')
            ->assertOk()
            ->assertSee('Sync Drive');
    }

    public function test_index_renders_sync_error_badge_for_failed_album(): void
    {
        GalleryAlbum::create([
            'title' => 'Failed Album',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/FA',
            'gdrive_sync_error' => 'Folder tidak dapat diakses.',
        ]);
        $this->actingAs($this->admin());

        $this->get('/admin/gallery-albums')
            ->assertOk()
            ->assertSee('Folder tidak dapat diakses.');
    }

    public function test_store_rejects_non_folder_drive_url(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/gallery-albums', [
            'title' => 'Bad Url Album',
            'gdrive_folder_url' => 'https://drive.google.com/file/d/XYZ/view',
        ])->assertSessionHasErrors('gdrive_folder_url');

        $this->assertDatabaseMissing('gallery_albums', ['title' => 'Bad Url Album']);

        $this->post('/admin/gallery-albums', [
            'title' => 'Good Url Album',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUv',
        ])->assertRedirect(route('admin.gallery-albums.index'));

        $this->assertDatabaseHas('gallery_albums', ['title' => 'Good Url Album']);
    }

    public function test_update_keeps_legacy_non_folder_url_unchanged(): void
    {
        $legacyUrl = 'https://drive.google.com/file/d/LEGACY123/view';
        $album = GalleryAlbum::create([
            'title' => 'Legacy Album',
            'gdrive_folder_url' => $legacyUrl,
        ]);
        $this->actingAs($this->admin());

        $this->put("/admin/gallery-albums/{$album->id}", [
            'title' => 'Legacy Album',
            'gdrive_folder_url' => $legacyUrl,
        ])->assertRedirect(route('admin.gallery-albums.index'));

        $this->put("/admin/gallery-albums/{$album->id}", [
            'title' => 'Legacy Album',
            'gdrive_folder_url' => 'https://drive.google.com/open?id=OTHER456',
        ])->assertSessionHasErrors('gdrive_folder_url');

        $this->put("/admin/gallery-albums/{$album->id}", [
            'title' => 'Legacy Album',
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/NEWFOLDER789',
        ])->assertRedirect(route('admin.gallery-albums.index'));

        $this->assertDatabaseHas('gallery_albums', [
            'id' => $album->id,
            'gdrive_folder_url' => 'https://drive.google.com/drive/folders/NEWFOLDER789',
        ]);
    }
}
