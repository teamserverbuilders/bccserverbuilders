<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OwnershipHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tax_declaration_ownership_histories';

    protected $fillable = [
        'tax_declaration_id',
        'new_tax_declaration_id',
        'new_td_number',
        'new_arp_number',
        'owner_id',
        'owner_name',
        'owner_tin',
        'owner_address',
        'owner_telephone',
        'effective_from',
        'effective_to',
        'transfer_date',
        'transfer_reason',
        'remarks',
        'previous_td_number',
        'previous_av',
        'transferred_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'transfer_date' => 'date',
        'previous_av' => 'decimal:2',
    ];

    public function taxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class);
    }

    public function newTaxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class, 'new_tax_declaration_id');
    }

    public function owner()
    {
        return $this->belongsTo(PropertyOwner::class, 'owner_id');
    }

    public function transferredBy()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
