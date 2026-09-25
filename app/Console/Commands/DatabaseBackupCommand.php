<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup 
                            {--gzip : Kompresi file dengan format .sql.gz} 
                            {--retention=14 : Hapus backup yang lebih lama dari N hari} 
                            {--notify : Kirim laporan ke grup Telegram}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melakukan backup database (MySQL / MariaDB / SQLite) dan menyimpan ke storage privat.';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService, TelegramService $telegramService): int
    {
        $this->info("Memulai proses backup database...");

        $compress = (bool) $this->option('gzip');
        $retention = (int) $this->option('retention');
        $notify = (bool) $this->option('notify');

        $result = $backupService->createBackup($compress);

        if (!$result['success']) {
            $this->error("Backup gagal: " . ($result['error'] ?? 'Terjadi kesalahan tidak dikenal.'));

            if ($notify && $telegramService->isConfigured()) {
                $msg = "⚠️ <b>DATABASE BACKUP GAGAL</b>\n";
                $msg .= "------------------------------------\n";
                $msg .= "<b>Error:</b> " . htmlspecialchars($result['error'] ?? 'Unknown') . "\n";
                $msg .= "<b>Waktu:</b> " . date('Y-m-d H:i:s') . "\n";
                $telegramService->sendMessage($msg);
            }

            return Command::FAILURE;
        }

        $this->info("✅ Backup berhasil dibuat!");
        $this->table(
            ['Properti', 'Nilai'],
            [
                ['Nama File', $result['filename']],
                ['Ukuran File', $result['human_size']],
                ['Durasi Dump', $result['duration'] . ' detik'],
                ['Engine Digunakan', $result['engine']],
                ['Jumlah Tabel', $result['tables_count']],
                ['Lokasi Penyimpanan', $result['path']],
            ]
        );

        // Retention policy cleanup
        if ($retention > 0) {
            $deleted = $backupService->cleanOldBackups($retention);
            if ($deleted > 0) {
                $this->comment("Pembersihan: {$deleted} file backup lama (> {$retention} hari) berhasil dihapus.");
            }
        }

        // Telegram Notification
        if ($notify && $telegramService->isConfigured()) {
            $msg = "💾 <b>DATABASE BACKUP BERHASIL</b>\n";
            $msg .= "------------------------------------\n";
            $msg .= "<b>File:</b> <code>{$result['filename']}</code>\n";
            $msg .= "<b>Ukuran:</b> {$result['human_size']}\n";
            $msg .= "<b>Durasi:</b> {$result['duration']} detik\n";
            $msg .= "<b>Engine:</b> {$result['engine']} ({$result['tables_count']} tabel)\n";
            $msg .= "<b>Waktu:</b> " . date('Y-m-d H:i:s') . "\n";
            $telegramService->sendMessage($msg);
            $this->info("Notifikasi Telegram berhasil dikirim.");
        }

        return Command::SUCCESS;
    }
}
