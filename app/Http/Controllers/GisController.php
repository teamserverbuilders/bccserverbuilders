<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FieldAppraisal;
use App\Models\GisLocation;
use App\Models\TaxDeclaration;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GisController extends Controller
{
    public function index(Request $request)
    {
        $query = GisLocation::with(['taxDeclaration.owner', 'taxDeclaration.classification', 'taxDeclaration.barangay'])
            ->when($request->barangay_id, function ($q) use ($request) {
                $q->whereHas('taxDeclaration', fn($q) => $q->where('barangay_id', $request->barangay_id));
            })
            ->when($request->classification_id, function ($q) use ($request) {
                $q->whereHas('taxDeclaration', fn($q) => $q->where('classification_id', $request->classification_id));
            });

        return response()->json($query->get());
    }

    public function mapProperties(Request $request)
    {
        $locations = GisLocation::with([
            'taxDeclaration:id,td_number,arp_number,status,classification_id,barangay_id,owner_id',
            'taxDeclaration.owner:id,owner_name',
            'taxDeclaration.classification:id,name,color',
            'taxDeclaration.barangay:id,name',
        ])->get()->map(function ($loc) {
            return [
                'id' => $loc->id,
                'td_id' => $loc->tax_declaration_id,
                'td_number' => $loc->taxDeclaration?->td_number,
                'owner' => $loc->taxDeclaration?->owner?->owner_name,
                'classification' => $loc->taxDeclaration?->classification?->name,
                'color' => $loc->taxDeclaration?->classification?->color ?? '#3B82F6',
                'barangay' => $loc->taxDeclaration?->barangay?->name,
                'status' => $loc->taxDeclaration?->status,
                'lat' => (float) $loc->latitude,
                'lng' => (float) $loc->longitude,
                'boundary_polygon' => $loc->boundary_polygon,
            ];
        });

        return response()->json($locations);
    }

    public function store(Request $request)
    {
        if ($request->filled('field_appraisal_id')) {
            $request->validate([
                'field_appraisal_id' => 'required|exists:field_appraisals,id',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ]);

            $appraisal = FieldAppraisal::findOrFail($request->field_appraisal_id);
            $appraisal->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'gis_location_updated',
                'auditable_type' => FieldAppraisal::class,
                'auditable_id' => $appraisal->id,
                'new_values' => [
                    'latitude' => $appraisal->latitude,
                    'longitude' => $appraisal->longitude,
                ],
                'ip_address' => request()->ip(),
            ]);

            return response()->json([
                'field_appraisal_id' => $appraisal->id,
                'latitude' => $appraisal->latitude,
                'longitude' => $appraisal->longitude,
            ], 201);
        }

        $request->validate([
            'tax_declaration_id' => 'required|exists:tax_declarations,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $location = GisLocation::updateOrCreate(
            ['tax_declaration_id' => $request->tax_declaration_id],
            array_merge($request->only([
                'latitude', 'longitude', 'boundary_polygon', 'area_computed', 'map_view_type',
            ]), ['created_by' => Auth::id(), 'updated_by' => Auth::id()])
        );

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'gis_location_updated',
            'auditable_type' => GisLocation::class,
            'auditable_id' => $location->id,
            'new_values' => $location->toArray(),
            'ip_address' => request()->ip(),
        ]);

        return response()->json($location, 201);
    }

    public function show(TaxDeclaration $taxDeclaration)
    {
        return response()->json($taxDeclaration->gisLocation);
    }

    public function showFieldAppraisal(FieldAppraisal $fieldAppraisal)
    {
        return response()->json([
            'id' => $fieldAppraisal->id,
            'appraisal_no' => $fieldAppraisal->appraisal_no,
            'latitude' => $fieldAppraisal->latitude,
            'longitude' => $fieldAppraisal->longitude,
            'tax_declaration_id' => $fieldAppraisal->tax_declaration_id,
        ]);
    }

    /**
     * Load saved land boundary for a tax declaration.
     */
    public function getLand(TaxDeclaration $taxDeclaration)
    {
        $location = GisLocation::where('tax_declaration_id', $taxDeclaration->id)->first();

        if (!$location) {
            return response()->json([
                'tax_declaration_id' => $taxDeclaration->id,
                'td_number' => $taxDeclaration->td_number,
                'coordinates' => [],
                'latitude' => null,
                'longitude' => null,
                'area' => null,
                'perimeter' => null,
                'created_at' => null,
                'updated_at' => null,
            ]);
        }

        $polygon = $location->boundary_polygon ?? [];
        $coordinates = $polygon['coordinates'] ?? (is_array($polygon) && isset($polygon[0]) ? $polygon : []);

        return response()->json([
            'id' => $location->id,
            'tax_declaration_id' => $taxDeclaration->id,
            'td_number' => $taxDeclaration->td_number,
            'coordinates' => $coordinates,
            'latitude' => $location->latitude !== null ? (float) $location->latitude : null,
            'longitude' => $location->longitude !== null ? (float) $location->longitude : null,
            'area' => $location->area_computed !== null ? (float) $location->area_computed : ($polygon['area'] ?? null),
            'perimeter' => $polygon['perimeter'] ?? null,
            'created_at' => $location->created_at,
            'updated_at' => $location->updated_at,
        ]);
    }

    /**
     * Save / replace land polygon for a tax declaration.
     * Payload matches the Land Mapper JSON contract.
     */
    public function saveLand(Request $request)
    {
        $data = $request->validate([
            'tax_declaration_id' => 'required|exists:tax_declarations,id',
            'coordinates' => 'required|array|min:3',
            'coordinates.*' => 'array|size:2',
            'coordinates.*.0' => 'numeric|between:-90,90',
            'coordinates.*.1' => 'numeric|between:-180,180',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'area' => 'required|numeric|min:0',
            'perimeter' => 'nullable|numeric|min:0',
        ]);

        $boundary = [
            'coordinates' => $data['coordinates'],
            'area' => round((float) $data['area'], 4),
            'perimeter' => isset($data['perimeter']) ? round((float) $data['perimeter'], 4) : null,
            'created_at' => now()->toIso8601String(),
        ];

        $location = GisLocation::firstOrNew(['tax_declaration_id' => $data['tax_declaration_id']]);

        if (!$location->exists) {
            $location->created_by = Auth::id();
        }

        $location->fill([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'boundary_polygon' => $boundary,
            'area_computed' => $boundary['area'],
            'map_view_type' => 'street',
            'osm_link' => sprintf(
                'https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=18/%s/%s',
                $data['latitude'],
                $data['longitude'],
                $data['latitude'],
                $data['longitude']
            ),
            'google_maps_link' => sprintf(
                'https://www.google.com/maps?q=%s,%s',
                $data['latitude'],
                $data['longitude']
            ),
            'updated_by' => Auth::id(),
        ]);
        $location->save();

        // Keep PropertyLocation in sync when a record already exists for this TD.
        $propertyLocation = \App\Models\PropertyLocation::where('tax_declaration_id', $data['tax_declaration_id'])->first();
        if ($propertyLocation) {
            $propertyLocation->update([
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'boundary_polygon' => $boundary,
                'google_maps_link' => $location->google_maps_link,
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'land_boundary_saved',
            'auditable_type' => GisLocation::class,
            'auditable_id' => $location->id,
            'new_values' => $boundary + [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ],
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'id' => $location->id,
            'tax_declaration_id' => $location->tax_declaration_id,
            'coordinates' => $boundary['coordinates'],
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'area' => (float) $location->area_computed,
            'perimeter' => $boundary['perimeter'],
            'created_at' => $location->created_at,
            'updated_at' => $location->updated_at,
        ], 201);
    }

    /**
     * Clear land boundary polygon (keeps pin coordinates if present).
     */
    public function deleteLand(TaxDeclaration $taxDeclaration)
    {
        $location = GisLocation::where('tax_declaration_id', $taxDeclaration->id)->first();

        if (!$location) {
            return response()->json(['message' => 'No land boundary found.'], 404);
        }

        $location->update([
            'boundary_polygon' => null,
            'area_computed' => null,
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Land boundary deleted.']);
    }

    public function barangayLayer()
    {
        return response()->json(\App\Models\Barangay::where('is_active', true)
            ->with('municipality:id,name')
            ->select('id', 'name', 'municipality_id', 'latitude', 'longitude', 'boundary_polygon')
            ->get());
    }

    public function heatmap()
    {
        $data = GisLocation::with('taxDeclaration.classification')
            ->get()
            ->map(fn($loc) => [
                'lat' => (float) $loc->latitude,
                'lng' => (float) $loc->longitude,
                'weight' => 1,
            ]);

        return response()->json($data);
    }
}


