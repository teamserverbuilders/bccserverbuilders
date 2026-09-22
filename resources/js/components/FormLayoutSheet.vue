<template>
    <div class="sheet-desk">
        <div class="sheet-page" :style="{ width: `${paperWidth()}px`, height: `${paperHeight()}px` }">
            <div v-for="element in fields" :key="element.id" class="sheet-block" :style="boxStyle(element)">
                <div v-if="element.type === 'header' || element.type === 'subheader'" class="sheet-copy">
                    <p v-for="(line, i) in textLines(element)" :key="i">
                        <span v-if="listed(element)" class="marker">{{ marker(element, i) }}</span>{{ line }}
                    </p>
                </div>

                <div v-else-if="element.type === 'logo'" class="sheet-logo">
                    <img v-if="element.image" :src="imageSrc(element.image)" alt="" />
                </div>

                <div v-else-if="element.type === 'line'" class="sheet-line"></div>

                <div v-else-if="element.type === 'docHeader' || element.type === 'docFooter'" class="sheet-band">
                    <div v-if="element.variant === 'columns'" class="sheet-cols">
                        <span>{{ element.parts?.left }}</span>
                        <span class="center">{{ element.parts?.center }}</span>
                        <span class="right">{{ element.parts?.right }}</span>
                    </div>
                    <p v-else>{{ element.text }}</p>
                </div>

                <div v-else-if="element.type === 'pageNumber'" class="sheet-num" :style="{ justifyContent: element.align === 'center' ? 'center' : element.align === 'right' ? 'flex-end' : 'flex-start' }">
                    {{ pageLabel(element) }}
                </div>

                <div v-else-if="element.type === 'table'" class="sheet-table">
                    <div v-for="(row, r) in element.cells" :key="r" class="sheet-row" :style="{ height: heightsOf(element)[r] + 'px' }">
                        <div v-for="(cell, c) in row" :key="cell.id" class="sheet-cell" :style="cellBox(element, r, c)">
                            <textarea
                                :value="modelValue[cell.id] || ''"
                                :placeholder="cell.text"
                                rows="1"
                                :class="{ 'is-filled': highlights.includes(cell.id) }"
                                @input="setValue(cell.id, typed(cell.text, $event.target.value))"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <label v-else class="sheet-field">
                    <span>{{ element.text || 'Field' }}<em v-if="element.required">*</em></span>
                    <textarea v-if="element.type === 'textarea'" :value="modelValue[element.id] || ''" :class="{ 'is-filled': highlights.includes(element.id) }" @input="setValue(element.id, typed(element.text, $event.target.value))"></textarea>
                    <select v-else-if="element.type === 'select'" :value="modelValue[element.id] || ''" :class="{ 'is-filled': highlights.includes(element.id) }" @change="setValue(element.id, $event.target.value)">
                        <option value="">Select</option>
                        <option v-for="option in element.options || []" :key="option" :value="option">{{ option }}</option>
                    </select>
                    <input v-else-if="element.type === 'checkbox'" type="checkbox" :checked="!!modelValue[element.id]" @change="setValue(element.id, $event.target.checked)" />
                    <input v-else :type="element.type === 'number' ? 'number' : element.type === 'date' ? 'date' : 'text'" :value="modelValue[element.id] || ''" :class="{ 'is-filled': highlights.includes(element.id) }" @input="setValue(element.id, typed(element.text, $event.target.value))" />
                </label>
            </div>
        </div>
    </div>
</template>

<script setup>
import { digitsOnly, numberOnlyLabel } from '@/utils/digitsOnly';

const props = defineProps({
    fields: { type: Array, default: () => [] },
    modelValue: { type: Object, default: () => ({}) },
    page: { type: Object, default: null },
    highlights: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

function paperWidth() {
    return Number(props.page?.width) || 794;
}

function paperHeight() {
    return Number(props.page?.height) || 1123;
}

function imageSrc(url) {
    if (!url) return '';
    try {
        const path = new URL(url, window.location.origin).pathname;
        if (path.startsWith('/storage/') || path.startsWith('/images/')) return path;
    } catch (err) {
        return url;
    }
    return url;
}

function typed(label, value) {
    return numberOnlyLabel(label) ? digitsOnly(value) : value;
}

function setValue(id, value) {
    emit('update:modelValue', { ...props.modelValue, [id]: value });
}

function listed(element) {
    return element.listType && element.listType !== 'none';
}

function textLines(element) {
    const lines = String(element.text || '').split('\n');
    return lines.length ? lines : [''];
}

function alphaLabel(n) {
    let value = n;
    let out = '';
    while (value > 0) {
        value -= 1;
        out = String.fromCharCode(97 + (value % 26)) + out;
        value = Math.floor(value / 26);
    }
    return out;
}

function romanLabel(n) {
    const map = [[10, 'x'], [9, 'ix'], [5, 'v'], [4, 'iv'], [1, 'i']];
    let out = '';
    let value = n;
    map.forEach(([step, glyph]) => {
        while (value >= step) {
            out += glyph;
            value -= step;
        }
    });
    return out || 'i';
}

function numberMarker(n, style) {
    if (style === 'alpha') return `${alphaLabel(n)}.`;
    if (style === 'Alpha') return `${alphaLabel(n).toUpperCase()}.`;
    if (style === 'roman') return `${romanLabel(n)}.`;
    if (style === 'paren') return `${n})`;
    return `${n}.`;
}

function marker(element, index) {
    const n = index + 1;
    const level = Number(element.indent) || 0;
    if (element.listType === 'bullet') return { disc: '•', circle: '◦', square: '▪' }[element.listStyle] || '•';
    if (element.listType === 'number') return numberMarker(n, element.listStyle || 'decimal');
    if (element.listStyle === 'legal') {
        if (level <= 0) return `${n}.`;
        if (level === 1) return `1.${n}`;
        return `1.1.${n}`;
    }
    if (level <= 0) return `${n}.`;
    if (level === 1) return `${alphaLabel(n)}.`;
    return `${romanLabel(n)}.`;
}

function fitGrid(values, count, total, min) {
    if (!count) return [];
    if (!Array.isArray(values) || values.length !== count) {
        return Array.from({ length: count }, () => total / count);
    }
    return values.map((value) => Math.max(min, Number(value) || min));
}

function widthsOf(element) {
    return fitGrid(element.colWidths, element.cells?.[0]?.length || 0, element.w, 36);
}

function heightsOf(element) {
    return fitGrid(element.rowHeights, element.cells?.length || 0, element.h, 22);
}

function pageLabel(element) {
    if (element.numberFormat === 'page') return 'Page 1';
    if (element.numberFormat === 'pageOf') return 'Page 1 of 1';
    return '1';
}

function boxStyle(element) {
    const weight = `${element.borderWidth || 1}px solid ${element.borderColor || '#000'}`;
    const side = (on) => (on ? weight : 'none');
    const border = element.border || {};
    const boxBorder = element.type === 'table' ? {} : {
        borderTop: side(border.top),
        borderRight: side(border.right),
        borderBottom: side(border.bottom),
        borderLeft: side(border.left),
    };
    return {
        left: `${element.x}px`,
        top: `${element.y}px`,
        width: `${element.w}px`,
        height: `${element.h}px`,
        fontFamily: element.fontFamily || 'Times New Roman',
        fontSize: `${element.fontSize || 11}px`,
        fontWeight: element.bold ? '700' : '400',
        fontStyle: element.italic ? 'italic' : 'normal',
        textDecoration: element.underline ? 'underline' : 'none',
        textAlign: element.align || 'left',
        lineHeight: String(element.lineHeight || 1.15),
        background: element.type === 'table' ? 'transparent' : (element.shading || 'transparent'),
        paddingTop: element.type === 'line' ? '0' : `${element.spaceBefore || 0}px`,
        paddingBottom: element.type === 'line' ? '0' : `${element.spaceAfter || 0}px`,
        paddingLeft: element.type === 'line' ? '0' : `${(element.indent || 0) * 18}px`,
        zIndex: element.type === 'pageNumber' ? 5 : 1,
        ...boxBorder,
    };
}

function cellStyle(element, r, c) {
    const border = element.border || {};
    const line = `${element.borderWidth || 1}px solid ${element.borderColor || '#000'}`;
    const guide = element.gridlines === false ? '1px solid transparent' : '1px dotted #b5b5b5';
    const rows = element.cells.length;
    const cols = element.cells[r]?.length || 1;
    return {
        borderTop: (r === 0 ? border.top : border.insideH) ? line : guide,
        borderLeft: (c === 0 ? border.left : border.insideV) ? line : guide,
        borderRight: c === cols - 1 ? (border.right ? line : guide) : 'none',
        borderBottom: r === rows - 1 ? (border.bottom ? line : guide) : 'none',
        backgroundColor: element.shading || 'transparent',
    };
}

function cellBox(element, r, c) {
    const cell = element.cells[r][c];
    const width = Number(cell.w) || element.w / (element.cells[r].length || 1);
    return { ...cellStyle(element, r, c), width: `${width}px` };
}
</script>

<style scoped>
.sheet-desk { overflow: auto; background: #e6e6e6; border: 1px solid #d0d0d0; padding: 28px; }
.sheet-page { position: relative; margin: 0 auto; background: white; box-shadow: 0 8px 24px rgba(0,0,0,.18); }
.sheet-block { position: absolute; box-sizing: border-box; }
.sheet-copy, .sheet-logo, .sheet-line, .sheet-band, .sheet-num, .sheet-table, .sheet-field { width: 100%; height: 100%; }
.sheet-copy p, .sheet-band p { margin: 0; }
.sheet-logo { position: relative; display: flex; align-items: center; justify-content: center; }
.sheet-logo img { position: absolute; inset: 0; width: 100%; height: 100%; max-width: none; object-fit: contain; }
.sheet-cols { display: grid; grid-template-columns: 1fr 1fr 1fr; height: 100%; }
.sheet-cols .center { text-align: center; }
.sheet-cols .right { text-align: right; }
.sheet-num { display: flex; align-items: center; }
.sheet-table { display: flex; flex-direction: column; }
.sheet-row { display: flex; width: 100%; min-height: 0; }
.sheet-cell { position: relative; box-sizing: border-box; flex: none; min-width: 0; overflow: hidden; }
.sheet-field input, .sheet-field textarea, .sheet-field select { width: 100%; box-sizing: border-box; border: 1px solid #94a3b8; background: white; font: inherit; color: inherit; }
.sheet-cell textarea { display: block; width: 100%; height: 100%; min-height: 0; border: 0; resize: none; outline: none; background: transparent; padding: 4px 6px; font: inherit; }
.sheet-cell textarea.is-filled, .sheet-field input.is-filled, .sheet-field textarea.is-filled, .sheet-field select.is-filled { background: #f0fdf4; }
.sheet-field { display: flex; flex-direction: column; gap: 4px; }
.sheet-field span { font-size: 11px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #1a3557; }
.sheet-field em { color: #ce1126; font-style: normal; margin-left: 2px; }
.sheet-field textarea, .sheet-field input, .sheet-field select { flex: 1; min-height: 0; border-radius: 2px; padding: 4px 6px; }
.sheet-field input[type="checkbox"] { width: 16px; height: 16px; flex: none; }
.marker { display: inline-block; min-width: 1.5em; }
.block-header { }
</style>
