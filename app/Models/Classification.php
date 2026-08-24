<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'assessment_rate', 'color', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'assessment_rate' => 'decimal:2',
    ];

    public function assessmentLevels()
    {
        return $this->hasMany(AssessmentLevel::class);
    }

    public function taxDeclarations()
    {
        return $this->hasMany(TaxDeclaration::class);
    }
}
