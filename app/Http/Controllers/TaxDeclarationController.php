<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TaxDeclaration;
use App\Models\PropertyOwner;
use App\Models\PropertyVersion;
use App\Models\OwnershipHistory;
use App\Models\WorkflowHistory;
use App\Models\AuditLog;
use App\Models\DuplicateRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

class TaxDeclarationController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxDeclaration::with(['owner', 'municipality', 'barangay', 'classification', 'createdBy'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('td_number', 'like', "%{$request->search}%")
                    ->orWhere('arp_number', 'like', "%{$request->search}%")
                    ->orWhereHas('owner', fn($q) => $q->where('owner_name', 'like', "%{$request->search}%"));
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->classification_id, fn($q) => $q->where('classification_id', $request->classification_id))
            ->when($request->barangay_id, fn($q) => $q->where('barangay_id', $request->barangay_id))
            ->when($request->municipality_id, fn($q) => $q->where('municipality_id', $request->municipality_id))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest();

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'td_number' => 'required|string|unique:tax_declarations,td_number',
            'owner_id' => 'required|exists:property_owners,id',
            'municipality_id' => 'nullable|exists:municipalities,id',
            'barangay_id' => 'nullable|exists:barangays,id',
            'classification_id' => 'nullable|exists:classifications,id',
        ]);

        try {
            $td = DB::transaction(function () use ($request) {
            $td = TaxDeclaration::create(array_merge(
                $request->only([
                    'td_number', 'arp_number', 'property_index_number', 'lot_number', 'block_number',
                    'survey_number', 'title_number', 'owner_id', 'municipality_id', 'barangay_id',
                    'classification_id', 'assessment_level_id', 'tax_type_id', 'taxability',
                    'current_use', 'actual_use', 'land_area', 'building_area', 'market_value',
                    'assessed_value', 'assessment_level', 'effectivity_date', 'date_issued', 'remarks',
                    // New fields
                    'owner_tin', 'owner_address', 'owner_telephone',
                    'administrator_name', 'administrator_tin', 'administrator_address', 'administrator_telephone',
                    'property_street', 'oct_tct_cloa_no', 'cct', 'title_date',
                    'boundary_north', 'boundary_east', 'boundary_south', 'boundary_west',
                    'kind_of_property', 'no_of_storeys', 'building_description',
                    'machinery_description', 'others_specify',
                    'base_market_value', 'adjusted_market_value', 'assessed_value_words',
                    'rounded_assessed_value', 'valuation_rows', 'assessment_rows',
                    'effectivity_quarter', 'effectivity_year',
                    'previous_td_number', 'previous_owner', 'previous_av',
                    'memoranda', 'approved_by_name',
                ]),
                ['created_by' => Auth::id(), 'status' => 'draft']
            ));

            PropertyVersion::create([
                'tax_declaration_id' => $td->id,
                'version_number' => 1,
                'data_snapshot' => $td->toArray(),
                'change_summary' => 'Initial creation',
                'created_by' => Auth::id(),
            ]);

            WorkflowHistory::create([
                'tax_declaration_id' => $td->id,
                'from_status' => null,
                'to_status' => 'draft',
                'action' => 'submit',
                'performed_by' => Auth::id(),
            ]);

            $this->checkDuplicates($td);
            $this->logAudit('created', $td);

            return $td;
            });

            return response()->json($td->load(['owner', 'municipality', 'barangay', 'classification']), 201);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Failed to save tax declaration: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(TaxDeclaration $taxDeclaration)
    {
        return response()->json($taxDeclaration->load([
            'owner', 'municipality', 'barangay', 'classification', 'assessmentLevel',
            'taxType', 'location', 'improvements', 'images', 'documents', 'gisLocation',
            'workflowHistory.performedBy', 'approvalHistory.user', 'ocrResults',
            'versions.createdBy', 'duplicates.duplicateTd',
            'ownershipHistory.owner', 'ownershipHistory.transferredBy', 'ownershipHistory.newTaxDeclaration:id,td_number,arp_number,status',
            'issuedFromHistory.taxDeclaration:id,td_number,arp_number',
        ]));
    }

    public function transferOwnership(Request $request, TaxDeclaration $taxDeclaration)
    {
        if ($taxDeclaration->is_locked && strtolower((string) $taxDeclaration->status) !== 'approved') {
            return response()->json(['message' => 'Record is locked and cannot be transferred.'], 403);
        }

        $request->validate([
            'new_td_number' => 'required|string|max:255|unique:tax_declarations,td_number',
            'new_arp_number' => 'nullable|string|max:255',
            'owner_id' => 'nullable|exists:property_owners,id',
            'owner_name' => 'required_without:owner_id|nullable|string|max:255',
            'owner_tin' => 'nullable|string|max:20',
            'owner_address' => 'nullable|string',
            'owner_telephone' => 'nullable|string|max:20',
            'co_owner_name' => 'nullable|string|max:255',
            'transfer_date' => 'required|date',
            'transfer_reason' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        $taxDeclaration->load('owner');

        if (!$taxDeclaration->owner_id && !$taxDeclaration->owner) {
            return response()->json(['message' => 'Current owner is required before transferring ownership.'], 422);
        }

        try {
            $newTd = DB::transaction(function () use ($request, $taxDeclaration) {
                $oldData = $taxDeclaration->toArray();
                $currentOwner = $taxDeclaration->owner;

                // Resolve / create new owner
                $newOwnerId = $request->owner_id;
                if (!$newOwnerId) {
                    $newOwner = PropertyOwner::create([
                        'owner_name' => $request->owner_name,
                        'co_owner_name' => $request->co_owner_name,
                        'tin' => $request->owner_tin,
                        'address' => $request->owner_address ?: ($request->owner_name ?: 'N/A'),
                        'contact_number' => $request->owner_telephone,
                    ]);
                    $newOwnerId = $newOwner->id;
                } else {
                    $newOwner = PropertyOwner::findOrFail($newOwnerId);
                }

                if ((int) $newOwnerId === (int) $taxDeclaration->owner_id) {
                    throw new \InvalidArgumentException('New owner must be different from the current owner.');
                }

                $transferDate = $request->date('transfer_date');

                // Snapshot the outgoing (current) owner from the old TD
                $snapshotName = $currentOwner?->owner_name
                    ?: ($taxDeclaration->previous_owner ?: 'Unknown owner');
                $snapshotTin = $currentOwner?->tin ?: $taxDeclaration->owner_tin;
                $snapshotAddress = $currentOwner?->address ?: $taxDeclaration->owner_address;
                $snapshotTelephone = $currentOwner?->contact_number ?: $taxDeclaration->owner_telephone;

                // 1) Create the NEW TD by cloning property data from the old one
                $cloneable = collect($taxDeclaration->getAttributes())
                    ->except([
                        'id', 'td_number', 'arp_number', 'owner_id',
                        'owner_tin', 'owner_address', 'owner_telephone',
                        'previous_td_number', 'previous_owner', 'previous_av',
                        'status', 'is_locked', 'locked_by', 'locked_at',
                        'approved_by', 'approved_at', 'approved_by_name',
                        'qr_code', 'version',
                        'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
                    ])
                    ->all();

                // Rebuild casted array fields from the model (getAttributes returns JSON strings)
                $cloneable['kind_of_property'] = $taxDeclaration->kind_of_property;
                $cloneable['valuation_rows'] = $taxDeclaration->valuation_rows;
                $cloneable['assessment_rows'] = $taxDeclaration->assessment_rows;

                $newTd = TaxDeclaration::create(array_merge($cloneable, [
                    'td_number'          => $request->new_td_number,
                    'arp_number'         => $request->new_arp_number ?: $taxDeclaration->arp_number,
                    'owner_id'           => $newOwnerId,
                    'owner_tin'          => $newOwner->tin,
                    'owner_address'      => $newOwner->address,
                    'owner_telephone'    => $newOwner->contact_number,
                    'previous_td_number' => $taxDeclaration->td_number,
                    'previous_owner'     => $snapshotName,
                    'previous_av'        => $taxDeclaration->assessed_value ?? $taxDeclaration->previous_av,
                    'status'             => 'draft',
                    'version'            => 1,
                    'is_locked'          => false,
                    'locked_by'          => null,
                    'locked_at'          => null,
                    'approved_by'        => null,
                    'approved_at'        => null,
                    'approved_by_name'   => null,
                    'qr_code'            => null,
                    'created_by'         => Auth::id(),
                    'date_issued'        => $transferDate,
                ]));

                // 2) Ownership history entry on the OLD TD (points to the new TD)
                OwnershipHistory::create([
                    'tax_declaration_id'     => $taxDeclaration->id,
                    'new_tax_declaration_id' => $newTd->id,
                    'new_td_number'          => $newTd->td_number,
                    'new_arp_number'         => $newTd->arp_number,
                    'owner_id'               => $taxDeclaration->owner_id,
                    'owner_name'             => $snapshotName,
                    'owner_tin'              => $snapshotTin,
                    'owner_address'          => $snapshotAddress,
                    'owner_telephone'        => $snapshotTelephone,
                    'effective_from'         => $taxDeclaration->date_issued ?? $taxDeclaration->created_at?->toDateString(),
                    'effective_to'           => $transferDate,
                    'transfer_date'          => $transferDate,
                    'transfer_reason'        => $request->transfer_reason,
                    'remarks'                => $request->remarks,
                    'previous_td_number'     => $taxDeclaration->td_number,
                    'previous_av'            => $taxDeclaration->assessed_value ?? $taxDeclaration->previous_av,
                    'transferred_by'         => Auth::id(),
                ]);

                // 3) Cancel the old TD (archived + locked) so it can no longer be edited
                $taxDeclaration->update([
                    'status'     => 'archived',
                    'is_locked'  => true,
                    'locked_by'  => Auth::id(),
                    'locked_at'  => now(),
                    'updated_by' => Auth::id(),
                    'version'    => $taxDeclaration->version + 1,
                ]);

                // Snapshots + audit for both records
                PropertyVersion::create([
                    'tax_declaration_id' => $taxDeclaration->id,
                    'version_number'     => $taxDeclaration->version,
                    'data_snapshot'      => $taxDeclaration->fresh()->toArray(),
                    'change_summary'     => "Cancelled — ownership transferred to {$newOwner->owner_name} under new TD {$newTd->td_number}",
                    'created_by'         => Auth::id(),
                ]);
                PropertyVersion::create([
                    'tax_declaration_id' => $newTd->id,
                    'version_number'     => 1,
                    'data_snapshot'      => $newTd->fresh()->toArray(),
                    'change_summary'     => "Issued via ownership transfer from TD {$taxDeclaration->td_number}",
                    'created_by'         => Auth::id(),
                ]);

                $this->logAudit('ownership_transferred', $taxDeclaration->fresh(), $oldData);
                $this->logAudit('created', $newTd);

                return $newTd->fresh()->load([
                    'owner', 'municipality', 'barangay', 'classification',
                    'ownershipHistory.owner', 'ownershipHistory.transferredBy',
                ]);
            });

            return response()->json([
                'message' => 'Ownership transferred. New TD issued.',
                'new_tax_declaration' => $newTd,
                'new_tax_declaration_id' => $newTd->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Failed to transfer ownership: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, TaxDeclaration $taxDeclaration)
    {
        if ($taxDeclaration->is_locked) {
            return response()->json(['message' => 'Record is locked.'], 403);
        }

        $oldData = $taxDeclaration->toArray();

        DB::transaction(function () use ($request, $taxDeclaration, $oldData) {
            $taxDeclaration->update(array_merge(
                $request->only([
                    'td_number', 'arp_number', 'property_index_number', 'lot_number', 'block_number',
                    'survey_number', 'title_number', 'owner_id', 'municipality_id', 'barangay_id',
                    'classification_id', 'assessment_level_id', 'tax_type_id', 'taxability',
                    'current_use', 'actual_use', 'land_area', 'building_area', 'market_value',
                    'assessed_value', 'assessment_level', 'effectivity_date', 'date_issued', 'remarks',
                    // New fields
                    'owner_tin', 'owner_address', 'owner_telephone',
                    'administrator_name', 'administrator_tin', 'administrator_address', 'administrator_telephone',
                    'property_street', 'oct_tct_cloa_no', 'cct', 'title_date',
                    'boundary_north', 'boundary_east', 'boundary_south', 'boundary_west',
                    'kind_of_property', 'no_of_storeys', 'building_description',
                    'machinery_description', 'others_specify',
                    'base_market_value', 'adjusted_market_value', 'assessed_value_words',
                    'rounded_assessed_value', 'valuation_rows', 'assessment_rows',
                    'effectivity_quarter', 'effectivity_year',
                    'previous_td_number', 'previous_owner', 'previous_av',
                    'memoranda', 'approved_by_name',
                ]),
                ['updated_by' => Auth::id(), 'version' => $taxDeclaration->version + 1]
            ));

            PropertyVersion::create([
                'tax_declaration_id' => $taxDeclaration->id,
                'version_number' => $taxDeclaration->version,
                'data_snapshot' => $taxDeclaration->fresh()->toArray(),
                'change_summary' => $request->change_summary ?? 'Record updated',
                'created_by' => Auth::id(),
            ]);

            $this->logAudit('updated', $taxDeclaration, $oldData);
        });

        return response()->json($taxDeclaration->fresh()->load(['owner', 'classification']));
    }

    public function destroy(TaxDeclaration $taxDeclaration)
    {
        // Soft-delete is always allowed (including approved/locked records).
        // Lock only blocks updates — trash can still receive the record.
        $this->logAudit('deleted', $taxDeclaration);
        $taxDeclaration->delete();
        return response()->json(['message' => 'Tax declaration deleted.']);
    }

    public function restore($id)
    {
        $td = TaxDeclaration::withTrashed()->findOrFail($id);
        $td->restore();
        $this->logAudit('restored', $td);
        return response()->json($td);
    }

    public function updateStatus(Request $request, TaxDeclaration $taxDeclaration)
    {
        $request->validate([
            'status' => 'required|in:draft,ocr_processing,ocr_review,encoder_review,assessor_verification,supervisor_review,approved,released,archived,rejected,returned',
            'remarks' => 'nullable|string',
        ]);

        $oldStatus = $taxDeclaration->status;
        $taxDeclaration->update(['status' => $request->status, 'updated_by' => Auth::id()]);

        if ($request->status === 'approved') {
            $taxDeclaration->update([
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'is_locked' => true,
                'locked_by' => Auth::id(),
                'locked_at' => now(),
            ]);
        }

        WorkflowHistory::create([
            'tax_declaration_id' => $taxDeclaration->id,
            'from_status' => $oldStatus,
            'to_status' => $request->status,
            'action' => $this->getActionFromStatus($request->status),
            'remarks' => $request->remarks,
            'performed_by' => Auth::id(),
        ]);

        return response()->json($taxDeclaration->fresh());
    }

    public function unlock(TaxDeclaration $taxDeclaration)
    {
        if (!$taxDeclaration->is_locked) {
            return response()->json(['message' => 'Record is not locked.'], 422);
        }

        $oldData = $taxDeclaration->toArray();

        $taxDeclaration->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
            'updated_by' => Auth::id(),
        ]);

        $this->logAudit('unlocked', $taxDeclaration->fresh(), $oldData);

        return response()->json($taxDeclaration->fresh()->load([
            'owner', 'municipality', 'barangay', 'classification',
            'ownershipHistory.owner', 'ownershipHistory.transferredBy',
            'ownershipHistory.newTaxDeclaration:id,td_number,arp_number,status',
            'issuedFromHistory.taxDeclaration:id,td_number,arp_number',
        ]));
    }

    public function generateQr(TaxDeclaration $taxDeclaration)
    {
        $verifyUrl = url('/verify/' . $taxDeclaration->td_number);
        $qrPath = 'qrcodes/' . $taxDeclaration->td_number . '.svg';

        $qrCode = QrCode::format('svg')->size(300)->generate($verifyUrl);

        Storage::disk('public')->makeDirectory('qrcodes');
        Storage::disk('public')->put($qrPath, $qrCode);

        $taxDeclaration->update(['qr_code' => $qrPath]);

        return response()->json([
            'qr_code' => '/storage/' . $qrPath,
            'qr_data_uri' => 'data:image/svg+xml;base64,' . base64_encode($qrCode),
            'url' => $verifyUrl,
        ]);
    }

    public function generatePdf(TaxDeclaration $taxDeclaration)
    {
        $td = $taxDeclaration->load([
            'owner', 'municipality', 'barangay', 'classification',
            'location', 'improvements', 'approvalHistory.user',
        ]);

        // dompdf needs a local filesystem path (not a public URL) to embed an image.
        $sealPath = null;
        if (!empty($td->municipality?->official_seal)) {
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($td->municipality->official_seal);
            if (is_file($path)) $sealPath = $path;
        }
        // Fallback to the bundled sidelogo when the municipality has no seal uploaded.
        if ($sealPath === null) {
            $fallback = public_path('images/sidelogo.png');
            if (is_file($fallback)) $sealPath = $fallback;
        }

        $pdf = Pdf::loadView('pdfs.tax-declaration', compact('td', 'sealPath'));
        return $pdf->download("TD-{$taxDeclaration->td_number}.pdf");
    }

    public function statistics()
    {
        return response()->json([
            'total' => TaxDeclaration::count(),
            'residential' => TaxDeclaration::whereHas('classification', fn($q) => $q->where('name', 'like', '%Residential%'))->count(),
            'commercial' => TaxDeclaration::whereHas('classification', fn($q) => $q->where('name', 'like', '%Commercial%'))->count(),
            'agricultural' => TaxDeclaration::whereHas('classification', fn($q) => $q->where('name', 'like', '%Agricultural%'))->count(),
            'industrial' => TaxDeclaration::whereHas('classification', fn($q) => $q->where('name', 'like', '%Industrial%'))->count(),
            'pending_ocr' => TaxDeclaration::whereIn('status', ['ocr_processing', 'ocr_review'])->count(),
            'pending_verification' => TaxDeclaration::whereIn('status', ['encoder_review', 'assessor_verification', 'supervisor_review'])->count(),
            'approved' => TaxDeclaration::where('status', 'approved')->count(),
            'archived' => TaxDeclaration::where('status', 'archived')->count(),
            'rejected' => TaxDeclaration::where('status', 'rejected')->count(),
            'today_uploads' => TaxDeclaration::whereDate('created_at', today())->count(),
            'monthly_data' => TaxDeclaration::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
            'by_barangay' => TaxDeclaration::selectRaw('barangay_id, COUNT(*) as count')
                ->with('barangay:id,name')
                ->groupBy('barangay_id')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'by_classification' => TaxDeclaration::selectRaw('classification_id, COUNT(*) as count')
                ->with('classification:id,name,color')
                ->groupBy('classification_id')
                ->get(),
        ]);
    }

    private function checkDuplicates(TaxDeclaration $td): void
    {
        $potential = TaxDeclaration::where('id', '!=', $td->id)
            ->where(function ($q) use ($td) {
                $q->where('lot_number', $td->lot_number)
                    ->orWhere('arp_number', $td->arp_number)
                    ->orWhereHas('owner', fn($q) => $q->where('owner_name', $td->owner->owner_name ?? ''));
            })
            ->get();

        foreach ($potential as $dup) {
            DuplicateRecord::firstOrCreate([
                'tax_declaration_id' => $td->id,
                'duplicate_td_id' => $dup->id,
            ], [
                'similarity_score' => 75.00,
                'matched_fields' => [],
                'status' => 'pending',
            ]);
        }
    }

    private function getActionFromStatus(string $status): string
    {
        return match ($status) {
            'approved' => 'approve',
            'rejected' => 'reject',
            'returned' => 'return',
            'archived' => 'archive',
            'released' => 'release',
            default => 'submit',
        };
    }

    private function logAudit(string $event, TaxDeclaration $td, ?array $oldData = null): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => "tax_declaration_{$event}",
            'auditable_type' => TaxDeclaration::class,
            'auditable_id' => $td->id,
            'old_values' => $oldData,
            'new_values' => $td->toArray(),
            'ip_address' => request()->ip(),
        ]);
    }
}

