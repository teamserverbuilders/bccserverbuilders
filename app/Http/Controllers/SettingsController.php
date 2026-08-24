<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\Barangay;
use App\Models\Classification;
use App\Models\AssessmentLevel;
use App\Models\TaxType;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    public function municipalities()
    {
        return response()->json(Municipality::orderBy('name')->get());
    }

    /**
     * Find or create a municipality from OCR / form location data.
     */
    public function resolveMunicipality(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'province' => 'nullable|string|max:255',
        ]);

        $name = trim($request->name);
        $province = trim($request->province ?? '');

        $existing = Municipality::query()
            ->when($province, fn ($q) => $q->where('province', $province))
            ->where(function ($q) use ($name) {
                $q->where('name', $name)
                    ->orWhere('name', 'like', '%' . $name)
                    ->orWhere('name', 'like', $name . '%');
            })
            ->first();

        if (!$existing && $province) {
            $existing = Municipality::where('name', $name)->first();
        }

        if ($existing) {
            if ($province && !$existing->province) {
                $existing->update(['province' => $province]);
            }
            return response()->json($existing->fresh());
        }

        $municipality = Municipality::create([
            'name' => $name,
            'province' => $province,
            'is_active' => true,
        ]);

        return response()->json($municipality, 201);
    }

    public function barangays()
    {
        return response()->json(Barangay::with('municipality:id,name')->orderBy('name')->get());
    }

    /**
     * Get regions from PSGC API.
     */
    public function psgcRegions()
    {
        try {
            $response = Http::timeout(10)->get('https://psgc.rootscratch.com/region');
            return response()->json($response->json() ?? []);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * Get provinces for a region from PSGC API.
     */
    public function psgcProvinces(Request $request)
    {
        try {
            $response = Http::timeout(10)->get('https://psgc.rootscratch.com/province', [
                'id' => $request->get('region_id'),
            ]);
            return response()->json($response->json() ?? []);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * Get municipalities/cities for a province from PSGC API.
     */
    public function psgcMunicipalities(Request $request)
    {
        try {
            $provinceId = $request->get('province_id');
            $response = Http::timeout(15)->get('https://psgc.rootscratch.com/municipal-city', [
                'id' => $provinceId,
            ]);
            return response()->json($response->json() ?? []);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * Get barangays for a municipality from PSGC API.
     */
    public function psgcBarangays(Request $request)
    {
        try {
            $response = Http::timeout(15)->get('https://psgc.rootscratch.com/barangay', [
                'id' => $request->get('city_id'),
            ]);
            return response()->json($response->json() ?? []);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * Add a barangay from PSGC selection — geocodes to get lat/lng.
     */
    public function storeBarangay(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'municipality_name' => 'nullable|string',
            'province_name' => 'nullable|string',
            'psgc_code' => 'nullable|string',
        ]);

        $munName = trim($request->municipality_name ?? '') ?: 'Unknown';
        $province = trim($request->province_name ?? '');

        $municipality = Municipality::firstOrCreate(
            ['name' => $munName],
            ['province' => $province, 'is_active' => true]
        );

        if ($province && !$municipality->province) {
            $municipality->update(['province' => $province]);
        }

        // Geocode using Nominatim (OpenStreetMap) for coordinates
        $lat = null;
        $lng = null;
        $searchStr = $request->name . ', ' . ($request->municipality_name ?? '') . ', ' . ($request->province_name ?? '') . ', Philippines';

        try {
            $geoResponse = Http::withHeaders([
                'User-Agent' => 'TDMS-Assessor/1.0',
            ])->timeout(8)->get('https://nominatim.openstreetmap.org/search', [
                'q'      => $searchStr,
                'format' => 'json',
                'limit'  => 1,
            ]);

            if ($geoResponse->successful()) {
                $geoData = $geoResponse->json();
                if (!empty($geoData[0])) {
                    $lat = (float) $geoData[0]['lat'];
                    $lng = (float) $geoData[0]['lon'];
                }
            }
        } catch (\Exception $e) {
            // Geocoding is optional — continue without coordinates
        }

        $barangay = Barangay::create([
            'name'            => $request->name,
            'municipality_id' => $municipality->id,
            'code'            => $request->psgc_code,
            'latitude'        => $lat,
            'longitude'       => $lng,
            'is_active'       => true,
        ]);

        return response()->json($barangay->load('municipality:id,name'), 201);
    }

    /**
     * Bulk import every barangay under a given municipality (skips geocoding for speed).
     * Idempotent — existing (name + municipality_id) rows are left alone.
     */
    public function bulkStoreBarangays(Request $request)
    {
        $request->validate([
            'municipality_name' => 'required|string',
            'province_name' => 'nullable|string',
            'barangays' => 'required|array|min:1',
            'barangays.*.name' => 'required|string',
            'barangays.*.psgc_code' => 'nullable|string',
        ]);

        $munName = trim($request->municipality_name);
        $province = trim($request->province_name ?? '');

        $municipality = Municipality::firstOrCreate(
            ['name' => $munName],
            ['province' => $province, 'is_active' => true]
        );
        if ($province && !$municipality->province) {
            $municipality->update(['province' => $province]);
        }

        $created = 0;
        $skipped = 0;
        $ids = [];

        DB::transaction(function () use ($request, $municipality, &$created, &$skipped, &$ids) {
            foreach ($request->barangays as $row) {
                $name = trim((string) $row['name']);
                if ($name === '') { $skipped++; continue; }

                $b = Barangay::firstOrCreate(
                    ['name' => $name, 'municipality_id' => $municipality->id],
                    [
                        'code'      => $row['psgc_code'] ?? null,
                        'is_active' => true,
                    ]
                );

                if ($b->wasRecentlyCreated) {
                    $created++;
                } else {
                    $skipped++;
                }
                $ids[] = $b->id;
            }
        });

        $barangays = Barangay::with('municipality:id,name')->whereIn('id', $ids)->get();

        return response()->json([
            'message' => "Imported {$created} new barangays" . ($skipped ? " ({$skipped} already existed)" : '') . '.',
            'created' => $created,
            'skipped' => $skipped,
            'municipality' => $municipality->only(['id', 'name', 'province']),
            'barangays' => $barangays,
        ], 201);
    }

    /**
     * Clear all barangay records from the database.
     */
    public function clearBarangays()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Barangay::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        return response()->json(['message' => 'All barangay records cleared.']);
    }

    public function deleteBarangay(Barangay $barangay)
    {
        $barangay->delete();
        return response()->json(['message' => 'Barangay removed.']);
    }

    public function updateBarangay(Request $request, Barangay $barangay)
    {
        $barangay->update($request->all());
        return response()->json($barangay->fresh());
    }

    public function classifications()
    {
        return response()->json(Classification::with('assessmentLevels')->orderBy('name')->get());
    }

    public function storeClassification(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        return response()->json(Classification::create($request->all()), 201);
    }

    public function assessmentLevels()
    {
        return response()->json(AssessmentLevel::with('classification:id,name')->get());
    }

    public function taxTypes()
    {
        return response()->json(TaxType::where('is_active', true)->get());
    }

    public function storeDepartment(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        return response()->json(Department::create($request->all()), 201);
    }

    public function storePosition(Request $request)
    {
        $request->validate(['name' => 'required|string', 'department_id' => 'required|exists:departments,id']);
        return response()->json(Position::create($request->all()), 201);
    }
}

