<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_declarations', function (Blueprint $table) {
            // Owner details (optional, may already be on property_owners)
            $table->string('owner_tin', 50)->nullable()->after('owner_id');
            $table->string('owner_address')->nullable()->after('owner_tin');
            $table->string('owner_telephone', 30)->nullable()->after('owner_address');

            // Administrator / Beneficial User
            $table->string('administrator_name')->nullable()->after('owner_telephone');
            $table->string('administrator_tin', 50)->nullable()->after('administrator_name');
            $table->string('administrator_address')->nullable()->after('administrator_tin');
            $table->string('administrator_telephone', 30)->nullable()->after('administrator_address');

            // Location of Property (street-level detail)
            $table->string('property_street')->nullable()->after('administrator_telephone');

            // Title details
            $table->string('oct_tct_cloa_no', 100)->nullable()->after('title_number');
            $table->string('cct', 50)->nullable()->after('oct_tct_cloa_no');
            $table->date('title_date')->nullable()->after('cct');

            // Boundaries
            $table->string('boundary_north')->nullable()->after('title_date');
            $table->string('boundary_east')->nullable()->after('boundary_north');
            $table->string('boundary_south')->nullable()->after('boundary_east');
            $table->string('boundary_west')->nullable()->after('boundary_south');

            // Kind of Property Assessed (checkboxes on form)
            $table->json('kind_of_property')->nullable()->after('boundary_west');

            // Building info
            $table->integer('no_of_storeys')->nullable()->after('kind_of_property');
            $table->text('building_description')->nullable()->after('no_of_storeys');

            // Valuation columns
            $table->decimal('base_market_value', 15, 2)->nullable()->after('building_area');
            $table->decimal('adjusted_market_value', 15, 2)->nullable()->after('market_value');

            // Total Assessed Value in words
            $table->string('assessed_value_words')->nullable()->after('assessed_value');

            // Effectivity breakdown
            $table->string('effectivity_quarter', 10)->nullable()->after('effectivity_date');
            $table->string('effectivity_year', 10)->nullable()->after('effectivity_quarter');

            // Cancellation / Previous TD
            $table->string('previous_td_number', 50)->nullable()->after('effectivity_year');
            $table->string('previous_owner')->nullable()->after('previous_td_number');
            $table->decimal('previous_av', 15, 2)->nullable()->after('previous_owner');

            // Memoranda (separate from remarks)
            $table->text('memoranda')->nullable()->after('remarks');

            // Approved by name/title (for display on form - not just user FK)
            $table->string('approved_by_name')->nullable()->after('approved_at');

            // Make classification_id nullable (optional input)
            $table->foreignId('classification_id')->nullable()->change();
            $table->foreignId('municipality_id')->nullable()->change();
            $table->foreignId('barangay_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tax_declarations', function (Blueprint $table) {
            $table->dropColumn([
                'owner_tin', 'owner_address', 'owner_telephone',
                'administrator_name', 'administrator_tin', 'administrator_address', 'administrator_telephone',
                'property_street', 'oct_tct_cloa_no', 'cct', 'title_date',
                'boundary_north', 'boundary_east', 'boundary_south', 'boundary_west',
                'kind_of_property', 'no_of_storeys', 'building_description',
                'base_market_value', 'adjusted_market_value', 'assessed_value_words',
                'effectivity_quarter', 'effectivity_year',
                'previous_td_number', 'previous_owner', 'previous_av',
                'memoranda', 'approved_by_name',
            ]);
        });
    }
};
