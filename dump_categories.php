<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

foreach(\App\Models\ServiceCategory::all() as $c) {
    echo "SLUG: [{$c->slug}] NAME: [{$c->name}] SCHEMA: " . json_encode($c->form_schema) . PHP_EOL;
}
