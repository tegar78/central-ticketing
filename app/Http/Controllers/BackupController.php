<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        protected DatabaseBackupService $backupService,
        protected TelegramService $telegramService
    ) {}

    /**
     * Display database backup management dashboard.
     */
    public function index(): View
    {
        $backups = $this->backupService->listBackups();
        $totalCount = count($backups);
        $totalBytes = array_sum(array_column($backups, 'size'));
        $totalSizeHuman = $this->backupService->formatBytes($totalBytes);

        $defaultConn = config('database.default', 'mysql');
        $dbConfig = config("database.connections.{$defaultConn}", []);
        $dbName = $dbConfig['database'] ?? '-';
        $dbDriver = strtoupper($dbConfig['driver'] ?? $defaultConn);

        // Get database table count
        $tableCount = 0;
        try {
            $tableCount = count(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"));
        } catch (\Exception $e) {
            $tableCount = 0;
        }

        $latestBackup = $backups[0] ?? null;

        return view('backups.index', compact(
            'backups',
            'totalCount',
            'totalBytes',
            'totalSizeHuman',
            'dbName',
            'dbDriver',
            'tableCount',
            'latestBackup'
        ));
    }

    /**
     * Create a new database backup manually.
     */
    public function create(Request $request): RedirectResponse
    {
        $compress = $request->boolean('gzip', true);
        $notify = $request->boolean('notify', false);

        $result = $this->backupService->createBackup($compress);

        if (!$result['success']) {
            return redirect()->route('backups.index')->with('error', 'Gagal membuat backup: ' . ($result['error'] ?? 'Terjadi kesalahan sistem.'));
        }

        // Optional Telegram notification
        if ($notify && $this->telegramService->isConfigured()) {
            $userName = htmlspecialchars(auth()->user()?->name ?? 'Admin', ENT_QUOTES, 'UTF-8');
            $msg = "💾 <b>DATABASE BACKUP MANUAL BERHASIL</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "<b>Admin:</b> {$userName}\n";
            $msg .= "<b>File:</b> <code>{$result['filename']}</code>\n";
            $msg .= "<b>Ukuran:</b> {$result['human_size']}\n";
            $msg .= "<b>Durasi:</b> {$result['duration']} detik\n";
            $msg .= "<b>Waktu:</b> " . date('Y-m-d H:i:s') . "\n";
            $this->telegramService->sendMessage($msg);
        }

        return redirect()->route('backups.index')->with(
            'success',
            "Backup database berhasil dibuat: {$result['filename']} ({$result['human_size']}) dalam {$result['duration']} detik."
        );
    }

    /**
     * Download backup file securely.
     */
    public function download(string $filename): BinaryFileResponse
    {
        $cleanName = basename($filename);
        if (!preg_match('/^backup-[a-zA-Z0-9_\-]+\.sql(\.gz)?$/', $cleanName)) {
            abort(404, 'Nama file backup tidak valid.');
        }

        $path = $this->backupService->getBackupPath($cleanName);

        if (!$path) {
            abort(404, 'File backup tidak ditemukan.');
        }

        return response()->download($path, $cleanName, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Delete a backup file.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $cleanName = basename($filename);
        if (!preg_match('/^backup-[a-zA-Z0-9_\-]+\.sql(\.gz)?$/', $cleanName)) {
            return redirect()->route('backups.index')->with('error', 'Nama file backup tidak valid.');
        }

        $deleted = $this->backupService->deleteBackup($cleanName);

        if ($deleted) {
            return redirect()->route('backups.index')->with('success', "File backup '{$cleanName}' berhasil dihapus.");
        }

        return redirect()->route('backups.index')->with('error', "Gagal menghapus file '{$cleanName}'.");
    }

    /**
     * Clean old backups older than specified days.
     */
    public function clean(Request $request)
    {
        $days = (int) $request->input('days', 14);
        if ($days < 1) $days = 14;

        $deletedCount = $this->backupService->cleanOldBackups($days);

        return redirect()->route('backups.index')->with(
            'success',
            "Pembersihan selesai: {$deletedCount} file backup yang lebih lama dari {$days} hari telah dihapus."
        );
    }
}
