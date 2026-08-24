<?php

namespace App\Http\Controllers;

use App\Models\FieldAppraisal;
use App\Models\PropertyImprovement;
use App\Models\PropertyLocation;
use App\Models\PropertyOwner;
use App\Models\PropertyVersion;
use App\Models\TaxDeclaration;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    private const TYPES = [
        'tax_declarations' => TaxDeclaration::class,
        'field_appraisals' => FieldAppraisal::class,
        'property_owners' => PropertyOwner::class,
        'property_locations' => PropertyLocation::class,
        'property_improvements' => PropertyImprovement::class,
        'property_versions' => PropertyVersion::class,
    ];

    public function index(Request $request)
    {
        $type = $request->get('type', 'tax_declarations');
        if (!isset(self::TYPES[$type])) {
            return response()->json(['message' => 'Invalid archive type.'], 422);
        }

        $perPage = (int) ($request->per_page ?? 15);
        $search = trim((string) $request->search);

        $query = match ($type) {
            'tax_declarations' => TaxDeclaration::onlyTrashed()
                ->with(['owner', 'classification', 'barangay'])
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('td_number', 'like', "%{$search}%")
                            ->orWhere('arp_number', 'like', "%{$search}%")
                            ->orWhereHas('owner', fn ($o) => $o->where('owner_name', 'like', "%{$search}%"));
                    });
                }),
            'field_appraisals' => FieldAppraisal::onlyTrashed()
                ->with(['taxDeclaration', 'assessor'])
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('appraisal_no', 'like', "%{$search}%")
                            ->orWhere('arp_no', 'like', "%{$search}%")
                            ->orWhere('owner_name', 'like', "%{$search}%")
                            ->orWhere('pin', 'like', "%{$search}%");
                    });
                }),
            'property_owners' => PropertyOwner::onlyTrashed()
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('owner_name', 'like', "%{$search}%")
                            ->orWhere('tin', 'like', "%{$search}%")
                            ->orWhere('contact_number', 'like', "%{$search}%");
                    });
                }),
            'property_locations' => PropertyLocation::onlyTrashed()
                ->with(['taxDeclaration' => fn ($q) => $q->withTrashed()])
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('barangay', 'like', "%{$search}%")
                            ->orWhere('municipality', 'like', "%{$search}%")
                            ->orWhere('province', 'like', "%{$search}%")
                            ->orWhere('street', 'like', "%{$search}%")
                            ->orWhereHas('taxDeclaration', fn ($td) => $td->withTrashed()->where('td_number', 'like', "%{$search}%"));
                    });
                }),
            'property_improvements' => PropertyImprovement::onlyTrashed()
                ->with(['taxDeclaration' => fn ($q) => $q->withTrashed()])
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('building_description', 'like', "%{$search}%")
                            ->orWhere('structure_description', 'like', "%{$search}%")
                            ->orWhere('land_improvements', 'like', "%{$search}%")
                            ->orWhereHas('taxDeclaration', fn ($td) => $td->withTrashed()->where('td_number', 'like', "%{$search}%"));
                    });
                }),
            'property_versions' => PropertyVersion::onlyTrashed()
                ->with([
                    'taxDeclaration' => fn ($q) => $q->withTrashed(),
                    'createdBy',
                ])
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('change_summary', 'like', "%{$search}%")
                            ->orWhere('version_number', 'like', "%{$search}%")
                            ->orWhereHas('taxDeclaration', fn ($td) => $td->withTrashed()->where('td_number', 'like', "%{$search}%"));
                    });
                }),
        };

        return response()->json(
            $query->latest('deleted_at')->paginate($perPage)
        );
    }

    public function counts()
    {
        return response()->json([
            'tax_declarations' => TaxDeclaration::onlyTrashed()->count(),
            'field_appraisals' => FieldAppraisal::onlyTrashed()->count(),
            'property_owners' => PropertyOwner::onlyTrashed()->count(),
            'property_locations' => PropertyLocation::onlyTrashed()->count(),
            'property_improvements' => PropertyImprovement::onlyTrashed()->count(),
            'property_versions' => PropertyVersion::onlyTrashed()->count(),
        ]);
    }

    public function restore(string $type, $id)
    {
        $model = $this->findTrashed($type, $id);
        $model->restore();

        return response()->json([
            'message' => 'Record restored.',
            'data' => $model->fresh(),
        ]);
    }

    public function forceDestroy(string $type, $id)
    {
        $model = $this->findTrashed($type, $id);
        $model->forceDelete();

        return response()->json(['message' => 'Record permanently deleted.']);
    }

    private function findTrashed(string $type, $id)
    {
        if (!isset(self::TYPES[$type])) {
            abort(422, 'Invalid archive type.');
        }

        $class = self::TYPES[$type];

        return $class::onlyTrashed()->findOrFail($id);
    }
}
