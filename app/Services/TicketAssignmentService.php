<?php

namespace App\Services;

use App\Models\User;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Log;

class TicketAssignmentService
{
  /**
   * Menentukan siapa yang akan menangani tiket
   * Menggunakan Algoritma Weighted Scoring (Beban Aktif + Riwayat Bulanan)
   */
  public function determineAssignee(ServiceCategory $category)
  {
    // Default Role Fallback
    $targetRole = $category->target_role ?? 'admin_layanan';

    $result = [
      'assigned_to' => null,
      'current_assignee_role' => $targetRole,
      'status' => 'submitted'
    ];

    // --- SKENARIO 1: DIRECT ASSIGNMENT ---
    if ($category->assignment_type === 'direct' && $category->default_assignee_id) {
      $defaultUser = User::find($category->default_assignee_id);

      if ($defaultUser && !$defaultUser->is_on_leave) {
        $result['assigned_to'] = $defaultUser->id;
        $result['status'] = 'assigned';
        return $result;
      }

      Log::info("Assignment Direct Gagal: User default sedang cuti/tidak aktif.");
    }

    // --- SKENARIO 2: SMART AUTO ASSIGNMENT ---
    if ($category->assignment_type === 'auto') {

      // 1. Query Kandidat + Hitung Beban Sekaligus
      // Kita gunakan withCount untuk efisiensi database (menghindari N+1 query)
      $candidates = User::where(function ($q) use ($targetRole) {
        $q->where('role', $targetRole)
          ->orWhereJsonContains('roles', $targetRole);
      })
        ->where('is_on_leave', false) // Filter yang tidak cuti
        ->withCount([
          // Hitung Tiket yang SEDANG DIKERJAKAN (Beban Berat)
          'assignedTickets as active_load' => function ($query) {
            $query->whereIn('status', ['assigned', 'in_progress', 'on_hold', 'waiting_for_pegawai']);
          },
          // Hitung Tiket SEBULAN TERAKHIR (Faktor Keadilan)
          'assignedTickets as monthly_history' => function ($query) {
            $query->where('created_at', '>=', now()->subDays(30));
          }
        ])
        ->get();

      if ($candidates->isEmpty()) {
        Log::warning("Auto-Assign: Tidak ada kandidat tersedia (Role: {$targetRole}, Aktif).");
        return $result;
      }

      // 2. Hitung Skor & Pilih Pemenang
      // Rumus: (Active * 3) + (History * 1)
      // Artinya: Kesibukan saat ini 3x lebih penting daripada sejarah, tapi sejarah tetap dihitung.

      $bestCandidate = null;
      $lowestScore = 999999;

      foreach ($candidates as $candidate) {
        // Kalkulasi Skor
        $score = ($candidate->active_load * 3) + ($candidate->monthly_history * 1);

        // Debugging (Cek log untuk memastikan algoritma adil)
        // Log::info("User: {$candidate->name} | Active: {$candidate->active_load} | History: {$candidate->monthly_history} | Score: {$score}");

        // Logic Mencari Skor Terendah
        if ($score < $lowestScore) {
          $lowestScore = $score;
          $bestCandidate = $candidate;
        }
        // Jika skor SERI, kita bisa tambahkan randomizer agar tidak selalu user urutan pertama (ID kecil) yang kena
        elseif ($score == $lowestScore) {
          if (rand(0, 1)) { // 50% chance untuk ganti kandidat jika seri
            $bestCandidate = $candidate;
          }
        }
      }

      // 3. Finalisasi
      if ($bestCandidate) {
        $result['assigned_to'] = $bestCandidate->id;
        $result['status'] = 'assigned';

        Log::info("Auto-Assign Winner: {$bestCandidate->name} (Score: {$lowestScore})");
      }
    }

    return $result;
  }
}
