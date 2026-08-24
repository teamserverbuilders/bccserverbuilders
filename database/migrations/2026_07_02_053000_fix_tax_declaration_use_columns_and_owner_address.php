<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_declarations', function (Blueprint $table) {
            $table->string('current_use', 100)->nullable()->change();
            $table->string('actual_use', 255)->nullable()->change();
        });

        Schema::table('property_owners', function (Blueprint $table) {
            $table->text('address')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('property_owners', function (Blueprint $table) {
            $table->text('address')->nullable(false)->change();
        });

        Schema::table('tax_declarations', function (Blueprint $table) {
            $table->enum('current_use', ['residential', 'commercial', 'agricultural', 'industrial', 'special', 'other'])->nullable()->change();
            $table->enum('actual_use', ['residential', 'commercial', 'agricultural', 'industrial', 'special', 'other'])->nullable()->change();
        });
    }
};
