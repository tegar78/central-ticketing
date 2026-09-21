<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BillingInstance;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

$instances = BillingInstance::all();
foreach ($instances as $inst) {
    echo "=== Instance {$inst->id}: [{$inst->tenant_code}] {$inst->name} ===\n";
    echo "Domain URL: {$inst->domain_url}\n";
    echo "DB: {$inst->db_database} @ {$inst->db_host}\n";
    $custCount = Customer::where('billing_node_id', $inst->id)->count();
    $tktCount = Ticket::where('billing_instance_id', $inst->id)->count();
    echo "Customers: {$custCount} | Tickets: {$tktCount}\n\n";
}

// Compare customers in instance 1 vs instance 4
$overlap = DB::select("
    SELECT COUNT(*) as overlap_count
    FROM customers c1
    JOIN customers c2 ON c1.no_services = c2.no_services
    WHERE c1.billing_node_id = 1 AND c2.billing_node_id = 4
");
echo "Overlap no_services between instance 1 and 4: " . ($overlap[0]->overlap_count ?? 0) . "\n";

// Check if any tickets belong to instance 4
$t4 = Ticket::where('billing_instance_id', 4)->get();
echo "Tickets for instance 4: " . $t4->count() . "\n";
foreach ($t4 as $t) {
    echo " - Ticket: {$t->ticket_number} | Subject: {$t->subject} | Customer: {$t->customer_name}\n";
}

// Check if any tickets belong to instance 1
$t1 = Ticket::where('billing_instance_id', 1)->get();
echo "Tickets for instance 1: " . $t1->count() . "\n";
foreach ($t1 as $t) {
    echo " - Ticket: {$t->ticket_number} | Subject: {$t->subject} | Customer: {$t->customer_name}\n";
}
