<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalHistory extends Model
{
    use HasFactory;

    protected $table = 'approval_history';

    protected $fillable = [
        'tax_declaration_id', 'user_id', 'role_name', 'decision', 'remarks', 'digital_signature',
    ];

    public function taxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
