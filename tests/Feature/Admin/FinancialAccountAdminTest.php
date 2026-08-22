<?php

namespace Tests\Feature\Admin;

use App\Models\FinancialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAccountAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_index_lists_accounts(): void
    {
        FinancialAccount::factory()->create(['name' => 'Kas Utama']);

        $this->actingAs($this->user('bendahara'))
            ->get('/admin/financial-accounts')
            ->assertOk()
            ->assertSee('Kas Utama');
    }

    public function test_create_page_loads(): void
    {
        $this->actingAs($this->user('bendahara'))
            ->get('/admin/financial-accounts/create')
            ->assertOk();
    }

    public function test_store_creates_account(): void
    {
        $this->actingAs($this->user('bendahara'))
            ->post('/admin/financial-accounts', [
                'name' => 'Bank Central',
                'type' => 'bank',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.financial-accounts.index'));

        $this->assertDatabaseHas('financial_accounts', [
            'name' => 'Bank Central',
            'type' => 'bank',
            'is_active' => true,
        ]);
    }

    public function test_store_validation_errors(): void
    {
        $this->actingAs($this->user('bendahara'))
            ->post('/admin/financial-accounts', [
                'type' => 'invalid',
            ])
            ->assertSessionHasErrors(['name', 'type']);

        $this->assertDatabaseCount('financial_accounts', 0);
    }

    public function test_edit_page_loads(): void
    {
        $account = FinancialAccount::factory()->create();

        $this->actingAs($this->user('bendahara'))
            ->get('/admin/financial-accounts/'.$account->id.'/edit')
            ->assertOk();
    }

    public function test_update_account(): void
    {
        $account = FinancialAccount::factory()->create(['name' => 'Lama', 'is_active' => true]);

        $this->actingAs($this->user('bendahara'))
            ->put('/admin/financial-accounts/'.$account->id, [
                'name' => 'Baru',
                'type' => 'kas',
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.financial-accounts.index'));

        $this->assertDatabaseHas('financial_accounts', [
            'id' => $account->id,
            'name' => 'Baru',
            'is_active' => false,
        ]);
    }

    public function test_destroy_account(): void
    {
        $account = FinancialAccount::factory()->create();

        $this->actingAs($this->user('bendahara'))
            ->delete('/admin/financial-accounts/'.$account->id)
            ->assertRedirect(route('admin.financial-accounts.index'));

        $this->assertDatabaseMissing('financial_accounts', ['id' => $account->id]);
    }

    public function test_403_for_role_outside_group(): void
    {
        $this->actingAs($this->user('organizer'))
            ->get('/admin/financial-accounts')
            ->assertStatus(403);
    }
}
