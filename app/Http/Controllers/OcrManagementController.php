<?php

namespace App\Http\Controllers;

use App\Models\OcrLog;
use App\Models\OcrResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * OCR Management Controller
 *
 * Handles the "OCR Management" admin page: listing the queue, single-record
 * details, batch scanning, review corrections, and deleting queue entries.
 *
 * OCR records are NOT linked to Tax Declarations in this flow. They are a
 * standalone catalog of scanned documents. Instead, the Tax Declaration form
 * has an "Existing TD in the OCR Scanner" search that reads directly from
 * this catalog (via {@see self::extractedTdNumbers()}) so the operator can
 * pre-fill a new TD form from previously scanned data.
 *
 * NOTE: The actual OCR *work* (upload + engine + field extraction) still
 * lives in {@see OcrController}, which is shared with the Tax Declaration
 * form and the Field Appraisal form.
 */
class OcrManagementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) ($request->per_page ?? 15);
        $perPage = max(1, min($perPage, 100));

        $query = OcrResult::query()
            ->with(['processedBy:id,name', 'reviewedBy:id,name'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('original_filename', 'like', "%{$search}%")
                        ->orWhere('source_file', 'like', "%{$search}%")
                        ->orWhere('extracted_fields->td_number', 'like', "%{$search}%")
                        ->orWhere('corrected_fields->td_number', 'like', "%{$search}%");
                });
            })
            ->latest();

        return response()->json($query->paginate($perPage));
    }

    /**
     * Endpoint powering the "Existing TD in the OCR Scanner" dropdown on the
     * Tax Declaration form. Returns OCR records (completed or reviewed) that
     * have a `td_number` in their extracted / corrected fields.
     */
    public function extractedTdNumbers(Request $request)
    {
        $q     = trim((string) $request->get('q', ''));
        $limit = min(max((int) $request->get('limit', 20), 1), 100);

        $query = OcrResult::query()
            ->whereIn('status', ['completed', 'reviewed'])
            ->where(function ($qq) {
                $qq->whereNotNull('extracted_fields->td_number')
                    ->orWhereNotNull('corrected_fields->td_number');
            });

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('extracted_fields->td_number', 'like', "%{$q}%")
                    ->orWhere('corrected_fields->td_number', 'like', "%{$q}%")
                    ->orWhere('extracted_fields->owner_name', 'like', "%{$q}%")
                    ->orWhere('corrected_fields->owner_name', 'like', "%{$q}%")
                    ->orWhere('original_filename', 'like', "%{$q}%");
            });
        }

        $results = $query->latest()
            ->limit($limit)
            ->get(['id', 'original_filename', 'extracted_fields', 'corrected_fields', 'confidence_score', 'created_at']);

        // Reshape for the dropdown: promote the effective td_number and owner
        // to top-level keys so the frontend can use it directly.
        $payload = $results->map(function (OcrResult $r) {
            $fields = $r->corrected_fields ?? $r->extracted_fields ?? [];
            return [
                'id'                => $r->id,
                'td_number'         => $fields['td_number'] ?? null,
                'owner_name'        => $fields['owner_name'] ?? null,
                'original_filename' => $r->original_filename,
                'confidence_score'  => $r->confidence_score,
                'created_at'        => $r->created_at,
                'fields'            => $fields,
            ];
        })->filter(fn ($row) => !empty($row['td_number']))->values();

        return response()->json($payload);
    }

    public function show(OcrResult $ocrResult)
    {
        $ocrResult->load(['processedBy:id,name', 'reviewedBy:id,name']);

        return response()->json($ocrResult);
    }

    public function batchScan(Request $request, OcrController $ocr)
    {
        $request->validate([
            'ocr_result_ids'   => 'required|array',
            'ocr_result_ids.*' => 'exists:ocr_results,id',
        ]);

        $results = [];
        foreach ($request->ocr_result_ids as $id) {
            $ocrResult = OcrResult::find($id);
            if ($ocrResult && $ocrResult->status === 'pending') {
                // Reuse the shared scan implementation so all consumers stay in sync.
                $results[] = $ocr->scan($ocrResult)->getData();
            }
        }

        return response()->json([
            'processed' => count($results),
            'results'   => $results,
        ]);
    }

    public function correct(Request $request, OcrResult $ocrResult)
    {
        $request->validate([
            'corrected_fields' => 'required|array',
        ]);

        $ocrResult->update([
            'corrected_fields' => $request->corrected_fields,
            'status'           => 'reviewed',
            'reviewed_by'      => Auth::id(),
            'reviewed_at'      => now(),
        ]);

        OcrLog::create([
            'ocr_result_id' => $ocrResult->id,
            'user_id'       => Auth::id(),
            'action'        => 'correct',
            'notes'         => 'Fields manually corrected',
            'changes'       => $request->corrected_fields,
        ]);

        return response()->json($ocrResult->fresh(['processedBy:id,name', 'reviewedBy:id,name']));
    }

    public function destroy(OcrResult $ocrResult)
    {
        $this->deleteRecord($ocrResult);

        return response()->json(['message' => 'OCR record deleted.']);
    }

    /**
     * Delete multiple OCR records in one call. Silently skips ids that no
     * longer exist so partial success doesn't block the whole batch.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $records = OcrResult::whereIn('id', $request->ids)->get();
        foreach ($records as $r) {
            $this->deleteRecord($r);
        }

        return response()->json([
            'deleted' => $records->count(),
            'message' => $records->count() . ' OCR record(s) deleted.',
        ]);
    }

    private function deleteRecord(OcrResult $ocrResult): void
    {
        if ($ocrResult->source_file && Storage::disk('public')->exists($ocrResult->source_file)) {
            Storage::disk('public')->delete($ocrResult->source_file);
        }

        $ocrResult->logs()->delete();
        $ocrResult->delete();
    }
}
