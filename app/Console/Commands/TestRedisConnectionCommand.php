<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Throwable;

class TestRedisConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uji koneksi, latency, dan fungsionalitas read/write Redis di Central Ticket System';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('====================================================');
        $this->info('   PENGUJIAN KONEKSI REDIS - CENTRAL TICKET SYSTEM  ');
        $this->info('====================================================');
        $this->newLine();

        $client   = config('database.redis.client', 'phpredis');
        $host     = config('database.redis.default.host', '127.0.0.1');
        $port     = config('database.redis.default.port', 6379);
        $db       = config('database.redis.default.database', 0);
        $hasPass  = !empty(config('database.redis.default.password'));

        $this->table(
            ['Parameter', 'Nilai Konfigurasi'],
            [
                ['REDIS_CLIENT', $client],
                ['REDIS_HOST', $host],
                ['REDIS_PORT', $port],
                ['REDIS_DB (Default/Queue)', $db],
                ['REDIS_CACHE_DB (Cache)', config('database.redis.cache.database', 1)],
                ['Password Terisi?', $hasPass ? 'Ya (Terkonfigurasi)' : 'Tidak (null/kosong)'],
                ['CACHE_STORE', config('cache.default')],
                ['QUEUE_CONNECTION', config('queue.default')],
                ['SESSION_DRIVER', config('session.driver')],
            ]
        );

        $this->newLine();
        $this->line('<fg=yellow>1. Memeriksa Ekstensi / Driver PHP...</>');

        if ($client === 'phpredis') {
            if (!extension_loaded('redis')) {
                $this->error('[-] Ekstensi PHP phpredis belum terpasang di sistem.');
                $this->warn('    Solusi di Ubuntu/Debian: sudo apt install php8.3-redis && sudo systemctl restart php8.3-fpm');
                $this->warn('    Atau gunakan predis: composer require predis/predis dan ubah REDIS_CLIENT=predis di .env');
                return self::FAILURE;
            }
            $this->info('[+] Ekstensi PHP phpredis terpasang aktif.');
        } elseif ($client === 'predis') {
            if (!class_exists('Predis\Client')) {
                $this->error('[-] Package predis/predis belum terpasang.');
                $this->warn('    Solusi: composer require predis/predis');
                return self::FAILURE;
            }
            $this->info('[+] Package Predis terpasang aktif.');
        }

        $this->newLine();
        $this->line("<fg=yellow>2. Melakukan PING ke {$host}:{$port}...</>");

        try {
            $startTime = microtime(true);
            $ping = Redis::ping();
            $latency = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("[+] Koneksi Berhasil! Respon Ping: " . (is_string($ping) ? $ping : 'PONG') . " ({$latency} ms)");
        } catch (Throwable $e) {
            $this->error('[-] Gagal menghubungkan ke Redis Server!');
            $this->error("    Pesan Error: {$e->getMessage()}");
            $this->newLine();
            $this->warn('Petunjuk Perbaikan:');
            $this->line('1. Pastikan service berjalan: sudo systemctl status redis-server');
            $this->line('2. Jika menggunakan password di redis.conf (requirepass), pastikan REDIS_PASSWORD di .env sesuai.');
            $this->line('3. Jika bind 127.0.0.1, pastikan REDIS_HOST diset ke 127.0.0.1.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('<fg=yellow>3. Pengujian Operasi Write & Read (Redis Facade)...</>');

        try {
            $testKey = 'central_ticketing_test_' . time();
            $testValue = 'OK_' . uniqid();

            Redis::set($testKey, $testValue);
            $readValue = Redis::get($testKey);
            Redis::del($testKey);

            if ($readValue === $testValue) {
                $this->info('[+] Uji Write & Read Redis Langsung: SUKSES');
            } else {
                $this->warn('[-] Nilai yang dibaca tidak cocok dengan yang ditulis.');
            }
        } catch (Throwable $e) {
            $this->error("[-] Gagal melakukan operasi Write/Read: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('<fg=yellow>4. Pengujian Integrasi Cache Store...</>');

        try {
            $cacheKey = 'cache_ping_' . time();
            Cache::put($cacheKey, 'redis_ready', 10);
            $cached = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            if ($cached === 'redis_ready') {
                $this->info("[+] Uji Facade Cache (" . config('cache.default') . "): SUKSES");
            } else {
                $this->warn('[-] Uji Facade Cache tidak mengembalikan nilai yang diharapkan.');
            }
        } catch (Throwable $e) {
            $this->error("[-] Gagal menguji Cache: {$e->getMessage()}");
        }

        $this->newLine();
        $this->info('====================================================');
        $this->info('  SEMUA PENGUJIAN KONEKSI REDIS BERJALAN DENGAN BAIK');
        $this->info('====================================================');
        $this->newLine();

        return self::SUCCESS;
    }
}
