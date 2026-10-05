<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_declaration_ownership_histories', function (Blueprint $table) {
            $table->string('transaction_code', 32)->nullable()->unique()->after('tax_declaration_id');
        });
    }

    public function down(): void
    {
        Schema::table('tax_declaration_ownership_histories', function (Blueprint $table) {
            $table->dropUnique(['transaction_code']);
            $table->dropColumn('transaction_code');
        });
    }
};
