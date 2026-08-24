<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OcrResult;
use App\Models\OcrLog;
use App\Models\TaxDeclaration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * OCR Controller (shared primitives)
 *
 * This controller is deliberately kept small — it exposes only the two
 * operations that multiple pages need to share:
 *
 *   - upload(): store an image/PDF and create an OcrResult row.
 *   - scan():   send that file to OCR.space and extract fields.
 *
 * Consumers:
 *   - Tax Declaration form  (auto-fill on file upload)
 *   - Field Appraisal form  (auto-fill on file upload)
 *   - OCR Management page   (via OcrManagementController for listing/review)
 *
 * Management-page-specific work (listing, review, batch, delete, apply)
 * lives in {@see OcrManagementController} so changes to the admin page
 * do not affect the TD / Field Appraisal flows.
 */
class OcrController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'tax_declaration_id' => 'nullable|exists:tax_declarations,id',
        ]);

        $file = $request->file('file');
        $path = $file->store('ocr-uploads', 'public');

        $ocrResult = OcrResult::create([
            'tax_declaration_id' => $request->tax_declaration_id,
            'source_file' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'source_type' => $file->getMimeType() === 'application/pdf' ? 'pdf' : 'image',
            'status' => 'pending',
        ]);

        OcrLog::create([
            'ocr_result_id' => $ocrResult->id,
            'user_id' => Auth::id(),
            'action' => 'upload',
            'notes' => 'File uploaded for OCR processing',
        ]);

        return response()->json($ocrResult, 201);
    }

    public function scan(OcrResult $ocrResult)
    {
        $ocrResult->update(['status' => 'processing']);

        try {
            $filePath  = Storage::disk('public')->path($ocrResult->source_file);
            $apiKey    = config('services.ocr_space.api_key');
            $endpoint  = config('services.ocr_space.endpoint');

            // OCR.space accepts multipart file uploads or base64
            $response = Http::withHeaders(['apikey' => $apiKey])
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post($endpoint, [
                    'language'          => 'eng',
                    'isOverlayRequired' => 'false',
                    'OCREngine'         => '2',       // Engine 2 handles mixed-orientation text better
                    'scale'             => 'true',
                    'isTable'           => 'true',    // Preserves tabular layout common in tax forms
                ]);

            $body = $response->json();

            if ($response->failed() || ($body['IsErroredOnProcessing'] ?? true)) {
                $errMsg = $body['ErrorMessage'][0] ?? $body['ParsedResults'][0]['ErrorMessage'] ?? 'OCR.space error';
                throw new \RuntimeException($errMsg);
            }

            $rawText   = collect($body['ParsedResults'] ?? [])->pluck('ParsedText')->implode("\n");
            $exitCode  = $body['ParsedResults'][0]['FileParseExitCode'] ?? 0;

            $extractedFields = $this->extractFields($rawText);

            // Confidence reflects how much of an actual Tax Declaration we recognized —
            // not just whether OCR.space could technically read the image. A clear photo
            // of an unrelated subject (e.g. a face) still "parses" successfully but yields
            // no TD fields, so it must score low rather than randomly high.
            $confidence = $this->calculateConfidence($rawText, $extractedFields, (int) $exitCode);

            $ocrResult->update([
                'raw_text'         => $rawText,
                'extracted_fields' => $extractedFields,
                'confidence_score' => $confidence,
                'status'           => 'completed',
                'processed_by'     => Auth::id(),
                'processed_at'     => now(),
            ]);

            OcrLog::create([
                'ocr_result_id' => $ocrResult->id,
                'user_id'       => Auth::id(),
                'action'        => 'scan',
                'notes'         => "OCR.space scan completed. Confidence: {$confidence}%",
            ]);

            if ($ocrResult->tax_declaration_id) {
                $td = TaxDeclaration::find($ocrResult->tax_declaration_id);
                $td?->update(['status' => 'ocr_review']);
            }

        } catch (\Exception $e) {
            $ocrResult->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return response()->json($ocrResult->fresh());
    }

    /**
     * Score how confident we are that the scanned document is actually a
     * usable Tax Declaration — based on how much real data was recognized,
     * not merely whether the OCR engine could technically read the file.
     *
     * A photo of an unrelated subject (a face, a blank wall, etc.) will
     * still "successfully" OCR with FileParseExitCode 1, but extractFields()
     * will find none of the ~20+ known TD fields — that case must score
     * near zero instead of a random high number.
     */
    private function calculateConfidence(string $rawText, array $extractedFields, int $exitCode): int
    {
        $textLength = mb_strlen(trim(preg_replace('/\s+/', ' ', $rawText)));

        $fieldsFound = collect($extractedFields)->filter(function ($value) {
            return is_array($value) ? count($value) > 0 : trim((string) $value) !== '';
        })->count();

        // How well OCR.space itself says it parsed the file (1=success, 2=warning, 3+=error).
        $engineScore = match ($exitCode) {
            1 => 1.0,
            2 => 0.75,
            default => 0.5,
        };

        if ($fieldsFound === 0) {
            // No recognizable TD field at all — cap well below the "usable" threshold
            // even if some unrelated text happened to be picked up.
            $base = $textLength > 20 ? 12 : 3;
            return (int) round($base * $engineScore);
        }

        // A genuine, fully-readable TD page typically yields 10+ distinct fields.
        $fieldRatio = min(1, $fieldsFound / 10);
        $textDensityScore = min(1, $textLength / 500);

        $score = (($fieldRatio * 0.75) + ($textDensityScore * 0.25)) * $engineScore;

        return (int) round(max(5, min(99, $score * 100)));
    }

    /**
     * Extract fields from OCR text.
     * Handles all common Philippine Tax Declaration of Real Property layouts
     * (various municipalities/provinces following R.A. 7160 format).
     */
    private function extractFields(string $text): array
    {
        $fields = [];
        try {

        // Normalize OCR quirks: smart quotes, multiple spaces, encoding issues
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x{201C}\x{201D}]/u', '"', $text);
        $text = preg_replace('/[\x{2018}\x{2019}]/u', "'", $text);

        // Known labels — reject captures that are just other field names
        $labelBlacklist = '/^(TIN|Telephone|Address|Administrator|Beneficial|Location|Property|PROPERTY|OCT|Survey|CCT|Lot|Block|Dated|Boundaries|North|South|East|West|KIND|LAND|BUILDING|MACHINERY|Others|Classification|TOTAL|Taxable|Exempt|Effectivity|Approved|cancels|Owner|Previous|Memoranda|Notes|No|Number|Street|Barangay|District|Municipality|Province|City|Occupant)$/i';

        $valid = function ($val, $minLen = 2) use ($labelBlacklist) {
            $clean = trim($val, " \t\n\r\0\x0B.:,");
            return strlen($clean) >= $minLen && !preg_match($labelBlacklist, $clean);
        };

        $money = function ($val) {
            $v = preg_replace('/[^\d.,\-]/', '', trim((string) $val));
            if ($v === '' || $v === null) {
                return $v;
            }

            $hasComma = str_contains($v, ',');
            $hasDot = str_contains($v, '.');

            if ($hasComma && $hasDot) {
                // Last separator is the decimal mark: 1.234,56 or 1,234.56
                if (strrpos($v, ',') > strrpos($v, '.')) {
                    $v = str_replace('.', '', $v);
                    $v = str_replace(',', '.', $v);
                } else {
                    $v = str_replace(',', '', $v);
                }
            } elseif ($hasComma) {
                // 5,280,00 / 5280,00 / 5,280
                if (substr_count($v, ',') > 1) {
                    $pos = strrpos($v, ',');
                    $v = str_replace(',', '', substr($v, 0, $pos)) . '.' . substr($v, $pos + 1);
                } elseif (preg_match('/,\d{1,2}$/', $v)) {
                    $v = str_replace(',', '.', $v);
                } else {
                    $v = str_replace(',', '', $v);
                }
            } elseif ($hasDot && substr_count($v, '.') > 1) {
                // OCR thousands dots: 5.280.00 → 5280.00
                $pos = strrpos($v, '.');
                $v = str_replace('.', '', substr($v, 0, $pos)) . '.' . substr($v, $pos + 1);
            }

            return $v;
        };

        // ══════════════════════════════════════════════════════════════════
        // SECTION 1: Declaration Header
        // ══════════════════════════════════════════════════════════════════

        // TD No. — formats: "TD No.: 2016-04-028-0940", "TD No. 01234", "T.D. No. 123"
        $tdPatterns = [
            '/T\.?D\.?\s*No\.?\s*:?\s*([\d][\d\w\-]+)/i',
            '/Tax\s+Declaration\s+No\.?\s*:?\s*([\d][\d\w\-]+)/i',
            '/Declaration\s+No\.?\s*:?\s*([\d][\d\w\-]+)/i',
        ];
        foreach ($tdPatterns as $p) {
            if (preg_match($p, $text, $m)) { $fields['td_number'] = trim($m[1]); break; }
        }

        // ARP No. (also used as primary ID on FAAS forms)
        if (preg_match('/A\.?R\.?P\.?\s*No\.?\s*:?\s*([\d][\d\w\-]+)/i', $text, $m)) {
            $fields['arp_number'] = trim($m[1]);
            $fields['arp_no'] = trim($m[1]);
            if (!isset($fields['td_number'])) {
                $fields['td_number'] = trim($m[1]);
            }
        }

        // Update Code (FAAS header) — require real code, not the leading "A" from "A.R.P."
        if (preg_match('/Update\s*Code\s*:?\s*([A-Za-z0-9][\w\-]{1,20})/i', $text, $m)) {
            $val = trim($m[1]);
            if ($valid($val, 2)
                && !preg_match('/^(A\.?R\.?P|P\.?I\.?N|OCT|TCT|KOT|Survey|Cad|PLS|No)$/i', $val)
                && !preg_match('/^A$/i', $val)
            ) {
                $fields['update_code'] = $val;
            }
        }

        // Property Identification No. / PIN
        $pinPatterns = [
            '/Property\s+Identification\s+No\.?\s*:?\s*([\d][\d\-]+[\d])/i',
            '/P\.?I\.?N\.?\s*:?\s*([\d][\d\-]+[\d])/i',
            '/\bPIN\s*:?\s*([\d][\d\-]{5,})/i',
        ];
        foreach ($pinPatterns as $p) {
            if (preg_match($p, $text, $m)) {
                $fields['property_identification_no'] = trim($m[1]);
                $fields['pin'] = trim($m[1]);
                break;
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // SECTION 2: Owner / Address
        // ══════════════════════════════════════════════════════════════════

        // Owner — never use "Previous Owner". Prefer FAAS "Owner : NAME Address :"
        $ownerCandidates = [];
        if (preg_match('/(?:^|\n)\s*Owner\s*:?\s*([A-ZÑ][A-Za-zÑñ\s,\.]+?)\s+Address\s*:?/im', $text, $m)) {
            $ownerCandidates[] = $m[1];
        }
        if (preg_match('/(?:^|\n)\s*Owner\s*:?\s*([A-ZÑ][A-Za-zÑñ\s,\.]{2,}?)(?=\s*(?:TIN|T\.?I\.?N|Telephone|Tel\.?|Address|Admin|Taxab[a-z]{0,10}|Taxable|\n|$))/im', $text, $m)) {
            $ownerCandidates[] = $m[1];
        }
        // Name on next line after Owner label
        if (preg_match('/(?:^|\n)\s*Owner\s*:?\s*\n\s*([A-ZÑ][A-Za-zÑñ\s,\.]{2,})/im', $text, $m)) {
            $ownerCandidates[] = $m[1];
        }
        // Loose same-line (OCR may drop capitals / colons)
        if (preg_match('/(?:^|\n)\s*Owner\s*:?\s*(.+?)(?=\s+Address\b|\s+T\.?I\.?N\b|\s+Tel\b|\n)/im', $text, $m)) {
            $ownerCandidates[] = $m[1];
        }
        foreach ($ownerCandidates as $rawOwner) {
            $val = $this->cleanPersonName($rawOwner);
            if ($valid($val, 3)) {
                $fields['owner_name'] = $val;
                break;
            }
        }

        // TIN (minimum 9 characters like 123-456-789)
        if (preg_match('/\bT\.?I\.?N\.?\s*:?\s*([\d][\d\-]{7,})/i', $text, $m)) {
            $fields['tin'] = trim($m[1]);
            $fields['owner_tin'] = trim($m[1]);
        }

        // Telephone / Contact
        if (preg_match('/(?:Telephone|Tel\.?\s*No\.?|Contact|Cell|Mobile)\s*:?\s*([\d][\d\-\(\)\+\s]{6,})/i', $text, $m)) {
            $fields['telephone'] = trim($m[1]);
            $fields['owner_telephone'] = trim($m[1]);
        }

        // Reject values that are actually the field label (incl. common OCR typos).
        // e.g. blank forms make regex capture "Beneficial Uscr" (User misread) or "Telephone No."
        // as if they were the field values.
        $isLabelLike = function ($val): bool {
            $v = trim((string) $val, " \t\n\r\0\x0B.:,");
            if ($v === '') return true;
            // Bare labels (any case)
            $bareLabels = '/^(TIN|Telephone|Tel\.?|Tel\.?\s*No\.?|Address|Administrator|Beneficial|Beneficial\s*U\w{0,4}|Administrator\s*\/?\s*Beneficial\s*U\w{0,4}|Occupant|Location|Property|Property\s+Location|Owner|Number|Street|Barangay|District|Municipality|Province|City)\.?$/i';
            if (preg_match($bareLabels, $v)) return true;
            // OCR variants of "User" (Uscr, Usr, Usor, Uscn, etc.) after "Beneficial"
            if (preg_match('/^Beneficial\s+U[a-z]{1,4}\.?$/i', $v)) return true;
            return false;
        };

        // Address — FAAS puts Address on the Owner row; also standalone / next-line
        $addrCandidates = [];
        if (preg_match('/\bOwner\b[^\n]{0,80}?\bAddress\s*:?\s*([A-Z0-9Ññ][A-Za-zÑñ0-9\s,\.\-#]+?)(?=\s*(?:T\.?I\.?N|TIN|Tel|Telephone|Administrator|Admin|\n|$))/im', $text, $m)) {
            $addrCandidates[] = $m[1];
        }
        if (preg_match('/(?:^|\n)\s*Address\s*:?\s*([A-Z0-9Ññ][A-Za-zÑñ0-9\s,\.\-#]+?)(?=\s*(?:T\.?I\.?N|TIN|Tel|Telephone|Administrator|Beneficial|Location|Property\s+Location|\n|$))/im', $text, $m)) {
            $addrCandidates[] = $m[1];
        }
        if (preg_match('/(?:^|\n)\s*Address\s*:?\s*\n\s*([A-Z0-9Ññ][A-Za-zÑñ0-9\s,\.\-#]{4,})/im', $text, $m)) {
            $addrCandidates[] = $m[1];
        }
        // Province-anchored (common PH addresses)
        if (preg_match('/Address\s*:?\s*([A-Z0-9Ññ][A-Za-zÑñ0-9\s,\.\-#]+?(?:Sur|Norte|City|Leyte|Samar|Cebu|Manila|Quezon|Laguna|Batangas|Cavite|Rizal|Bulacan|Pampanga|Pangasinan|Camarines\s*Sur|Camarines\s*Norte|Albay|Iloilo|Davao|Zambales|Isabela|Nueva\s*\w+))/im', $text, $m)) {
            $addrCandidates[] = $m[1];
        }
        foreach ($addrCandidates as $rawAddr) {
            $val = rtrim(trim(preg_replace('/\s+/', ' ', $rawAddr)), ' ,');
            $val = preg_replace('/\s+(Telephone|Tel\.?|Administrator|Beneficial|TIN|T\.?I\.?N|Location|Property|Taxab[a-z]{0,10})\b.*$/i', '', $val);
            // Strip trailing " No." / " No" (common on Telephone No. label leakage) then re-check
            $val = preg_replace('/\s+No\.?\s*$/i', '', $val);
            if (preg_match('/^(PROPERTY|LOCATION|PROPERTY\s+LOCATION)$/i', $val)) continue;
            if ($isLabelLike($val)) continue;
            if ($valid($val, 4)) {
                $fields['address'] = $val;
                $fields['owner_address'] = $val;
                break;
            }
        }

        // Administrator / Beneficial User / Occupant (FAAS)
        // Tolerate OCR typos of "User" (Uscr, Usr, Usor) in the *label* half so the
        // capture group doesn't swallow the label itself as the value.
        $benUser = 'Beneficial\s*U\w{1,4}';
        $adminPatterns = [
            '/(?:Administrator|' . $benUser . ')[\/\s]*(?:' . $benUser . ')?\s*:?\s*([A-Z][A-Za-zÑñ\s,\.]{3,}?)(?=\s{2,}|\s+(?:TIN|Tel|Address|Location|\n))/im',
            '/Administrator\s*\/\s*Occupant\s*:?\s*([A-Z][A-Za-zÑñ\s,\.]{3,}?)(?=\s{2,}|\s+(?:Address|TIN|Tel|\n))/im',
            '/Occupant\s*:?\s*([A-Z][A-Za-zÑñ\s,\.]{3,}?)(?=\s{2,}|\s+(?:Address|TIN|Tel|\n))/im',
        ];
        foreach ($adminPatterns as $p) {
            if (preg_match($p, $text, $m)) {
                $val = $this->cleanPersonName($m[1]);
                if ($isLabelLike($val)) continue;
                if ($valid($val, 3)) { $fields['administrator'] = $val; break; }
            }
        }

        // Administrator address (line after Administrator when labeled Address)
        if (isset($fields['administrator']) && preg_match('/Administrator[^\n]*\n\s*Address\s*:?\s*([A-Z0-9][A-Za-zÑñ0-9\s,\.\-#]+?)(?=\s{2,}|\n)/im', $text, $m)) {
            $val = rtrim(trim($m[1]), ' ,');
            if ($valid($val, 4)) $fields['administrator_address'] = $val;
        }

        // ══════════════════════════════════════════════════════════════════
        // SECTION 3: Location of Property
        // ══════════════════════════════════════════════════════════════════

        $locationStop = '(?=\s+(?:OCT|Survey|CCT|Lot|Block|Boundaries|Bound|Title|TCT|\n))';

        // PH standard TD: values on line after column headers
        // e.g. header line with (Barangay/District) then "San Vicente Bato, Camarines Sur"
        if (preg_match('/Location\s+(?:of\s+)?Property[^\n]*\n\s*([A-Za-zÑñ][A-Za-zÑñ\s\.]+)\s+([A-Za-zÑñ]+)\s*,\s*([A-Za-zÑñ\s\.]+?)' . $locationStop . '/im', $text, $m)) {
            $brgy = trim($m[1]);
            if ($valid($brgy, 2) && !preg_match('/^(Number|Street|Barangay|District|Municipality|Province|City|Location|Property|and)$/i', $brgy)) {
                $fields['barangay'] = $brgy;
            }
            if ($valid(trim($m[2]), 2)) $fields['municipality'] = trim($m[2]);
            if ($valid(trim($m[3]), 2)) $fields['province'] = trim($m[3]);
        }

        // Same line after parenthetical column labels (skip empty street column)
        if (empty($fields['barangay']) && preg_match('/Location\s+(?:of\s+)?Property\s*:?\s*(?:\([^)]+\)\s*)+([A-Za-zÑñ][A-Za-zÑñ\s\.]+)\s+([A-Za-zÑñ]+)\s*,\s*([A-Za-zÑñ\s\.]+?)' . $locationStop . '/im', $text, $m)) {
            $brgy = trim($m[1]);
            if ($valid($brgy, 2) && !preg_match('/^(Number|Street|Barangay|District|Municipality|Province|City|Location|Property|and)$/i', $brgy)) {
                $fields['barangay'] = $brgy;
            }
            if ($valid(trim($m[2]), 2)) $fields['municipality'] = trim($m[2]);
            if ($valid(trim($m[3]), 2)) $fields['province'] = trim($m[3]);
        }

        // Full location line — skip parenthetical labels
        if (preg_match('/Location\s+(?:of\s+)?Property\s*:?\s*(?:\([^)]+\)\s*)+(.+?)' . $locationStop . '/im', $text, $m)) {
            $val = rtrim(trim($m[1]), ' ,');
            if ($valid($val, 3)) $fields['location_street'] = $val;
        } elseif (preg_match('/Location\s+(?:of\s+)?Property\s*:?\s*([A-Za-z0-9Ññ\s,\.\-]+?)' . $locationStop . '/im', $text, $m)) {
            $val = rtrim(trim($m[1]), ' ,');
            if ($valid($val, 3)) $fields['location_street'] = $val;
        }

        // Parse location text: "San Vicente Bato, Camarines Sur" or comma-separated
        $locText = $fields['location_street'] ?? '';
        if (empty($fields['barangay']) && $locText && preg_match('/^([A-Za-zÑñ][A-Za-zÑñ\s\.]+)\s+([A-Za-zÑñ]+)\s*,\s*([A-Za-zÑñ\s\.]+)$/u', $locText, $m)) {
            $fields['barangay'] = trim($m[1]);
            if (empty($fields['municipality'])) $fields['municipality'] = trim($m[2]);
            if (empty($fields['province'])) $fields['province'] = trim($m[3]);
        } elseif ($locText) {
            $segments = array_map('trim', explode(',', $locText));
            $segments = array_values(array_filter($segments, fn($s) => strlen($s) > 1));
            if (count($segments) >= 2) {
                if (empty($fields['barangay'])) $fields['barangay'] = $segments[0];
                if (empty($fields['municipality'])) $fields['municipality'] = $segments[1];
                if (empty($fields['province']) && count($segments) >= 3) $fields['province'] = $segments[2];
            }
        }

        // FAAS labeled location rows: No./Street, Barangay, Municipality, Province of
        // Prefer PROPERTY LOCATION block so Municipality is not skipped / overwritten by weak guesses
        $locBlock = $text;
        if (preg_match('/PROPERTY\s+LOCATION(.{0,600}?)(?=PROPERTY\s+BOUND|LAND\s+SKETCH|LAND\s+APPRAISAL|BOUNDAR|KIND\s+OF\s+LAND|$)/is', $text, $bm)) {
            $locBlock = $bm[1];
        }

        if (preg_match('/No\.?\s*\/?\s*Street\s*:?\s*([A-Za-z0-9Ññ][A-Za-zÑñ0-9\s,\.\-#\/]*?)(?=\s*(?:Barangay|Brgy|Municipality|Mun\.|\n))/im', $locBlock, $m)) {
            $val = rtrim(trim($m[1]), ' ,');
            if ($valid($val, 1)) $fields['location_street'] = $val;
        } elseif (empty($fields['location_street']) && preg_match('/No\.?\s*\/?\s*Street\s*:?\s*([A-Za-z0-9Ññ][A-Za-zÑñ0-9\s,\.\-#\/]*?)(?=\s*(?:Barangay|Brgy|Municipality|\n))/im', $text, $m)) {
            $val = rtrim(trim($m[1]), ' ,');
            if ($valid($val, 1)) $fields['location_street'] = $val;
        }

        // Barangay (labeled) — allow override from PROPERTY LOCATION block
        if (preg_match('/(?:Barangay|Brgy\.?)\s*:?\s*([A-Za-zÑñ][A-Za-zÑñ\s\.\-]+?)(?=\s*(?:Municipality|Mun\.|City|Province|Prov\.|\n))/im', $locBlock, $m)
            || (empty($fields['barangay']) && preg_match('/(?:Barangay|Brgy\.?|District)\s*:?\s*([A-Za-zÑñ][A-Za-zÑñ\s\.\-]+?)(?=\s*(?:Municipality|City|Province|\n))/im', $text, $m))
        ) {
            $val = trim(preg_replace('/\s+/', ' ', $m[1]));
            if ($valid($val, 2) && !preg_match('/^(Municipality|Province|Street|Number)$/i', $val)) {
                $fields['barangay'] = $val;
            }
        }

        // Municipality (labeled) — ALWAYS prefer this over earlier weak parses
        // OCR typos: Munlcipality, Municpality, Mun.
        $munPatterns = [
            '/(?:Municipality|Munlcipality|Municpality|Mun\.?\s*\/?\s*City|Mun\.)\s*:?\s*([A-Za-zÑñ][A-Za-zÑñ\s\.\-]{1,40}?)(?=\s*(?:Province|Prov\.|\n|$))/im',
            '/(?:Municipality|Munlcipality|Municpality|Mun\.)\s*:?\s*([A-Za-zÑñ][A-Za-zÑñ\s\.\-]{1,40}?)(?=\s*,|\s+(?:Province|Prov\.)|\s*(?:\n|$))/im',
        ];
        foreach ($munPatterns as $p) {
            if (preg_match($p, $locBlock, $m) || preg_match($p, $text, $m)) {
                $val = trim(preg_replace('/\s+/', ' ', $m[1]));
                if ($valid($val, 2) && !preg_match('/^(and|or|of|Province|Prov)$/i', $val)) {
                    if (preg_match('/^([A-Za-z])(?:\s+[A-Za-z]){2,}$/', $val)) {
                        $fields['municipality'] = ucwords(strtolower(preg_replace('/\s+/', '', $val)));
                    } else {
                        $fields['municipality'] = $val;
                    }
                    break;
                }
            }
        }

        // Province (labeled) — capture full multi-word names (e.g. CAMARINES SUR)
        if (preg_match('/(?:Province|Prov\.)\s*(?:of)?\s*:?\s*([A-Za-zÑñ][A-Za-zÑñ\s\.]+?)(?=\s*(?:\n|OCT|Survey|Land\s+Sketch|Bound|\b(?:NE|NW|SE|SW)\b|$))/im', $locBlock, $m)
            || preg_match('/(?:Province|Prov\.)\s*(?:of)?\s*:+\s*([A-Za-zÑñ][A-Za-zÑñ\s\.]+?)(?=\s*(?:\n|OCT|Survey|Land\s+Sketch|Bound|\b(?:NE|NW|SE|SW)\b|$))/im', $text, $m)
        ) {
            $val = trim(preg_replace('/\s+/', ' ', $m[1]));
            if ($valid($val, 2)) $fields['province'] = $val;
        } elseif (empty($fields['province']) && preg_match('/(?:Province|Prov\.)\s*:+\s*([A-Za-zÑñ][A-Za-zÑñ\s\.]+?)(?=\s*(?:\n|OCT|Survey|$))/im', $text, $m)) {
            $val = trim(preg_replace('/\s+/', ' ', $m[1]));
            if ($valid($val, 2)) $fields['province'] = $val;
        }

        // Fallback: parse "San Vicente, Bato, Camarines Sur" from owner address / location line
        $addrForLoc = $fields['address'] ?? $fields['owner_address'] ?? $fields['location_street'] ?? '';
        if ($addrForLoc && preg_match('/^([A-Za-zÑñ][A-Za-zÑñ\s\.\-]+),\s*([A-Za-zÑñ][A-Za-zÑñ\s\.\-]+),\s*([A-Za-zÑñ][A-Za-zÑñ\s\.\-]+)$/u', trim($addrForLoc), $am)) {
            if (empty($fields['barangay'])) $fields['barangay'] = trim($am[1]);
            if (empty($fields['municipality'])) $fields['municipality'] = trim($am[2]);
            if (empty($fields['province'])) $fields['province'] = trim($am[3]);
        }

        // Split "Bato, Camarines Sur" when municipality field contains province
        if (!empty($fields['municipality']) && str_contains($fields['municipality'], ',')) {
            $parts = array_map('trim', explode(',', $fields['municipality'], 2));
            $fields['municipality'] = $parts[0];
            if (empty($fields['province']) && !empty($parts[1])) {
                $fields['province'] = $parts[1];
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // SECTION 4: Title / Survey Details (only if actual values exist)
        // ══════════════════════════════════════════════════════════════════

        // OCT/TCT/CLOA/KOT — must contain a digit
        $titlePatterns = [
            '/(?:OCT|TCT|CLOA|KOT|O\.?C\.?T|T\.?C\.?T)[\/\s]*(?:TCT[\/\s]*)?(?:CLOA|KOT)?\s*(?:No\.?)?\s*:?\s*([\w\d\-]+)/i',
            '/Title\s*(?:No\.?)?\s*:?\s*((?:OCT|TCT|CLOA|KOT)[- ]?[\d\-]+)/i',
        ];
        foreach ($titlePatterns as $p) {
            if (preg_match($p, $text, $m)) {
                $val = trim($m[1]);
                if (preg_match('/\d/', $val) && $valid($val)) {
                    $fields['oct_tct_cloa'] = $val;
                    $fields['oct_tct_kot_no'] = $val;
                    break;
                }
            }
        }

        // Survey No. — must have digit
        if (preg_match('/Survey\s*(?:No\.?|Plan)?\s*:?\s*((?:Psd|Swo|Csd|Bsd|LRC)?[\s\-]?[\d][\w\d\-\(\)]+)/i', $text, $m)) {
            $val = trim($m[1]);
            if (preg_match('/\d/', $val)) $fields['survey_no'] = $val;
        }

        // Cad/PLS Lot No. (FAAS) or Lot No.
        if (preg_match('/Cad\s*\/?\s*PLS\s*Lot\s*(?:No\.?)?\s*:?\s*([\d][\d\w\-]*)/i', $text, $m)) {
            $fields['cad_pls_lot_no'] = trim($m[1]);
            $fields['lot_no'] = trim($m[1]);
        } elseif (preg_match('/Lot\s*(?:No\.?)?\s*:?\s*([\d][\d\w\-]*)/i', $text, $m)) {
            $fields['lot_no'] = trim($m[1]);
        }

        // Block No.
        if (preg_match('/Block\s*(?:No\.?)?\s*:?\s*([\d][\d\w\-]*)/i', $text, $m)) {
            $fields['block_no'] = trim($m[1]);
        }

        // ══════════════════════════════════════════════════════════════════
        // SECTION 5: Boundaries
        // ══════════════════════════════════════════════════════════════════

        // Split text into lines for more accurate boundary parsing
        $lines = preg_split('/\n/', $text);
        $boundaryText = '';
        foreach ($lines as $line) {
            if (preg_match('/(?:Boundar|North|South|East|West|\bNE\b|\bSE\b|\bSW\b|\bNW\b)\s*:?/i', $line)) {
                $boundaryText .= ' ' . $line;
            }
        }
        if (!$boundaryText) $boundaryText = $text;

        $dirs = ['North', 'South', 'East', 'West'];
        foreach ($dirs as $dir) {
            $otherDirs = implode('|', array_diff($dirs, [$dir]));
            $pattern = "/{$dir}\s*:?\s*([A-ZÑ][A-Za-zÑñ\s\.\,]+?)(?=\s+(?:{$otherDirs}|NE|SE|SW|NW)|(?:KIND|LAND|BUILDING|Property|Classi|Bound)|\s*$)/i";
            if (preg_match($pattern, $boundaryText, $m)) {
                $val = rtrim(trim($m[1]), ' ,.');
                if ($valid($val, 2)) $fields['boundary_' . strtolower($dir)] = $val;
            }
        }

        // FAAS compass corners: NE / SE / SW / NW → map to north/east/south/west
        $cornerMap = [
            'ne' => 'north',
            'se' => 'east',
            'sw' => 'south',
            'nw' => 'west',
        ];
        foreach ($cornerMap as $corner => $dirKey) {
            if (!empty($fields['boundary_' . $dirKey])) continue;
            $pattern = "/\b{$corner}\s*:?\s*([A-ZÑ0-9][A-Za-zÑñ0-9\s\.\,\-]+?)(?=\s+(?:NE|SE|SW|NW|North|South|East|West|LAND|Classi|Bound)|\n|$)/i";
            if (preg_match($pattern, $boundaryText, $m)) {
                $val = rtrim(trim($m[1]), ' ,.');
                if ($valid($val, 1)) {
                    $fields['boundary_' . $dirKey] = $val;
                    $fields['boundary_' . $corner] = $val;
                }
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // SECTION 6: Kind of Property Assessed
        // ══════════════════════════════════════════════════════════════════

        $kindOfProperty = [];
        // Multiple detection methods for checked boxes
        $checkPatterns = [
            'Land'      => ['/\[?\s*[Xx☒✓■●]\s*\]?\s*LAND/i', '/LAND\s*[\[(\s]*[Xx☒✓■●]/i', '/[Xx☒]\s+LAND/'],
            'Building'  => ['/\[?\s*[Xx☒✓■●]\s*\]?\s*BUILDING/i', '/BUILDING\s*[\[(\s]*[Xx☒✓■●]/i', '/[Xx☒]\s+BUILDING/'],
            'Machinery' => ['/\[?\s*[Xx☒✓■●]\s*\]?\s*MACHINERY/i', '/MACHINERY\s*[\[(\s]*[Xx☒✓■●]/i'],
            'Others'    => ['/\[?\s*[Xx☒✓■●]\s*\]?\s*OTHER/i', '/OTHER\s*[\[(\s]*[Xx☒✓■●]/i'],
        ];
        foreach ($checkPatterns as $kind => $patterns) {
            foreach ($patterns as $p) {
                if (preg_match($p, $text)) { $kindOfProperty[] = $kind; break; }
            }
        }
        if (!empty($kindOfProperty)) {
            $fields['kind_of_property'] = $kindOfProperty;
        }

        // ══════════════════════════════════════════════════════════════════
        // SECTION 7: Classification & Valuation
        // ══════════════════════════════════════════════════════════════════

        // The left table structure is: CLASSIFICATION | AREA | BASE MARKET VALUE | ACTUAL USE
        // Example OCR: "Agricultural  0.467300  27,477.24  Riceland, Irrigated"
        // FAAS land table: Classification Kind | Sub-Class | Actual Use | Area | Unit Value | Base Market Value
        $classTypes = 'RESIDENTIAL|AGRICULTURAL|COMMERCIAL|INDUSTRIAL|MINERAL|SPECIAL|TIMBER|FOREST|IMPROVEMENTS|PLANT\s+AND\s+TREES';

        // Classification (first column — broad category like Agricultural, Residential, etc.)
        if (preg_match("/\b({$classTypes})\s+(?:\d|[\d,]+\.?\d*|1st|2nd|3rd|4th|[A-Za-z])/i", $text, $m)) {
            $cls = preg_replace('/\s+/', ' ', trim($m[1]));
            $fields['classification'] = ucwords(strtolower($cls));
            if (stripos($cls, 'timber') !== false || stripos($cls, 'forest') !== false) {
                $fields['classification'] = 'Timber/Forest';
            }
        }

        // Sub-class (1st, 2nd, 3rd, 4th) near classification
        if (preg_match("/(?:{$classTypes})\s+(1st|2nd|3rd|4th|I{1,3}V?|[A-D])\b/i", $text, $m)) {
            $fields['sub_class'] = trim($m[1]);
        } elseif (preg_match('/Sub[-\s]*Class\s*:?\s*([A-Za-z0-9]{1,6})/i', $text, $m)) {
            $fields['sub_class'] = trim($m[1]);
        }

        // FAAS land row: Kind  SubClass  ActualUse  Area  UnitValue  BaseMV
        // e.g. "Agricultural  3rd  Riceland, Irrigated  0.467300  58,800.00  27,477.24"
        if (preg_match(
            "/\b({$classTypes})\s+(?:(1st|2nd|3rd|4th|[A-D])\s+)?([A-Za-z][A-Za-z\s,\.]+?)\s+(\d+\.\d{2,})\s+([\d,]+\.?\d{2})\s+([\d,]+\.?\d{2})/i",
            $text,
            $m
        )) {
            if (!isset($fields['classification'])) {
                $fields['classification'] = ucwords(strtolower(trim($m[1])));
            }
            if (!empty($m[2])) $fields['sub_class'] = trim($m[2]);
            $use = rtrim(trim($m[3]), ' ,.');
            if (strlen($use) > 2 && !preg_match('/^(Area|Unit|Base|Market|Value)$/i', $use)) {
                $fields['actual_use'] = $use;
            }
            $fields['area'] = $money($m[4]);
            $fields['unit_value'] = $money($m[5]);
            $fields['base_market_value'] = $money($m[6]);
        }

        // Actual Use (last column — specific use like "Riceland, Irrigated", "Coconut Land", etc.)
        // Specific actual uses that are DISTINCT from classifications
        $actualUseList = 'Riceland|Rice\s*Land|Coconut\s*Land|Corn\s*Land|Sugar\s*Land|Sugarcane|Nipa\s*Land|Cogon\s*Land|Fruit\s*Land|Root\s*Crop|Idle\s*Land|Pasture\s*Land|Fishpond|Mangrove|Orchard|Vegetable|Bamboo';
        $actualUseMods = 'Irrigated|Unirrigated|1st\s*Class|2nd\s*Class|3rd\s*Class|4th\s*Class';

        // Try to find specific actual use (e.g. "Riceland, Irrigated")
        if (!isset($fields['actual_use']) && preg_match("/({$actualUseList})(?:[,\s]+({$actualUseMods}))?/i", $text, $m)) {
            $use = trim($m[1]);
            if (!empty($m[2])) $use .= ', ' . trim($m[2]);
            $fields['actual_use'] = $use;
        }
        // Fallback: after base market value number, capture the remaining text on same line
        if (!isset($fields['actual_use']) && preg_match("/\d+\.\d{4,}\s+[\d,]+\.?\d{2}\s+([A-Z][A-Za-z\s,]+?)(?:\n|$)/im", $text, $m)) {
            $val = rtrim(trim($m[1]), ' ,.');
            if (strlen($val) > 3) $fields['actual_use'] = $val;
        }
        // Last fallback: if only classification-level terms found and no specific use
        if (!isset($fields['actual_use'])) {
            if (preg_match("/(?:Actual\s+Use|ACTUAL\s*USE)\s*:?\s*([A-Za-z][A-Za-z\s,]+?)(?:\n|$)/im", $text, $m)) {
                $fields['actual_use'] = rtrim(trim($m[1]), ' ,.');
            }
        }

        // Area — look for hectare/sq.m. values (4+ decimals like 0.467300)
        if (!isset($fields['area']) && preg_match('/(\d+\.\d{4,})/', $text, $m)) {
            $fields['area'] = trim($m[1]);
        } elseif (!isset($fields['area']) && preg_match('/(?:Area|Ha|Sq\.?\s*M)\s*[:\.]?\s*(\d[\d,]*\.?\d*)/i', $text, $m)) {
            $fields['area'] = $money($m[1]);
        }

        // Unit Value (FAAS land / plants)
        if (!isset($fields['unit_value']) && preg_match('/Unit\s*(?:Value|Price)\s*:?\s*(?:PHP|PhP|₱|P)?\s*([\d,]+\.?\d+)/i', $text, $m)) {
            $fields['unit_value'] = $money($m[1]);
        }

        // Plants/Trees row: Kind  ProdClass  Area  NonFB  FB  Total  Unit  BMV
        if (preg_match(
            '/(?:PLANTS?\s+AND\s*\/?\s*TREES|Kinds?\s+of\s+Plants)[^\n]*\n(?:[^\n]+\n){0,3}\s*([A-Za-z][A-Za-z\s\-]+?)\s+(\d(?:st|nd|rd|th)?|[A-D])\s+(\d+\.?\d*)\s+(\d+)\s+(\d+)\s+(\d+)\s+([\d,]+\.?\d*)\s+([\d,]+\.?\d*)/im',
            $text,
            $m
        )) {
            $fields['plant_kind'] = trim($m[1]);
            $fields['plant_prod_class'] = trim($m[2]);
            $fields['plant_area'] = $money($m[3]);
            $fields['plant_non_fb'] = trim($m[4]);
            $fields['plant_fb'] = trim($m[5]);
            $fields['plant_total'] = trim($m[6]);
            $fields['plant_unit_value'] = $money($m[7]);
            $fields['plant_base_market_value'] = $money($m[8]);
        }

        // Value adjustment factors (FAAS back page)
        // Example:
        //   [ a ] Along ..... road/no road frontage     -9
        //   [ b ] ......... kms. to all weather road      0
        //   [ c ] ......... kms. to market (Pob.)          5
        //   Total Adjustments :                          -4 %
        //   TOTAL PERCENTAGE ADJUSTMENT                  96 %
        $adjBlock = $text;
        if (preg_match('/VALUE\s+ADJUSTMENT\s+FACTORS(.{0,900}?)(?=PROPERTY\s+ASSESSMENT|Previous\s+Owner|Taxability|MEMORANDA|CONFORME|ASSESSED\s+BY|$)/is', $text, $ab)) {
            $adjBlock = $ab[1];
        }

        // [ a ] Along road / no road frontage — % optional (OCR often omits it)
        if (preg_match('/\[\s*a\s*\][^\n]{0,80}?(-?\d+)\s*%?/i', $adjBlock, $m)
            || preg_match('/Along[^\n]{0,60}?(?:road|frontage)[^\n]{0,20}?(-?\d+)\s*%?/i', $adjBlock, $m)
            || preg_match('/Along[^\n]{0,60}?(?:road|frontage)[^\n]{0,20}?(-?\d+)\s*%?/i', $text, $m)
        ) {
            $fields['adj_along_road'] = trim($m[1]);
        }

        // [ b ] kms to all weather road
        if (preg_match('/\[\s*b\s*\][^\n]{0,80}?(-?\d+)\s*%?/i', $adjBlock, $m)
            || preg_match('/(?:all\s+weather\s+road|weather\s+road)[^\n]{0,30}?(-?\d+)\s*%?/i', $adjBlock, $m)
            || preg_match('/(?:all\s+weather\s+road|weather\s+road)[^\n]{0,30}?(-?\d+)\s*%?/i', $text, $m)
        ) {
            $fields['adj_kms_weather_road'] = trim($m[1]);
        }

        // [ c ] kms to market (Pob.)
        if (preg_match('/\[\s*c\s*\][^\n]{0,80}?(-?\d+)\s*%?/i', $adjBlock, $m)
            || preg_match('/(?:kms?\.?\s*to\s+market|to\s+market\s*\(?\s*Pob|market\s*\(?\s*Pob)[^\n]{0,30}?(-?\d+)\s*%?/i', $adjBlock, $m)
            || preg_match('/(?:kms?\.?\s*to\s+market|to\s+market\s*\(?\s*Pob)[^\n]{0,30}?(-?\d+)\s*%?/i', $text, $m)
        ) {
            $fields['adj_kms_to_market'] = trim($m[1]);
        }

        // Total Adjustments (sum of a+b+c)
        if (preg_match('/Total\s+Adjustments\s*:?\s*(-?\d+)\s*%?/i', $adjBlock, $m)
            || preg_match('/Total\s+Adjustments\s*:?\s*(-?\d+)\s*%?/i', $text, $m)
        ) {
            $fields['adj_total_adjustments'] = trim($m[1]);
        }

        // TOTAL PERCENTAGE ADJUSTMENT (usually 100 + total)
        if (preg_match('/TOTAL\s+PERCENTAGE\s+ADJUSTMENT[^\n]{0,40}?(-?\d+)\s*%?/i', $adjBlock, $m)
            || preg_match('/Total\s+Percentage\s+Adjustment\s*=?\s*(-?\d+)\s*%?/i', $text, $m)
        ) {
            $fields['adj_total_percentage'] = trim($m[1]);
        }

        // If a/b/c found but totals missing, compute them
        if (isset($fields['adj_along_road']) || isset($fields['adj_kms_weather_road']) || isset($fields['adj_kms_to_market'])) {
            $sum = (float) ($fields['adj_along_road'] ?? 0)
                + (float) ($fields['adj_kms_weather_road'] ?? 0)
                + (float) ($fields['adj_kms_to_market'] ?? 0);
            if (!isset($fields['adj_total_adjustments'])) {
                $fields['adj_total_adjustments'] = (string) $sum;
            }
            if (!isset($fields['adj_total_percentage'])) {
                $fields['adj_total_percentage'] = (string) (100 + $sum);
            }
        }

        // ─── Assessment Table (right side of form) ───────────────────────
        // Format variations:
        //   "AGRICULTURAL  26,378.15  20  5,275.63"
        //   "AGRICULTURAL  26378.15  20%  5275.63"
        //   "Residential  1,500,000.00  20  300,000.00"
        if (preg_match("/(?:{$classTypes})\s+([\d,]+\.?\d*)\s+(\d{1,3})%?\s+([\d,]+\.?\d*)/i", $text, $m)) {
            $fields['adjusted_market_value'] = $money($m[1]);
            $fields['assessment_level'] = trim($m[2]);
            $fields['assessed_value'] = $money($m[3]);
        }

        // TOTAL / Rounded — "TOTAL: 26,378.15 Rounded: 5,280.00" or "TOTAL 26378.15 5280.00"
        if (preg_match('/TOTAL\s*:?\s*([\d,]+\.?\d+)\s+Rounded\s*:?\s*([\d,]+\.?\d+)/i', $text, $m)) {
            if (!isset($fields['adjusted_market_value'])) {
                $fields['adjusted_market_value'] = $money($m[1]);
            }
            $fields['rounded_assessed_value'] = $money($m[2]);
            if (!isset($fields['assessed_value'])) {
                $fields['assessed_value'] = $money($m[2]);
            }
        } elseif (preg_match('/Rounded\s*:?\s*([\d,]+\.?\d+)/i', $text, $m)) {
            $fields['rounded_assessed_value'] = $money($m[1]);
        } elseif (preg_match('/TOTAL\s*:?\s*([\d,]+\.?\d+)\s+([\d,]+\.?\d+)/i', $text, $m)) {
            if (!isset($fields['adjusted_market_value'])) {
                $fields['adjusted_market_value'] = $money($m[1]);
            }
            if (!isset($fields['assessed_value'])) {
                $fields['assessed_value'] = $money($m[2]);
            }
        }

        // ─── Left side table: Classification | Area | Base Market Value | Actual Use ─
        // Pattern: "Agricultural  0.467300  27,477.24  Riceland, Irrigated"
        if (preg_match("/(?:{$classTypes})\s+\d+\.\d+\s+([\d,]+\.?\d{2})/i", $text, $m)) {
            $fields['base_market_value'] = $money($m[1]);
        }
        // Alternate: area then base market value
        if (!isset($fields['base_market_value']) && preg_match('/\d+\.\d{4,}\s+([\d,]+\.?\d{2})/i', $text, $m)) {
            $bmv = $money($m[1]);
            if ((float) $bmv > 100) $fields['base_market_value'] = $bmv;
        }
        // TOTAL row for left table
        if (!isset($fields['base_market_value']) && preg_match('/TOTAL\s*:?\s*[\d\.]+\s+([\d,]+\.?\d{2})/i', $text, $m)) {
            $fields['base_market_value'] = $money($m[1]);
        }
        // Fallback: labeled
        if (!isset($fields['base_market_value']) && preg_match('/(?:Base\s+)?Market\s+Value\s*:?\s*(?:PHP|PhP|₱|P)?\s*([\d,]+\.?\d+)/i', $text, $m)) {
            $fields['base_market_value'] = $money($m[1]);
        }

        // Market Value (distinct from Base/Adjusted)
        if (preg_match('/(?:Total\s+)?Market\s+Value\s*:?\s*(?:PHP|PhP|₱|P)?\s*([\d,]+\.?\d+)/i', $text, $m)) {
            $fields['market_value'] = $money($m[1]);
        }

        // Assessment Level — fallback
        if (!isset($fields['assessment_level'])) {
            if (preg_match('/(?:Ass(?:m[\'`]?t|essment)\s*Level|Level\s*\(?%?\)?)\s*:?\s*(\d{1,3})/i', $text, $m)) {
                $fields['assessment_level'] = trim($m[1]);
            }
            // or look for standalone percentage between two currency values
            if (!isset($fields['assessment_level']) && preg_match('/([\d,]+\.?\d{2})\s+(\d{1,3})\s+([\d,]+\.?\d{2})/', $text, $m)) {
                $pct = (int) $m[2];
                if ($pct >= 1 && $pct <= 80) $fields['assessment_level'] = (string) $pct;
            }
        }

        // Assessed Value — fallback
        if (!isset($fields['assessed_value'])) {
            if (preg_match('/Assessed\s+Value\s*:?\s*(?:PHP|PhP|₱|P)?\s*([\d,]+\.?\d+)/i', $text, $m)) {
                $fields['assessed_value'] = $money($m[1]);
            }
        }

        // Total Assessed Value in words
        if (preg_match('/(?:Total\s+)?Assessed\s+Value\s*:?\s*([A-Z][A-Z\s]+?(?:PESOS|PESO|CENTAVOS?))/i', $text, $m)) {
            $fields['assessed_value_words'] = trim($m[1]);
        } elseif (preg_match('/\b([A-Z][A-Z\s]+(?:THOUSAND|HUNDRED|MILLION)[A-Z\s]*?(?:PESOS|PESO))\b/', $text, $m)) {
            $fields['assessed_value_words'] = trim($m[1]);
        }

        // ══════════════════════════════════════════════════════════════════
        // SECTION 8: Taxability & Effectivity
        // ══════════════════════════════════════════════════════════════════

        // Taxable/Exempt — TD checkboxes or FAAS Taxability: T / E / Taxable / Exempt
        // Allow OCR typos: Taxabity, Taxablity, Taxabilit, etc.
        $taxLabel = 'Taxab[a-z]{0,10}';
        if (preg_match('/' . $taxLabel . '\s*:?\s*[_\-\.\s]*(?:Taxable\b|([TE])\b)/i', $text, $m)) {
            if (!empty($m[1])) {
                $fields['taxability'] = strtoupper($m[1]) === 'E' ? 'exempt' : 'taxable';
            } else {
                $fields['taxability'] = 'taxable';
            }
        } elseif (preg_match('/' . $taxLabel . '\s*:?\s*[_\-\.\s]*Exempt\b/i', $text)) {
            $fields['taxability'] = 'exempt';
        } elseif (preg_match('/TAXABLE\s*[\[(\s]*[Xx☒✓■●]/i', $text) || preg_match('/[Xx☒✓■●]\s*[\])\s]*TAXABLE/i', $text)) {
            $fields['taxability'] = 'taxable';
        } elseif (preg_match('/EXEMPT\s*[\[(\s]*[Xx☒✓■●]/i', $text) || preg_match('/[Xx☒✓■●]\s*[\])\s]*EXEMPT/i', $text)) {
            $fields['taxability'] = 'exempt';
        }

        // Effectivity (Form 1) / Tax Effectivity (Form 2)
        foreach ($this->parseEffectivity($text) as $ek => $ev) {
            $fields[$ek] = $ev;
        }

        // ══════════════════════════════════════════════════════════════════
        // SECTION 9: Signature
        // ══════════════════════════════════════════════════════════════════

        // Approved By — capture name, stop at date or title markers
        $approvedPatterns = [
            '/APPROVED\s+BY\s*:?\s*([A-Z][A-Za-zÑñ\s\.,]+?)(?=\s+(?:January|February|March|April|May|June|July|August|September|October|November|December|\d{4}|OIC|Provincial|Municipal|City|Assessor))/im',
            '/APPROVED\s+BY\s*:?\s*([A-Z][A-Za-zÑñ\s\.,]{5,})/im',
        ];
        foreach ($approvedPatterns as $p) {
            if (preg_match($p, $text, $m)) {
                $val = rtrim(trim($m[1]), ' ,.');
                if ($valid($val, 4)) { $fields['approved_by'] = $val; break; }
            }
        }

        // Approval date — search broadly for any date near approval context
        $dateRegex = '(?:January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2},?\s*\d{4}';
        $dateRegexShort = '(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\w*\.?\s+\d{1,2},?\s*\d{4}';
        $dateRegexNumeric = '\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}';
        $dateRegexDMY = '\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\w*\.?\s+\d{4}';

        $approvalDateFound = false;

        if (isset($fields['approved_by'])) {
            $escapedName = preg_quote($fields['approved_by'], '/');
            if (preg_match("/{$escapedName}.*?({$dateRegex}|{$dateRegexShort}|{$dateRegexDMY}|{$dateRegexNumeric})/is", $text, $md)) {
                $fields['approval_date'] = trim($md[1]);
                $approvalDateFound = true;
            }
        }

        if (!$approvalDateFound) {
            if (preg_match("/(?:APPROVED|Date)\s*:?.*?({$dateRegex}|{$dateRegexShort}|{$dateRegexDMY}|{$dateRegexNumeric})/is", $text, $md)) {
                $fields['approval_date'] = trim($md[1]);
                $approvalDateFound = true;
            }
        }

        if (!$approvalDateFound) {
            $halfText = substr($text, (int) (strlen($text) / 2));
            if (preg_match("/({$dateRegex}|{$dateRegexShort}|{$dateRegexDMY}|{$dateRegexNumeric})/i", $halfText, $md)) {
                $fields['approval_date'] = trim($md[1]);
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // SECTION 10: Previous Declaration
        // ══════════════════════════════════════════════════════════════════

        $cancelPatterns = [
            '/(?:cancel[s]?|supersede[s]?|revoke[s]?)\s+(?:TD|Tax\s+Declaration)\s*No\.?\s*:?\s*([\d][\d\w\-]+)/i',
            '/(?:TD|Tax\s+Declaration)\s*(?:No\.?)?\s*:?\s*([\d][\d\w\-]+)\s*(?:is\s+)?(?:cancelled|superseded)/i',
        ];
        foreach ($cancelPatterns as $p) {
            if (preg_match($p, $text, $m)) { $fields['previous_td'] = trim($m[1]); break; }
        }

        // Previous Owner — stop before Taxability (incl. OCR typos like Taxabity)
        $taxStop = 'Taxab[a-z]{0,10}|Taxable|Exempt';
        // Rejects footer/notes fragments that OCR sometimes leaks into this field
        $prevOwnerBlocklist = '/(ownership|legal\s+title|does\s+not|cannot|for\s+real\s+property|taxation\s+purposes|schedule\s+of\s+unit|ordinance|sanggunian|by\s+itself\s+alone|confer)/i';

        $tryPrevOwner = function (string $raw) use (&$fields, $valid, $prevOwnerBlocklist) {
            $val = $this->cleanPersonName($raw);
            if (!$valid($val, 3)) return false;
            if (preg_match($prevOwnerBlocklist, $val)) return false;
            $fields['previous_owner'] = $val;
            return true;
        };

        // Sample-form "value row" layout — Google Vision sometimes emits the values
        // for the cancellation block on the line ABOVE the "This declaration cancels TD No." label:
        //   97-028-0806   Owner: AGUILA, Natalia   Previous A.V.   PhP 5,280.00
        // Captures previous_td + previous_owner (+ previous_av below) in one shot.
        if (preg_match(
            '/(?<!\d)(\d[\d\w\-]{4,})\s+Owner\s*:\s*(.+?)\s+Previous\s+A\.?V\.?\s*:?\s*(?:PHP|PhP|₱|Php|P)?\s*([\d.,]+)/i',
            $text,
            $m
        )) {
            if (empty($fields['previous_td'])) {
                $fields['previous_td'] = trim($m[1]);
            }
            $tryPrevOwner($m[2]);
            if (empty($fields['previous_av'])) {
                $fields['previous_av'] = $money($m[3]);
            }
        }

        if (empty($fields['previous_owner'])
            && preg_match('/\bPrevious\s+Owner\s*:?\s*(.+?)(?=\s*(?:' . $taxStop . '|Previous\s+(?:Assessed|A\.?V)|PhP|₱|Tax\s+Effect|\n|$))/im', $text, $m)
        ) {
            $tryPrevOwner($m[1]);
        } elseif (empty($fields['previous_owner'])
            && preg_match('/\bcancel(?:s|led)?\b[\s\S]{0,160}?\bOwner\s*:\s*(.+?)(?=\s*(?:Previous|PhP|₱|' . $taxStop . '|\r?\n|$))/i', $text, $m)
        ) {
            // Fallback for the "cancels TD No. ___ Owner: ___" block (may span a line break).
            // Requires a literal colon after Owner, which prevents matching the substring
            // "Owner" inside words like "Ownership" from the footer notes.
            $tryPrevOwner($m[1]);
        }

        // Previous Assessed Value — allow 5,280.00 / 5.280,00 / 5,280,00 / 5280
        if (preg_match('/Previous\s+(?:Assessed\s+Value|A\.?V\.?)\s*:?\s*(?:PHP|PhP|₱|Php|P)?\s*([\d.,]+)/i', $text, $m)) {
            $fields['previous_av'] = $money($m[1]);
        }

        // Conforme (FAAS)
        if (preg_match('/CONFORME\s*:?\s*(?:By\s*:?\s*)?([A-Z][A-Za-zÑñ\s,\.]{3,}?)(?=\s+(?:CTC|Dated|Issued|ASSESSED|\n))/im', $text, $m)) {
            $val = rtrim(trim($m[1]), ' ,.');
            if ($valid($val, 3)) $fields['conforme_name'] = $val;
        }
        if (preg_match('/CTC\s*No\.?\s*:?\s*([\d][\d\-]*)/i', $text, $m)) {
            $fields['conforme_ctc_no'] = trim($m[1]);
        }
        if (preg_match('/Issued\s+at\s*:?\s*([A-Za-zÑñ][A-Za-zÑñ\s,\.]+?)(?=\s*(?:\n|RECOMMENDING|APPROVED|$))/im', $text, $m)) {
            $val = rtrim(trim($m[1]), ' ,.');
            if ($valid($val, 2)) $fields['conforme_issued_at'] = $val;
        }

        // Assessed By (FAAS)
        if (preg_match('/ASSESSED\s+BY\s*:?\s*(?:Name\s*:?\s*)?([A-Z][A-Za-zÑñ\s,\.]{3,}?)(?=\s+(?:Date|CTC|Title|RECOMMENDING|\n))/im', $text, $m)) {
            $val = rtrim(trim($m[1]), ' ,.');
            if ($valid($val, 3)) $fields['assessed_by'] = $val;
        }

        // ══════════════════════════════════════════════════════════════════
        // SECTION 11: Memoranda
        // ══════════════════════════════════════════════════════════════════

        if (preg_match('/(Revised\s+Pursuant\s+to\s+Sec\.?\s*\d+[A-Za-z]?\s+of\s+R\.?A\.?\s*\d+)/i', $text, $m)) {
            $fields['memoranda'] = preg_replace('/\s+/', ' ', trim($m[1]));
        }

        if (empty($fields['memoranda']) && preg_match(
            '/Memoranda\s*:?\s*[-–]?\s*(.+?)(?=\s*(?:REFERENCES?\s*&?\s*(?:AND\s+)?POSTING|REFERENCES?\s+AND|REFERENCE\s*&?\s*POSTING|POSTING\s+SUMMARY|Notes?\s*:|This\s+declaration|CONFORME|ASSESSED\s+BY|$))/is',
            $text,
            $m
        )) {
            $val = trim($m[1]);
            $val = preg_replace('/\bREFERENCES?\s*&?\s*(?:AND\s+)?(?:POSTING\s+SUMMARY)?\b.*$/is', '', $val);
            $val = preg_replace('/\bPosting\s+Report\b.*$/is', '', $val);
            $val = preg_replace('/\bPrevious\s+Record\b.*$/is', '', $val);
            $val = preg_replace('/\bP\.?I\.?N\.?\b.*$/is', '', $val);
            $val = trim($val);
            if (strlen($val) > 3
                && !preg_match('/^[\s\-–]*$/', $val)
                && !preg_match('/^(REFERENCE|REFERENCES|Posting|Previous\s+Record|Inspection|Posted|Clerk)/i', $val)
            ) {
                $fields['memoranda'] = preg_replace('/\s+/', ' ', $val);
            }
        }

        if (!empty($fields['memoranda'])) {
            $fields['memoranda'] = trim(preg_replace('/\s*REFERENCES?\s*&?\s*(?:AND\s+)?.*$/i', '', $fields['memoranda']));
            if ($fields['memoranda'] === '') unset($fields['memoranda']);
        }

        // ══════════════════════════════════════════════════════════════════
        // POST-PROCESSING
        // ══════════════════════════════════════════════════════════════════

        // Strip trailing label leakage from text fields
        $textKeys = ['owner_name', 'address', 'administrator', 'administrator_address', 'location_street',
            'boundary_north', 'boundary_south', 'boundary_east', 'boundary_west',
            'approved_by', 'assessed_by', 'previous_owner', 'memoranda', 'conforme_name', 'conforme_issued_at',
            'actual_use', 'plant_kind'];
        $trailingLabels = '/\s+(Telephone|Tel\.?|TIN|Address|Administrator|Beneficial|Location|OCT|Survey|No\.?|Number|Street|Barangay|District|Municipality|Province|City|CTC|Dated|Issued|Taxab[a-z]{0,10}|Taxable|Exempt)\s*$/i';
        foreach ($textKeys as $k) {
            if (isset($fields[$k]) && is_string($fields[$k])) {
                $fields[$k] = preg_replace('/[\|\/\\\\_]+/', ' ', $fields[$k]);
                $fields[$k] = preg_replace('/\s+/', ' ', $fields[$k]);
                $fields[$k] = preg_replace($trailingLabels, '', $fields[$k]);
                $fields[$k] = rtrim($fields[$k], " \t|/-_.,");
            }
        }

        // After trailing-label stripping, some values may collapse to a bare label
        // (e.g. "Telephone No." → "Telephone", "Beneficial User" → "Beneficial").
        // Drop those so blank source fields don't get filled with neighbor-label text.
        $labelOnlyKeys = ['address', 'owner_address', 'administrator', 'administrator_name', 'administrator_address'];
        $bareLabelRe = '/^(TIN|Telephone|Tel\.?|Address|Administrator|Beneficial|Beneficial\s*U\w{0,4}|Occupant|Location|Property|Owner|Number|Street|Barangay|District|Municipality|Province|City)\.?$/i';
        foreach ($labelOnlyKeys as $k) {
            if (isset($fields[$k]) && is_string($fields[$k])) {
                $v = trim($fields[$k], " \t\n\r\0\x0B.:,");
                if ($v === '' || preg_match($bareLabelRe, $v) || preg_match('/^Beneficial\s+U[a-z]{1,4}\.?$/i', $v)) {
                    unset($fields[$k]);
                }
            }
        }
        if (isset($fields['previous_owner'])) {
            $fields['previous_owner'] = $this->cleanPersonName($fields['previous_owner']);
            // Safety net: drop obvious footer/notes leakage that OCR sometimes captures.
            if (preg_match('/(ownership|legal\s+title|does\s+not|cannot|for\s+real\s+property|taxation\s+purposes|schedule\s+of\s+unit|ordinance|sanggunian|by\s+itself\s+alone|confer)/i', $fields['previous_owner'])) {
                unset($fields['previous_owner']);
            } elseif ($fields['previous_owner'] === '') {
                unset($fields['previous_owner']);
            }
        }
        if (isset($fields['owner_name'])) {
            $fields['owner_name'] = $this->cleanPersonName($fields['owner_name']);
            if ($fields['owner_name'] === '') unset($fields['owner_name']);
        }
        if (isset($fields['administrator'])) {
            $fields['administrator'] = $this->cleanPersonName($fields['administrator']);
            if ($fields['administrator'] === '') unset($fields['administrator']);
        }
        if (isset($fields['administrator_name'])) {
            $fields['administrator_name'] = $this->cleanPersonName($fields['administrator_name']);
            if ($fields['administrator_name'] === '') unset($fields['administrator_name']);
        }
        if (isset($fields['address'])) {
            $fields['address'] = preg_replace('/\bSen\s+Vicente\b/i', 'San Vicente', $fields['address']);
            $fields['address'] = preg_replace('/Camannes/i', 'Camarines', $fields['address']);
            $fields['owner_address'] = $fields['address'];
        }
        if (isset($fields['owner_address'])) {
            $fields['owner_address'] = preg_replace('/\bSen\s+Vicente\b/i', 'San Vicente', $fields['owner_address']);
            $fields['owner_address'] = preg_replace('/Camannes/i', 'Camarines', $fields['owner_address']);
        }
        if (isset($fields['province'])) {
            $fields['province'] = $this->normalizeProvinceName($fields['province']);
        }

        // Alias normalization for TD + FAAS consumers
        if (isset($fields['address']) && !isset($fields['owner_address'])) {
            $fields['owner_address'] = $fields['address'];
        }
        if (isset($fields['administrator']) && !isset($fields['administrator_name'])) {
            $fields['administrator_name'] = $fields['administrator'];
        }
        if (isset($fields['arp_number']) && !isset($fields['arp_no'])) {
            $fields['arp_no'] = $fields['arp_number'];
        }
        if (isset($fields['property_identification_no']) && !isset($fields['pin'])) {
            $fields['pin'] = $fields['property_identification_no'];
        }
        if (isset($fields['oct_tct_cloa']) && !isset($fields['oct_tct_kot_no'])) {
            $fields['oct_tct_kot_no'] = $fields['oct_tct_cloa'];
        }
        if (isset($fields['lot_no']) && !isset($fields['cad_pls_lot_no'])) {
            $fields['cad_pls_lot_no'] = $fields['lot_no'];
        }

        // Detect document flavor for clients
        if (preg_match('/FIELD\s+APPRAISAL\s+AND\s+ASSESSMENT\s+SHEET|\bFAAS\b/i', $text)) {
            $fields['document_type'] = 'faas';
        } elseif (preg_match('/TAX\s+DECLARATION\s+OF\s+REAL\s+PROPERTY/i', $text)) {
            $fields['document_type'] = 'tax_declaration';
        }

        // Post-filter bad OCR leftovers
        if (isset($fields['update_code']) && (strlen($fields['update_code']) < 2 || preg_match('/^A$/i', $fields['update_code']))) {
            unset($fields['update_code']);
        }
        if (isset($fields['address']) && preg_match('/^(PROPERTY|LOCATION|PROPERTY\s+LOCATION)$/i', $fields['address'])) {
            unset($fields['address']);
        }
        if (isset($fields['administrator_address']) && preg_match('/^(PROPERTY|LOCATION|PROPERTY\s+LOCATION)$/i', $fields['administrator_address'])) {
            unset($fields['administrator_address']);
        }

        // Remove entries with empty/whitespace-only values
        $fields = array_filter($fields, function ($v) {
            if (is_array($v)) return !empty($v);
            return $v !== '' && $v !== null && trim($v) !== '';
        });

        // Sanity check numeric values — remove if clearly wrong
        $numericKeys = [
            'area', 'base_market_value', 'market_value', 'adjusted_market_value', 'assessed_value',
            'previous_av', 'unit_value', 'plant_area', 'plant_unit_value', 'plant_base_market_value',
            'rounded_assessed_value',
        ];
        foreach ($numericKeys as $k) {
            if (isset($fields[$k])) {
                $num = (float) $fields[$k];
                if ($num <= 0) unset($fields[$k]);
            }
        }

        } catch (\Throwable $e) {
            // Swallow any regex or parsing error — return whatever was collected before the failure
            \Illuminate\Support\Facades\Log::warning('OCR extractFields error: ' . $e->getMessage());
        }

        return $fields;
    }

    /**
     * Parse Effectivity (FAAS Form 1/2) and TD "Effectivity of Assessment/Reassessment".
     * Real OCR examples:
     *   FAAS: "Effectvity 2021 fst Qtr"
     *   TD:   "-sity of Assessment/Reassessment : 1st 2021 (huarter Year"
     *
     * @return array{effectivity_year?: string, effectivity_quarter?: string, effectivity?: string, tax_effectivity_year?: string, tax_effectivity_quarter?: string}
     */
    private function parseEffectivity(string $text): array
    {
        $out = [];
        $ordinals = ['1' => '1st', '2' => '2nd', '3' => '3rd', '4' => '4th'];
        $maxYear = (int) date('Y') + 1;

        $acceptYear = static function (string $y) use ($maxYear): ?string {
            $n = (int) preg_replace('/\D/', '', $y);
            return ($n >= 1990 && $n <= $maxYear) ? (string) $n : null;
        };
        $acceptQ = static function (string $raw) use ($ordinals): ?string {
            $raw = strtolower(trim($raw));
            // OCR: fst / ist / lst / 1st → 1
            if (preg_match('/^(f|i|l|1)?\s*st$/', $raw) || $raw === 'f' || $raw === 'i' || $raw === 'l') {
                return '1st';
            }
            if (preg_match('/^2?\s*nd$/', $raw) || $raw === '2') return '2nd';
            if (preg_match('/^3?\s*rd$/', $raw) || $raw === '3') return '3rd';
            if (preg_match('/^4?\s*th$/', $raw) || $raw === '4') return '4th';
            if (preg_match('/([1-4])/', $raw, $m)) {
                return $ordinals[$m[1]] ?? null;
            }
            return null;
        };

        // Soft-normalize for matching (keep newlines)
        $t = str_replace(["\t", '|', '·', '•'], ' ', $text);
        $t = preg_replace('/[ ]{2,}/', ' ', $t) ?? $t;
        // Fix common OCR misreads seen on FAAS back page
        $t = preg_replace('/\bEffectvity\b/i', 'Effectivity', $t) ?? $t;
        $t = preg_replace('/\bEfectivity\b/i', 'Effectivity', $t) ?? $t;
        $t = preg_replace('/\bEffectivlty\b/i', 'Effectivity', $t) ?? $t;
        $t = preg_replace('/\bfst\b/i', '1st', $t) ?? $t;
        $t = preg_replace('/\bist\b/i', '1st', $t) ?? $t;
        $t = preg_replace('/\blst\b/i', '1st', $t) ?? $t;
        // TD: "Quarter" often OCR'd as huarter / Ouarter / etc.
        $t = preg_replace('/\b[hHqO0]?uarter\b/i', 'Quarter', $t) ?? $t;

        // Labels: Effectivity | Tax Effectivity | remaining OCR variants
        $label = '(?:Tax\s+)?Effe?cti?v(?:ity|lty|ty|it[yi]?)?';
        // TD official label — Effectivity is often truncated to "-sity" / "ity"
        $tdLabel = '(?:[A-Za-z\-]{0,12}(?:sity|ivity|fectivity|ffectivity)\s+of\s+)?Assessment\s*[\/\-]?\s*Reassessment';

        // Quarter token: 1st / fst / 1 / f (OCR of 1)
        $qTok = '([fFil1-4]|fst|1st|2nd|3rd|4th|[Il1-4]\s*(?:st|nd|rd|th))';
        $qWord = '(?:Qtr|Quarter)';

        $set = static function (?string $year, ?string $qRaw) use (&$out, $acceptYear, $acceptQ): void {
            if ($year !== null && $year !== '') {
                $y = $acceptYear($year);
                if ($y) {
                    $out['effectivity_year'] = $y;
                    $out['tax_effectivity_year'] = $y;
                }
            }
            if ($qRaw !== null && $qRaw !== '') {
                $q = $acceptQ($qRaw);
                if ($q) {
                    $out['effectivity_quarter'] = $q;
                    $out['tax_effectivity_quarter'] = $q;
                }
            }
        };

        // ── Tax Declaration: "Effectivity of Assessment/Reassessment : 1st 2021"
        // Production OCR #77: "-sity of Assessment/Reassessment : 1st 2021 (huarter Year"
        if (preg_match('/' . $tdLabel . '\s*:?\s*' . $qTok . '\s*(?:' . $qWord . ')?\s*((?:19|20)\d{2})/i', $t, $m)) {
            $set($m[2], $m[1]);
        }
        if ((empty($out['effectivity_year']) || empty($out['effectivity_quarter']))
            && preg_match('/' . $tdLabel . '\s*:?\s*((?:19|20)\d{2})\s+' . $qTok . '\s*(?:' . $qWord . ')?/i', $t, $m)
        ) {
            $set($m[1], $m[2]);
        }
        // Values above underlines may OCR as: "1st 2021 Quarter Year" or split lines
        if ((empty($out['effectivity_year']) || empty($out['effectivity_quarter']))
            && preg_match('/' . $tdLabel . '\s*:?\s*(.{0,160})/is', $t, $m)
        ) {
            $win = preg_split('/\b(?:APPROVED|CANCELS|PREVIOUS|MEMORANDA|NOTES|TAXABLE|EXEMPT)\b/i', $m[1])[0] ?? $m[1];
            if (preg_match('/' . $qTok . '\s*(?:' . $qWord . ')?\s*((?:19|20)\d{2})/i', $win, $mm)) {
                $set($mm[2], $mm[1]);
            } elseif (preg_match('/((?:19|20)\d{2})\s+' . $qTok . '/i', $win, $mm)) {
                $set($mm[1], $mm[2]);
            } else {
                if (empty($out['effectivity_year']) && preg_match('/\b((?:19|20)\d{2})\b/', $win, $mm)) {
                    $set($mm[1], null);
                }
                if (empty($out['effectivity_quarter']) && preg_match('/\b' . $qTok . '\b/i', $win, $mm)) {
                    $set(null, $mm[1]);
                }
            }
        }

        // Direct hit for the exact OCR line we see in production:
        // "Effectvity 2021 fst Qtr" / "Effectivity : 2021 1st Qtr"
        if ((empty($out['effectivity_year']) || empty($out['effectivity_quarter']))
            && preg_match('/' . $label . '\s*:?\s*((?:19|20)\d{2})\s+' . $qTok . '\s*(?:' . $qWord . ')?/i', $t, $m)
        ) {
            $set($m[1], $m[2]);
        }

        // Quarter then year
        if ((empty($out['effectivity_year']) || empty($out['effectivity_quarter']))
            && preg_match('/' . $label . '\s*:?\s*' . $qTok . '\s*(?:' . $qWord . ')?\s*(?:of\s+)?(?:CY\s*)?[,\/\-]?\s*((?:19|20)\d{2})/i', $t, $m)
        ) {
            $set($m[2], $m[1]);
        }

        // Label then next line(s)
        if ((empty($out['effectivity_year']) || empty($out['effectivity_quarter']))
            && preg_match('/' . $label . '\s*:?\s*\n\s*((?:19|20)\d{2})\s*\n?\s*' . $qTok . '\s*(?:' . $qWord . ')?/i', $t, $m)
        ) {
            $set($m[1], $m[2]);
        }

        // Window after label
        if ((empty($out['effectivity_year']) || empty($out['effectivity_quarter']))
            && preg_match('/' . $label . '\s*:?\s*(.{0,140})/is', $t, $m)
        ) {
            $win = preg_split('/\b(?:CONFORME|ASSESSED|RECOMMENDING|APPROVED|MEMORANDA|REFERENCE|APPRAISED|Previous\s+Owner)\b/i', $m[1])[0] ?? $m[1];
            if (preg_match('/((?:19|20)\d{2})\s+' . $qTok . '/i', $win, $mm)) {
                $set($mm[1], $mm[2]);
            } elseif (preg_match('/' . $qTok . '\s*(?:' . $qWord . ')?\s*((?:19|20)\d{2})/i', $win, $mm)) {
                $set($mm[2], $mm[1]);
            } else {
                if (empty($out['effectivity_year']) && preg_match('/\b((?:19|20)\d{2})\b/', $win, $mm)) {
                    $set($mm[1], null);
                }
                if (empty($out['effectivity_quarter']) && preg_match('/\b' . $qTok . '\b/i', $win, $mm)) {
                    $set(null, $mm[1]);
                }
            }
        }

        // Near Taxability / Previous A.V. — matches: "...PhP 5,280,00 Effectvity 2021 fst Qtr"
        if ((empty($out['effectivity_year']) || empty($out['effectivity_quarter']))
            && preg_match(
                '/(?:Taxab[a-z]{0,10}|Taxable|Previous\s+(?:Assessed|A\.?V)|PhP|₱).{0,220}?' . $label . '\s*:?\s*((?:19|20)\d{2})\s+' . $qTok . '/is',
                $t,
                $m
            )
        ) {
            $set($m[1], $m[2]);
        }

        // Standalone after Previous Assessed Value amount
        if ((empty($out['effectivity_year']) || empty($out['effectivity_quarter']))
            && preg_match('/Previous\s+Assessed\s+Value.{0,80}?((?:19|20)\d{2})\s+' . $qTok . '\s*(?:' . $qWord . ')?/is', $t, $m)
        ) {
            $set($m[1], $m[2]);
        }

        // FAAS last resort: "2021 1st Qtr" / "2021 fst Qtr"
        if ((empty($out['effectivity_year']) || empty($out['effectivity_quarter']))
            && preg_match('/FIELD\s+APPRAISAL|\bFAAS\b|VALUE\s+ADJUSTMENT|Previous\s+Owner|Taxab|APPRAISED\s+BY/i', $t)
            && preg_match('/\b((?:19|20)\d{2})\s+' . $qTok . '\s*(?:' . $qWord . ')\b/i', $t, $m)
        ) {
            $set($m[1], $m[2]);
        }

        // TD last resort near APPROVED BY: "1st 2021" / "1st Quarter 2021 Year"
        if ((empty($out['effectivity_year']) || empty($out['effectivity_quarter']))
            && preg_match('/TAX\s+DECLARATION|Assessment\s*[\/\-]?\s*Reassessment|APPROVED\s+BY/i', $t)
            && preg_match('/\b' . $qTok . '\s*(?:' . $qWord . ')?\s*((?:19|20)\d{2})\s*(?:Year)?/i', $t, $m)
        ) {
            $set($m[2], $m[1]);
        }

        if (!empty($out['effectivity_year']) && !empty($out['effectivity_quarter'])) {
            $out['effectivity'] = $out['effectivity_year'] . ' ' . $out['effectivity_quarter'] . ' Qtr';
        }

        return $out;
    }

    /** Strip Taxability / border artifacts / label bleed from person-name OCR text. */
    private function cleanPersonName(string $raw): string
    {
        $val = preg_replace('/\s+/', ' ', trim($raw));
        // Table-border OCR artifacts: | / \ _ etc.
        $val = preg_replace('/[\|\/\\\\_]+/', ' ', $val);
        $val = preg_replace('/\s+/', ' ', $val);
        // Taxabity / Taxablity / Taxability / Taxable / Exempt / Address / TIN bleed
        $val = preg_replace('/\s*(?:Taxab[a-z]{0,10}|Taxable|Exempt|Previous\s+(?:Assessed\s+Value|A\.?V\.?)|Tax\s+Effectivity|Address|T\.?I\.?N\.?|Telephone|Tel\.?\s*No\.?).*$/i', '', $val);
        $val = preg_replace('/\b(?:Taxab[a-z]{0,10}|Taxable|Exempt)\b/i', '', $val);
        $val = preg_replace('/\s+(TIN|Telephone|Tel|Address|Administrator|Beneficial)\b.*$/i', '', $val);
        return rtrim(trim($val), " \t|/-_.,");
    }

    /** Repair common OCR truncations / typos of Philippine province names. */
    private function normalizeProvinceName(string $raw): string
    {
        $val = trim(preg_replace('/\s+/', ' ', $raw));
        $val = preg_replace('/Camannes/i', 'Camarines', $val);
        $val = preg_replace('/Camarlnes/i', 'Camarines', $val);
        $upper = strtoupper($val);
        $fixes = [
            'CAMARI' => 'CAMARINES SUR',
            'CAMARINE' => 'CAMARINES SUR',
            'CAMANNES SUR' => 'CAMARINES SUR',
            'CAMARINES S' => 'CAMARINES SUR',
            'CAMARINES SU' => 'CAMARINES SUR',
            'CAMARINES N' => 'CAMARINES NORTE',
            'CAMARINES NO' => 'CAMARINES NORTE',
            'CAMARINES NOR' => 'CAMARINES NORTE',
            'CAMARINES NORT' => 'CAMARINES NORTE',
        ];
        if (isset($fixes[$upper])) {
            return $fixes[$upper];
        }
        return $val;
    }
}


