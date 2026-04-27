# Project Guide & Conventions

## Folder Structure

### Backend (Laravel 12): `sigap-backend`
```
app/
├── Http/
│   ├── Controllers/   # Controller Utama (API endpoints)
│   ├── Requests/      # Form Requests untuk Validasi
│   └── Resources/     # API Resources (JSON serialization)
├── Models/            # Eloquent Models
├── Services/          # (Optional) Business logic komplex
└── bootstrap/
    └── app.php        # Register routes, middlewares (Laravel 12 spec)
routes/
├── api.php            # Rute JSON API Sanctum Protected
└── web.php            # (Minimal use)
```

### Frontend (React Vite): `sigap-frontend`
```
src/
├── api/               # Fungsi untuk Axios client ke Laravel API
├── components/        # Reusable UI components (Radix UI / Tailwind)
├── views/             # Page-level components
├── router/            # React Router definitions
├── stores/            # State management (Context/Zustand jika perlu)
└── utils/             # Helpers
```

## Naming Conventions
### Backend
- **Files**: `PascalCase.php` (Classes) / `snake_case` (DB)
- **Functions/Variables**: `camelCase` / `snake_case` sesuaikan standar Laravel
- **API Routes**: `kebab-case`
- **Database Tables**: `snake_case` (plural), kolom `snake_case`

### Frontend
- **Files**: `PascalCase.tsx` / `.jsx` (components), `camelCase.ts` / `.js` (utils) 
- **Components**: `PascalCase`
- **Variables/Functions**: `camelCase`
- **CSS Classes**: Tailwind utilities (`kebab-case`)

## Code Patterns

### Backend API Pattern
Sebisa mungkin kembalikan JSON dengan format standardized dan gunakan Eloquent Resources per-model jika dirasa output perlu ditransformasi. Validasi terpisah dalam Form Request.

### Frontend API Pattern
Semua call backend API dieksekusi di layer `src/api/` sehingga views tidak perlu pusing mengurus `axios` instance langsung. Biasakan menangkap error secara graceful.

### Frontend UI & Aesthetics Standards
Agar hasil pembangunan antarmuka konsisten dan profesional, patuhi tiga pilar utama berikut dalam implementasi komponen UI (*Milestone 1, Phase 3 dst.*):
1. **Animatif**: Manfaatkan library animasi (cth: Framer Motion/Tailwind Animate) untuk transisi antar halaman (page transition), kemunculan modal, *hover effect*, skeleton loader, dan _micro-interactions_ lainnya agar UI tidak kaku.
2. **Responsif**: Layout fleksibel menggunakan Tailwind utilities grid/flex dan breakpoints (`sm:`, `md:`, `lg:`, `xl:`), pastikan dapat mengakomodasi ruang tampilan sekecil *mobile-device* maupun selebar monitor *desktop*.
3. **Style Konsisten**: Jangan menggunakan pewarnaan / komponen ad-hoc sembarangan. Gunakan Radix UI primitives yang dibalut konfigurasi kelas CSS Tailwind bawaan agar spacing, borderradius, color palette (contoh: slate/blue UI standard) harmonis dari ujung ke ujung aplikasi.
