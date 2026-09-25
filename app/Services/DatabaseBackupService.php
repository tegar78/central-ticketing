<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

class DatabaseBackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        $this->ensureDirectoryExists();
    }

    /**
     * Ensure the backup directory exists and is protected.
     */
    protected function ensureDirectoryExists(): void
    {
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }

        // Add an .htaccess inside backup dir to prevent direct web access
        $htaccessPath = $this->backupDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!File::exists($htaccessPath)) {
            File::put($htaccessPath, "Deny from all\n");
        }
    }

    /**
     * Get backup directory path.
     */
    public function getBackupDirectory(): string
    {
        return $this->backupDir;
    }

    /**
     * Create a full database backup.
     *
     * @param bool $compress Whether to compress the output using Gzip (.sql.gz)
     * @return array Backup metadata
     */
    public function createBackup(bool $compress = false): array
    {
        $startTime = microtime(true);
        $connection = config('database.default', 'mysql');
        $dbConfig = config("database.connections.{$connection}");

        $dbName = $dbConfig['database'] ?? 'database';
        $timestamp = date('Ymd-His');
        $baseName = 'backup-' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $dbName) . '-' . $timestamp;
        $sqlFilename = $baseName . '.sql';
        $sqlPath = $this->backupDir . DIRECTORY_SEPARATOR . $sqlFilename;

        $driver = $dbConfig['driver'] ?? 'mysql';
        $engineUsed = 'php-pdo';
        $tablesCount = 0;

        try {
            if ($driver === 'sqlite') {
                $this->dumpSqlite($dbConfig['database'], $sqlPath);
                $engineUsed = 'sqlite-copy';
                $tablesCount = 1;
            } else {
                // Try mysqldump first if available
                $dumpSuccess = $this->tryMysqldump($dbConfig, $sqlPath);
                if ($dumpSuccess) {
                    $engineUsed = 'mysqldump';
                    $tablesCount = count(DB::select('SHOW TABLES'));
                } else {
                    // Fallback to pure PHP PDO dumper
                    $tablesCount = $this->dumpWithPdo($sqlPath);
                    $engineUsed = 'php-pdo';
                }
            }

            $finalFilename = $sqlFilename;
            $finalPath = $sqlPath;

            // Compress with Gzip if requested
            if ($compress && File::exists($sqlPath)) {
                $gzFilename = $baseName . '.sql.gz';
                $gzPath = $this->backupDir . DIRECTORY_SEPARATOR . $gzFilename;

                if ($this->compressFile($sqlPath, $gzPath)) {
                    File::delete($sqlPath);
                    $finalFilename = $gzFilename;
                    $finalPath = $gzPath;
                }
            }

            $size = File::exists($finalPath) ? File::size($finalPath) : 0;
            $duration = round(microtime(true) - $startTime, 2);

            return [
                'success'      => true,
                'filename'     => $finalFilename,
                'path'         => $finalPath,
                'size'         => $size,
                'human_size'   => $this->formatBytes($size),
                'duration'     => $duration,
                'engine'       => $engineUsed,
                'tables_count' => $tablesCount,
                'created_at'   => Carbon::now()->format('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            Log::error('DatabaseBackupService failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if (File::exists($sqlPath)) {
                File::delete($sqlPath);
            }

            return [
                'success'  => false,
                'error'    => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2),
            ];
        }
    }

    /**
     * Dump database using native mysqldump binary if accessible.
     */
    protected function tryMysqldump(array $config, string $outputPath): bool
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $db = $config['database'] ?? '';
        $user = $config['username'] ?? 'root';
        $pass = $config['password'] ?? '';

        $passwordArg = !empty($pass) ? "-p" . escapeshellarg($pass) : "";
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            $passwordArg,
            escapeshellarg($db),
            escapeshellarg($outputPath)
        );

        $output = [];
        $returnCode = 1;
        @exec($command, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath) && File::size($outputPath) > 100) {
            return true;
        }

        // Clean up partial/error output file
        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        return false;
    }

    /**
     * Pure PHP PDO SQL Dumper - 100% reliable across any platform (Windows, Linux, DBngin).
     */
    protected function dumpWithPdo(string $outputPath): int
    {
        $pdo = DB::connection()->getPdo();
        $dbName = DB::connection()->getDatabaseName();

        $handle = fopen($outputPath, 'w');
        if (!$handle) {
            throw new \RuntimeException("Tidak dapat membuat file backup di: {$outputPath}");
        }

        // Header
        $now = date('Y-m-d H:i:s');
        $header = <<<SQL
-- ========================================================
-- Central Ticketing System - Database Backup
-- Database: `{$dbName}`
-- Timestamp: {$now}
-- Generated by: DatabaseBackupService (Pure PHP PDO Engine)
-- ========================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

SQL;
        fwrite($handle, $header . "\n");

        // Fetch all tables
        $tables = [];
        $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            // Write Drop and Create Table
            fwrite($handle, "\n-- --------------------------------------------------------\n");
            fwrite($handle, "-- Struktur Tabel `{$table}`\n");
            fwrite($handle, "-- --------------------------------------------------------\n\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            $createSql = $createStmt['Create Table'] ?? '';
            fwrite($handle, $createSql . ";\n\n");

            // Write Data
            fwrite($handle, "-- Dumping data untuk tabel `{$table}`\n");

            $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $rowCount = (int) $countStmt->fetchColumn();

            if ($rowCount > 0) {
                // Chunk queries to keep memory low
                $offset = 0;
                $chunkSize = 200;

                while ($offset < $rowCount) {
                    $dataStmt = $pdo->query("SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}");
                    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($rows)) {
                        $columns = array_keys($rows[0]);
                        $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
                        $colList = implode(', ', $escapedColumns);

                        $valList = [];
                        foreach ($rows as $row) {
                            $rowVals = [];
                            foreach ($row as $val) {
                                if (is_null($val)) {
                                    $rowVals[] = 'NULL';
                                } elseif (is_numeric($val) && !is_string($val)) {
                                    $rowVals[] = $val;
                                } elseif (is_array($val) || is_object($val)) {
                                    $rowVals[] = $pdo->quote(json_encode($val));
                                } else {
                                    $rowVals[] = $pdo->quote((string) $val);
                                }
                            }
                            $valList[] = '(' . implode(', ', $rowVals) . ')';
                        }

                        $insertSql = "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $valList) . ";\n";
                        fwrite($handle, $insertSql);
                    }

                    $offset += $chunkSize;
                }
            } else {
                fwrite($handle, "-- (Tabel kosong)\n");
            }
            fwrite($handle, "\n");
        }

        // Footer
        $totalTables = count($tables);
        $footer = "\n-- ========================================================\n"
                . "-- Selesai Ekspor Seluruh Tabel ({$totalTables} Tabel)\n"
                . "-- ========================================================\n"
                . "SET FOREIGN_KEY_CHECKS=1;\n";

        fwrite($handle, $footer);
        fclose($handle);

        return count($tables);
    }

    /**
     * Dump SQLite file.
     */
    protected function dumpSqlite(string $sqliteDbPath, string $outputPath): void
    {
        if (!File::exists($sqliteDbPath)) {
            throw new \RuntimeException("Database file SQLite tidak ditemukan di: {$sqliteDbPath}");
        }

        File::copy($sqliteDbPath, $outputPath);
    }

    /**
     * Compress a file using Gzip.
     */
    protected function compressFile(string $sourcePath, string $destPath): bool
    {
        if (!function_exists('gzopen')) {
            return false;
        }

        $source = fopen($sourcePath, 'rb');
        $dest = gzopen($destPath, 'wb9');

        if (!$source || !$dest) {
            if ($source) fclose($source);
            if ($dest) gzclose($dest);
            return false;
        }

        while (!feof($source)) {
            gzwrite($dest, fread($source, 1024 * 512));
        }

        fclose($source);
        gzclose($dest);

        return File::exists($destPath);
    }

    /**
     * List all available backups with metadata.
     *
     * @return array
     */
    public function listBackups(): array
    {
        if (!File::exists($this->backupDir)) {
            return [];
        }

        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            $name = $file->getFilename();

            // Only recognize .sql and .sql.gz files
            if (!str_ends_with($name, '.sql') && !str_ends_with($name, '.sql.gz')) {
                continue;
            }

            $size = $file->getSize();
            $mtime = $file->getMTime();
            $createdAt = Carbon::createFromTimestamp($mtime, config('app.timezone', 'Asia/Jakarta'));

            $backups[] = [
                'filename'      => $name,
                'path'          => $file->getPathname(),
                'size'          => $size,
                'human_size'    => $this->formatBytes($size),
                'created_at'    => $createdAt->format('Y-m-d H:i:s'),
                'created_human' => $createdAt->diffForHumans(),
                'timestamp'     => $mtime,
                'is_compressed' => str_ends_with($name, '.gz'),
            ];
        }

        // Sort descending by created timestamp
        usort($backups, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $backups;
    }

    /**
     * Get validated absolute path of a backup file.
     */
    public function getBackupPath(string $filename): ?string
    {
        $cleanName = basename($filename);
        $path = $this->backupDir . DIRECTORY_SEPARATOR . $cleanName;

        if (File::exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Delete a backup file by filename.
     */
    public function deleteBackup(string $filename): bool
    {
        $path = $this->getBackupPath($filename);

        if ($path && File::exists($path)) {
            return File::delete($path);
        }

        return false;
    }

    /**
     * Remove backups older than specified retention days.
     *
     * @param int $retentionDays
     * @return int Number of files deleted
     */
    public function cleanOldBackups(int $retentionDays = 14): int
    {
        $backups = $this->listBackups();
        $cutoff = Carbon::now()->subDays($retentionDays)->timestamp;
        $deletedCount = 0;

        foreach ($backups as $b) {
            if ($b['timestamp'] < $cutoff) {
                if ($this->deleteBackup($b['filename'])) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Convert bytes to human-readable format.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $power), $precision) . ' ' . ($units[$power] ?? 'B');
    }
}
