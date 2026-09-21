<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

config([
    'database.connections.test_bill2' => [
        'driver'    => 'mysql',
        'host'      => '127.0.0.2',
        'port'      => '3306',
        'database'  => 'bill2-dsnet',
        'username'  => 'root',
        'password'  => '',
        'charset'   => 'utf8',
        'collation' => 'utf8_general_ci',
    ],
    'database.connections.test_bill3' => [
        'driver'    => 'mysql',
        'host'      => '127.0.0.2',
        'port'      => '3306',
        'database'  => 'bill3-gyh',
        'username'  => 'root',
        'password'  => '',
        'charset'   => 'utf8',
        'collation' => 'utf8_general_ci',
    ]
]);

$cust2 = DB::connection('test_bill2')->table('customer')->pluck('no_services')->toArray();
$cust3 = DB::connection('test_bill3')->table('customer')->pluck('no_services')->toArray();

$common = array_intersect($cust2, $cust3);
echo "Common no_services between bill2-dsnet and bill3-gyh: " . count($common) . "\n";
if (!empty($common)) {
    echo "Sample common: " . implode(', ', array_slice($common, 0, 10)) . "\n";
}
