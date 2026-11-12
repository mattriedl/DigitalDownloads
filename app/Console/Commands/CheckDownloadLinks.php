<?php

namespace App\Console\Commands;

use App\Models\ItemLink;
use App\Support\Notifier;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Nightly dead-link checker for external download links.
 *
 * A link is flagged broken after 3 failed checks in a row (one bad night is often a
 * temporary outage). "Failed" means not found, gone, a server error or no connection.
 * 401/403/429 answers are treated as inconclusive: many download hosts block automated
 * checks but work fine for people.
 */
class CheckDownloadLinks extends Command
{
    protected $signature = 'links:check
        {--all : Check every external link now, even ones checked recently}
        {--limit=2000 : Maximum links to check in one run}
        {--concurrency=10 : Links checked at the same time}';

    protected $description = 'Check external download links and flag the ones that stopped working';

    private const INCONCLUSIVE = [401, 403, 429];

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    public function handle(): int
    {
        $links = ItemLink::query()
            ->where('type', ItemLink::TYPE_EXTERNAL)
            ->where('is_active', true)
            ->whereNotNull('url')
            ->when(! $this->option('all'), fn ($q) => $q->where(fn ($q) => $q
                ->whereNull('last_checked_at')
                ->orWhere('last_checked_at', '<', now()->subHours(20))))
            ->orderByRaw('last_checked_at is not null')
            ->orderBy('last_checked_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($links->isEmpty()) {
            $this->info('No links due for a check.');

            return self::SUCCESS;
        }

        $wasBroken = $links->where('is_broken', true)->pluck('id')->all();
        $bar = $this->output->createProgressBar($links->count());

        foreach ($links->chunk(max(1, (int) $this->option('concurrency'))) as $chunk) {
            foreach ($this->checkChunk($chunk) as $id => $status) {
                if ($status === 'skip') {
                    continue;
                }
                $chunk->firstWhere('id', $id)->recordCheck($status);
            }
            $bar->advance($chunk->count());
        }
        $bar->finish();
        $this->newLine();

        $nowBroken = ItemLink::whereIn('id', $links->pluck('id'))->where('is_broken', true)->pluck('id')->all();
        $newlyBroken = array_diff($nowBroken, $wasBroken);

        $this->info(sprintf('Checked %d links: %d broken, %d newly broken.', $links->count(), count($nowBroken), count($newlyBroken)));

        if ($newlyBroken) {
            Notifier::admin('admin_broken_links', [
                'count' => count($newlyBroken),
                'admin_url' => url('/admin/items?tableFilters[broken][isActive]=true'),
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, int|null|string> link id => HTTP status, null for no connection, 'skip' when inconclusive
     */
    private function checkChunk(Collection $chunk): array
    {
        $request = fn (Pool $pool, ItemLink $link, string $method) => $pool->as((string) $link->id)
            ->withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => '*/*', 'Range' => 'bytes=0-0'])
            ->timeout(20)
            ->connectTimeout(10)
            ->withOptions(['allow_redirects' => ['max' => 10]])
            ->send($method, $link->url);

        // HEAD first: cheap, and doesn't start large downloads.
        $responses = Http::pool(fn (Pool $pool) => $chunk->map(fn ($link) => $request($pool, $link, 'HEAD'))->all());

        // Some servers refuse HEAD; ask again with GET (the Range header keeps it to one byte).
        $retry = $chunk->filter(function (ItemLink $link) use ($responses) {
            $r = $responses[(string) $link->id] ?? null;

            return ! $r instanceof Response || in_array($r->status(), [400, 403, 404, 405, 501], true);
        });
        if ($retry->isNotEmpty()) {
            $again = Http::pool(fn (Pool $pool) => $retry->map(fn ($link) => $request($pool, $link, 'GET'))->all());
            foreach ($again as $id => $r) {
                $responses[$id] = $r;
            }
        }

        $results = [];
        foreach ($chunk as $link) {
            $results[$link->id] = $this->statusOf($responses[(string) $link->id] ?? null);
        }

        return $results;
    }

    private function statusOf(mixed $response): int|string|null
    {
        if ($response instanceof Response) {
            $status = $response->status();

            return in_array($status, self::INCONCLUSIVE, true) ? 'skip' : $status;
        }

        // Connection errors (DNS failure, refused, timeout) count as a failed check.
        if ($response instanceof ConnectException || $response instanceof Throwable) {
            return null;
        }

        return 'skip';
    }
}
