<template>
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ formName || 'Form' }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ targetLabel }}</p>
            </div>
            <div class="flex items-center gap-2">
                <Button label="Form list" icon="pi pi-list" size="small" outlined @click="router.push('/form-layouts')" />
                <Button label="Edit" icon="pi pi-pencil" size="small" @click="router.push(`/form-layouts/${route.params.id}/edit`)" />
                <Button label="Delete" icon="pi pi-trash" size="small" severity="danger" outlined @click="confirmDelete" />
            </div>
        </div>

        <FormLayoutSheet v-if="loaded" v-model="answers" :fields="fields" :page="page" />

        <div class="flex justify-end">
            <Button label="Save answers" icon="pi pi-check" size="small" :loading="saving" @click="saveAnswers" />
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import axios from 'axios';
import Button from 'primevue/button';
import { useToast } from '@/composables/useToast';
import FormLayoutSheet from '@/components/FormLayoutSheet.vue';

const route = useRoute();
const router = useRouter();
const confirm = useConfirm();
const toast = useToast();
const formName = ref('');
const target = ref('tax_declaration');
const fields = ref([]);
const page = ref(null);
const answers = ref({});
const loaded = ref(false);
const saving = ref(false);

const targetLabel = computed(() => (target.value === 'field_appraisal' ? 'Field Appraisal' : 'Tax Declaration'));

async function load() {
    try {
        const { data } = await axios.get(`form-layouts/${route.params.id}`);
        formName.value = data.name;
        target.value = data.target;
        fields.value = Array.isArray(data.fields) ? data.fields : [];
        page.value = data.page || null;
        loaded.value = true;
    } catch (err) {
        toast.apiError(err, 'Could not open the form');
        router.push('/form-layouts');
    }
}

function missingFields() {
    return fields.value.filter((element) => {
        if (!element.required || !['text', 'textarea', 'number', 'date', 'checkbox', 'select'].includes(element.type)) return false;
        const value = answers.value[element.id];
        if (element.type === 'checkbox') return !value;
        return value == null || String(value).trim() === '';
    });
}

async function saveAnswers() {
    const missing = missingFields();
    if (missing.length) {
        toast.error('Fill the required fields', missing.map((element) => element.text).join(', '));
        return;
    }
    saving.value = true;
    try {
        await axios.post(`form-layouts/${route.params.id}/entries`, { values: answers.value });
        toast.success('Saved', 'The form answers were stored.');
        answers.value = {};
    } catch (err) {
        toast.apiError(err, 'Could not save the answers');
    } finally {
        saving.value = false;
    }
}

function confirmDelete() {
    confirm.require({
        message: `Delete "${formName.value}"? Saved answers for this form will be removed too.`,
        header: 'Delete form',
        icon: 'pi pi-trash',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptSeverity: 'danger',
        accept: async () => {
            try {
                await axios.delete(`form-layouts/${route.params.id}`);
                toast.success('Deleted', 'The form was removed.');
                router.push('/form-layouts');
            } catch (err) {
                toast.apiError(err, 'Could not delete the form');
            }
        },
    });
}

onMounted(load);
</script>
