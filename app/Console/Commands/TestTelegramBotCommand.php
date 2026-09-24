<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class TestTelegramBotCommand extends Command
{
    protected $signature = 'telegram:test {--chat_id= : Custom Chat ID to send test message to}';

    protected $description = 'Uji koneksi, periksa token bot (getMe), dan kirim pesan uji coba ke grup Telegram';

    public function handle(): int
    {
        $this->newLine();
        $this->info('========================================================');
        $this->info('   PENGUJIAN BOT TELEGRAM - CENTRAL TICKET SYSTEM       ');
        $this->info('========================================================');
        $this->newLine();

        $botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');
        $chatId   = $this->option('chat_id') ?: (config('services.telegram.chat_id') ?: env('TELEGRAM_GROUP_CHAT_ID'));

        $this->table(
            ['Parameter', 'Status / Nilai'],
            [
                ['TELEGRAM_BOT_TOKEN', !empty($botToken) ? substr($botToken, 0, 10) . '...' . substr($botToken, -5) : '<fg=red>Belum Diatur</>'],
                ['TELEGRAM_GROUP_CHAT_ID', !empty($chatId) ? $chatId : '<fg=red>Belum Diatur</>'],
            ]
        );

        if (empty($botToken)) {
            $this->newLine();
            $this->error('[-] Error: TELEGRAM_BOT_TOKEN belum diisi di file .env');
            $this->warn('    Dapatkan token dari @BotFather dan masukkan ke TELEGRAM_BOT_TOKEN=.env');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('<fg=yellow>1. Memeriksa identitas bot via getMe...</>');

        try {
            $res = Http::withoutVerifying()->timeout(5)->get("https://api.telegram.org/bot{$botToken}/getMe");
            $data = $res->json();

            if (!$res->successful() || empty($data['ok'])) {
                $this->error('[-] Gagal memverifikasi token bot Telegram!');
                $this->error('    Respon API: ' . ($data['description'] ?? $res->body()));
                return self::FAILURE;
            }

            $botUser = $data['result'];
            $this->info("[+] Bot Valid! Nama: {$botUser['first_name']} | Username: @{$botUser['username']} (ID: {$botUser['id']})");
        } catch (Throwable $e) {
            $this->error("[-] Gagal menghubungi API Telegram: {$e->getMessage()}");
            return self::FAILURE;
        }

        if (empty($chatId)) {
            $this->newLine();
            $this->warn('[!] TELEGRAM_GROUP_CHAT_ID belum diatur di .env');
            $this->line('    Tips: Tambahkan bot Anda ke dalam grup Telegram, jadikan admin, lalu ambil ID grup (biasanya berawalan -100...)');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->line("<fg=yellow>2. Mengirim pesan uji coba ke chat ID: {$chatId}...</>");

        try {
            $timeNow = now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') . ' WIB';
            $testMsg = "🤖 <b>PENGUJIAN KONEKSI BOT TELEGRAM</b>\n"
                     . "━━━━━━━━━━━━━━━━━━━━\n"
                     . "✅ Bot Telegram Central Ticket System berhasil terhubung!\n"
                     . "🕒 Waktu Uji: <code>{$timeNow}</code>\n"
                     . "🚀 Notifikasi update tiket, penugasan teknisi, dan tiket selesai siap diproses.";

            $sendRes = Http::withoutVerifying()->timeout(5)->asJson()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $testMsg,
                'parse_mode' => 'HTML',
            ]);

            $sendData = $sendRes->json();

            if ($sendRes->successful() && !empty($sendData['ok'])) {
                $this->info("[+] Pesan uji coba BERHASIL terkirim ke grup!");
                $this->newLine();
                $this->info('========================================================');
                $this->info('  SEMUA PENGUJIAN BOT TELEGRAM BERJALAN DENGAN SUKSES   ');
                $this->info('========================================================');
                return self::SUCCESS;
            } else {
                $this->error('[-] Gagal mengirim pesan ke grup Telegram!');
                $this->error('    Respon API: ' . ($sendData['description'] ?? $sendRes->body()));
                $this->newLine();
                $this->warn('Petunjuk Perbaikan:');
                $this->line('1. Pastikan bot sudah di-invite / dimasukkan ke dalam grup Telegram.');
                $this->line('2. Pastikan bot diberi izin (permission) untuk mengirim pesan / admin di grup.');
                $this->line('3. Jika grup berjenis supergroup, chat ID umumnya memiliki awalan -100 (misal: -1001234567890).');
                return self::FAILURE;
            }
        } catch (Throwable $e) {
            $this->error("[-] Exception saat mengirim pesan: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
