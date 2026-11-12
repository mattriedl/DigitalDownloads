<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemLink;
use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * Sample categories and items for staging and local testing.
 * Never run this on a live site: `php artisan db:seed --class=DemoSeeder`.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Software' => ['Windows', 'Mac', 'Linux', 'Utilities'],
            'Mobile Apps' => ['Android', 'iOS'],
            'Games' => ['Action', 'Puzzle', 'Emulators'],
            'Templates' => ['Website Templates', 'WordPress Themes', 'Graphics'],
            'Ebooks' => ['Programming', 'Business'],
            'Music' => ['Royalty-Free', 'Sound Effects'],
        ];

        $icons = ['Software' => 'monitor', 'Mobile Apps' => 'smartphone', 'Games' => 'gamepad', 'Templates' => 'layout', 'Ebooks' => 'book', 'Music' => 'music'];

        $leafCategories = [];
        $order = 0;
        foreach ($tree as $parentName => $children) {
            $parent = Category::firstOrCreate(['name' => $parentName, 'parent_id' => null], [
                'icon' => $icons[$parentName] ?? null,
                'description' => "Free {$parentName} downloads.",
                'sort_order' => $order++,
            ]);
            foreach ($children as $i => $childName) {
                $leafCategories[] = Category::firstOrCreate(['name' => $childName, 'parent_id' => $parent->id], [
                    'description' => "Free {$childName} downloads.",
                    'sort_order' => $i,
                ]);
            }
        }

        $tags = collect(['free', 'open source', 'portable', 'lightweight', 'popular'])
            ->map(fn ($name) => Tag::firstOrCreate(['name' => $name]));

        if (Item::count() > 0) {
            return;
        }

        foreach ($leafCategories as $category) {
            for ($n = 1; $n <= 4; $n++) {
                $item = Item::create([
                    'category_id' => $category->id,
                    'title' => "{$category->name} Sample {$n}",
                    'short_description' => "A sample {$category->name} download used to preview the layout.",
                    'description' => "<p>This is demo content for the {$category->name} category. Replace it with real items from the admin panel.</p>",
                    'version' => "1.{$n}.0",
                    'file_size' => (10 * $n).' MB',
                    'file_type' => 'ZIP',
                    'license' => 'Freeware',
                    'publisher' => 'Example Publisher',
                    'platforms' => 'Windows, Mac',
                    'is_featured' => $n === 1,
                    'is_trending' => $n === 2,
                    'status' => Item::STATUS_PUBLISHED,
                    'published_at' => now()->subDays(random_int(0, 60)),
                ]);

                $item->forceFill([
                    'download_count' => random_int(50, 50000),
                    'view_count' => random_int(500, 90000),
                ])->saveQuietly();

                ItemLink::create([
                    'item_id' => $item->id,
                    'label' => 'Download from official site',
                    'type' => ItemLink::TYPE_EXTERNAL,
                    'url' => 'https://example.com/downloads/'.$item->slug,
                ]);
                ItemLink::create([
                    'item_id' => $item->id,
                    'label' => 'Mirror',
                    'type' => ItemLink::TYPE_EXTERNAL,
                    'url' => 'https://example.org/mirror/'.$item->slug,
                    'sort_order' => 1,
                ]);

                $item->tags()->sync($tags->random(2)->pluck('id'));
            }
        }
    }
}
