/**
 * Flags OCR review values that contain misspelled or garbled words.
 * Codes, amounts, and dates are left alone. Person names that simply
 * are not in the word list are not flagged; only unusual letter patterns
 * and near-misses of known form words are.
 */

const SKIP_KEYS = new Set([
    'td_number', 'property_identification_no', 'property_index_number', 'pin',
    'arp_number', 'arp_no', 'update_code', 'tin', 'owner_tin', 'administrator_tin',
    'telephone', 'owner_telephone', 'administrator_telephone',
    'survey_no', 'survey_number', 'lot_no', 'lot_number', 'block_no', 'block_number',
    'cad_pls_lot_no', 'oct_tct_cloa', 'oct_tct_cloa_no', 'oct_tct_kot_no',
    'area', 'unit_value', 'base_market_value', 'market_value', 'adjusted_market_value',
    'assessment_level', 'assessed_value', 'rounded_assessed_value', 'previous_av',
    'previous_td', 'previous_td_number',
    'plant_area', 'plant_non_fb', 'plant_fb', 'plant_total', 'plant_unit_value',
    'plant_base_market_value', 'plant_prod_class',
    'adj_along_road', 'adj_kms_weather_road', 'adj_kms_to_market',
    'adj_total_adjustments', 'adj_total_percentage',
    'effectivity_year', 'effectivity_quarter', 'tax_effectivity_year', 'tax_effectivity_quarter',
    'effectivity_date', 'approval_date', 'date_issued',
    'document_type', 'conforme_ctc_no',
]);

const STATIC_WORDS = `
agricultural residential commercial industrial mineral special timber forest
improvements taxable exempt assessment reassessment declaration property owner
address municipality province barangay land building machinery coconut rice corn
mango banana vacant orchard pasture swamp creek river road north south east west
quarter effectivity approved assessor municipal city street village subdivision
block survey cadastral memorandum memoranda pursuant revised section ordinance
previous administrator occupant beneficial telephone classification subclass actual
market value assessed plant tree fruit bearing adjustment percentage weather
conforme issued appraisal inspection location identification number owner
camarines albay sorsogon catanduanes masbate quezon laguna batangas cavite rizal
bulacan pampanga tarlac nueva ecija zambales bataan aurora isabela cagayan
ilocos pangasinan benguet ifugao kalinga apayao mountain abra manila caloocan
quezon makati pasig taguig paranaque muntinlupa las pinas valenzuela malabon
navotas mandaluyong marikina san juan pasay pateros baao iriga naga nabua bato
buhi bula balatan camaligan canaman gainza magarao milaor minalabac pamplona
pasacao pili bombon calabanga caramoan del gallego goa lupi ocampo presentacion
ragay sagñay sipocot siruma tigonan tinambac poblacion vicente santiago
agricultural residential
`.split(/\s+/).filter(Boolean);

const ALLOWED_SHORT = new Set([
    'de', 'del', 'dela', 'los', 'las', 'san', 'sta', 'sto', 'ng', 'sa', 'na', 'ang', 'mga',
    'st', 'brgy', 'bgy', 'rd', 'ave', 'ext', 'blk', 'lot', 'no', 'of', 'the', 'and', 'for',
    'with', 'from', 'inc', 'jr', 'sr', 'ii', 'iii', 'iv', 'vi', 'vii', 'viii', 'ix', 'xi',
    'ne', 'nw', 'se', 'sw', 'ha', 'sq', 'sqm', 'php', 'arp', 'pin', 'tin', 'oct', 'tct',
    'cloa', 'kot', 'faas', 'td', 'av', 'qtr',
]);

const staticDict = new Set(STATIC_WORDS.map((word) => word.toLowerCase()));

function levenshtein(a, b, limit) {
    if (Math.abs(a.length - b.length) > limit) return limit + 1;
    const prev = new Array(b.length + 1);
    const curr = new Array(b.length + 1);
    for (let j = 0; j <= b.length; j++) prev[j] = j;
    for (let i = 1; i <= a.length; i++) {
        curr[0] = i;
        let rowMin = curr[0];
        for (let j = 1; j <= b.length; j++) {
            const cost = a[i - 1] === b[j - 1] ? 0 : 1;
            curr[j] = Math.min(prev[j] + 1, curr[j - 1] + 1, prev[j - 1] + cost);
            if (curr[j] < rowMin) rowMin = curr[j];
        }
        if (rowMin > limit) return limit + 1;
        for (let j = 0; j <= b.length; j++) prev[j] = curr[j];
    }
    return prev[b.length];
}

function buildDict(extraWords) {
    const dict = new Set(staticDict);
    for (const phrase of extraWords || []) {
        const parts = String(phrase).toLowerCase().match(/[a-z]{4,}/g) || [];
        parts.forEach((part) => dict.add(part));
    }
    return dict;
}

function unusualNote(token) {
    if (/^\d+(?:st|nd|rd|th)$/i.test(token)) return null;
    if (/[a-z]/i.test(token) && /\d/.test(token)) {
        return `“${token}” looks unusual`;
    }
    const letters = token.replace(/[^A-Za-z]/g, '');
    if (letters.length >= 5 && !/[aeiou]/i.test(letters)) {
        return `“${token}” looks unusual`;
    }
    if (/[^aeiou]{5,}/i.test(letters)) {
        return `“${token}” looks unusual`;
    }
    if (/([^aeiou])\1{2,}/i.test(letters)) {
        return `“${token}” looks unusual`;
    }
    return null;
}

function nearestKnown(token, dict) {
    const word = token.toLowerCase();
    if (word.length < 5 || dict.has(word)) return null;
    const max = word.length >= 8 ? 2 : 1;
    let best = null;
    let bestDist = max + 1;
    for (const candidate of dict) {
        if (candidate.length < 5) continue;
        if (Math.abs(candidate.length - word.length) > max) continue;
        if (candidate.slice(0, 2) !== word.slice(0, 2)) continue;
        const dist = levenshtein(word, candidate, max);
        if (dist > 0 && dist < bestDist) {
            best = candidate;
            bestDist = dist;
        }
    }
    if (!best) return null;
    if (token === token.toUpperCase()) return best.toUpperCase();
    return best.charAt(0).toUpperCase() + best.slice(1);
}

function tokenAllowed(token, dict) {
    const word = token.toLowerCase();
    if (word.length <= 3 || ALLOWED_SHORT.has(word) || dict.has(word)) return true;
    if (/^(?:i|v|x|l|c|d|m)+$/i.test(word)) return true;
    return false;
}

/**
 * @returns {string|null} A short warning, or null when the value looks fine.
 */
export function findOcrTextIssues(value, key, extraWords = []) {
    if (value == null || Array.isArray(value) || typeof value === 'object') return null;
    if (SKIP_KEYS.has(key)) return null;

    const text = String(value).trim();
    if (!text) return null;

    const dict = buildDict(extraWords);
    const tokens = text.match(/[A-Za-z][A-Za-z0-9]{2,}/g) || [];
    const notes = [];

    for (const token of tokens) {
        if (tokenAllowed(token, dict)) continue;
        const unusual = unusualNote(token);
        if (unusual) {
            notes.push(unusual);
            continue;
        }
        const suggestion = nearestKnown(token, dict);
        if (suggestion) {
            notes.push(`“${token}” may be “${suggestion}”`);
        }
    }

    if (!notes.length) return null;
    return `Check spelling: ${notes.slice(0, 2).join('; ')}`;
}
