<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tax_declaration_id', 'province', 'municipality', 'barangay', 'purok',
        'street', 'zip_code', 'latitude', 'longitude', 'boundary_polygon', 'google_maps_link',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'boundary_polygon' => 'array',
    ];

    public function taxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class);
    }
}
