<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class BackupController extends Controller
{
    protected function backupDir(): string
    {
        $dir = storage_path('app/backups');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        return $dir;
    }

    public function index()
    {
        $dir = $this->backupDir();
        $files = collect(File::files($dir))
            ->filter(fn ($f) => in_array($f->getExtension(), ['sql', 'sqlite', 'db']))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values()
            ->map(fn ($f) => [
                'name' => $f->getFilename(),
                'size' => $f->getSize(),
                'date' => date('Y-m-d H:i:s', $f->getMTime()),
            ]);

        return Inertia::render('Backup/Index', [
            'backups' => $files,
        ]);
    }

    public function create()
    {
        $exitCode = Artisan::call('db:backup');

        if ($exitCode === 0) {
            return redirect()->route('backups.index')
                ->with('success', 'Backup database berhasil dibuat.');
        }

        return redirect()->route('backups.index')
            ->with('error', 'Gagal membuat backup database.');
    }

    public function download(string $filename)
    {
        $path = $this->backupDir() . DIRECTORY_SEPARATOR . $filename;

        if (! File::exists($path) || str_contains($filename, '..')) {
            abort(404);
        }

        return response()->download($path);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'max:512000', 'mimes:sql,sqlite,db'],
        ]);

        $file = $request->file('backup_file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['sql', 'sqlite', 'db'])) {
            return redirect()->route('backups.index')
                ->with('error', 'Format file tidak didukung. Gunakan file .sql, .sqlite, atau .db');
        }

        if ($extension === 'sql') {
            $head = file_get_contents($file->getRealPath(), false, null, 0, 1024);
            if (preg_match('/<\s*(script|html|php|iframe)/i', $head)) {
                return redirect()->route('backups.index')
                    ->with('error', 'File mengandung konten yang tidak valid.');
            }
        }

        $filename = 'upload_' . date('Y-m-d_H-i-s') . '.' . $extension;
        $file->move($this->backupDir(), $filename);

        return redirect()->route('backups.index')
            ->with('success', "File backup '{$filename}' berhasil diupload.");
    }

    public function restore(string $filename)
    {
        $path = $this->backupDir() . DIRECTORY_SEPARATOR . $filename;

        if (! File::exists($path) || str_contains($filename, '..')) {
            return redirect()->route('backups.index')
                ->with('error', 'File backup tidak ditemukan.');
        }

        Artisan::call('db:backup');

        $exitCode = Artisan::call('db:restore', [
            'file' => $path,
            '--force' => true,
        ]);

        if ($exitCode === 0) {
            return redirect()->route('backups.index')
                ->with('success', "Database berhasil direstore dari '{$filename}'. Backup otomatis sebelum restore telah dibuat.");
        }

        return redirect()->route('backups.index')
            ->with('error', 'Gagal merestore database.');
    }

    public function destroy(string $filename)
    {
        $path = $this->backupDir() . DIRECTORY_SEPARATOR . $filename;

        if (! File::exists($path) || str_contains($filename, '..')) {
            return redirect()->route('backups.index')
                ->with('error', 'File backup tidak ditemukan.');
        }

        File::delete($path);

        return redirect()->route('backups.index')
            ->with('success', "File backup '{$filename}' berhasil dihapus.");
    }
}
