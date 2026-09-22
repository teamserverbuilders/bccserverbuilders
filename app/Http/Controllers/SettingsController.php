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
use Illuminate\Support\Facades\Cache;
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
     * Regions from the public PSGC directory.
     * The previous host (psgc.rootscratch.com) returns HTTP 500, so a cleared
     * browser cache left these dropdowns empty. Results are cached on the server.
     */
    public function psgcRegions()
    {
        try {
            return $this->psgcResponse($this->psgcRows(
                $this->rememberPsgc('https://psgc.gitlab.io/api/regions.json')
            ));
        } catch (\Throwable $e) {
            return $this->psgcError($e);
        }
    }

    /**
     * Provinces for a region. Cities with no province (NCR, Isabela City)
     * are included so the same dropdown can still reach their barangays.
     */
    public function psgcProvinces(Request $request)
    {
        $regionId = preg_replace('/\D/', '', (string) $request->get('region_id'));
        if ($regionId === '') {
            return $this->psgcResponse([]);
        }

        try {
            $rows = $this->psgcRows($this->rememberPsgc(
                "https://psgc.gitlab.io/api/regions/{$regionId}/provinces.json"
            ));
            $cities = $this->rememberPsgc("https://psgc.gitlab.io/api/regions/{$regionId}/cities.json");
            foreach ($cities as $city) {
                if (!is_array($city) || !empty($city['provinceCode']) || empty($city['code'])) {
                    continue;
                }
                $rows[] = [
                    'psgc_id' => (string) $city['code'],
                    'name' => (string) $city['name'],
                ];
            }
            usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

            return $this->psgcResponse($rows);
        } catch (\Throwable $e) {
            return $this->psgcError($e);
        }
    }

    /**
     * Cities and municipalities for a province. A city chosen from the
     * province list (NCR) is returned as the only municipality.
     */
    public function psgcMunicipalities(Request $request)
    {
        $provinceId = preg_replace('/\D/', '', (string) $request->get('province_id'));
        if ($provinceId === '') {
            return $this->psgcResponse([]);
        }

        try {
            $rows = $this->psgcRows($this->rememberPsgc(
                "https://psgc.gitlab.io/api/provinces/{$provinceId}/cities-municipalities.json"
            ));
            if (!$rows) {
                $rows = $this->psgcRows($this->rememberPsgc(
                    "https://psgc.gitlab.io/api/cities-municipalities/{$provinceId}.json"
                ));
            }

            return $this->psgcResponse($rows);
        } catch (\Throwable $e) {
            return $this->psgcError($e);
        }
    }

    /**
     * Barangays for a city or municipality.
     */
    public function psgcBarangays(Request $request)
    {
        $cityId = preg_replace('/\D/', '', (string) $request->get('city_id'));
        if ($cityId === '') {
            return $this->psgcResponse([]);
        }

        try {
            return $this->psgcResponse($this->psgcRows($this->rememberPsgc(
                "https://psgc.gitlab.io/api/cities-municipalities/{$cityId}/barangays.json"
            )));
        } catch (\Throwable $e) {
            return $this->psgcError($e);
        }
    }

    private function rememberPsgc(string $url): array
    {
        $key = 'psgc.gitlab.'.md5($url);
        try {
            $cached = Cache::get($key);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            // A missing cache table must not block the directory.
        }

        $data = $this->fetchPsgc($url);

        try {
            Cache::put($key, $data, now()->addDays(30));
        } catch (\Throwable $e) {
        }

        return $data;
    }

    private function fetchPsgc(string $url): array
    {
        $response = $this->psgcHttpGet($url);
        if ($response->status() === 404) {
            return [];
        }
        if (!$response->successful()) {
            throw new \RuntimeException('PSGC returned HTTP '.$response->status());
        }

        $json = $response->json();
        if (!is_array($json)) {
            return [];
        }
        if (isset($json['code'])) {
            return [$json];
        }

        return $json;
    }

    private function psgcHttpGet(string $url)
    {
        $send = function (bool $verify) use ($url) {
            return Http::withOptions(['verify' => $verify])
                ->connectTimeout(8)
                ->timeout(25)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'TDRMS-Assessor/1.0'])
                ->get($url);
        };

        try {
            return $send(true);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $ssl = str_contains($message, 'SSL')
                || str_contains($message, 'certificate')
                || str_contains($message, 'cURL error 60');
            if (!$ssl) {
                throw $e;
            }

            return $send(false);
        }
    }

    private function psgcRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['code']) || empty($row['name'])) {
                continue;
            }
            $name = (string) $row['name'];
            if (!empty($row['regionName']) && $row['regionName'] !== $name) {
                $name = $row['regionName'].' ('.$name.')';
            }
            $out[] = [
                'psgc_id' => (string) $row['code'],
                'name' => $name,
            ];
        }
        usort($out, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $out;
    }

    private function psgcResponse(array $rows)
    {
        return response()->json(array_values($rows))->header('Cache-Control', 'no-store');
    }

    private function psgcError(\Throwable $e)
    {
        report($e);

        return response()->json([
            'message' => 'Could not load the PSGC directory from this server. Allow outbound HTTPS to psgc.gitlab.io, then try again.',
        ], 502)->header('Cache-Control', 'no-store');
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
                'User-Agent' => 'TDRMS-Assessor/1.0',
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

