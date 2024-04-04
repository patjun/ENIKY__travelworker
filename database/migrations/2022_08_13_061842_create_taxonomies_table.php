<?php

use App\Models\Website;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('term_id');
            $table->string('term_name', 200);
            $table->string('term_taxonomy', 32);
            $table->unsignedBigInteger('term_parent_id');
            $table->bigInteger('term_count');

            $table->foreignIdFor(Website::class);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('taxonomies');
    }
};
