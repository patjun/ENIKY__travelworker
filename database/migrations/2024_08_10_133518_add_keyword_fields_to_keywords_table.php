<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table( 'keywords', function ( Blueprint $table ) {
            $table->string('location_code', 10)->nullable();
            $table->string('language_code', 10)->nullable();
            $table->boolean('search_partners')->default(false);
            $table->string('competition', 10)->nullable();
            $table->integer('competition_index')->nullable();
            $table->integer('search_volume')->nullable();
            $table->decimal('low_top_of_page_bid', 8, 2)->nullable();
            $table->decimal('high_top_of_page_bid', 8, 2)->nullable();
            $table->decimal('cpc', 8, 2)->nullable();
            $table->json('monthly_searches')->nullable();
            $table->json('keyword_annotations')->nullable();
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table( 'keywords', function ( Blueprint $table ) {
            $table->dropColumn('location_code');
            $table->dropColumn('language_code');
            $table->dropColumn('search_partners');
            $table->dropColumn('competition');
            $table->dropColumn('competition_index');
            $table->dropColumn('search_volume');
            $table->dropColumn('low_top_of_page_bid');
            $table->dropColumn('high_top_of_page_bid');
            $table->dropColumn('cpc');
            $table->dropColumn('monthly_searches');
            $table->dropColumn('keyword_annotations');
        } );
    }
};
