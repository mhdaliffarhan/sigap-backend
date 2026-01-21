<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Kategori Layanan (Otak dari sistem dinamis)
        // Kita gunakan nama baru agar tidak bentrok dengan tabel categories lama jika ada
        Schema::create('service_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Contoh: "Peminjaman Mobil"
            $table->string('slug')->unique(); // Contoh: "peminjaman-mobil" (untuk URL)

            // Tipe layanan: booking (peminjaman), service (perbaikan), request (permintaan barang)
            $table->enum('type', ['booking', 'service', 'request'])->default('service');

            $table->string('icon')->nullable(); // Nama icon untuk frontend (misal: "car", "building")
            $table->text('description')->nullable();

            // JSON Schema: Ini kunci kedinamisannya!
            // Kita simpan struktur form input di sini.
            $table->json('form_schema')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // Agar data tidak hilang permanen saat dihapus
        });

        // 2. Tabel Resources (Inventaris Universal)
        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relasi ke kategori (Resource ini milik layanan apa?)
            $table->foreignUuid('service_category_id')
                ->constrained('service_categories')
                ->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('capacity')->nullable(); 

            // Metadata: Data unik spesifik (Plat nomor, lokasi lantai, dll)
            $table->json('meta_data')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
        Schema::dropIfExists('service_categories');
    }
};
