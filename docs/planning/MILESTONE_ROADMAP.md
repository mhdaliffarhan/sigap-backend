# Milestone Roadmap

## Milestone 1 — Foundation & Dynamic Services
**Goal**: Menyiapkan struktur dasar aplikasi dan fitur pembuatan layanan dinamis
**Estimasi**: 1-2 minggu pertama (Target April 2026)

### Deliverables
- [ ] Database schema & migrations (Review existing models)
- [ ] Authentication system (Integrasi SSO Pintu)
- [ ] ServiceCategory API (CRUD form schema dinamis)
- [ ] Project structure & boilerplate Frontend (React + Vite)

### Success Criteria
- User bisa login dengan SSO
- Superadmin/Admin Layanan bisa membuat dan mengedit kategori layanan beserta custom form schema-nya.

---

## Milestone 2 — Ticketing & Workflow
**Goal**: Pengguna dapat mengajukan tiket berdasarkan layanan dinamis dan Admin memprosesnya.
**Estimasi**: 1-2 minggu
**Depends on**: Milestone 1

### Deliverables
- [ ] Ticket submission (berdasarkan form schema dinamis)
- [ ] Dashboard List Tiket (sesuai Role)
- [ ] Workflow Transition (Ubah status tiket: pending, progress, solved, dll)
- [ ] Timeline & Comments
- [ ] Notifikasi

### Success Criteria
- User bisa buat tiket.
- Admin Teknis menerima notifikasi dan dapat merespons / mengupdate status tiket.
- Histori tiket terlihat dengan jelas.

---

## Milestone 3 — Polish, Legacy Modules & Deployment
**Goal**: Pengembalian fungsionalitas spesifik (BMN/Zoom) ke dalam workflow, testing, dan rilis.
**Estimasi**: 1 minggu
**Depends on**: Milestone 2

### Deliverables
- [ ] Integrasi legacy BMN dan ZoomAccount jika masih dibutuhkan atau mapping ke form dinamis.
- [ ] Pengisian Ulasan/Feedback
- [ ] UI/UX Polish di React Frontend
- [ ] Deployment (HestiaCP / VPS BPS)

### Success Criteria
- Flow tiket lengkap dari awal s.d. rating.
- Aplikasi tayang di production enviroment BPS NTB.
