<?php

namespace App\Http\Controllers;

use App\Models\FieldAppraisal;
use App\Models\TaxDeclaration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FieldAppraisalController extends Controller
{
    private function rules(?int $id = null): array
    {
        return [
            'appraisal_no'        => 'required|string|unique:field_appraisals,appraisal_no' . ($id ? ",{$id}" : ''),
            'form_template'       => 'nullable|string|in:form_1,form_2',
            'tax_declaration_id'  => 'nullable|exists:tax_declarations,id',
            'inspection_date'     => 'nullable|date',
            'inspection_location' => 'nullable|string',

            // Form 2 header / identity
            'update_code' => 'nullable|string',
            'pin' => 'nullable|string',
            'arp_no' => 'nullable|string',
            'oct_tct_kot_no' => 'nullable|string',
            'survey_no' => 'nullable|string',
            'cad_pls_lot_no' => 'nullable|string',
            'owner_name' => 'nullable|string',
            'owner_address' => 'nullable|string',
            'owner_tin' => 'nullable|string',
            'owner_telephone' => 'nullable|string',
            'administrator_name' => 'nullable|string',
            'administrator_address' => 'nullable|string',
            'administrator_tin' => 'nullable|string',
            'administrator_telephone' => 'nullable|string',

            // Property Location
            'property_street'       => 'nullable|string',
            'property_barangay'     => 'nullable|string',
            'property_municipality' => 'nullable|string',
            'property_province'     => 'nullable|string',

            // Boundaries
            'boundary_north' => 'nullable|string',
            'boundary_east'  => 'nullable|string',
            'boundary_south' => 'nullable|string',
            'boundary_west'  => 'nullable|string',

            // Totals
            'land_total_area' => 'nullable|numeric',
            'land_total_base_market_value' => 'nullable|numeric',
            'plant_total_area' => 'nullable|numeric',
            'plant_total_non_fb' => 'nullable|integer',
            'plant_total_fb' => 'nullable|integer',
            'plant_total_count' => 'nullable|integer',
            'plant_total_base_market_value' => 'nullable|numeric',

            // Adjustments
            'adj_along_road' => 'nullable|numeric',
            'adj_kms_weather_road' => 'nullable|numeric',
            'adj_kms_to_market' => 'nullable|numeric',
            'adj_total_adjustments' => 'nullable|numeric',
            'adj_total_percentage' => 'nullable|numeric',

            // Assessment totals
            'total_adjusted_market_value' => 'nullable|numeric',
            'rounded_assessed_value' => 'nullable|numeric',

            // Previous / taxability
            'previous_owner' => 'nullable|string',
            'previous_assessed_value' => 'nullable|numeric',
            'taxability' => 'nullable|string',
            'effectivity_year' => 'nullable|string',
            'effectivity_quarter' => 'nullable|string',

            // Signatures
            'appraised_by_name' => 'nullable|string',
            'appraised_by_title' => 'nullable|string',
            'appraised_by_date' => 'nullable|date',
            'assessed_by_name' => 'nullable|string',
            'assessed_by_title' => 'nullable|string',
            'assessed_by_date' => 'nullable|date',
            'recommending_name' => 'nullable|string',
            'recommending_title' => 'nullable|string',
            'recommending_date' => 'nullable|date',
            'approved_by_name' => 'nullable|string',
            'approved_by_title' => 'nullable|string',
            'approved_by_date' => 'nullable|date',

            // Conforme (Form 2)
            'conforme_name' => 'nullable|string',
            'conforme_ctc_no' => 'nullable|string',
            'conforme_dated' => 'nullable|date',
            'conforme_issued_at' => 'nullable|string',

            // Memoranda & references
            'memoranda' => 'nullable|string',
            'ref_pin' => 'nullable|string',
            'ref_arp_no' => 'nullable|string',
            'ref_ar_page_no' => 'nullable|string',
            'posting_pin_date' => 'nullable|date',
            'posting_pin_clerk' => 'nullable|string',
            'posting_pin_inspection' => 'nullable|string',
            'posting_arp_date' => 'nullable|date',
            'posting_arp_clerk' => 'nullable|string',
            'posting_arp_inspection' => 'nullable|string',
            'posting_ar_page_date' => 'nullable|date',
            'posting_ar_page_clerk' => 'nullable|string',
            'posting_ar_page_inspection' => 'nullable|string',

            // GIS / values / status
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'computed_market_value' => 'nullable|numeric',
            'computed_assessed_value' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'status' => 'nullable|in:draft,inspected,computed,approved,revision',

            // Row collections
            'land_rows' => 'nullable|array',
            'land_rows.*.classification_kind' => 'nullable|string',
            'land_rows.*.sub_class' => 'nullable|string',
            'land_rows.*.actual_use' => 'nullable|string',
            'land_rows.*.area' => 'nullable|numeric',
            'land_rows.*.unit_value' => 'nullable|numeric',
            'land_rows.*.base_market_value' => 'nullable|numeric',

            'plant_rows' => 'nullable|array',
            'plant_rows.*.kind' => 'nullable|string',
            'plant_rows.*.prod_class' => 'nullable|string',
            'plant_rows.*.area_planted' => 'nullable|numeric',
            'plant_rows.*.non_fb' => 'nullable|integer',
            'plant_rows.*.fb' => 'nullable|integer',
            'plant_rows.*.total' => 'nullable|integer',
            'plant_rows.*.unit_value' => 'nullable|numeric',
            'plant_rows.*.base_market_value' => 'nullable|numeric',

            'assessment_rows' => 'nullable|array',
            'assessment_rows.*.classification' => 'nullable|string',
            'assessment_rows.*.adjusted_market_value' => 'nullable|numeric',
            'assessment_rows.*.assessment_level' => 'nullable|numeric',
            'assessment_rows.*.assessed_value' => 'nullable|numeric',
        ];
    }

    private function syncRows(FieldAppraisal $appraisal, array $data): void
    {
        if (array_key_exists('land_rows', $data)) {
            $appraisal->landRows()->delete();
            foreach (array_values($data['land_rows'] ?? []) as $i => $row) {
                $appraisal->landRows()->create([
                    'classification_kind' => $row['classification_kind'] ?? null,
                    'sub_class' => $row['sub_class'] ?? null,
                    'actual_use' => $row['actual_use'] ?? null,
                    'area' => $row['area'] ?? null,
                    'unit_value' => $row['unit_value'] ?? null,
                    'base_market_value' => $row['base_market_value'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        if (array_key_exists('plant_rows', $data)) {
            $appraisal->plantRows()->delete();
            foreach (array_values($data['plant_rows'] ?? []) as $i => $row) {
                $appraisal->plantRows()->create([
                    'kind' => $row['kind'] ?? null,
                    'prod_class' => $row['prod_class'] ?? null,
                    'area_planted' => $row['area_planted'] ?? null,
                    'non_fb' => $row['non_fb'] ?? null,
                    'fb' => $row['fb'] ?? null,
                    'total' => $row['total'] ?? null,
                    'unit_value' => $row['unit_value'] ?? null,
                    'base_market_value' => $row['base_market_value'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        if (array_key_exists('assessment_rows', $data)) {
            $appraisal->assessmentRows()->delete();
            foreach (array_values($data['assessment_rows'] ?? []) as $i => $row) {
                $appraisal->assessmentRows()->create([
                    'classification' => $row['classification'] ?? null,
                    'adjusted_market_value' => $row['adjusted_market_value'] ?? null,
                    'assessment_level' => $row['assessment_level'] ?? null,
                    'assessed_value' => $row['assessed_value'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }
    }

    public function index(Request $request)
    {
        $query = FieldAppraisal::with(['taxDeclaration:id,td_number', 'assessor:id,name'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q, $s) => $q->where('appraisal_no', 'like', "%{$s}%"))
            ->latest();

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['assessor_id'] = Auth::id();

        $landRows = $data['land_rows'] ?? null;
        $plantRows = $data['plant_rows'] ?? null;
        $assessmentRows = $data['assessment_rows'] ?? null;
        unset($data['land_rows'], $data['plant_rows'], $data['assessment_rows']);

        $appraisal = DB::transaction(function () use ($data, $landRows, $plantRows, $assessmentRows) {
            $appraisal = FieldAppraisal::create($data);
            $this->syncRows($appraisal, [
                'land_rows' => $landRows ?? [],
                'plant_rows' => $plantRows ?? [],
                'assessment_rows' => $assessmentRows ?? [],
            ]);
            return $appraisal;
        });

        return response()->json(
            $appraisal->fresh()->load(['taxDeclaration', 'assessor', 'landRows', 'plantRows', 'assessmentRows']),
            201
        );
    }

    public function show(FieldAppraisal $fieldAppraisal)
    {
        return response()->json(
            $fieldAppraisal->load(['taxDeclaration.owner', 'assessor', 'landRows', 'plantRows', 'assessmentRows'])
        );
    }

    public function update(Request $request, FieldAppraisal $fieldAppraisal)
    {
        $data = $request->validate($this->rules($fieldAppraisal->id));

        $landRows = array_key_exists('land_rows', $data) ? $data['land_rows'] : null;
        $plantRows = array_key_exists('plant_rows', $data) ? $data['plant_rows'] : null;
        $assessmentRows = array_key_exists('assessment_rows', $data) ? $data['assessment_rows'] : null;
        unset($data['land_rows'], $data['plant_rows'], $data['assessment_rows']);

        DB::transaction(function () use ($fieldAppraisal, $data, $landRows, $plantRows, $assessmentRows) {
            $fieldAppraisal->update($data);
            $sync = [];
            if ($landRows !== null) $sync['land_rows'] = $landRows;
            if ($plantRows !== null) $sync['plant_rows'] = $plantRows;
            if ($assessmentRows !== null) $sync['assessment_rows'] = $assessmentRows;
            if ($sync) $this->syncRows($fieldAppraisal, $sync);
        });

        return response()->json(
            $fieldAppraisal->fresh()->load(['taxDeclaration', 'assessor', 'landRows', 'plantRows', 'assessmentRows'])
        );
    }

    public function destroy(FieldAppraisal $fieldAppraisal)
    {
        $fieldAppraisal->delete();
        return response()->json(['message' => 'Field appraisal deleted.']);
    }

    public function uploadPhotos(Request $request, FieldAppraisal $fieldAppraisal)
    {
        $request->validate(['photos.*' => 'required|image|max:10240']);

        $photos = $fieldAppraisal->photos ?? [];
        foreach ($request->file('photos', []) as $photo) {
            $path = $photo->store('appraisal-photos', 'public');
            $photos[] = [
                'path'       => $path,
                'filename'   => $photo->getClientOriginalName(),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }
        $fieldAppraisal->update(['photos' => $photos]);

        return response()->json(['photos' => $photos]);
    }

    public function uploadAttachments(Request $request, FieldAppraisal $fieldAppraisal)
    {
        $request->validate(['attachments.*' => 'required|file|max:20480']);

        $attachments = $fieldAppraisal->attachments ?? [];
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('appraisal-attachments', 'public');
            $attachments[] = [
                'path'       => $path,
                'filename'   => $file->getClientOriginalName(),
                'type'       => $file->getMimeType(),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }
        $fieldAppraisal->update(['attachments' => $attachments]);

        return response()->json(['attachments' => $attachments]);
    }

    public function uploadSketch(Request $request, FieldAppraisal $fieldAppraisal)
    {
        $request->validate(['sketch' => 'required|image|max:10240']);

        if ($fieldAppraisal->land_sketch) {
            Storage::disk('public')->delete($fieldAppraisal->land_sketch);
        }

        $path = $request->file('sketch')->store('appraisal-sketches', 'public');
        $fieldAppraisal->update(['land_sketch' => $path]);

        return response()->json(['land_sketch' => $path]);
    }

    public function deleteSketch(FieldAppraisal $fieldAppraisal)
    {
        if ($fieldAppraisal->land_sketch) {
            Storage::disk('public')->delete($fieldAppraisal->land_sketch);
            $fieldAppraisal->update(['land_sketch' => null]);
        }

        return response()->json(['land_sketch' => null]);
    }

    public function approve(FieldAppraisal $fieldAppraisal)
    {
        $fieldAppraisal->update(['status' => 'approved']);

        if ($fieldAppraisal->tax_declaration_id) {
            $td = TaxDeclaration::find($fieldAppraisal->tax_declaration_id);
            if ($td) {
                $updates = [];
                if ($fieldAppraisal->computed_market_value) {
                    $updates['market_value'] = $fieldAppraisal->computed_market_value;
                }
                if ($fieldAppraisal->computed_assessed_value) {
                    $updates['assessed_value'] = $fieldAppraisal->computed_assessed_value;
                }
                if ($fieldAppraisal->latitude && $fieldAppraisal->longitude) {
                    $updates['latitude']  = $fieldAppraisal->latitude;
                    $updates['longitude'] = $fieldAppraisal->longitude;
                }
                if ($updates) {
                    $td->update($updates);
                }
            }
        }

        return response()->json(
            $fieldAppraisal->fresh()->load(['taxDeclaration', 'assessor', 'landRows', 'plantRows', 'assessmentRows'])
        );
    }

    public function generatePdf(FieldAppraisal $fieldAppraisal)
    {
        $fa = $fieldAppraisal->load(['taxDeclaration', 'assessor', 'landRows', 'plantRows', 'assessmentRows']);

        $sealPath = null;
        $fallback = public_path('images/sidelogo.png');
        if (is_file($fallback)) $sealPath = $fallback;

        $sketchPath = null;
        if (!empty($fa->land_sketch)) {
            $path = Storage::disk('public')->path($fa->land_sketch);
            if (is_file($path)) $sketchPath = $path;
        }

        $pdf = Pdf::loadView('pdfs.field-appraisal', compact('fa', 'sealPath', 'sketchPath'))
            ->setPaper('letter', 'portrait');

        $name = $fa->appraisal_no ?: $fa->id;
        return $pdf->download("FAAS-{$name}.pdf");
    }
}
