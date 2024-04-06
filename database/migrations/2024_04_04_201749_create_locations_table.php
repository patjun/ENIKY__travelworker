<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void {
		Schema::create( 'locations', function ( Blueprint $table ) {
			$table->id();
            $table->string( 'name' );
            $table->string( 'street')
                ->nullable();
            $table->string( 'zip' )
                ->nullable();
            $table->string( 'city' )
                ->nullable();
            $table->string( 'country' )
                ->nullable();
            $table->decimal( 'latitude', 10, 7)
                ->nullable();
            $table->decimal( 'longitude', 10, 7)
                ->nullable();
			$table->softDeletes();
			$table->timestamps();
		} );
	}

	public function down(): void {
		Schema::dropIfExists( 'locations' );
	}
};
