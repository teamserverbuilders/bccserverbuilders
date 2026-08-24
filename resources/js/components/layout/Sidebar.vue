<template>
    <aside
        :class="[
            'relative z-30 flex flex-col shrink-0 transition-all duration-300',
            'bg-[#1a3557] border-r border-[#122540]',
            collapsed ? 'w-[60px]' : 'w-[240px]',
        ]"
    >
        <!-- Logo / Branding -->
        <div
            class="flex items-center gap-3 h-16 px-3 border-b border-[#122540] bg-[#122540]"
        >
            <img
                :src="logoSrc"
                alt="Municipality Seal"
                class="w-9 h-9 rounded-full object-cover shrink-0 ring-2 ring-[#b8860b]"
            />
            <Transition name="fade-label">
                <div v-if="!collapsed" class="overflow-hidden min-w-0">
                    <p
                        class="text-[12px] font-bold text-white leading-tight whitespace-nowrap tracking-wide uppercase"
                    >
                        Assessor's Office
                    </p>
                    <p
                        class="text-[10px] text-blue-300 leading-tight whitespace-nowrap mt-0.5"
                    >
                        Municipality of Baao
                    </p>
                </div>
            </Transition>
        </div>

        <!-- Gold accent line -->
        <div
            class="h-[2px] bg-gradient-to-r from-[#b8860b] via-[#d4a017] to-[#b8860b] shrink-0"
        ></div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
            <template v-for="group in navGroups" :key="group.label">
                <!-- Group label -->
                <div v-if="!collapsed" class="px-2 pt-4 pb-1 first:pt-2">
                    <span
                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-blue-400/70"
                    >
                        {{ group.label }}
                    </span>
                </div>
                <div v-else class="my-2 mx-1 border-t border-[#1e4880]"></div>

                <NavItem
                    v-for="item in group.items"
                    :key="item.to"
                    :item="item"
                    :collapsed="collapsed"
                />
            </template>
        </nav>

        <!-- Version footer -->
        <div class="border-t border-[#122540] px-3 py-2">
            <p
                v-if="!collapsed"
                class="text-[10px] text-blue-400/50 whitespace-nowrap"
            >
                TDMS v1.0 — {{ year }}
            </p>
            <p v-else class="text-[10px] text-blue-400/50 text-center">v1</p>
        </div>
    </aside>
</template>

<script setup>
import NavItem from "./NavItem.vue";

defineProps({ collapsed: { type: Boolean, default: false } });

const logoSrc = "/images/sidelogo.png";
const year = new Date().getFullYear();

const navGroups = [
    {
        label: "Overview",
        items: [
            { to: "/dashboard", label: "Dashboard", icon: "pi-home" },
            { to: "/workflow", label: "Workflow", icon: "pi-sitemap" },
        ],
    },
    {
        label: "Property Records",
        items: [
            {
                to: "/tax-declarations",
                label: "Tax Declarations",
                icon: "pi-file-edit",
            },
            {
                to: "/field-appraisals",
                label: "Field Appraisals",
                icon: "pi-clipboard",
            },
            {
                to: "/property-owners",
                label: "Property Owners",
                icon: "pi-users",
            },
            {
                to: "/property-improvements",
                label: "Improvements",
                icon: "pi-building",
            },
            {
                to: "/property-locations",
                label: "Locations",
                icon: "pi-map-marker",
            },
            { to: "/documents", label: "Supporting Docs", icon: "pi-folder" },
            { to: "/ownership-history", label: "Ownership History", icon: "pi-history" },
            { to: "/archive", label: "Archive", icon: "pi-inbox" },
        ],
    },
    {
        label: "Reports",
        items: [
            { to: "/reports", label: "Reports", icon: "pi-chart-bar" },
            { to: "/audit", label: "Audit Trail", icon: "pi-list" },
        ],
    },
    {
        label: "Administration",
        items: [
            { to: "/users", label: "User Management", icon: "pi-user-edit" },
            { to: "/roles", label: "Roles & Permissions", icon: "pi-shield" },
            { to: "/settings", label: "Settings", icon: "pi-cog" },
        ],
    },
];
</script>

<style scoped>
.fade-label-enter-active,
.fade-label-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}
.fade-label-enter-from,
.fade-label-leave-to {
    opacity: 0;
    transform: translateX(-6px);
}
</style>
