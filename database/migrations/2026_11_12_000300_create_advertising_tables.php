<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Advertising: zones (fixed slots in the theme), ads placed in them, and daily stats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_zones', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // used by the theme, e.g. header_leaderboard
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete(); // optional targeting
            $table->string('name');
            $table->string('type', 20)->default('image'); // image, code
            $table->string('image_path')->nullable();
            $table->text('target_url')->nullable();
            $table->longText('code')->nullable(); // AdSense or other network snippet
            $table->unsignedSmallInteger('weight')->default(1); // rotation weight
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('hide_for_members')->default(true);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamps();

            $table->index(['ad_zone_id', 'is_active']);
        });

        Schema::create('ad_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unique(['ad_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_stats');
        Schema::dropIfExists('ads');
        Schema::dropIfExists('ad_zones');
    }
};
