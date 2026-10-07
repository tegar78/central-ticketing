<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BillingInstance;
use App\Models\Customer;
use App\Models\Odp;

class ListBillingInstancesCommand extends Command
{
    protected $signature = 'billing:list';

    protected $description = 'Tampilkan daftar semua Billing Instance CodeIgniter 3 yang terdaftar di Central Ticket';

    public function handle()
    {
        $instances = BillingInstance::all();

        if ($instances->isEmpty()) {
            $this->warn('Belum ada Billing Instance yang terdaftar.');
            $this->comment('Gunakan: php artisan billing:add untuk menambahkan billing baru.');
            return 0;
        }

        $rows = [];
        foreach ($instances as $inst) {
            $customerCount = Customer::where('billing_node_id', $inst->id)->count();
            $odpCount = Odp::forBillingNode($inst->id)->count();

            $rows[] = [
                $inst->id,
                $inst->tenant_code,
                $inst->name,
                $inst->domain_url,
                $inst->api_key,
                $inst->is_active ? 'Aktif' : 'Nonaktif',
                $customerCount,
                $odpCount,
            ];
        }

        $this->table(
            ['ID', 'Tenant Code', 'Nama', 'Domain URL', 'API Key', 'Status', 'Pelanggan', 'ODP'],
            $rows
        );

        return 0;
    }
}
