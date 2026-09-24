<?php

namespace Tests\Feature\Admin;

use App\Models\InventoryExternalLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryExternalLoanAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function loanPayload(): array
    {
        return [
            'external_party' => 'Komunitas Lari Bandung',
            'contact_name' => 'Andi',
            'contact_info' => '081234567890',
            'items_description' => '2 unit tenda dome, 1 pcs sound portable',
            'purpose' => 'Long Run',
            'borrow_date' => now()->toDateString(),
            'expected_return_date' => now()->addDays(7)->toDateString(),
        ];
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin/inventory/external-loans')->assertRedirect('/login');
    }

    public function test_index_is_forbidden_for_participant(): void
    {
        $this->actingAs($this->user('participant'))->get('/admin/inventory/external-loans')->assertForbidden();
    }

    public function test_create_external_loan(): void
    {
        $admin = $this->user('admin_full_access');

        $response = $this->actingAs($admin)->post('/admin/inventory/external-loans', $this->loanPayload());

        $loan = InventoryExternalLoan::first();
        $this->assertNotNull($loan);
        $response->assertRedirect(route('admin.inventory.external-loans.show', $loan->id));

        $this->assertDatabaseHas('inventory_external_loans', [
            'id' => $loan->id,
            'external_party' => 'Komunitas Lari Bandung',
            'status' => InventoryExternalLoan::STATUS_APPROVED,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_handover_sets_loan_borrowed(): void
    {
        $admin = $this->user('admin_full_access');
        $this->actingAs($admin)->post('/admin/inventory/external-loans', $this->loanPayload());
        $loan = InventoryExternalLoan::first();

        $this->actingAs($admin)->post("/admin/inventory/external-loans/{$loan->id}/handover", [
            'from_location' => 'Gudang Bandung',
            'to_name' => 'Admin SH3',
            'condition_at_handover' => 'good',
            'notes' => 'Serah terima pertama',
        ])->assertRedirect(route('admin.inventory.external-loans.show', $loan->id));

        $this->assertDatabaseHas('inventory_external_loans', [
            'id' => $loan->id,
            'status' => InventoryExternalLoan::STATUS_BORROWED,
            'condition_before' => 'good',
        ]);
        $this->assertDatabaseHas('inventory_external_handovers', [
            'inventory_external_loan_id' => $loan->id,
            'sequence' => 1,
            'condition_at_handover' => 'good',
            'created_by' => $admin->id,
        ]);

        // Later handovers are append-only with an incrementing sequence.
        $this->actingAs($admin)->post("/admin/inventory/external-loans/{$loan->id}/handover", [
            'from_location' => 'Gudang Bandung',
            'to_name' => 'Venue Senayan',
            'condition_at_handover' => 'good',
        ]);

        $this->assertDatabaseHas('inventory_external_handovers', [
            'inventory_external_loan_id' => $loan->id,
            'sequence' => 2,
        ]);
    }

    public function test_return_closes_loan(): void
    {
        $admin = $this->user('admin_full_access');
        $this->actingAs($admin)->post('/admin/inventory/external-loans', $this->loanPayload());
        $loan = InventoryExternalLoan::first();
        $this->actingAs($admin)->post("/admin/inventory/external-loans/{$loan->id}/handover", [
            'from_location' => 'Gudang Bandung',
            'to_name' => 'Admin SH3',
            'condition_at_handover' => 'excellent',
        ]);

        $this->actingAs($admin)->post("/admin/inventory/external-loans/{$loan->id}/return", [
            'condition_after' => 'fair',
        ])->assertRedirect(route('admin.inventory.external-loans.show', $loan->id));

        $this->assertDatabaseHas('inventory_external_loans', [
            'id' => $loan->id,
            'status' => InventoryExternalLoan::STATUS_RETURNED,
            'condition_before' => 'excellent',
            'condition_after' => 'fair',
            'actual_return_date' => now()->toDateString(),
            'returned_received_by' => $admin->id,
        ]);
    }

    public function test_cancel_approved_loan(): void
    {
        $admin = $this->user('admin_full_access');
        $this->actingAs($admin)->post('/admin/inventory/external-loans', $this->loanPayload());
        $loan = InventoryExternalLoan::first();

        $this->actingAs($admin)
            ->post("/admin/inventory/external-loans/{$loan->id}/cancel")
            ->assertRedirect(route('admin.inventory.external-loans.show', $loan->id));

        $this->assertDatabaseHas('inventory_external_loans', [
            'id' => $loan->id,
            'status' => InventoryExternalLoan::STATUS_CANCELLED,
        ]);
    }

    public function test_cancel_returned_loan_is_rejected(): void
    {
        $admin = $this->user('admin_full_access');
        $this->actingAs($admin)->post('/admin/inventory/external-loans', $this->loanPayload());
        $loan = InventoryExternalLoan::first();
        $this->actingAs($admin)->post("/admin/inventory/external-loans/{$loan->id}/handover", [
            'from_location' => 'Gudang Bandung',
            'to_name' => 'Admin SH3',
            'condition_at_handover' => 'good',
        ]);
        $this->actingAs($admin)->post("/admin/inventory/external-loans/{$loan->id}/return", [
            'condition_after' => 'good',
        ]);

        $this->actingAs($admin)
            ->post("/admin/inventory/external-loans/{$loan->id}/cancel")
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('inventory_external_loans', [
            'id' => $loan->id,
            'status' => InventoryExternalLoan::STATUS_RETURNED,
        ]);
    }

    public function test_mutations_forbidden_for_participant(): void
    {
        $participant = $this->user('participant');

        $this->actingAs($participant)
            ->post('/admin/inventory/external-loans', $this->loanPayload())
            ->assertForbidden();

        $this->assertSame(0, InventoryExternalLoan::count());
    }
}
