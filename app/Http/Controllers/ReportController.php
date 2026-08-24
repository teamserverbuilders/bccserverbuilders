<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TaxDeclaration;
use App\Models\PropertyOwner;
use App\Models\Barangay;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\OcrResult;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /** Shared filter logic for the Property Report (used by both the JSON endpoint and the PDF export). */
    private function propertyQuery(Request $request): Builder
    {
        return TaxDeclaration::with(['owner', 'barangay', 'classification', 'createdBy'])
            ->when($request->barangay_id, fn($q) => $q->where('barangay_id', $request->barangay_id))
            ->when($request->classification_id, fn($q) => $q->where('classification_id', $request->classification_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest();
    }

    public function propertyReport(Request $request)
    {
        return response()->json($this->propertyQuery($request)->paginate($request->per_page ?? 15));
    }

    public function barangayReport()
    {
        $data = Barangay::withCount('taxDeclarations')
            ->with(['taxDeclarations' => fn($q) => $q->selectRaw('barangay_id, SUM(assessed_value) as total_assessed, SUM(market_value) as total_market')
                ->groupBy('barangay_id')])
            ->get();

        return response()->json($data);
    }

    /** Shared data builder for the Assessment Report (used by both the JSON endpoint and the PDF export). */
    private function assessmentData(): array
    {
        return [
            'total_market_value' => (float) TaxDeclaration::where('status', 'approved')->sum('market_value'),
            'total_assessed_value' => (float) TaxDeclaration::where('status', 'approved')->sum('assessed_value'),
            'by_classification' => TaxDeclaration::where('status', 'approved')
                ->with('classification:id,name')
                ->selectRaw('classification_id, SUM(market_value) as total_market, SUM(assessed_value) as total_assessed, COUNT(*) as count')
                ->groupBy('classification_id')
                ->get(),
        ];
    }

    public function assessmentReport(Request $request)
    {
        return response()->json($this->assessmentData());
    }

    public function ocrAccuracyReport()
    {
        return response()->json([
            'total_scanned' => OcrResult::count(),
            'completed' => OcrResult::where('status', 'completed')->count(),
            'failed' => OcrResult::where('status', 'failed')->count(),
            'average_confidence' => (float) (OcrResult::where('status', 'completed')->avg('confidence_score') ?? 0),
            'monthly' => OcrResult::selectRaw('MONTH(created_at) as month, AVG(confidence_score) as avg_confidence, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->get(),
        ]);
    }

    /** Shared filter logic for the Audit Report (used by both the JSON endpoint and the PDF export). */
    private function auditQuery(Request $request): Builder
    {
        return AuditLog::with('user:id,name')
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->event, fn($q) => $q->where('event', $request->event))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest();
    }

    public function auditReport(Request $request)
    {
        return response()->json($this->auditQuery($request)->paginate($request->per_page ?? 50));
    }

    public function userActivityReport()
    {
        return response()->json(
            User::withCount(['auditLogs as total_activities'])
                ->with('department:id,name')
                ->orderByDesc('total_activities')
                ->get(['id', 'name', 'email', 'department_id', 'last_login_at'])
        );
    }

    public function digitizationReport()
    {
        $total = TaxDeclaration::count();
        $digitized = TaxDeclaration::whereNotIn('status', ['draft'])->count();

        return response()->json([
            'total' => $total,
            'digitized' => $digitized,
            'percentage' => $total > 0 ? round(($digitized / $total) * 100, 2) : 0,
            'by_status' => TaxDeclaration::selectRaw('status, COUNT(*) as count')->groupBy('status')->get(),
            'by_month' => TaxDeclaration::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->get(),
        ]);
    }

    /**
     * Export a report as a PDF. Supports type=property|assessment|audit.
     *
     * IMPORTANT: this previously always exported the *property* dataset
     * regardless of the requested `type`, and always rendered a Blade view
     * (`pdfs.reports.{type}`) that did not exist — so every click produced a
     * 500 error. Each type now builds its own dataset and renders its own
     * (existing) view. Exported datasets are capped at 2000 rows to keep
     * PDF generation fast and memory-bounded.
     */
    public function exportPdf(Request $request)
    {
        $type = $request->get('type', 'property');
        $exportCap = 2000;
        $generatedAt = now();

        try {
            switch ($type) {
                case 'assessment':
                    $data = $this->assessmentData();
                    $view = 'pdfs.reports.assessment';
                    break;

                case 'audit':
                    $data = [
                        'logs' => $this->auditQuery($request)->limit($exportCap)->get(),
                    ];
                    $view = 'pdfs.reports.audit';
                    break;

                case 'property':
                    $records = $this->propertyQuery($request)->limit($exportCap)->get();
                    $data = [
                        'records' => $records,
                        'filters' => $request->only(['barangay_id', 'classification_id', 'status', 'date_from', 'date_to']),
                        'total_market_value' => (float) $records->sum('market_value'),
                        'total_assessed_value' => (float) $records->sum('assessed_value'),
                    ];
                    $view = 'pdfs.reports.property';
                    break;

                default:
                    return response()->json(['message' => "Unknown report type: {$type}"], 422);
            }

            $pdf = Pdf::loadView($view, array_merge($data, ['generatedAt' => $generatedAt, 'type' => $type]))
                ->setPaper('a4', 'landscape');

            return $pdf->download("report-{$type}-" . $generatedAt->format('Y-m-d') . ".pdf");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Report PDF export failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Could not generate the PDF report. Please try again.',
            ], 500);
        }
    }
}
