<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_appraisals', function (Blueprint $table) {
            $table->string('form_template', 32)->default('form_1')->after('appraisal_no');
        });
    }

    public function down(): void
    {
        Schema::table('field_appraisals', function (Blueprint $table) {
            $table->dropColumn('form_template');
        });
    }
};
