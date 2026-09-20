<?php
// Inspect Central Ticket System DB
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;
$t = Ticket::where('customer_name', 'like', '%Soleha%')->first();
if ($t) {
    echo "CENTRAL DB TICKET:\n";
    echo "  ID: {$t->id}\n";
    echo "  Ticket Number: {$t->ticket_number}\n";
    echo "  Remote Ticket ID: '{$t->remote_ticket_id}'\n";
    echo "  Status: {$t->status}\n";
    echo "  Billing Instance ID: {$t->billing_instance_id}\n";
    echo "  Callback URL: " . ($t->billingInstance->callback_url ?? 'NONE') . "\n";
} else {
    echo "Central ticket Soleha not found!\n";
}
