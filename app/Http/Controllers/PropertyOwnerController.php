<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FieldAppraisal;
use App\Models\PropertyOwner;
use Illuminate\Http\Request;

class PropertyOwnerController extends Controller
{
    public function index(Request $request)
    {
        $query = PropertyOwner::withCount('taxDeclarations')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('owner_name', 'like', "%{$request->search}%")
                        ->orWhere('tin', 'like', "%{$request->search}%")
                        ->orWhere('contact_number', 'like', "%{$request->search}%")
                        ->orWhere('address', 'like', "%{$request->search}%");
                });
            })
            ->latest();

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'owner_name' => 'required|string|max:255',
            'address' => 'nullable|string',
        ]);

        $owner = PropertyOwner::create($request->only([
            'owner_name', 'co_owner_name', 'tin', 'sex', 'civil_status', 'citizenship',
            'birth_date', 'address', 'contact_number', 'email',
        ]));

        return response()->json($owner, 201);
    }

    public function show(PropertyOwner $propertyOwner)
    {
        $propertyOwner->load([
            'taxDeclarations' => fn ($q) => $q
                ->with(['classification', 'barangay', 'municipality'])
                ->latest(),
            'ownershipHistories' => fn ($q) => $q
                ->with([
                    'taxDeclaration' => fn ($td) => $td
                        ->with(['classification:id,name', 'barangay:id,name', 'municipality:id,name']),
                ]),
        ]);

        $tdIds = $propertyOwner->taxDeclarations->pluck('id')->filter()->all();
        $ownerName = trim((string) $propertyOwner->owner_name);

        $fieldAppraisals = FieldAppraisal::query()
            ->without(['landRows', 'plantRows', 'assessmentRows'])
            ->with(['taxDeclaration:id,td_number,arp_number', 'assessor:id,name'])
            ->where(function ($q) use ($tdIds, $ownerName) {
                if (!empty($tdIds)) {
                    $q->whereIn('tax_declaration_id', $tdIds);
                }
                if ($ownerName !== '') {
                    $method = !empty($tdIds) ? 'orWhereRaw' : 'whereRaw';
                    $q->{$method}('LOWER(owner_name) = ?', [mb_strtolower($ownerName)]);
                }
                // Avoid matching everything if neither filter applies
                if (empty($tdIds) && $ownerName === '') {
                    $q->whereRaw('1 = 0');
                }
            })
            ->latest()
            ->get();

        $previouslyOwned = $propertyOwner->ownershipHistories
            ->filter(fn ($h) => $h->taxDeclaration)
            ->map(function ($h) {
                $td = $h->taxDeclaration;
                return [
                    'id' => $h->id,
                    'tax_declaration_id' => $td->id,
                    'td_number' => $td->td_number,
                    'arp_number' => $td->arp_number,
                    'status' => $td->status,
                    'classification' => $td->classification,
                    'barangay' => $td->barangay,
                    'municipality' => $td->municipality,
                    'transfer_date' => $h->transfer_date,
                    'transfer_reason' => $h->transfer_reason,
                    'effective_from' => $h->effective_from,
                    'effective_to' => $h->effective_to,
                    'owner_name_snapshot' => $h->owner_name,
                ];
            })
            ->values();

        $data = $propertyOwner->toArray();
        $data['field_appraisals'] = $fieldAppraisals;
        $data['previously_owned_declarations'] = $previouslyOwned;
        $data['tax_declarations_count'] = $propertyOwner->taxDeclarations->count();
        $data['field_appraisals_count'] = $fieldAppraisals->count();
        $data['previously_owned_count'] = $previouslyOwned->count();

        return response()->json($data);
    }

    public function update(Request $request, PropertyOwner $propertyOwner)
    {
        $propertyOwner->update($request->only([
            'owner_name', 'co_owner_name', 'tin', 'sex', 'civil_status', 'citizenship',
            'birth_date', 'address', 'contact_number', 'email',
        ]));

        return response()->json($propertyOwner->fresh());
    }

    public function destroy(PropertyOwner $propertyOwner)
    {
        $propertyOwner->delete();
        return response()->json(['message' => 'Owner deleted.']);
    }
}
