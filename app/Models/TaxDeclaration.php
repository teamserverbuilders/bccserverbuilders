<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxDeclaration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'td_number', 'arp_number', 'property_index_number',
        'lot_number', 'block_number', 'survey_number', 'title_number',

        // Owner
        'owner_id', 'owner_tin', 'owner_address', 'owner_telephone',

        // Administrator / Beneficial User
        'administrator_name', 'administrator_tin', 'administrator_address', 'administrator_telephone',

        // Location
        'municipality_id', 'barangay_id', 'property_street',

        // Title details
        'oct_tct_cloa_no', 'cct', 'title_date',

        // Boundaries
        'boundary_north', 'boundary_east', 'boundary_south', 'boundary_west',

        // Kind of property
        'kind_of_property',

        // Building / machinery / others
        'no_of_storeys', 'building_description', 'machinery_description', 'others_specify',

        // Classification & use
        'classification_id', 'assessment_level_id', 'tax_type_id',
        'taxability', 'status', 'current_use', 'actual_use',

        // Areas & values
        'land_area', 'building_area', 'base_market_value',
        'market_value', 'adjusted_market_value',
        'assessed_value', 'rounded_assessed_value', 'assessed_value_words', 'assessment_level',
        'valuation_rows', 'assessment_rows',

        // Effectivity
        'effectivity_date', 'effectivity_quarter', 'effectivity_year',

        // Previous TD
        'previous_td_number', 'previous_owner', 'previous_av',

        // Approval & meta
        'date_issued', 'remarks', 'memoranda',
        'approved_by', 'approved_at', 'approved_by_name',
        'is_locked', 'locked_by', 'locked_at',
        'created_by', 'updated_by', 'qr_code', 'version',
    ];

    protected $casts = [
        'effectivity_date'      => 'date',
        'date_issued'           => 'date',
        'title_date'            => 'date',
        'is_locked'             => 'boolean',
        'locked_at'             => 'datetime',
        'approved_at'           => 'datetime',
        'market_value'          => 'decimal:2',
        'base_market_value'     => 'decimal:2',
        'adjusted_market_value' => 'decimal:2',
        'assessed_value'        => 'decimal:2',
        'rounded_assessed_value'=> 'decimal:2',
        'previous_av'           => 'decimal:2',
        'land_area'             => 'decimal:4',
        'building_area'         => 'decimal:4',
        'assessment_level'      => 'decimal:2',
        'kind_of_property'      => 'array',
        'valuation_rows'        => 'array',
        'assessment_rows'       => 'array',
    ];

    protected static function booted(): void
    {
        static::deleting(function (TaxDeclaration $td) {
            if ($td->isForceDeleting()) {
                return;
            }
            // Soft-delete related property records so they appear in Archive
            $td->location()->delete();
            $td->improvements()->delete();
            $td->versions()->get()->each(fn (PropertyVersion $v) => $v->delete());
            $td->ownershipHistory()->get()->each(fn (OwnershipHistory $h) => $h->delete());
        });

        static::restoring(function (TaxDeclaration $td) {
            PropertyLocation::onlyTrashed()->where('tax_declaration_id', $td->id)->restore();
            PropertyImprovement::onlyTrashed()->where('tax_declaration_id', $td->id)->restore();
            PropertyVersion::onlyTrashed()->where('tax_declaration_id', $td->id)->restore();
            OwnershipHistory::onlyTrashed()->where('tax_declaration_id', $td->id)->restore();
        });
    }

    public function owner()
    {
        return $this->belongsTo(PropertyOwner::class, 'owner_id');
    }

    public function ownershipHistory()
    {
        return $this->hasMany(OwnershipHistory::class)->orderByDesc('transfer_date')->orderByDesc('id');
    }

    /**
     * The history row (on the old TD) that recorded the transfer which produced this TD.
     * Present only on TDs issued via Transfer Ownership.
     */
    public function issuedFromHistory()
    {
        return $this->hasOne(OwnershipHistory::class, 'new_tax_declaration_id');
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class);
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function assessmentLevel()
    {
        return $this->belongsTo(AssessmentLevel::class);
    }

    public function taxType()
    {
        return $this->belongsTo(TaxType::class);
    }

    public function location()
    {
        return $this->hasOne(PropertyLocation::class);
    }

    public function improvements()
    {
        return $this->hasOne(PropertyImprovement::class);
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class);
    }

    public function documents()
    {
        return $this->hasMany(PropertyDocument::class);
    }

    public function versions()
    {
        return $this->hasMany(PropertyVersion::class);
    }

    public function ocrResults()
    {
        return $this->hasMany(OcrResult::class);
    }

    public function gisLocation()
    {
        return $this->hasOne(GisLocation::class);
    }

    public function fieldAppraisals()
    {
        return $this->hasMany(FieldAppraisal::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function workflowHistory()
    {
        return $this->hasMany(WorkflowHistory::class);
    }

    public function approvalHistory()
    {
        return $this->hasMany(ApprovalHistory::class);
    }

    public function duplicates()
    {
        return $this->hasMany(DuplicateRecord::class);
    }
}
