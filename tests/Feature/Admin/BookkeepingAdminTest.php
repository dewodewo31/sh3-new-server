<?php

namespace Tests\Feature\Admin;

use App\Models\Bookkeeping;
use App\Models\Sponsor;
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
        $entry = Bookkeeping::factory()->create(['amount' => 10000, 'status' => Bookkeeping::STATUS_DRAFT]);
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

    public function test_update_clears_stale_sponsor_when_category_changed(): void
    {
        $sponsor = Sponsor::factory()->create();
        $entry = Bookkeeping::factory()->create([
            'category' => 'sponsor',
            'sponsor_id' => $sponsor->id,
            'status' => Bookkeeping::STATUS_DRAFT,
        ]);
        $this->actingAs($this->user('bendahara'));

        $this->put('/admin/bookkeepings/'.$entry->id, [
            'transaction_date' => '2026-08-18',
            'description' => 'Ganti ke lain',
            'type' => 'expense',
            'amount' => 20000,
            'category' => 'other',
        ])->assertRedirect(route('admin.bookkeepings.index'));

        $this->assertDatabaseHas('bookkeepings', [
            'id' => $entry->id,
            'category' => 'other',
            'sponsor_id' => null,
        ]);
    }

    public function test_can_delete_bookkeeping_and_removes_receipt(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bookkeepings/nota.jpg', 'fake-image-content');

        $entry = Bookkeeping::factory()->create([
            'description' => 'Akan dihapus',
            'receipt' => 'bookkeepings/nota.jpg',
            'status' => Bookkeeping::STATUS_DRAFT,
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

    // --- Lifecycle: submit / approve / mark-paid / cancel ---

    public function test_submit_moves_draft_to_submitted(): void
    {
        $entry = Bookkeeping::factory()->create([
            'status' => Bookkeeping::STATUS_DRAFT,
            'type' => 'expense', 'amount' => 1000, 'category' => 'other',
        ]);
        $this->actingAs($this->user('admin_full_access'));

        $this->put('/admin/bookkeepings/'.$entry->id.'/submit')
            ->assertRedirect(route('admin.bookkeepings.index'));

        $this->assertDatabaseHas('bookkeepings', ['id' => $entry->id, 'status' => Bookkeeping::STATUS_SUBMITTED]);
    }

    public function test_approve_moves_to_approved_not_paid(): void
    {
        $this->actingAs($this->user('admin_full_access'));

        // draft fast-tracks to approved
        $draft = Bookkeeping::factory()->create([
            'status' => Bookkeeping::STATUS_DRAFT,
            'type' => 'expense', 'amount' => 1000, 'category' => 'other',
        ]);
        $this->put('/admin/bookkeepings/'.$draft->id.'/approve')
            ->assertRedirect(route('admin.bookkeepings.index'));
        $this->assertDatabaseHas('bookkeepings', ['id' => $draft->id, 'status' => Bookkeeping::STATUS_APPROVED]);

        // submitted -> approved
        $submitted = Bookkeeping::factory()->create([
            'status' => Bookkeeping::STATUS_SUBMITTED,
            'type' => 'expense', 'amount' => 1000, 'category' => 'other',
        ]);
        $this->put('/admin/bookkeepings/'.$submitted->id.'/approve')
            ->assertRedirect(route('admin.bookkeepings.index'));
        $this->assertDatabaseHas('bookkeepings', ['id' => $submitted->id, 'status' => Bookkeeping::STATUS_APPROVED]);
        $this->assertDatabaseMissing('bookkeepings', ['id' => $submitted->id, 'status' => Bookkeeping::STATUS_PAID]);
    }

    public function test_mark_paid_moves_approved_to_paid(): void
    {
        $entry = Bookkeeping::factory()->create([
            'status' => Bookkeeping::STATUS_APPROVED,
            'type' => 'expense', 'amount' => 1000, 'category' => 'other',
        ]);
        $this->actingAs($this->user('admin_full_access'));

        $this->put('/admin/bookkeepings/'.$entry->id.'/mark-paid')
            ->assertRedirect(route('admin.bookkeepings.index'));

        $this->assertDatabaseHas('bookkeepings', ['id' => $entry->id, 'status' => Bookkeeping::STATUS_PAID]);
    }

    public function test_mark_paid_rejects_non_approved(): void
    {
        $entry = Bookkeeping::factory()->create([
            'status' => Bookkeeping::STATUS_DRAFT,
            'type' => 'expense', 'amount' => 1000, 'category' => 'other',
        ]);
        $this->actingAs($this->user('admin_full_access'));

        $this->put('/admin/bookkeepings/'.$entry->id.'/mark-paid')
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('bookkeepings', ['id' => $entry->id, 'status' => Bookkeeping::STATUS_DRAFT]);
    }

    public function test_cancel_lifecycle_for_editable_statuses(): void
    {
        $this->actingAs($this->user('admin_full_access'));

        foreach ([Bookkeeping::STATUS_DRAFT, Bookkeeping::STATUS_SUBMITTED, Bookkeeping::STATUS_APPROVED] as $status) {
            $entry = Bookkeeping::factory()->create([
                'status' => $status,
                'type' => 'expense', 'amount' => 1000, 'category' => 'other',
            ]);
            $this->put('/admin/bookkeepings/'.$entry->id.'/cancel')
                ->assertRedirect(route('admin.bookkeepings.index'));
            $this->assertDatabaseHas('bookkeepings', ['id' => $entry->id, 'status' => Bookkeeping::STATUS_CANCELLED]);
        }
    }

    public function test_paid_cannot_be_cancelled(): void
    {
        $entry = Bookkeeping::factory()->create([
            'status' => Bookkeeping::STATUS_PAID,
            'type' => 'expense', 'amount' => 1000, 'category' => 'other',
        ]);
        $this->actingAs($this->user('admin_full_access'));

        $this->put('/admin/bookkeepings/'.$entry->id.'/cancel')
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('bookkeepings', ['id' => $entry->id, 'status' => Bookkeeping::STATUS_PAID]);
    }

    // --- Paid immutability ---

    public function test_paid_entry_edit_redirects_back_with_error(): void
    {
        $entry = Bookkeeping::factory()->create([
            'status' => Bookkeeping::STATUS_PAID,
            'type' => 'expense', 'amount' => 1000, 'description' => 'Asli', 'category' => 'other',
        ]);
        $this->actingAs($this->user('admin_full_access'));

        $this->put('/admin/bookkeepings/'.$entry->id, [
            'transaction_date' => '2026-08-18',
            'description' => 'Diubah',
            'type' => 'expense',
            'amount' => 99999,
            'category' => 'other',
        ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('bookkeepings', ['id' => $entry->id, 'description' => 'Asli', 'amount' => 1000]);
    }

    public function test_paid_entry_delete_returns_403(): void
    {
        $entry = Bookkeeping::factory()->create([
            'status' => Bookkeeping::STATUS_PAID,
            'type' => 'expense', 'amount' => 1000, 'category' => 'other',
        ]);
        $this->actingAs($this->user('admin_full_access'));

        $this->delete('/admin/bookkeepings/'.$entry->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('bookkeepings', ['id' => $entry->id]);
    }

    // --- Authorization ---

    public function test_bendahara_gets_403_on_approver_actions(): void
    {
        $entry = Bookkeeping::factory()->create([
            'status' => Bookkeeping::STATUS_SUBMITTED,
            'type' => 'expense', 'amount' => 1000, 'category' => 'other',
        ]);
        $this->actingAs($this->user('bendahara'));

        $this->put('/admin/bookkeepings/'.$entry->id.'/approve')->assertStatus(403);
        $this->put('/admin/bookkeepings/'.$entry->id.'/mark-paid')->assertStatus(403);
        $this->put('/admin/bookkeepings/'.$entry->id.'/cancel')->assertStatus(403);
    }

    public function test_bendahara_can_submit_and_view_reports(): void
    {
        $entry = Bookkeeping::factory()->create([
            'status' => Bookkeeping::STATUS_DRAFT,
            'type' => 'expense', 'amount' => 1000, 'category' => 'other',
        ]);
        $this->actingAs($this->user('bendahara'));

        $this->put('/admin/bookkeepings/'.$entry->id.'/submit')
            ->assertRedirect(route('admin.bookkeepings.index'));
        $this->assertDatabaseHas('bookkeepings', ['id' => $entry->id, 'status' => Bookkeeping::STATUS_SUBMITTED]);

        $this->get('/admin/bookkeepings')->assertOk();
        $this->get('/admin/bookkeepings/reports')->assertOk();
        $this->get('/admin/bookkeepings/receivables')->assertOk();
        $this->get('/admin/bookkeepings/payables')->assertOk();
        $this->get('/admin/bookkeepings/cash-flow')->assertOk();
    }

    // --- Store validation ---

    public function test_store_requires_fields(): void
    {
        $this->actingAs($this->user('bendahara'));

        $this->post('/admin/bookkeepings', [])
            ->assertSessionHasErrors(['transaction_date', 'description', 'type', 'amount', 'category']);

        $this->assertDatabaseCount('bookkeepings', 0);
    }

    public function test_store_accepts_only_draft_submitted_paid_initial_status(): void
    {
        $this->actingAs($this->user('bendahara'));

        $base = [
            'transaction_date' => '2026-08-18',
            'description' => 'Entri',
            'type' => 'expense',
            'amount' => 1000,
            'category' => 'other',
        ];

        foreach ([Bookkeeping::STATUS_DRAFT, Bookkeeping::STATUS_SUBMITTED, Bookkeeping::STATUS_PAID] as $status) {
            $count = Bookkeeping::count();
            $this->post('/admin/bookkeepings', array_merge($base, ['status' => $status]))
                ->assertRedirect(route('admin.bookkeepings.index'));
            $this->assertDatabaseHas('bookkeepings', ['status' => $status]);
            $this->assertSame($count + 1, Bookkeeping::count());
        }

        // approved/cancelled are not valid initial statuses -> rejected gracefully
        $this->post('/admin/bookkeepings', array_merge($base, ['status' => Bookkeeping::STATUS_APPROVED]))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('bookkeepings', ['status' => Bookkeeping::STATUS_APPROVED]);

        // out-of-enum status -> validation error
        $this->post('/admin/bookkeepings', array_merge($base, ['status' => 'bogus']))
            ->assertSessionHasErrors('status');
    }
}
