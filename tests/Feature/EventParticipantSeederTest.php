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

    public function test_seeder_writes_unique_sh3_ticket_qr_code(): void
    {
        $this->runSeeder();

        $registrations = EventParticipant::with('participant')->get();

        $this->assertTrue($registrations->isNotEmpty());

        $codes = [];
        foreach ($registrations as $registration) {
            $this->assertMatchesRegularExpression('/^SH3-\d+-\d{2}-[A-Z0-9]{6}$/', (string) $registration->qr_code);
            $this->assertNotSame($registration->participant->hash_id, $registration->qr_code);
            $codes[] = $registration->qr_code;
        }

        // every ticket code is unique
        $this->assertSame(count($codes), count(array_unique($codes)));
        $this->assertSame($registrations->count(), EventParticipant::where('qr_code', 'like', 'SH3-%')->count());
    }

    public function test_generate_qr_route_produces_unique_sh3_code(): void
    {
        $this->runSeeder();

        $admin = User::factory()->create(['role' => 'admin_full_access']);
        $this->actingAs($admin);

        $registration = EventParticipant::first();

        $this->post("/admin/attendance/event-participant/{$registration->id}/generate-qr")
            ->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $registration->fresh();
        $this->assertMatchesRegularExpression('/^SH3-\d+-\d{2}-[A-Z0-9]{6}$/', (string) $fresh->qr_code);
        $this->assertNotSame($fresh->participant->hash_id, $fresh->qr_code);

        // a second regeneration still yields a valid, unique (different) code
        $before = $fresh->qr_code;
        $this->post("/admin/attendance/event-participant/{$registration->id}/generate-qr")
            ->assertRedirect();
        $after = $registration->fresh()->qr_code;
        $this->assertMatchesRegularExpression('/^SH3-\d+-\d{2}-[A-Z0-9]{6}$/', (string) $after);
        $this->assertNotSame($before, $after);
    }
}
