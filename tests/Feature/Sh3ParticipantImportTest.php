<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Database\Seeders\Sh3ParticipantImportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Sh3ParticipantImportTest extends TestCase
{
    use RefreshDatabase;

    private function runImport(): void
    {
        $this->seed(Sh3ParticipantImportSeeder::class);
    }

    public function test_import_creates_19_participants_with_unique_nm_codes(): void
    {
        $this->runImport();

        $codes = Participant::where('hash_id', '!=', Participant::OTS_AGGREGATOR_CODE)
            ->pluck('hash_id');

        $this->assertSame(19, $codes->count());
        $this->assertSame(19, $codes->unique()->count());

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^NM\d{4}$/', $code);
        }

        foreach (['Bengkiam', 'Riri', 'Moka', 'Yuliani', '888999', 'Ipau'] as $username) {
            $user = User::where('username', $username)->first();
            $this->assertNotNull($user, "username {$username} harus ada");
            $this->assertSame('participant', $user->role);
            $this->assertTrue((bool) $user->is_active);
        }
    }

    public function test_import_creates_ots_sentinel_with_reserved_code(): void
    {
        $this->runImport();

        $this->assertSame(1, Participant::where('hash_id', Participant::OTS_AGGREGATOR_CODE)->count());
        $this->assertDatabaseHas('participants', [
            'hash_id' => Participant::OTS_AGGREGATOR_CODE,
            'name' => 'Manual OTS NON MEMBER',
            'is_active' => true,
        ]);
    }

    public function test_passwords_are_hashed_and_login_works(): void
    {
        $this->runImport();

        $user = User::where('username', 'Bengkiam')->first();
        $this->assertNotNull($user);
        $this->assertNotSame('334477', $user->password);
        $this->assertTrue(Hash::check('334477', $user->password));

        $this->postJson('/api/v1/auth/login', [
            'username' => 'Bengkiam',
            'password' => '334477',
        ])->assertOk()
            ->assertJsonPath('message', 'Login berhasil');

        $this->postJson('/api/v1/auth/login', [
            'username' => 'Bengkiam',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    public function test_import_is_idempotent_on_re_run(): void
    {
        $this->runImport();
        $this->runImport();

        $this->assertSame(19, Participant::where('hash_id', '!=', Participant::OTS_AGGREGATOR_CODE)->count());
        $this->assertSame(1, Participant::where('hash_id', Participant::OTS_AGGREGATOR_CODE)->count());
        $this->assertSame(19, User::where('role', 'participant')->count());
        $this->assertSame(0, Participant::select('hash_id')->groupBy('hash_id')->havingRaw('count(*) > 1')->count());
        $this->assertSame(0, User::select('username')->whereNotNull('username')->groupBy('username')->havingRaw('count(*) > 1')->count());
    }

    public function test_empty_email_gets_dummy_email(): void
    {
        $this->runImport();

        $this->assertDatabaseHas('participants', [
            'name' => 'Cohan Luchas',
            'email' => 'dummy+3690@example.com',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'dummy+3690@example.com',
        ]);
    }

    public function test_duplicate_email_across_source_gets_dummy_for_second(): void
    {
        $this->runImport();

        // Megawati keeps the real email, Hermawan gets a dummy.
        $this->assertDatabaseHas('participants', [
            'name' => 'Megawati',
            'email' => 'megawati23tk@gmail.com',
        ]);
        $this->assertDatabaseHas('participants', [
            'name' => 'Hermawan sulistio',
            'email' => 'dummy+2431@example.com',
        ]);
    }

    public function test_invalid_username_is_normalized(): void
    {
        $this->runImport();

        $this->assertDatabaseHas('users', [
            'username' => 'Budi_Kang',
        ]);
    }
}
