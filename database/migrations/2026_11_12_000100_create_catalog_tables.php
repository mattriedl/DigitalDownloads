<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog: categories, items, download links, screenshots, versions and tags.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_home')->default(true);
            $table->unsignedInteger('items_count')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description', 300)->nullable();
            $table->longText('description')->nullable();
            $table->text('changelog')->nullable();

            // Details shown on the item page
            $table->string('version', 50)->nullable();
            $table->string('file_size', 30)->nullable();
            $table->string('file_type', 50)->nullable();
            $table->string('license', 100)->nullable();
            $table->string('publisher')->nullable();
            $table->string('publisher_url')->nullable();
            $table->string('requirements')->nullable();
            $table->string('platforms')->nullable();

            // Media
            $table->string('thumbnail')->nullable();
            $table->string('thumbnail_credit')->nullable();
            $table->string('video_url')->nullable();
            $table->string('audio_url')->nullable();

            // Counters and ratings
            $table->unsignedBigInteger('download_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            // Flags and publishing
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_members_only')->default(false);
            $table->boolean('allow_comments')->default(true);
            $table->string('status', 20)->default('draft'); // draft, published, scheduled
            $table->timestamp('published_at')->nullable();

            // SEO
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->boolean('noindex')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['is_featured', 'status']);
            $table->index('download_count');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('items', function (Blueprint $table) {
                $table->fullText(['title', 'short_description', 'publisher']);
            });
        }

        // Where a download actually comes from: an external site (most items) or a hosted file.
        Schema::create('item_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('Download');
            $table->string('type', 20)->default('external'); // external, hosted
            $table->text('url')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_size', 30)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('click_count')->default(0);

            // Filled by the nightly dead-link checker
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedSmallInteger('last_status_code')->nullable();
            $table->boolean('is_broken')->default(false);
            $table->unsignedTinyInteger('failed_checks')->default(0);

            $table->timestamps();

            $table->index(['item_id', 'is_active', 'sort_order']);
            $table->index(['is_broken', 'last_checked_at']);
        });

        Schema::create('item_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('item_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('version', 50);
            $table->text('notes')->nullable();
            $table->date('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('item_tag', function (Blueprint $table) {
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['item_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('item_versions');
        Schema::dropIfExists('item_screenshots');
        Schema::dropIfExists('item_links');
        Schema::dropIfExists('items');
        Schema::dropIfExists('categories');
    }
};
