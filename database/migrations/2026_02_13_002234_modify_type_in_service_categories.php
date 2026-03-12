<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // OPSI TERBAIK: Ubah jadi STRING agar fleksibel (bisa 'booking', 'repair', 'general', dll)
        // Kita gunakan DB::statement karena mengubah tipe ENUM via Schema Builder kadang bermasalah di Doctrine
        Schema::table('service_categories', function (Blueprint $table) {
            // Ubah tipe kolom jadi string
            $table->string('type', 50)->default('general')->change();
        });
    }

    public function down(): void
    {
        // Kembalikan ke ENUM terbatas (jika rollback)
        Schema::table('service_categories', function (Blueprint $table) {
            // Note: Rollback ENUM kadang tricky, tapi logic-nya begini
            $table->enum('type', ['booking', 'service', 'request'])->change();
        });
    }
};
