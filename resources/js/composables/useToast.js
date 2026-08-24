import { toast } from 'vue-sonner';

/**
 * Thin wrapper around vue-sonner that keeps the same call-site API
 * used throughout the project ( success / error / info / warn / add ).
 */
export function useToast() {
    const add = ({ severity = 'info', summary = '', detail = '', life = 4000 }) => {
        const msg = detail || summary;
        switch (severity) {
            case 'success': toast.success(summary, { description: detail || undefined, duration: life }); break;
            case 'error':   toast.error(summary,   { description: detail || undefined, duration: life }); break;
            case 'warn':    toast.warning(summary, { description: detail || undefined, duration: life }); break;
            default:        toast.info(summary,    { description: detail || undefined, duration: life }); break;
        }
    };

    return {
        add,
        success: (summary, detail, life = 4000) => toast.success(summary, { description: detail, duration: life }),
        error:   (summary, detail, life = 8000) => toast.error(summary,   { description: detail, duration: life }),
        info:    (summary, detail, life = 4000) => toast.info(summary,    { description: detail, duration: life }),
        warn:    (summary, detail, life = 5000) => toast.warning(summary, { description: detail, duration: life }),
        loading: (summary, detail) => toast.loading(summary, { description: detail }),
        dismiss: (id) => toast.dismiss(id),
        apiError: showApiError,
    };
}

/**
 * Show a detailed vue-sonner error for failed API requests (validation, 500, network).
 */
export function showApiError(err, fallbackTitle = 'Request failed') {
    const status = err?.response?.status;
    const data = err?.response?.data ?? {};
    const validation = data.errors || {};
    const lines = Object.entries(validation).flatMap(([field, msgs]) => {
        const list = Array.isArray(msgs) ? msgs : [msgs];
        return list.map(m => `${field.replace(/_/g, ' ')}: ${m}`);
    });

    let title = fallbackTitle;
    if (status === 422) title = 'Validation failed';
    else if (status === 403) title = 'Not allowed';
    else if (status === 401) title = 'Session expired';
    else if (status >= 500) title = 'Server error';
    else if (status) title = `${fallbackTitle} (${status})`;

    const description = lines.length
        ? lines.join('\n')
        : (data.message || err?.message || 'Please check the form and try again.');

    toast.error(title, {
        description,
        duration: 12000,
    });

    return validation;
}

export { toast };
