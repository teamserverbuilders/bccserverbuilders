<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_improvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_declaration_id')->constrained()->cascadeOnDelete();
            $table->boolean('has_building')->default(false);
            $table->text('building_description')->nullable();
            $table->boolean('has_structure')->default(false);
            $table->text('structure_description')->nullable();
            $table->boolean('has_fence')->default(false);
            $table->text('fence_description')->nullable();
            $table->enum('road_access', ['paved', 'unpaved', 'none'])->nullable();
            $table->boolean('has_electricity')->default(false);
            $table->string('water_source')->nullable();
            $table->text('land_improvements')->nullable();
            $table->text('other_improvements')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_improvements');
    }
};
