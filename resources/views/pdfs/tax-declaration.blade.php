<?php
    /** @var \App\Models\TaxDeclaration $td */
    /** @var string|null $sealPath */
    $kinds = is_array($td->kind_of_property) ? array_map('strtolower', $td->kind_of_property) : [];
    $hasKind = fn($n) => in_array(strtolower($n), $kinds, true);
    $check = fn($n) => $hasKind($n) ? '[ X ]' : '[  ]';

    $fmtNum = function ($val, $digits = 2) {
        if ($val === null || $val === '' ) return '';
        if (!is_numeric($val)) return $val;
        return number_format((float) $val, $digits);
    };
    $fmtDate = function ($val, $fmt = 'F d, Y') {
        if (!$val) return '';
        try { return \Illuminate\Support\Carbon::parse($val)->format($fmt); }
        catch (\Throwable $e) { return (string) $val; }
    };

    $valuationRows  = is_array($td->valuation_rows)  ? $td->valuation_rows  : [];
    $assessmentRows = is_array($td->assessment_rows) ? $td->assessment_rows : [];

    if (!count($valuationRows) && !count($assessmentRows)) {
        $valuationRows  = [[
            'classification_name' => $td->classification?->name,
            'area'                => $td->land_area,
            'base_market_value'   => $td->base_market_value ?? $td->market_value,
            'actual_use'          => $td->actual_use ?? $td->current_use,
        ]];
        $assessmentRows = [[
            'classification'       => $td->classification?->name,
            'adjusted_market_value'=> $td->adjusted_market_value ?? $td->market_value,
            'assessment_level'     => $td->assessment_level,
            'assessed_value'       => $td->assessed_value,
        ]];
    }

    $rowCount = max(count($valuationRows), count($assessmentRows), 7);

    $totalArea    = array_sum(array_map(fn($r) => (float) ($r['area'] ?? 0), $valuationRows));
    $totalBaseMv  = array_sum(array_map(fn($r) => (float) ($r['base_market_value'] ?? 0), $valuationRows));
    $totalAdjMv   = array_sum(array_map(fn($r) => (float) ($r['adjusted_market_value'] ?? 0), $assessmentRows));

    $municipalityLine = trim(collect([$td->municipality?->name, $td->municipality?->province])->filter()->implode(', '));

    $rightClasses = ['RESIDENTIAL','AGRICULTURAL','COMMERCIAL','INDUSTRIAL','MINERAL','SPECIAL','TIMBER/FOREST','IMPROVEMENTS'];
    $assessmentByClass = [];
    foreach ($assessmentRows as $r) {
        $key = strtoupper(trim((string) ($r['classification'] ?? '')));
        if ($key !== '') $assessmentByClass[$key] = $r;
    }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Declaration - {{ $td->td_number }}</title>
    <style>
        @page { margin: 18px 22px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9.5px; color: #111; }
        h1.title { text-align: center; font-size: 15px; font-weight: bold; margin: 0 0 8px 0; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; }
        .no-border td, .no-border th { border: 0; padding: 1px 2px; }
        .form td { border: 0; padding: 2px 3px; vertical-align: middle; }
        .lbl { font-weight: bold; white-space: nowrap; padding-right: 4px; }
        .val { border-bottom: 1px solid #444; padding: 1px 3px; min-height: 12px; }
        .val-inline { border-bottom: 1px solid #444; padding: 1px 6px; }
        .box { border: 1px solid #444; padding: 4px 6px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .small { font-size: 8.5px; color: #444; }
        .caption { font-size: 8px; color: #555; text-align: center; font-style: italic; }
        .kind td { border: 0; padding: 3px 4px; }
        .assess { border-collapse: collapse; width: 100%; }
        .assess th, .assess td { border: 1px solid #000; padding: 2px 3px; font-size: 8.5px; }
        .assess th { background: #f2f2f2; font-weight: bold; text-align: center; }
        .assess td.num { text-align: right; }
        .assess td.tot { font-weight: bold; }
        .thick-right { border-right: 2px solid #000 !important; }
        .footer-notes { font-size: 7.5px; color: #333; margin-top: 6px; line-height: 1.35; }
        .sig-line { border-bottom: 1px solid #000; min-height: 12px; padding: 0 4px; text-align: center; font-weight: bold; }
        .field-label { font-weight: bold; padding-right: 6px; white-space: nowrap; }
        .chk { font-family: DejaVu Sans Mono, monospace; letter-spacing: 1px; margin-right: 4px; }
        .gov-id-outer { width: 100%; border-collapse: collapse; }
        .gov-id-outer > tbody > tr > td { border: 0; padding: 0; text-align: center; }
        .gov-id { border-collapse: collapse; width: 320px; margin: 0 auto; table-layout: fixed; }
        .gov-id td { border: 0; padding: 0; }
        .gov-id .seal-side { width: 70px; vertical-align: middle; text-align: center; padding-right: 6px; }
        .gov-id .seal-side img { width: 58px; height: 58px; object-fit: contain; display: block; }
        .gov-id .text-side { width: 180px; vertical-align: middle; text-align: center; line-height: 1.35; white-space: nowrap; }
        .gov-id .text-side .republic { font-size: 10.5px; }
        .gov-id .text-side .province-line { font-size: 10px; }
        .gov-id .text-side .municipality-line { font-size: 10px; }
        .gov-id .balance-side { width: 70px; }
        .office-name { text-align: center; font-size: 11px; font-weight: bold; margin-top: 6px; letter-spacing: 0.3px; }
        .gov-divider { border-top: 1.5px solid #000; margin: 6px 0 6px 0; }
    </style>
</head>
<body>

{{-- Compact centered group: equal seal/balance cells keep the text page-centered; seal stays close --}}
<table class="gov-id-outer">
    <tr>
        <td>
            <table class="gov-id" align="center" width="320">
                <tr>
                    <td class="seal-side">
                        @if(!empty($sealPath))
                            <img src="{{ $sealPath }}">
                        @endif
                    </td>
                    <td class="text-side">
                        <div class="republic">Republic of the Philippines</div>
                        <div class="province-line">Province of Camarines Sur</div>
                        <div class="municipality-line">Municipality of Baao</div>
                    </td>
                    <td class="balance-side"></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<div class="office-name">OFFICE OF THE MUNICIPAL ASSESSOR</div>
<div class="gov-divider"></div>

<h1 class="title">TAX DECLARATION OF REAL PROPERTY</h1>

{{-- TD No. / PIN --}}
<table class="form">
    <tr>
        <td style="width:12%"><span class="lbl">TD No.</span></td>
        <td style="width:38%"><div class="val-inline">{{ $td->td_number ?: '' }}</div></td>
        <td style="width:22%; text-align:right"><span class="lbl">Property Identification No.</span></td>
        <td style="width:28%"><div class="val-inline">{{ $td->property_index_number ?: '' }}</div></td>
    </tr>
</table>

{{-- Owner --}}
<table class="form">
    <tr>
        <td style="width:9%"><span class="lbl">Owner</span></td>
        <td style="width:58%"><div class="val-inline">{{ $td->owner?->owner_name ?: '' }}</div></td>
        <td style="width:7%"><span class="lbl">TIN</span></td>
        <td style="width:26%"><div class="val-inline">{{ $td->owner_tin ?: ($td->owner?->tin ?: '') }}</div></td>
    </tr>
    <tr>
        <td><span class="lbl">Address</span></td>
        <td><div class="val-inline">{{ $td->owner_address ?: ($td->owner?->address ?: '') }}</div></td>
        <td><span class="lbl">Telephone No.</span></td>
        <td><div class="val-inline">{{ $td->owner_telephone ?: ($td->owner?->contact_number ?: '') }}</div></td>
    </tr>
</table>

{{-- Administrator / Beneficial User --}}
<table class="form">
    <tr>
        <td style="width:24%"><span class="lbl">Administrator/Beneficial User:</span></td>
        <td style="width:43%"><div class="val-inline">{{ $td->administrator_name ?: '' }}</div></td>
        <td style="width:7%"><span class="lbl">TIN</span></td>
        <td style="width:26%"><div class="val-inline">{{ $td->administrator_tin ?: '' }}</div></td>
    </tr>
    <tr>
        <td><span class="lbl">Address</span></td>
        <td><div class="val-inline">{{ $td->administrator_address ?: '' }}</div></td>
        <td><span class="lbl">Telephone No.</span></td>
        <td><div class="val-inline">{{ $td->administrator_telephone ?: '' }}</div></td>
    </tr>
</table>

{{-- Location of Property --}}
<table class="form" style="margin-top:4px;">
    <tr>
        <td style="width:18%"><span class="lbl">Location of Property :</span></td>
        <td style="width:29%"><div class="val-inline center">{{ $td->property_street ?: '' }}</div></td>
        <td style="width:24%"><div class="val-inline center">{{ $td->barangay?->name ?: '' }}</div></td>
        <td style="width:29%"><div class="val-inline center">{{ $municipalityLine }}</div></td>
    </tr>
    <tr>
        <td></td>
        <td class="caption">(Number and Street)</td>
        <td class="caption">(Barangay/District)</td>
        <td class="caption">(Municipality and Province/City)</td>
    </tr>
</table>

{{-- Title / Survey / Cad. Lot / Block section (municipal variant) --}}
<table class="form" style="margin-top:4px;">
    <tr>
        <td style="width:14%"><span class="lbl">KOT Blg.:</span></td>
        <td style="width:36%"><div class="val-inline">{{ $td->kot_blg ?: '' }}</div></td>
        <td style="width:14%"><span class="lbl">Survey No.</span></td>
        <td style="width:36%"><div class="val-inline">{{ $td->survey_number ?: '' }}</div></td>
    </tr>
    <tr>
        <td><span class="lbl">OCT No.:</span></td>
        <td><div class="val-inline">{{ $td->oct_tct_cloa_no ?: '' }}</div></td>
        <td><span class="lbl">TCT No.</span></td>
        <td><div class="val-inline">{{ $td->cct ?: '' }}</div></td>
    </tr>
    <tr>
        <td><span class="lbl">Cad. Lot No.</span></td>
        <td><div class="val-inline">{{ $td->lot_number ?: '' }}</div></td>
        <td><span class="lbl">Blk. No.:</span></td>
        <td><div class="val-inline">{{ $td->block_number ?: '' }}</div></td>
    </tr>
</table>

{{-- Boundaries (vertical N / E / S / W) --}}
<table class="form" style="margin-top:6px;">
    <tr>
        <td style="width:12%; vertical-align:top; padding-top:2px;"><span class="lbl">Boundaries:</span></td>
        <td style="width:88%">
            <table class="no-border" style="width:100%;">
                <tr>
                    <td style="width:8%;"><span class="lbl">North:</span></td>
                    <td><div class="val-inline">{{ $td->boundary_north ?: '' }}</div></td>
                </tr>
                <tr>
                    <td><span class="lbl">East:</span></td>
                    <td><div class="val-inline">{{ $td->boundary_east ?: '' }}</div></td>
                </tr>
                <tr>
                    <td><span class="lbl">South:</span></td>
                    <td><div class="val-inline">{{ $td->boundary_south ?: '' }}</div></td>
                </tr>
                <tr>
                    <td><span class="lbl">West:</span></td>
                    <td><div class="val-inline">{{ $td->boundary_west ?: '' }}</div></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Kind of Property Assessed (simplified LAND heading — matches municipal variant) --}}
@php
    $primaryKind = 'LAND';
    if (!empty($kinds)) {
        $first = strtoupper((string) $kinds[0]);
        if ($first !== '') $primaryKind = $first;
    }
@endphp
<div style="margin-top:6px;">
    <div style="font-weight:bold;">KIND OF PROPERTY ASSESSED :</div>
</div>
<div style="text-align:center; font-weight:bold; font-size:11px; margin-top:2px;">{{ $primaryKind }}</div>

{{-- Simplified 6-column Classification / Assessment table (matches municipal variant) --}}
@php
    $simpleRowCount = max(count($valuationRows), count($assessmentRows), 7);
    $simpleTotalArea      = 0;
    $simpleTotalMarketVal = 0;
    $simpleTotalAssessed  = 0;
@endphp
<table class="assess" style="margin-top:4px;">
    <thead>
        <tr>
            <th style="width:16%">CLASSIFICATION</th>
            <th style="width:12%">AREA<br><span style="font-weight:normal;">( ha./sq.m. )</span></th>
            <th style="width:18%">ACTUAL USE</th>
            <th style="width:20%">MARKET VALUE</th>
            <th style="width:14%">ASSESSMENT<br>LEVEL</th>
            <th style="width:20%">ASSESSED VALUE</th>
        </tr>
    </thead>
    <tbody>
        @for ($i = 0; $i < $simpleRowCount; $i++)
            @php
                $L = $valuationRows[$i] ?? [];
                $R = $assessmentRows[$i] ?? [];
                $classification = $L['classification_name'] ?? ($L['classification'] ?? ($R['classification'] ?? ''));
                $area           = $L['area'] ?? null;
                $actualUse      = $L['actual_use'] ?? '';
                $marketVal      = $R['adjusted_market_value'] ?? ($L['base_market_value'] ?? null);
                $assmLevel      = $R['assessment_level'] ?? null;
                $assessed       = $R['assessed_value'] ?? null;

                if ($area !== null && $area !== '')     $simpleTotalArea      += (float) $area;
                if ($marketVal !== null && $marketVal !== '') $simpleTotalMarketVal += (float) $marketVal;
                if ($assessed !== null && $assessed !== '')   $simpleTotalAssessed  += (float) $assessed;
            @endphp
            <tr>
                <td>{{ $classification }}</td>
                <td class="num">{{ ($area !== null && $area !== '') ? $fmtNum($area, 6) : '' }}</td>
                <td>{{ $actualUse }}</td>
                <td class="num">{{ ($marketVal !== null && $marketVal !== '') ? '₱  ' . $fmtNum($marketVal, 2) : '' }}</td>
                <td class="num">{{ ($assmLevel !== null && $assmLevel !== '') ? ($fmtNum($assmLevel, 0) . '%') : '' }}</td>
                <td class="num">{{ ($assessed !== null && $assessed !== '') ? '₱  ' . $fmtNum($assessed, 2) : '' }}</td>
            </tr>
        @endfor
        <tr>
            <td class="tot right">TOTAL</td>
            <td class="num tot">{{ $fmtNum($simpleTotalArea, 6) }}</td>
            <td></td>
            <td class="num tot">₱  {{ $fmtNum($simpleTotalMarketVal, 2) }}</td>
            <td></td>
            <td class="num tot">₱  {{ $fmtNum($simpleTotalAssessed, 2) }}</td>
        </tr>
    </tbody>
</table>

{{-- Assessed value in words + taxable/exempt + effectivity (single compact row) --}}
<table class="form" style="margin-top:6px;">
    <tr>
        <td style="width:14%"><span class="lbl">Assessed value :</span></td>
        <td style="width:86%"><div class="val-inline" style="text-transform:uppercase;">{{ $td->assessed_value_words ?: '' }}</div></td>
    </tr>
    <tr>
        <td></td>
        <td class="caption">( Amount in Words )</td>
    </tr>
</table>

<table class="form" style="margin-top:2px;">
    <tr>
        <td style="width:5%;"><span class="chk">{{ strtolower((string) $td->taxability) === 'taxable' ? '[X]' : '[  ]' }}</span></td>
        <td style="width:7%;"><b>Taxable</b></td>
        <td style="width:5%;"><span class="chk">{{ strtolower((string) $td->taxability) === 'exempt' ? '[X]' : '[  ]' }}</span></td>
        <td style="width:7%;"><b>Exempt</b></td>
        <td style="width:36%; text-align:right;"><span class="lbl">Effectivity of Assessment/Reassessment :</span></td>
        <td style="width:20%"><div class="val-inline center">{{ $td->effectivity_quarter ?: '' }}</div><div class="caption">Qtr.</div></td>
        <td style="width:20%"><div class="val-inline center">{{ $td->effectivity_year ?: '' }}</div><div class="caption">Yr.</div></td>
    </tr>
</table>

{{-- Approved By --}}
<table class="form" style="margin-top:8px;">
    <tr>
        <td style="width:22%; text-align:right;"><span class="lbl">APPROVED BY :</span></td>
        <td style="width:48%"><div class="sig-line">{{ $td->approved_by_name ?: '' }}</div><div class="caption">Provincial Assessor</div></td>
        <td style="width:30%"><div class="sig-line">{{ $fmtDate($td->approved_at ?: $td->date_issued) }}</div><div class="caption">Date</div></td>
    </tr>
</table>

{{-- This declaration cancels TD No. --}}
<table class="form" style="margin-top:6px;">
    <tr>
        <td style="width:24%"><span class="lbl">This declaration cancels TD No.</span></td>
        <td style="width:18%"><div class="val-inline">{{ $td->previous_td_number ?: '' }}</div></td>
        <td style="width:7%"><span class="lbl">Owner :</span></td>
        <td style="width:23%"><div class="val-inline">{{ $td->previous_owner ?: '' }}</div></td>
        <td style="width:12%; text-align:right;"><span class="lbl">Previous A.V. :</span></td>
        <td style="width:16%"><div class="val-inline">₱ {{ $fmtNum($td->previous_av, 2) }}</div></td>
    </tr>
</table>

{{-- Memoranda --}}
<table style="margin-top:8px; width:100%; border-collapse:collapse;">
    <tr>
        <td style="width:14%; padding:4px 6px; font-weight:bold; border:1px solid #000; vertical-align:top;">Memoranda :</td>
        <td style="padding:4px 6px; border:1px solid #000; border-left:0; vertical-align:top; height:48px; font-size:8.5px; line-height:1.4;">{!! nl2br(e($td->memoranda ?: '')) !!}</td>
    </tr>
</table>

{{-- Doc. File No. --}}
<table class="form" style="margin-top:4px;">
    <tr>
        <td style="width:70%"></td>
        <td style="width:12%; text-align:right;"><span class="lbl">Doc. File No. :</span></td>
        <td style="width:18%"><div class="val-inline">{{ $td->doc_file_no ?? '' }}</div></td>
    </tr>
</table>

{{-- Notes (bordered) --}}
<div style="margin-top:8px; border:1px solid #000; padding:6px 8px; font-size:7.8px; line-height:1.4; color:#111;">
    <b>Note :</b> * This declaration is for real property taxation purposes only. The valuation indicated herein are based on the schedule of unit market values prepared for the purpose and duly enacted into an Ordinance by the Sangguniang Panlalawigan dated <span style="border-bottom:1px solid #000; padding:0 30px;">&nbsp;</span>, {{ optional($td->approved_at ?: $td->date_issued)?->format('Y') ?: date('Y') }}.<br>
    It does not and cannot by itself alone confer any ownership or legal title to the property.
</div>

</body>
</html>
