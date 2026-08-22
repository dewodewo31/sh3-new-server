<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Event;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinancialSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_chain_runs_and_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('financial_accounts'));
        $this->assertTrue(Schema::hasTable('activities'));
        $this->assertTrue(Schema::hasTable('event_budgets'));
        $this->assertTrue(Schema::hasTable('bookkeepings'));
    }

    public function test_financial_accounts_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('financial_accounts', [
            'id', 'name', 'type', 'is_active', 'created_at', 'updated_at',
        ]));
    }

    public function test_activities_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('activities', [
            'id', 'name', 'is_active', 'sort_order', 'created_at', 'updated_at',
        ]));
    }

    public function test_event_budgets_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('event_budgets', [
            'id', 'event_id', 'activity_id', 'amount', 'notes', 'created_at', 'updated_at',
        ]));
    }

    public function test_bookkeepings_financial_columns(): void
    {
        // ponytail: spec listed 'receipt_path' but the migration adds 'receipt'; assert real columns.
        $this->assertTrue(Schema::hasColumns('bookkeepings', [
            'id', 'status', 'approved_by', 'approved_at', 'payee',
            'financial_account_id', 'activity_id', 'event_id', 'receipt',
        ]));
    }

    public function test_bookkeeping_fk_to_financial_account_is_enforced(): void
    {
        $this->expectException(QueryException::class);

        DB::table('bookkeepings')->insert([
            'transaction_date' => '2026-08-01',
            'description' => 'FK financial_account',
            'type' => 'income',
            'amount' => 1000,
            'category' => 'other',
            'financial_account_id' => 999999,
        ]);
    }

    public function test_bookkeeping_fk_to_activity_is_enforced(): void
    {
        $this->expectException(QueryException::class);

        DB::table('bookkeepings')->insert([
            'transaction_date' => '2026-08-01',
            'description' => 'FK activity',
            'type' => 'income',
            'amount' => 1000,
            'category' => 'other',
            'activity_id' => 999999,
        ]);
    }

    public function test_event_budget_fk_to_event_is_enforced(): void
    {
        $activity = Activity::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('event_budgets')->insert([
            'event_id' => 999999,
            'activity_id' => $activity->id,
            'amount' => 1000,
        ]);
    }

    public function test_event_budget_fk_to_activity_is_enforced(): void
    {
        $event = Event::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('event_budgets')->insert([
            'event_id' => $event->id,
            'activity_id' => 999999,
            'amount' => 1000,
        ]);
    }
}
