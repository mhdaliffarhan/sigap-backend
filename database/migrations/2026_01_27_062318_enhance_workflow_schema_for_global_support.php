<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom description di workflow_statuses
        Schema::table('workflow_statuses', function (Blueprint $table) {
            $table->string('description')->nullable()->after('label');
        });

        // 2. Ubah service_category_id di workflow_transitions jadi BOLEH NULL (untuk Global Workflow)
        Schema::table('workflow_transitions', function (Blueprint $table) {
            // Drop foreign key lama dulu (nama biasanya: tabel_kolom_foreign)
            // Kita coba drop dengan array syntax agar aman
            $table->dropForeign(['service_category_id']);

            // Ubah kolom jadi nullable
            $table->uuid('service_category_id')->nullable()->change();

            // Pasang lagi foreign key
            $table->foreign('service_category_id')
                ->references('id')
                ->on('service_categories')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_transitions', function (Blueprint $table) {
            $table->dropForeign(['service_category_id']);
            // Kembalikan jadi tidak nullable (hati-hati jika ada data null, ini akan error)
            $table->uuid('service_category_id')->nullable(false)->change();
            $table->foreign('service_category_id')->references('id')->on('service_categories')->onDelete('cascade');
        });

        Schema::table('workflow_statuses', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
