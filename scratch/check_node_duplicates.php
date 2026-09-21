<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Within node 1
$dupNode1 = DB::select("
    SELECT no_services, COUNT(*) as cnt
    FROM customers
    WHERE billing_node_id = 1
    GROUP BY no_services
    HAVING cnt > 1
");
echo "Duplicates within node 1 alone: " . count($dupNode1) . "\n";

// Within node 4
$dupNode4 = DB::select("
    SELECT no_services, COUNT(*) as cnt
    FROM customers
    WHERE billing_node_id = 4
    GROUP BY no_services
    HAVING cnt > 1
");
echo "Duplicates within node 4 alone: " . count($dupNode4) . "\n";

// Same coords within node 1 alone
$dupCoordsNode1 = DB::select("
    SELECT latitude, longitude, COUNT(*) as cnt, GROUP_CONCAT(name, ' | ') as names, GROUP_CONCAT(no_services, ', ') as nos
    FROM customers
    WHERE billing_node_id = 1 AND latitude IS NOT NULL AND latitude != '' AND latitude != '0'
    GROUP BY latitude, longitude
    HAVING cnt > 1
");
echo "Same coordinates within node 1 alone: " . count($dupCoordsNode1) . "\n";
if (!empty($dupCoordsNode1)) {
    foreach (array_slice($dupCoordsNode1, 0, 5) as $d) {
        echo " - ({$d->latitude}, {$d->longitude}): {$d->cnt} customers: {$d->nos} ({$d->names})\n";
    }
}
