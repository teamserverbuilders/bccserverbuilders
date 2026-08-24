<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldAppraisalAssessmentRow extends Model
{
    protected $fillable = [
        'field_appraisal_id',
        'classification',
        'adjusted_market_value',
        'assessment_level',
        'assessed_value',
        'sort_order',
    ];

    protected $casts = [
        'adjusted_market_value' => 'decimal:2',
        'assessment_level' => 'decimal:2',
        'assessed_value' => 'decimal:2',
    ];

    public function fieldAppraisal()
    {
        return $this->belongsTo(FieldAppraisal::class);
    }
}
