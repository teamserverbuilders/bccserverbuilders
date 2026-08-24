<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_declarations', function (Blueprint $table) {
            $table->text('machinery_description')->nullable()->after('building_description');
            $table->string('others_specify')->nullable()->after('machinery_description');
            $table->decimal('rounded_assessed_value', 15, 2)->nullable()->after('assessed_value');
            $table->json('valuation_rows')->nullable()->after('rounded_assessed_value');
            $table->json('assessment_rows')->nullable()->after('valuation_rows');
        });
    }

    public function down(): void
    {
        Schema::table('tax_declarations', function (Blueprint $table) {
            $table->dropColumn([
                'machinery_description',
                'others_specify',
                'rounded_assessed_value',
                'valuation_rows',
                'assessment_rows',
            ]);
        });
    }
};
