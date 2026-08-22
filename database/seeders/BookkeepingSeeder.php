<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Bookkeeping;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookkeepingSeeder extends Seeder
{
    public function run(): void
    {
        $createdBy  = User::where('role', 'bendahara')->first()?->id ?? User::first()?->id;
        $approvedBy = User::where('role', 'admin_full_access')->first()?->id ?? User::first()?->id;

        // — Data referensi (idempotent dari seeder lain) —
        $eventAnniversary  = Event::where('title', 'SH3 Anniversary Run 2026')->first();
        $eventCisarua      = Event::where('title', 'Long Run Cisarua')->first();
        $eventKotaTua      = Event::where('title', 'Night Run Kota Tua')->first();
        $eventBromo        = Event::where('title', 'Ultra Marathon Bromo')->first();
        $eventCityRun      = Event::where('title', 'City Run Sudirman')->first();

        $sponsorNike       = Sponsor::where('name', 'Nike Indonesia')->first();
        $sponsorBCA        = Sponsor::where('name', 'Bank Central Asia')->first();
        $sponsorAdidas     = Sponsor::where('name', 'Adidas Running')->first();

        $actLariPagi   = Activity::where('name', 'Lari Pagi')->first();
        $actCityRun    = Activity::where('name', 'City Run')->first();
        $actMajorEvent = Activity::where('name', 'Major Event')->first();

        $akunKas       = FinancialAccount::where('name', 'Kas')->first();
        $akunBank      = FinancialAccount::where('name', 'Bank')->first();
        $akunPiutang   = FinancialAccount::where('name', 'Piutang')->first();
        $akunHutang    = FinancialAccount::where('name', 'Hutang')->first();
        $akunPendapatan = FinancialAccount::where('name', 'Pendapatan')->first();
        $akunBeban     = FinancialAccount::where('name', 'Beban')->first();

        // — 10 contoh transaksi —
        $entries = [
            // 1  PAID — sponsorship masuk, sudah cair
            [
                'description'         => 'Sponsorship Nike Indonesia — Anniversary Run 2026',
                'type'                => 'income',
                'amount'              => 500000000,
                'category'            => 'sponsor',
                'status'              => 'paid',
                'sponsor_id'          => $sponsorNike?->id,
                'event_id'            => $eventAnniversary?->id,
                'financial_account_id' => $akunBank?->id,
                'activity_id'         => $actMajorEvent?->id,
                'payee'               => 'PT Nike Indonesia',
                'transaction_date'    => now()->subDays(30)->toDateString(),
                'approved_by'         => $approvedBy,
                'approved_at'         => now()->subDays(29),
            ],

            // 2  PAID — registrasi event masuk
            [
                'description'         => 'Pendapatan registrasi Night Run Kota Tua (120 peserta × Rp50.000)',
                'type'                => 'income',
                'amount'              => 6000000,
                'category'            => 'event_income',
                'status'              => 'paid',
                'sponsor_id'          => null,
                'event_id'            => $eventKotaTua?->id,
                'financial_account_id' => $akunKas?->id,
                'activity_id'         => $actCityRun?->id,
                'payee'               => null,
                'transaction_date'    => now()->subDays(14)->toDateString(),
                'approved_by'         => $approvedBy,
                'approved_at'         => now()->subDays(13),
            ],

            // 3  PAID — belanja konsumsi
            [
                'description'         => 'Pembelian snackbar & air mineral untuk Long Run Cisarua',
                'type'                => 'expense',
                'amount'              => 3500000,
                'category'            => 'other',
                'status'              => 'paid',
                'sponsor_id'          => null,
                'event_id'            => $eventCisarua?->id,
                'financial_account_id' => $akunKas?->id,
                'activity_id'         => $actLariPagi?->id,
                'payee'               => 'Toko snack Arta Jaya',
                'transaction_date'    => now()->subDays(12)->toDateString(),
                'approved_by'         => $approvedBy,
                'approved_at'         => now()->subDays(11),
            ],

            // 4  APPROVED — sewa venue, belum bayar
            [
                'description'         => 'Sewa hall GBK untuk Anniversary Run (2 hari)',
                'type'                => 'expense',
                'amount'              => 25000000,
                'category'            => 'other',
                'status'              => 'approved',
                'sponsor_id'          => null,
                'event_id'            => $eventAnniversary?->id,
                'financial_account_id' => $akunHutang?->id,
                'activity_id'         => $actMajorEvent?->id,
                'payee'               => 'GBK Venue Management',
                'due_date'            => now()->addDays(7)->toDateString(),
                'transaction_date'    => now()->subDays(5)->toDateString(),
                'approved_by'         => $approvedBy,
                'approved_at'         => now()->subDays(4),
            ],

            // 5  SUBMITTED — sponsorship BCA, sedang review
            [
                'description'         => 'Sponsorship Bank Central Asia — Anniversary Run 2026',
                'type'                => 'income',
                'amount'              => 400000000,
                'category'            => 'sponsor',
                'status'              => 'submitted',
                'sponsor_id'          => $sponsorBCA?->id,
                'event_id'            => $eventAnniversary?->id,
                'financial_account_id' => $akunPiutang?->id,
                'activity_id'         => $actMajorEvent?->id,
                'payee'               => 'PT Bank Central Asia Tbk',
                'transaction_date'    => now()->subDays(3)->toDateString(),
                'approved_by'         => null,
                'approved_at'         => null,
            ],

            // 6  DRAFT — pengeluaran cetak jersey, baru dibuat
            [
                'description'         => 'Percetakan jersey finisher Ultra Marathon Bromo (300 pcs)',
                'type'                => 'expense',
                'amount'              => 45000000,
                'category'            => 'other',
                'status'              => 'draft',
                'sponsor_id'          => null,
                'event_id'            => $eventBromo?->id,
                'financial_account_id' => $akunBeban?->id,
                'activity_id'         => $actCityRun?->id,
                'payee'               => 'Printing Merdeka',
                'due_date'            => now()->addDays(14)->toDateString(),
                'transaction_date'    => now()->toDateString(),
                'approved_by'         => null,
                'approved_at'         => null,
            ],

            // 7  DRAFT — pemasukan iuran anggota bulanan
            [
                'description'         => 'Iuran anggota SH3 bulan Agustus 2026',
                'type'                => 'income',
                'amount'              => 2500000,
                'category'            => 'other',
                'status'              => 'draft',
                'sponsor_id'          => null,
                'event_id'            => null,
                'financial_account_id' => $akunKas?->id,
                'activity_id'         => $actLariPagi?->id,
                'payee'               => null,
                'transaction_date'    => now()->toDateString(),
                'approved_by'         => null,
                'approved_at'         => null,
            ],

            // 8  CANCELLED — sponsorship Adidas, dibatalkan
            [
                'description'         => 'Sponsorship Adidas Running — City Run Sudirman (dibatalkan)',
                'type'                => 'income',
                'amount'              => 250000000,
                'category'            => 'sponsor',
                'status'              => 'cancelled',
                'sponsor_id'          => $sponsorAdidas?->id,
                'event_id'            => $eventCityRun?->id,
                'financial_account_id' => $akunPiutang?->id,
                'activity_id'         => $actCityRun?->id,
                'payee'               => 'PT Adidas Indonesia',
                'transaction_date'    => now()->subDays(20)->toDateString(),
                'approved_by'         => null,
                'approved_at'         => null,
            ],

            // 9  SUBMITTED — belanja medis
            [
                'description'         => 'Pembelian kotak P3K & obat-obatan event Anniversary',
                'type'                => 'expense',
                'amount'              => 1800000,
                'category'            => 'other',
                'status'              => 'submitted',
                'sponsor_id'          => null,
                'event_id'            => $eventAnniversary?->id,
                'financial_account_id' => $akunHutang?->id,
                'activity_id'         => $actMajorEvent?->id,
                'payee'               => 'Apotek Sehat',
                'transaction_date'    => now()->subDays(2)->toDateString(),
                'approved_by'         => null,
                'approved_at'         => null,
            ],

            // 10 PAID — biaya operasional bulanan
            [
                'description'         => 'Biaya operasional kantor SH3 Agustus 2026 (sewa, listrik, internet)',
                'type'                => 'expense',
                'amount'              => 4500000,
                'category'            => 'other',
                'status'              => 'paid',
                'sponsor_id'          => null,
                'event_id'            => null,
                'financial_account_id' => $akunKas?->id,
                'activity_id'         => $actLariPagi?->id,
                'payee'               => 'Pembayaran listrik & internet',
                'transaction_date'    => now()->subDays(1)->toDateString(),
                'approved_by'         => $approvedBy,
                'approved_at'         => now()->subDays(1),
            ],
        ];

        foreach ($entries as $data) {
            Bookkeeping::updateOrCreate(
                ['description' => $data['description']],
                array_merge($data, [
                    'receipt'    => null,
                    'created_by' => $createdBy,
                ])
            );
        }
    }
}
