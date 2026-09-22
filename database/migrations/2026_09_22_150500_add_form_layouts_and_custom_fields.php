<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('target')->unique();
            $table->json('fields');
            $table->timestamps();
        });

        Schema::table('tax_declarations', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('memoranda');
        });

        Schema::table('field_appraisals', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('field_appraisals', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });

        Schema::table('tax_declarations', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });

        Schema::dropIfExists('form_layouts');
    }
};
