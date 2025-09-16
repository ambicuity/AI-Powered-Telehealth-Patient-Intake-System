<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_forms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->enum('status', ['uploaded', 'processing', 'extracted', 'failed'])->default('uploaded');
            $table->enum('source_type', ['pdf', 'image', 'text']);
            $table->string('source_url')->nullable(); // S3 URL for file uploads
            $table->json('extracted_payload')->nullable(); // ML extracted data
            $table->decimal('confidence', 3, 2)->nullable(); // 0.00 to 1.00
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->index(['patient_id', 'status']);
            $table->index(['status']);
            $table->index(['processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_forms');
    }
};