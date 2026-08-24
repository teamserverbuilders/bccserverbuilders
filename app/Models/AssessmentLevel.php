<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'classification_id', 'name', 'min_market_value', 'max_market_value', 'assessment_rate', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_market_value' => 'decimal:2',
        'max_market_value' => 'decimal:2',
        'assessment_rate' => 'decimal:2',
    ];

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }
}
