<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_declaration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_file');
            $table->enum('source_type', ['image', 'pdf']);
            $table->text('raw_text')->nullable();
            $table->json('extracted_fields')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'reviewed'])->default('pending');
            $table->json('corrected_fields')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_results');
    }
};
