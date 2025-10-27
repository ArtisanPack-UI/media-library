<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Users Table Migration (Testing Only)
 *
 * This migration is for testing purposes only. In production,
 * applications should use their own users table. This migration
 * is only loaded during package tests and will not run in
 * consuming applications.
 *
 * @since 1.0.0
 */
return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create( 'users', function ( Blueprint $table ) {
			$table->id();
			$table->string( 'name' );
			$table->string( 'email' )->unique();
			$table->timestamp( 'email_verified_at' )->nullable();
			$table->string( 'password' );
			$table->rememberToken();
			$table->timestamps();
		} );
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists( 'users' );
	}
};
