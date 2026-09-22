<template>
    <div class="designer" @pointerdown="closeMenus">
        <div class="titlebar">
            <div class="doc-title">
                <strong>Form Layout</strong>
                <input v-model="formName" class="doc-name" :disabled="mode === 'fill'" />
            </div>
            <div class="title-tools">
                <button type="button" class="tab" @click="goToList">Form list</button>
                <button v-for="tab in tabs" :key="tab.id" type="button" class="tab" :class="{ on: target === tab.id }" @click="switchTarget(tab.id)">
                    {{ tab.label }}
                </button>
                <select class="doc-pick" :value="layoutId || ''" @change="onPickForm($event.target.value)">
                    <option value="">New form</option>
                    <option v-for="item in layouts" :key="item.id" :value="item.id">{{ item.name }}</option>
                </select>
                <button type="button" class="tab" @click="startNew">New</button>
                <button type="button" class="tab" :class="{ on: mode === 'design' }" @click="mode = 'design'">Design</button>
                <button type="button" class="tab" :class="{ on: mode === 'fill' }" @click="enterFill">Fill</button>
                <button type="button" class="save" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save' }}</button>
            </div>
        </div>

        <div class="ribbon" @pointerdown.stop>
            <div class="ribbon-group">
                <div class="font-stack">
                    <div class="font-row">
                        <select :value="selected?.fontFamily || 'Times New Roman'" :disabled="!selected" @change="setStyle('fontFamily', $event.target.value)">
                            <option v-for="font in fonts" :key="font">{{ font }}</option>
                        </select>
                        <select class="size" :value="selected?.fontSize || 11" :disabled="!selected" @change="setStyle('fontSize', Number($event.target.value))">
                            <option v-for="size in fontSizes" :key="size" :value="size">{{ size }}</option>
                        </select>
                    </div>
                    <div class="font-row">
                        <button type="button" class="mini" :class="{ on: selected?.bold }" :disabled="!selected" @click="toggleStyle('bold')"><b>B</b></button>
                        <button type="button" class="mini" :class="{ on: selected?.italic }" :disabled="!selected" @click="toggleStyle('italic')"><i>I</i></button>
                        <button type="button" class="mini" :class="{ on: selected?.underline }" :disabled="!selected" @click="toggleStyle('underline')"><u>U</u></button>
                    </div>
                </div>
                <span class="group-caption">Font</span>
            </div>

            <div class="ribbon-group">
                <div class="para-stack">
                    <div class="para-row">
                        <div class="split">
                            <button type="button" class="mini icon" title="Bullets" :class="{ on: selected?.listType === 'bullet' }" :disabled="!canList" @click="toggleList('bullet', 'disc')">
                                <svg viewBox="0 0 16 16"><circle cx="2.2" cy="3" r="1.15"/><circle cx="2.2" cy="8" r="1.15"/><circle cx="2.2" cy="13" r="1.15"/><path d="M5.5 3h9M5.5 8h9M5.5 13h9" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                            </button>
                            <button type="button" class="caret" title="Bullet library" :disabled="!canList" @click="togglePara($event, 'bullets')">▾</button>
                            <div v-if="paraMenu === 'bullets'" class="word-menu para-menu" :style="menuStyle()">
                                <button type="button" @click="applyList('none', 'none')">None</button>
                                <button type="button" @click="applyList('bullet', 'disc')"><span class="marker">•</span> Filled round</button>
                                <button type="button" @click="applyList('bullet', 'circle')"><span class="marker">◦</span> Hollow round</button>
                                <button type="button" @click="applyList('bullet', 'square')"><span class="marker">▪</span> Square</button>
                            </div>
                        </div>
                        <div class="split">
                            <button type="button" class="mini icon" title="Numbering" :class="{ on: selected?.listType === 'number' }" :disabled="!canList" @click="toggleList('number', 'decimal')">
                                <svg viewBox="0 0 16 16"><text x="0" y="5" font-size="5" fill="currentColor">1</text><text x="0" y="10" font-size="5" fill="currentColor">2</text><text x="0" y="15" font-size="5" fill="currentColor">3</text><path d="M6 3.5h9M6 8.5h9M6 13.5h9" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                            </button>
                            <button type="button" class="caret" title="Numbering library" :disabled="!canList" @click="togglePara($event, 'numbers')">▾</button>
                            <div v-if="paraMenu === 'numbers'" class="word-menu para-menu" :style="menuStyle()">
                                <button type="button" @click="applyList('none', 'none')">None</button>
                                <button type="button" @click="applyList('number', 'decimal')">1. 2. 3.</button>
                                <button type="button" @click="applyList('number', 'paren')">1) 2) 3)</button>
                                <button type="button" @click="applyList('number', 'alpha')">a. b. c.</button>
                                <button type="button" @click="applyList('number', 'Alpha')">A. B. C.</button>
                                <button type="button" @click="applyList('number', 'roman')">i. ii. iii.</button>
                            </div>
                        </div>
                        <div class="split">
                            <button type="button" class="mini icon" title="Multilevel List" :class="{ on: selected?.listType === 'multi' }" :disabled="!canList" @click="toggleList('multi', 'outline')">
                                <svg viewBox="0 0 16 16"><text x="0" y="5" font-size="5" fill="currentColor">1</text><text x="3" y="10" font-size="5" fill="currentColor">a</text><text x="6" y="15" font-size="5" fill="currentColor">i</text><path d="M6 3.5h9M8 8.5h7M10 13.5h5" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                            </button>
                            <button type="button" class="caret" title="Multilevel list" :disabled="!canList" @click="togglePara($event, 'multi')">▾</button>
                            <div v-if="paraMenu === 'multi'" class="word-menu para-menu" :style="menuStyle()">
                                <button type="button" @click="applyList('none', 'none')">None</button>
                                <button type="button" @click="applyList('multi', 'outline')">1. / a. / i.</button>
                                <button type="button" @click="applyList('multi', 'legal')">1 / 1.1 / 1.1.1</button>
                            </div>
                        </div>
                        <span class="para-gap"></span>
                        <button type="button" class="mini icon" title="Decrease Indent" :disabled="!selected || selected.type === 'line'" @click="changeIndent(-1)">
                            <svg viewBox="0 0 16 16"><path d="M10 3H3M10 8H5M10 13H3" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M13 5.5 10 8l3 2.5" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                        </button>
                        <button type="button" class="mini icon" title="Increase Indent" :disabled="!selected || selected.type === 'line'" @click="changeIndent(1)">
                            <svg viewBox="0 0 16 16"><path d="M3 3h7M3 8h5M3 13h7" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M11 5.5 14 8l-3 2.5" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                        </button>
                        <div class="split">
                            <button type="button" class="mini icon" title="Sort" :disabled="!selected" @click="togglePara($event, 'sort')">
                                <svg viewBox="0 0 16 16"><text x="1" y="7" font-size="7" font-weight="700" fill="currentColor">A</text><text x="1" y="15" font-size="7" font-weight="700" fill="currentColor">Z</text><path d="M11 3v9M11 12l-2-2M11 12l2-2" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                            </button>
                            <div v-if="paraMenu === 'sort'" class="word-menu para-menu" :style="menuStyle()">
                                <button type="button" @click="sortSelected('asc')">Sort A to Z</button>
                                <button type="button" @click="sortSelected('desc')">Sort Z to A</button>
                            </div>
                        </div>
                        <button type="button" class="mini icon pilcrow-btn" title="Show/Hide ¶" :class="{ on: showMarks }" @click="showMarks = !showMarks">¶</button>
                    </div>
                    <div class="para-row">
                        <button type="button" class="mini icon" title="Align Left" :class="{ on: selected?.align === 'left' }" :disabled="!selected" @click="setStyle('align', 'left')">
                            <svg viewBox="0 0 16 16"><path d="M2 3h12M2 7h8M2 11h12M2 15h8" fill="none" stroke="currentColor" stroke-width="1.3"/></svg>
                        </button>
                        <button type="button" class="mini icon" title="Center" :class="{ on: selected?.align === 'center' }" :disabled="!selected" @click="setStyle('align', 'center')">
                            <svg viewBox="0 0 16 16"><path d="M2 3h12M4 7h8M2 11h12M4 15h8" fill="none" stroke="currentColor" stroke-width="1.3"/></svg>
                        </button>
                        <button type="button" class="mini icon" title="Align Right" :class="{ on: selected?.align === 'right' }" :disabled="!selected" @click="setStyle('align', 'right')">
                            <svg viewBox="0 0 16 16"><path d="M2 3h12M6 7h8M2 11h12M6 15h8" fill="none" stroke="currentColor" stroke-width="1.3"/></svg>
                        </button>
                        <button type="button" class="mini icon" title="Justify" :class="{ on: selected?.align === 'justify' }" :disabled="!selected" @click="setStyle('align', 'justify')">
                            <svg viewBox="0 0 16 16"><path d="M2 3h12M2 7h12M2 11h12M2 15h12" fill="none" stroke="currentColor" stroke-width="1.3"/></svg>
                        </button>
                        <span class="para-gap"></span>
                        <div class="split">
                            <button type="button" class="mini icon" title="Line and Paragraph Spacing" :disabled="!selected" @click="togglePara($event, 'spacing')">
                                <svg viewBox="0 0 16 16"><path d="M3 2.5h8M3 6h8M3 10h8M3 13.5h8" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="M13 2v4M13 2l-1.4 1.4M13 2l1.4 1.4M13 14v-4M13 14l-1.4-1.4M13 14l1.4-1.4" fill="none" stroke="currentColor" stroke-width="1.1"/></svg>
                            </button>
                            <button type="button" class="caret" title="Line and paragraph spacing" :disabled="!selected" @click="togglePara($event, 'spacing')">▾</button>
                            <div v-if="paraMenu === 'spacing'" class="word-menu para-menu" :style="menuStyle()">
                                <button v-for="gap in lineHeights" :key="gap" type="button" :class="{ picked: Number(selected?.lineHeight) === gap }" @click="setStyle('lineHeight', gap)">{{ gap.toFixed(gap === 1.15 ? 2 : 1) }}</button>
                                <button type="button" @click="changeSpacing('spaceBefore', true)">Add Space Before</button>
                                <button type="button" @click="changeSpacing('spaceBefore', false)">Remove Space Before</button>
                                <button type="button" @click="changeSpacing('spaceAfter', true)">Add Space After</button>
                                <button type="button" @click="changeSpacing('spaceAfter', false)">Remove Space After</button>
                            </div>
                        </div>
                        <div class="split">
                            <button type="button" class="mini icon" title="Shading" :disabled="!selected" @click="togglePara($event, 'shade')">
                                <svg viewBox="0 0 16 16"><path d="M3 9.5 8.5 4l3 3L6 12.5H3V9.5z" fill="currentColor"/><path d="M9.2 3.2l1.2-1.2a1 1 0 0 1 1.4 0l1.4 1.4a1 1 0 0 1 0 1.4L12 6" fill="none" stroke="currentColor" stroke-width="1"/><path d="M2 14h12" stroke="#c5a059" stroke-width="2"/></svg>
                            </button>
                            <button type="button" class="caret" title="Shading colors" :disabled="!selected" @click="togglePara($event, 'shade')">▾</button>
                            <div v-if="paraMenu === 'shade'" class="word-menu para-menu shade-menu" :style="menuStyle()">
                                <button type="button" @click="setShading(null)">No Color</button>
                                <div class="swatches">
                                    <button v-for="color in shadeSwatches" :key="color" type="button" class="swatch" :style="{ background: color }" @click="setShading(color)"></button>
                                </div>
                                <label class="shade-custom">More colors <input type="color" :value="selected?.shading || '#ffff00'" @input="setShading($event.target.value, false)" /></label>
                            </div>
                        </div>
                        <div class="split">
                            <button type="button" class="mini icon" title="Borders" @click="togglePara($event, 'borders')">
                                <span class="border-icon all"></span>
                            </button>
                            <button type="button" class="caret" title="Borders" @click="togglePara($event, 'borders')">▾</button>
                            <div v-if="paraMenu === 'borders'" class="word-menu para-menu" :style="menuStyle()">
                                <button v-for="item in borderMenu" :key="item.id" type="button" @click="applyBorder(item.id)">
                                    <span class="menu-icon" :class="item.id"></span>
                                    {{ item.label }}
                                </button>
                                <div v-if="shadingOpen" class="shading">
                                    <label>Color <input v-model="shadeColor" type="color" @input="paintBorder" /></label>
                                    <label>Width
                                        <select v-model.number="shadeWidth" @change="paintBorder">
                                            <option :value="1">1 pt</option>
                                            <option :value="2">2 pt</option>
                                            <option :value="3">3 pt</option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <span class="group-caption">Paragraph</span>
            </div>

            <div class="ribbon-group">
                <div class="insert-row">
                    <button
                        v-for="item in palette"
                        :key="item.type"
                        type="button"
                        class="ribbon-btn"
                        draggable="true"
                        @dragstart="onPaletteDragStart(item.type, $event)"
                        @click="addElement(item.type)"
                    >
                        <i :class="['pi', item.icon]"></i>
                        {{ item.label }}
                    </button>
                </div>
                <span class="group-caption">Insert</span>
            </div>

            <div class="ribbon-group">
                <div class="menu-wrap">
                    <button type="button" class="ribbon-btn" @click="toggleTable($event)">
                        <span class="table-icon"></span>
                        Table
                    </button>
                    <div v-if="tableMenuOpen" class="table-pop" :style="menuStyle()" @pointerleave="hoverCell = { r: 0, c: 0 }">
                        <p>{{ hoverCell.r || 1 }} × {{ hoverCell.c || 1 }} table</p>
                        <div class="table-grid">
                            <button
                                v-for="n in 80"
                                :key="n"
                                type="button"
                                :class="{ hot: cellOf(n).r <= hoverCell.r && cellOf(n).c <= hoverCell.c }"
                                @mouseenter="hoverCell = cellOf(n)"
                                @click="insertTable(cellOf(n).r, cellOf(n).c)"
                            ></button>
                        </div>
                    </div>
                </div>
                <span class="group-caption">Tables</span>
            </div>

            <div class="ribbon-group">
                <div class="hf-stack">
                    <div class="split">
                        <button type="button" class="hf-btn" @click="togglePara($event, 'header')">
                            <svg viewBox="0 0 16 16"><path d="M3 1.5h10v13H3z" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M5 4h6M5 6.5h6M5 9h4" fill="none" stroke="currentColor" stroke-width="1.1"/></svg>
                            Header
                        </button>
                        <button type="button" class="caret" title="Header" @click="togglePara($event, 'header')">▾</button>
                        <div v-if="paraMenu === 'header'" class="word-menu para-menu hf-menu" :style="menuStyle()">
                            <button type="button" @click="setBand('docHeader', 'blank')">Blank</button>
                            <button type="button" @click="setBand('docHeader', 'columns')">Blank (Three Columns)</button>
                            <button type="button" @click="removeBand('docHeader')">Remove Header</button>
                        </div>
                    </div>
                    <div class="split">
                        <button type="button" class="hf-btn" @click="togglePara($event, 'footer')">
                            <svg viewBox="0 0 16 16"><path d="M3 1.5h10v13H3z" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 11.5h7" stroke="#e07a1f" stroke-width="1.6"/></svg>
                            Footer
                        </button>
                        <button type="button" class="caret" title="Footer" @click="togglePara($event, 'footer')">▾</button>
                        <div v-if="paraMenu === 'footer'" class="word-menu para-menu hf-menu" :style="menuStyle()">
                            <button type="button" @click="setBand('docFooter', 'blank')">Blank</button>
                            <button type="button" @click="setBand('docFooter', 'columns')">Blank (Three Columns)</button>
                            <button type="button" @click="removeBand('docFooter')">Remove Footer</button>
                        </div>
                    </div>
                    <div class="split">
                        <button type="button" class="hf-btn" @click="togglePara($event, 'pageNum')">
                            <svg viewBox="0 0 16 16"><path d="M3 1.5h10v13H3z" fill="none" stroke="currentColor" stroke-width="1.2"/><text x="5" y="11" font-size="7" font-weight="700" fill="currentColor">#</text></svg>
                            Page Number
                        </button>
                        <button type="button" class="caret" title="Page Number" @click="togglePara($event, 'pageNum')">▾</button>
                        <div v-if="paraMenu === 'pageNum'" class="word-menu para-menu hf-menu" :style="menuStyle()">
                            <p class="menu-label">Top of Page</p>
                            <button type="button" @click="placePageNumber('top', 'left', 'plain')">Plain Number, Left</button>
                            <button type="button" @click="placePageNumber('top', 'center', 'plain')">Plain Number, Center</button>
                            <button type="button" @click="placePageNumber('top', 'right', 'plain')">Plain Number, Right</button>
                            <button type="button" @click="placePageNumber('top', 'center', 'page')">Page X</button>
                            <button type="button" @click="placePageNumber('top', 'right', 'pageOf')">Page X of Y</button>
                            <p class="menu-label">Bottom of Page</p>
                            <button type="button" @click="placePageNumber('bottom', 'left', 'plain')">Plain Number, Left</button>
                            <button type="button" @click="placePageNumber('bottom', 'center', 'plain')">Plain Number, Center</button>
                            <button type="button" @click="placePageNumber('bottom', 'right', 'plain')">Plain Number, Right</button>
                            <button type="button" @click="placePageNumber('bottom', 'center', 'page')">Page X</button>
                            <button type="button" @click="placePageNumber('bottom', 'right', 'pageOf')">Page X of Y</button>
                            <p class="menu-label">Current Position</p>
                            <button type="button" @click="placePageNumber('free', 'left', 'plain')">Plain Number</button>
                            <button type="button" @click="placePageNumber('free', 'left', 'page')">Page X</button>
                            <button type="button" @click="placePageNumber('free', 'left', 'pageOf')">Page X of Y</button>
                            <button type="button" @click="removePageNumbers">Remove Page Numbers</button>
                        </div>
                    </div>
                </div>
                <span class="group-caption">Header &amp; Footer</span>
            </div>

            <div class="ribbon-group">
                <div class="setup-row">
                    <div class="menu-wrap">
                        <button type="button" class="setup-btn" :class="{ open: paraMenu === 'margins' }" @click="toggleSetup($event, 'margins')">
                            <svg viewBox="0 0 32 32"><rect x="6" y="3" width="20" height="26" fill="none" stroke="currentColor" stroke-width="1.4"/><rect x="10" y="7" width="12" height="18" fill="none" stroke="#2b579a" stroke-width="1.3"/></svg>
                            <span>Margins</span>
                            <span class="setup-caret">▾</span>
                        </button>
                        <div v-if="paraMenu === 'margins'" class="word-menu setup-menu" :style="menuStyle()">
                            <button v-for="item in marginChoices" :key="item.id" type="button" class="setup-choice" :class="{ picked: pageSetup.margin === item.id }" @click="applyMargin(item.id)">
                                <span class="margin-thumb" :style="marginThumb(item.id)"></span>
                                <span class="choice-copy"><strong>{{ item.label }}</strong><small>{{ item.detail }}</small></span>
                            </button>
                            <div class="setup-custom">
                                <p class="menu-label">Custom Margins</p>
                                <label>Top (in) <input v-model.number="customMargin.top" type="number" min="0" max="3" step="0.05" /></label>
                                <label>Bottom (in) <input v-model.number="customMargin.bottom" type="number" min="0" max="3" step="0.05" /></label>
                                <label>Left (in) <input v-model.number="customMargin.left" type="number" min="0" max="3" step="0.05" /></label>
                                <label>Right (in) <input v-model.number="customMargin.right" type="number" min="0" max="3" step="0.05" /></label>
                                <button type="button" class="apply" @click="applyCustomMargins">Apply</button>
                            </div>
                        </div>
                    </div>
                    <div class="menu-wrap">
                        <button type="button" class="setup-btn" :class="{ open: paraMenu === 'orientation' }" @click="toggleSetup($event, 'orientation')">
                            <svg viewBox="0 0 32 32"><rect x="6" y="5" width="11" height="16" fill="white" stroke="currentColor" stroke-width="1.3"/><rect x="13" y="12" width="14" height="11" fill="white" stroke="#2b579a" stroke-width="1.3"/></svg>
                            <span>Orientation</span>
                            <span class="setup-caret">▾</span>
                        </button>
                        <div v-if="paraMenu === 'orientation'" class="word-menu setup-menu" :style="menuStyle()">
                            <button type="button" class="setup-choice" :class="{ picked: pageSetup.orientation === 'portrait' }" @click="applyOrientation('portrait')">
                                <span class="orient-thumb portrait"></span>
                                <span class="choice-copy"><strong>Portrait</strong></span>
                            </button>
                            <button type="button" class="setup-choice" :class="{ picked: pageSetup.orientation === 'landscape' }" @click="applyOrientation('landscape')">
                                <span class="orient-thumb landscape"></span>
                                <span class="choice-copy"><strong>Landscape</strong></span>
                            </button>
                        </div>
                    </div>
                    <div class="menu-wrap">
                        <button type="button" class="setup-btn" :class="{ open: paraMenu === 'size' }" @click="toggleSetup($event, 'size')">
                            <svg viewBox="0 0 32 32"><rect x="7" y="3" width="13" height="18" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M23 6v12M23 6l-2 2M23 6l2 2M23 18l-2-2M23 18l2-2M7 26h13M7 26l2-2M7 26l2 2M20 26l-2-2M20 26l-2 2" fill="none" stroke="#2b579a" stroke-width="1.2"/></svg>
                            <span>Size</span>
                            <span class="setup-caret">▾</span>
                        </button>
                        <div v-if="paraMenu === 'size'" class="word-menu setup-menu" :style="menuStyle()">
                            <button v-for="item in paperSizes" :key="item.id" type="button" class="setup-choice" :class="{ picked: pageSetup.size === item.id }" @click="applySize(item.id)">
                                <span class="choice-copy"><strong>{{ item.label }}</strong><small>{{ item.detail }}</small></span>
                            </button>
                        </div>
                    </div>
                    <div class="menu-wrap">
                        <button type="button" class="setup-btn" :class="{ open: paraMenu === 'columns' }" @click="toggleSetup($event, 'columns')">
                            <svg viewBox="0 0 32 32"><rect x="5" y="4" width="22" height="24" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M12.3 4v24M19.7 4v24" stroke="currentColor"/><path d="M7 9h4M7 12h4M7 15h3M14 9h4M14 12h4M21.5 9h4M21.5 12h4" stroke="currentColor"/></svg>
                            <span>Columns</span>
                            <span class="setup-caret">▾</span>
                        </button>
                        <div v-if="paraMenu === 'columns'" class="word-menu setup-menu" :style="menuStyle()">
                            <button v-for="item in columnChoices" :key="item.id" type="button" class="setup-choice" :class="{ picked: pageSetup.columns === item.id }" @click="applyColumns(item.id)">
                                <span class="col-thumb" :class="item.cls"></span>
                                <span class="choice-copy"><strong>{{ item.label }}</strong></span>
                            </button>
                            <div class="setup-custom">
                                <p class="menu-label">More Columns</p>
                                <label>Number <input v-model.number="customColumns.count" type="number" min="1" max="4" step="1" /></label>
                                <label>Spacing (in) <input v-model.number="customColumns.gap" type="number" min="0" max="1" step="0.05" /></label>
                                <button type="button" class="apply" @click="applyCustomColumns">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
                <span class="group-caption">Page Setup</span>
            </div>
        </div>

        <div class="workspace">
            <div class="desk" :class="{ drawing: drawingTable }" @dragover.prevent @drop.prevent="onPaletteDrop">
                <div
                    ref="pageRef"
                    class="page"
                    :style="{ width: pageW() + 'px', height: pageH() + 'px' }"
                    @dragover.prevent
                    @drop.prevent.stop="onPaletteDrop"
                    @pointerdown="onPagePointerDown"
                >
                    <div v-if="rubber" class="rubber" :style="rubberStyle"></div>
                    <div
                        v-for="element in elements"
                        :key="element.id"
                        class="block"
                        :class="[element.type, { selected: selectedId === element.id && mode === 'design' }]"
                        :style="boxStyle(element)"
                        @pointerdown="onBlockPointerDown($event, element)"
                    >
                        <div v-if="isHeading(element)" class="heading-text" :class="element.type">
                            <textarea v-if="mode === 'design' && selectedId === element.id" v-model="element.text" @pointerdown.stop></textarea>
                            <div v-else class="copy">
                                <p v-for="(line, i) in textLines(element)" :key="i">
                                    <span v-if="listed(element)" class="marker">{{ marker(element, i) }}</span>{{ line }}<span v-if="showMarks" class="pilcrow">¶</span>
                                </p>
                            </div>
                        </div>

                        <div
                            v-else-if="element.type === 'logo'"
                            class="logo-box"
                            :class="{ empty: !element.image }"
                            @dragover.prevent
                            @drop.prevent="onLogoFileDrop($event, element)"
                        >
                            <img v-if="element.image" :src="imageSrc(element.image)" alt="" />
                            <span v-if="mode === 'design'" class="logo-hint" :class="{ show: !element.image }">{{ element.image ? 'Change image' : 'Insert image' }}</span>
                            <label v-if="mode === 'design'" class="logo-insert" @pointerdown.stop="selectedId = element.id">
                                <input type="file" accept="image/*" @change="uploadLogo($event, element)" />
                            </label>
                        </div>

                        <div v-else-if="element.type === 'line'" class="line-box"></div>

                        <div v-else-if="element.type === 'docHeader' || element.type === 'docFooter'" class="band">
                            <span v-if="mode === 'design'" class="band-tag">{{ element.type === 'docHeader' ? 'Header' : 'Footer' }}</span>
                            <div v-if="element.variant === 'columns'" class="band-cols">
                                <template v-if="mode === 'design'">
                                    <textarea v-model="element.parts.left" placeholder="Left" style="text-align:left" @pointerdown.stop="selectedId = element.id"></textarea>
                                    <textarea v-model="element.parts.center" placeholder="Center" style="text-align:center" @pointerdown.stop="selectedId = element.id"></textarea>
                                    <textarea v-model="element.parts.right" placeholder="Right" style="text-align:right" @pointerdown.stop="selectedId = element.id"></textarea>
                                </template>
                                <template v-else>
                                    <span>{{ element.parts.left }}</span>
                                    <span class="center">{{ element.parts.center }}</span>
                                    <span class="right">{{ element.parts.right }}</span>
                                </template>
                            </div>
                            <textarea v-else-if="mode === 'design'" v-model="element.text" :placeholder="element.type === 'docHeader' ? 'Header' : 'Footer'" @pointerdown.stop="selectedId = element.id"></textarea>
                            <div v-else class="copy"><p>{{ element.text }}</p></div>
                        </div>

                        <div v-else-if="element.type === 'pageNumber'" class="page-num" :style="{ justifyContent: element.align === 'center' ? 'center' : element.align === 'right' ? 'flex-end' : 'flex-start' }">
                            {{ pageLabel(element) }}
                        </div>

                        <div v-else-if="element.type === 'table'" class="sheet-table">
                            <div v-for="(row, r) in element.cells" :key="r" class="sheet-row" :style="{ height: heightsOf(element)[r] + 'px' }">
                                <div v-for="(cell, c) in row" :key="cell.id" class="sheet-cell" :class="{ active: selectedCell?.cellId === cell.id }" :style="cellBox(element, r, c)">
                                    <textarea
                                        v-if="mode === 'design'"
                                        v-model="cell.text"
                                        rows="1"
                                        @pointerdown.stop="selectTableCell(element, cell)"
                                    ></textarea>
                                    <button
                                        v-if="mode === 'design' && selectedCell?.cellId === cell.id"
                                        type="button"
                                        class="cell-remove"
                                        title="Remove cell"
                                        @pointerdown.stop
                                        @click.stop="removeSelectedCell"
                                    >×</button>
                                    <textarea
                                        v-else
                                        :value="answers[cell.id] || ''"
                                        :placeholder="cell.text"
                                        rows="1"
                                        @input="answers[cell.id] = $event.target.value"
                                        @pointerdown.stop
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="mode === 'design'" class="field-box fake">
                            <div class="copy">
                                <p v-for="(line, i) in textLines(element)" :key="i">
                                    <span v-if="listed(element)" class="marker">{{ marker(element, i) }}</span>{{ line || 'Field' }}<em v-if="element.required && i === textLines(element).length - 1">*</em><span v-if="showMarks" class="pilcrow">¶</span>
                                </p>
                            </div>
                            <div class="fake-control"></div>
                        </div>

                        <label v-else class="field-box">
                            <div class="copy">
                                <p v-for="(line, i) in textLines(element)" :key="i">
                                    <span v-if="listed(element)" class="marker">{{ marker(element, i) }}</span>{{ line || 'Field' }}<em v-if="element.required && i === textLines(element).length - 1">*</em><span v-if="showMarks" class="pilcrow">¶</span>
                                </p>
                            </div>
                            <textarea v-if="element.type === 'textarea'" :value="answers[element.id] || ''" @input="answers[element.id] = $event.target.value"></textarea>
                            <select v-else-if="element.type === 'select'" :value="answers[element.id] || ''" @change="answers[element.id] = $event.target.value">
                                <option value="">Select</option>
                                <option v-for="option in element.options" :key="option" :value="option">{{ option }}</option>
                            </select>
                            <input v-else-if="element.type === 'checkbox'" type="checkbox" :checked="!!answers[element.id]" @change="answers[element.id] = $event.target.checked" />
                            <input v-else :type="inputType(element.type)" :value="answers[element.id] || ''" @input="answers[element.id] = $event.target.value" />
                        </label>

                        <template v-if="mode === 'design' && selectedId === element.id">
                            <button v-if="canDrag(element)" type="button" class="move-bar" @pointerdown.stop="startMove($event, element)">Drag</button>
                            <span v-for="handle in resizeHandles(element)" :key="handle" class="handle" :class="handle" @pointerdown.stop="startResize($event, element, handle)"></span>
                            <template v-if="element.type === 'table'">
                                <template v-for="(row, r) in element.cells" :key="'cols-' + r">
                                    <span
                                        v-for="c in row.length - 1"
                                        :key="'col-' + r + '-' + c"
                                        class="grip col"
                                        :style="colGripStyle(element, r, c)"
                                        @pointerdown.stop="startGridResize($event, element, 'col', r, c - 1)"
                                    ></span>
                                </template>
                                <span
                                    v-for="r in element.cells.length - 1"
                                    :key="'row-' + r"
                                    class="grip row"
                                    :style="{ top: gridOffset(heightsOf(element), r) + 'px' }"
                                    @pointerdown.stop="startGridResize($event, element, 'row', 0, r - 1)"
                                ></span>
                            </template>
                        </template>
                    </div>
                </div>
            </div>

            <aside class="panel settings">
                <p v-if="drawingTable" class="hint">Drag on the page to draw the table.</p>
                <template v-if="selected && mode === 'design'">
                    <p class="palette-label">{{ selectionTitle(selected) }}</p>
                    <label v-if="!['table', 'line', 'docHeader', 'docFooter', 'pageNumber'].includes(selected.type)" class="setting">
                        {{ isHeading(selected) ? 'Text' : 'Field name' }}
                        <input v-model="selected.text" />
                    </label>
                    <p class="size-readout">{{ Math.round(selected.w) }} × {{ Math.round(selected.h) }} px</p>
                    <div v-if="selected.type === 'table'" class="row-actions">
                        <button type="button" @click="addTableRow">Add row</button>
                        <button type="button" @click="addTableColumn">Add column</button>
                        <button v-if="selectedCell?.tableId === selected.id" type="button" class="danger" @click="removeSelectedCell">Remove cell</button>
                    </div>
                    <label v-if="isInput(selected)" class="check">
                        <input v-model="selected.required" type="checkbox" />
                        Required
                    </label>
                    <div v-if="selected.type === 'select'" class="setting">
                        Choices, one per line
                        <textarea v-model="optionText" rows="5" @change="applyOptions"></textarea>
                    </div>
                    <div v-if="selected.type === 'logo'" class="logo-actions">
                        <button type="button" @click="selected.image = '/images/sidelogo.png'">Use office seal</button>
                        <label class="upload">
                            Insert image
                            <input type="file" accept="image/*" @change="uploadLogo" />
                        </label>
                    </div>
                    <button type="button" class="danger" @click="removeSelected">Remove</button>
                </template>
                <p v-else-if="mode === 'fill'" class="hint">Type in the fields on the page, then save the answers.</p>
                <p v-else-if="!drawingTable" class="hint">Use the ribbon to insert a header, field, or table. Drag a box to move it, and pull its edges to resize it.</p>
                <button v-if="mode === 'fill'" type="button" class="primary wide" :disabled="saving" @click="saveEntry">Save answers</button>
            </aside>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from '@/composables/useToast';

const SIZES = {
    letter: { w: 816, h: 1056 },
    legal: { w: 816, h: 1344 },
    folio: { w: 816, h: 1248 },
    a4: { w: 794, h: 1123 },
    a5: { w: 559, h: 794 },
    executive: { w: 696, h: 1008 },
    tabloid: { w: 1056, h: 1632 },
};
const MARGIN_PRESETS = {
    normal: { top: 96, right: 96, bottom: 96, left: 96 },
    narrow: { top: 48, right: 48, bottom: 48, left: 48 },
    moderate: { top: 96, right: 72, bottom: 96, left: 72 },
    wide: { top: 96, right: 192, bottom: 96, left: 192 },
    mirrored: { top: 96, right: 96, bottom: 96, left: 120 },
    office: { top: 96, right: 120, bottom: 96, left: 120 },
};
const paperSizes = [
    { id: 'letter', label: 'Letter', detail: '8.5" × 11"' },
    { id: 'legal', label: 'Legal', detail: '8.5" × 14"' },
    { id: 'folio', label: 'Long bond', detail: '8.5" × 13"' },
    { id: 'a4', label: 'A4', detail: '8.27" × 11.69"' },
    { id: 'a5', label: 'A5', detail: '5.83" × 8.27"' },
    { id: 'executive', label: 'Executive', detail: '7.25" × 10.5"' },
    { id: 'tabloid', label: 'Tabloid', detail: '11" × 17"' },
];
const marginChoices = [
    { id: 'normal', label: 'Normal', detail: 'Top: 1"   Bottom: 1"   Left: 1"   Right: 1"' },
    { id: 'narrow', label: 'Narrow', detail: 'Top: 0.5"   Bottom: 0.5"   Left: 0.5"   Right: 0.5"' },
    { id: 'moderate', label: 'Moderate', detail: 'Top: 1"   Bottom: 1"   Left: 0.75"   Right: 0.75"' },
    { id: 'wide', label: 'Wide', detail: 'Top: 1"   Bottom: 1"   Left: 2"   Right: 2"' },
    { id: 'mirrored', label: 'Mirrored', detail: 'Top: 1"   Bottom: 1"   Inside: 1.25"   Outside: 1"' },
    { id: 'office', label: 'Office 2003 Default', detail: 'Top: 1"   Bottom: 1"   Left: 1.25"   Right: 1.25"' },
];
const columnChoices = [
    { id: 'one', label: 'One', cls: 'cols-1' },
    { id: 'two', label: 'Two', cls: 'cols-2' },
    { id: 'three', label: 'Three', cls: 'cols-3' },
    { id: 'left', label: 'Left', cls: 'cols-left' },
    { id: 'right', label: 'Right', cls: 'cols-right' },
];

function defaultPage() {
    return {
        size: 'a4',
        orientation: 'portrait',
        margin: 'normal',
        margins: { ...MARGIN_PRESETS.normal },
        columns: 'one',
        columnCount: 1,
        columnGap: 48,
    };
}

const pageSetup = reactive(defaultPage());
const customMargin = reactive({ top: 1, right: 1, bottom: 1, left: 1 });
const customColumns = reactive({ count: 2, gap: 0.5 });

function pageW() {
    const size = SIZES[pageSetup.size] || SIZES.a4;
    return pageSetup.orientation === 'landscape' ? size.h : size.w;
}

function pageH() {
    const size = SIZES[pageSetup.size] || SIZES.a4;
    return pageSetup.orientation === 'landscape' ? size.w : size.h;
}
const handles = ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'];
const fonts = ['Times New Roman', 'Arial', 'Calibri', 'Georgia', 'Courier New'];
const fontSizes = [8, 9, 10, 11, 12, 14, 16, 18, 20, 24, 28, 36];
const lineHeights = [1, 1.15, 1.5, 2, 2.5, 3];
const shadeSwatches = ['#ffff00', '#c6efce', '#9dc3e6', '#f8cbad', '#f4b183', '#d9d9d9', '#c5a059', '#1a3557', '#ffffff', '#000000'];

const tabs = [
    { id: 'tax_declaration', label: 'Tax Declaration' },
    { id: 'field_appraisal', label: 'Field Appraisal' },
];

const palette = [
    { type: 'header', label: 'Header', icon: 'pi-bookmark' },
    { type: 'subheader', label: 'Subheader', icon: 'pi-minus' },
    { type: 'logo', label: 'Logo', icon: 'pi-image' },
    { type: 'text', label: 'Text field', icon: 'pi-align-left' },
    { type: 'textarea', label: 'Long text', icon: 'pi-align-justify' },
    { type: 'number', label: 'Number', icon: 'pi-hashtag' },
    { type: 'date', label: 'Date', icon: 'pi-calendar' },
    { type: 'checkbox', label: 'Yes / No', icon: 'pi-check-square' },
    { type: 'select', label: 'Dropdown', icon: 'pi-list' },
];

const borderMenu = [
    { id: 'bottom', label: 'Bottom Border' },
    { id: 'top', label: 'Top Border' },
    { id: 'left', label: 'Left Border' },
    { id: 'right', label: 'Right Border' },
    { id: 'none', label: 'No Border' },
    { id: 'all', label: 'All Borders' },
    { id: 'outside', label: 'Outside Borders' },
    { id: 'inside', label: 'Inside Borders' },
    { id: 'insideH', label: 'Inside Horizontal Border' },
    { id: 'insideV', label: 'Inside Vertical Border' },
    { id: 'diagonalDown', label: 'Diagonal Down Border' },
    { id: 'diagonalUp', label: 'Diagonal Up Border' },
    { id: 'line', label: 'Horizontal Line' },
    { id: 'draw', label: 'Draw Table' },
    { id: 'gridlines', label: 'View Gridlines' },
    { id: 'shading', label: 'Borders and Shading...' },
];

const toast = useToast();
const route = useRoute();
const router = useRouter();
const pageRef = ref(null);
const target = ref('tax_declaration');
const layouts = ref([]);
const layoutId = ref(null);
const formName = ref('Untitled form');
const elements = ref([]);
const selectedId = ref(null);
const selectedCell = ref(null);
const answers = ref({});
const mode = ref('design');
const saving = ref(false);
const optionText = ref('');
const paraMenu = ref(null);
const menuAnchor = ref(null);
const showMarks = ref(false);
const tableMenuOpen = ref(false);
const shadingOpen = ref(false);
const shadeColor = ref('#000000');
const shadeWidth = ref(1);
const hoverCell = ref({ r: 1, c: 1 });
const drawingTable = ref(false);
const rubber = ref(null);
let paletteType = null;
let gesture = null;

const selected = computed(() => elements.value.find((element) => element.id === selectedId.value) || null);
const canList = computed(() => !!selected.value && !['logo', 'line', 'table', 'docHeader', 'docFooter', 'pageNumber'].includes(selected.value.type));
const rubberStyle = computed(() => rubber.value ? {
    left: `${rubber.value.x}px`,
    top: `${rubber.value.y}px`,
    width: `${rubber.value.w}px`,
    height: `${rubber.value.h}px`,
} : {});

watch(selected, (element) => {
    optionText.value = element?.type === 'select' ? (element.options || []).join('\n') : '';
    if (element) {
        shadeColor.value = element.borderColor || '#000000';
        shadeWidth.value = element.borderWidth || 1;
    }
});

onMounted(async () => {
    await loadList();
    await applyRoute();
});
watch(() => `${route.name}:${route.params.id || ''}`, async (next, prev) => {
    if (prev === undefined) return;
    await applyRoute();
});
onBeforeUnmount(endGesture);

function blankBorder() {
    return { top: false, right: false, bottom: false, left: false, insideH: false, insideV: false, diagonalDown: false, diagonalUp: false };
}

function styleDefaults(extra = {}) {
    return {
        fontFamily: 'Times New Roman',
        fontSize: 11,
        bold: false,
        italic: false,
        underline: false,
        align: 'left',
        listType: 'none',
        listStyle: 'none',
        indent: 0,
        lineHeight: 1.15,
        spaceBefore: 0,
        spaceAfter: 0,
        shading: null,
        variant: 'blank',
        parts: { left: '', center: '', right: '' },
        numberFormat: 'plain',
        anchor: 'free',
        border: blankBorder(),
        borderColor: '#000000',
        borderWidth: 1,
        gridlines: true,
        cells: [],
        ...extra,
    };
}

async function loadList() {
    const { data } = await axios.get('form-layouts', { params: { target: target.value } });
    layouts.value = data;
}

async function switchTarget(next) {
    if (next === target.value) return;
    target.value = next;
    startNew();
    await loadList();
}

function enterFill() {
    selectedId.value = null;
    selectedCell.value = null;
    drawingTable.value = false;
    mode.value = 'fill';
}

function startNew() {
    layoutId.value = null;
    formName.value = 'Untitled form';
    elements.value = [];
    answers.value = {};
    selectedId.value = null;
    selectedCell.value = null;
    mode.value = 'design';
    drawingTable.value = false;
    loadPage(null);
    if (route.name !== 'form-layout-new') router.push({ name: 'form-layout-new' });
}

function onPickForm(id) {
    if (!id) {
        startNew();
        return;
    }
    router.push({ name: 'form-layout-edit', params: { id: String(id) } });
}

function goToList() {
    router.push({ name: 'form-layouts' });
}

async function applyRoute() {
    const id = route.params.id;
    if (!id) {
        layoutId.value = null;
        formName.value = 'Untitled form';
        elements.value = [];
        answers.value = {};
        selectedId.value = null;
        selectedCell.value = null;
        mode.value = 'design';
        drawingTable.value = false;
        loadPage(null);
        return;
    }
    if (String(layoutId.value) !== String(id)) await openLayout(id);
}

async function openLayout(id) {
    try {
        const { data } = await axios.get(`form-layouts/${id}`);
        layoutId.value = data.id;
        formName.value = data.name;
        target.value = data.target || target.value;
        elements.value = Array.isArray(data.fields) ? data.fields.map(normalize) : [];
        loadPage(data.page);
        answers.value = {};
        selectedId.value = null;
        mode.value = 'design';
        await loadList();
    } catch (err) {
        toast.apiError(err, 'Could not open the form');
        router.push({ name: 'form-layouts' });
    }
}

function normalize(element) {
    return {
        ...styleDefaults(),
        id: element.id,
        type: element.type,
        x: Number(element.x) || 0,
        y: Number(element.y) || 0,
        w: Number(element.w) || 180,
        h: Number(element.h) || 48,
        text: element.text || '',
        image: element.image || null,
        required: !!element.required,
        options: Array.isArray(element.options) ? element.options : [],
        fontFamily: element.fontFamily || 'Times New Roman',
        fontSize: Number(element.fontSize) || 11,
        bold: !!element.bold,
        italic: !!element.italic,
        underline: !!element.underline,
        align: ['left', 'center', 'right', 'justify'].includes(element.align) ? element.align : 'left',
        listType: ['bullet', 'number', 'multi'].includes(element.listType) ? element.listType : 'none',
        listStyle: element.listStyle || 'none',
        indent: Number(element.indent) || 0,
        lineHeight: Number(element.lineHeight) || 1.15,
        spaceBefore: Number(element.spaceBefore) || 0,
        spaceAfter: Number(element.spaceAfter) || 0,
        shading: element.shading || null,
        variant: element.variant === 'columns' ? 'columns' : 'blank',
        parts: {
            left: element.parts?.left || '',
            center: element.parts?.center || '',
            right: element.parts?.right || '',
        },
        numberFormat: ['plain', 'page', 'pageOf'].includes(element.numberFormat) ? element.numberFormat : 'plain',
        anchor: ['top', 'bottom', 'free'].includes(element.anchor) ? element.anchor : 'free',
        border: { ...blankBorder(), ...(element.border || {}) },
        borderColor: element.borderColor || '#000000',
        borderWidth: Number(element.borderWidth) || 1,
        gridlines: element.gridlines !== false,
        cells: normalizeCells(element),
        colWidths: [],
        rowHeights: fitGrid(element.rowHeights, element.cells?.length || 0, Number(element.h) || 48, 22),
    };
}

function addElement(type, x = null, y = null, size = null) {
    const presets = {
        header: { w: 460, h: 56, text: 'Header', fontSize: 28, bold: true },
        subheader: { w: 360, h: 40, text: 'Subheader', fontSize: 18, bold: true },
        logo: { w: 120, h: 120, text: 'Logo' },
        text: { w: 240, h: 64, text: 'Text field' },
        textarea: { w: 320, h: 120, text: 'Long text' },
        number: { w: 180, h: 64, text: 'Number' },
        date: { w: 200, h: 64, text: 'Date' },
        checkbox: { w: 180, h: 36, text: 'Yes / No' },
        select: { w: 240, h: 64, text: 'Dropdown' },
        line: { w: 460, h: 8, text: '' },
        table: { w: 420, h: 140, text: 'Table' },
        docHeader: { w: pageW(), h: 72, text: '' },
        docFooter: { w: pageW(), h: 72, text: '' },
        pageNumber: { w: 140, h: 28, text: '' },
    };
    const preset = { ...presets[type], ...(size || {}) };
    let left = x;
    let top = y;
    if (left == null && type !== 'docHeader' && type !== 'docFooter') {
        const box = contentBox();
        preset.w = Math.min(preset.w, Math.max(48, box.w));
        preset.h = Math.min(preset.h, Math.max(type === 'line' ? 8 : 28, box.h));
        left = box.x;
        top = Math.min(box.y + (elements.value.length % 6) * 24, Math.max(0, pageH() - preset.h));
    }
    const element = {
        ...styleDefaults({ fontSize: preset.fontSize || 11, bold: !!preset.bold }),
        id: `f_${Math.random().toString(36).slice(2, 10)}`,
        type,
        x: left == null ? 48 : Math.max(0, Math.min(pageW() - preset.w, left)),
        y: top == null ? 48 : Math.max(0, Math.min(pageH() - preset.h, top)),
        w: preset.w,
        h: preset.h,
        text: preset.text,
        image: null,
        required: false,
        options: type === 'select' ? ['Option 1', 'Option 2'] : [],
        border: type === 'line'
            ? { ...blankBorder(), bottom: true }
            : type === 'table'
                ? { ...blankBorder(), top: true, right: true, bottom: true, left: true, insideH: true, insideV: true }
                : blankBorder(),
        cells: type === 'table' ? makeCells(preset.rows || 3, preset.cols || 3) : [],
    };
    if (type === 'table') ensureGrid(element);
    elements.value.push(element);
    selectedId.value = element.id;
    mode.value = 'design';
    return element;
}

function makeCells(rows, cols) {
    return Array.from({ length: rows }, () => Array.from({ length: cols }, () => ({
        id: `c_${Math.random().toString(36).slice(2, 10)}`,
        text: '',
    })));
}

function cellOf(n) {
    return { r: Math.ceil(n / 10), c: ((n - 1) % 10) + 1 };
}

function insertTable(rows, cols, x = null, y = null, w = null, h = null) {
    addElement('table', x, y, { w: w || Math.min(520, 80 * cols), h: h || Math.min(360, 32 * rows), rows, cols });
    tableMenuOpen.value = false;
    drawingTable.value = false;
}

function addTableRow() {
    const table = selected.value;
    if (!table || table.type !== 'table' || table.cells.length >= 10) return;
    const cols = table.cells[0]?.length || 1;
    const sample = table.cells[table.cells.length - 1];
    table.cells.push(makeCells(1, cols)[0].map((cell, c) => ({ ...cell, w: Number(sample?.[c]?.w) || table.w / cols })));
    ensureGrid(table);
    table.rowHeights[table.rowHeights.length - 1] = 28;
    table.h = table.rowHeights.reduce((sum, height) => sum + height, 0);
}

function addTableColumn() {
    const table = selected.value;
    if (!table || table.type !== 'table' || (table.cells[0]?.length || 0) >= 10) return;
    table.cells.forEach((row) => {
        const cell = makeCells(1, 1)[0][0];
        cell.w = 80;
        row.push(cell);
    });
    table.w = Math.min(pageW() - table.x, table.w + 80);
    ensureGrid(table);
}

function onPaletteDragStart(type, event) {
    paletteType = type;
    event.dataTransfer.effectAllowed = 'copy';
    event.dataTransfer.setData('text/plain', type);
}

function onPaletteDrop(event) {
    if (!paletteType || !pageRef.value) return;
    const point = pagePoint(event);
    addElement(paletteType, point.x - 40, point.y - 20);
    paletteType = null;
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
        zIndex: element.type === 'pageNumber'
            ? (selectedId.value === element.id ? 7 : 5)
            : (selectedId.value === element.id ? 4 : 1),
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
        ...boxBorder,
    };
}

function cellStyle(element, r, c) {
    const border = element.border || {};
    const line = `${element.borderWidth || 1}px solid ${element.borderColor || '#000'}`;
    const guide = element.gridlines ? '1px dotted #b5b5b5' : '1px solid transparent';
    const rows = element.cells.length;
    const cols = element.cells[r]?.length || 1;
    const diagonals = [];
    if (border.diagonalDown) diagonals.push(`linear-gradient(to bottom right, transparent calc(50% - .6px), ${element.borderColor || '#000'} calc(50% - .6px), ${element.borderColor || '#000'} calc(50% + .6px), transparent calc(50% + .6px))`);
    if (border.diagonalUp) diagonals.push(`linear-gradient(to top right, transparent calc(50% - .6px), ${element.borderColor || '#000'} calc(50% - .6px), ${element.borderColor || '#000'} calc(50% + .6px), transparent calc(50% + .6px))`);
    return {
        borderTop: (r === 0 ? border.top : border.insideH) ? line : guide,
        borderLeft: (c === 0 ? border.left : border.insideV) ? line : guide,
        borderRight: c === cols - 1 ? (border.right ? line : guide) : 'none',
        borderBottom: r === rows - 1 ? (border.bottom ? line : guide) : 'none',
        backgroundColor: element.shading || 'transparent',
        backgroundImage: diagonals.join(', ') || 'none',
    };
}

function closeMenus() {
    paraMenu.value = null;
    tableMenuOpen.value = false;
    shadingOpen.value = false;
    menuAnchor.value = null;
}

function placeMenu(event, width = 280) {
    const opener = event?.currentTarget?.closest?.('.split, .menu-wrap') || event?.currentTarget;
    if (!opener) return;
    const rect = opener.getBoundingClientRect();
    let left = rect.left;
    if (left + width > window.innerWidth - 12) left = Math.max(8, window.innerWidth - width - 12);
    menuAnchor.value = { top: rect.bottom + 4, left };
}

function menuStyle() {
    if (!menuAnchor.value) return {};
    return {
        position: 'fixed',
        top: `${menuAnchor.value.top}px`,
        left: `${menuAnchor.value.left}px`,
        right: 'auto',
        zIndex: 80,
    };
}

function togglePara(event, name) {
    tableMenuOpen.value = false;
    if (paraMenu.value === name) {
        paraMenu.value = null;
        shadingOpen.value = false;
        menuAnchor.value = null;
        return;
    }
    placeMenu(event);
    paraMenu.value = name;
    if (name !== 'borders') shadingOpen.value = false;
}

function toggleSetup(event, name) {
    tableMenuOpen.value = false;
    shadingOpen.value = false;
    if (paraMenu.value === name) {
        paraMenu.value = null;
        menuAnchor.value = null;
        return;
    }
    if (name === 'margins') syncCustomMargins();
    if (name === 'columns') syncCustomColumns();
    placeMenu(event, 360);
    paraMenu.value = name;
}

function columnRects() {
    const margin = pageSetup.margins;
    const innerW = Math.max(48, pageW() - margin.left - margin.right);
    const innerH = Math.max(48, pageH() - margin.top - margin.bottom);
    const count = clamp(pageSetup.columnCount || 1, 1, 4);
    const gap = count > 1 ? Math.min(pageSetup.columnGap, innerW / (count * 2)) : 0;
    const usable = innerW - gap * (count - 1);
    let ratios;
    if (pageSetup.columns === 'left' && count === 2) ratios = [0.32, 0.68];
    else if (pageSetup.columns === 'right' && count === 2) ratios = [0.68, 0.32];
    else ratios = Array.from({ length: count }, () => 1 / count);
    let x = margin.left;
    return ratios.map((ratio) => {
        const width = usable * ratio;
        const rect = { x, y: margin.top, w: width, h: innerH };
        x += width + gap;
        return rect;
    });
}

function contentBox() {
    return columnRects()[0] || { x: 48, y: 48, w: Math.max(72, pageW() - 96), h: Math.max(72, pageH() - 96) };
}

function pxToInches(px) {
    return Math.round((Number(px) / 96) * 100) / 100;
}

function inchesToPx(value) {
    const inches = Number(value);
    if (!Number.isFinite(inches)) return 0;
    return Math.round(clamp(inches, 0, 3) * 96 * 10) / 10;
}

function clampMargins() {
    const maxX = Math.max(0, (pageW() - 72) / 2);
    const maxY = Math.max(0, (pageH() - 72) / 2);
    pageSetup.margins.top = clamp(pageSetup.margins.top, 0, maxY);
    pageSetup.margins.bottom = clamp(pageSetup.margins.bottom, 0, maxY);
    pageSetup.margins.left = clamp(pageSetup.margins.left, 0, maxX);
    pageSetup.margins.right = clamp(pageSetup.margins.right, 0, maxX);
}

function normalizePage(page) {
    const next = defaultPage();
    if (!page || typeof page !== 'object') return next;
    if (SIZES[page.size]) next.size = page.size;
    if (page.orientation === 'landscape' || page.orientation === 'portrait') next.orientation = page.orientation;
    const names = ['normal', 'narrow', 'moderate', 'wide', 'mirrored', 'office', 'custom'];
    if (names.includes(page.margin)) next.margin = page.margin;
    if (page.margins && typeof page.margins === 'object') {
        next.margins = {
            top: Number(page.margins.top) || 0,
            right: Number(page.margins.right) || 0,
            bottom: Number(page.margins.bottom) || 0,
            left: Number(page.margins.left) || 0,
        };
    } else if (MARGIN_PRESETS[next.margin]) {
        next.margins = { ...MARGIN_PRESETS[next.margin] };
    }
    const counts = { one: 1, two: 2, three: 3, left: 2, right: 2 };
    if (['one', 'two', 'three', 'left', 'right', 'custom'].includes(page.columns)) next.columns = page.columns;
    next.columnCount = clamp(Number(page.columnCount) || counts[next.columns] || 1, 1, 4);
    next.columnGap = clamp(Number(page.columnGap ?? 48), 0, 96);
    return next;
}

function loadPage(page) {
    const next = normalizePage(page);
    pageSetup.size = next.size;
    pageSetup.orientation = next.orientation;
    pageSetup.margin = next.margin;
    pageSetup.margins = next.margins;
    pageSetup.columns = next.columns;
    pageSetup.columnCount = next.columnCount;
    pageSetup.columnGap = next.columnGap;
}

function syncCustomMargins() {
    customMargin.top = pxToInches(pageSetup.margins.top);
    customMargin.right = pxToInches(pageSetup.margins.right);
    customMargin.bottom = pxToInches(pageSetup.margins.bottom);
    customMargin.left = pxToInches(pageSetup.margins.left);
}

function syncCustomColumns() {
    customColumns.count = pageSetup.columnCount;
    customColumns.gap = pxToInches(pageSetup.columnGap);
}

function marginThumb(id) {
    const margin = MARGIN_PRESETS[id] || MARGIN_PRESETS.normal;
    const edge = (px) => `${Math.max(2, Math.round(px / 28))}px`;
    return {
        borderTopWidth: edge(margin.top),
        borderRightWidth: edge(margin.right),
        borderBottomWidth: edge(margin.bottom),
        borderLeftWidth: edge(margin.left),
    };
}

function repositionPageNumber(element) {
    const width = element.w;
    const height = element.h;
    element.x = element.align === 'center' ? (pageW() - width) / 2 : element.align === 'right' ? pageW() - width - 36 : 36;
    if (element.anchor === 'top') {
        const header = elements.value.find((item) => item.type === 'docHeader');
        element.y = Math.max(8, ((header?.h || 72) - height) / 2);
    }
    if (element.anchor === 'bottom') {
        const footer = elements.value.find((item) => item.type === 'docFooter');
        element.y = (footer?.y || pageH() - 72) + Math.max(8, ((footer?.h || 72) - height) / 2);
    }
    element.x = clamp(element.x, 0, Math.max(0, pageW() - width));
    element.y = clamp(element.y, 0, Math.max(0, pageH() - height));
}

function fitElementsToPage() {
    elements.value.forEach((element) => {
        if (element.type === 'docHeader' || element.type === 'docFooter') pinBand(element);
    });
    elements.value.forEach((element) => {
        if (element.type === 'docHeader' || element.type === 'docFooter') return;
        if (element.type === 'pageNumber' && element.anchor !== 'free') {
            repositionPageNumber(element);
            return;
        }
        const minW = element.type === 'pageNumber' ? 36 : element.type === 'line' ? 40 : 48;
        const minH = element.type === 'line' ? 4 : element.type === 'pageNumber' ? 20 : 24;
        element.w = Math.min(Math.max(element.w, minW), pageW());
        element.h = Math.min(Math.max(element.h, minH), pageH());
        element.x = clamp(element.x, 0, Math.max(0, pageW() - element.w));
        element.y = clamp(element.y, 0, Math.max(0, pageH() - element.h));
        if (element.type === 'table') ensureGrid(element);
    });
}

function applyMargin(id) {
    const preset = MARGIN_PRESETS[id];
    if (!preset) return;
    pageSetup.margin = id;
    pageSetup.margins = { ...preset };
    clampMargins();
    paraMenu.value = null;
}

function applyCustomMargins() {
    pageSetup.margin = 'custom';
    pageSetup.margins = {
        top: inchesToPx(customMargin.top),
        right: inchesToPx(customMargin.right),
        bottom: inchesToPx(customMargin.bottom),
        left: inchesToPx(customMargin.left),
    };
    clampMargins();
    paraMenu.value = null;
}

function applyOrientation(orientation) {
    pageSetup.orientation = orientation === 'landscape' ? 'landscape' : 'portrait';
    clampMargins();
    fitElementsToPage();
    paraMenu.value = null;
}

function applySize(size) {
    if (!SIZES[size]) return;
    pageSetup.size = size;
    clampMargins();
    fitElementsToPage();
    paraMenu.value = null;
}

function applyColumns(id) {
    const counts = { one: 1, two: 2, three: 3, left: 2, right: 2 };
    if (!counts[id]) return;
    pageSetup.columns = id;
    pageSetup.columnCount = counts[id];
    pageSetup.columnGap = 48;
    paraMenu.value = null;
}

function applyCustomColumns() {
    pageSetup.columns = 'custom';
    pageSetup.columnCount = clamp(Math.round(Number(customColumns.count) || 1), 1, 4);
    pageSetup.columnGap = clamp(inchesToPx(customColumns.gap), 0, 96);
    paraMenu.value = null;
}

function toggleTable(event) {
    paraMenu.value = null;
    shadingOpen.value = false;
    if (tableMenuOpen.value) {
        tableMenuOpen.value = false;
        menuAnchor.value = null;
        return;
    }
    placeMenu(event);
    tableMenuOpen.value = true;
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
    if (element.listType === 'bullet') {
        return { disc: '•', circle: '◦', square: '▪' }[element.listStyle] || '•';
    }
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

function applyList(type, style) {
    if (!canList.value) {
        toast.info('Select text', 'Click a header or text box, then choose a list.');
        return;
    }
    selected.value.listType = type;
    selected.value.listStyle = style;
    paraMenu.value = null;
}

function toggleList(type, style) {
    if (!canList.value) {
        toast.info('Select text', 'Click a header or text box, then choose a list.');
        return;
    }
    if (selected.value.listType === type) {
        selected.value.listType = 'none';
        selected.value.listStyle = 'none';
        return;
    }
    selected.value.listType = type;
    selected.value.listStyle = style;
}

function changeIndent(delta) {
    if (!selected.value || selected.value.type === 'line') return;
    selected.value.indent = Math.max(0, Math.min(8, (Number(selected.value.indent) || 0) + delta));
}

function changeSpacing(key, add) {
    if (!selected.value) return;
    const current = Number(selected.value[key]) || 0;
    selected.value[key] = add ? Math.min(36, current + 12) : 0;
    paraMenu.value = null;
}

function setShading(color, close = true) {
    if (!selected.value) return;
    selected.value.shading = color;
    if (close) paraMenu.value = null;
}

function sortSelected(dir) {
    const element = selected.value;
    if (!element) return;
    const factor = dir === 'desc' ? -1 : 1;
    const compare = (a, b) => String(a).localeCompare(String(b), undefined, { sensitivity: 'base' }) * factor;
    if (element.type === 'table') {
        element.cells = [...element.cells].sort((a, b) => compare(a[0]?.text || '', b[0]?.text || ''));
    } else if (!['logo', 'line'].includes(element.type)) {
        const lines = String(element.text || '').split('\n');
        element.text = lines.sort(compare).join('\n');
    }
    paraMenu.value = null;
}

function setStyle(key, value) {
    if (!selected.value) return;
    selected.value[key] = value;
}

function toggleStyle(key) {
    if (!selected.value) return;
    selected.value[key] = !selected.value[key];
}

function applyBorder(action) {
    if (action === 'line') {
        addElement('line');
        paraMenu.value = null;
        return;
    }
    if (action === 'draw') {
        drawingTable.value = true;
        paraMenu.value = null;
        toast.info('Draw table', 'Drag on the page to set the table size.');
        return;
    }
    if (action === 'shading') {
        shadingOpen.value = !shadingOpen.value;
        return;
    }
    if (action === 'gridlines') {
        const tables = selected.value?.type === 'table' ? [selected.value] : elements.value.filter((element) => element.type === 'table');
        if (!tables.length) {
            toast.info('View gridlines', 'Insert a table first.');
            return;
        }
        const next = !tables[0].gridlines;
        tables.forEach((table) => { table.gridlines = next; });
        paraMenu.value = null;
        return;
    }
    if (!selected.value) {
        toast.info('Select an item', 'Click a box or table, then choose a border.');
        return;
    }
    const border = selected.value.border || blankBorder();
    selected.value.border = border;
    if (action === 'none') Object.keys(border).forEach((key) => { border[key] = false; });
    else if (action === 'all') {
        border.top = border.right = border.bottom = border.left = border.insideH = border.insideV = true;
    } else if (action === 'outside') {
        border.top = border.right = border.bottom = border.left = true;
        border.insideH = border.insideV = false;
    } else if (action === 'inside') {
        border.insideH = border.insideV = true;
    } else if (action === 'insideH') border.insideH = !border.insideH;
    else if (action === 'insideV') border.insideV = !border.insideV;
    else border[action] = !border[action];
    paraMenu.value = null;
}

function paintBorder() {
    if (!selected.value) return;
    selected.value.borderColor = shadeColor.value;
    selected.value.borderWidth = shadeWidth.value;
}

function isHeading(element) {
    return element.type === 'header' || element.type === 'subheader';
}

function selectionTitle(element) {
    if (element.type === 'table') return 'Table';
    if (element.type === 'docHeader') return 'Header';
    if (element.type === 'docFooter') return 'Footer';
    if (element.type === 'pageNumber') return 'Page Number';
    return 'Selected box';
}

function canDrag(element) {
    if (element.type === 'docHeader' || element.type === 'docFooter') return false;
    if (element.type === 'pageNumber' && element.anchor !== 'free') return false;
    return true;
}

function resizeHandles(element) {
    if (element.type === 'docHeader') return ['s'];
    if (element.type === 'docFooter') return ['n'];
    if (element.type === 'pageNumber' && element.anchor !== 'free') return [];
    return handles;
}

function pinBand(band) {
    band.x = 0;
    band.w = pageW();
    band.h = clamp(band.h || 72, 36, 180);
    band.y = band.type === 'docHeader' ? 0 : pageH() - band.h;
}

function setBand(type, variant) {
    let band = elements.value.find((element) => element.type === type);
    if (!band) {
        const y = type === 'docHeader' ? 0 : pageH() - 72;
        band = addElement(type, 0, y, { w: pageW(), h: 72 });
    }
    band.variant = variant;
    band.parts = band.parts || { left: '', center: '', right: '' };
    pinBand(band);
    selectedId.value = band.id;
    paraMenu.value = null;
}

function removeBand(type) {
    const band = elements.value.find((element) => element.type === type);
    elements.value = elements.value.filter((element) => element.type !== type);
    if (band && selectedId.value === band.id) selectedId.value = null;
    paraMenu.value = null;
}

function pageLabel(element) {
    if (element.numberFormat === 'page') return 'Page 1';
    if (element.numberFormat === 'pageOf') return 'Page 1 of 1';
    return '1';
}

function placePageNumber(anchor, align, format) {
    elements.value = elements.value.filter((element) => element.type !== 'pageNumber');
    if (anchor === 'top' && !elements.value.some((element) => element.type === 'docHeader')) setBand('docHeader', 'blank');
    if (anchor === 'bottom' && !elements.value.some((element) => element.type === 'docFooter')) setBand('docFooter', 'blank');
    const w = format === 'plain' ? 48 : 150;
    const h = 28;
    const x = align === 'center' ? (pageW() - w) / 2 : align === 'right' ? pageW() - w - 36 : 36;
    let y = 160;
    if (anchor === 'top') {
        const header = elements.value.find((element) => element.type === 'docHeader');
        y = Math.max(8, ((header?.h || 72) - h) / 2);
    }
    if (anchor === 'bottom') {
        const footer = elements.value.find((element) => element.type === 'docFooter');
        y = (footer?.y || pageH() - 72) + Math.max(8, ((footer?.h || 72) - h) / 2);
    }
    const element = addElement('pageNumber', x, y, { w, h });
    element.align = align;
    element.numberFormat = format;
    element.anchor = anchor;
    element.x = x;
    element.y = y;
    element.w = w;
    element.h = h;
    paraMenu.value = null;
}

function removePageNumbers() {
    const ids = elements.value.filter((element) => element.type === 'pageNumber').map((element) => element.id);
    elements.value = elements.value.filter((element) => element.type !== 'pageNumber');
    if (ids.includes(selectedId.value)) selectedId.value = null;
    paraMenu.value = null;
}

function isInput(element) {
    return ['text', 'textarea', 'number', 'date', 'checkbox', 'select'].includes(element.type);
}

function inputType(type) {
    if (type === 'number') return 'number';
    if (type === 'date') return 'date';
    return 'text';
}

function pagePoint(event) {
    const rect = pageRef.value.getBoundingClientRect();
    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
}

function onPagePointerDown(event) {
    if (event.target !== pageRef.value) return;
    if (mode.value === 'design' && drawingTable.value) {
        const point = pagePoint(event);
        rubber.value = { x: point.x, y: point.y, w: 0, h: 0 };
        gesture = { kind: 'draw-table', x: point.x, y: point.y };
        window.addEventListener('pointermove', onPointerMove);
        window.addEventListener('pointerup', endGesture);
        return;
    }
    selectedId.value = null;
}

function selectTableCell(element, cell) {
    selectedId.value = element.id;
    selectedCell.value = { tableId: element.id, cellId: cell.id };
}

function removeSelectedCell() {
    const table = selected.value;
    const pick = selectedCell.value;
    if (!table || table.type !== 'table' || !pick || pick.tableId !== table.id) return;
    let rowIndex = -1;
    let colIndex = -1;
    table.cells.forEach((row, r) => {
        const index = row.findIndex((cell) => cell.id === pick.cellId);
        if (index >= 0) {
            rowIndex = r;
            colIndex = index;
        }
    });
    if (rowIndex < 0) return;
    const row = table.cells[rowIndex];
    if (table.cells.length === 1 && row.length === 1) {
        toast.info('One cell left', 'Remove the table if you want it gone.');
        return;
    }
    const removedWidth = Number(row[colIndex].w) || 0;
    row.splice(colIndex, 1);
    if (!row.length) {
        table.cells.splice(rowIndex, 1);
        table.rowHeights.splice(rowIndex, 1);
        table.h = table.rowHeights.reduce((sum, height) => sum + height, 0);
        selectedCell.value = null;
        return;
    }
    const neighbor = row[Math.min(colIndex, row.length - 1)];
    neighbor.w = Number(neighbor.w) + removedWidth;
    selectedCell.value = { tableId: table.id, cellId: neighbor.id };
}

function onBlockPointerDown(event, element) {
    if (mode.value !== 'design') return;
    if (element.type !== 'table' || !event.target.closest('.sheet-cell')) selectedCell.value = null;
    selectedId.value = element.id;
    if (!canDrag(element) || event.target.closest('.handle, .grip, .move-bar, .logo-insert, textarea, input, select, button')) return;
    startMove(event, element);
}

function startMove(event, element) {
    const point = pagePoint(event);
    gesture = { kind: 'move', id: element.id, dx: point.x - element.x, dy: point.y - element.y };
    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', endGesture);
}

function fitGrid(values, count, total, min) {
    if (!count) return [];
    if (!Array.isArray(values) || values.length !== count) {
        return Array.from({ length: count }, () => total / count);
    }
    return values.map((value) => Math.max(min, Number(value) || min));
}

function normalizeCells(element) {
    const cells = Array.isArray(element.cells) ? element.cells : [];
    const shared = Array.isArray(element.colWidths) && element.colWidths.length && !Array.isArray(element.colWidths[0])
        ? element.colWidths.map(Number)
        : null;
    const width = Number(element.w) || 180;
    return cells.map((row) => {
        const line = Array.isArray(row) ? row : [];
        const each = width / (line.length || 1);
        return line.map((cell, c) => ({
            id: cell.id,
            text: cell.text || '',
            w: Number(cell.w) || Number(shared?.[c]) || each,
        }));
    });
}

function ensureGrid(element) {
    const rows = element.cells?.length || 0;
    if (!Array.isArray(element.rowHeights)) element.rowHeights = [];
    while (element.rowHeights.length < rows) element.rowHeights.push(Math.max(22, (element.h || 48) / (rows || 1)));
    element.rowHeights.splice(rows);
    element.cells.forEach((row) => {
        const count = row.length || 1;
        const each = (element.w || 180) / count;
        row.forEach((cell) => {
            if (!Number(cell.w)) cell.w = each;
        });
        const sum = row.reduce((total, cell) => total + Number(cell.w), 0);
        if (sum > 0 && Math.abs(sum - element.w) > 1) {
            const scale = element.w / sum;
            row.forEach((cell) => { cell.w = Math.max(36, Number(cell.w) * scale); });
            const used = row.slice(0, -1).reduce((total, cell) => total + Number(cell.w), 0);
            row[row.length - 1].w = Math.max(36, element.w - used);
        }
    });
}

function cellBox(element, r, c) {
    const cell = element.cells[r][c];
    return { ...cellStyle(element, r, c), width: `${Number(cell.w) || element.w / element.cells[r].length}px` };
}

function heightsOf(element) {
    return fitGrid(element.rowHeights, element.cells?.length || 0, element.h, 22);
}

function rowTop(element, r) {
    return heightsOf(element).slice(0, r).reduce((sum, height) => sum + height, 0);
}

function edgeLeft(element, r, count) {
    return element.cells[r].slice(0, count).reduce((sum, cell) => sum + (Number(cell.w) || 0), 0);
}

function colGripStyle(element, r, count) {
    return {
        left: `${edgeLeft(element, r, count)}px`,
        top: `${rowTop(element, r)}px`,
        height: `${heightsOf(element)[r]}px`,
    };
}

function gridOffset(sizes, count) {
    return sizes.slice(0, count).reduce((sum, size) => sum + size, 0);
}

function startResize(event, element, dir) {
    const point = pagePoint(event);
    if (element.type === 'table') ensureGrid(element);
    gesture = {
        kind: 'resize',
        id: element.id,
        dir,
        x: element.x,
        y: element.y,
        w: element.w,
        h: element.h,
        px: point.x,
        py: point.y,
        cellWidths: element.cells.map((row) => row.map((cell) => Number(cell.w) || 0)),
        rowHeights: element.rowHeights ? element.rowHeights.map(Number) : null,
    };
    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', endGesture);
}

function startGridResize(event, element, axis, row, index) {
    ensureGrid(element);
    const point = pagePoint(event);
    gesture = {
        kind: 'grid',
        id: element.id,
        axis,
        row,
        index,
        start: axis === 'col' ? point.x : point.y,
        left: axis === 'col' ? Number(element.cells[row][index].w) : 0,
        right: axis === 'col' ? Number(element.cells[row][index + 1].w) : 0,
        heights: element.rowHeights.map(Number),
    };
    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', endGesture);
}

function onPointerMove(event) {
    if (!gesture || !pageRef.value) return;
    const point = pagePoint(event);
    if (gesture.kind === 'draw-table') {
        rubber.value = {
            x: Math.min(gesture.x, point.x),
            y: Math.min(gesture.y, point.y),
            w: Math.abs(point.x - gesture.x),
            h: Math.abs(point.y - gesture.y),
        };
        return;
    }
    const element = elements.value.find((item) => item.id === gesture.id);
    if (!element) return;
    if (gesture.kind === 'grid') {
        const min = gesture.axis === 'col' ? 36 : 22;
        const delta = (gesture.axis === 'col' ? point.x : point.y) - gesture.start;
        if (gesture.axis === 'col') {
            const first = gesture.left + delta;
            const second = gesture.right - delta;
            if (first >= min && second >= min) {
                element.cells[gesture.row][gesture.index].w = first;
                element.cells[gesture.row][gesture.index + 1].w = second;
            }
            return;
        }
        const next = gesture.heights.map(Number);
        const first = next[gesture.index] + delta;
        const second = next[gesture.index + 1] - delta;
        if (first >= min && second >= min) {
            next[gesture.index] = first;
            next[gesture.index + 1] = second;
            element.rowHeights = next;
        }
        return;
    }
    if (gesture.kind === 'move') {
        if (!canDrag(element)) return;
        element.x = clamp(point.x - gesture.dx, 0, pageW() - element.w);
        element.y = clamp(point.y - gesture.dy, 0, pageH() - element.h);
        return;
    }

    let { x, y, w, h } = gesture;
    const dx = point.x - gesture.px;
    const dy = point.y - gesture.py;
    if (gesture.dir.includes('e')) w += dx;
    if (gesture.dir.includes('s')) h += dy;
    if (gesture.dir.includes('w')) { w -= dx; x += dx; }
    if (gesture.dir.includes('n')) { h -= dy; y += dy; }
    const cols = element.type === 'table' ? (element.cells?.[0]?.length || 1) : 1;
    const rows = element.type === 'table' ? (element.cells?.length || 1) : 1;
    const minW = element.type === 'line' ? 40 : element.type === 'table' ? cols * 36 : element.type === 'logo' ? 48 : 72;
    const minH = element.type === 'line' ? 4 : element.type === 'table' ? rows * 22 : element.type === 'logo' ? 48 : 28;
    if (w < minW) { if (gesture.dir.includes('w')) x -= minW - w; w = minW; }
    if (h < minH) { if (gesture.dir.includes('n')) y -= minH - h; h = minH; }
    x = clamp(x, 0, pageW() - minW);
    y = clamp(y, 0, pageH() - minH);
    element.x = Math.round(x);
    element.y = Math.round(y);
    element.w = Math.round(Math.min(w, pageW() - x));
    element.h = Math.round(Math.min(h, pageH() - y));
    if (element.type === 'docHeader' || element.type === 'docFooter') pinBand(element);
    if (element.type === 'table' && gesture.cellWidths && gesture.rowHeights && gesture.w && gesture.h) {
        const scaleX = element.w / gesture.w;
        element.cells.forEach((row, r) => {
            row.forEach((cell, c) => {
                cell.w = Math.max(36, (gesture.cellWidths[r]?.[c] || cell.w) * scaleX);
            });
            const used = row.slice(0, -1).reduce((sum, cell) => sum + Number(cell.w), 0);
            if (row.length) row[row.length - 1].w = Math.max(36, element.w - used);
        });
        element.rowHeights = gesture.rowHeights.map((height) => Math.max(22, height * (element.h / gesture.h)));
    }
}

function endGesture() {
    if (gesture?.kind === 'draw-table' && rubber.value && rubber.value.w > 40 && rubber.value.h > 24) {
        const rows = clamp(Math.round(rubber.value.h / 28), 1, 8);
        const cols = clamp(Math.round(rubber.value.w / 90), 1, 8);
        insertTable(rows, cols, rubber.value.x, rubber.value.y, rubber.value.w, rubber.value.h);
    }
    rubber.value = null;
    gesture = null;
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', endGesture);
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function applyOptions() {
    if (!selected.value) return;
    selected.value.options = optionText.value.split('\n').map((line) => line.trim()).filter(Boolean);
}

function removeSelected() {
    elements.value = elements.value.filter((element) => element.id !== selectedId.value);
    selectedId.value = null;
}

async function uploadLogo(event, element = null) {
    const target = element || selected.value;
    const file = event.target.files?.[0];
    if (event.target) event.target.value = '';
    if (!file || !target || target.type !== 'logo') return;
    if (!file.type.startsWith('image/')) {
        toast.error('Choose an image', 'The logo needs a picture file.');
        return;
    }
    selectedId.value = target.id;
    if (!layoutId.value) await save();
    if (!layoutId.value) return;
    const body = new FormData();
    body.append('image', file);
    try {
        const { data } = await axios.post(`form-layouts/${layoutId.value}/image`, body);
        target.image = data.url;
    } catch (err) {
        toast.apiError(err, 'Could not upload the logo');
    }
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

function onLogoFileDrop(event, element) {
    const file = [...(event.dataTransfer?.files || [])].find((item) => item.type.startsWith('image/'));
    if (!file) return;
    event.stopPropagation();
    uploadLogo({ target: { files: [file] } }, element);
}

function payload() {
    if (selected.value?.type === 'select') applyOptions();
    return {
        name: formName.value.trim() || 'Untitled form',
        target: target.value,
        fields: elements.value,
        page: {
            size: pageSetup.size,
            orientation: pageSetup.orientation,
            margin: pageSetup.margin,
            margins: { ...pageSetup.margins },
            columns: pageSetup.columns,
            columnCount: pageSetup.columnCount,
            columnGap: pageSetup.columnGap,
        },
    };
}

async function save() {
    if (!formName.value.trim()) {
        toast.error('Name the form', 'Give this form a name before saving.');
        return;
    }
    const emptyChoice = elements.value.find((element) => element.type === 'select' && !(element.options || []).length);
    if (emptyChoice) {
        toast.error('Add dropdown choices', `"${emptyChoice.text}" needs at least one choice.`);
        selectedId.value = emptyChoice.id;
        return;
    }
    saving.value = true;
    try {
        const request = layoutId.value
            ? axios.put(`form-layouts/${layoutId.value}`, payload())
            : axios.post('form-layouts', payload());
        const { data } = await request;
        const created = !route.params.id;
        layoutId.value = data.id;
        if (data.page) loadPage(data.page);
        await loadList();
        if (created) await router.replace({ name: 'form-layout-edit', params: { id: data.id } });
        toast.success('Form saved', 'This layout is ready to fill.');
    } catch (err) {
        toast.apiError(err, 'Could not save the form');
    } finally {
        saving.value = false;
    }
}

async function saveEntry() {
    const missing = elements.value.filter((element) => element.required && isBlank(element));
    if (missing.length) {
        toast.error('Fill the required fields', missing.map((element) => element.text).join(', '));
        return;
    }
    if (!layoutId.value) {
        await save();
        if (!layoutId.value) return;
    }
    saving.value = true;
    try {
        await axios.post(`form-layouts/${layoutId.value}/entries`, { values: answers.value });
        toast.success('Saved', 'The form answers were stored.');
        answers.value = {};
    } catch (err) {
        toast.apiError(err, 'Could not save the answers');
    } finally {
        saving.value = false;
    }
}

function isBlank(element) {
    const value = answers.value[element.id];
    if (element.type === 'checkbox') return !value;
    return value == null || String(value).trim() === '';
}
</script>

<style scoped>
.designer { display: flex; flex-direction: column; gap: 8px; height: 100%; min-height: 0; min-width: 0; overflow: hidden; }
.titlebar { display: flex; justify-content: space-between; gap: 12px; align-items: center; }
.doc-title, .title-tools, .font-row, .row-actions, .logo-actions { display: flex; align-items: center; gap: 8px; }
.doc-title strong { color: #1a3557; font-size: 16px; }
.doc-name, .doc-pick, .font-stack select { height: 28px; border: 1px solid #c8c8c8; background: white; border-radius: 2px; padding: 0 8px; font-size: 13px; }
.doc-name { width: 220px; }
.tab, .save { height: 28px; padding: 0 10px; border: 1px solid #c8c8c8; background: white; border-radius: 2px; font-size: 12px; color: #1a3557; }
.tab.on, .mini.on { background: #1a3557; color: white; border-color: #1a3557; }
.save { background: #1a3557; color: white; border-color: #c5a059; font-weight: 700; }
.ribbon { display: flex; align-items: stretch; gap: 0; max-width: 100%; overflow-x: auto; overflow-y: hidden; background: #f3f3f3; border: 1px solid #d0d0d0; }
.ribbon-group { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 4px; padding: 6px 10px 4px; border-right: 1px solid #e1e1e1; flex: 0 0 auto; }
.group-caption { font-size: 11px; color: #666; line-height: 16px; white-space: nowrap; }
.font-stack { display: flex; flex-direction: column; justify-content: flex-end; gap: 4px; min-height: 64px; }
.font-stack select { min-width: 150px; }
.font-stack .size { min-width: 58px; width: 58px; }
.mini { width: 26px; height: 26px; border: 1px solid transparent; background: transparent; border-radius: 2px; color: #333; }
.mini.icon { width: 24px; height: 22px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
.mini.icon svg { width: 16px; height: 16px; display: block; }
.mini:disabled, .caret:disabled { opacity: 0.35; }
.mini:hover, .ribbon-btn:hover, .caret:hover { background: white; border-color: #c8c8c8; }
.para-stack { display: flex; flex-direction: column; justify-content: flex-end; gap: 2px; min-height: 64px; }
.para-row, .split { display: flex; align-items: center; }
.para-gap { width: 8px; }
.split { position: relative; }
.caret { width: 12px; height: 22px; border: 1px solid transparent; background: transparent; color: #444; font-size: 9px; padding: 0; }
.pilcrow-btn { font: 700 15px/1 Georgia, "Times New Roman", serif; }
.para-menu { top: calc(100% + 4px); min-width: 170px; }
.word-menu button.picked { background: #f3e6c0; }
.swatches { display: grid; grid-template-columns: repeat(5, 18px); gap: 4px; padding: 6px; }
.swatch { width: 18px; height: 18px; border: 1px solid #bbb; padding: 0; }
.shade-custom { display: flex; align-items: center; gap: 8px; padding: 6px 8px; font-size: 12px; }
.copy { width: 100%; }
.copy p { margin: 0; }
.marker { display: inline-block; min-width: 1.5em; }
.pilcrow { color: #888; font-weight: 400; }
.hf-stack { display: flex; flex-direction: column; gap: 1px; min-width: 168px; }
.hf-stack .split { width: 100%; }
.hf-btn { display: flex; align-items: center; gap: 6px; width: 100%; height: 22px; padding: 0 4px; border: 1px solid transparent; background: transparent; font-size: 12px; color: #333; white-space: nowrap; }
.hf-btn svg { width: 16px; height: 16px; }
.hf-btn:hover { background: white; border-color: #c8c8c8; }
.word-menu.hf-menu { min-width: 230px; }
.menu-label { margin: 6px 8px 2px; font-size: 11px; font-weight: 700; color: #666; }
.band { position: relative; width: 100%; height: 100%; display: flex; }
.band-tag { position: absolute; top: 2px; right: 8px; font-size: 10px; letter-spacing: 0.06em; text-transform: uppercase; color: #8a8a8a; pointer-events: none; }
.band textarea { width: 100%; height: 100%; border: 0; resize: none; outline: none; background: transparent; font: inherit; color: inherit; }
.band-cols { display: grid; grid-template-columns: 1fr 1fr 1fr; width: 100%; height: 100%; }
.band-cols span { padding: 6px; }
.band-cols .center { text-align: center; }
.band-cols .right { text-align: right; }
.block.docHeader { box-shadow: inset 0 -1px 0 #c8c8c8; }
.block.docFooter { box-shadow: inset 0 1px 0 #c8c8c8; }
.page-num { display: flex; align-items: center; width: 100%; height: 100%; }
.insert-row { display: flex; flex-direction: row; flex-wrap: nowrap; align-items: flex-end; }
.ribbon-btn { width: 72px; height: 64px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; border: 1px solid transparent; background: transparent; font-size: 11px; line-height: 1.15; text-align: center; color: #333; border-radius: 2px; cursor: grab; white-space: normal; }
.menu-wrap { position: relative; }
.word-menu, .table-pop { position: fixed; z-index: 80; background: white; border: 1px solid #c8c8c8; box-shadow: 0 8px 20px rgba(0,0,0,.15); min-width: 230px; padding: 4px; }
.word-menu.para-menu { min-width: 170px; }
.word-menu { max-height: min(70vh, 520px); overflow-y: auto; }
.word-menu button { display: flex; align-items: center; gap: 10px; width: 100%; text-align: left; background: white; border: 0; padding: 5px 8px; font-size: 13px; color: #222; }
.word-menu button:hover, .table-grid button.hot { background: #f3e6c0; }
.menu-icon, .border-icon, .table-icon { width: 16px; height: 16px; display: inline-block; box-sizing: border-box; background: white; }
.border-icon.all, .menu-icon.all { border: 1px solid #333; box-shadow: inset 0 0 0 4px white, inset 0 0 0 5px #333; }
.menu-icon.bottom { border-bottom: 2px solid #333; }
.menu-icon.top { border-top: 2px solid #333; }
.menu-icon.left { border-left: 2px solid #333; }
.menu-icon.right { border-right: 2px solid #333; }
.menu-icon.none { border: 1px dashed #bbb; }
.menu-icon.outside { border: 2px solid #333; }
.menu-icon.inside, .menu-icon.insideH, .menu-icon.insideV { border: 1px solid #bbb; }
.menu-icon.inside { background: linear-gradient(#333, #333) center/100% 1px no-repeat, linear-gradient(#333, #333) center/1px 100% no-repeat; }
.menu-icon.insideH { background: linear-gradient(#333, #333) center/100% 1px no-repeat; }
.menu-icon.insideV { background: linear-gradient(#333, #333) center/1px 100% no-repeat; }
.menu-icon.diagonalDown { background: linear-gradient(to bottom right, transparent 46%, #333 46%, #333 54%, transparent 54%); }
.menu-icon.diagonalUp { background: linear-gradient(to top right, transparent 46%, #333 46%, #333 54%, transparent 54%); }
.menu-icon.line { background: linear-gradient(#333, #333) center/100% 2px no-repeat; }
.menu-icon.draw, .table-icon { border: 1px solid #333; background: linear-gradient(#d0d0d0, #d0d0d0) center/100% 1px no-repeat, linear-gradient(#d0d0d0, #d0d0d0) center/1px 100% no-repeat; }
.menu-icon.gridlines { border: 1px dotted #666; }
.menu-icon.shading { background: linear-gradient(#1a3557, #c5a059); }
.shading { display: flex; gap: 8px; padding: 8px; border-top: 1px solid #eee; font-size: 12px; }
.table-pop { width: 230px; }
.table-pop p { font-size: 12px; margin: 4px 6px 6px; }
.table-grid { display: grid; grid-template-columns: repeat(10, 16px); gap: 2px; padding: 4px; }
.table-grid button { width: 16px; height: 16px; border: 1px solid #bfbfbf; background: white; padding: 0; }
.workspace { display: grid; grid-template-columns: minmax(0, 1fr) 230px; gap: 10px; min-height: 0; flex: 1; overflow: hidden; }
.desk { height: 100%; min-height: 0; overflow: auto; background: #e6e6e6; border: 1px solid #d0d0d0; padding: 28px; }
.desk.drawing { cursor: crosshair; }
.page { position: relative; margin: 0 auto; background: white; box-shadow: 0 8px 24px rgba(0,0,0,.18); }
.setup-row { display: flex; align-items: flex-end; gap: 2px; }
.setup-btn { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; width: 78px; min-height: 74px; padding: 2px 2px 0; border: 1px solid transparent; background: transparent; color: #333; font-size: 11px; line-height: 1.1; }
.setup-btn svg { width: 32px; height: 32px; }
.setup-caret { font-size: 9px; line-height: 12px; color: #444; }
.setup-btn:hover, .setup-btn.open { background: white; border-color: #c8c8c8; }
.setup-menu { min-width: 280px; max-width: 340px; }
.setup-choice { align-items: center; }
.choice-copy { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.choice-copy small { color: #666; font-size: 11px; font-weight: 400; white-space: normal; }
.margin-thumb, .orient-thumb, .col-thumb { box-sizing: border-box; background: white; flex: none; }
.margin-thumb { width: 28px; height: 36px; border-style: solid; border-color: #8eb4e0; background: #e8f1fb; }
.orient-thumb { border: 1px solid #2b579a; }
.orient-thumb.portrait { width: 16px; height: 22px; }
.orient-thumb.landscape { width: 22px; height: 16px; }
.col-thumb { width: 28px; height: 36px; border: 1px solid #8aa4c8; }
.col-thumb.cols-2 { background: linear-gradient(#8eb4e0, #8eb4e0) center/1px 100% no-repeat; }
.col-thumb.cols-3 { background: linear-gradient(#8eb4e0, #8eb4e0) 33% 0/1px 100% no-repeat, linear-gradient(#8eb4e0, #8eb4e0) 66% 0/1px 100% no-repeat; }
.col-thumb.cols-left { background: linear-gradient(#8eb4e0, #8eb4e0) 30% 0/1px 100% no-repeat; }
.col-thumb.cols-right { background: linear-gradient(#8eb4e0, #8eb4e0) 70% 0/1px 100% no-repeat; }
.setup-custom { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; padding: 8px; border-top: 1px solid #eee; }
.setup-custom .menu-label, .setup-custom .apply { grid-column: 1 / -1; }
.setup-custom label { display: flex; flex-direction: column; gap: 2px; font-size: 11px; color: #444; }
.setup-custom input { height: 26px; border: 1px solid #c8c8c8; padding: 0 6px; width: 100%; }
.rubber { position: absolute; border: 1px dashed #1a3557; background: rgba(26, 53, 87, .08); pointer-events: none; }
.block { position: absolute; box-sizing: border-box; }
.block.selected { outline: 1px solid #2b579a; }
.heading-text, .heading-text textarea, .logo-box, .field-box, .line-box, .sheet-table { width: 100%; height: 100%; }
.heading-text { display: flex; align-items: flex-start; overflow: auto; }
.heading-text textarea { border: 0; resize: none; background: transparent; font: inherit; color: inherit; text-align: inherit; padding: 0; }
.logo-box { position: relative; display: flex; align-items: center; justify-content: center; color: #8a6a24; font-size: 12px; overflow: hidden; }
.logo-box.empty { border: 1px dashed #c5a059; background: #fffdf6; }
.logo-box img { position: absolute; inset: 0; width: 100%; height: 100%; max-width: none; object-fit: contain; pointer-events: none; }
.logo-insert { position: absolute; inset: 0; z-index: 2; cursor: pointer; }
.logo-insert input { display: none; }
.logo-hint { position: absolute; left: 0; right: 0; bottom: 0; z-index: 1; padding: 4px; background: rgba(26, 53, 87, .78); color: white; font-size: 11px; text-align: center; pointer-events: none; opacity: 0; }
.logo-hint.show, .logo-box:hover .logo-hint { opacity: 1; }
.line-box { height: 100%; }
.sheet-table { display: flex; flex-direction: column; width: 100%; height: 100%; }
.sheet-row { display: flex; width: 100%; min-height: 0; }
.sheet-cell { position: relative; box-sizing: border-box; flex: none; min-width: 0; overflow: hidden; }
.sheet-cell.active { outline: 1px solid #2b579a; outline-offset: -1px; }
.cell-remove { position: absolute; top: 2px; right: 2px; z-index: 4; width: 16px; height: 16px; border: 0; border-radius: 2px; background: #ce1126; color: white; font-size: 12px; line-height: 16px; padding: 0; cursor: pointer; }
.sheet-cell textarea { display: block; width: 100%; height: 100%; min-height: 0; box-sizing: border-box; margin: 0; padding: 4px 6px; border: 0; resize: none; outline: none; background: transparent; font: inherit; color: inherit; text-align: inherit; line-height: inherit; }
.grip { position: absolute; z-index: 6; background: transparent; }
.grip.col { width: 10px; margin-left: -5px; cursor: col-resize; }
.grip.row { left: 0; right: 0; height: 10px; margin-top: -5px; cursor: row-resize; }
.field-box { position: relative; display: flex; flex-direction: column; gap: 4px; }
.field-box span { font-size: 11px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #1a3557; }
.field-box em { color: #ce1126; font-style: normal; margin-left: 2px; }
.field-box input, .field-box textarea, .field-box select, .fake-control { flex: 1; min-height: 0; border: 1px solid #94a3b8; border-radius: 2px; padding: 4px 6px; font-size: 13px; background: white; }
.field-box input[type="checkbox"] { width: 16px; height: 16px; flex: none; }
.move-bar { position: absolute; top: -20px; left: 0; height: 18px; padding: 0 6px; border: 0; border-radius: 2px; background: #2b579a; color: white; font-size: 10px; cursor: move; }
.handle { position: absolute; width: 8px; height: 8px; background: white; border: 1px solid #2b579a; }
.nw { left: -4px; top: -4px; cursor: nwse-resize; }
.n { left: calc(50% - 4px); top: -4px; cursor: ns-resize; }
.ne { right: -4px; top: -4px; cursor: nesw-resize; }
.e { right: -4px; top: calc(50% - 4px); cursor: ew-resize; }
.se { right: -4px; bottom: -4px; cursor: nwse-resize; }
.s { left: calc(50% - 4px); bottom: -4px; cursor: ns-resize; }
.sw { left: -4px; bottom: -4px; cursor: nesw-resize; }
.w { left: -4px; top: calc(50% - 4px); cursor: ew-resize; }
.panel { height: 100%; min-height: 0; background: #f7f7f7; border: 1px solid #d0d0d0; padding: 12px; overflow: auto; }
.palette-label { margin: 0 0 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #666; }
.setting { display: flex; flex-direction: column; gap: 4px; margin-bottom: 10px; font-size: 12px; color: #333; }
.setting input, .setting textarea, .upload, .row-actions button, .logo-actions button { width: 100%; border: 1px solid #c8c8c8; border-radius: 2px; padding: 6px; font-size: 13px; background: white; }
.row-actions, .logo-actions { flex-direction: column; align-items: stretch; margin-bottom: 10px; }
.upload input { display: none; }
.check { display: flex; align-items: center; gap: 6px; font-size: 13px; margin-bottom: 10px; }
.size-readout, .hint { font-size: 12px; color: #666; margin-bottom: 10px; }
.danger { color: #ce1126; font-size: 13px; }
.primary.wide { width: 100%; height: 34px; margin-top: 12px; border: 0; border-radius: 2px; background: #1a3557; color: white; }
@media (max-width: 1100px) {
    .titlebar { flex-direction: column; align-items: stretch; }
    .workspace { display: flex; flex-direction: column; }
    .desk { flex: 1; }
    .panel { height: auto; max-height: 240px; flex: none; }
}
</style>
