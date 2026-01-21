<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Status (Daftar semua kemungkinan status)
        Schema::create('workflow_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // Contoh: "submitted", "approved", "waiting_vendor"
            $table->string('label'); // Tampilan di layar: "Menunggu Persetujuan"
            $table->string('color')->default('gray'); // Warna badge (blue, green, red)
            $table->boolean('is_end_state')->default(false); // Apakah ini status final (selesai)?
            $table->timestamps();
        });

        // 2. Transisi Workflow (Aturan main tombol aksi)
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Transisi ini berlaku untuk layanan apa?
            $table->foreignUuid('service_category_id')
                ->constrained('service_categories')
                ->onDelete('cascade');

            // Tombol muncul saat tiket ada di status mana?
            $table->foreignUuid('from_status_id')
                ->constrained('workflow_statuses');

            // Kalau tombol diklik, status berubah jadi apa?
            $table->foreignUuid('to_status_id')
                ->constrained('workflow_statuses');

            $table->string('action_label'); // Tulisan di tombol (Contoh: "Eskalasi ke Vendor")

            // Siapa yang boleh klik tombol ini? (Role)
            $table->string('trigger_role'); // Contoh: "admin_layanan"

            // Setelah diklik, tiket ini jadi tanggung jawab siapa?
            $table->string('target_assignee_role')->nullable(); // Contoh: "admin_vendor"

            // Form tambahan yang harus diisi saat tombol diklik (Alasan, Biaya, dll)
            $table->json('required_form_schema')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
        Schema::dropIfExists('workflow_statuses');
    }
};
