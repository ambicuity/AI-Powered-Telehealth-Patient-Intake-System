<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actor_user_id')->nullable(); // User who performed the action
            $table->string('action'); // create, update, delete, view, etc.
            $table->string('entity_type'); // User, Patient, Appointment, etc.
            $table->uuid('entity_id')->nullable(); // ID of the entity affected
            $table->json('metadata')->nullable(); // Additional context data (without PHI)
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->foreign('actor_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['actor_user_id']);
            $table->index(['action', 'entity_type']);
            $table->index(['created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};