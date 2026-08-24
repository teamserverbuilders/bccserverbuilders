<?php

namespace App\Http\Controllers;

use App\Models\PropertyImprovement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyImprovementController extends Controller
{
    public function index(Request $request)
    {
        $query = PropertyImprovement::with([
            'taxDeclaration:id,td_number,arp_number,owner_id,barangay_id',
            'taxDeclaration.owner:id,owner_name',
            'taxDeclaration.barangay:id,name',
        ]);

        $this->applyFilters($query, $request);

        $summaryQuery = PropertyImprovement::query();
        $this->applyFilters($summaryQuery, $request);

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'has_building' => (clone $summaryQuery)->where('has_building', true)->count(),
            'has_electricity' => (clone $summaryQuery)->where('has_electricity', true)->count(),
            'has_fence' => (clone $summaryQuery)->where('has_fence', true)->count(),
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
                    $inner->where('building_description', 'like', "%{$search}%")
                        ->orWhere('structure_description', 'like', "%{$search}%")
                        ->orWhere('fence_description', 'like', "%{$search}%")
                        ->orWhere('land_improvements', 'like', "%{$search}%")
                        ->orWhere('other_improvements', 'like', "%{$search}%")
                        ->orWhere('water_source', 'like', "%{$search}%")
                        ->orWhereHas('taxDeclaration', function ($td) use ($search) {
                            $td->where('td_number', 'like', "%{$search}%")
                                ->orWhere('arp_number', 'like', "%{$search}%")
                                ->orWhereHas('owner', fn ($o) => $o->where('owner_name', 'like', "%{$search}%"));
                        });
                });
            })
            ->when($request->tax_declaration_id, fn ($q) => $q->where('tax_declaration_id', $request->tax_declaration_id))
            ->when($request->road_access, fn ($q) => $q->where('road_access', $request->road_access))
            ->when($request->boolean('has_building'), fn ($q) => $q->where('has_building', true))
            ->when($request->boolean('has_structure'), fn ($q) => $q->where('has_structure', true))
            ->when($request->boolean('has_fence'), fn ($q) => $q->where('has_fence', true))
            ->when($request->boolean('has_electricity'), fn ($q) => $q->where('has_electricity', true));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $improvement = PropertyImprovement::create($data);

        return response()->json(
            $improvement->load([
                'taxDeclaration:id,td_number,arp_number,owner_id,barangay_id',
                'taxDeclaration.owner:id,owner_name',
                'taxDeclaration.barangay:id,name',
            ]),
            201
        );
    }

    public function show(PropertyImprovement $propertyImprovement)
    {
        return response()->json(
            $propertyImprovement->load([
                'taxDeclaration.owner',
                'taxDeclaration.barangay',
                'taxDeclaration.municipality',
                'taxDeclaration.classification',
            ])
        );
    }

    public function update(Request $request, PropertyImprovement $propertyImprovement)
    {
        $data = $this->validated($request, $propertyImprovement->id);

        $propertyImprovement->update($data);

        return response()->json(
            $propertyImprovement->fresh()->load([
                'taxDeclaration:id,td_number,arp_number,owner_id,barangay_id',
                'taxDeclaration.owner:id,owner_name',
                'taxDeclaration.barangay:id,name',
            ])
        );
    }

    public function destroy(PropertyImprovement $propertyImprovement)
    {
        $propertyImprovement->delete();

        return response()->json(['message' => 'Property improvement moved to archive.']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'tax_declaration_id' => [
                'required',
                'exists:tax_declarations,id',
                Rule::unique('property_improvements', 'tax_declaration_id')
                    ->whereNull('deleted_at')
                    ->ignore($ignoreId),
            ],
            'has_building' => 'boolean',
            'building_description' => 'nullable|string',
            'has_structure' => 'boolean',
            'structure_description' => 'nullable|string',
            'has_fence' => 'boolean',
            'fence_description' => 'nullable|string',
            'road_access' => 'nullable|in:paved,unpaved,none',
            'has_electricity' => 'boolean',
            'water_source' => 'nullable|string|max:255',
            'land_improvements' => 'nullable|string',
            'other_improvements' => 'nullable|string',
        ]);
    }
}
