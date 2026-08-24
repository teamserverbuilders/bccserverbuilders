<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FieldAppraisal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'appraisal_no', 'form_template', 'tax_declaration_id', 'assessor_id',
        'inspection_date', 'inspection_location',

        // Form 2 header / identity
        'update_code', 'pin', 'arp_no', 'oct_tct_kot_no', 'survey_no', 'cad_pls_lot_no',
        'owner_name', 'owner_address', 'owner_tin', 'owner_telephone',
        'administrator_name', 'administrator_address', 'administrator_tin', 'administrator_telephone',

        // Property Location
        'property_street', 'property_barangay', 'property_municipality', 'property_province',

        // Boundaries
        'boundary_north', 'boundary_east', 'boundary_south', 'boundary_west',

        // Land sketch
        'land_sketch',

        // Land / plant totals
        'land_total_area', 'land_total_base_market_value',
        'plant_total_area', 'plant_total_non_fb', 'plant_total_fb', 'plant_total_count', 'plant_total_base_market_value',

        // Value adjustments
        'adj_along_road', 'adj_kms_weather_road', 'adj_kms_to_market',
        'adj_total_adjustments', 'adj_total_percentage',

        // Assessment totals
        'total_adjusted_market_value', 'rounded_assessed_value',

        // Previous / taxability / effectivity
        'previous_owner', 'previous_assessed_value', 'taxability',
        'effectivity_year', 'effectivity_quarter',

        // Signatures
        'appraised_by_name', 'appraised_by_title', 'appraised_by_date',
        'assessed_by_name', 'assessed_by_title', 'assessed_by_date',
        'recommending_name', 'recommending_title', 'recommending_date',
        'approved_by_name', 'approved_by_title', 'approved_by_date',

        // Conforme (Form 2)
        'conforme_name', 'conforme_ctc_no', 'conforme_dated', 'conforme_issued_at',

        // Memoranda & references
        'memoranda',
        'ref_pin', 'ref_arp_no', 'ref_ar_page_no',
        'posting_pin_date', 'posting_pin_clerk', 'posting_pin_inspection',
        'posting_arp_date', 'posting_arp_clerk', 'posting_arp_inspection',
        'posting_ar_page_date', 'posting_ar_page_clerk', 'posting_ar_page_inspection',

        // Legacy JSON (kept for backward compatibility)
        'land_details', 'building_details', 'improvement_details', 'computation',

        // GIS / computation / media
        'latitude', 'longitude',
        'computed_market_value', 'computed_assessed_value',
        'photos', 'attachments', 'remarks', 'status',
    ];

    protected $casts = [
        'land_details'        => 'array',
        'building_details'    => 'array',
        'improvement_details' => 'array',
        'computation'         => 'array',
        'photos'              => 'array',
        'attachments'         => 'array',
        'inspection_date'     => 'date',
        'appraised_by_date'   => 'date',
        'assessed_by_date'    => 'date',
        'recommending_date'   => 'date',
        'approved_by_date'    => 'date',
        'conforme_dated'      => 'date',
        'posting_pin_date'    => 'date',
        'posting_arp_date'    => 'date',
        'posting_ar_page_date'=> 'date',
        'computed_market_value'   => 'decimal:2',
        'computed_assessed_value' => 'decimal:2',
        'land_total_area' => 'decimal:6',
        'land_total_base_market_value' => 'decimal:2',
        'plant_total_area' => 'decimal:6',
        'plant_total_base_market_value' => 'decimal:2',
        'adj_along_road' => 'decimal:2',
        'adj_kms_weather_road' => 'decimal:2',
        'adj_kms_to_market' => 'decimal:2',
        'adj_total_adjustments' => 'decimal:2',
        'adj_total_percentage' => 'decimal:2',
        'total_adjusted_market_value' => 'decimal:2',
        'rounded_assessed_value' => 'decimal:2',
        'previous_assessed_value' => 'decimal:2',
    ];

    protected $with = ['landRows', 'plantRows', 'assessmentRows'];

    public function taxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class);
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function landRows()
    {
        return $this->hasMany(FieldAppraisalLandRow::class)->orderBy('sort_order');
    }

    public function plantRows()
    {
        return $this->hasMany(FieldAppraisalPlantRow::class)->orderBy('sort_order');
    }

    public function assessmentRows()
    {
        return $this->hasMany(FieldAppraisalAssessmentRow::class)->orderBy('sort_order');
    }
}
