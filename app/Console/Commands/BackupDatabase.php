<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database to a SQL file in storage/app/backups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        $backupDir = storage_path('app/backups');

        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        return match ($driver) {
            'sqlite' => $this->backupSqlite($connection, $filePath),
            'mysql', 'mariadb' => $this->backupMysql($connection, $filePath),
            default => $this->unsupportedDriver($driver),
        };
    }

    /**
     * Backup a SQLite database by copying the file.
     */
    protected function backupSqlite(array $connection, string $filePath): int
    {
        $dbPath = $connection['database'];

        if (! File::exists($dbPath)) {
            $this->error("SQLite database file not found: {$dbPath}");
            return self::FAILURE;
        }

        File::copy($dbPath, $filePath);

        $this->info("Database backed up successfully!");
        $this->info("File: {$filePath}");

        return self::SUCCESS;
    }

    /**
     * Backup a MySQL/MariaDB database using mysqldump.
     */
    protected function backupMysql(array $connection, string $filePath): int
    {
        $host = $connection['host'] ?? '127.0.0.1';
        $port = $connection['port'] ?? '3306';
        $database = $connection['database'];
        $username = $connection['username'] ?? 'root';
        $password = $connection['password'] ?? '';

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($filePath)
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('Database backup failed!');
            $this->error(implode("\n", $output));
            return self::FAILURE;
        }

        $this->info("Database backed up successfully!");
        $this->info("File: {$filePath}");

        return self::SUCCESS;
    }

    /**
     * Handle unsupported database drivers.
     */
    protected function unsupportedDriver(string $driver): int
    {
        $this->error("Unsupported database driver: {$driver}");
        return self::FAILURE;
    }
}
