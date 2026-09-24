<?php

namespace Tests\Feature\Admin;

use App\Models\InventoryDocument;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryLoanAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function availableItem(): InventoryItem
    {
        return InventoryItem::factory()->create(['status' => InventoryItem::STATUS_AVAILABLE]);
    }

    private function loanPayload(InventoryItem $item): array
    {
        return [
            'inventory_item_id' => $item->id,
            'borrower_name' => 'Budi Santoso',
            'purpose' => 'Long Run',
            'borrow_date' => now()->toDateString(),
            'expected_return_date' => now()->addDays(7)->toDateString(),
        ];
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin/inventory/loans')->assertRedirect('/login');
    }

    public function test_index_is_forbidden_for_participant(): void
    {
        $this->actingAs($this->user('participant'))->get('/admin/inventory/loans')->assertForbidden();
    }

    public function test_create_loan_for_available_item(): void
    {
        $admin = $this->user('admin_full_access');
        $item = $this->availableItem();

        $response = $this->actingAs($admin)->post('/admin/inventory/loans', $this->loanPayload($item));

        $loan = InventoryLoan::first();
        $this->assertNotNull($loan);
        $response->assertRedirect(route('admin.inventory.loans.show', $loan->id));

        $this->assertDatabaseHas('inventory_loans', [
            'id' => $loan->id,
            'inventory_item_id' => $item->id,
            'borrower_name' => 'Budi Santoso',
            'status' => InventoryLoan::STATUS_APPROVED,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
        ]);

        // Item is marked borrowed immediately so inventory list reflects the active loan.
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'status' => InventoryItem::STATUS_BORROWED,
        ]);
    }

    public function test_double_borrow_is_rejected(): void
    {
        $admin = $this->user('admin_full_access');
        $item = $this->availableItem();

        $this->actingAs($admin)->post('/admin/inventory/loans', $this->loanPayload($item));

        $this->actingAs($admin)
            ->post('/admin/inventory/loans', $this->loanPayload($item))
            ->assertSessionHasErrors('inventory_item_id');

        $this->assertSame(1, InventoryLoan::count());
    }

    public function test_handover_sets_item_and_loan_borrowed(): void
    {
        $admin = $this->user('admin_full_access');
        $item = $this->availableItem();
        $this->actingAs($admin)->post('/admin/inventory/loans', $this->loanPayload($item));
        $loan = InventoryLoan::first();

        $this->actingAs($admin)->post("/admin/inventory/loans/{$loan->id}/handover", [
            'from_location' => 'Gudang',
            'to_name' => 'Budi Santoso',
            'condition_at_handover' => 'good',
            'notes' => 'Serah terima pertama',
        ])->assertRedirect(route('admin.inventory.loans.show', $loan->id));

        $this->assertDatabaseHas('inventory_loans', [
            'id' => $loan->id,
            'status' => InventoryLoan::STATUS_BORROWED,
            'condition_before' => 'good',
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'status' => InventoryItem::STATUS_BORROWED,
        ]);
        $this->assertDatabaseHas('inventory_handovers', [
            'inventory_loan_id' => $loan->id,
            'sequence' => 1,
            'condition_at_handover' => 'good',
            'created_by' => $admin->id,
        ]);

        // Later handovers are append-only with an incrementing sequence.
        $this->actingAs($admin)->post("/admin/inventory/loans/{$loan->id}/handover", [
            'from_location' => 'Gudang',
            'to_name' => 'Venue Senayan',
            'condition_at_handover' => 'good',
        ]);

        $this->assertDatabaseHas('inventory_handovers', [
            'inventory_loan_id' => $loan->id,
            'sequence' => 2,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'status' => InventoryItem::STATUS_BORROWED,
        ]);
    }

    public function test_return_closes_loan_and_makes_item_available(): void
    {
        $admin = $this->user('admin_full_access');
        $item = $this->availableItem();
        $this->actingAs($admin)->post('/admin/inventory/loans', $this->loanPayload($item));
        $loan = InventoryLoan::first();
        $this->actingAs($admin)->post("/admin/inventory/loans/{$loan->id}/handover", [
            'from_location' => 'Gudang',
            'to_name' => 'Budi Santoso',
            'condition_at_handover' => 'excellent',
        ]);

        $this->actingAs($admin)->post("/admin/inventory/loans/{$loan->id}/return", [
            'condition_after' => 'fair',
        ])->assertRedirect(route('admin.inventory.loans.show', $loan->id));

        $this->assertDatabaseHas('inventory_loans', [
            'id' => $loan->id,
            'status' => InventoryLoan::STATUS_RETURNED,
            'condition_before' => 'excellent',
            'condition_after' => 'fair',
            'actual_return_date' => now()->toDateString(),
            'returned_received_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'status' => InventoryItem::STATUS_AVAILABLE,
        ]);
    }

    public function test_cancel_approved_loan(): void
    {
        $admin = $this->user('admin_full_access');
        $item = $this->availableItem();
        $this->actingAs($admin)->post('/admin/inventory/loans', $this->loanPayload($item));
        $loan = InventoryLoan::first();

        $this->actingAs($admin)
            ->post("/admin/inventory/loans/{$loan->id}/cancel")
            ->assertRedirect(route('admin.inventory.loans.show', $loan->id));

        $this->assertDatabaseHas('inventory_loans', [
            'id' => $loan->id,
            'status' => InventoryLoan::STATUS_CANCELLED,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'status' => InventoryItem::STATUS_AVAILABLE,
        ]);
    }

    public function test_cancel_returned_loan_is_rejected(): void
    {
        $admin = $this->user('admin_full_access');
        $item = $this->availableItem();
        $this->actingAs($admin)->post('/admin/inventory/loans', $this->loanPayload($item));
        $loan = InventoryLoan::first();
        $this->actingAs($admin)->post("/admin/inventory/loans/{$loan->id}/handover", [
            'from_location' => 'Gudang',
            'to_name' => 'Budi Santoso',
            'condition_at_handover' => 'good',
        ]);
        $this->actingAs($admin)->post("/admin/inventory/loans/{$loan->id}/return", [
            'condition_after' => 'good',
        ]);

        $this->actingAs($admin)
            ->post("/admin/inventory/loans/{$loan->id}/cancel")
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('inventory_loans', [
            'id' => $loan->id,
            'status' => InventoryLoan::STATUS_RETURNED,
        ]);
    }

    public function test_document_stored_on_local_disk_not_public(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = $this->user('admin_full_access');
        $item = $this->availableItem();
        $this->actingAs($admin)->post('/admin/inventory/loans', $this->loanPayload($item));
        $loan = InventoryLoan::first();

        $this->actingAs($admin)->post("/admin/inventory/loans/{$loan->id}/documents", [
            'file' => UploadedFile::fake()->create('agreement.pdf', 500, 'application/pdf'),
            'type' => 'loan_agreement',
        ])->assertRedirect(route('admin.inventory.loans.show', $loan->id));

        $document = InventoryDocument::first();
        $this->assertNotNull($document);
        $this->assertDatabaseHas('inventory_documents', [
            'id' => $document->id,
            'inventory_loan_id' => $loan->id,
            'inventory_item_id' => $item->id,
            'type' => 'loan_agreement',
            'uploaded_by' => $admin->id,
        ]);

        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
        $this->assertStringNotContainsString('/storage/', $document->file_path);
        $this->assertSame($document->id, $loan->fresh()->agreement_document_id);
    }

    public function test_document_download_allowed_for_manage_role_and_forbidden_otherwise(): void
    {
        Storage::fake('local');
        $admin = $this->user('admin_full_access');
        $organizer = $this->user('organizer');
        $item = $this->availableItem();
        $this->actingAs($admin)->post('/admin/inventory/loans', $this->loanPayload($item));
        $loan = InventoryLoan::first();
        $this->actingAs($admin)->post("/admin/inventory/loans/{$loan->id}/documents", [
            'file' => UploadedFile::fake()->create('agreement.pdf', 100, 'application/pdf'),
            'type' => 'loan_agreement',
        ]);
        $document = InventoryDocument::first();

        $this->actingAs($admin)
            ->get("/admin/inventory/documents/{$document->id}/download")
            ->assertOk();

        $this->actingAs($organizer)
            ->get("/admin/inventory/documents/{$document->id}/download")
            ->assertForbidden();
    }
}
