<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\ServiceCategory;

$category = ServiceCategory::where('slug', 'perbaikan-bmn')->first();
if ($category) {
    $category->update([
        'name' => 'Perbaikan BMN',
        'type' => 'service',
        'icon' => 'tool',
        'description' => 'Layanan perbaikan barang milik negara (elektronik, mebel, dll)',
        'form_schema' => [
            ['name' => 'kode_barang', 'label' => 'Kode Barang BMN', 'type' => 'text', 'required' => true, 'placeholder' => 'Contoh: 3.10.01.02.001'],
            ['name' => 'nup', 'label' => 'NUP', 'type' => 'text', 'required' => true, 'placeholder' => 'Contoh: 1'],
            ['name' => 'asset_location', 'label' => 'Lokasi Barang', 'type' => 'text', 'required' => false],
            ['name' => 'severity', 'label' => 'Tingkat Keparahan', 'type' => 'select', 'required' => true, 'options' => [
                ['value' => 'low', 'label' => 'Rendah (Dapat menunggu)'],
                ['value' => 'normal', 'label' => 'Normal'],
                ['value' => 'high', 'label' => 'Tinggi (Butuh segera)'],
                ['value' => 'critical', 'label' => 'Kritis (Mendesak/Layanan Lumpuh)']
            ]]
        ],
        'action_schema' => [
            ['name' => 'diagnosis_notes', 'label' => 'Hasil Diagnosa', 'type' => 'textarea', 'required' => true],
            ['name' => 'repair_status', 'label' => 'Status Perbaikan', 'type' => 'select', 'required' => true, 'options' => [
                ['value' => 'direct_repair', 'label' => 'Selesai Diperbaiki'],
                ['value' => 'need_sparepart', 'label' => 'Butuh Sparepart'],
                ['value' => 'need_vendor', 'label' => 'Butuh Vendor Luar'],
                ['value' => 'unrepairable', 'label' => 'Tidak Dapat Diperbaiki']
            ]],
            ['name' => 'estimasi_hari', 'label' => 'Estimasi Pengerjaan (Hari)', 'type' => 'number', 'required' => false]
        ]
    ]);
    echo "Successfully updated perbaikan-bmn category.\n";
} else {
    // Create it if it doesn't exist
    ServiceCategory::create([
        'name' => 'Perbaikan BMN',
        'slug' => 'perbaikan-bmn',
        'type' => 'service',
        'icon' => 'tool',
        'description' => 'Layanan perbaikan barang milik negara (elektronik, mebel, dll)',
        'form_schema' => [
            ['name' => 'kode_barang', 'label' => 'Kode Barang BMN', 'type' => 'text', 'required' => true],
            ['name' => 'nup', 'label' => 'NUP', 'type' => 'text', 'required' => true],
            ['name' => 'asset_location', 'label' => 'Lokasi Barang', 'type' => 'text', 'required' => false],
            ['name' => 'severity', 'label' => 'Tingkat Keparahan', 'type' => 'select', 'required' => true, 'options' => [
                ['value' => 'low', 'label' => 'Low'],
                ['value' => 'normal', 'label' => 'Normal'],
                ['value' => 'high', 'label' => 'High'],
                ['value' => 'critical', 'label' => 'Critical']
            ]]
        ],
        'is_active' => true
    ]);
    echo "Successfully created perbaikan-bmn category.\n";
}
