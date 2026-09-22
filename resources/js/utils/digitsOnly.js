export function digitsOnly(value) {
    return String(value ?? '').replace(/\D/g, '');
}

/** True for labels such as "TD No." or "Tel No.", not "No./Street". */
export function numberOnlyLabel(label) {
    const text = String(label || '');
    if (/street/i.test(text)) return false;
    return /\bno\./i.test(text);
}
