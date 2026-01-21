<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Relasi ke Service Category (Wajib diisi untuk tiket baru)
            // Kita buat nullable dulu agar tidak error pada data lama, tapi nanti harus diisi
            $table->foreignUuid('service_category_id')
                ->nullable()
                ->constrained('service_categories');

            // Relasi ke Resource yang dipilih (Mobil X / Ruangan Y)
            $table->foreignUuid('resource_id')
                ->nullable()
                ->constrained('resources');

            // Tanggal Booking Universal (Menggantikan zoom_date dll)
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();

            // Menyimpan inputan user dari form dinamis dalam format JSON
            $table->json('dynamic_form_data')->nullable();

            // Menandakan "Bola" tiket ini sedang dipegang role mana
            $table->string('current_assignee_role')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['service_category_id']);
            $table->dropForeign(['resource_id']);
            $table->dropColumn([
                'service_category_id',
                'resource_id',
                'start_date',
                'end_date',
                'dynamic_form_data',
                'current_assignee_role'
            ]);
        });
    }
};
