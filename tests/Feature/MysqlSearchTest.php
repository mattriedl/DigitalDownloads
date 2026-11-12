<?php

namespace Tests\Feature;

use App\Models\Item;
use Database\Seeders\DefaultsSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * MySQL's full-text index only sees committed rows, so this test can't run inside the
 * usual rolled-back transaction: it migrates and commits real data instead.
 */
class MysqlSearchTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([DefaultsSeeder::class, DemoSeeder::class]);
    }

    public function test_mysql_search_matches_partial_words_and_ignores_stopwords(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL full-text only (run with DB_CONNECTION=mysql).');
        }

        $windows = Item::published()->where('title', 'like', 'Windows%')->count();
        $this->assertGreaterThan(0, $windows);
        foreach (['windows', 'wind', 'the windows', 'WINDOWS sample'] as $term) {
            $this->assertSame($windows, Item::published()->search($term)->count(), $term);
        }
        $this->assertSame(0, Item::published()->search('+"()~<>*-')->count());
    }
}
