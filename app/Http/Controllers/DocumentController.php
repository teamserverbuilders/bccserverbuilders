<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PropertyDocument;
use App\Models\PropertyImage;
use App\Models\TaxDeclaration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class DocumentController extends Controller
{
    private const DOCUMENT_TYPES = [
        'original_scan', 'compressed_copy', 'pdf_copy', 'ocr_text', 'supporting',
        'transfer', 'land_title', 'tax_receipt', 'survey_plan', 'sketch_plan', 'legal', 'other',
    ];

    public function listAll(Request $request)
    {
        $query = PropertyDocument::with([
            'taxDeclaration:id,td_number,arp_number,owner_id',
            'taxDeclaration.owner:id,owner_name',
            'uploadedBy:id,name',
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%")
                    ->orWhereHas('taxDeclaration', fn ($td) => $td
                        ->where('td_number', 'like', "%{$search}%")
                        ->orWhere('arp_number', 'like', "%{$search}%")
                        ->orWhereHas('owner', fn ($o) => $o->where('owner_name', 'like', "%{$search}%")));
            });
        }

        if ($type = $request->input('document_type')) {
            $query->where('document_type', $type);
        }

        if ($tdId = $request->input('tax_declaration_id')) {
            $query->where('tax_declaration_id', $tdId);
        }

        $counts = PropertyDocument::query()
            ->selectRaw('document_type, COUNT(*) as count')
            ->groupBy('document_type')
            ->pluck('count', 'document_type');

        $paginator = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'type_counts' => $counts,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tax_declaration_id' => 'required|exists:tax_declarations,id',
            'file' => 'required|file|max:51200',
            'document_type' => 'required|in:' . implode(',', self::DOCUMENT_TYPES),
            'title' => 'required|string|max:255',
        ]);

        $taxDeclaration = TaxDeclaration::findOrFail($request->tax_declaration_id);

        return $this->createDocument($request, $taxDeclaration);
    }

    public function index(Request $request, TaxDeclaration $taxDeclaration)
    {
        return response()->json($taxDeclaration->documents()->with('uploadedBy:id,name')->get());
    }

    public function upload(Request $request, TaxDeclaration $taxDeclaration)
    {
        $request->validate([
            'file' => 'required|file|max:51200',
            'document_type' => 'required|in:' . implode(',', self::DOCUMENT_TYPES),
            'title' => 'required|string|max:255',
        ]);

        return $this->createDocument($request, $taxDeclaration);
    }

    private function createDocument(Request $request, TaxDeclaration $taxDeclaration)
    {
        $file = $request->file('file');
        $path = $file->store("documents/{$taxDeclaration->td_number}", 'public');

        $doc = PropertyDocument::create([
            'tax_declaration_id' => $taxDeclaration->id,
            'document_type' => $request->document_type,
            'title' => $request->title,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        return response()->json(
            $doc->load([
                'uploadedBy:id,name',
                'taxDeclaration:id,td_number,arp_number,owner_id',
                'taxDeclaration.owner:id,owner_name',
            ]),
            201
        );
    }

    public function download(PropertyDocument $document)
    {
        $fullPath = Storage::disk('public')->path($document->file_path);
        return response()->download($fullPath, $document->file_name);
    }

    public function destroy(PropertyDocument $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return response()->json(['message' => 'Document deleted.']);
    }

    public function uploadImage(Request $request, TaxDeclaration $taxDeclaration)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
            'image_type' => 'required|in:front,rear,left,right,road,landmark,aerial,additional',
            'caption' => 'nullable|string|max:255',
        ]);

        $file = $request->file('image');
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $dir = "property-images/{$taxDeclaration->td_number}";

        $img = Image::read($file);
        $img->scale(width: 1920);
        Storage::disk('public')->put("{$dir}/{$filename}", $img->toJpeg(85));

        $thumb = Image::read($file);
        $thumb->scale(width: 400);
        Storage::disk('public')->put("{$dir}/thumb_{$filename}", $thumb->toJpeg(70));

        $image = PropertyImage::create([
            'tax_declaration_id' => $taxDeclaration->id,
            'image_type' => $request->image_type,
            'file_path' => "{$dir}/{$filename}",
            'file_name' => $filename,
            'mime_type' => 'image/jpeg',
            'file_size' => Storage::disk('public')->size("{$dir}/{$filename}"),
            'thumbnail_path' => "{$dir}/thumb_{$filename}",
            'caption' => $request->caption,
            'uploaded_by' => Auth::id(),
        ]);

        return response()->json($image, 201);
    }

    public function getImages(TaxDeclaration $taxDeclaration)
    {
        return response()->json($taxDeclaration->images()->orderBy('image_type')->orderBy('sort_order')->get()
            ->map(function ($img) {
                $img->url = asset('storage/' . $img->file_path);
                $img->thumbnail_url = $img->thumbnail_path ? asset('storage/' . $img->thumbnail_path) : $img->url;
                return $img;
            }));
    }

    public function deleteImage(PropertyImage $image)
    {
        Storage::disk('public')->delete([$image->file_path, $image->thumbnail_path ?? '']);
        $image->delete();
        return response()->json(['message' => 'Image deleted.']);
    }
}


