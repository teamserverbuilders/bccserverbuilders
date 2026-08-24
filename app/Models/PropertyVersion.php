<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tax_declaration_id', 'version_number', 'data_snapshot', 'change_summary', 'created_by',
    ];

    protected $casts = [
        'data_snapshot' => 'array',
    ];

    public function taxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
