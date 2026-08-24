<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldAppraisalPlantRow extends Model
{
    protected $fillable = [
        'field_appraisal_id',
        'kind',
        'prod_class',
        'area_planted',
        'non_fb',
        'fb',
        'total',
        'unit_value',
        'base_market_value',
        'sort_order',
    ];

    protected $casts = [
        'area_planted' => 'decimal:6',
        'unit_value' => 'decimal:2',
        'base_market_value' => 'decimal:2',
    ];

    public function fieldAppraisal()
    {
        return $this->belongsTo(FieldAppraisal::class);
    }
}
