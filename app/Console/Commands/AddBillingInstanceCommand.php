<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BillingInstance;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AddBillingInstanceCommand extends Command
{
    protected $signature = 'billing:add 
                            {tenant_code? : Kode tenant unik (contoh: BILL-002)}
                            {name? : Nama perusahaan / instansi billing}
                            {domain_url? : URL domain billing CI3 (contoh: https://bill2.gayuh.net.id.test)}
                            {--api-key= : API key custom (opsional, otomatis dibuat jika kosong)}
                            {--db-host= : Host database MySQL CI3 (opsional)}
                            {--db-port=3306 : Port database MySQL CI3}
                            {--db-name= : Nama database MySQL CI3 (opsional)}
                            {--db-user= : Username database MySQL CI3}
                            {--db-pass= : Password database MySQL CI3}';

    protected $description = 'Tambahkan koneksi Billing Instance CodeIgniter 3 baru ke Central Ticket System';

    public function handle()
    {
        $this->info("══════════════════════════════════════════════════════");
        $this->info("       TAMBAH BILLING INSTANCE CODEIGNITER 3         ");
        $this->info("══════════════════════════════════════════════════════");

        // 1. Tenant Code
        $tenantCode = $this->argument('tenant_code') ?: $this->ask('Masukkan Tenant Code (contoh: BILL-002)');
        $tenantCode = strtoupper(trim($tenantCode));

        if (BillingInstance::where('tenant_code', $tenantCode)->exists()) {
            $this->error("Tenant code [{$tenantCode}] sudah ada di database!");
            if (!$this->confirm('Apakah Anda ingin mengupdate data tenant ini?', false)) {
                return 1;
            }
        }

        // 2. Name
        $name = $this->argument('name') ?: $this->ask('Masukkan Nama Instance Billing (contoh: PT Gayuh Media Cabang 2)');

        // 3. Domain URL
        $domainUrl = $this->argument('domain_url') ?: $this->ask('Masukkan Domain URL Billing CI3 (contoh: https://bill2.gayuh.net.id.test)');
        $domainUrl = rtrim(trim($domainUrl), '/');

        // 4. API Key
        $apiKey = $this->option('api-key') ?: 'key-' . Str::slug($tenantCode) . '-secret-' . Str::random(12);

        // 5. Direct Database (Opsional)
        $withDb = $this->confirm('Apakah ingin konfigurasi Direct Database MySQL lokal/remote untuk billing ini?', false);
        $dbHost = null;
        $dbPort = 3306;
        $dbName = null;
        $dbUser = null;
        $dbPass = null;

        if ($withDb) {
            $dbHost = $this->option('db-host') ?: $this->ask('DB Host', '127.0.0.1');
            $dbPort = $this->option('db-port') ?: $this->ask('DB Port', '3306');
            $dbName = $this->option('db-name') ?: $this->ask('DB Database Name');
            $dbUser = $this->option('db-user') ?: $this->ask('DB Username', 'root');
            $dbPass = $this->option('db-pass') ?: (string) $this->secret('DB Password (biarkan kosong jika tidak ada)');
        }

        $instance = BillingInstance::updateOrCreate(
            ['tenant_code' => $tenantCode],
            [
                'name'         => $name,
                'domain_url'   => $domainUrl,
                'api_key'      => $apiKey,
                'callback_url' => "{$domainUrl}/central/callback",
                'is_active'    => true,
                'db_host'      => $dbHost,
                'db_port'      => $dbPort,
                'db_database'  => $dbName,
                'db_username'  => $dbUser,
                'db_password'  => $dbPass,
            ]
        );

        $this->newLine();
        $this->info("✓ Billing Instance [{$instance->tenant_code}] berhasil disimpan!");
        $this->table(
            ['Field', 'Value'],
            [
                ['Tenant Code', $instance->tenant_code],
                ['Nama', $instance->name],
                ['Domain URL', $instance->domain_url],
                ['API Key', $instance->api_key],
                ['Callback URL', $instance->callback_url],
                ['Status', $instance->is_active ? 'Aktif' : 'Nonaktif'],
                ['DB Database', $instance->db_database ?: '(REST API only)'],
            ]
        );

        // Test API Connection
        $this->info("Mencoba tes koneksi REST API ke [{$domainUrl}/central/customers]...");
        try {
            $res = Http::withoutVerifying()
                ->timeout(5)
                ->withHeaders([
                    'X-API-Key' => $instance->api_key,
                    'Accept'    => 'application/json',
                ])
                ->get("{$domainUrl}/central/customers");

            if ($res->successful()) {
                $count = count($res->json()['data'] ?? $res->json() ?? []);
                $this->info("✓ Tes koneksi REST API BERHASIL! Terhubung dan menemukan {$count} pelanggan.");
            } else {
                $this->warn("⚠ REST API merespon dengan HTTP {$res->status()}. Pastikan Central.php di CI3 sudah terpasang dan API Key cocok.");
            }
        } catch (\Throwable $e) {
            $this->warn("⚠ Tidak dapat menghubungi REST API: " . $e->getMessage());
            $this->line("  Pastikan web server billing CI3 sudah berjalan.");
        }

        $this->newLine();
        $this->info("Untuk melakukan sinkronisasi data dari node ini kapan saja:");
        $this->comment("  php artisan sync:billing-customers {$instance->tenant_code}");

        return 0;
    }
}
