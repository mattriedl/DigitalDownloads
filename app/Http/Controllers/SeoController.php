<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/** sitemap.xml, robots.txt and the IndexNow key file. */
class SeoController extends Controller
{
    /** Search engines accept up to 50,000 URLs per sitemap; smaller files are quicker to build. */
    public const PER_SITEMAP = 5000;

    public function index(): Response
    {
        $xml = Cache::remember('sitemap_index', now()->addHour(), function () {
            $itemPages = max(1, (int) ceil(Item::published()->where('noindex', false)->count() / self::PER_SITEMAP));
            $lastItem = Item::published()->max('updated_at');

            $maps = [];
            for ($i = 1; $i <= $itemPages; $i++) {
                $maps[] = [route('sitemap.items', $i), $lastItem];
            }
            $maps[] = [route('sitemap.section', 'categories'), Category::max('updated_at')];
            if (Post::published()->exists()) {
                $maps[] = [route('sitemap.section', 'blog'), Post::published()->max('updated_at')];
            }
            $maps[] = [route('sitemap.section', 'pages'), Page::max('updated_at')];

            return view('seo.sitemap-index', ['maps' => $maps])->render();
        });

        return $this->xml($xml);
    }

    public function items(int $page): Response
    {
        $xml = Cache::remember("sitemap_items_{$page}", now()->addHour(), function () use ($page) {
            $items = Item::published()->where('noindex', false)
                ->orderBy('id')
                ->forPage($page, self::PER_SITEMAP)
                ->get(['id', 'slug', 'updated_at', 'thumbnail', 'title']);

            abort_if($items->isEmpty() && $page > 1, 404);

            return view('seo.sitemap', ['urls' => $items->map(fn (Item $item) => [
                'loc' => route('items.show', $item),
                'lastmod' => $item->updated_at,
                'image' => $item->thumbnail ? asset('storage/'.$item->thumbnail) : null,
            ])])->render();
        });

        return $this->xml($xml);
    }

    public function section(string $section): Response
    {
        $xml = Cache::remember("sitemap_{$section}", now()->addHour(), fn () => match ($section) {
            'categories' => $this->categories(),
            'blog' => $this->posts(),
            'pages' => $this->pages(),
        });

        return $this->xml($xml);
    }

    private function categories(): string
    {
        $urls = collect([['loc' => route('home'), 'lastmod' => Item::published()->max('updated_at')]])
            ->merge(Category::active()->get(['id', 'slug', 'updated_at'])->map(fn ($c) => [
                'loc' => route('categories.show', $c), 'lastmod' => $c->updated_at,
            ]))
            ->push(['loc' => route('categories.index'), 'lastmod' => null])
            ->push(['loc' => route('popular'), 'lastmod' => null])
            ->push(['loc' => route('latest'), 'lastmod' => null])
            ->push(['loc' => route('top-rated'), 'lastmod' => null]);

        return view('seo.sitemap', ['urls' => $urls])->render();
    }

    private function posts(): string
    {
        $urls = Post::published()->get(['id', 'slug', 'updated_at', 'image'])->map(fn ($p) => [
            'loc' => route('blog.show', $p),
            'lastmod' => $p->updated_at,
            'image' => $p->image ? asset('storage/'.$p->image) : null,
        ])->prepend(['loc' => route('blog.index'), 'lastmod' => null]);

        return view('seo.sitemap', ['urls' => $urls])->render();
    }

    private function pages(): string
    {
        $urls = Page::active()->get(['slug', 'updated_at'])->map(fn ($p) => [
            'loc' => route('pages.show', $p->slug), 'lastmod' => $p->updated_at,
        ])->push(['loc' => route('contact'), 'lastmod' => null]);

        return view('seo.sitemap', ['urls' => $urls])->render();
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /search',
            'Disallow: /download/*/get',
            'Disallow: /download/*/go/',
            'Disallow: /ads/',
            'Disallow: /auth/',
        ];
        if ($extra = trim((string) setting('robots_txt_extra'))) {
            $lines[] = '';
            $lines[] = $extra;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap.index');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** IndexNow proves site ownership with a text file named after the key, containing the key. */
    public function indexNowKey(string $key): Response
    {
        abort_unless(hash_equals((string) setting('indexnow_key'), $key), 404);

        return response($key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function xml(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
