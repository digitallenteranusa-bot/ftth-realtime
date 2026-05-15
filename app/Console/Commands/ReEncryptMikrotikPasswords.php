<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;

class ReEncryptMikrotikPasswords extends Command
{
    protected $signature = 'mikrotik:reencrypt {old_key : The old APP_KEY (base64:... format)}';
    protected $description = 'Re-encrypt Mikrotik api_password from old APP_KEY to current APP_KEY';

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

        $mikrotiks = DB::table('mikrotiks')->select('id', 'name', 'api_password')->get();

        if ($mikrotiks->isEmpty()) {
            $this->info('Tidak ada data mikrotik.');
            return 0;
        }

        $success = 0;
        $failed = 0;

        foreach ($mikrotiks as $row) {
            if (empty($row->api_password)) {
                continue;
            }

            try {
                $plainPassword = $oldEncrypter->decryptString($row->api_password);
                $newEncrypted = $newEncrypter->encryptString($plainPassword);

                DB::table('mikrotiks')
                    ->where('id', $row->id)
                    ->update(['api_password' => $newEncrypted]);

                $this->info("OK: {$row->name} (ID: {$row->id})");
                $success++;
            } catch (\Exception $e) {
                $this->error("GAGAL: {$row->name} (ID: {$row->id}) — {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Selesai. Berhasil: {$success}, Gagal: {$failed}");

        if ($failed > 0) {
            $this->warn('Ada yang gagal — kemungkinan old_key salah untuk beberapa record.');
        }

        return $failed > 0 ? 1 : 0;
    }
}
