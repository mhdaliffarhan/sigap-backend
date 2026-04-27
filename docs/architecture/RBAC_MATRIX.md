# Role & Permission Matrix

Aplikasi SIGAP mengimplementasikan Role-Based Access Control (RBAC) dengan pemetaan hak akses sebagai berikut:

| Aksi / Modul | Superadmin | Admin Layanan | Admin Teknis | Admin Pengadaan | Pegawai |
|---|:---:|:---:|:---:|:---:|:---:|
| **Login via SSO** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Manajemen Users & Roles** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Konfigurasi Form Layanan (Dinamis)**| ✅ | ✅ | ❌ | ❌ | ❌ |
| **Buat Tiket Layanan** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Lihat Semua Tiket** | ✅ | ✅ | Khusus Layanannya | ❌ | Tiket Milik Sendiri |
| **Diagnosis/Proses Tiket** | ✅ | ✅ | Khusus Layanannya | ❌ | ❌ |
| **Eskalasi / Transfer Tiket** | ✅ | ✅ | Khusus Layanannya | ❌ | ❌ |
| **Beri Feedback / Penilaian** | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Sistem Pengadaan BMN** | ✅ | ❌ | ❌ | ✅ | ❌ |

> **Catatan:**  
> Matrix di atas disesuaikan dengan transisi aplikasi menjadi sistem manajemen tiket umum. Khusus role `Admin Pengadaan`, aksesnya difokuskan pada pelayanan tiket yang berhubungan dengan resource pengadaan/BMN.
