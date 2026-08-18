<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($this->admin);
    }

    public function test_dashboard_renders(): void
    {
        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Aktivitas Terbaru');
    }

    public function test_dashboard_shows_stat_card_labels(): void
    {
        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Total Events')
            ->assertSee('Participants')
            ->assertSee('Payments')
            ->assertSee('Users');
    }

    public function test_dashboard_shows_upcoming_event_title(): void
    {
        $event = Event::factory()->create([
            'title' => 'Running Sunday Fun Run',
            'status' => 'publish',
        ]);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Running Sunday Fun Run')
            ->assertSee($event->start_date->format('M'));
    }

    public function test_dashboard_recent_activity_includes_payment_and_participant(): void
    {
        $participant = Participant::factory()->create(['name' => 'Dewi Lestari']);
        Payment::factory()->create([
            'participant_id' => $participant->id,
            'status' => 'pending',
            'invoice_number' => 'INV/20260815/TEST01',
        ]);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Dewi Lestari')
            ->assertSee('INV/20260815/TEST01');
    }

    public function test_dashboard_handles_empty_database(): void
    {
        $this->get('/admin/dashboard')->assertOk();
    }
}