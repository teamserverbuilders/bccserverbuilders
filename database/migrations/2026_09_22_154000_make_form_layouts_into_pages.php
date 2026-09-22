<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_layouts', function (Blueprint $table) {
            $table->dropUnique(['target']);
            $table->string('name')->default('Untitled form')->after('id');
        });

        Schema::create('form_layout_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_layout_id')->constrained()->cascadeOnDelete();
            $table->json('values');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_layout_entries');

        Schema::table('form_layouts', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->unique('target');
        });
    }
};
