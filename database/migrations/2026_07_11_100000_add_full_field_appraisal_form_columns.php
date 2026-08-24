<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_appraisals', function (Blueprint $table) {
            // Property Location
            $table->string('property_street')->nullable()->after('inspection_location');
            $table->string('property_barangay')->nullable()->after('property_street');
            $table->string('property_municipality')->nullable()->after('property_barangay');
            $table->string('property_province')->nullable()->after('property_municipality');

            // Property Boundaries
            $table->string('boundary_north')->nullable()->after('property_province');
            $table->string('boundary_east')->nullable()->after('boundary_north');
            $table->string('boundary_south')->nullable()->after('boundary_east');
            $table->string('boundary_west')->nullable()->after('boundary_south');

            // Land Appraisal totals
            $table->decimal('land_total_area', 15, 6)->nullable()->after('boundary_west');
            $table->decimal('land_total_base_market_value', 15, 2)->nullable()->after('land_total_area');

            // Plants and Trees Appraisal totals
            $table->decimal('plant_total_area', 15, 6)->nullable()->after('land_total_base_market_value');
            $table->unsignedInteger('plant_total_non_fb')->nullable()->after('plant_total_area');
            $table->unsignedInteger('plant_total_fb')->nullable()->after('plant_total_non_fb');
            $table->unsignedInteger('plant_total_count')->nullable()->after('plant_total_fb');
            $table->decimal('plant_total_base_market_value', 15, 2)->nullable()->after('plant_total_count');

            // Value Adjustment Factors for Agricultural Lands
            $table->decimal('adj_along_road', 8, 2)->nullable()->after('plant_total_base_market_value');
            $table->decimal('adj_kms_weather_road', 8, 2)->nullable()->after('adj_along_road');
            $table->decimal('adj_kms_to_market', 8, 2)->nullable()->after('adj_kms_weather_road');
            $table->decimal('adj_total_adjustments', 8, 2)->nullable()->after('adj_kms_to_market');
            $table->decimal('adj_total_percentage', 8, 2)->nullable()->after('adj_total_adjustments');

            // Property Assessment totals
            $table->decimal('total_adjusted_market_value', 15, 2)->nullable()->after('adj_total_percentage');
            $table->decimal('rounded_assessed_value', 15, 2)->nullable()->after('total_adjusted_market_value');

            // Previous owner / taxability / effectivity
            $table->string('previous_owner')->nullable()->after('rounded_assessed_value');
            $table->decimal('previous_assessed_value', 15, 2)->nullable()->after('previous_owner');
            $table->string('taxability', 20)->nullable()->after('previous_assessed_value');
            $table->string('effectivity_year', 10)->nullable()->after('taxability');
            $table->string('effectivity_quarter', 20)->nullable()->after('effectivity_year');

            // Signatures — Appraised By
            $table->string('appraised_by_name')->nullable()->after('effectivity_quarter');
            $table->string('appraised_by_title')->nullable()->after('appraised_by_name');
            $table->date('appraised_by_date')->nullable()->after('appraised_by_title');

            // Signatures — Assessed By
            $table->string('assessed_by_name')->nullable()->after('appraised_by_date');
            $table->string('assessed_by_title')->nullable()->after('assessed_by_name');
            $table->date('assessed_by_date')->nullable()->after('assessed_by_title');

            // Signatures — Recommending Approval
            $table->string('recommending_name')->nullable()->after('assessed_by_date');
            $table->string('recommending_title')->nullable()->after('recommending_name');
            $table->date('recommending_date')->nullable()->after('recommending_title');

            // Signatures — Approved
            $table->string('approved_by_name')->nullable()->after('recommending_date');
            $table->string('approved_by_title')->nullable()->after('approved_by_name');
            $table->date('approved_by_date')->nullable()->after('approved_by_title');

            // Memoranda
            $table->text('memoranda')->nullable()->after('approved_by_date');

            // References and Posting Summary
            $table->string('ref_pin', 100)->nullable()->after('memoranda');
            $table->string('ref_arp_no', 100)->nullable()->after('ref_pin');
            $table->string('ref_ar_page_no', 100)->nullable()->after('ref_arp_no');

            $table->date('posting_pin_date')->nullable()->after('ref_ar_page_no');
            $table->string('posting_pin_clerk', 50)->nullable()->after('posting_pin_date');
            $table->string('posting_pin_inspection')->nullable()->after('posting_pin_clerk');

            $table->date('posting_arp_date')->nullable()->after('posting_pin_inspection');
            $table->string('posting_arp_clerk', 50)->nullable()->after('posting_arp_date');
            $table->string('posting_arp_inspection')->nullable()->after('posting_arp_clerk');

            $table->date('posting_ar_page_date')->nullable()->after('posting_arp_inspection');
            $table->string('posting_ar_page_clerk', 50)->nullable()->after('posting_ar_page_date');
            $table->string('posting_ar_page_inspection')->nullable()->after('posting_ar_page_clerk');
        });

        // Land Appraisal rows
        Schema::create('field_appraisal_land_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_appraisal_id')->constrained('field_appraisals')->cascadeOnDelete();
            $table->string('classification_kind')->nullable();
            $table->string('sub_class', 50)->nullable();
            $table->string('actual_use')->nullable();
            $table->decimal('area', 15, 6)->nullable();
            $table->decimal('unit_value', 15, 2)->nullable();
            $table->decimal('base_market_value', 15, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Plants and Trees Appraisal rows
        Schema::create('field_appraisal_plant_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_appraisal_id')->constrained('field_appraisals')->cascadeOnDelete();
            $table->string('kind')->nullable();
            $table->string('prod_class', 50)->nullable();
            $table->decimal('area_planted', 15, 6)->nullable();
            $table->unsignedInteger('non_fb')->nullable();
            $table->unsignedInteger('fb')->nullable();
            $table->unsignedInteger('total')->nullable();
            $table->decimal('unit_value', 15, 2)->nullable();
            $table->decimal('base_market_value', 15, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Property Assessment rows
        Schema::create('field_appraisal_assessment_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_appraisal_id')->constrained('field_appraisals')->cascadeOnDelete();
            $table->string('classification')->nullable();
            $table->decimal('adjusted_market_value', 15, 2)->nullable();
            $table->decimal('assessment_level', 8, 2)->nullable();
            $table->decimal('assessed_value', 15, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_appraisal_assessment_rows');
        Schema::dropIfExists('field_appraisal_plant_rows');
        Schema::dropIfExists('field_appraisal_land_rows');

        Schema::table('field_appraisals', function (Blueprint $table) {
            $table->dropColumn([
                'property_street', 'property_barangay', 'property_municipality', 'property_province',
                'boundary_north', 'boundary_east', 'boundary_south', 'boundary_west',
                'land_total_area', 'land_total_base_market_value',
                'plant_total_area', 'plant_total_non_fb', 'plant_total_fb', 'plant_total_count', 'plant_total_base_market_value',
                'adj_along_road', 'adj_kms_weather_road', 'adj_kms_to_market', 'adj_total_adjustments', 'adj_total_percentage',
                'total_adjusted_market_value', 'rounded_assessed_value',
                'previous_owner', 'previous_assessed_value', 'taxability', 'effectivity_year', 'effectivity_quarter',
                'appraised_by_name', 'appraised_by_title', 'appraised_by_date',
                'assessed_by_name', 'assessed_by_title', 'assessed_by_date',
                'recommending_name', 'recommending_title', 'recommending_date',
                'approved_by_name', 'approved_by_title', 'approved_by_date',
                'memoranda',
                'ref_pin', 'ref_arp_no', 'ref_ar_page_no',
                'posting_pin_date', 'posting_pin_clerk', 'posting_pin_inspection',
                'posting_arp_date', 'posting_arp_clerk', 'posting_arp_inspection',
                'posting_ar_page_date', 'posting_ar_page_clerk', 'posting_ar_page_inspection',
            ]);
        });
    }
};
