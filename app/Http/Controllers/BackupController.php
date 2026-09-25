<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function index()
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
    public function create(Request $request)
    {
        $compress = $request->boolean('gzip', true);
        $notify = $request->boolean('notify', false);

        $result = $this->backupService->createBackup($compress);

        if (!$result['success']) {
            return redirect()->route('backups.index')->with('error', 'Gagal membuat backup: ' . ($result['error'] ?? 'Terjadi kesalahan sistem.'));
        }

        // Optional Telegram notification
        if ($notify && $this->telegramService->isConfigured()) {
            $msg = "💾 <b>DATABASE BACKUP MANUAL BERHASIL</b>\n";
            $msg .= "------------------------------------\n";
            $msg .= "<b>Admin:</b> " . htmlspecialchars(auth()->user()->name ?? 'Admin') . "\n";
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
        $path = $this->backupService->getBackupPath($filename);

        if (!$path) {
            abort(404, 'File backup tidak ditemukan.');
        }

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Delete a backup file.
     */
    public function destroy(string $filename)
    {
        $deleted = $this->backupService->deleteBackup($filename);

        if ($deleted) {
            return redirect()->route('backups.index')->with('success', "File backup '{$filename}' berhasil dihapus.");
        }

        return redirect()->route('backups.index')->with('error', "Gagal menghapus file '{$filename}'.");
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
