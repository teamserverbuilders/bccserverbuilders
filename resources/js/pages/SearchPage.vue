<template>
    <div class="max-w-5xl mx-auto space-y-4">
        <!-- Search bar -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <div class="flex-1 flex items-center h-11 rounded-lg border border-slate-200 dark:border-slate-700 focus-within:border-[#1a3557] focus-within:ring-1 focus-within:ring-[#1a3557]/20 bg-white dark:bg-slate-900 px-3">
                    <i class="pi pi-search text-slate-400 text-sm mr-2"></i>
                    <input
                        ref="inputEl"
                        v-model="query"
                        type="text"
                        placeholder="Search TD numbers, owners, TIN, ARP, PIN, barangays, users…"
                        class="flex-1 h-full bg-transparent outline-none text-sm text-slate-800 dark:text-slate-100 placeholder:text-slate-400"
                        @keyup.enter="runSearch(true)"
                    />
                    <button v-if="query" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="clear">
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>
                <Button label="Search" icon="pi pi-search" @click="runSearch(true)" :loading="loading" />
            </div>

            <!-- Type filter chips -->
            <div class="flex flex-wrap items-center gap-2 mt-3">
                <button
                    v-for="t in typeTabs"
                    :key="t.type"
                    type="button"
                    class="inline-flex items-center gap-1.5 h-7 px-3 rounded-full text-xs font-medium transition-colors border"
                    :class="activeType === t.type
                        ? 'bg-[#1a3557] text-white border-[#1a3557]'
                        : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-[#1a3557] hover:text-[#1a3557] dark:hover:text-white'"
                    @click="setType(t.type)"
                >
                    <i :class="t.icon" class="text-[10px]"></i>
                    <span>{{ t.label }}</span>
                    <span
                        v-if="countsByType[t.type] !== undefined"
                        class="ml-0.5 text-[10px] rounded-full px-1.5 py-0.5"
                        :class="activeType === t.type ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-500'"
                    >
                        {{ countsByType[t.type] }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Error banner -->
        <div
            v-if="errorMsg"
            class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/30 text-red-700 dark:text-red-300 text-sm px-4 py-3 flex items-start gap-2"
        >
            <i class="pi pi-exclamation-triangle mt-0.5"></i>
            <span class="flex-1 break-all">{{ errorMsg }}</span>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 text-center text-slate-400 text-sm">
            <i class="pi pi-spin pi-spinner text-2xl mb-2 block"></i>
            Searching…
        </div>

        <!-- Empty state (no query) -->
        <div
            v-else-if="!hasSearched"
            class="bg-white dark:bg-gray-800 rounded-xl p-12 shadow-sm border border-gray-100 dark:border-gray-700 text-center"
        >
            <i class="pi pi-search text-5xl text-gray-200 dark:text-gray-700 mb-4 block"></i>
            <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-400">Search across everything</h3>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1 max-w-md mx-auto">
                Find any tax declaration, property owner, field appraisal, document, user or barangay in one place.
            </p>
            <div class="flex flex-wrap justify-center gap-2 mt-6">
                <button
                    v-for="s in exampleQueries"
                    :key="s"
                    type="button"
                    class="px-3 py-1 rounded-full border border-slate-200 dark:border-slate-700 text-xs text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                    @click="query = s; runSearch(true)"
                >
                    {{ s }}
                </button>
            </div>
        </div>

        <!-- No results -->
        <div
            v-else-if="hasSearched && !loading && !anyResults"
            class="bg-white dark:bg-gray-800 rounded-xl p-12 shadow-sm border border-gray-100 dark:border-gray-700 text-center"
        >
            <i class="pi pi-inbox text-5xl text-gray-200 dark:text-gray-700 mb-4 block"></i>
            <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-400">
                No results for <span class="text-slate-800 dark:text-slate-100">"{{ lastQuery }}"</span>
            </h3>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">
                Try a different keyword, or check spelling.
            </p>
        </div>

        <!-- Results summary + list -->
        <template v-else-if="hasSearched && anyResults">
            <div class="text-xs text-slate-500 dark:text-slate-400 px-1">
                Found <span class="font-semibold text-slate-700 dark:text-slate-200">{{ results.total }}</span>
                result<span v-if="results.total !== 1">s</span>
                for <span class="font-semibold text-slate-700 dark:text-slate-200">"{{ lastQuery }}"</span>
                <span v-if="activeType !== 'all'"> in <span class="capitalize">{{ activeTypeLabel }}</span></span>
            </div>

            <div
                v-for="g in results.groups"
                :key="g.type"
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
            >
                <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40">
                    <h3 class="font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2 text-sm">
                        <span :class="['h-6 w-6 rounded-md flex items-center justify-center text-white text-[11px]', badgeColor(g.type)]">
                            <i :class="g.icon"></i>
                        </span>
                        {{ g.label }}
                        <span class="text-xs text-slate-400 font-normal">({{ g.items.length }})</span>
                    </h3>
                    <button
                        v-if="activeType === 'all'"
                        type="button"
                        class="text-xs text-[#1a3557] dark:text-blue-400 hover:underline"
                        @click="setType(g.type)"
                    >
                        Show only {{ g.label }} →
                    </button>
                </div>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    <li
                        v-for="item in g.items"
                        :key="`${g.type}-${item.id}`"
                        class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors cursor-pointer"
                        @click="goTo(item)"
                    >
                        <div class="px-5 py-3 flex items-start gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate" v-html="highlight(item.title)"></p>
                                <p v-if="item.subtitle" class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5" v-html="highlight(item.subtitle)"></p>
                                <p v-if="item.meta" class="text-[11px] text-slate-400 mt-1 truncate">{{ item.meta }}</p>
                            </div>
                            <i class="pi pi-arrow-right text-xs text-slate-300 mt-1"></i>
                        </div>
                    </li>
                </ul>
                <div v-if="g.items.length >= perGroup" class="px-5 py-2 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 text-center">
                    <button
                        v-if="activeType !== g.type"
                        type="button"
                        class="text-xs text-[#1a3557] dark:text-blue-400 hover:underline"
                        @click="setType(g.type)"
                    >
                        View all matches in {{ g.label }} →
                    </button>
                    <span v-else class="text-[11px] text-slate-400">
                        Showing the top {{ perGroup }} matches. Refine your search to narrow further.
                    </span>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const inputEl = ref(null);
const query = ref('');
const lastQuery = ref('');
const results = ref({ query: '', total: 0, groups: [] });
const loading = ref(false);
const errorMsg = ref('');
const hasSearched = ref(false);
const activeType = ref('all');
const countsByType = ref({});

const typeTabs = [
    { type: 'all', label: 'All', icon: 'pi pi-th-large' },
    { type: 'tax_declaration', label: 'Tax Declarations', icon: 'pi pi-file-edit' },
    { type: 'property_owner', label: 'Owners', icon: 'pi pi-users' },
    { type: 'field_appraisal', label: 'Field Appraisals', icon: 'pi pi-clipboard' },
    { type: 'document', label: 'Documents', icon: 'pi pi-folder' },
    { type: 'user', label: 'Users', icon: 'pi pi-id-card' },
    { type: 'barangay', label: 'Barangays', icon: 'pi pi-map-marker' },
];

const exampleQueries = ['AGUILA', '2016-04', 'San Vicente', 'Agricultural'];

const perGroup = computed(() => (activeType.value === 'all' ? 5 : 25));
const activeTypeLabel = computed(() => typeTabs.find(t => t.type === activeType.value)?.label ?? '');
const anyResults = computed(() => (results.value?.groups?.length ?? 0) > 0);

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
    const q = (lastQuery.value || '').trim();
    if (!q) return escapeHtml(text);
    const safe = escapeHtml(text);
    return safe.replace(new RegExp(`(${escapeRegExp(q)})`, 'gi'),
        '<mark class="bg-yellow-200 dark:bg-yellow-500/40 text-inherit rounded px-0.5">$1</mark>');
}

async function runSearch(pushUrl = false) {
    const q = query.value.trim();
    if (q.length < 2) {
        results.value = { query: '', total: 0, groups: [] };
        hasSearched.value = false;
        return;
    }
    loading.value = true;
    hasSearched.value = true;
    errorMsg.value = '';
    lastQuery.value = q;
    try {
        const params = { q, limit: perGroup.value };
        if (activeType.value !== 'all') params.type = activeType.value;
        const { data } = await axios.get('search', { params });
        results.value = data;
        if (Array.isArray(data?.errors) && data.errors.length) {
            errorMsg.value = `Some sources errored: ${data.errors.join(' | ')}`;
        }
        if (activeType.value === 'all') updateCounts(data.groups);
        if (pushUrl) {
            router.replace({
                query: {
                    q,
                    ...(activeType.value !== 'all' ? { type: activeType.value } : {}),
                },
            });
        }
    } catch (err) {
        results.value = { query: q, total: 0, groups: [] };
        errorMsg.value = err?.response?.data?.message || err?.message || 'Search failed';
    } finally {
        loading.value = false;
    }
}

function updateCounts(groups) {
    const map = {};
    for (const g of groups || []) map[g.type] = g.items.length;
    let all = 0;
    for (const k in map) all += map[k];
    map.all = all;
    countsByType.value = map;
}

function setType(type) {
    if (activeType.value === type) return;
    activeType.value = type;
    runSearch(true);
}

function clear() {
    query.value = '';
    lastQuery.value = '';
    results.value = { query: '', total: 0, groups: [] };
    hasSearched.value = false;
    activeType.value = 'all';
    countsByType.value = {};
    router.replace({ query: {} });
    nextTick(() => inputEl.value?.focus());
}

function goTo(item) {
    if (!item?.url) return;
    router.push(item.url);
}

onMounted(async () => {
    inputEl.value?.focus();
    const initial = (route.query.q ?? '').toString();
    const initialType = (route.query.type ?? 'all').toString();
    if (initial) {
        query.value = initial;
        activeType.value = typeTabs.some(t => t.type === initialType) ? initialType : 'all';
        await runSearch(false);
    }
});

// Re-search when the URL query string changes (e.g., navbar sends a new q).
watch(() => route.query, (nq) => {
    const newQ = (nq.q ?? '').toString();
    const newType = (nq.type ?? 'all').toString();
    if (newQ && (newQ !== query.value || newType !== activeType.value)) {
        query.value = newQ;
        activeType.value = typeTabs.some(t => t.type === newType) ? newType : 'all';
        runSearch(false);
    }
});
</script>
