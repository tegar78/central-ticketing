<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$instances = DB::select("SELECT * FROM billing_instances");
echo json_encode($instances, JSON_PRETTY_PRINT) . "\n";
