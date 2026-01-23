<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update Service Categories (Untuk Konfigurasi Layanan)
        Schema::table('service_categories', function (Blueprint $table) {
            // Role default yang menangani layanan ini (misal: 'teknisi', 'supir')
            $table->string('handling_role')->default('admin_layanan')->after('type');

            // Schema Form untuk PJ saat menyelesaikan tiket (Output)
            $table->json('action_schema')->nullable()->after('form_schema');

            // Penanda apakah layanan ini berbasis resource/booking (butuh kalender)
            $table->boolean('is_resource_based')->default(false)->after('is_active');
        });

        // 2. Update Tickets (Untuk Menyimpan Data Eksekusi)
        Schema::table('tickets', function (Blueprint $table) {
            // Data hasil kerja PJ (misal: Foto bukti, Sparepart yg diganti)
            $table->json('action_data')->nullable()->after('ticket_data');

            // Penanda jika tiket sedang "dioper" ke unit lain (eskalasi)
            $table->boolean('is_escalated')->default(false)->after('status');
        });

        // 3. Tabel Baru: Riwayat Transfer / Delegasi (Untuk Tracking detail)
        Schema::create('ticket_transfers', function (Blueprint $table) {
            $table->id();

            // GANTI INI: Dari foreignUuid menjadi foreignId
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');

            $table->unsignedBigInteger('from_user_id');
            $table->string('from_role');

            $table->string('to_role');
            $table->unsignedBigInteger('to_user_id')->nullable();

            $table->text('notes')->nullable();
            $table->string('status')->default('pending');

            $table->timestamps();

            $table->foreign('from_user_id')->references('id')->on('users');
            $table->foreign('to_user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_transfers');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['action_data', 'is_escalated']);
        });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn(['handling_role', 'action_schema', 'is_resource_based']);
        });
    }
};
