<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldAppraisalLandRow extends Model
{
    protected $fillable = [
        'field_appraisal_id',
        'classification_kind',
        'sub_class',
        'actual_use',
        'area',
        'unit_value',
        'base_market_value',
        'sort_order',
    ];

    protected $casts = [
        'area' => 'decimal:6',
        'unit_value' => 'decimal:2',
        'base_market_value' => 'decimal:2',
    ];

    public function fieldAppraisal()
    {
        return $this->belongsTo(FieldAppraisal::class);
    }
}
