<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Ubah kolom 'type' dari ENUM menjadi STRING (VARCHAR)
            // agar bisa menampung slug dinamis (contoh: 'peminjaman-kendaraan')
            $table->string('type', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Kembalikan ke ENUM jika rollback (Opsional, hati-hati data bisa hilang)
            // $table->enum('type', ['perbaikan', 'zoom_meeting'])->change();
        });
    }
};
