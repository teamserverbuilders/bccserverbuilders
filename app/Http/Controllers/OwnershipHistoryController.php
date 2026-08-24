<?php

namespace App\Http\Controllers;

use App\Models\OwnershipHistory;
use Illuminate\Http\Request;

class OwnershipHistoryController extends Controller
{
    /**
     * Paginated list of ownership transfers (cancelled TD → new TD).
     */
    public function index(Request $request)
    {
        $query = OwnershipHistory::query()
            ->with([
                'taxDeclaration:id,td_number,arp_number,status,owner_id',
                'newTaxDeclaration:id,td_number,arp_number,status,owner_id',
                'newTaxDeclaration.owner:id,owner_name',
                'owner:id,owner_name,tin',
                'transferredBy:id,name',
            ])
            ->orderByDesc('transfer_date')
            ->orderByDesc('id');

        if ($search = trim((string) $request->query('search', ''))) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('owner_name', 'like', $like)
                    ->orWhere('new_td_number', 'like', $like)
                    ->orWhere('previous_td_number', 'like', $like)
                    ->orWhere('new_arp_number', 'like', $like)
                    ->orWhere('transfer_reason', 'like', $like)
                    ->orWhere('remarks', 'like', $like)
                    ->orWhereHas('taxDeclaration', fn ($q) => $q->where('td_number', 'like', $like))
                    ->orWhereHas('newTaxDeclaration', fn ($q) => $q->where('td_number', 'like', $like)
                        ->orWhereHas('owner', fn ($o) => $o->where('owner_name', 'like', $like)))
                    ->orWhereHas('owner', fn ($q) => $q->where('owner_name', 'like', $like));
            });
        }

        if ($from = $request->query('date_from')) {
            $query->whereDate('transfer_date', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->whereDate('transfer_date', '<=', $to);
        }

        $perPage = max(5, min(50, (int) $request->query('per_page', 15)));

        return response()->json($query->paginate($perPage));
    }

    /**
     * Single transfer record with full context.
     */
    public function show(OwnershipHistory $ownershipHistory)
    {
        $ownershipHistory->load([
            'taxDeclaration.owner:id,owner_name',
            'taxDeclaration.barangay:id,name',
            'taxDeclaration.municipality:id,name',
            'taxDeclaration.classification:id,name',
            'newTaxDeclaration.owner:id,owner_name',
            'newTaxDeclaration.barangay:id,name',
            'newTaxDeclaration.municipality:id,name',
            'newTaxDeclaration.classification:id,name',
            'owner:id,owner_name,tin,address,contact_number',
            'transferredBy:id,name,email',
        ]);

        return response()->json($ownershipHistory);
    }
}
