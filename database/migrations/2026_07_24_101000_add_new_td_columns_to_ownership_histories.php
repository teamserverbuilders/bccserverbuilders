<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_declaration_ownership_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('new_tax_declaration_id')->nullable()->after('tax_declaration_id');
            $table->string('new_td_number')->nullable()->after('new_tax_declaration_id');
            $table->string('new_arp_number')->nullable()->after('new_td_number');

            $table->foreign('new_tax_declaration_id', 'td_ownership_hist_new_td_fk')
                ->references('id')->on('tax_declarations')->nullOnDelete();
            $table->index('new_tax_declaration_id', 'td_ownership_hist_new_td_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tax_declaration_ownership_histories', function (Blueprint $table) {
            $table->dropForeign('td_ownership_hist_new_td_fk');
            $table->dropIndex('td_ownership_hist_new_td_idx');
            $table->dropColumn(['new_tax_declaration_id', 'new_td_number', 'new_arp_number']);
        });
    }
};
