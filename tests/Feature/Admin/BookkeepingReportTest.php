<?php

namespace Tests\Feature\Admin;

use App\Models\Bookkeeping;
use App\Models\Event;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookkeepingReportTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_dashboard_totals_income_expense_balance(): void
    {
        Bookkeeping::factory()->create(['type' => 'income', 'amount' => 100000, 'category' => 'other']);
        Bookkeeping::factory()->create(['type' => 'expense', 'amount' => 40000, 'category' => 'other']);

        $this->actingAs($this->user('bendahara'))
            ->get('/admin/bookkeepings/reports')
            ->assertOk()
            ->assertSee('Rp 100.000')
            ->assertSee('Rp 40.000')
            ->assertSee('Rp 60.000');
    }

    public function test_receivables_committed_received_outstanding(): void
    {
        $event = Event::factory()->create();
        $sponsor = Sponsor::factory()->create();

        DB::table('event_sponsors')->insert([
            'event_id' => $event->id,
            'sponsor_id' => $sponsor->id,
            'value' => 200000,
            'status' => 'approved',
        ]);

        Bookkeeping::factory()->create([
            'type' => 'income',
            'amount' => 50000,
            'category' => 'other',
            'event_id' => $event->id,
            'status' => Bookkeeping::STATUS_SUBMITTED,
        ]);

        $this->actingAs($this->user('bendahara'))
            ->get('/admin/bookkeepings/receivables?event_id='.$event->id)
            ->assertOk()
            ->assertSee('Rp 200.000')   // committed from event_sponsors
            ->assertSee('Rp 50.000')    // received (submitted income)
            ->assertSee('Rp 150.000');  // outstanding
    }

    public function test_payables_committed_paid_outstanding(): void
    {
        Bookkeeping::factory()->create([
            'type' => 'expense', 'amount' => 30000, 'category' => 'other',
            'payee' => 'Vendor A', 'status' => Bookkeeping::STATUS_SUBMITTED,
        ]);
        Bookkeeping::factory()->create([
            'type' => 'expense', 'amount' => 20000, 'category' => 'other',
            'payee' => 'Vendor B', 'status' => Bookkeeping::STATUS_APPROVED,
        ]);
        Bookkeeping::factory()->create([
            'type' => 'expense', 'amount' => 10000, 'category' => 'other',
            'payee' => 'Vendor C', 'status' => Bookkeeping::STATUS_PAID,
        ]);

        $this->actingAs($this->user('bendahara'))
            ->get('/admin/bookkeepings/payables')
            ->assertOk()
            ->assertSee('Rp 50.000')   // committed = submitted + approved
            ->assertSee('Rp 10.000')   // paid
            ->assertSee('Rp 40.000');  // outstanding
    }

    public function test_cash_flow_opening_income_expense_closing(): void
    {
        Bookkeeping::factory()->create([
            'type' => 'income', 'amount' => 100000, 'category' => 'other',
            'transaction_date' => '2026-03-15', 'status' => Bookkeeping::STATUS_PAID,
        ]);
        Bookkeeping::factory()->create([
            'type' => 'expense', 'amount' => 40000, 'category' => 'other',
            'transaction_date' => '2026-07-20', 'status' => Bookkeeping::STATUS_PAID,
        ]);

        $this->actingAs($this->user('bendahara'))
            ->get('/admin/bookkeepings/cash-flow?year=2026')
            ->assertOk()
            ->assertSee('Rp 0')          // opening (nothing before 2026)
            ->assertSee('Rp 100.000')    // income
            ->assertSee('Rp 40.000')     // expense
            ->assertSee('Rp 60.000');    // closing
    }
}
