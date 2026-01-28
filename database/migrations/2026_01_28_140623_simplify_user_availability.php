<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambahkan status availability di users
        Schema::table('users', function (Blueprint $table) {
            // false = Aktif/Masuk, true = Cuti/Libur
            $table->boolean('is_on_leave')->default(false)->after('role');
        });

        // 2. Hapus tabel leave_logs yang ribet (Clean up)
        Schema::dropIfExists('leave_logs');
    }

    public function down(): void
    {
        // Restore logic (jika rollback)
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_on_leave');
        });

        // (Opsional) Re-create leave_logs structure if needed
    }
};
