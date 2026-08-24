<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\FieldAppraisal;
use App\Models\PropertyDocument;
use App\Models\PropertyOwner;
use App\Models\TaxDeclaration;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Global multi-entity search used by the navbar quick-search combobox.
     *
     * Returns a grouped payload:
     *   { query, total, groups: [ { type, label, icon, items: [...] } ] }
     * Each item exposes: { id, title, subtitle, meta, url, type }
     */
    public function global(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([
                'query' => $q,
                'total' => 0,
                'groups' => [],
            ]);
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
        $perGroup = max(1, min(50, (int) $request->query('limit', 5)));
        $typeFilter = $request->query('type'); // optional: only return this group

        $shouldRun = fn (string $type) => !$typeFilter || $typeFilter === 'all' || $typeFilter === $type;

        $groups = [];
        $errors = [];

        // Each group is wrapped so a single bad column or missing table
        // doesn't take down the whole search response.

        // -- Tax Declarations
        if ($shouldRun('tax_declaration')) try {
            $tds = TaxDeclaration::with(['owner:id,owner_name', 'barangay:id,name', 'classification:id,name'])
                ->where(function ($w) use ($like) {
                    $w->where('td_number', 'like', $like)
                        ->orWhere('arp_number', 'like', $like)
                        ->orWhere('property_index_number', 'like', $like)
                        ->orWhere('lot_number', 'like', $like)
                        ->orWhere('title_number', 'like', $like)
                        ->orWhere('oct_tct_cloa_no', 'like', $like)
                        ->orWhere('survey_number', 'like', $like)
                        ->orWhere('previous_td_number', 'like', $like)
                        ->orWhere('previous_owner', 'like', $like)
                        ->orWhere('owner_address', 'like', $like)
                        ->orWhereHas('owner', fn ($o) => $o->where('owner_name', 'like', $like)
                            ->orWhere('co_owner_name', 'like', $like)
                            ->orWhere('tin', 'like', $like));
                })
                ->latest('updated_at')
                ->limit($perGroup)
                ->get();

            if ($tds->isNotEmpty()) {
                $groups[] = [
                    'type'  => 'tax_declaration',
                    'label' => 'Tax Declarations',
                    'icon'  => 'pi pi-file-edit',
                    'items' => $tds->map(fn ($td) => [
                        'id'       => $td->id,
                        'type'     => 'tax_declaration',
                        'title'    => $td->td_number,
                        'subtitle' => $td->owner?->owner_name ?: ($td->previous_owner ?: 'No owner'),
                        'meta'     => trim(implode(' · ', array_filter([
                            $td->classification?->name,
                            $td->barangay?->name,
                            $td->status ? ucwords(str_replace('_', ' ', $td->status)) : null,
                        ]))),
                        'url'      => "/tax-declarations/{$td->id}",
                    ])->all(),
                ];
            }
        } catch (\Throwable $e) {
            report($e);
            $errors[] = 'tax_declaration:' . $e->getMessage();
        }

        // -- Property Owners
        if ($shouldRun('property_owner')) try {
            $owners = PropertyOwner::where(function ($w) use ($like) {
                    $w->where('owner_name', 'like', $like)
                        ->orWhere('co_owner_name', 'like', $like)
                        ->orWhere('tin', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('contact_number', 'like', $like)
                        ->orWhere('address', 'like', $like);
                })
                ->withCount('taxDeclarations')
                ->latest('updated_at')
                ->limit($perGroup)
                ->get();

            if ($owners->isNotEmpty()) {
                $groups[] = [
                    'type'  => 'property_owner',
                    'label' => 'Property Owners',
                    'icon'  => 'pi pi-users',
                    'items' => $owners->map(fn ($o) => [
                        'id'       => $o->id,
                        'type'     => 'property_owner',
                        'title'    => $o->owner_name,
                        'subtitle' => $o->tin ? "TIN {$o->tin}" : ($o->email ?: ($o->contact_number ?: '—')),
                        'meta'     => trim(implode(' · ', array_filter([
                            $o->tax_declarations_count ? "{$o->tax_declarations_count} declaration(s)" : null,
                            $o->address,
                        ]))),
                        'url'      => "/property-owners/{$o->id}",
                    ])->all(),
                ];
            }
        } catch (\Throwable $e) {
            report($e);
            $errors[] = 'property_owner:' . $e->getMessage();
        }

        // -- Field Appraisals
        if ($shouldRun('field_appraisal')) try {
            $fas = FieldAppraisal::where(function ($w) use ($like) {
                    $w->where('appraisal_no', 'like', $like)
                        ->orWhere('pin', 'like', $like)
                        ->orWhere('arp_no', 'like', $like)
                        ->orWhere('owner_name', 'like', $like)
                        ->orWhere('property_barangay', 'like', $like)
                        ->orWhere('inspection_location', 'like', $like);
                })
                ->latest('updated_at')
                ->limit($perGroup)
                ->get();

            if ($fas->isNotEmpty()) {
                $groups[] = [
                    'type'  => 'field_appraisal',
                    'label' => 'Field Appraisals',
                    'icon'  => 'pi pi-clipboard',
                    'items' => $fas->map(fn ($fa) => [
                        'id'       => $fa->id,
                        'type'     => 'field_appraisal',
                        'title'    => $fa->appraisal_no ?: "Appraisal #{$fa->id}",
                        'subtitle' => $fa->owner_name ?: '—',
                        'meta'     => trim(implode(' · ', array_filter([
                            $fa->property_barangay,
                            $fa->inspection_date ? $fa->inspection_date->format('Y-m-d') : null,
                        ]))),
                        'url'      => "/field-appraisals/{$fa->id}",
                    ])->all(),
                ];
            }
        } catch (\Throwable $e) {
            report($e);
            $errors[] = 'field_appraisal:' . $e->getMessage();
        }

        // -- Supporting Documents
        if ($shouldRun('document')) try {
            $docs = PropertyDocument::with('taxDeclaration:id,td_number')
                ->where(function ($w) use ($like) {
                    $w->where('title', 'like', $like)
                        ->orWhere('file_name', 'like', $like)
                        ->orWhere('document_type', 'like', $like);
                })
                ->latest('updated_at')
                ->limit($perGroup)
                ->get();

            if ($docs->isNotEmpty()) {
                $groups[] = [
                    'type'  => 'document',
                    'label' => 'Documents',
                    'icon'  => 'pi pi-folder',
                    'items' => $docs->map(fn ($d) => [
                        'id'       => $d->id,
                        'type'     => 'document',
                        'title'    => $d->title ?: $d->file_name,
                        'subtitle' => $d->document_type ? ucwords(str_replace('_', ' ', $d->document_type)) : 'Document',
                        'meta'     => $d->taxDeclaration ? "TD {$d->taxDeclaration->td_number}" : null,
                        'url'      => $d->taxDeclaration
                            ? "/documents?td_id={$d->tax_declaration_id}"
                            : '/documents',
                    ])->all(),
                ];
            }
        } catch (\Throwable $e) {
            report($e);
            $errors[] = 'document:' . $e->getMessage();
        }

        // -- Users
        if ($shouldRun('user')) try {
            $users = User::where(function ($w) use ($like) {
                    $w->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('employee_id', 'like', $like);
                })
                ->latest('updated_at')
                ->limit($perGroup)
                ->get();

            if ($users->isNotEmpty()) {
                $groups[] = [
                    'type'  => 'user',
                    'label' => 'Users',
                    'icon'  => 'pi pi-id-card',
                    'items' => $users->map(fn ($u) => [
                        'id'       => $u->id,
                        'type'     => 'user',
                        'title'    => $u->name,
                        'subtitle' => $u->email,
                        'meta'     => $u->employee_id,
                        'url'      => '/users',
                    ])->all(),
                ];
            }
        } catch (\Throwable $e) {
            report($e);
            $errors[] = 'user:' . $e->getMessage();
        }

        // -- Barangays
        if ($shouldRun('barangay')) try {
            $brgys = Barangay::with('municipality:id,name')
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($perGroup)
                ->get();

            if ($brgys->isNotEmpty()) {
                $groups[] = [
                    'type'  => 'barangay',
                    'label' => 'Barangays',
                    'icon'  => 'pi pi-map-marker',
                    'items' => $brgys->map(fn ($b) => [
                        'id'       => $b->id,
                        'type'     => 'barangay',
                        'title'    => $b->name,
                        'subtitle' => $b->municipality?->name ?: '—',
                        'meta'     => null,
                        'url'      => '/settings',
                    ])->all(),
                ];
            }
        } catch (\Throwable $e) {
            report($e);
            $errors[] = 'barangay:' . $e->getMessage();
        }

        $total = collect($groups)->sum(fn ($g) => count($g['items']));

        return response()->json([
            'query'  => $q,
            'total'  => $total,
            'groups' => $groups,
            'errors' => $errors,
        ]);
    }
}
