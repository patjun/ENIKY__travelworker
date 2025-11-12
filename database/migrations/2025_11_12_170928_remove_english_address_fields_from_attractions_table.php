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
        Schema::table('attractions', function (Blueprint $table) {
            $table->dropColumn(['en_street', 'en_zip', 'en_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attractions', function (Blueprint $table) {
            $table->string('en_street')->nullable()->after('en_name');
            $table->string('en_zip')->nullable()->after('en_street');
            $table->string('en_email')->nullable()->after('en_zip');
        });
    }
};
