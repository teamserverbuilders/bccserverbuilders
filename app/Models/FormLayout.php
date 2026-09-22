<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormLayout extends Model
{
    public const TARGETS = ['tax_declaration', 'field_appraisal'];

    public const PAGE_WIDTH = 794;

    public const PAGE_HEIGHT = 1123;

    public const TYPES = ['header', 'subheader', 'logo', 'text', 'textarea', 'number', 'date', 'checkbox', 'select', 'table', 'line', 'docHeader', 'docFooter', 'pageNumber'];

    protected $fillable = ['name', 'target', 'fields', 'page'];

    protected $casts = [
        'fields' => 'array',
        'page' => 'array',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(FormLayoutEntry::class);
    }
}
