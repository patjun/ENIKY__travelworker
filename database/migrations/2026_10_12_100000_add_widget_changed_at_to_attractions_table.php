<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $columns = [
        'contact_info_changed_at',
        'rating_changed_at',
        'opening_hours_changed_at',
        'accessibility_changed_at',
        'structured_data_changed_at',
        'en_contact_info_changed_at',
        'en_rating_changed_at',
        'en_opening_hours_changed_at',
        'en_accessibility_changed_at',
        'en_structured_data_changed_at',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attractions', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                $table->timestamp($column)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attractions', function (Blueprint $table) {
            $table->dropColumn($this->columns);
        });
    }
};
