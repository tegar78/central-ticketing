<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BillingInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BulkRegisterBillingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:bulk-register
                            {--protocol=https : Protocol yang digunakan (https atau http)}
                            {--shared-key= : Gunakan satu API key seragam untuk semua billing}
                            {--dry-run : Tampilkan daftar tanpa menyimpannya ke database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daftarkan secara massal (bulk) seluruh domain billing Gayuh ke Central Ticket System';

    /**
     * Daftar 40 domain billing server Gayuh
     *
     * @var array<int, string>
     */
    protected array $domains = [
        'bill2-gmi.gayuh.net.id',
        'bill3-gyh.gayuh.net.id',
        'bill2-gyh.gayuh.net.id',
        'bill2-ckd.gayuh.net.id',
        'bill2-dsnet.gayuh.net.id',
        'bill2-free.gayuh.net.id',
        'bill2-jabrik.gayuh.net.id',
        'bill2-jb.gayuh.net.id',
        'bill2-mitra2.gayuh.biz.id',
        'bill2-mitra3.gayuh.biz.id',
        'bill2-mitra4.gayuh.biz.id',
        'bill2-pay.gayuh.net.id',
        'bill2-pop3.gayuh.biz.id',
        'bill2-pop4.gayuh.biz.id',
        'bill2-pop5.gayuh.biz.id',
        'bill2-pop6.gayuh.biz.id',
        'bill2-pop8.gayuh.biz.id',
        'bill2-pop9.gayuh.biz.id',
        'bill2-pos1.gayuh.net.id',
        'bill2-sales.gayuh.net.id',
        'bill2-sn.gayuh.net.id',
        'bill2-wd.gayuh.net.id',
        'bill3-fkds.gayuh.net.id',
        'bill3-free.gayuh.net.id',
        'bill3-habudin.gayuh.net.id',
        'bill3-jabrik.gayuh.net.id',
        'bill3-kedaung.gayuh.net.id',
        'bill3-malik.gayuh.net.id',
        'bill3-mamat.gayuh.net.id',
        'bill3-miftah.gayuh.net.id',
        'bill3-nahdi.gayuh.net.id',
        'bill3-pay.gayuh.net.id',
        'bill3-pgb.gayuh.net.id',
        'bill3-roki.gayuh.net.id',
        'bill3-rwboy.gayuh.net.id',
        'bill3-sales.gayuh.net.id',
        'bill3-sanuri.gayuh.net.id',
        'bill3-sn.gayuh.net.id',
        'bill3-subur.gayuh.net.id',
        'bill3-wdnet.gayuh.net.id',
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->info("╔══════════════════════════════════════════════════════════════════════╗");
        $this->info("║        BULK REGISTER BILLING INSTANCES (GAYUH ECOSYSTEM)             ║");
        $this->info("╚══════════════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $protocol = strtolower((string) $this->option('protocol'));
        if (!in_array($protocol, ['http', 'https'], true)) {
            $protocol = 'https';
        }

        // Ambil dari opsi command atau dari .env (BILLING_SHARED_KEY) agar aman dari Git/GitHub
        $sharedKey = $this->option('shared-key') ?: env('BILLING_SHARED_KEY');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Total domain yang akan didaftarkan: " . count($this->domains));
        $this->info("Protocol default: {$protocol}://");
        $this->info("Shared API Key: " . ($sharedKey ?: '(Generated per tenant / set di .env BILLING_SHARED_KEY)'));
        if ($isDryRun) {
            $this->warn("MODE DRY-RUN: Data tidak akan disimpan ke database.");
        }
        $this->newLine();

        $tableRows = [];
        $createdCount = 0;
        $updatedCount = 0;

        foreach ($this->domains as $domain) {
            // Contoh domain: bill2-dsnet.gayuh.net.id -> bill2-dsnet
            $parts = explode('.', $domain);
            $subdomain = $parts[0] ?? $domain;

            // Tenant code: BILL2-DSNET
            $tenantCode = strtoupper(str_replace('_', '-', $subdomain));

            // Nama rapi: BILL2 DSNET (GAYUH)
            $readableName = ucwords(str_replace(['-', '_'], ' ', $subdomain)) . ' (Gayuh)';

            $domainUrl = "{$protocol}://{$domain}";
            $callbackUrl = "{$domainUrl}/central/callback";

            // Tentukan API key
            $apiKey = !empty($sharedKey)
                ? (string) $sharedKey
                : 'key-' . strtolower($tenantCode) . '-secret-' . substr(md5($domain), 0, 10);

            $tableRows[] = [
                $tenantCode,
                $readableName,
                $domainUrl,
                $apiKey,
            ];

            if (!$isDryRun) {
                $existing = BillingInstance::where('tenant_code', $tenantCode)
                    ->orWhere('domain_url', $domainUrl)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'tenant_code'  => $tenantCode,
                        'name'         => $readableName,
                        'domain_url'   => $domainUrl,
                        'callback_url' => $callbackUrl,
                        'is_active'    => true,
                        // Hanya update api_key jika parameter shared-key diisi
                        'api_key'      => !empty($sharedKey) ? $sharedKey : ($existing->api_key ?: $apiKey),
                    ]);
                    $updatedCount++;
                } else {
                    BillingInstance::create([
                        'tenant_code'  => $tenantCode,
                        'name'         => $readableName,
                        'domain_url'   => $domainUrl,
                        'api_key'      => $apiKey,
                        'callback_url' => $callbackUrl,
                        'is_active'    => true,
                    ]);
                    $createdCount++;
                }
            }
        }

        $this->table(
            ['Tenant Code', 'Nama Instance', 'Domain URL', 'API Key'],
            $tableRows
        );

        if (!$isDryRun) {
            $this->newLine();
            $this->info("✓ Registrasi massal selesai!");
            $this->info("  - Baru ditambahkan: {$createdCount}");
            $this->info("  - Diperbarui: {$updatedCount}");
            $this->info("  - Total billing aktif: " . BillingInstance::where('is_active', true)->count());
            $this->newLine();
            $this->comment("Langkah selanjutnya untuk sinkronisasi seluruh pelanggan:");
            $this->line("  php artisan sync:billing-customers --all");
        }

        return 0;
    }
}
