<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketTransfer;
use App\Models\Timeline;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TicketActionController extends Controller
{
  /**
   * PJ Menyelesaikan Tiket (Resolve) dengan Data Dinamis
   */
  public function resolve(Request $request, Ticket $ticket)
  {
    $user = auth()->user();

    // 1. Validasi Hak Akses (Apakah user ini PJ yang berhak?)
    // Cek apakah Role user saat ini sesuai dengan current_assignee_role tiket
    // ATAU apakah user ini ditunjuk spesifik (assigned_user_id)
    $userRoles = is_array($user->roles) ? $user->roles : json_decode($user->roles, true);

    // Cek sederhana: apakah salah satu role user cocok dengan assignee tiket
    if (!in_array($ticket->current_assignee_role, $userRoles)) {
      return response()->json(['message' => 'Anda tidak memiliki akses untuk mengeksekusi tiket ini.'], 403);
    }

    // 2. Ambil Schema Action dari Layanan
    $service = $ticket->serviceCategory;
    $actionSchema = $service->action_schema;

    // 3. Validasi Input Dinamis (Jika ada schema)
    if ($actionSchema && count($actionSchema) > 0) {
      $dynamicRules = [];
      foreach ($actionSchema as $field) {
        if ($field['required'] ?? false) {
          $dynamicRules['action_data.' . $field['name']] = 'required';
        }
      }

      // Validasi data yang dikirim PJ
      $request->validate(array_merge([
        'notes' => 'nullable|string',
        'action_data' => 'required|array'
      ], $dynamicRules));
    }

    // 4. Update Tiket
    $ticket->status = 'resolved'; // Atau 'completed' tergantung flow
    $ticket->action_data = $request->action_data;
    $ticket->save();

    // 5. Catat Timeline
    Timeline::create([
      'ticket_id' => $ticket->id,
      'user_id' => $user->id,
      'action' => 'ticket_resolved',
      'details' => "Tiket diselesaikan oleh {$user->name}. Catatan: " . ($request->notes ?? '-'),
    ]);

    // Log Audit
    AuditLog::create([
      'user_id' => $user->id,
      'action' => 'TICKET_RESOLVED',
      'details' => "Resolved Ticket {$ticket->ticket_number}",
      'ip_address' => $request->ip()
    ]);

    return response()->json(['message' => 'Tiket berhasil diselesaikan', 'data' => $ticket]);
  }

  /**
   * PJ Mengoper Tiket (Transfer / Eskalasi)
   */
  public function transfer(Request $request, Ticket $ticket)
  {
    $validated = $request->validate([
      'to_role' => 'required|exists:roles,code', // Oper ke role apa (misal: 'gudang')
      'notes' => 'required|string',
    ]);

    $user = auth()->user();
    $oldRole = $ticket->current_assignee_role;

    // 1. Catat di tabel ticket_transfers
    TicketTransfer::create([
      'ticket_id' => $ticket->id,
      'from_user_id' => $user->id,
      'from_role' => $oldRole,
      'to_role' => $validated['to_role'],
      'notes' => $validated['notes'],
      'status' => 'pending' // Menandakan sedang di tangan divisi lain
    ]);

    // 2. Update Status Tiket
    $ticket->current_assignee_role = $validated['to_role']; // Bola pindah
    $ticket->is_escalated = true; // Flag bahwa ini tiket operan
    // Status bisa diubah jadi 'on_hold' atau tetap 'in_progress'
    $ticket->save();

    // 3. Timeline
    Timeline::create([
      'ticket_id' => $ticket->id,
      'user_id' => $user->id,
      'action' => 'ticket_transferred',
      'details' => "Tiket dioper ke bagian: " . strtoupper($validated['to_role']) . ". Alasan: {$validated['notes']}",
    ]);

    return response()->json(['message' => 'Tiket berhasil didelegasikan/dioper', 'data' => $ticket]);
  }
}
