<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Menampung data form dinamis (Tujuan, Alasan, Jumlah Penumpang, dll)
            $table->json('ticket_data')->nullable()->after('description');

            // Opsional: Relasi ke Service Category (Agar tau ini tiket layanan apa)
            // Jika belum ada, tambahkan ini. Jika sudah, skip.
            if (!Schema::hasColumn('tickets', 'service_category_id')) {
                $table->foreignUuid('service_category_id')->nullable()->constrained('service_categories');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('ticket_data');
            // $table->dropForeign(['service_category_id']); // Hati-hati drop foreign
            // $table->dropColumn('service_category_id');
        });
    }
};
