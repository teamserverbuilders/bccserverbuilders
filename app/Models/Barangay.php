<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id', 'name', 'code', 'latitude', 'longitude', 'boundary_polygon', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'boundary_polygon' => 'array',
    ];

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function taxDeclarations()
    {
        return $this->hasMany(TaxDeclaration::class);
    }
}
