<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['patient', 'provider', 'admin'])->default('patient');
            $table->json('provider_fields')->nullable(); // For provider-specific data like specialization, license_number
            $table->rememberToken();
            $table->timestamps();

            $table->index(['role']);
            $table->index(['email', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};