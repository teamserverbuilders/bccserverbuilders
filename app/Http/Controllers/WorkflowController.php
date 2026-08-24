<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TaxDeclaration;
use App\Models\WorkflowHistory;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    /**
     * Kanban board data: declarations grouped by status + counts.
     */
    public function board()
    {
        $records = TaxDeclaration::with([
            'owner:id,owner_name',
            'classification:id,name,color',
            'barangay:id,name',
        ])
            ->select('id', 'td_number', 'status', 'owner_id', 'classification_id', 'barangay_id', 'updated_at', 'created_at')
            ->latest('updated_at')
            ->get();

        $counts = TaxDeclaration::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'records' => $records,
            'counts' => $counts,
        ]);
    }

    /**
     * Paginated workflow history from workflow_history table.
     */
    public function history(Request $request)
    {
        $query = WorkflowHistory::with([
            'taxDeclaration:id,td_number,status',
            'taxDeclaration.owner:id,owner_name',
            'performedBy:id,name',
        ])
            ->when($request->status, fn ($q) => $q->where('to_status', $request->status))
            ->when($request->action, fn ($q) => $q->where('action', $request->action))
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->whereHas('taxDeclaration', function ($q) use ($search) {
                    $q->where('td_number', 'like', "%{$search}%")
                        ->orWhereHas('owner', fn ($q) => $q->where('owner_name', 'like', "%{$search}%"));
                });
            })
            ->latest();

        return response()->json($query->paginate($request->per_page ?? 20));
    }
}
