<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuplicateRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_declaration_id', 'duplicate_td_id', 'similarity_score', 'matched_fields',
        'status', 'reviewed_by', 'reviewed_at', 'notes',
    ];

    protected $casts = [
        'matched_fields' => 'array',
        'similarity_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function taxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class);
    }

    public function duplicateTd()
    {
        return $this->belongsTo(TaxDeclaration::class, 'duplicate_td_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
