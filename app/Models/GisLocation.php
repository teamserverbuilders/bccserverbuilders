<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GisLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_declaration_id', 'latitude', 'longitude', 'boundary_polygon',
        'area_computed', 'map_view_type', 'google_maps_link', 'osm_link',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'boundary_polygon' => 'array',
        'area_computed' => 'decimal:4',
    ];

    public function taxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class);
    }
}
