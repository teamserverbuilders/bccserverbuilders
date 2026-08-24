<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Municipality extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'province', 'region', 'zip_code', 'latitude', 'longitude',
        'logo', 'official_seal', 'address', 'contact_number', 'email', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function barangays()
    {
        return $this->hasMany(Barangay::class);
    }

    public function taxDeclarations()
    {
        return $this->hasMany(TaxDeclaration::class);
    }
}
