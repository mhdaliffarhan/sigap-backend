<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ubah kolom 'role' dari ENUM menjadi STRING biasa
            // Agar bisa menerima value dinamis seperti 'admin_ga', 'kepala_gudang', dll
            $table->string('role', 50)->change();

            // Jika Anda menggunakan kolom 'roles' (jamak) untuk multi-role
            // Pastikan tipe datanya JSON atau TEXT, bukan String biasa
            // $table->json('roles')->nullable()->change(); 
        });
    }

    public function down(): void
    {
        // Tidak perlu rollback ke ENUM agar data aman
    }
};
