<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\EventParticipantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventParticipantSeederTest extends TestCase
{
    use RefreshDatabase;

    private function runSeeder(): void
    {
        User::factory()->create(['role' => 'bendahara']);
        User::factory()->create(['role' => 'admin_bnh']);

        Category::factory()->create();
        Event::factory()->count(2)->create([
            'status' => 'publish',
            'price' => 0,
        ]);

        Participant::factory()->count(3)->create();

        $this->seed(EventParticipantSeeder::class);
    }

    public function test_seeder_writes_participant_code_as_qr_code(): void
    {
        $this->runSeeder();

        $registrations = EventParticipant::with('participant')->get();

        $this->assertTrue($registrations->isNotEmpty());

        foreach ($registrations as $registration) {
            $this->assertSame($registration->participant->participant_code, $registration->qr_code);
            $this->assertStringStartsNotWith('SH3-', (string) $registration->qr_code);
        }

        $this->assertSame(0, EventParticipant::where('qr_code', 'like', 'SH3-%')->count());
    }

    public function test_generate_qr_route_is_idempotent(): void
    {
        $this->runSeeder();

        $admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($admin);

        $registration = EventParticipant::first();

        $this->post("/admin/attendance/event-participant/{$registration->id}/generate-qr")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($registration->participant->participant_code, $registration->fresh()->qr_code);

        $this->post("/admin/attendance/event-participant/{$registration->id}/generate-qr")
            ->assertRedirect();

        $this->assertSame($registration->participant->participant_code, $registration->fresh()->qr_code);
    }
}
