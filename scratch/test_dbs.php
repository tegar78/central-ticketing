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

try {
    $c2 = DB::connection('test_bill2')->table('customer')->count();
    echo "bill2-dsnet customer count: " . $c2 . "\n";
} catch (\Exception $e) {
    echo "bill2-dsnet error: " . $e->getMessage() . "\n";
}

try {
    $c3 = DB::connection('test_bill3')->table('customer')->count();
    echo "bill3-gyh customer count: " . $c3 . "\n";
} catch (\Exception $e) {
    echo "bill3-gyh error: " . $e->getMessage() . "\n";
}
