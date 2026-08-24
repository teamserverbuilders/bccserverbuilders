<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_declarations', function (Blueprint $table) {
            $table->id();
            $table->string('td_number', 50)->unique();
            $table->string('arp_number', 50)->nullable()->unique();
            $table->string('property_index_number', 50)->nullable();
            $table->string('lot_number', 50)->nullable();
            $table->string('block_number', 50)->nullable();
            $table->string('survey_number', 50)->nullable();
            $table->string('title_number', 50)->nullable();

            $table->foreignId('owner_id')->constrained('property_owners')->cascadeOnDelete();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_level_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_type_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('taxability', ['taxable', 'exempt'])->default('taxable');
            $table->enum('status', ['draft', 'ocr_processing', 'ocr_review', 'encoder_review', 'assessor_verification', 'supervisor_review', 'approved', 'released', 'archived', 'rejected', 'returned'])->default('draft');
            $table->enum('current_use', ['residential', 'commercial', 'agricultural', 'industrial', 'special', 'other'])->nullable();
            $table->enum('actual_use', ['residential', 'commercial', 'agricultural', 'industrial', 'special', 'other'])->nullable();

            $table->decimal('land_area', 15, 4)->nullable();
            $table->decimal('building_area', 15, 4)->nullable();
            $table->decimal('market_value', 15, 2)->nullable();
            $table->decimal('assessed_value', 15, 2)->nullable();
            $table->decimal('assessment_level', 5, 2)->nullable();

            $table->date('effectivity_date')->nullable();
            $table->date('date_issued')->nullable();
            $table->text('remarks')->nullable();

            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->string('qr_code')->nullable();
            $table->integer('version')->default(1);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_declarations');
    }
};
