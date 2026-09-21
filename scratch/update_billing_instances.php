<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BillingInstance;

// 1. Update BILL-001 to point to bill2-dsnet
$bill001 = BillingInstance::where('tenant_code', 'BILL-001')->first();
if ($bill001) {
    $bill001->update([
        'db_host'     => '127.0.0.2',
        'db_port'     => '3306',
        'db_database' => 'bill2-dsnet',
        'db_username' => 'root',
        'db_password' => '',
    ]);
    echo "Updated BILL-001 db_database to bill2-dsnet\n";
}

// 2. Update BILL-GAYUH to point to bill3-gyh
$billGayuh = BillingInstance::where('tenant_code', 'BILL-GAYUH')->first();
if ($billGayuh) {
    $billGayuh->update([
        'db_host'     => '127.0.0.2',
        'db_port'     => '3306',
        'db_database' => 'bill3-gyh',
        'db_username' => 'root',
        'db_password' => '',
    ]);
    echo "Updated BILL-GAYUH db_database to bill3-gyh\n";
}
