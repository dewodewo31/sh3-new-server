<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_index_lists_activities(): void
    {
        Activity::factory()->create(['name' => 'Lari Pagi']);

        $this->actingAs($this->user('bendahara'))
            ->get('/admin/activities')
            ->assertOk()
            ->assertSee('Lari Pagi');
    }

    public function test_create_page_loads(): void
    {
        $this->actingAs($this->user('bendahara'))
            ->get('/admin/activities/create')
            ->assertOk();
    }

    public function test_store_creates_activity(): void
    {
        $this->actingAs($this->user('bendahara'))
            ->post('/admin/activities', [
                'name' => 'City Run',
                'sort_order' => 5,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.activities.index'));

        $this->assertDatabaseHas('activities', [
            'name' => 'City Run',
            'sort_order' => 5,
            'is_active' => true,
        ]);
    }

    public function test_store_validation_errors(): void
    {
        $this->actingAs($this->user('bendahara'))
            ->post('/admin/activities', [])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseCount('activities', 0);
    }

    public function test_edit_page_loads(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs($this->user('bendahara'))
            ->get('/admin/activities/'.$activity->id.'/edit')
            ->assertOk();
    }

    public function test_update_activity(): void
    {
        $activity = Activity::factory()->create(['name' => 'Lama', 'is_active' => true]);

        $this->actingAs($this->user('bendahara'))
            ->put('/admin/activities/'.$activity->id, [
                'name' => 'Baru',
                'sort_order' => 9,
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.activities.index'));

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'name' => 'Baru',
            'sort_order' => 9,
            'is_active' => false,
        ]);
    }

    public function test_destroy_activity(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs($this->user('bendahara'))
            ->delete('/admin/activities/'.$activity->id)
            ->assertRedirect(route('admin.activities.index'));

        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }

    public function test_403_for_role_outside_group(): void
    {
        $this->actingAs($this->user('organizer'))
            ->get('/admin/activities')
            ->assertStatus(403);
    }
}
