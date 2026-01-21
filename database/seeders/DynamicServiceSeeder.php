<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DynamicServiceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Status Workflow Dasar
        $submittedId = Str::uuid();
        $approvedId = Str::uuid();
        $rejectedId = Str::uuid();

        DB::table('workflow_statuses')->insert([
            ['id' => $submittedId, 'code' => 'submitted', 'label' => 'Menunggu Persetujuan', 'color' => 'blue', 'is_end_state' => false],
            ['id' => $approvedId, 'code' => 'approved', 'label' => 'Disetujui', 'color' => 'green', 'is_end_state' => true],
            ['id' => $rejectedId, 'code' => 'rejected', 'label' => 'Ditolak', 'color' => 'red', 'is_end_state' => true],
        ]);

        // 2. Buat Layanan: Peminjaman Kendaraan Dinas
        $mobilCatId = Str::uuid();
        DB::table('service_categories')->insert([
            'id' => $mobilCatId,
            'name' => 'Peminjaman Kendaraan',
            'slug' => 'peminjaman-kendaraan',
            'type' => 'booking',
            'icon' => 'car',
            'description' => 'Layanan peminjaman mobil dinas kantor',
            'form_schema' => json_encode([
                [
                    'name' => 'keperluan',
                    'label' => 'Keperluan Dinas',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Contoh: Perjalanan dinas ke Lombok Timur'
                ],
                [
                    'name' => 'jumlah_penumpang',
                    'label' => 'Jumlah Penumpang',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'name' => 'with_driver',
                    'label' => 'Butuh Supir?',
                    'type' => 'boolean',
                    'required' => false
                ]
            ]),
            'is_active' => true,
        ]);

        // 3. Buat Data Dummy Mobil (Resources)
        DB::table('resources')->insert([
            [
                'id' => Str::uuid(),
                'service_category_id' => $mobilCatId,
                'name' => 'Toyota Innova - DR 1234 XY',
                'description' => 'Mobil operasional utama, Transmisi Manual',
                'capacity' => 7,
                'meta_data' => json_encode(['plat' => 'DR 1234 XY', 'warna' => 'Hitam']),
                'is_active' => true
            ],
            [
                'id' => Str::uuid(),
                'service_category_id' => $mobilCatId,
                'name' => 'Toyota Avanza - DR 5678 AB',
                'description' => 'Mobil operasional cadangan',
                'capacity' => 6,
                'meta_data' => json_encode(['plat' => 'DR 5678 AB', 'warna' => 'Silver']),
                'is_active' => true
            ]
        ]);

        // 4. Buat Workflow Transition (Logika Tombol)
        // Admin Layanan bisa menyetujui tiket mobil
        DB::table('workflow_transitions')->insert([
            'id' => Str::uuid(),
            'service_category_id' => $mobilCatId,
            'from_status_id' => $submittedId,
            'to_status_id' => $approvedId,
            'action_label' => 'Setujui Peminjaman',
            'trigger_role' => 'admin_layanan', // Sesuaikan dengan role yang ada di User Anda
            'target_assignee_role' => null,
            'required_form_schema' => null
        ]);
    }
}
