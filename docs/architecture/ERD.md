# Entity Relationship Diagram (ERD)

Berdasarkan analisis model yang ada pada aplikasi SIGAP, berikut adalah entitas yang digunakan untuk mendukung sistem yang dinamis:

## Entitas Utama

### Auth & RBAC
- **User**: Menyimpan data pegawai (dimigrasi untuk terkoneksi/sinkron dengan SSO BPS).
- **Role**: Menyimpan definisi peran (Superadmin, Admin Layanan, Admin Teknis, Admin Pengadaan, User).

### Core Services & Ticketing
- **ServiceCategory**: Menyimpan definisi layanan dinamis, termasuk tipe form dan workflow spesifik.
- **Ticket**: Entri utama untuk permintaan layanan atau pelaporan masalah. Mengacu pada form dinamis di ServiceCategory.
- **WorkOrder**: Surat perintah/penugasan pengerjaan tiket jika dibutuhkan spesifik untuk Admin.

### Ticket Lifecycle
- **WorkflowStatus**: Status dari proses tiket (Pending, In Progress, Solved, dll).
- **WorkflowTransition**: Aturan pergantian status berdasarkan previledge.
- **TicketDiagnosis**: Diagnosa masalah/kebutuhan yang dilakukan admin.
- **TicketTransfer**: Riwayat pemindahan tiket (eskalasi/delegasi) antar Admin.
- **Timeline**: Catatan riwayat aktivitas form tiket (log status dan aksi).
- **Comment**: Diskusi yang ada pada tiket antar User dan Admin.
- **TicketFeedback**: Ulasan atau kepuasan pengguna setelah tiket diselesaikan.

### Pendukung & Notifikasi
- **Notification**: Notifikasi terkait update tiket (email/in-app).
- **AuditLog**: Jejak rekam atau activity log dari keseluruhan sistem.

### Modul Layanan Khusus (Peminjaman & Perbaikan BMN)
- **Asset / Resource**: Entitas aset fisik (mobil, ruangan) untuk alur layanan **Peminjaman** yang butuh kalender interaktif.
- **ZoomAccount**: Data akun spesifik untuk form peminjaman virtual Zoom.
- **Database BMN**: Referensi inventaris (mungkin terpisah/terhubung) untuk layanan **Perbaikan BMN** yang ditangani oleh Admin Pengadaan.

---

> **Catatan untuk User:**\n> Apakah ada entitas di atas yang perlu diubah/dihapus dalam proses transisi MVP saat ini, atau adakah modul lain yang belum masuk?
