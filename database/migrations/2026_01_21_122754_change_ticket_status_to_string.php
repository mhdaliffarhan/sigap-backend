<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Ubah kolom 'status' dari ENUM menjadi STRING (VARCHAR)
            // Agar bisa menampung status dinamis apa saja (contoh: 'delegated_to_ga')
            $table->string('status', 100)->change();
        });
    }

    public function down(): void
    {
        // Tidak perlu dikembalikan ke ENUM karena akan menyebabkan data hilang
        // jika ada status baru yang tidak sesuai list ENUM lama.
    }
};
