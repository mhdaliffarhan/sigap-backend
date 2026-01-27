<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use App\Mail\NotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TicketNotificationService
{
    /**
     * Helper: Cari User berdasarkan Role dengan Robust (Support String & JSON)
     * KUNCI PERBAIKAN: Cek kolom 'role' (string) terlebih dahulu sesuai migrasi terakhir
     */
    private static function getUsersByRole(string $roleName)
    {
        return User::where(function ($query) use ($roleName) {
            // Cek kolom 'role' (String tunggal) - INI YANG UTAMA
            $query->where('role', $roleName)
                // Cek kolom 'roles' (JSON Array) - BACKUP untuk kompatibilitas
                ->orWhereJsonContains('roles', $roleName);
        })->get();
    }

    /**
     * Helper: Create notification and send email
     * PERBAIKAN: Mapping data ke kolom dedicated (reference_type, reference_id) 
     * agar sesuai dengan struktur tabel notifications.
     */
    private static function createNotificationWithEmail(array $data): Notification
    {
        // 1. Tentukan Action URL jika tidak disediakan
        $actionUrl = $data['action_url'] ?? null;
        if (!$actionUrl && isset($data['reference_id']) && ($data['reference_type'] ?? '') === 'ticket') {
            $actionUrl = "/tickets/{$data['reference_id']}";
        }

        // 2. Simpan ke Database
        $notification = Notification::create([
            'user_id'        => $data['user_id'],
            'title'          => $data['title'],
            'message'        => $data['message'],
            'type'           => $data['type'] ?? 'info',

            // Masukkan ke kolom khusus, JANGAN dibungkus ke JSON 'data'
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id'   => $data['reference_id'] ?? null,
            'action_url'     => $actionUrl,

            // Kolom data untuk metadata tambahan saja
            'data'           => $data['data'] ?? null,
            'is_read'        => false
        ]);

        // 3. Kirim Email (Opsional - dalam try catch)
        try {
            $user = User::find($data['user_id']);
            if ($user && $user->email && class_exists(NotificationMail::class)) {
                // Gunakan send() untuk testing langsung, queue() untuk production
                Mail::to($user->email)->send(new NotificationMail($user, $notification));
            }
        } catch (\Exception $e) {
            // Log error email saja, jangan gagalkan proses DB
            Log::error('Gagal kirim email notifikasi: ' . $e->getMessage());
        }

        return $notification;
    }

    // =========================================================================
    // EVENT HANDLERS
    // =========================================================================

    /**
     * Tiket baru dibuat 
     */
    public static function onTicketCreated(Ticket $ticket): void
    {
        // 1. Tentukan Target Role (Priority: Assignee dari Layanan -> Admin Layanan)
        $targetRole = $ticket->current_assignee_role ?? 'admin_layanan';

        // 2. Cari Admin/PJ (Menggunakan helper baru yang support string role)
        $admins = self::getUsersByRole($targetRole);

        $typeLabel = 'Layanan';
        if ($ticket->service_category) {
            $typeLabel = $ticket->service_category->name;
        } elseif ($ticket->type === 'zoom_meeting') {
            $typeLabel = 'Zoom Meeting';
        } elseif ($ticket->type === 'perbaikan') {
            $typeLabel = 'Perbaikan Aset';
        }

        // Notif ke Admin
        foreach ($admins as $admin) {
            self::createNotificationWithEmail([
                'user_id'        => $admin->id,
                'title'          => "Tiket Masuk: {$typeLabel}",
                'message'        => "#{$ticket->ticket_number} - {$ticket->title}",
                'type'           => 'info',
                'reference_type' => 'ticket',
                'reference_id'   => $ticket->id,
            ]);
        }

        // 3. Notif ke Pembuat Tiket (User)
        if ($ticket->user_id) {
            self::createNotificationWithEmail([
                'user_id'        => $ticket->user_id,
                'title'          => 'Tiket Berhasil Dibuat',
                'message'        => "Tiket #{$ticket->ticket_number} berhasil dikirim dan menunggu review.",
                'type'           => 'success',
                'reference_type' => 'ticket',
                'reference_id'   => $ticket->id,
            ]);
        }
    }

    /**
     * Tiket di-assign ke teknisi
     */
    public static function onTicketAssigned(Ticket $ticket): void
    {
        // Notif ke Teknisi
        if ($ticket->assigned_to) {
            self::createNotificationWithEmail([
                'user_id'        => $ticket->assigned_to,
                'title'          => 'Tugas Baru',
                'message'        => "#{$ticket->ticket_number} ditugaskan kepada Anda.",
                'type'           => 'info',
                'reference_type' => 'ticket',
                'reference_id'   => $ticket->id,
            ]);
        }

        // Notif ke Pelapor
        if ($ticket->user_id) {
            self::createNotificationWithEmail([
                'user_id'        => $ticket->user_id,
                'title'          => 'Tiket Diproses',
                'message'        => "Tiket #{$ticket->ticket_number} kini ditangani oleh petugas.",
                'type'           => 'info',
                'reference_type' => 'ticket',
                'reference_id'   => $ticket->id,
            ]);
        }
    }

    /**
     * Status tiket berubah
     */
    public static function onStatusChanged(Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus) return;

        $statusLabels = [
            'in_progress' => 'sedang dikerjakan',
            'on_hold' => 'ditunda sementara',
            'waiting_for_submitter' => 'menunggu konfirmasi Anda',
            'closed' => 'telah selesai',
            'resolved' => 'telah diselesaikan petugas',
            'approved' => 'disetujui',
            'rejected' => 'ditolak',
            'assigned' => 'diterima petugas'
        ];

        $label = $statusLabels[$newStatus] ?? str_replace('_', ' ', $newStatus);

        $notifType = 'info';
        if ($newStatus === 'rejected') $notifType = 'error';
        if (in_array($newStatus, ['closed', 'resolved', 'approved'])) $notifType = 'success';
        if ($newStatus === 'waiting_for_submitter' || $newStatus === 'on_hold') $notifType = 'warning';

        // Notif ke Pelapor
        if ($ticket->user_id) {
            self::createNotificationWithEmail([
                'user_id'        => $ticket->user_id,
                'title'          => 'Update Status Tiket',
                'message'        => "Tiket #{$ticket->ticket_number} saat ini {$label}.",
                'type'           => $notifType,
                'reference_type' => 'ticket',
                'reference_id'   => $ticket->id,
            ]);
        }

        // [Legacy] Jika on_hold (Perbaikan), notif ke Admin Penyedia
        if ($ticket->type === 'perbaikan' && $newStatus === 'on_hold') {
            $admins = self::getUsersByRole('admin_penyedia');
            foreach ($admins as $admin) {
                self::createNotificationWithEmail([
                    'user_id'        => $admin->id,
                    'title'          => 'Tiket Menunggu (On Hold)',
                    'message'        => "#{$ticket->ticket_number} butuh perhatian (Sparepart/Vendor).",
                    'type'           => 'warning',
                    'reference_type' => 'ticket',
                    'reference_id'   => $ticket->id,
                ]);
            }
        }
    }

    /**
     * Tiket selesai
     */
    public static function onTicketClosed(Ticket $ticket): void
    {
        if ($ticket->user_id) {
            self::createNotificationWithEmail([
                'user_id'        => $ticket->user_id,
                'title'          => 'Tiket Ditutup',
                'message'        => "Tiket #{$ticket->ticket_number} telah selesai sepenuhnya.",
                'type'           => 'success',
                'reference_type' => 'ticket',
                'reference_id'   => $ticket->id,
            ]);
        }
    }

    // --- ZOOM ---

    public static function onZoomApproved(Ticket $ticket): void
    {
        if ($ticket->user_id) {
            self::createNotificationWithEmail([
                'user_id'        => $ticket->user_id,
                'title'          => 'Booking Zoom Disetujui',
                'message'        => "Jadwal #{$ticket->ticket_number} telah dikonfirmasi. Cek detail tiket.",
                'type'           => 'success',
                'reference_type' => 'ticket',
                'reference_id'   => $ticket->id,
            ]);
        }
    }

    public static function onZoomRejected(Ticket $ticket, string $reason): void
    {
        if ($ticket->user_id) {
            self::createNotificationWithEmail([
                'user_id'        => $ticket->user_id,
                'title'          => 'Booking Zoom Ditolak',
                'message'        => "#{$ticket->ticket_number} ditolak. Alasan: {$reason}",
                'type'           => 'error',
                'reference_type' => 'ticket',
                'reference_id'   => $ticket->id,
            ]);
        }
    }

    // --- WORK ORDERS ---

    public static function onWorkOrderCreated(Ticket $ticket, string $woType): void
    {
        $admins = self::getUsersByRole('admin_penyedia');
        $typeLabels = ['sparepart' => 'Sparepart', 'vendor' => 'Jasa Vendor', 'license' => 'Lisensi'];
        $label = $typeLabels[$woType] ?? $woType;

        foreach ($admins as $admin) {
            self::createNotificationWithEmail([
                'user_id'        => $admin->id,
                'title'          => "Permintaan {$label} Baru",
                'message'        => "Tiket #{$ticket->ticket_number} membutuhkan pengadaan {$label}.",
                'type'           => 'info',
                'reference_type' => 'ticket',
                'reference_id'   => $ticket->id,
            ]);
        }
    }

    public static function onWorkOrderStatusChanged(Ticket $ticket, int $technicianId, string $oldStatus, string $newStatus): void
    {
        $statusLabels = [
            'requested' => 'Menunggu Proses',
            'in_procurement' => 'Dalam Tahap Pengadaan',
            'completed' => 'Sudah Selesai',
            'unsuccessful' => 'Tidak Berhasil',
        ];
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;

        // Notif ke teknisi
        self::createNotificationWithEmail([
            'user_id'        => $technicianId,
            'title'          => 'Update Pengadaan',
            'message'        => "Status pengadaan tiket #{$ticket->ticket_number}: {$newLabel}",
            'type'           => $newStatus === 'completed' ? 'success' : ($newStatus === 'unsuccessful' ? 'error' : 'info'),
            'reference_type' => 'ticket',
            'reference_id'   => $ticket->id,
        ]);
    }

    public static function onAllWorkOrdersCompleted(Ticket $ticket, int $technicianId): void
    {
        self::createNotificationWithEmail([
            'user_id'        => $technicianId,
            'title'          => 'Pengadaan Selesai',
            'message'        => "Semua item tiket #{$ticket->ticket_number} tersedia. Silakan lanjutkan.",
            'type'           => 'success',
            'reference_type' => 'ticket',
            'reference_id'   => $ticket->id,
        ]);
    }

    // --- DIAGNOSIS & COMMENTS ---

    public static function onDiagnosisCreated(Ticket $ticket, string $repairType): void
    {
        $repairTypeLabels = [
            'direct_repair' => 'dapat diperbaiki langsung',
            'need_sparepart' => 'membutuhkan sparepart',
            'need_vendor' => 'membutuhkan vendor',
            'unrepairable' => 'tidak dapat diperbaiki (rusak berat)',
        ];
        $label = $repairTypeLabels[$repairType] ?? $repairType;

        // Notif Pelapor
        if ($ticket->user_id) {
            self::createNotificationWithEmail([
                'user_id'        => $ticket->user_id,
                'title'          => 'Hasil Diagnosis',
                'message'        => "Teknisi memeriksa tiket #{$ticket->ticket_number}: {$label}",
                'type'           => $repairType === 'unrepairable' ? 'warning' : 'info',
                'reference_type' => 'ticket',
                'reference_id'   => $ticket->id,
            ]);
        }
    }

    public static function onCommentCreated(Ticket $ticket, int $commenterId, ?int $parentCommenterId = null): void
    {
        $userIdsToNotify = [];

        // 1. Reply -> Penulis asli
        if ($parentCommenterId && $parentCommenterId !== $commenterId) {
            $userIdsToNotify[] = $parentCommenterId;
        }
        // 2. Pelapor (jika bukan dia yang nulis)
        if ($ticket->user_id && $ticket->user_id !== $commenterId) {
            $userIdsToNotify[] = $ticket->user_id;
        }
        // 3. PJ (jika bukan dia yang nulis)
        if ($ticket->assigned_to && $ticket->assigned_to !== $commenterId) {
            $userIdsToNotify[] = $ticket->assigned_to;
        }

        $userIdsToNotify = array_unique($userIdsToNotify);

        foreach ($userIdsToNotify as $userId) {
            self::createNotificationWithEmail([
                'user_id'        => $userId,
                'title'          => 'Diskusi Baru',
                'message'        => "Pesan baru pada tiket #{$ticket->ticket_number}",
                'type'           => 'info',
                'reference_type' => 'ticket',
                'reference_id'   => $ticket->id,
            ]);
        }
    }
}
