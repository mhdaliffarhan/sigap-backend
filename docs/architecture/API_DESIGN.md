# API Structure Design

Struktur API backend berbasis REST memanfaatkan JSON API dan proteksi Sanctum.

## Auth & Profile
- `POST /api/auth/login-sso` (Meneruskan logic SSO Aplikasi Pintu)
- `POST /api/auth/logout`
- `GET  /api/auth/me`

## Service & Form Configuration
- `GET    /api/service-categories` (List layanan dinamis)
- `POST   /api/service-categories` (Superadmin/Admin Layanan)
- `GET    /api/service-categories/{slug}`
- `PUT    /api/service-categories/{slug}`
- `DELETE /api/service-categories/{slug}`

## Ticketing System
- `GET    /api/tickets` (Disesuaikan berdasarkan scope user login)
- `POST   /api/tickets` (Create issue by User)
- `GET    /api/tickets/{id}`
- `PUT    /api/tickets/{id}` (Update info dasar)
- `DELETE /api/tickets/{id}`

### Ticket Actions
- `POST /api/tickets/{id}/transition` (Update workflow status)
- `POST /api/tickets/{id}/diagnosis` (Input diagnosis teknis)
- `POST /api/tickets/{id}/comments` (Tambah komentar/tanya jawab)
- `POST /api/tickets/{id}/transfer` (Eskalasi tiket ke admin lain)
- `POST /api/tickets/{id}/feedback` (Penilaian dari timbulnya tiket)

## Legacy/Specific Endpoints (Optional for MVP)
- `GET  /api/assets`
- `GET  /api/zoom-accounts`
