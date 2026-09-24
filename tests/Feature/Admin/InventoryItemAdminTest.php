<?php

namespace Tests\Feature\Admin;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryItemAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin/inventory')->assertRedirect('/login');
    }

    public function test_index_is_forbidden_for_participant_and_sponsor(): void
    {
        $this->actingAs($this->user('participant'))->get('/admin/inventory')->assertForbidden();
        $this->actingAs($this->user('sponsor'))->get('/admin/inventory')->assertForbidden();
    }

    public function test_admin_full_access_can_view_index_create_and_show(): void
    {
        $admin = $this->user('admin_full_access');
        $item = InventoryItem::factory()->create();

        $this->actingAs($admin)->get('/admin/inventory')->assertOk();
        $this->actingAs($admin)->get('/admin/inventory/create')->assertOk();
        $this->actingAs($admin)->get("/admin/inventory/{$item->id}")->assertOk();
    }

    public function test_organizer_can_view_index_but_not_create(): void
    {
        $organizer = $this->user('organizer');

        $this->actingAs($organizer)->get('/admin/inventory')->assertOk();
        $this->actingAs($organizer)->get('/admin/inventory/create')->assertForbidden();
    }

    public function test_store_creates_inventory_item_with_auto_asset_code(): void
    {
        $admin = $this->user('admin_full_access');

        $response = $this->actingAs($admin)->post('/admin/inventory', [
            'name' => 'Laptop ASUS',
            'brand' => 'ASUS',
            'model' => 'VivoBook',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $response->assertRedirect(route('admin.inventory.index'));

        $this->assertDatabaseHas('inventory_items', [
            'asset_code' => 'AST-0001',
            'name' => 'Laptop ASUS',
            'created_by' => $admin->id,
        ]);

        // Second item continues the sequence.
        $this->actingAs($admin)->post('/admin/inventory', ['name' => 'Speaker JBL']);

        $this->assertDatabaseHas('inventory_items', [
            'asset_code' => 'AST-0002',
            'name' => 'Speaker JBL',
        ]);
    }

    public function test_update_inventory_item_keeps_asset_code(): void
    {
        $admin = $this->user('admin_full_access');
        $item = InventoryItem::factory()->create(['asset_code' => 'AST-UPD']);

        $response = $this->actingAs($admin)->put("/admin/inventory/{$item->id}", [
            'name' => 'Updated Name',
            'status' => 'borrowed',
        ]);

        $response->assertRedirect(route('admin.inventory.index'));

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'asset_code' => 'AST-UPD',
            'name' => 'Updated Name',
            'status' => 'borrowed',
            'updated_by' => $admin->id,
        ]);
    }

    public function test_destroy_inventory_item_and_photos(): void
    {
        Storage::fake('public');
        $admin = $this->user('admin_full_access');
        $item = InventoryItem::factory()->create();
        $photo = InventoryPhoto::create([
            'inventory_item_id' => $item->id,
            'context' => 'gallery',
            'file_path' => 'inventory/photos/photo.jpg',
            'uploaded_by' => $admin->id,
        ]);
        Storage::disk('public')->put('inventory/photos/photo.jpg', 'fake');

        $this->actingAs($admin)
            ->delete("/admin/inventory/{$item->id}")
            ->assertRedirect(route('admin.inventory.index'));

        $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('inventory_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing('inventory/photos/photo.jpg');
    }

    public function test_upload_and_delete_photo(): void
    {
        Storage::fake('public');
        $admin = $this->user('admin_full_access');
        $item = InventoryItem::factory()->create();

        $response = $this->actingAs($admin)->post("/admin/inventory/{$item->id}/photos", [
            'photo' => UploadedFile::fake()->create('photo.jpg', 100),
            'context' => 'gallery',
            'caption' => 'Front view',
        ]);

        $response->assertRedirect(route('admin.inventory.show', $item->id));

        $photo = InventoryPhoto::first();
        $this->assertNotNull($photo);
        $this->assertDatabaseHas('inventory_photos', [
            'inventory_item_id' => $item->id,
            'context' => 'gallery',
            'caption' => 'Front view',
            'uploaded_by' => $admin->id,
        ]);
        Storage::disk('public')->assertExists($photo->file_path);

        $this->actingAs($admin)
            ->delete("/admin/inventory/photos/{$photo->id}")
            ->assertRedirect(route('admin.inventory.show', $item->id));

        $this->assertDatabaseMissing('inventory_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($photo->file_path);
    }

    public function test_index_filters_by_status_and_category(): void
    {
        $admin = $this->user('admin_full_access');
        $category = InventoryCategory::create(['name' => 'Audio', 'slug' => 'audio']);
        InventoryItem::factory()->create(['name' => 'Speaker A', 'status' => 'available']);
        InventoryItem::factory()->create(['name' => 'Speaker B', 'status' => 'borrowed', 'category_id' => $category->id]);

        $this->actingAs($admin)
            ->get('/admin/inventory?status=borrowed&category_id='.$category->id)
            ->assertOk()
            ->assertSee('Speaker B')
            ->assertDontSee('Speaker A');
    }

    public function test_store_rejects_invalid_status(): void
    {
        $admin = $this->user('admin_full_access');

        $this->actingAs($admin)->post('/admin/inventory', [
            'name' => 'Bad Status',
            'status' => 'exploded',
        ])->assertSessionHasErrors('status');
    }
}
