<?php

namespace App\Http\Controllers;

use App\Models\PropertyLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = PropertyLocation::with([
            'taxDeclaration:id,td_number,arp_number,owner_id,barangay_id',
            'taxDeclaration.owner:id,owner_name',
            'taxDeclaration.barangay:id,name',
        ]);

        $this->applyFilters($query, $request);

        $summaryQuery = PropertyLocation::query();
        $this->applyFilters($summaryQuery, $request);

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'with_coordinates' => (clone $summaryQuery)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->count(),
            'with_maps_link' => (clone $summaryQuery)
                ->whereNotNull('google_maps_link')
                ->where('google_maps_link', '!=', '')
                ->count(),
            'barangays' => (int) ((clone $summaryQuery)
                ->whereNotNull('barangay')
                ->where('barangay', '!=', '')
                ->selectRaw('COUNT(DISTINCT barangay) as aggregate')
                ->value('aggregate') ?? 0),
        ];

        $paginator = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json(array_merge($paginator->toArray(), ['summary' => $summary]));
    }

    private function applyFilters($query, Request $request): void
    {
        $query
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($inner) use ($search) {
                    $inner->where('province', 'like', "%{$search}%")
                        ->orWhere('municipality', 'like', "%{$search}%")
                        ->orWhere('barangay', 'like', "%{$search}%")
                        ->orWhere('purok', 'like', "%{$search}%")
                        ->orWhere('street', 'like', "%{$search}%")
                        ->orWhere('zip_code', 'like', "%{$search}%")
                        ->orWhereHas('taxDeclaration', function ($td) use ($search) {
                            $td->where('td_number', 'like', "%{$search}%")
                                ->orWhere('arp_number', 'like', "%{$search}%")
                                ->orWhereHas('owner', fn ($o) => $o->where('owner_name', 'like', "%{$search}%"));
                        });
                });
            })
            ->when($request->tax_declaration_id, fn ($q) => $q->where('tax_declaration_id', $request->tax_declaration_id))
            ->when($request->barangay, fn ($q) => $q->where('barangay', 'like', "%{$request->barangay}%"))
            ->when($request->municipality, fn ($q) => $q->where('municipality', 'like', "%{$request->municipality}%"))
            ->when($request->boolean('has_coordinates'), function ($q) {
                $q->whereNotNull('latitude')->whereNotNull('longitude');
            });
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $location = PropertyLocation::create($data);

        return response()->json($this->loadRelations($location), 201);
    }

    public function show(PropertyLocation $propertyLocation)
    {
        return response()->json(
            $propertyLocation->load([
                'taxDeclaration.owner',
                'taxDeclaration.barangay',
                'taxDeclaration.municipality',
                'taxDeclaration.classification',
            ])
        );
    }

    public function update(Request $request, PropertyLocation $propertyLocation)
    {
        $data = $this->validated($request, $propertyLocation->id);

        $propertyLocation->update($data);

        return response()->json($this->loadRelations($propertyLocation->fresh()));
    }

    public function destroy(PropertyLocation $propertyLocation)
    {
        $propertyLocation->delete();

        return response()->json(['message' => 'Property location moved to archive.']);
    }

    private function loadRelations(PropertyLocation $location): PropertyLocation
    {
        return $location->load([
            'taxDeclaration:id,td_number,arp_number,owner_id,barangay_id',
            'taxDeclaration.owner:id,owner_name',
            'taxDeclaration.barangay:id,name',
        ]);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'tax_declaration_id' => [
                'required',
                'exists:tax_declarations,id',
                Rule::unique('property_locations', 'tax_declaration_id')
                    ->whereNull('deleted_at')
                    ->ignore($ignoreId),
            ],
            'province' => 'nullable|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'purok' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_link' => 'nullable|string|max:500',
            'boundary_polygon' => 'nullable|array',
        ]);
    }
}
