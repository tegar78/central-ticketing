<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\BillingInstance;
use Illuminate\Support\Facades\DB;

echo "Total customers: " . Customer::count() . "\n";

$byNode = DB::select("
    SELECT billing_node_id, COUNT(*) as cnt
    FROM customers
    GROUP BY billing_node_id
");
echo "Breakdown by billing_node_id:\n";
foreach ($byNode as $b) {
    $inst = BillingInstance::find($b->billing_node_id);
    $instName = $inst ? $inst->instance_name . " ({$inst->instance_code})" : "NOT FOUND";
    echo " - billing_node_id {$b->billing_node_id} ({$instName}): {$b->cnt} customers\n";
}

// Check if duplicates are across different billing_node_id or within the same billing_node_id
$dupCheck = DB::select("
    SELECT no_services, GROUP_CONCAT(id) as ids, GROUP_CONCAT(billing_node_id) as node_ids, GROUP_CONCAT(name, ' | ') as names, COUNT(*) as cnt
    FROM customers
    GROUP BY no_services
    HAVING cnt > 1
    LIMIT 10
");
echo "\nSample duplicate no_services details:\n";
foreach ($dupCheck as $d) {
    echo " - No: {$d->no_services} | IDs: {$d->ids} | Node IDs: {$d->node_ids} | Names: {$d->names}\n";
}
