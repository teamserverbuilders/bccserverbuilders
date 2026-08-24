<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_appraisals', function (Blueprint $table) {
            $table->string('land_sketch')->nullable()->after('boundary_west');
        });
    }

    public function down(): void
    {
        Schema::table('field_appraisals', function (Blueprint $table) {
            $table->dropColumn('land_sketch');
        });
    }
};
