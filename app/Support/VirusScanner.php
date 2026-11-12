<?php

namespace App\Support;

use Illuminate\Support\Facades\Process;

/**
 * Scans a file with ClamAV. Uses the clamd daemon (fast) when it is running,
 * otherwise the standalone clamscan. Returns null when ClamAV is not installed.
 *
 * Server setup: apt install clamav clamav-daemon && systemctl enable --now clamav-daemon
 */
class VirusScanner
{
    public const CLEAN = 'clean';

    public const INFECTED = 'infected';

    /** @return array{status: string, detail: ?string}|null */
    public function scan(string $absolutePath): ?array
    {
        $binary = $this->binary();
        if ($binary === null) {
            return null;
        }

        $args = $binary === 'clamdscan'
            ? ['clamdscan', '--no-summary', '--fdpass', $absolutePath]
            : ['clamscan', '--no-summary', $absolutePath];

        $result = Process::timeout(300)->run($args);

        // ClamAV exit codes: 0 = clean, 1 = virus found, 2 = error.
        return match ($result->exitCode()) {
            0 => ['status' => self::CLEAN, 'detail' => null],
            1 => ['status' => self::INFECTED, 'detail' => trim(str_replace($absolutePath.':', '', $result->output()))],
            default => ['status' => 'error', 'detail' => trim($result->errorOutput() ?: $result->output())],
        };
    }

    protected function binary(): ?string
    {
        foreach (['clamdscan', 'clamscan'] as $bin) {
            if (Process::run(['which', $bin])->successful()) {
                return $bin;
            }
        }

        return null;
    }
}
