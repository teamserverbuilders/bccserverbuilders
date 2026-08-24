<?php
    /** @var \App\Models\FieldAppraisal $fa */
    /** @var string|null $sealPath */
    /** @var string|null $sketchPath */

    $fmtNum = function ($val, $digits = 2) {
        if ($val === null || $val === '') return '';
        if (!is_numeric($val)) return (string) $val;
        return number_format((float) $val, $digits);
    };
    $fmtDate = function ($val, $fmt = 'F d, Y') {
        if (!$val) return '';
        try { return \Illuminate\Support\Carbon::parse($val)->format($fmt); }
        catch (\Throwable $e) { return (string) $val; }
    };

    $landRows = $fa->landRows?->values() ?? collect();
    $plantRows = $fa->plantRows?->values() ?? collect();
    $assessmentRows = $fa->assessmentRows?->values() ?? collect();

    // Keep a few blank form rows, but don't flood the page with thin empty lines.
    $landPad   = max(3, min($landRows->count() + 1, 6));
    $plantPad  = max(3, min($plantRows->count() + 1, 5));
    $assessPad = max(4, min($assessmentRows->count() + 1, 7));

    $taxRaw = strtolower(trim((string) ($fa->taxability ?? '')));
    $isTaxable = in_array($taxRaw, ['taxable', 't'], true);
    $isExempt  = in_array($taxRaw, ['exempt', 'e'], true);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>FAAS - {{ $fa->appraisal_no }}</title>
    <style>
        @page { margin: 14px 16px; size: letter; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8.5px; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        .page-break { page-break-before: always; }

        .hdr { width: 100%; margin-bottom: 4px; }
        .hdr td { border: 0; vertical-align: middle; padding: 0; }
        .hdr .seal { width: 58px; }
        .hdr .seal img { width: 52px; height: 52px; }
        .hdr .title-cell { text-align: center; }
        .hdr .title { font-size: 13px; font-weight: bold; letter-spacing: 0.3px; }
        .hdr .subtitle { font-size: 9.5px; margin-top: 1px; }
        .hdr .update { width: 120px; text-align: left; font-size: 8px; vertical-align: bottom; padding-bottom: 4px; }
        .hdr .update .line { border-bottom: 1px solid #000; min-height: 12px; padding: 1px 3px; font-weight: bold; }

        .box { border: 1px solid #000; margin-top: 4px; }
        .box table td { border: 1px solid #000; padding: 2px 4px; vertical-align: top; }
        .box .lbl { font-weight: bold; white-space: nowrap; }
        .sec-title { text-align: center; font-weight: bold; font-size: 9px; padding: 3px 4px; border-bottom: 1px solid #000; letter-spacing: 0.4px; }

        .grid2 td { width: 50%; }
        .grid3 td { width: 33.33%; }
        .val { min-height: 11px; }

        .loc-box { border: 1px solid #000; margin-top: 4px; }
        .loc-box .sec-title { border-bottom: 1px solid #000; }
        .loc-box table td { border: 1px solid #000; padding: 2px 4px; }

        .sketch-wrap { border: 1px solid #000; margin-top: 4px; }
        .sketch-wrap td { border: 1px solid #000; padding: 2px 4px; vertical-align: top; }
        .bound-cell { width: 42%; }
        .bound-row td { border: 1px solid #000; padding: 8px 6px; height: 36px; vertical-align: middle; }
        .sketch-cell { width: 58%; text-align: center; height: 160px; vertical-align: top; }
        .sketch-cell .label { text-align: left; font-weight: bold; margin-bottom: 4px; }
        .sketch-cell img { max-width: 100%; max-height: 140px; }
        .sketch-cell .sketch-placeholder { height: 130px; }

        .tbl { border: 1px solid #000; margin-top: 6px; border-collapse: collapse; width: 100%; }
        .tbl th, .tbl td { border: 1px solid #000; padding: 5px 4px; font-size: 8px; }
        .tbl th { background: #f3f3f3; font-weight: bold; text-align: center; vertical-align: middle; }
        .tbl tbody td { height: 22px; vertical-align: middle; }
        .tbl .num { text-align: right; }
        .tbl .tot { font-weight: bold; text-align: center; }
        .tbl-title { text-align: center; font-weight: bold; font-size: 9px; padding: 4px; border: 1px solid #000; border-bottom: 0; letter-spacing: 0.4px; margin-top: 8px; }

        .two-col { width: 100%; margin-top: 4px; border-collapse: collapse; }
        .two-col > tbody > tr > td { border: 0; padding: 0; vertical-align: top; }
        .two-col .left { width: 48%; padding-right: 3px; }
        .two-col .right { width: 52%; padding-left: 3px; }

        .adj-box, .assess-box { border: 1px solid #000; }
        .adj-box .sec-title, .assess-box .sec-title { border-bottom: 1px solid #000; }
        .adj-inner td { border: 0; padding: 2px 5px; font-size: 8px; }
        .adj-inner .pct { text-align: right; width: 40px; border-bottom: 1px solid #000; }
        .uline { border-bottom: 1px solid #000; display: inline-block; min-width: 40px; text-align: center; padding: 0 2px; }

        .info-row { border: 1px solid #000; margin-top: 4px; }
        .info-row td { border: 1px solid #000; padding: 3px 5px; vertical-align: middle; }

        .sig-grid { width: 100%; margin-top: 4px; border-collapse: collapse; }
        .sig-grid > tbody > tr > td { border: 1px solid #000; width: 50%; padding: 5px 6px; vertical-align: top; height: 78px; }
        .sig-head { font-weight: bold; font-size: 8.5px; margin-bottom: 4px; }
        .sig-line { border-bottom: 1px solid #000; min-height: 14px; text-align: center; font-weight: bold; margin-top: 18px; padding: 0 4px; }
        .sig-cap { text-align: center; font-size: 7.5px; color: #333; }
        .sig-field { margin-top: 3px; }

        .memo { border: 1px solid #000; margin-top: 4px; min-height: 70px; padding: 4px 6px; }
        .memo .lbl { font-weight: bold; }

        .ref-title { text-align: center; font-weight: bold; font-size: 9px; margin-top: 6px; margin-bottom: 2px; letter-spacing: 0.3px; }
        .ref-tbl th, .ref-tbl td { border: 1px solid #000; padding: 3px 4px; font-size: 8px; text-align: center; }
        .ref-tbl th { background: #f3f3f3; font-weight: bold; }
        .ref-tbl .left { text-align: left; font-weight: bold; }
    </style>
</head>
<body>

{{-- ===================== PAGE 1 — FRONT ===================== --}}
<table class="hdr">
    <tr>
        <td class="seal">
            @if(!empty($sealPath))
                <img src="{{ $sealPath }}">
            @endif
        </td>
        <td class="title-cell">
            <div class="title">FIELD APPRAISAL AND ASSESSMENT SHEET (FAAS)</div>
            <div class="subtitle">(LAND/PLANTS and TREES)</div>
        </td>
        <td class="update">
            <div class="lbl">UPDATE CODE:</div>
            <div class="line">{{ $fa->update_code ?: '' }}</div>
        </td>
    </tr>
</table>

{{-- Identity box --}}
<div class="box">
    <table>
        <tr>
            <td style="width:50%"><span class="lbl">A.R.P. No.:</span> <span class="val">{{ $fa->arp_no ?: '' }}</span></td>
            <td style="width:50%"><span class="lbl">P.I.N.:</span> <span class="val">{{ $fa->pin ?: '' }}</span></td>
        </tr>
    </table>
    <table>
        <tr>
            <td style="width:34%"><span class="lbl">OCT/TCT/KOT No:</span> <span class="val">{{ $fa->oct_tct_kot_no ?: '' }}</span></td>
            <td style="width:33%"><span class="lbl">Survey No.:</span> <span class="val">{{ $fa->survey_no ?: '' }}</span></td>
            <td style="width:33%"><span class="lbl">Cad/PLS Lot No.:</span> <span class="val">{{ $fa->cad_pls_lot_no ?: '' }}</span></td>
        </tr>
    </table>
    <table>
        <tr>
            <td style="width:50%"><span class="lbl">OWNER:</span> <span class="val">{{ $fa->owner_name ?: '' }}</span></td>
            <td style="width:50%"><span class="lbl">Address:</span> <span class="val">{{ $fa->owner_address ?: '' }}</span></td>
        </tr>
        <tr>
            <td style="width:50%"><span class="lbl">Administrator/Occupant:</span> <span class="val">{{ $fa->administrator_name ?: '' }}</span></td>
            <td style="width:50%"><span class="lbl">Address:</span> <span class="val">{{ $fa->administrator_address ?: '' }}</span></td>
        </tr>
    </table>
</div>

{{-- Property Location --}}
<div class="loc-box">
    <div class="sec-title">PROPERTY LOCATION</div>
    <table>
        <tr>
            <td style="width:50%"><span class="lbl">No./Street:</span> {{ $fa->property_street ?: '' }}</td>
            <td style="width:50%"><span class="lbl">Barangay:</span> {{ $fa->property_barangay ?: '' }}</td>
        </tr>
        <tr>
            <td style="width:50%"><span class="lbl">Municipality:</span> <b>{{ $fa->property_municipality ?: '' }}</b></td>
            <td style="width:50%"><span class="lbl">Province of:</span> <b>{{ $fa->property_province ?: '' }}</b></td>
        </tr>
    </table>
</div>

{{-- Boundaries + Land Sketch --}}
<table class="sketch-wrap">
    <tr>
        <td class="bound-cell">
            <table style="width:100%; border-collapse:collapse;">
                <tr class="bound-row"><td><span class="lbl">NE:</span> {{ $fa->boundary_north ?: '' }}</td></tr>
                <tr class="bound-row"><td><span class="lbl">SE:</span> {{ $fa->boundary_east ?: '' }}</td></tr>
                <tr class="bound-row"><td><span class="lbl">SW:</span> {{ $fa->boundary_south ?: '' }}</td></tr>
                <tr class="bound-row"><td><span class="lbl">NW:</span> {{ $fa->boundary_west ?: '' }}</td></tr>
            </table>
        </td>
        <td class="sketch-cell">
            <div class="label">Land Sketch:</div>
            @if(!empty($sketchPath))
                <img src="{{ $sketchPath }}">
            @else
                <div class="sketch-placeholder"></div>
            @endif
        </td>
    </tr>
</table>

{{-- Land Appraisal --}}
<div class="tbl-title">LAND APPRAISAL</div>
<table class="tbl">
    <thead>
        <tr>
            <th style="width:18%">Classification<br>Kind</th>
            <th style="width:10%">Sub-<br>Class</th>
            <th style="width:16%">Actual Use</th>
            <th style="width:14%">Area<br>(Ha. or Sq. M.)</th>
            <th style="width:16%">Unit Value</th>
            <th style="width:18%">Base Market Value</th>
        </tr>
    </thead>
    <tbody>
        @for ($i = 0; $i < $landPad; $i++)
            @php $r = $landRows->get($i); @endphp
            <tr>
                <td>{{ $r->classification_kind ?? '' }}</td>
                <td style="text-align:center;">{{ $r->sub_class ?? '' }}</td>
                <td>{{ $r->actual_use ?? '' }}</td>
                <td class="num">{{ isset($r) ? $fmtNum($r->area, 6) : '' }}</td>
                <td class="num">{{ isset($r) ? $fmtNum($r->unit_value, 2) : '' }}</td>
                <td class="num">{{ isset($r) ? $fmtNum($r->base_market_value, 2) : '' }}</td>
            </tr>
        @endfor
        <tr>
            <td colspan="3" class="tot">TOTAL</td>
            <td class="num tot">{{ $fmtNum($fa->land_total_area, 6) }}</td>
            <td></td>
            <td class="num tot">{{ $fmtNum($fa->land_total_base_market_value, 2) }}</td>
        </tr>
    </tbody>
</table>

{{-- Plants and Trees --}}
<div class="tbl-title">PLANTS AND TREES APPRAISAL</div>
<table class="tbl">
    <thead>
        <tr>
            <th rowspan="2" style="width:16%">Kinds of<br>Plants and/or<br>Trees</th>
            <th rowspan="2" style="width:10%">Product<br>Class</th>
            <th rowspan="2" style="width:12%">Area Planted<br>Ha. or Sq. M.<br>(Optional)</th>
            <th colspan="3">Total Numbers</th>
            <th rowspan="2" style="width:12%">Unit<br>Price</th>
            <th rowspan="2" style="width:14%">Base Market Value</th>
        </tr>
        <tr>
            <th style="width:10%">N-Fruit<br>Bearing</th>
            <th style="width:10%">Fruit<br>Bearing</th>
            <th style="width:8%">Total</th>
        </tr>
    </thead>
    <tbody>
        @for ($i = 0; $i < $plantPad; $i++)
            @php $r = $plantRows->get($i); @endphp
            <tr>
                <td>{{ $r->kind ?? '' }}</td>
                <td style="text-align:center;">{{ $r->prod_class ?? '' }}</td>
                <td class="num">{{ isset($r) ? $fmtNum($r->area_planted, 6) : '' }}</td>
                <td class="num">{{ isset($r) && $r->non_fb !== null ? $fmtNum($r->non_fb, 0) : '' }}</td>
                <td class="num">{{ isset($r) && $r->fb !== null ? $fmtNum($r->fb, 0) : '' }}</td>
                <td class="num">{{ isset($r) && $r->total !== null ? $fmtNum($r->total, 0) : '' }}</td>
                <td class="num">{{ isset($r) ? $fmtNum($r->unit_value, 2) : '' }}</td>
                <td class="num">{{ isset($r) ? $fmtNum($r->base_market_value, 2) : '' }}</td>
            </tr>
        @endfor
        <tr>
            <td colspan="2" class="tot">TOTAL</td>
            <td class="num tot">{{ $fmtNum($fa->plant_total_area, 6) }}</td>
            <td class="num tot">{{ $fa->plant_total_non_fb !== null ? $fmtNum($fa->plant_total_non_fb, 0) : '' }}</td>
            <td class="num tot">{{ $fa->plant_total_fb !== null ? $fmtNum($fa->plant_total_fb, 0) : '' }}</td>
            <td class="num tot">{{ $fa->plant_total_count !== null ? $fmtNum($fa->plant_total_count, 0) : '' }}</td>
            <td></td>
            <td class="num tot">{{ $fmtNum($fa->plant_total_base_market_value, 2) }}</td>
        </tr>
    </tbody>
</table>

{{-- ===================== PAGE 2 — BACK ===================== --}}
<div class="page-break"></div>

<table class="two-col">
    <tr>
        <td class="left">
            <div class="adj-box">
                <div class="sec-title">VALUE ADJUSTMENT FACTORS for AGRICULTURAL LANDS</div>
                <table class="adj-inner" style="width:100%;">
                    <tr>
                        <td>Base Market Value...............................</td>
                        <td class="pct">100%</td>
                    </tr>
                    <tr><td colspan="2" style="padding-top:4px;"><b>Adjustments:</b></td></tr>
                    <tr>
                        <td>(a) Along <span class="uline">&nbsp;&nbsp;&nbsp;&nbsp;</span> rd. or no road outlet</td>
                        <td class="pct">{{ $fmtNum($fa->adj_along_road ?? 0, 0) }}%</td>
                    </tr>
                    <tr>
                        <td>(b) <span class="uline">&nbsp;&nbsp;&nbsp;&nbsp;</span> Km. to all weather road</td>
                        <td class="pct">{{ $fmtNum($fa->adj_kms_weather_road ?? 0, 0) }}%</td>
                    </tr>
                    <tr>
                        <td>(c) <span class="uline">&nbsp;&nbsp;&nbsp;&nbsp;</span> Km. to market (poblacion)</td>
                        <td class="pct">{{ $fmtNum($fa->adj_kms_to_market ?? 0, 0) }}%</td>
                    </tr>
                    <tr>
                        <td style="padding-top:6px;"><b>Total Adjustment =</b></td>
                        <td class="pct"><b>{{ $fmtNum($fa->adj_total_adjustments ?? 0, 0) }}%</b></td>
                    </tr>
                    <tr>
                        <td style="padding-top:4px;"><b>TOTAL PERCENTAGE ADJUSTMENT =</b></td>
                        <td class="pct"><b>{{ $fmtNum($fa->adj_total_percentage ?? 100, 0) }}%</b></td>
                    </tr>
                </table>
            </div>
        </td>
        <td class="right">
            <div class="assess-box">
                <div class="sec-title">PROPERTY ASSESSMENT</div>
                <table class="tbl" style="margin-top:0; border:0;">
                    <thead>
                        <tr>
                            <th style="width:28%">Classification<br>(Kind)</th>
                            <th style="width:28%">Adjusted Market<br>Value</th>
                            <th style="width:16%">Assm't<br>Level</th>
                            <th style="width:28%">Assessed Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < $assessPad; $i++)
                            @php $r = $assessmentRows->get($i); @endphp
                            <tr>
                                <td>{{ $r->classification ?? '' }}</td>
                                <td class="num">{{ isset($r) && $r->adjusted_market_value !== null ? 'P  '.$fmtNum($r->adjusted_market_value, 2) : '' }}</td>
                                <td class="num">{{ isset($r) && $r->assessment_level !== null ? $fmtNum($r->assessment_level, 0).'%' : '' }}</td>
                                <td class="num">{{ isset($r) && $r->assessed_value !== null ? 'P  '.$fmtNum($r->assessed_value, 2) : '' }}</td>
                            </tr>
                        @endfor
                        <tr>
                            <td class="tot">TOTAL</td>
                            <td class="num tot">P  {{ $fmtNum($fa->total_adjusted_market_value ?? $fa->computed_market_value, 2) }}</td>
                            <td></td>
                            <td class="num tot">P  {{ $fmtNum($fa->rounded_assessed_value ?? $fa->computed_assessed_value, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </td>
    </tr>
</table>

<table class="info-row" style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="width:55%"><span class="lbl">Previous Owner:</span> {{ $fa->previous_owner ?: '' }}</td>
        <td style="width:45%">
            <span class="lbl">Taxability:</span>
            &nbsp;&nbsp;{{ $isTaxable ? '✔' : '____' }} T
            &nbsp;&nbsp;&nbsp;{{ $isExempt ? '✔' : '____' }} E
        </td>
    </tr>
    <tr>
        <td><span class="lbl">Previous Assessed Value:</span> P {{ $fmtNum($fa->previous_assessed_value, 2) }}</td>
        <td>
            <span class="lbl">Tax Effectivity:</span>
            {{ $fa->effectivity_quarter ?: '' }}{{ $fa->effectivity_quarter && $fa->effectivity_year ? ' / ' : '' }}{{ $fa->effectivity_year ?: '' }}
        </td>
    </tr>
</table>

<table class="sig-grid">
    <tr>
        <td>
            <div class="sig-head">CONFORME:</div>
            <div class="sig-field"><span class="lbl">By:</span> {{ $fa->conforme_name ?: '' }}</div>
            <div class="sig-field"><span class="lbl">CTC No.:</span> {{ $fa->conforme_ctc_no ?: '' }}</div>
            <div class="sig-field"><span class="lbl">Dated:</span> {{ $fmtDate($fa->conforme_dated) }}</div>
            <div class="sig-field"><span class="lbl">Issued at:</span> {{ $fa->conforme_issued_at ?: '' }}</div>
        </td>
        <td>
            <div class="sig-head">ASSESSED BY:</div>
            <div class="sig-line">{{ $fa->assessed_by_name ?: ($fa->appraised_by_name ?: '') }}</div>
            <div class="sig-cap">Name</div>
            <div class="sig-line" style="margin-top:10px;">{{ $fmtDate($fa->assessed_by_date ?: $fa->appraised_by_date) }}</div>
            <div class="sig-cap">Date</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="sig-head">RECOMMENDING APPROVAL:</div>
            <div class="sig-line">{{ $fa->recommending_name ?: '' }}</div>
            <div class="sig-cap">{{ $fa->recommending_title ?: 'Municipal Assessor' }}</div>
            <div class="sig-line" style="margin-top:10px;">{{ $fmtDate($fa->recommending_date) }}</div>
            <div class="sig-cap">Date</div>
        </td>
        <td>
            <div class="sig-head">APPROVED BY:</div>
            <div class="sig-line">{{ $fa->approved_by_name ?: '' }}</div>
            <div class="sig-cap">{{ $fa->approved_by_title ?: 'Acting-Provincial Assessor' }}</div>
            <div class="sig-line" style="margin-top:10px;">{{ $fmtDate($fa->approved_by_date) }}</div>
            <div class="sig-cap">Date</div>
        </td>
    </tr>
</table>

<div class="memo">
    <div class="lbl">MEMORANDA:</div>
    <div style="margin-top:4px; line-height:1.35;">{!! nl2br(e($fa->memoranda ?: '')) !!}</div>
</div>

<div class="ref-title">REFERENCE &amp; POSTING SUMMARY</div>
<table class="ref-tbl" style="width:100%; border-collapse:collapse;">
    <thead>
        <tr>
            <th style="width:16%">Reference</th>
            <th style="width:22%">Previous Record</th>
            <th colspan="2">Posting Report</th>
            <th style="width:18%" rowspan="2">Post<br>Inspection</th>
        </tr>
        <tr>
            <th></th>
            <th></th>
            <th style="width:22%">Date<br>Posted</th>
            <th style="width:22%">Posting<br>Clerk Initial</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="left">P.I.N.</td>
            <td>{{ $fa->ref_pin ?: '' }}</td>
            <td>{{ $fmtDate($fa->posting_pin_date, 'm/d/Y') }}</td>
            <td>{{ $fa->posting_pin_clerk ?: '' }}</td>
            <td>{{ $fa->posting_pin_inspection ?: '' }}</td>
        </tr>
        <tr>
            <td class="left">A.R.P. No.</td>
            <td>{{ $fa->ref_arp_no ?: '' }}</td>
            <td>{{ $fmtDate($fa->posting_arp_date, 'm/d/Y') }}</td>
            <td>{{ $fa->posting_arp_clerk ?: '' }}</td>
            <td>{{ $fa->posting_arp_inspection ?: '' }}</td>
        </tr>
        <tr>
            <td class="left">A.R. Page No.</td>
            <td>{{ $fa->ref_ar_page_no ?: '' }}</td>
            <td>{{ $fmtDate($fa->posting_ar_page_date, 'm/d/Y') }}</td>
            <td>{{ $fa->posting_ar_page_clerk ?: '' }}</td>
            <td>{{ $fa->posting_ar_page_inspection ?: '' }}</td>
        </tr>
    </tbody>
</table>

</body>
</html>
