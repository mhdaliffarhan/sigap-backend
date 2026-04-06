<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DynamicServiceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil Role ID dari tabel roles
        $roleKendaraan = DB::table('roles')->where('code', 'admin_kendaraan')->first();
        $roleRuangan = DB::table('roles')->where('code', 'admin_ruangan')->first();
        $roleZoom = DB::table('roles')->where('code', 'admin_zoom')->first();

        // 2. Buat Status Workflow Dasar (Helper Function Style)
        $statuses = [
            ['code' => 'submitted', 'label' => 'Menunggu Persetujuan', 'color' => 'blue', 'is_end_state' => false],
            ['code' => 'approved', 'label' => 'Disetujui', 'color' => 'green', 'is_end_state' => false],
            ['code' => 'rejected', 'label' => 'Ditolak', 'color' => 'red', 'is_end_state' => true],
            ['code' => 'completed', 'label' => 'Selesai', 'color' => 'gray', 'is_end_state' => true],
        ];

        foreach ($statuses as $status) {
            DB::table('workflow_statuses')->updateOrInsert(
                ['code' => $status['code']],
                array_merge($status, ['id' => DB::table('workflow_statuses')->where('code', $status['code'])->value('id') ?? (string) Str::uuid()])
            );
        }

        $submittedId = DB::table('workflow_statuses')->where('code', 'submitted')->value('id');

        // 3. LAYANAN 1: Peminjaman Kendaraan Dinas
        $mobilSlug = 'peminjaman-kendaraan';
        $mobilId = DB::table('service_categories')->where('slug', $mobilSlug)->value('id') ?? (string) Str::uuid();
        DB::table('service_categories')->updateOrInsert(['slug' => $mobilSlug], [
            'id' => $mobilId,
            'name' => 'Peminjaman Kendaraan',
            'type' => 'booking',
            'handling_role_id' => $roleKendaraan?->id,
            'icon' => 'car',
            'description' => 'Layanan peminjaman mobil dinas kantor',
            'is_resource_based' => true,
            'form_schema' => json_encode([
                ['name' => 'tujuan', 'label' => 'Tujuan Perjalanan', 'type' => 'text', 'required' => true],
                ['name' => 'keperluan', 'label' => 'Keperluan', 'type' => 'textarea', 'required' => true],
                ['name' => 'jumlah_penumpang', 'label' => 'Jumlah Penumpang', 'type' => 'number', 'required' => true],
                ['name' => 'with_driver', 'label' => 'Butuh Supir?', 'type' => 'boolean', 'required' => false]
            ]),
            'action_schema' => json_encode([
                ['name' => 'km_akhir', 'label' => 'Kilometer Akhir', 'type' => 'number', 'required' => true],
                ['name' => 'kondisi_mobil', 'label' => 'Kondisi Mobil Setelah Pakai', 'type' => 'textarea', 'required' => true]
            ]),
            'is_active' => true,
        ]);

        // 4. LAYANAN 2: Peminjaman Aula / Ruang Rapat
        $ruangSlug = 'peminjaman-ruangan';
        $ruangId = DB::table('service_categories')->where('slug', $ruangSlug)->value('id') ?? (string) Str::uuid();
        DB::table('service_categories')->updateOrInsert(['slug' => $ruangSlug], [
            'id' => $ruangId,
            'name' => 'Peminjaman Aula & Ruangan',
            'type' => 'booking',
            'handling_role_id' => $roleRuangan?->id,
            'icon' => 'building',
            'description' => 'Layanan peminjaman Aula Utama dan Ruang Rapat',
            'is_resource_based' => true,
            'form_schema' => json_encode([
                ['name' => 'nama_acara', 'label' => 'Nama Acara', 'type' => 'text', 'required' => true],
                ['name' => 'estimasi_peserta', 'label' => 'Estimasi Jumlah Peserta', 'type' => 'number', 'required' => true],
                ['name' => 'fasilitas_tambahan', 'label' => 'Butuh Sound System / Proyektor?', 'type' => 'boolean', 'required' => false]
            ]),
            'is_active' => true,
        ]);

        // 5. LAYANAN 3: Peminjaman Akun Zoom
        $zoomSlug = 'peminjaman-zoom';
        $zoomId = DB::table('service_categories')->where('slug', $zoomSlug)->value('id') ?? (string) Str::uuid();
        DB::table('service_categories')->updateOrInsert(['slug' => $zoomSlug], [
            'id' => $zoomId,
            'name' => 'Peminjaman Akun Zoom',
            'type' => 'booking',
            'handling_role_id' => $roleZoom?->id,
            'icon' => 'video',
            'description' => 'Layanan peminjaman akun Zoom Meeting Premium',
            'is_resource_based' => true,
            'form_schema' => json_encode([
                ['name' => 'topik_meeting', 'label' => 'Topik Meeting', 'type' => 'text', 'required' => true],
                ['name' => 'is_recorded', 'label' => 'Butuh Cloud Recording?', 'type' => 'boolean', 'required' => false]
            ]),
            'is_active' => true,
        ]);

        // 6. Buat Sample Resources
        $resources = [
            ['name' => 'Innova Hitam - DR 1234 XY', 'cat_id' => $mobilId, 'cap' => 7],
            ['name' => 'Aula Utama', 'cat_id' => $ruangId, 'cap' => 100],
            ['name' => 'Zoom Account 1 (Premium)', 'cat_id' => $zoomId, 'cap' => null],
        ];

        foreach ($resources as $res) {
            DB::table('resources')->updateOrInsert(['name' => $res['name']], [
                'id' => DB::table('resources')->where('name', $res['name'])->value('id') ?? (string) Str::uuid(),
                'service_category_id' => $res['cat_id'],
                'capacity' => $res['cap'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
