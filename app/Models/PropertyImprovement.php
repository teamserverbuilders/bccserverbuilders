<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyImprovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tax_declaration_id', 'has_building', 'building_description', 'has_structure',
        'structure_description', 'has_fence', 'fence_description', 'road_access',
        'has_electricity', 'water_source', 'land_improvements', 'other_improvements',
    ];

    protected $casts = [
        'has_building' => 'boolean',
        'has_structure' => 'boolean',
        'has_fence' => 'boolean',
        'has_electricity' => 'boolean',
    ];

    public function taxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class);
    }
}
