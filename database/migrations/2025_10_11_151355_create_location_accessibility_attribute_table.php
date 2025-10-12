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
        Schema::create('location_accessibility_attribute', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->constrained()
                ->onDelete('cascade')
                ->name('loc_acc_attr_location_fk');
            $table->foreignId('accessibility_attribute_id')
                ->constrained()
                ->onDelete('cascade')
                ->name('loc_acc_attr_attribute_fk');
            $table->timestamps();

            $table->primary(['location_id', 'accessibility_attribute_id'], 'loc_acc_attr_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_accessibility_attribute');
    }
};
