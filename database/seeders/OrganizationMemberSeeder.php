<?php

namespace Database\Seeders;

use App\Models\OrganizationMember;
use App\Models\Participant;
use Illuminate\Database\Seeder;

class OrganizationMemberSeeder extends Seeder
{
    public function run(): void
    {
        $participants = Participant::all();
        $participantPool = $participants->pluck('id')->toArray();

        $members = [
            ['name' => 'Hendra Wijaya', 'position' => 'Ketua Umum', 'role_description' => 'Memimpin organisasi SH3', 'sort_order' => 1],
            ['name' => 'Sarah Amelia', 'position' => 'Wakil Ketua', 'role_description' => 'Membantu tugas ketua umum', 'sort_order' => 2],
            ['name' => 'Rudi Hartono', 'position' => 'Sekretaris', 'role_description' => 'Administrasi organisasi', 'sort_order' => 3],
            ['name' => 'Maya Puspita', 'position' => 'Bendahara', 'role_description' => 'Pengelolaan keuangan', 'sort_order' => 4],
            ['name' => 'Andika Putra', 'position' => 'Koordinator Event', 'role_description' => 'Mengelola kegiatan lari', 'sort_order' => 5],
            ['name' => 'Rina Susanti', 'position' => 'Koordinator Membership', 'role_description' => 'Pengelolaan anggota', 'sort_order' => 6],
            ['name' => 'Bagus Prakoso', 'position' => 'Koordinator Humas', 'role_description' => 'Hubungan masyarakat dan sponsor', 'sort_order' => 7],
            ['name' => 'Dina Anggraini', 'position' => 'Koordinator Media', 'role_description' => 'Dokumentasi dan publikasi', 'sort_order' => 8],
        ];

        foreach ($members as $i => $data) {
            $participantId = $participantPool[$i] ?? null;

            OrganizationMember::updateOrCreate(
                ['name' => $data['name'], 'position' => $data['position']],
                [
                    'participant_id' => $participantId,
                    'role_description' => $data['role_description'],
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                    'period_start' => now()->startOfYear()->toDateString(),
                    'period_end' => now()->endOfYear()->toDateString(),
                ]
            );
        }
    }
}
