<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Files hosted on the site are virus-scanned before anyone can download them. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_links', function (Blueprint $table) {
            // null for external links; pending, clean, infected or unscanned (no scanner installed)
            $table->string('scan_status', 20)->nullable()->after('file_size');
            $table->string('scan_result')->nullable()->after('scan_status');
            $table->timestamp('scanned_at')->nullable()->after('scan_result');
        });
    }

    public function down(): void
    {
        Schema::table('item_links', function (Blueprint $table) {
            $table->dropColumn(['scan_status', 'scan_result', 'scanned_at']);
        });
    }
};
