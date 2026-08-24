<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyOwner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_name', 'co_owner_name', 'tin', 'sex', 'civil_status', 'citizenship',
        'birth_date', 'address', 'contact_number', 'email',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function taxDeclarations()
    {
        return $this->hasMany(TaxDeclaration::class, 'owner_id');
    }

    public function ownershipHistories()
    {
        return $this->hasMany(OwnershipHistory::class, 'owner_id')->orderByDesc('transfer_date')->orderByDesc('id');
    }
}
