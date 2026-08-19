<?php

namespace Tests\Feature\Admin;

use App\Models\GalleryAlbum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
