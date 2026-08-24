import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/auth/LoginPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('@/pages/auth/ForgotPasswordPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/verify/:tdNumber',
        name: 'qr-verify',
        component: () => import('@/pages/QrVerifyPage.vue'),
        meta: { public: true },
    },
    {
        path: '/',
        component: () => import('@/layouts/MainLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/dashboard' },
            { path: 'dashboard', name: 'dashboard', component: () => import('@/pages/DashboardPage.vue') },

            // ─── Property Records ──────────────────────────────────────────
            // Tax Declarations
            { path: 'tax-declarations', name: 'tax-declarations', component: () => import('@/pages/tax-declarations/TaxDeclarationIndex.vue') },
            { path: 'tax-declarations/create', name: 'td-create', component: () => import('@/pages/tax-declarations/TaxDeclarationForm.vue') },
            { path: 'tax-declarations/:id/pdf', name: 'td-pdf', component: () => import('@/pages/tax-declarations/TaxDeclarationPdf.vue') },
            { path: 'tax-declarations/:id/edit', name: 'td-edit', component: () => import('@/pages/tax-declarations/TaxDeclarationForm.vue') },
            { path: 'tax-declarations/:id', name: 'td-show', component: () => import('@/pages/tax-declarations/TaxDeclarationView.vue') },
            { path: 'workflow', name: 'workflow', component: () => import('@/pages/WorkflowPage.vue') },

            // Field Appraisals
            { path: 'field-appraisals', name: 'field-appraisals', component: () => import('@/pages/field-appraisals/FieldAppraisalIndex.vue') },
            { path: 'field-appraisals/create', name: 'fa-create', component: () => import('@/pages/field-appraisals/FieldAppraisalForm.vue') },
            { path: 'field-appraisals/:id/pdf', name: 'fa-pdf', component: () => import('@/pages/field-appraisals/FieldAppraisalPdf.vue') },
            { path: 'field-appraisals/:id/edit', name: 'fa-edit', component: () => import('@/pages/field-appraisals/FieldAppraisalForm.vue') },
            { path: 'field-appraisals/:id', name: 'fa-show', component: () => import('@/pages/field-appraisals/FieldAppraisalView.vue') },

            // Property Owners
            { path: 'property-owners', name: 'properties', component: () => import('@/pages/properties/PropertyIndex.vue') },
            { path: 'property-owners/:id', name: 'property-show', component: () => import('@/pages/properties/PropertyView.vue') },

            // Property Improvements
            { path: 'property-improvements', name: 'property-improvements', component: () => import('@/pages/PropertyImprovementsPage.vue') },

            // Property Locations
            { path: 'property-locations', name: 'property-locations', component: () => import('@/pages/PropertyLocationsPage.vue') },

            // Supporting Documents
            { path: 'documents', name: 'documents', component: () => import('@/pages/DocumentsPage.vue') },
            { path: 'archive', name: 'archive', component: () => import('@/pages/ArchivePage.vue') },
            { path: 'ownership-history', name: 'ownership-history', component: () => import('@/pages/OwnershipHistoryPage.vue') },

            // Global search results page
            { path: 'search', name: 'search', component: () => import('@/pages/SearchPage.vue') },

            // ─── Tools ─────────────────────────────────────────────────────
            { path: 'ocr', name: 'ocr', component: () => import('@/pages/ocr/OcrIndex.vue') },
            { path: 'ocr/:id', name: 'ocr-review', component: () => import('@/pages/ocr/OcrReview.vue') },
            { path: 'gis', name: 'gis', component: () => import('@/pages/GisMapPage.vue') },
            { path: 'land-map', name: 'land-map', component: () => import('@/pages/LandMapPage.vue'), meta: { fullBleed: true } },

            // ─── Reports ───────────────────────────────────────────────────
            { path: 'reports', name: 'reports', component: () => import('@/pages/ReportsPage.vue') },
            { path: 'audit', name: 'audit', component: () => import('@/pages/AuditPage.vue') },

            // ─── Admin ─────────────────────────────────────────────────────
            { path: 'users', name: 'users', component: () => import('@/pages/users/UsersIndex.vue') },
            { path: 'roles', name: 'roles', component: () => import('@/pages/users/RolesPage.vue') },
            { path: 'settings', name: 'settings', component: () => import('@/pages/settings/SettingsPage.vue') },
            { path: 'profile', name: 'profile', component: () => import('@/pages/ProfilePage.vue') },
        ],
    },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/pages/NotFoundPage.vue') },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    if (to.meta.public) return next();

    if (to.meta.requiresAuth) {
        if (!authStore.token) return next({ name: 'login', query: { redirect: to.fullPath } });
        if (!authStore.user) {
            try {
                await authStore.fetchUser();
            } catch {
                return next({ name: 'login' });
            }
        }
        return next();
    }

    if (to.meta.guest && authStore.token) return next({ name: 'dashboard' });

    next();
});

export default router;
