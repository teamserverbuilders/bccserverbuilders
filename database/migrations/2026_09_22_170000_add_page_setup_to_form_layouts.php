<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_layouts', function (Blueprint $table) {
            $table->json('page')->nullable()->after('fields');
        });
    }

    public function down(): void
    {
        Schema::table('form_layouts', function (Blueprint $table) {
            $table->dropColumn('page');
        });
    }
};
