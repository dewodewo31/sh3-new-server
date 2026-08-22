<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventBudget;
use Illuminate\Database\Seeder;

class EventBudgetSeeder extends Seeder
{
    public function run(): void
    {
        $eventAnniversary = Event::where('title', 'SH3 Anniversary Run 2026')->first();
        $eventCisarua     = Event::where('title', 'Long Run Cisarua')->first();
        $eventKotaTua     = Event::where('title', 'Night Run Kota Tua')->first();
        $eventBromo       = Event::where('title', 'Ultra Marathon Bromo')->first();
        $eventCityRun     = Event::where('title', 'City Run Sudirman')->first();

        $actLariPagi   = Activity::where('name', 'Lari Pagi')->first();
        $actCityRun    = Activity::where('name', 'City Run')->first();
        $actMajorEvent = Activity::where('name', 'Major Event')->first();

        if (! $actLariPagi || ! $actCityRun || ! $actMajorEvent) {
            return; // activities belum di-seed
        }

        $budgets = [
            // ── Anniversary Run 2026 (event besar) ──
            [$eventAnniversary, $actMajorEvent, 800000000, 'Total budget Anniversary: venue, logistics, medali, kaos'],
            [$eventAnniversary, $actCityRun,    150000000, 'Operasional hari-H (panitia, konsumsi, toilet portable)'],
            [$eventAnniversary, $actLariPagi,    50000000, 'Persiapan latihan 3 bulan sebelum event'],

            // ── Long Run Cisarua ──
            [$eventCisarua, $actLariPagi,  8000000, 'Sewa bus, konsumsi, medali finisher'],
            [$eventCisarua, $actCityRun,   2000000, 'Dokumentasi & hadiah'],

            // ── Night Run Kota Tua ──
            [$eventKotaTua, $actCityRun,  20000000, 'Sewa venue Kota Tua, lampu dekorasi, souvenir LED'],
            [$eventKotaTua, $actLariPagi,  5000000, 'Konsumsi & snacks'],

            // ── Ultra Marathon Bromo ──
            [$eventBromo, $actMajorEvent, 500000000, 'Logistik, medali, jaket finisher, transportasi'],
            [$eventBromo, $actLariPagi,  100000000, 'Persiapan rute, SAR, medis, tim pendukung'],

            // ── City Run Sudirman ──
            [$eventCityRun, $actCityRun,   25000000, 'Sewa road barrier, water station, kaos event'],
            [$eventCityRun, $actLariPagi,   5000000, 'Konsumsi panitia'],
        ];

        foreach ($budgets as [$event, $activity, $amount, $notes]) {
            if (! $event) {
                continue;
            }

            EventBudget::updateOrCreate(
                ['event_id' => $event->id, 'activity_id' => $activity->id],
                ['amount' => $amount, 'notes' => $notes]
            );
        }
    }
}
