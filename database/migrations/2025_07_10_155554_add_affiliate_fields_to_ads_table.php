<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->boolean('is_affiliate')->default(false)->after('status');
            $table->string('affiliate_url')->nullable()->after('is_affiliate');
            $table->string('affiliate_source')->nullable()->after('affiliate_url');
            $table->unsignedBigInteger('click_count')->default(0)->after('affiliate_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['is_affiliate', 'affiliate_url', 'affiliate_source', 'click_count']);
        });
    }
};
