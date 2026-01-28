<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Log Cuti / Ketidakhadiran
        Schema::create('leave_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason'); // Sakit, Cuti Tahunan, Izin, Dinas Luar
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved'); // Asumsi approved by default/admin
            $table->timestamps();

            // Index untuk mempercepat pencarian availability
            $table->index(['user_id', 'start_date', 'end_date']);
        });

        // 2. Config Assignment di Kategori Layanan
        Schema::table('service_categories', function (Blueprint $table) {
            // target_role: Siapa yang mengerjakan layanan ini? (misal: 'staff_ga', 'teknisi_it')
            $table->string('target_role')->nullable()->after('slug');

            // assignment_type: 
            // 'auto' = Sistem pilih user termalas (load terendah)
            // 'manual' = Masuk pool, rebutan/assign manual oleh admin
            // 'direct' = Langsung ke default_assignee (orang spesifik)
            $table->string('assignment_type')->default('auto')->after('target_role');

            // User spesifik jika assignment_type = 'direct'
            $table->foreignId('default_assignee_id')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_logs');
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn(['target_role', 'assignment_type', 'default_assignee_id']);
        });
    }
};
