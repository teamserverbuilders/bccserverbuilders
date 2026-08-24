<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tax_declaration_id', 'document_type', 'title', 'file_path', 'file_name',
        'mime_type', 'file_size', 'version', 'is_verified', 'digital_signature',
        'uploaded_by', 'verified_at', 'verified_by',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function taxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
