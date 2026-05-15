<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;

class ReEncryptMikrotikPasswords extends Command
{
    protected $signature = 'app:reencrypt {old_key : The old APP_KEY (base64:... format)}';
    protected $description = 'Re-encrypt all encrypted fields (Mikrotik api_password, OLT password) from old APP_KEY to current APP_KEY';

    public function handle(): int
    {
        $oldKeyInput = $this->argument('old_key');

        if (!str_starts_with($oldKeyInput, 'base64:')) {
            $this->error('Key harus format base64:... (copy dari .env)');
            return 1;
        }

        $oldKeyBytes = base64_decode(substr($oldKeyInput, 7));
        $newKeyBytes = base64_decode(substr(config('app.key'), 7));

        $oldEncrypter = new Encrypter($oldKeyBytes, config('app.cipher'));
        $newEncrypter = new Encrypter($newKeyBytes, config('app.cipher'));

        $totalSuccess = 0;
        $totalFailed = 0;

        $tables = [
            ['table' => 'mikrotiks', 'field' => 'api_password', 'label' => 'Mikrotik'],
            ['table' => 'olts', 'field' => 'password', 'label' => 'OLT'],
        ];

        foreach ($tables as $entry) {
            $this->newLine();
            $this->info("=== {$entry['label']} ({$entry['table']}.{$entry['field']}) ===");

            $rows = DB::table($entry['table'])->select('id', 'name', $entry['field'])->get();

            if ($rows->isEmpty()) {
                $this->info('Tidak ada data.');
                continue;
            }

            foreach ($rows as $row) {
                $value = $row->{$entry['field']};
                if (empty($value)) {
                    continue;
                }

                try {
                    $plain = $oldEncrypter->decryptString($value);
                    $reEncrypted = $newEncrypter->encryptString($plain);

                    DB::table($entry['table'])
                        ->where('id', $row->id)
                        ->update([$entry['field'] => $reEncrypted]);

                    $this->info("  OK: {$row->name} (ID: {$row->id})");
                    $totalSuccess++;
                } catch (\Exception $e) {
                    $this->error("  GAGAL: {$row->name} (ID: {$row->id}) — {$e->getMessage()}");
                    $totalFailed++;
                }
            }
        }

        $this->newLine();
        $this->info("Selesai. Berhasil: {$totalSuccess}, Gagal: {$totalFailed}");

        if ($totalFailed > 0) {
            $this->warn('Ada yang gagal — kemungkinan old_key salah.');
        }

        return $totalFailed > 0 ? 1 : 0;
    }
}
