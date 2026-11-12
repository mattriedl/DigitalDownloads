<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BannerPromote ads: instead of pasted code, an ad stores the BannerPromote spot name
 * (leaderboard, medium-rectangle, ...) and an optional smaller spot used on phones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('spot', 40)->nullable()->after('code');
            $table->string('mobile_spot', 40)->nullable()->after('spot');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['spot', 'mobile_spot']);
        });
    }
};
