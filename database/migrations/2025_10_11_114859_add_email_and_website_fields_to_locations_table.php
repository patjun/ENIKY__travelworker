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
        Schema::table('locations', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->string('website_opening_hours')->nullable()->after('website');
            $table->string('website_pricing')->nullable()->after('website_opening_hours');
            $table->string('en_email')->nullable()->after('en_phone');
            $table->string('en_website_opening_hours')->nullable()->after('en_website');
            $table->string('en_website_pricing')->nullable()->after('en_website_opening_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'website_opening_hours',
                'website_pricing',
                'en_email',
                'en_website_opening_hours',
                'en_website_pricing',
            ]);
        });
    }
};
