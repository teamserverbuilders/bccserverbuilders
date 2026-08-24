<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OcrResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_declaration_id', 'source_file', 'original_filename', 'source_type', 'raw_text', 'extracted_fields',
        'confidence_score', 'status', 'corrected_fields', 'processed_by', 'reviewed_by',
        'processed_at', 'reviewed_at', 'error_message',
    ];

    protected $casts = [
        'extracted_fields' => 'array',
        'corrected_fields' => 'array',
        'confidence_score' => 'decimal:2',
        'processed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function taxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function logs()
    {
        return $this->hasMany(OcrLog::class);
    }
}
