<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Participant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'participant']);
        $this->participant = Participant::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
        $this->putJson('/api/v1/profile', ['name' => 'Budi', 'email' => 'budi@example.com'])->assertUnauthorized();
        $this->postJson('/api/v1/profile/photo')->assertUnauthorized();
    }

    public function test_profile_returns_404_without_participant(): void
    {
        $this->user->participants()->delete();
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/profile')->assertNotFound();
    }

    public function test_can_view_profile(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.user.id', $this->user->id)
            ->assertJsonPath('data.user.email', $this->user->email)
            ->assertJsonPath('data.participant.id', $this->participant->id)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role', 'avatar'],
                    'participant' => ['id', 'name', 'email', 'membership_type'],
                ],
            ]);
    }

    public function test_can_update_profile(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/profile', [
            'name' => 'Budi Santoso',
            'email' => 'budi.new@example.com',
            'phone' => '08123456789',
            'gender' => 'male',
            'date_of_birth' => '1995-05-10',
            'address' => 'Jl. Baru No. 5',
            'emergency_contact' => 'Siti',
            'emergency_phone' => '08987654321',
            'medical_conditions' => null,
            'blood_type' => 'O',
            'jersey_size' => 'L',
        ])->assertOk()
            ->assertJsonPath('message', 'Profil berhasil diupdate')
            ->assertJsonPath('data.user.name', 'Budi Santoso')
            ->assertJsonPath('data.user.email', 'budi.new@example.com')
            ->assertJsonPath('data.participant.phone', '08123456789')
            ->assertJsonPath('data.participant.jersey_size', 'L');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Budi Santoso',
            'email' => 'budi.new@example.com',
        ]);
        $this->assertDatabaseHas('participants', [
            'id' => $this->participant->id,
            'name' => 'Budi Santoso',
            'email' => 'budi.new@example.com',
            'phone' => '08123456789',
            'gender' => 'male',
            'date_of_birth' => '1995-05-10',
            'jersey_size' => 'L',
        ]);
    }

    public function test_update_with_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        Participant::factory()->create(['email' => 'taken-participant@example.com']);

        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/profile', [
            'name' => 'Budi',
            'email' => 'taken@example.com',
        ])->assertUnprocessable();

        $this->putJson('/api/v1/profile', [
            'name' => 'Budi',
            'email' => 'taken-participant@example.com',
        ])->assertUnprocessable();
    }

    public function test_update_keeps_own_email_unique(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/profile', [
            'name' => $this->user->name,
            'email' => $this->user->email,
        ])->assertOk();
    }

    public function test_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/profile/photo', [
            'avatar' => UploadedFile::fake()->image('avatar.png'),
        ])->assertOk()
            ->assertJsonPath('message', 'Foto profil berhasil diupload');

        $path = $this->user->fresh()->avatar;

        $this->assertNotNull($path);
        $this->assertStringStartsWith('avatars/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_upload_non_image_is_rejected(): void
    {
        Storage::fake('public');

        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/profile/photo', [
            'avatar' => UploadedFile::fake()->create('file.txt', 100),
        ])->assertUnprocessable();
    }

    public function test_upload_photo_requires_participant(): void
    {
        Storage::fake('public');

        $this->user->participants()->delete();
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/profile/photo', [
            'avatar' => UploadedFile::fake()->image('avatar.png'),
        ])->assertNotFound();
    }
}
