# Milestone 1 Detail Plan

## Phase 1 — Database & Auth Foundation
### Tasks
- [ ] Review dan fix tabel bawaan/lama yang perlu disesuaikan.
- [ ] Implementasi logic Auth via SSO Aplikasi Pintu.
- [ ] Sesuaikan seeder untuk Superadmin dan Admin awal.

### Files Target
- `app/Models/User.php`
- `app/Http/Controllers/AuthController.php`
- `database/migrations/*`

### Checkpoints
- [ ] Migration berjalan tanpa error.
- [ ] Login via SSO return Sanctum token dan Role benar.

## Phase 2 — Service Definition API (Backend)
### Tasks
- [ ] Core CRUD referensi layanan (`ServiceCategory`)
- [ ] Validasi stuktur JSON `form_schema`

### Files Target
- `app/Http/Controllers/ServiceCategoryController.php`
- `app/Models/ServiceCategory.php`

### Checkpoints
- [ ] Endpoint `GET`, `POST`, `PUT`, `DELETE` untuk service categories berfungsi via API client.

## Phase 3 — Frontend Setup & Basic View
### Tasks
- [ ] Setup folder React Vite (cek config).
- [ ] Login page UI & call API SSO.
- [ ] Layout dashboard dan Navbar sesuai Role.
- [ ] Halaman manajemen ServiceCategory.

### Files Target
- `c:/laragon/www/sigap-frontend/src/views/`
- `c:/laragon/www/sigap-frontend/src/router/`

### Checkpoints
- [ ] Login redirect ke dashboard.
- [ ] Admin bisa tambah ServiceCategory dinamis.
