<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\ServiceCategory;

$cat = ServiceCategory::where('slug', 'perbaikan-bmn')->first();
echo "CATEGORY: " . $cat->name . "\n";
echo "FORM SCHEMA:\n";
echo json_encode($cat->form_schema, JSON_PRETTY_PRINT);
echo "\n";
