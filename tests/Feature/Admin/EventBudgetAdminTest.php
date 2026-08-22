<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventBudgetAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeEvent(): Event
    {
        return Event::factory()->create();
    }

    private function makeActivity(): Activity
    {
        return Activity::factory()->create();
    }

    public function test_index_lists_budgets(): void
    {
        $event = $this->makeEvent();
        $activity = $this->makeActivity();
        EventBudget::factory()->create([
            'event_id' => $event->id,
            'activity_id' => $activity->id,
            'amount' => 75000,
        ]);

        $this->actingAs($this->user('bendahara'))
            ->get('/admin/event-budgets')
            ->assertOk()
            ->assertSee($event->title)
            ->assertSee($activity->name);
    }

    public function test_create_page_loads_with_options(): void
    {
        $this->makeEvent();
        $this->makeActivity();

        $this->actingAs($this->user('bendahara'))
            ->get('/admin/event-budgets/create')
            ->assertOk();
    }

    public function test_store_creates_budget(): void
    {
        $event = $this->makeEvent();
        $activity = $this->makeActivity();

        $this->actingAs($this->user('bendahara'))
            ->post('/admin/event-budgets', [
                'event_id' => $event->id,
                'activity_id' => $activity->id,
                'amount' => 50000,
                'notes' => 'Anggaran latihan',
            ])
            ->assertRedirect(route('admin.event-budgets.index'));

        $this->assertDatabaseHas('event_budgets', [
            'event_id' => $event->id,
            'activity_id' => $activity->id,
            'amount' => 50000,
            'notes' => 'Anggaran latihan',
        ]);
    }

    public function test_store_validation_errors(): void
    {
        $this->actingAs($this->user('bendahara'))
            ->post('/admin/event-budgets', [
                'amount' => -5,
            ])
            ->assertSessionHasErrors(['event_id', 'activity_id', 'amount']);

        $this->assertDatabaseCount('event_budgets', 0);
    }

    public function test_edit_page_loads(): void
    {
        $budget = EventBudget::factory()->create([
            'event_id' => $this->makeEvent()->id,
            'activity_id' => $this->makeActivity()->id,
        ]);

        $this->actingAs($this->user('bendahara'))
            ->get('/admin/event-budgets/'.$budget->id.'/edit')
            ->assertOk();
    }

    public function test_update_budget(): void
    {
        $budget = EventBudget::factory()->create([
            'event_id' => $this->makeEvent()->id,
            'activity_id' => $this->makeActivity()->id,
            'amount' => 10000,
        ]);

        $this->actingAs($this->user('bendahara'))
            ->put('/admin/event-budgets/'.$budget->id, [
                'event_id' => $budget->event_id,
                'activity_id' => $budget->activity_id,
                'amount' => 25000,
                'notes' => 'Revisi',
            ])
            ->assertRedirect(route('admin.event-budgets.index'));

        $this->assertDatabaseHas('event_budgets', [
            'id' => $budget->id,
            'amount' => 25000,
            'notes' => 'Revisi',
        ]);
    }

    public function test_destroy_budget(): void
    {
        $budget = EventBudget::factory()->create([
            'event_id' => $this->makeEvent()->id,
            'activity_id' => $this->makeActivity()->id,
        ]);

        $this->actingAs($this->user('bendahara'))
            ->delete('/admin/event-budgets/'.$budget->id)
            ->assertRedirect(route('admin.event-budgets.index'));

        $this->assertDatabaseMissing('event_budgets', ['id' => $budget->id]);
    }

    public function test_403_for_role_outside_group(): void
    {
        $this->actingAs($this->user('organizer'))
            ->get('/admin/event-budgets')
            ->assertStatus(403);
    }
}
