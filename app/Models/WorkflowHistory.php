<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowHistory extends Model
{
    use HasFactory;

    protected $table = 'workflow_history';

    protected $fillable = [
        'tax_declaration_id', 'from_status', 'to_status', 'action', 'remarks', 'performed_by',
    ];

    public function taxDeclaration()
    {
        return $this->belongsTo(TaxDeclaration::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
