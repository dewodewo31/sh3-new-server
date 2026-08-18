<?php

namespace Tests\Feature\Admin;

use App\Models\Bookkeeping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookkeepingAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_can_list_bookkeepings(): void
    {
        $entry = Bookkeeping::factory()->create(['description' => 'Catatan list test']);
        $this->actingAs($this->user('bendahara'));

        $this->get('/admin/bookkeepings')
            ->assertOk()
            ->assertSee('Catatan list test');
    }

    public function test_can_create_bookkeeping_with_receipt(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user('bendahara'));

        $this->post('/admin/bookkeepings', [
            'transaction_date' => '2026-08-18',
            'description' => 'Pemasukan iuran',
            'type' => 'income',
            'amount' => 50000,
            'category' => 'other',
            'receipt' => UploadedFile::fake()->image('nota.jpg'),
        ])->assertRedirect(route('admin.bookkeepings.index'));

        $this->assertDatabaseHas('bookkeepings', [
            'description' => 'Pemasukan iuran',
            'type' => 'income',
            'amount' => 50000,
            'category' => 'other',
        ]);

        $this->assertNotEmpty(Storage::disk('public')->files('bookkeepings'));
    }

    public function test_category_sponsor_requires_sponsor_id(): void
    {
        $this->actingAs($this->user('bendahara'));

        $this->post('/admin/bookkeepings', [
            'transaction_date' => '2026-08-18',
            'description' => 'Sponsor tanpa id',
            'type' => 'income',
            'amount' => 100000,
            'category' => 'sponsor',
        ])->assertSessionHasErrors('sponsor_id');
    }

    public function test_can_update_bookkeeping(): void
    {
        $entry = Bookkeeping::factory()->create(['amount' => 10000]);
        $this->actingAs($this->user('bendahara'));

        $this->put('/admin/bookkeepings/'.$entry->id, [
            'transaction_date' => '2026-08-18',
            'description' => 'Diperbarui',
            'type' => 'expense',
            'amount' => 75000,
            'category' => 'other',
        ])->assertRedirect(route('admin.bookkeepings.index'));

        $this->assertDatabaseHas('bookkeepings', [
            'id' => $entry->id,
            'description' => 'Diperbarui',
            'amount' => 75000,
        ]);
    }

    public function test_can_delete_bookkeeping_and_removes_receipt(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bookkeepings/nota.jpg', 'fake-image-content');

        $entry = Bookkeeping::factory()->create([
            'description' => 'Akan dihapus',
            'receipt' => 'bookkeepings/nota.jpg',
        ]);
        $this->actingAs($this->user('bendahara'));

        $this->delete('/admin/bookkeepings/'.$entry->id)
            ->assertRedirect(route('admin.bookkeepings.index'));

        $this->assertDatabaseMissing('bookkeepings', ['id' => $entry->id]);
        Storage::disk('public')->assertMissing('bookkeepings/nota.jpg');
    }

    public function test_totals_reflect_income_and_expense(): void
    {
        Bookkeeping::factory()->create(['type' => 'income', 'amount' => 100000, 'category' => 'other']);
        Bookkeeping::factory()->create(['type' => 'expense', 'amount' => 40000, 'category' => 'other']);
        $this->actingAs($this->user('bendahara'));

        $this->get('/admin/bookkeepings')
            ->assertOk()
            ->assertSee('Rp 100.000')
            ->assertSee('Rp 40.000')
            ->assertSee('Rp 60.000');
    }
}
