<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class CompressPdfAttachment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(private string $path)
    {
    }

    public function handle(): void
    {
        $disk = Storage::disk('public');
        $fullPath = $disk->path($this->path);

        if (!file_exists($fullPath)) {
            return;
        }

        $originalSize = filesize($fullPath);
        $tmpOutput = $fullPath . '.compressed.tmp';

        try {
            $process = new Process([
                config('services.ghostscript.bin'),
                '-sDEVICE=pdfwrite',
                '-dPDFSETTINGS=/ebook',
                '-dCompatibilityLevel=1.4',
                '-dNOPAUSE',
                '-dBATCH',
                '-dQUIET',
                '-sOutputFile=' . $tmpOutput,
                $fullPath,
            ]);
            $process->setTimeout(540);
            $process->run();

            if ($process->isSuccessful() && file_exists($tmpOutput) && filesize($tmpOutput) > 0
                && filesize($tmpOutput) < $originalSize) {
                rename($tmpOutput, $fullPath);
                Log::info("Kompresi PDF berhasil: {$this->path} ({$originalSize} → " . filesize($fullPath) . ' bytes)');
            } else {
                if (file_exists($tmpOutput)) {
                    unlink($tmpOutput);
                }
                Log::info("Kompresi PDF dilewati (tidak ada penghematan ukuran atau Ghostscript gagal): {$this->path}");
            }
        } catch (\Throwable $e) {
            if (file_exists($tmpOutput)) {
                @unlink($tmpOutput);
            }
            Log::warning("Kompresi PDF gagal untuk {$this->path}: " . $e->getMessage());
        }
    }
}
