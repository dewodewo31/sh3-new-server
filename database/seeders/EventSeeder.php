<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');
        $organizer = User::where('role', 'organizer')->first()?->id ?? User::first()?->id;

        $events = [
            [
                'slug' => 'sh3-anniversary-run-2026',
                'title' => 'SH3 Anniversary Run 2026',
                'category_slug' => 'major-events',
                'description' => 'Perayaan ulang tahun SH3 dengan lari 21K, 10K, dan 5K.',
                'location' => 'GBK Stadium',
                'address' => 'Jalan Pintu Satu Senayan, Jakarta Pusat',
                'key_points' => ['Start & finish di GBK', 'Finisher medal eksklusif', 'Live music'],
                'status' => 'publish',
                'start_offset_days' => 60,
                'duration_hours' => 6,
                'quota' => 500,
                'price' => 150_000,
                'schedules' => [
                    ['title' => 'Gun Start 21K', 'description' => 'Start kategori 21K', 'order_number' => 1],
                    ['title' => 'Gun Start 10K', 'description' => 'Start kategori 10K', 'order_number' => 2],
                    ['title' => 'Gun Start 5K', 'description' => 'Start kategori 5K', 'order_number' => 3],
                    ['title' => 'Awarding Ceremony', 'description' => 'Pengumuman pemenang', 'order_number' => 4],
                ],
            ],
            [
                'slug' => 'long-run-cisarua',
                'title' => 'Long Run Cisarua',
                'category_slug' => 'long-run',
                'description' => 'Long run mingguan di kawasan Cisarua dengan pemandangan pegunungan.',
                'location' => 'Cisarua, Bogor',
                'address' => 'Taman Wisata Cisarua, Bogor, Jawa Barat',
                'key_points' => ['Rute menantang', 'Coffee break', 'Pemandu pace group'],
                'status' => 'publish',
                'start_offset_days' => 14,
                'duration_hours' => 3,
                'quota' => 100,
                'price' => 25_000,
                'schedules' => [
                    ['title' => 'Warm Up', 'description' => 'Pemanasan bersama', 'order_number' => 1],
                    ['title' => 'Start Long Run', 'description' => 'Mulai lari 10K', 'order_number' => 2],
                ],
            ],
            [
                'slug' => 'night-run-kota-tua',
                'title' => 'Night Run Kota Tua',
                'category_slug' => 'short-run',
                'description' => 'Fun run malam hari menyusuri kawasan Kota Tua Jakarta.',
                'location' => 'Kota Tua, Jakarta Barat',
                'address' => 'Fatahillah Square, Kota Tua, Jakarta Barat',
                'key_points' => ['Night run 5K', 'Foto instagramable', 'Souvenir lampu LED'],
                'status' => 'ongoing',
                'start_offset_days' => -5,
                'duration_hours' => 3,
                'quota' => 200,
                'price' => 50_000,
                'schedules' => [
                    ['title' => 'Runner Check-in', 'description' => 'Registrasi ulang peserta', 'order_number' => 1],
                    ['title' => 'Start Night Run', 'description' => 'Mulai lari 5K', 'order_number' => 2],
                    ['title' => 'Finish & Snack', 'description' => 'Makanan dan minuman', 'order_number' => 3],
                ],
            ],
            [
                'slug' => 'ultra-marathon-bromo',
                'title' => 'Ultra Marathon Bromo',
                'category_slug' => 'super-long',
                'description' => 'Ultra marathon 50K di kawasan Gunung Bromo.',
                'location' => 'Gunung Bromo',
                'address' => 'Taman Nasional Bromo Tengger Semeru',
                'key_points' => ['Jarak 50K', 'Elevation +2000m', 'Finisher jacket'],
                'status' => 'completed',
                'start_offset_days' => -45,
                'duration_hours' => 14,
                'quota' => 300,
                'price' => 350_000,
                'schedules' => [
                    ['title' => 'Race Briefing', 'description' => 'Briefing peserta', 'order_number' => 1],
                    ['title' => 'Gun Start', 'description' => 'Start ultra 50K', 'order_number' => 2],
                ],
            ],
            [
                'slug' => 'city-run-sudirman',
                'title' => 'City Run Sudirman',
                'category_slug' => 'short-run',
                'description' => 'Fun run di kawasan perkantoran Sudirman di akhir pekan.',
                'location' => 'Sudirman, Jakarta',
                'address' => 'Bundaran HI, Jakarta Pusat',
                'key_points' => ['Rute datar', 'Cocok untuk pemula', 'Booth UMKM'],
                'status' => 'draft',
                'start_offset_days' => 90,
                'duration_hours' => 3,
                'quota' => 150,
                'price' => 40_000,
                'schedules' => [
                    ['title' => 'Start City Run', 'description' => 'Mulai lari 5K', 'order_number' => 1],
                ],
            ],
            [
                'slug' => 'long-run-monas',
                'title' => 'Long Run Monas',
                'category_slug' => 'long-run',
                'description' => 'Long run pagi hari mengelilingi kawasan Monas.',
                'location' => 'Monas, Jakarta Pusat',
                'address' => 'Monumen Nasional, Jakarta Pusat',
                'key_points' => ['Rute 10K', 'Sarapan bersama', 'Gratis untuk member'],
                'status' => 'completed',
                'start_offset_days' => -15,
                'duration_hours' => 2,
                'quota' => null,
                'price' => null,
                'schedules' => [
                    ['title' => 'Start Long Run', 'description' => 'Mulai lari 10K', 'order_number' => 1],
                ],
            ],
        ];

        foreach ($events as $data) {
            $categoryId = $categories[$data['category_slug']] ?? null;

            if (! $categoryId) {
                continue;
            }

            $start = now()->addDays($data['start_offset_days'])->setTime(6, 0);
            $end = (clone $start)->addHours($data['duration_hours']);
            $regStart = (clone $start)->subDays(30);
            $regEnd = $data['status'] === 'draft'
                ? (clone $start)->addDays(30)
                : (clone $start)->subDays(7);

            $event = Event::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id' => $categoryId,
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'location' => $data['location'],
                    'address' => $data['address'],
                    'key_points' => $data['key_points'],
                    'start_date' => $start,
                    'end_date' => $end,
                    'registration_start_date' => $regStart,
                    'registration_end_date' => $regEnd,
                    'quota' => $data['quota'],
                    'price' => $data['price'],
                    'is_free_for_members' => true,
                    'status' => $data['status'],
                    'created_by' => $organizer,
                    'updated_by' => $organizer,
                ]
            );

            foreach ($data['schedules'] as $i => $schedule) {
                EventSchedule::updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'order_number' => $schedule['order_number'],
                    ],
                    [
                        'title' => $schedule['title'],
                        'description' => $schedule['description'],
                        'start_time' => (clone $start)->addMinutes($i * 60),
                        'end_time' => (clone $start)->addMinutes($i * 60 + 45),
                    ]
                );
            }
        }
    }
}
