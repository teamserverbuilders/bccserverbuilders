<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OcrLog extends Model
{
    use HasFactory;

    protected $fillable = ['ocr_result_id', 'user_id', 'action', 'notes', 'changes'];

    protected $casts = [
        'changes' => 'array',
    ];

    public function ocrResult()
    {
        return $this->belongsTo(OcrResult::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
