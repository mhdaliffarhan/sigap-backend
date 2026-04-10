<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\WorkflowStatus;
use App\Models\ServiceCategory;

$statuses = WorkflowStatus::all()->pluck('id', 'code');
$output = "STATUSES:\n" . print_r($statuses->toArray(), true);

$categories = ServiceCategory::where('slug', 'perbaikan-bmn')->pluck('id', 'slug');
$output .= "\nCATEGORIES:\n" . print_r($categories->toArray(), true);

file_put_contents('debug_output.txt', $output);
echo "Output written to debug_output.txt\n";
