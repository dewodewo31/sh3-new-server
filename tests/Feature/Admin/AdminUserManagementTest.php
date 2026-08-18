<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($this->admin);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Andi Wijaya',
            'email' => 'andi@example.com',
            'password' => 'password123',
            'role' => 'organizer',
            'is_active' => 1,
        ], $overrides);
    }

    public function test_admin_can_view_user_list(): void
    {
        User::factory()->create(['name' => 'Budi Admin', 'role' => 'admin_full_access']);

        $this->get('/admin/users')
            ->assertOk()
            ->assertSee('Budi Admin');
    }

    public function test_admin_can_view_create_user_page(): void
    {
        $this->get('/admin/users/create')
            ->assertOk()
            ->assertSee('Buat User');
    }

    public function test_admin_can_create_user(): void
    {
        $this->post('/admin/users', $this->validPayload())
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User berhasil dibuat');

        $this->assertDatabaseHas('users', [
            'name' => 'Andi Wijaya',
            'email' => 'andi@example.com',
            'role' => 'organizer',
            'is_active' => true,
        ]);

        $this->assertDatabaseCount('user_activity_logs', 1);
    }

    public function test_create_user_requires_password(): void
    {
        $this->from('/admin/users/create')->post('/admin/users', $this->validPayload([
            'password' => '',
        ]))->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors('password');
    }

    public function test_create_user_requires_valid_role(): void
    {
        $this->from('/admin/users/create')->post('/admin/users', $this->validPayload([
            'role' => 'super_root',
        ]))->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors('role');
    }

    public function test_create_user_with_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'andi@example.com']);

        $this->post('/admin/users', $this->validPayload())
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_view_user_detail(): void
    {
        $user = User::factory()->create(['name' => 'Sinta Dewi']);

        $this->get('/admin/users/'.$user->id)
            ->assertOk()
            ->assertSee('Sinta Dewi');
    }

    public function test_admin_can_view_edit_user_page(): void
    {
        $user = User::factory()->create(['name' => 'Sinta Dewi']);

        $this->get('/admin/users/'.$user->id.'/edit')
            ->assertOk()
            ->assertSee('Sinta Dewi');
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->put('/admin/users/'.$user->id, $this->validPayload([
            'name' => 'New Name',
            'email' => 'new@example.com',
            'password' => 'newpassword123',
        ]))->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User berhasil diupdate');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_update_user_keeps_password_when_not_provided(): void
    {
        $user = User::factory()->create(['email' => 'keep@example.com']);

        $this->put('/admin/users/'.$user->id, $this->validPayload([
            'email' => 'keep@example.com',
            'password' => '',
        ]))->assertRedirect(route('admin.users.index'));

        $this->assertTrue($user->fresh()->password === $user->password);
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->from('/admin/users')->put('/admin/users/'.$user->id.'/toggle-active')
            ->assertRedirect('/admin/users')
            ->assertSessionHas('success', 'Status user berhasil diubah');

        $this->assertFalse($user->fresh()->is_active);

        $this->put('/admin/users/'.$user->id.'/toggle-active');

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->create(['name' => 'To Delete']);

        $this->delete('/admin/users/'.$user->id)
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User berhasil dihapus');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseCount('user_activity_logs', 1);
    }

    public function test_show_nonexistent_user_returns_404(): void
    {
        $this->get('/admin/users/99999')->assertNotFound();
    }
}