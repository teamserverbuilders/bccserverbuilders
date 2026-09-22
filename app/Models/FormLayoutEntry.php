<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormLayoutEntry extends Model
{
    protected $fillable = ['form_layout_id', 'values', 'created_by'];

    protected $casts = [
        'values' => 'array',
    ];

    public function layout(): BelongsTo
    {
        return $this->belongsTo(FormLayout::class, 'form_layout_id');
    }
}
