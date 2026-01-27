<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // Pastikan import ini ada

class WorkflowSeeder extends Seeder
{
  public function run()
  {
    // 1. Buat Status Dasar
    $statuses = [
      ['code' => 'submitted', 'label' => 'Diajukan', 'description' => 'Tiket baru masuk', 'color' => 'gray'],
      ['code' => 'assigned', 'label' => 'Ditugaskan', 'description' => 'Tiket sudah diserahkan ke teknisi/staff', 'color' => 'blue'],
      ['code' => 'in_progress', 'label' => 'Sedang Dikerjakan', 'description' => 'Sedang dalam penanganan', 'color' => 'yellow'],
      ['code' => 'pending_review', 'label' => 'Menunggu Review', 'description' => 'Selesai dikerjakan, menunggu konfirmasi', 'color' => 'orange'],
      ['code' => 'resolved', 'label' => 'Selesai (Resolved)', 'description' => 'Masalah selesai, menunggu penutupan', 'color' => 'green'],
      ['code' => 'closed', 'label' => 'Ditutup', 'description' => 'Tiket selesai sepenuhnya', 'color' => 'green'],
      ['code' => 'rejected', 'label' => 'Ditolak', 'description' => 'Pengajuan ditolak', 'color' => 'red'],
    ];

    foreach ($statuses as $status) {
      // Cek apakah data sudah ada?
      $exist = DB::table('workflow_statuses')->where('code', $status['code'])->first();

      if (!$exist) {
        // INSERT BARU (Generate UUID)
        DB::table('workflow_statuses')->insert(array_merge($status, [
          'id' => (string) Str::uuid(), // <--- Solusi Error 1364
          'created_at' => now(),
          'updated_at' => now(),
        ]));
      } else {
        // UPDATE (Tanpa ID)
        DB::table('workflow_statuses')->where('code', $status['code'])->update(array_merge($status, [
          'updated_at' => now(),
        ]));
      }
    }

    // Ambil ID status untuk relasi
    $s = DB::table('workflow_statuses')->pluck('id', 'code');

    // 2. Buat Transisi Global (service_category_id = NULL)
    $transitions = [
      // Admin Layanan: Submitted -> Assigned
      [
        'from_status_id' => $s['submitted'],
        'to_status_id' => $s['assigned'],
        'action_label' => 'Tugaskan / Assign',
        'trigger_role' => 'admin_layanan',
        'target_assignee_role' => 'teknisi',
        'service_category_id' => null
      ],
      // Admin Layanan: Submitted -> Rejected
      [
        'from_status_id' => $s['submitted'],
        'to_status_id' => $s['rejected'],
        'action_label' => 'Tolak Pengajuan',
        'trigger_role' => 'admin_layanan',
        'target_assignee_role' => null,
        'service_category_id' => null,
        'required_form_schema' => json_encode([
          ['name' => 'alasan_penolakan', 'label' => 'Alasan Penolakan', 'type' => 'textarea', 'required' => true]
        ])
      ],

      // Teknisi: Assigned -> In Progress
      [
        'from_status_id' => $s['assigned'],
        'to_status_id' => $s['in_progress'],
        'action_label' => 'Mulai Pengerjaan',
        'trigger_role' => 'teknisi',
        'target_assignee_role' => 'teknisi',
        'service_category_id' => null
      ],

      // Teknisi: In Progress -> Resolved
      [
        'from_status_id' => $s['in_progress'],
        'to_status_id' => $s['resolved'],
        'action_label' => 'Selesaikan Pekerjaan',
        'trigger_role' => 'teknisi',
        'target_assignee_role' => 'admin_layanan',
        'service_category_id' => null,
        'required_form_schema' => json_encode([
          ['name' => 'laporan_pengerjaan', 'label' => 'Laporan Pengerjaan', 'type' => 'textarea', 'required' => true],
          ['name' => 'foto_bukti', 'label' => 'Link Foto Bukti (Opsional)', 'type' => 'text', 'required' => false]
        ])
      ],

      // Admin Layanan: Resolved -> Closed
      [
        'from_status_id' => $s['resolved'],
        'to_status_id' => $s['closed'],
        'action_label' => 'Tutup Tiket',
        'trigger_role' => 'admin_layanan',
        'target_assignee_role' => null,
        'service_category_id' => null
      ],
    ];

    foreach ($transitions as $transition) {
      // Kriteria unik untuk cek duplikat
      $criteria = [
        'from_status_id' => $transition['from_status_id'],
        'to_status_id' => $transition['to_status_id'],
        'trigger_role' => $transition['trigger_role'],
        'service_category_id' => null
      ];

      $exist = DB::table('workflow_transitions')->where($criteria)->first();

      if (!$exist) {
        // INSERT BARU (Generate UUID)
        DB::table('workflow_transitions')->insert(array_merge($transition, [
          'id' => (string) Str::uuid(), // <--- Solusi Error 1364
          'created_at' => now(),
          'updated_at' => now(),
        ]));
      } else {
        // UPDATE
        DB::table('workflow_transitions')->where('id', $exist->id)->update(array_merge($transition, [
          'updated_at' => now(),
        ]));
      }
    }
  }
}
