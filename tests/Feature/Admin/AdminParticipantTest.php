<?php

namespace Tests\Feature\Admin;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminParticipantTest extends TestCase
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
            'name' => 'Rudi Hartono',
            'email' => 'rudi@example.com',
            'phone' => '08123456789',
            'gender' => 'male',
            'date_of_birth' => '1995-05-10',
            'address' => 'Jl. Kenanga No. 2',
            'emergency_contact' => 'Siti',
            'emergency_phone' => '08987654321',
            'jersey_size' => 'L',
            'membership_type' => 'none',
        ], $overrides);
    }

    public function test_admin_can_view_participant_list(): void
    {
        Participant::factory()->create(['name' => 'Joko Susilo']);

        $this->get('/admin/participants')
            ->assertOk()
            ->assertSee('Joko Susilo');
    }

    public function test_admin_can_view_create_participant_page(): void
    {
        $this->get('/admin/participants/create')
            ->assertOk();
    }

    public function test_admin_can_create_participant(): void
    {
        $this->post('/admin/participants', $this->validPayload())
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('success', 'Peserta berhasil ditambahkan');

        $this->assertDatabaseHas('participants', [
            'name' => 'Rudi Hartono',
            'email' => 'rudi@example.com',
            'jersey_size' => 'L',
        ]);

        $this->assertDatabaseCount('user_activity_logs', 1);
    }

    public function test_create_participant_requires_name_and_email(): void
    {
        $this->from('/admin/participants/create')->post('/admin/participants', [])
            ->assertRedirect('/admin/participants/create')
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_create_participant_with_duplicate_email_is_rejected(): void
    {
        Participant::factory()->create(['email' => 'rudi@example.com']);

        $this->post('/admin/participants', $this->validPayload())
            ->assertSessionHasErrors('email');
    }

    public function test_create_participant_with_invalid_gender_is_rejected(): void
    {
        $this->post('/admin/participants', $this->validPayload([
            'gender' => 'alien',
        ]))->assertSessionHasErrors('gender');
    }

    public function test_admin_can_view_participant_detail(): void
    {
        $participant = Participant::factory()->create(['name' => 'Sinta Dewi']);

        $this->get('/admin/participants/'.$participant->id)
            ->assertOk()
            ->assertSee('Sinta Dewi');
    }

    public function test_admin_can_view_edit_participant_page(): void
    {
        $participant = Participant::factory()->create(['name' => 'Sinta Dewi']);

        $this->get('/admin/participants/'.$participant->id.'/edit')
            ->assertOk()
            ->assertSee('Sinta Dewi');
    }

    public function test_admin_can_update_participant(): void
    {
        $participant = Participant::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->put('/admin/participants/'.$participant->id, $this->validPayload([
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]))->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('success', 'Data peserta berhasil diupdate');

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_admin_can_delete_participant(): void
    {
        $participant = Participant::factory()->create(['name' => 'To Delete']);

        $this->delete('/admin/participants/'.$participant->id)
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('success', 'Peserta berhasil dihapus');

        $this->assertDatabaseMissing('participants', ['id' => $participant->id]);
        $this->assertDatabaseCount('user_activity_logs', 1);
    }

    public function test_show_nonexistent_participant_returns_404(): void
    {
        $this->get('/admin/participants/99999')->assertNotFound();
    }
}