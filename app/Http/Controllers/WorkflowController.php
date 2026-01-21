<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\WorkflowTransition;
use App\Models\Timeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowController extends Controller
{
    /**
     * Mendapatkan daftar aksi/tombol yang tersedia untuk User saat ini pada Tiket tertentu
     */
    public function getAvailableActions(Request $request, Ticket $ticket)
    {
        $user = auth()->user();

        // Ambil role user (support array roles atau single role)
        $userRoles = is_array($user->roles) ? $user->roles : [$user->role];

        // Cari status ID dari tabel workflow_statuses berdasarkan kode status tiket saat ini
        // Asumsi: Kita perlu join atau cari id status berdasarkan string 'status' di tiket
        // Untuk simplifikasi awal, kita anggap status di tiket sudah sesuai dengan 'code' di workflow_statuses
        $currentStatus = \App\Models\WorkflowStatus::where('code', $ticket->status)->first();

        if (!$currentStatus) {
            return response()->json(['data' => []]);
        }

        // Cari transisi yang:
        // 1. Sesuai kategori layanan tiket ini
        // 2. Dari status tiket saat ini
        // 3. Trigger role-nya dimiliki oleh user yang login
        $transitions = WorkflowTransition::where('service_category_id', $ticket->service_category_id)
            ->where('from_status_id', $currentStatus->id)
            ->whereIn('trigger_role', $userRoles)
            ->get();

        return response()->json([
            'data' => $transitions->map(function ($transition) {
                return [
                    'id' => $transition->id,
                    'label' => $transition->action_label,
                    'variant' => $this->getButtonVariant($transition->action_label), // Helper untuk warna tombol
                    'require_form' => $transition->required_form_schema, // Jika butuh input alasan dll
                ];
            })
        ]);
    }

    /**
     * Mengeksekusi transisi (Klik Tombol)
     */
    public function executeTransition(Request $request, Ticket $ticket, WorkflowTransition $transition)
    {
        // Validasi lagi apakah user boleh akses transisi ini (Security)
        $user = auth()->user();
        $userRoles = is_array($user->roles) ? $user->roles : [$user->role];

        if (!in_array($transition->trigger_role, $userRoles)) {
            return response()->json(['message' => 'Unauthorized action'], 403);
        }

        // Validasi input tambahan jika ada required_form_schema
        if ($transition->required_form_schema) {
            // Disini bisa tambahkan validasi dinamis berdasarkan schema
            // $request->validate(...);
        }

        DB::transaction(function () use ($ticket, $transition, $user, $request) {
            $toStatus = \App\Models\WorkflowStatus::find($transition->to_status_id);
            $oldStatus = $ticket->status;

            // 1. Update Status Tiket
            $ticket->status = $toStatus->code;

            // 2. Update Assignee Role (Delegasi)
            // Jika transisi mendefinisikan target role baru, update tiketnya
            if ($transition->target_assignee_role) {
                $ticket->current_assignee_role = $transition->target_assignee_role;
            }

            $ticket->save();

            // 3. Catat di Timeline
            Timeline::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'action' => 'status_changed',
                'details' => "Status berubah ke '{$toStatus->label}' via aksi: {$transition->action_label}",
                'metadata' => [
                    'old_status' => $oldStatus,
                    'new_status' => $toStatus->code,
                    'input_data' => $request->all() // Simpan alasan/input form tombol jika ada
                ]
            ]);
        });

        return response()->json(['message' => 'Status berhasil diperbarui', 'data' => $ticket]);
    }

    // Helper sederhana untuk menentukan warna tombol frontend
    private function getButtonVariant($label)
    {
        $label = strtolower($label);
        if (str_contains($label, 'tolak') || str_contains($label, 'reject')) return 'destructive';
        if (str_contains($label, 'setuju') || str_contains($label, 'approve')) return 'default'; // Biru/Hitam
        if (str_contains($label, 'selesai') || str_contains($label, 'complete')) return 'success'; // Hijau (custom class)
        return 'outline'; // Default netral
    }
}
