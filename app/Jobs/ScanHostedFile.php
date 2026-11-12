<?php

namespace App\Jobs;

use App\Models\ItemLink;
use App\Support\Notifier;
use App\Support\VirusScanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/** Scans a newly uploaded download file; infected files are deleted and the link switched off. */
class ScanHostedFile implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $linkId) {}

    public function handle(VirusScanner $scanner): void
    {
        $link = ItemLink::with('item')->find($this->linkId);
        if (! $link || $link->type !== ItemLink::TYPE_HOSTED || ! $link->file_path) {
            return;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($link->file_path)) {
            $link->forceFill(['scan_status' => 'error', 'scan_result' => 'File not found', 'scanned_at' => now()])->saveQuietly();

            return;
        }

        $result = $scanner->scan($disk->path($link->file_path));

        if ($result === null) {
            $link->forceFill(['scan_status' => 'unscanned', 'scan_result' => 'ClamAV is not installed on this server', 'scanned_at' => now()])->saveQuietly();

            return;
        }

        $link->forceFill([
            'scan_status' => $result['status'],
            'scan_result' => $result['detail'] ? mb_substr($result['detail'], 0, 255) : null,
            'scanned_at' => now(),
        ])->saveQuietly();

        if ($result['status'] === VirusScanner::INFECTED) {
            $disk->delete($link->file_path);
            $link->forceFill(['is_active' => false])->saveQuietly();

            Notifier::admin('admin_infected_file', [
                'item_title' => $link->item?->title,
                'file' => basename($link->file_path),
                'virus' => $result['detail'],
                'admin_url' => url('/admin/items/'.$link->item?->slug.'/edit'),
            ]);
        }
    }
}
