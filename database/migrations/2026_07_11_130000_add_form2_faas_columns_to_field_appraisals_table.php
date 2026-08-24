<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_appraisals', function (Blueprint $table) {
            $table->string('update_code')->nullable()->after('form_template');
            $table->string('pin')->nullable()->after('update_code');
            $table->string('arp_no')->nullable()->after('pin');
            $table->string('oct_tct_kot_no')->nullable()->after('arp_no');
            $table->string('survey_no')->nullable()->after('oct_tct_kot_no');
            $table->string('cad_pls_lot_no')->nullable()->after('survey_no');

            $table->string('owner_name')->nullable()->after('cad_pls_lot_no');
            $table->string('owner_address')->nullable()->after('owner_name');
            $table->string('administrator_name')->nullable()->after('owner_address');
            $table->string('administrator_address')->nullable()->after('administrator_name');

            $table->string('conforme_name')->nullable()->after('approved_by_date');
            $table->string('conforme_ctc_no')->nullable()->after('conforme_name');
            $table->date('conforme_dated')->nullable()->after('conforme_ctc_no');
            $table->string('conforme_issued_at')->nullable()->after('conforme_dated');
        });
    }

    public function down(): void
    {
        Schema::table('field_appraisals', function (Blueprint $table) {
            $table->dropColumn([
                'update_code', 'pin', 'arp_no', 'oct_tct_kot_no', 'survey_no', 'cad_pls_lot_no',
                'owner_name', 'owner_address', 'administrator_name', 'administrator_address',
                'conforme_name', 'conforme_ctc_no', 'conforme_dated', 'conforme_issued_at',
            ]);
        });
    }
};
