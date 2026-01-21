<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdditionalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Status Baru: "Menunggu Admin GA"
        $delegatedId = Str::uuid();
        
        // Cek dulu agar tidak duplikat
        $exist = DB::table('workflow_statuses')->where('code', 'delegated_to_ga')->first();
        if (!$exist) {
            DB::table('workflow_statuses')->insert([
                'id' => $delegatedId,
                'code' => 'delegated_to_ga',
                'label' => 'Menunggu Admin GA', // Label di Badge Status
                'color' => 'orange',            // Warna Badge
                'is_end_state' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $delegatedId = $exist->id;
        }

        // Ambil ID status & kategori yang sudah ada
        $submitted = DB::table('workflow_statuses')->where('code', 'submitted')->first();
        $approved = DB::table('workflow_statuses')->where('code', 'approved')->first();
        $rejected = DB::table('workflow_statuses')->where('code', 'rejected')->first();
        
        // Target Layanan: Peminjaman Kendaraan
        $mobilCategory = DB::table('service_categories')->where('slug', 'peminjaman-kendaraan')->first();

        if ($mobilCategory && $submitted && $approved && $rejected) {
            
            // A. TRANSISI 1: Admin Layanan -> Delegasi ke Admin GA
            DB::table('workflow_transitions')->insert([
                'id' => Str::uuid(),
                'service_category_id' => $mobilCategory->id,
                'from_status_id' => $submitted->id,
                'to_status_id' => $delegatedId,
                'action_label' => 'Delegasikan ke Admin GA',
                'trigger_role' => 'admin_layanan', // Yang pencet: Admin Layanan
                'target_assignee_role' => 'admin_ga', // Bola pindah ke: Admin GA
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // B. TRANSISI 2: Admin GA -> Setujui
            DB::table('workflow_transitions')->insert([
                'id' => Str::uuid(),
                'service_category_id' => $mobilCategory->id,
                'from_status_id' => $delegatedId,
                'to_status_id' => $approved->id,
                'action_label' => 'Setujui Peminjaman',
                'trigger_role' => 'admin_ga', // Yang pencet: Admin GA
                'target_assignee_role' => null, // Selesai
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // C. TRANSISI 3: Admin GA -> Tolak (Pake Alasan)
            DB::table('workflow_transitions')->insert([
                'id' => Str::uuid(),
                'service_category_id' => $mobilCategory->id,
                'from_status_id' => $delegatedId,
                'to_status_id' => $rejected->id,
                'action_label' => 'Tolak Peminjaman',
                'trigger_role' => 'admin_ga',
                'target_assignee_role' => null,
                'required_form_schema' => json_encode([
                    [
                        'name' => 'reason',
                        'label' => 'Alasan Penolakan',
                        'type' => 'textarea',
                        'required' => true
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // D. TRANSISI 4: Admin GA -> Kembalikan ke Admin Layanan (Salah Alamat/Revisi)
            DB::table('workflow_transitions')->insert([
                'id' => Str::uuid(),
                'service_category_id' => $mobilCategory->id,
                'from_status_id' => $delegatedId,
                'to_status_id' => $submitted->id, // Balik ke status awal
                'action_label' => 'Kembalikan ke Admin Layanan',
                'trigger_role' => 'admin_ga',
                'target_assignee_role' => 'admin_layanan', // Bola balik ke Admin Layanan
                'required_form_schema' => json_encode([
                    [
                        'name' => 'notes',
                        'label' => 'Catatan Pengembalian',
                        'type' => 'textarea',
                        'required' => true
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}