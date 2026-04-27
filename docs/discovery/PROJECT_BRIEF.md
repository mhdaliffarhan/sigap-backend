# Project Brief: SIGAP

## Overview
- **Nama Project**: SIGAP (Sistem Layanan Internal Terpadu)
- **Tujuan**: Transformasi aplikasi internal BPS NTB menjadi sistem terpadu yang dinamis untuk menambah dan mengurangi layanan tanpa koding.
- **Target Users**: 
  - Superadmin
  - Admin Layanan
  - Admin Teknis (admin khusus tiap layanan)
  - Admin Pengadaan
  - User
- **Timeline**: Target selesai bulan ini (April 2026).

## Tech Stack
- **Backend**: Laravel 12 (PHP 8.2+), Laravel Sanctum
- **Frontend**: React 19 + Vite + Tailwind CSS 4 + Radix UI
- **Database**: MySQL
- **Deployment**: Mengikuti alur deployment yang sudah ada.

## Core Features (MVP)
1. **Ticketing System Internal** dengan 3 kategori workflow utama:
   - **Peminjaman** (Urgent): Booking/reservasi (Zoom, aula, mobil) dengan kalender interaktif & database aset.
   - **Perbaikan BMN** (Urgent): Tiket perbaikan yang terhubung ke database BMN spesifik dan ditangani oleh Admin Pengadaan.
   - **Jasa** (Menyusul): Workflow untuk permohonan jasa yang alurnya akan didefinisikan kemudian.
2. Pendefinisian jenis layanan secara dinamis (custom form).
3. Manajemen Tiket dan Workflow progress yang komprehensif.

## Integrasi Eksternal
- SSO BPS NTB (Aplikasi Pintu)

## Catatan Khusus
- Reference UI menggunakan gaya desain dan komponen yang telah ada pada repository saat ini (React Radix UI / Tailwind).
