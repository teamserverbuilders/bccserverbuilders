<template>
    <div class="relative" ref="rootEl">
        <!-- Trigger input (looks like a search box, not a button) -->
        <div
            class="hidden md:flex items-center h-8 rounded-md border transition-colors bg-slate-50 dark:bg-slate-800 w-72 lg:w-96"
            :class="focused
                ? 'border-[#1a3557] ring-1 ring-[#1a3557]/20 dark:border-blue-500 dark:ring-blue-500/30'
                : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'"
        >
            <i class="pi pi-search text-xs text-slate-400 pl-3 pr-2"></i>
            <input
                ref="inputEl"
                v-model="query"
                type="text"
                placeholder="Search records, owners, users…"
                class="flex-1 h-full bg-transparent outline-none text-xs text-slate-700 dark:text-slate-200 placeholder:text-slate-400"
                @focus="onFocus"
                @keydown="onKeyDown"
                @input="onInput"
            />
            <button
                v-if="query"
                type="button"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 pr-2"
                @click="clear"
                aria-label="Clear search"
            >
                <i class="pi pi-times text-[10px]"></i>
            </button>
            <kbd v-else class="mr-2 text-[10px] bg-slate-200 dark:bg-slate-700 text-slate-500 px-1.5 py-0.5 rounded font-sans">
                {{ shortcutLabel }}
            </kbd>
        </div>

        <!-- Compact icon-only trigger for mobile -->
        <button
            type="button"
            class="md:hidden h-8 w-8 inline-flex items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
            @click="openMobile"
            aria-label="Open search"
        >
            <i class="pi pi-search text-[14px]"></i>
        </button>

        <!-- Dropdown results -->
        <Transition name="ds">
            <div
                v-if="open"
                class="absolute right-0 mt-1.5 w-[92vw] md:w-[520px] lg:w-[640px] max-h-[70vh] overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-2xl z-50 flex flex-col"
                @click.stop
            >
                <!-- Loading -->
                <div v-if="loading" class="flex items-center gap-2 px-4 py-3 text-xs text-slate-500 border-b border-slate-100 dark:border-slate-800">
                    <i class="pi pi-spin pi-spinner text-xs"></i>
                    <span>Searching…</span>
                </div>

                <!-- Empty state (before typing) -->
                <div v-else-if="!query || query.length < 2" class="px-4 py-8 text-center text-slate-400 text-xs">
                    <i class="pi pi-search text-2xl block mb-2 opacity-40"></i>
                    <p>Type at least 2 characters to search.</p>
                    <p class="mt-1">
                        Try:
                        <span class="text-slate-500">TD numbers, owners, TIN, ARP, PIN, barangays…</span>
                    </p>
                </div>

                <!-- Error banner -->
                <div v-if="errorMsg" class="px-4 py-2 text-[11px] bg-red-50 dark:bg-red-950/30 text-red-700 dark:text-red-300 border-b border-red-100 dark:border-red-900 flex items-start gap-2">
                    <i class="pi pi-exclamation-triangle mt-0.5"></i>
                    <span class="flex-1 break-all">{{ errorMsg }}</span>
                </div>

                <!-- No results -->
                <div v-if="!loading && query.length >= 2 && !hasResults" class="px-4 py-8 text-center text-slate-400 text-xs">
                    <i class="pi pi-inbox text-2xl block mb-2 opacity-40"></i>
                    <p>No matches for <span class="font-medium text-slate-600 dark:text-slate-300">"{{ query }}"</span>.</p>
                    <button
                        type="button"
                        class="mt-3 text-[#1a3557] dark:text-blue-400 hover:underline text-xs"
                        @click="goToFullSearch"
                    >
                        Open advanced search →
                    </button>
                </div>

                <!-- Results -->
                <div v-else class="flex-1 overflow-y-auto">
                    <div v-for="(g, gi) in results.groups" :key="g.type" class="py-1">
                        <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5 bg-slate-50/60 dark:bg-slate-800/40 sticky top-0">
                            <i :class="g.icon" class="text-[10px]"></i>
                            <span>{{ g.label }}</span>
                            <span class="text-slate-300">·</span>
                            <span class="text-slate-400 normal-case tracking-normal">{{ g.items.length }}</span>
                        </div>
                        <button
                            v-for="(item, ii) in g.items"
                            :key="`${g.type}-${item.id}`"
                            type="button"
                            :data-idx="flatIndex(gi, ii)"
                            class="w-full text-left px-3 py-2 flex items-start gap-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0 transition-colors"
                            :class="activeIndex === flatIndex(gi, ii)
                                ? 'bg-slate-100 dark:bg-slate-800'
                                : 'hover:bg-slate-50 dark:hover:bg-slate-800/60'"
                            @mouseenter="activeIndex = flatIndex(gi, ii)"
                            @click="navigateTo(item)"
                        >
                            <span class="h-7 w-7 shrink-0 rounded-md flex items-center justify-center text-white text-xs"
                                :class="badgeColor(item.type)">
                                <i :class="g.icon" class="text-[11px]"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate" v-html="highlight(item.title)"></span>
                                </span>
                                <span v-if="item.subtitle" class="block text-xs text-slate-500 dark:text-slate-400 truncate" v-html="highlight(item.subtitle)"></span>
                                <span v-if="item.meta" class="block text-[11px] text-slate-400 truncate">{{ item.meta }}</span>
                            </span>
                            <i class="pi pi-arrow-right text-[10px] text-slate-300 mt-2 shrink-0"></i>
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div v-if="hasResults" class="px-3 py-2 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex items-center justify-between text-[10px] text-slate-400">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-500">↑↓</kbd>navigate</span>
                        <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-500">↵</kbd>open</span>
                        <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-500">esc</kbd>close</span>
                    </div>
                    <button
                        type="button"
                        class="text-[#1a3557] dark:text-blue-400 hover:underline"
                        @click="goToFullSearch"
                    >
                        Advanced search →
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

const rootEl = ref(null);
const inputEl = ref(null);

const query = ref('');
const results = ref({ query: '', total: 0, groups: [] });
const loading = ref(false);
const open = ref(false);
const focused = ref(false);
const activeIndex = ref(0);
const errorMsg = ref('');

let debounceTimer = null;
let latestReq = 0;

const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform);
const shortcutLabel = isMac ? '⌘K' : 'Ctrl K';

const hasResults = computed(() => (results.value?.groups?.length ?? 0) > 0);
const flatItems = computed(() =>
    (results.value?.groups ?? []).flatMap(g => g.items.map(i => ({ ...i, _groupIcon: g.icon })))
);

function flatIndex(gi, ii) {
    let idx = 0;
    for (let i = 0; i < gi; i++) idx += results.value.groups[i].items.length;
    return idx + ii;
}

function badgeColor(type) {
    return {
        tax_declaration: 'bg-blue-500',
        property_owner:  'bg-emerald-500',
        field_appraisal: 'bg-amber-500',
        document:        'bg-purple-500',
        user:            'bg-indigo-500',
        barangay:        'bg-rose-500',
    }[type] || 'bg-slate-400';
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}
function escapeRegExp(str) { return String(str ?? '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

function highlight(text) {
    const q = (query.value || '').trim();
    if (!q) return escapeHtml(text);
    const safeHtml = escapeHtml(text);
    const re = new RegExp(`(${escapeRegExp(q)})`, 'gi');
    return safeHtml.replace(re, '<mark class="bg-yellow-200 dark:bg-yellow-500/40 text-inherit rounded px-0.5">$1</mark>');
}

function onFocus() {
    focused.value = true;
    open.value = true;
    if (query.value.length >= 2 && !results.value.groups.length) doSearch();
}

function onInput() {
    open.value = true;
    activeIndex.value = 0;
    if (debounceTimer) clearTimeout(debounceTimer);
    if (query.value.trim().length < 2) {
        results.value = { query: '', total: 0, groups: [] };
        return;
    }
    debounceTimer = setTimeout(doSearch, 220);
}

async function doSearch() {
    const q = query.value.trim();
    if (q.length < 2) return;
    const reqId = ++latestReq;
    loading.value = true;
    errorMsg.value = '';
    try {
        const { data } = await axios.get('search', { params: { q } });
        if (reqId !== latestReq) return;
        results.value = data;
        if (Array.isArray(data?.errors) && data.errors.length) {
            errorMsg.value = `Some sources errored: ${data.errors.join(' | ')}`;
        }
    } catch (err) {
        if (reqId !== latestReq) return;
        results.value = { query: q, total: 0, groups: [] };
        errorMsg.value = err?.response?.data?.message || err?.message || 'Search failed';
    } finally {
        if (reqId === latestReq) loading.value = false;
    }
}

function onKeyDown(e) {
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!open.value) open.value = true;
        activeIndex.value = Math.min(activeIndex.value + 1, Math.max(flatItems.value.length - 1, 0));
        scrollActiveIntoView();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex.value = Math.max(activeIndex.value - 1, 0);
        scrollActiveIntoView();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const item = flatItems.value[activeIndex.value];
        if (item) navigateTo(item);
        else if (query.value.trim()) goToFullSearch();
    } else if (e.key === 'Escape') {
        e.preventDefault();
        close();
    }
}

function scrollActiveIntoView() {
    nextTick(() => {
        const el = rootEl.value?.querySelector(`[data-idx="${activeIndex.value}"]`);
        el?.scrollIntoView({ block: 'nearest' });
    });
}

function navigateTo(item) {
    if (!item?.url) return;
    router.push(item.url);
    close();
}

function goToFullSearch() {
    router.push({ path: '/search', query: query.value ? { q: query.value } : {} });
    close();
}

function clear() {
    query.value = '';
    results.value = { query: '', total: 0, groups: [] };
    activeIndex.value = 0;
    inputEl.value?.focus();
}

function close() {
    open.value = false;
    focused.value = false;
    inputEl.value?.blur();
}

function openMobile() {
    goToFullSearch();
}

function onDocumentClick(e) {
    if (!rootEl.value) return;
    if (!rootEl.value.contains(e.target)) close();
}

function onGlobalKey(e) {
    const mod = e.metaKey || e.ctrlKey;
    if (mod && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        inputEl.value?.focus();
        inputEl.value?.select();
        open.value = true;
    }
    if (e.key === '/' && document.activeElement?.tagName !== 'INPUT' && document.activeElement?.tagName !== 'TEXTAREA') {
        e.preventDefault();
        inputEl.value?.focus();
        open.value = true;
    }
}

watch(query, (v) => { if (!v) activeIndex.value = 0; });

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onGlobalKey);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onGlobalKey);
    if (debounceTimer) clearTimeout(debounceTimer);
});
</script>

<style scoped>
.ds-enter-active, .ds-leave-active { transition: all 0.14s ease; }
.ds-enter-from, .ds-leave-to      { opacity: 0; transform: translateY(-4px) scale(0.985); }
</style>
