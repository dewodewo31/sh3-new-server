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
            ['name' => 'Hendra Wijaya', 'position' => 'Ketua Umum', 'role_description' => 'Memimpin organisasi SH3', 'sort_order' => 1, 'level' => 1, 'parent_position' => null],
            ['name' => 'Sarah Amelia', 'position' => 'Wakil Ketua', 'role_description' => 'Membantu tugas ketua umum', 'sort_order' => 2, 'level' => 2, 'parent_position' => 'Ketua Umum'],
            ['name' => 'Rudi Hartono', 'position' => 'Sekretaris', 'role_description' => 'Administrasi organisasi', 'sort_order' => 3, 'level' => 2, 'parent_position' => 'Ketua Umum'],
            ['name' => 'Maya Puspita', 'position' => 'Bendahara', 'role_description' => 'Pengelolaan keuangan', 'sort_order' => 4, 'level' => 2, 'parent_position' => 'Ketua Umum'],
            ['name' => 'Andika Putra', 'position' => 'Koordinator Event', 'role_description' => 'Mengelola kegiatan lari', 'sort_order' => 5, 'level' => 3, 'parent_position' => 'Wakil Ketua'],
            ['name' => 'Rina Susanti', 'position' => 'Koordinator Membership', 'role_description' => 'Pengelolaan anggota', 'sort_order' => 6, 'level' => 3, 'parent_position' => 'Wakil Ketua'],
            ['name' => 'Bagus Prakoso', 'position' => 'Koordinator Humas', 'role_description' => 'Hubungan masyarakat dan sponsor', 'sort_order' => 7, 'level' => 3, 'parent_position' => 'Sekretaris'],
            ['name' => 'Dina Anggraini', 'position' => 'Koordinator Media', 'role_description' => 'Dokumentasi dan publikasi', 'sort_order' => 8, 'level' => 3, 'parent_position' => 'Sekretaris'],
        ];

        foreach ($members as $i => $data) {
            $participantId = $participantPool[$i] ?? null;
            $parent = $data['parent_position'] ? OrganizationMember::where('position', $data['parent_position'])->first() : null;

            OrganizationMember::updateOrCreate(
                ['name' => $data['name'], 'position' => $data['position']],
                [
                    'participant_id' => $participantId,
                    'parent_id' => $parent?->id,
                    'role_description' => $data['role_description'],
                    'sort_order' => $data['sort_order'],
                    'level' => $data['level'],
                    'is_active' => true,
                    'period_start' => now()->startOfYear()->toDateString(),
                    'period_end' => now()->endOfYear()->toDateString(),
                ]
            );
        }
    }
}
