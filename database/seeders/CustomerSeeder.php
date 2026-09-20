<?php

namespace Database\Seeders;

use App\Models\BillingInstance;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant1 = BillingInstance::where('tenant_code', 'BILL-001')->first() ?? BillingInstance::create([
            'tenant_code' => 'BILL-001',
            'name' => 'PT Gayuh Media Informatika',
            'domain_url' => 'http://bill-gyh.gayuh.net.id.test',
            'api_key' => 'key-bill-001-secret-12345',
            'is_active' => true,
        ]);

        $tenant2 = BillingInstance::where('tenant_code', 'BILL-002')->first() ?? BillingInstance::create([
            'tenant_code' => 'BILL-002',
            'name' => 'Mitra Billing 2',
            'domain_url' => 'http://bill2.gayuh.net.id.test',
            'api_key' => 'key-bill-002-secret-67890',
            'is_active' => true,
        ]);

        $tenant3 = BillingInstance::where('tenant_code', 'BILL-003')->first() ?? BillingInstance::create([
            'tenant_code' => 'BILL-003',
            'name' => 'Mitra Billing 3 (Area Barat)',
            'domain_url' => 'http://bill3.gayuh.net.id.test',
            'api_key' => 'key-bill-003-secret-11223',
            'is_active' => true,
        ]);

        $customersData = [
            // Tenant 1 Customers
            [
                'billing_node_id' => $tenant1->id,
                'remote_customer_id' => 101,
                'no_services' => '220509133210',
                'name' => 'Soleha-(W6)',
                'phone' => '089512345678',
                'address' => 'Jl. Darussalam Selatan No. 12, Tangerang',
                'odp_name' => 'ODP-TNG-W6-01',
                'latitude' => '-6.178300',
                'longitude' => '106.631800',
                'package_name' => 'Home 20 Mbps',
                'monthly_fee' => 220000.00,
                'status' => 'active',
            ],
            [
                'billing_node_id' => $tenant1->id,
                'remote_customer_id' => 102,
                'no_services' => '300003',
                'name' => 'Juwati-(S15)',
                'phone' => '6285591326727',
                'address' => 'Pintu Air Teko II No. 45',
                'odp_name' => 'ODP-TEKO-S15-02',
                'latitude' => '-6.175000',
                'longitude' => '106.640000',
                'package_name' => 'Home 30 Mbps',
                'monthly_fee' => 300000.00,
                'status' => 'active',
            ],
            [
                'billing_node_id' => $tenant1->id,
                'remote_customer_id' => 103,
                'no_services' => '220509133215',
                'name' => 'Putri zahra-(V6)',
                'phone' => '62895365184751',
                'address' => 'Darussalam Selatan II 001/004',
                'odp_name' => 'ODP-TNG-V6-03',
                'latitude' => '-6.180000',
                'longitude' => '106.635000',
                'package_name' => 'Home 20 Mbps',
                'monthly_fee' => 220000.00,
                'status' => 'isolated',
            ],
            [
                'billing_node_id' => $tenant1->id,
                'remote_customer_id' => 104,
                'no_services' => '220509133216',
                'name' => 'Mailah-(V6)',
                'phone' => '089512345679',
                'address' => 'Darussalam Selatan Blok B No. 3',
                'odp_name' => 'ODP-TNG-V6-03',
                'latitude' => '-6.181000',
                'longitude' => '106.636000',
                'package_name' => 'Home 10 Mbps',
                'monthly_fee' => 150000.00,
                'status' => 'active',
            ],
            [
                'billing_node_id' => $tenant1->id,
                'remote_customer_id' => 105,
                'no_services' => '220509133217',
                'name' => 'Soniah ahmad-(U11)',
                'phone' => '089587654321',
                'address' => 'Jl. Kebon Jeruk No. 8, Tangerang',
                'odp_name' => 'ODP-TNG-U11-04',
                'latitude' => '-6.185000',
                'longitude' => '106.639000',
                'package_name' => 'Home 50 Mbps',
                'monthly_fee' => 450000.00,
                'status' => 'inactive',
            ],

            // Tenant 2 Customers
            [
                'billing_node_id' => $tenant2->id,
                'remote_customer_id' => 201,
                'no_services' => '10293847',
                'name' => 'Budi Santoso',
                'phone' => '081299887766',
                'address' => 'Jl. Merdeka No. 12, Jakarta Central',
                'odp_name' => 'ODP-JKT-MDK-01',
                'latitude' => '-6.200000',
                'longitude' => '106.816666',
                'package_name' => 'Biz 100 Mbps',
                'monthly_fee' => 850000.00,
                'status' => 'active',
            ],
            [
                'billing_node_id' => $tenant2->id,
                'remote_customer_id' => 202,
                'no_services' => '99887766',
                'name' => 'Siti Rahma',
                'phone' => '081377665544',
                'address' => 'Jl. Sudirman No. 45, Bandung',
                'odp_name' => 'ODP-BDG-SDR-05',
                'latitude' => '-6.917464',
                'longitude' => '107.619123',
                'package_name' => 'Home 20 Mbps',
                'monthly_fee' => 220000.00,
                'status' => 'isolated',
            ],

            // Tenant 3 Customers
            [
                'billing_node_id' => $tenant3->id,
                'remote_customer_id' => 301,
                'no_services' => '5544332211',
                'name' => 'Ahmad Fauzi',
                'phone' => '082155443322',
                'address' => 'Jl. Barat Raya No. 99, Serang',
                'odp_name' => 'ODP-SRG-BRT-01',
                'latitude' => '-6.120000',
                'longitude' => '106.150000',
                'package_name' => 'Home 50 Mbps',
                'monthly_fee' => 450000.00,
                'status' => 'active',
            ],
        ];

        Customer::upsert(
            $customersData,
            ['billing_node_id', 'remote_customer_id'],
            ['no_services', 'name', 'phone', 'address', 'odp_name', 'latitude', 'longitude', 'package_name', 'monthly_fee', 'status', 'updated_at']
        );
    }
}
