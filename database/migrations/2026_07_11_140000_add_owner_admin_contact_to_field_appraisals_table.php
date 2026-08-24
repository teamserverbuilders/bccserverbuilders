<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_appraisals', function (Blueprint $table) {
            $table->string('owner_tin', 50)->nullable()->after('owner_address');
            $table->string('owner_telephone', 30)->nullable()->after('owner_tin');
            $table->string('administrator_tin', 50)->nullable()->after('administrator_address');
            $table->string('administrator_telephone', 30)->nullable()->after('administrator_tin');
        });
    }

    public function down(): void
    {
        Schema::table('field_appraisals', function (Blueprint $table) {
            $table->dropColumn([
                'owner_tin', 'owner_telephone',
                'administrator_tin', 'administrator_telephone',
            ]);
        });
    }
};
