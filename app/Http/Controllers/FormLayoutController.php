<?php

namespace App\Http\Controllers;

use App\Models\FormLayout;
use App\Models\FormLayoutEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormLayoutController extends Controller
{
    public function index(Request $request)
    {
        $target = $request->validate([
            'target' => 'nullable|in:tax_declaration,field_appraisal',
        ])['target'] ?? null;

        return response()->json(
            FormLayout::query()
                ->when($target, fn ($query) => $query->where('target', $target))
                ->select(['id', 'name', 'target', 'created_at', 'updated_at'])
                ->withCount('entries')
                ->latest()
                ->get()
        );
    }

    public function show(FormLayout $formLayout)
    {
        return response()->json($formLayout);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'target' => 'required|in:tax_declaration,field_appraisal',
            'fields' => 'present|array|max:80',
            'page' => 'nullable|array',
        ]);
        $page = $this->pageSetup($data['page'] ?? null);

        $layout = FormLayout::create([
            'name' => trim($data['name']),
            'target' => $data['target'],
            'page' => $page,
            'fields' => $this->elements($data['fields'], $page),
        ]);

        return response()->json($layout, 201);
    }

    public function update(Request $request, FormLayout $formLayout)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'fields' => 'present|array|max:80',
            'page' => 'nullable|array',
        ]);
        $page = $this->pageSetup($data['page'] ?? $formLayout->page);

        $formLayout->update([
            'name' => trim($data['name']),
            'page' => $page,
            'fields' => $this->elements($data['fields'], $page),
        ]);

        return response()->json($formLayout);
    }

    public function destroy(FormLayout $formLayout)
    {
        $formLayout->delete();

        return response()->json(['message' => 'Form deleted.']);
    }

    public function uploadImage(Request $request, FormLayout $formLayout)
    {
        $request->validate([
            'image' => 'required|image|max:4096',
        ]);

        $path = $request->file('image')->store('form-layouts/'.$formLayout->id, 'public');

        return response()->json([
            'url' => '/storage/'.ltrim(str_replace('\\', '/', $path), '/'),
        ]);
    }

    public function storeEntry(Request $request, FormLayout $formLayout)
    {
        $data = $request->validate([
            'values' => 'present|array',
        ]);

        $entry = FormLayoutEntry::create([
            'form_layout_id' => $formLayout->id,
            'values' => $this->answers($formLayout, $data['values']),
            'created_by' => Auth::id(),
        ]);

        return response()->json($entry, 201);
    }

    private function pageSetup(?array $page): array
    {
        $sizes = [
            'letter' => [816, 1056],
            'legal' => [816, 1344],
            'folio' => [816, 1248],
            'a4' => [794, 1123],
            'a5' => [559, 794],
            'executive' => [696, 1008],
            'tabloid' => [1056, 1632],
        ];
        $presets = [
            'normal' => ['top' => 96, 'right' => 96, 'bottom' => 96, 'left' => 96],
            'narrow' => ['top' => 48, 'right' => 48, 'bottom' => 48, 'left' => 48],
            'moderate' => ['top' => 96, 'right' => 72, 'bottom' => 96, 'left' => 72],
            'wide' => ['top' => 96, 'right' => 192, 'bottom' => 96, 'left' => 192],
            'mirrored' => ['top' => 96, 'right' => 96, 'bottom' => 96, 'left' => 120],
            'office' => ['top' => 96, 'right' => 120, 'bottom' => 96, 'left' => 120],
        ];
        $columnCounts = ['one' => 1, 'two' => 2, 'three' => 3, 'left' => 2, 'right' => 2];

        $page = is_array($page) ? $page : [];
        $size = isset($sizes[$page['size'] ?? '']) ? $page['size'] : 'a4';
        [$short, $long] = $sizes[$size];
        $orientation = ($page['orientation'] ?? '') === 'landscape' ? 'landscape' : 'portrait';
        $width = $orientation === 'landscape' ? $long : $short;
        $height = $orientation === 'landscape' ? $short : $long;

        $margin = $page['margin'] ?? 'normal';
        if (!isset($presets[$margin]) && $margin !== 'custom') {
            $margin = 'normal';
        }
        $incoming = is_array($page['margins'] ?? null) ? $page['margins'] : [];
        $source = $margin === 'custom' ? [
            'top' => $incoming['top'] ?? 96,
            'right' => $incoming['right'] ?? 96,
            'bottom' => $incoming['bottom'] ?? 96,
            'left' => $incoming['left'] ?? 96,
        ] : $presets[$margin];
        $maxY = max(0, ($height - 72) / 2);
        $maxX = max(0, ($width - 72) / 2);
        $margins = [
            'top' => $this->bound($source['top'], 0, $maxY),
            'right' => $this->bound($source['right'], 0, $maxX),
            'bottom' => $this->bound($source['bottom'], 0, $maxY),
            'left' => $this->bound($source['left'], 0, $maxX),
        ];

        $columns = $page['columns'] ?? 'one';
        if (!isset($columnCounts[$columns]) && $columns !== 'custom') {
            $columns = 'one';
        }
        $columnCount = $columns === 'custom'
            ? (int) $this->bound($page['columnCount'] ?? 2, 1, 4)
            : $columnCounts[$columns];

        return [
            'size' => $size,
            'orientation' => $orientation,
            'margin' => $margin,
            'margins' => $margins,
            'columns' => $columns,
            'columnCount' => $columnCount,
            'columnGap' => $this->bound($page['columnGap'] ?? 48, 0, 96),
            'width' => $width,
            'height' => $height,
        ];
    }

    private function elements(array $fields, array $page): array
    {
        $ids = [];
        $elements = [];
        $pageWidth = $page['width'];
        $pageHeight = $page['height'];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $type = $field['type'] ?? '';
            $id = (string) ($field['id'] ?? '');
            if (!in_array($type, FormLayout::TYPES, true) || !preg_match('/^[A-Za-z0-9_-]{1,40}$/', $id) || in_array($id, $ids, true)) {
                continue;
            }
            if (in_array($type, ['docHeader', 'docFooter'], true)) {
                foreach ($elements as $existing) {
                    if (($existing['type'] ?? '') === $type) {
                        continue 2;
                    }
                }
            }
            $ids[] = $id;

            $minH = $type === 'line' ? 2 : ($type === 'pageNumber' ? 20 : 24);
            $minW = $type === 'pageNumber' ? 36 : 48;
            $w = $this->bound($field['w'] ?? 180, $minW, $pageWidth);
            $h = $this->bound($field['h'] ?? 40, $minH, $pageHeight);
            $x = $this->bound($field['x'] ?? 0, 0, $pageWidth - $w);
            $y = $this->bound($field['y'] ?? 0, 0, $pageHeight - $h);
            if ($type === 'docHeader' || $type === 'docFooter') {
                $h = $this->bound($field['h'] ?? 72, 36, 180);
                $w = $pageWidth;
                $x = 0;
                $y = $type === 'docHeader' ? 0 : $pageHeight - $h;
            }

            $options = [];
            if ($type === 'select') {
                foreach ($field['options'] ?? [] as $option) {
                    $option = trim((string) $option);
                    if ($option !== '' && !in_array($option, $options, true)) {
                        $options[] = mb_substr($option, 0, 80);
                    }
                }
            }

            $cells = $type === 'table' ? $this->cells($field['cells'] ?? [], $pageWidth) : [];
            if ($type === 'table' && count($cells) < 1) {
                continue;
            }

            $fonts = ['Times New Roman', 'Arial', 'Calibri', 'Georgia', 'Courier New'];
            $font = $field['fontFamily'] ?? 'Times New Roman';

            $elements[] = [
                'id' => $id,
                'type' => $type,
                'x' => $x,
                'y' => $y,
                'w' => $w,
                'h' => $h,
                'text' => mb_substr(trim((string) ($field['text'] ?? '')), 0, 2000),
                'image' => $this->imageUrl($field['image'] ?? null),
                'required' => (bool) ($field['required'] ?? false),
                'options' => $options,
                'fontFamily' => in_array($font, $fonts, true) ? $font : 'Times New Roman',
                'fontSize' => (int) $this->bound($field['fontSize'] ?? 11, 8, 72),
                'bold' => (bool) ($field['bold'] ?? false),
                'italic' => (bool) ($field['italic'] ?? false),
                'underline' => (bool) ($field['underline'] ?? false),
                'align' => in_array($field['align'] ?? 'left', ['left', 'center', 'right', 'justify'], true) ? ($field['align'] ?? 'left') : 'left',
                'listType' => $this->listType($field['listType'] ?? 'none'),
                'listStyle' => $this->listStyle($field['listType'] ?? 'none', $field['listStyle'] ?? 'none'),
                'indent' => (int) $this->bound($field['indent'] ?? 0, 0, 8),
                'lineHeight' => $this->lineHeight($field['lineHeight'] ?? 1.15),
                'spaceBefore' => (int) $this->bound($field['spaceBefore'] ?? 0, 0, 36),
                'spaceAfter' => (int) $this->bound($field['spaceAfter'] ?? 0, 0, 36),
                'shading' => $this->optionalColor($field['shading'] ?? null),
                'variant' => ($field['variant'] ?? '') === 'columns' ? 'columns' : 'blank',
                'parts' => $this->parts($field['parts'] ?? null),
                'numberFormat' => in_array($field['numberFormat'] ?? 'plain', ['plain', 'page', 'pageOf'], true) ? $field['numberFormat'] : 'plain',
                'anchor' => in_array($field['anchor'] ?? 'free', ['top', 'bottom', 'free'], true) ? $field['anchor'] : 'free',
                'border' => $this->border($field['border'] ?? []),
                'borderColor' => $this->color($field['borderColor'] ?? '#000000'),
                'borderWidth' => (int) $this->bound($field['borderWidth'] ?? 1, 1, 6),
                'gridlines' => (bool) ($field['gridlines'] ?? true),
                'cells' => $cells,
                'colWidths' => $type === 'table' ? $this->gridSizes($field['colWidths'] ?? null, count($cells[0] ?? []), $w, 36) : [],
                'rowHeights' => $type === 'table' ? $this->gridSizes($field['rowHeights'] ?? null, count($cells), $h, 22) : [],
            ];
        }

        return $elements;
    }

    private function answers(FormLayout $layout, array $incoming): array
    {
        $out = [];
        foreach ($layout->fields ?? [] as $field) {
            if (($field['type'] ?? '') === 'table') {
                foreach ($field['cells'] ?? [] as $row) {
                    foreach ($row as $cell) {
                        $id = $cell['id'] ?? null;
                        if (!$id) {
                            continue;
                        }
                        $value = $incoming[$id] ?? null;
                        $out[$id] = is_scalar($value) ? mb_substr(trim((string) $value), 0, 500) : null;
                    }
                }
                continue;
            }

            if (!in_array($field['type'], ['text', 'textarea', 'number', 'date', 'checkbox', 'select'], true)) {
                continue;
            }
            $id = $field['id'];
            $value = $incoming[$id] ?? null;
            $out[$id] = match ($field['type']) {
                'number' => ($value === '' || $value === null || !is_numeric($value)) ? null : (0 + $value),
                'checkbox' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'select' => in_array($value, $field['options'] ?? [], true) ? $value : null,
                'date' => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value) ? substr($value, 0, 10) : null,
                default => is_scalar($value) ? mb_substr(trim((string) $value), 0, 2000) : null,
            };
        }

        return $out;
    }

    private function bound(mixed $value, float $min, float $max): float
    {
        $number = is_numeric($value) ? (float) $value : $min;

        return round(min($max, max($min, $number)), 1);
    }

    private function cells(mixed $cells, float $pageWidth): array
    {
        if (!is_array($cells)) {
            return [];
        }

        $rows = [];
        foreach (array_slice($cells, 0, 10) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $line = [];
            foreach (array_slice($row, 0, 10) as $cell) {
                $id = is_array($cell) ? (string) ($cell['id'] ?? '') : '';
                if (!preg_match('/^[A-Za-z0-9_-]{1,40}$/', $id)) {
                    $id = 'c_'.substr(md5(uniqid('', true)), 0, 8);
                }
                $width = is_array($cell) ? ($cell['w'] ?? null) : null;
                $line[] = [
                    'id' => $id,
                    'text' => mb_substr(trim((string) (is_array($cell) ? ($cell['text'] ?? '') : '')), 0, 300),
                    'w' => is_numeric($width) ? (float) $this->bound($width, 36, $pageWidth) : null,
                ];
            }
            if ($line) {
                $rows[] = $line;
            }
        }

        return $rows;
    }

    private function border(mixed $border): array
    {
        $keys = ['top', 'right', 'bottom', 'left', 'insideH', 'insideV', 'diagonalDown', 'diagonalUp'];
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = (bool) (is_array($border) ? ($border[$key] ?? false) : false);
        }

        return $out;
    }

    private function color(mixed $value): string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : '#000000';
    }

    private function gridSizes(mixed $values, int $count, float $total, float $min): array
    {
        if ($count < 1) {
            return [];
        }
        $values = is_array($values) ? array_values($values) : [];
        if (count($values) !== $count) {
            return array_fill(0, $count, round(max($min, $total / $count), 1));
        }

        return array_map(fn ($value) => (float) $this->bound($value, $min, max($min, $total)), $values);
    }

    private function parts(mixed $parts): array
    {
        $parts = is_array($parts) ? $parts : [];
        $out = [];
        foreach (['left', 'center', 'right'] as $key) {
            $out[$key] = mb_substr(trim((string) ($parts[$key] ?? '')), 0, 300);
        }

        return $out;
    }

    private function optionalColor(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : null;
    }

    private function listType(mixed $value): string
    {
        return in_array($value, ['bullet', 'number', 'multi'], true) ? $value : 'none';
    }

    private function listStyle(mixed $type, mixed $style): string
    {
        $allowed = [
            'none' => ['none'],
            'bullet' => ['disc', 'circle', 'square'],
            'number' => ['decimal', 'paren', 'alpha', 'Alpha', 'roman'],
            'multi' => ['outline', 'legal'],
        ];
        $type = $this->listType($type);
        $choices = $allowed[$type];

        return in_array($style, $choices, true) ? $style : $choices[0];
    }

    private function lineHeight(mixed $value): float
    {
        $raw = is_numeric($value) ? (float) $value : 1.15;
        foreach ([1, 1.15, 1.5, 2, 2.5, 3] as $height) {
            if (abs($raw - $height) < 0.01) {
                return $height;
            }
        }

        return 1.15;
    }

    private function imageUrl(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH) ?: $value;
        if (str_starts_with($path, '/images/') || str_starts_with($path, '/storage/')) {
            return $path;
        }

        return null;
    }
}
