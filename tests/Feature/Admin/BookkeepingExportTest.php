<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Bookkeeping;
use App\Models\Event;
use App\Models\EventBudget;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class BookkeepingExportTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function content($response): string
    {
        if ($response->baseResponse instanceof StreamedResponse) {
            return $response->streamedContent();
        }

        return $response->getContent();
    }

    private function exportUrl(string $route, array $params = []): string
    {
        return route('admin.bookkeepings.'.$route, $params);
    }

    // --- (a) Transactions ledger ---

    public function test_export_transactions_csv_authorized(): void
    {
        Bookkeeping::factory()->create(['description' => 'Ledger CSV test', 'category' => 'other']);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('sh3-bookkeepings-ledger', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Ledger CSV test', $this->content($response));
    }

    public function test_export_transactions_xlsx_authorized(): void
    {
        Bookkeeping::factory()->create(['description' => 'Ledger XLSX test', 'category' => 'other']);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export', ['format' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $this->content($response));
    }

    public function test_export_transactions_pdf_authorized(): void
    {
        Bookkeeping::factory()->create(['description' => 'Ledger PDF test', 'category' => 'other']);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export', ['format' => 'pdf']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $this->content($response));
    }

    public function test_export_transactions_forbidden_for_non_bendahara(): void
    {
        $this->actingAs($this->user('admin_laman'));

        $this->get($this->exportUrl('export', ['format' => 'csv']))->assertStatus(403);
    }

    public function test_export_transactions_respects_type_filter(): void
    {
        Bookkeeping::factory()->create(['description' => 'Pemasukan Unik ABC', 'type' => 'income', 'category' => 'other']);
        Bookkeeping::factory()->create(['description' => 'Pengeluaran Unik XYZ', 'type' => 'expense', 'category' => 'other']);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export', ['format' => 'csv', 'type' => 'income']));

        $response->assertOk();
        $this->assertStringContainsString('Pemasukan Unik ABC', $this->content($response));
        $this->assertStringNotContainsString('Pengeluaran Unik XYZ', $this->content($response));
    }

    public function test_export_transactions_respects_date_filter(): void
    {
        Bookkeeping::factory()->create(['description' => 'Januari Entri', 'transaction_date' => '2026-01-15', 'category' => 'other']);
        Bookkeeping::factory()->create(['description' => 'Maret Entri', 'transaction_date' => '2026-03-15', 'category' => 'other']);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export', [
            'format' => 'csv',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('Januari Entri', $this->content($response));
        $this->assertStringNotContainsString('Maret Entri', $this->content($response));
    }

    public function test_export_transactions_is_read_only(): void
    {
        $count = Bookkeeping::count();
        Bookkeeping::factory()->times(3)->create(['category' => 'other']);
        $this->actingAs($this->user('bendahara'));

        $this->get($this->exportUrl('export', ['format' => 'csv']))->assertOk();

        $this->assertSame($count + 3, Bookkeeping::count());
    }

    public function test_export_transactions_csv_has_utf8_bom(): void
    {
        Bookkeeping::factory()->create(['description' => 'BOM test', 'category' => 'other']);
        $this->actingAs($this->user('bendahara'));

        $content = $this->content($this->get($this->exportUrl('export', ['format' => 'csv'])));

        $this->assertSame("\xEF\xBB\xBF", substr($content, 0, 3));
    }

    // --- (b) Budget vs actual ---

    public function test_export_budget_vs_actual_csv_authorized(): void
    {
        $event = Event::factory()->create();
        $activity = Activity::factory()->create(['name' => 'Pos Anggaran Unik']);
        EventBudget::factory()->create(['event_id' => $event->id, 'activity_id' => $activity->id, 'amount' => 1500000]);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export-budget-vs-actual', ['event_id' => $event->id, 'format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('Pos Anggaran Unik', $this->content($response));
        $this->assertStringContainsString('1500000', $this->content($response));
    }

    public function test_export_budget_vs_actual_xlsx_authorized(): void
    {
        $event = Event::factory()->create();
        $activity = Activity::factory()->create(['name' => 'Pos XLSX Unik']);
        EventBudget::factory()->create(['event_id' => $event->id, 'activity_id' => $activity->id, 'amount' => 1200000]);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export-budget-vs-actual', ['event_id' => $event->id, 'format' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $this->content($response));
    }

    public function test_export_budget_vs_actual_pdf_authorized(): void
    {
        $event = Event::factory()->create();
        $activity = Activity::factory()->create(['name' => 'Pos PDF Unik']);
        EventBudget::factory()->create(['event_id' => $event->id, 'activity_id' => $activity->id, 'amount' => 900000]);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export-budget-vs-actual', ['event_id' => $event->id, 'format' => 'pdf']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $this->content($response));
    }

    public function test_export_budget_vs_actual_requires_event_id(): void
    {
        $this->actingAs($this->user('bendahara'));

        $this->get($this->exportUrl('export-budget-vs-actual', ['format' => 'csv']))->assertStatus(404);
    }

    // --- (c) Cash flow ---

    public function test_export_cash_flow_csv_authorized(): void
    {
        Bookkeeping::factory()->create(['type' => 'income', 'amount' => 100000, 'category' => 'other', 'transaction_date' => '2026-03-15', 'status' => Bookkeeping::STATUS_PAID]);
        Bookkeeping::factory()->create(['type' => 'expense', 'amount' => 40000, 'category' => 'other', 'transaction_date' => '2026-07-20', 'status' => Bookkeeping::STATUS_PAID]);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export-cash-flow', ['year' => 2026, 'format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('Arus Kas 2026', $this->content($response));
        $this->assertStringContainsString('100000', $this->content($response));
    }

    public function test_export_cash_flow_xlsx_authorized(): void
    {
        Bookkeeping::factory()->create(['type' => 'income', 'amount' => 100000, 'category' => 'other', 'transaction_date' => '2026-03-15', 'status' => Bookkeeping::STATUS_PAID]);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export-cash-flow', ['year' => 2026, 'format' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $this->content($response));
    }

    public function test_export_cash_flow_pdf_authorized(): void
    {
        Bookkeeping::factory()->create(['type' => 'income', 'amount' => 100000, 'category' => 'other', 'transaction_date' => '2026-03-15', 'status' => Bookkeeping::STATUS_PAID]);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export-cash-flow', ['year' => 2026, 'format' => 'pdf']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $this->content($response));
    }

    // --- (d) Receivables ---

    public function test_export_receivables_csv_authorized(): void
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
            'type' => 'income', 'amount' => 50000, 'category' => 'other',
            'event_id' => $event->id, 'status' => Bookkeeping::STATUS_SUBMITTED,
        ]);

        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export-receivables', ['event_id' => $event->id, 'format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('200000', $this->content($response));
    }

    public function test_export_receivables_pdf_authorized(): void
    {
        $event = Event::factory()->create();
        $sponsor = Sponsor::factory()->create();

        DB::table('event_sponsors')->insert([
            'event_id' => $event->id,
            'sponsor_id' => $sponsor->id,
            'value' => 200000,
            'status' => 'approved',
        ]);

        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export-receivables', ['event_id' => $event->id, 'format' => 'pdf']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $this->content($response));
    }

    // --- (e) Payables ---

    public function test_export_payable_csv_authorized(): void
    {
        Bookkeeping::factory()->create(['type' => 'expense', 'amount' => 30000, 'category' => 'other', 'payee' => 'Vendor A', 'status' => Bookkeeping::STATUS_SUBMITTED]);
        Bookkeeping::factory()->create(['type' => 'expense', 'amount' => 20000, 'category' => 'other', 'payee' => 'Vendor B', 'status' => Bookkeeping::STATUS_APPROVED]);
        $this->actingAs($this->user('bendahara'));

        $response = $this->get($this->exportUrl('export-payable', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('50000', $this->content($response));
    }
}
