import axios from 'axios';

function resolveBaseUrl() {
    if (import.meta.env && import.meta.env.VITE_API_BASEURL) {
        return import.meta.env.VITE_API_BASEURL;
    }

    const meta = document.head.querySelector('meta[name="api-base-url"]');
    if (meta?.content) {
        return meta.content;
    }

    return window.location.origin;
}

const apiClient = axios.create({
    baseURL: resolveBaseUrl(),
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

export { resolveBaseUrl };
export default apiClient;
