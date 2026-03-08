<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RestoreDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore
                            {file : Path to the backup file to restore}
                            {--force : Skip confirmation prompt (required in production)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the database from a backup file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = $this->argument('file');

        // If the file path is not absolute, look for it in the backups directory
        if (! File::isAbsolute($file)) {
            $file = storage_path('app/backups/' . $file);
        }

        if (! File::exists($file)) {
            $this->error("Backup file not found: {$file}");
            return self::FAILURE;
        }

        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        // Confirmation prompt
        if (! $this->option('force')) {
            $this->warn("You are about to restore the database from: {$file}");
            $this->warn("This will OVERWRITE the current {$driver} database: {$connection['database']}");

            if (! $this->confirm('Do you wish to continue?')) {
                $this->info('Restore cancelled.');
                return self::SUCCESS;
            }
        }

        return match ($driver) {
            'sqlite' => $this->restoreSqlite($connection, $file),
            'mysql', 'mariadb' => $this->restoreMysql($connection, $file),
            default => $this->unsupportedDriver($driver),
        };
    }

    /**
     * Restore a SQLite database by copying the backup file.
     */
    protected function restoreSqlite(array $connection, string $file): int
    {
        $dbPath = $connection['database'];

        File::copy($file, $dbPath);

        $this->info("Database restored successfully from: {$file}");

        return self::SUCCESS;
    }

    /**
     * Restore a MySQL/MariaDB database using the mysql command.
     */
    protected function restoreMysql(array $connection, string $file): int
    {
        $host = $connection['host'] ?? '127.0.0.1';
        $port = $connection['port'] ?? '3306';
        $database = $connection['database'];
        $username = $connection['username'] ?? 'root';
        $password = $connection['password'] ?? '';

        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s %s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($file)
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('Database restore failed!');
            $this->error(implode("\n", $output));
            return self::FAILURE;
        }

        $this->info("Database restored successfully from: {$file}");

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
