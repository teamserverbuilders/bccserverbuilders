<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_appraisals', function (Blueprint $table) {
            $table->id();
            $table->string('appraisal_no')->unique();
            $table->foreignId('tax_declaration_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assessor_id')->nullable()->constrained('users')->nullOnDelete();

            // Inspection
            $table->date('inspection_date')->nullable();
            $table->string('inspection_location')->nullable();

            // Land Details
            $table->json('land_details')->nullable();
            // Building Details
            $table->json('building_details')->nullable();
            // Improvement Details
            $table->json('improvement_details')->nullable();

            // GIS Pin (replaces land sketch)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Computation
            $table->json('computation')->nullable();
            $table->decimal('computed_market_value', 15, 2)->nullable();
            $table->decimal('computed_assessed_value', 15, 2)->nullable();

            // Photos & Attachments
            $table->json('photos')->nullable();
            $table->json('attachments')->nullable();

            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'inspected', 'computed', 'approved', 'revision'])->default('draft');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_appraisals');
    }
};
