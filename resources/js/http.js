const getCsrfToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
};

const http = {
    async request(url, options = {}) {
        const token = getCsrfToken();
        const { loading = true, ...fetchOptions } = options;
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token,
            ...(loading ? {} : { 'X-Skip-Loading': '1' }),
            ...fetchOptions.headers,
        };

        const config = {
            ...fetchOptions,
            headers,
        };

        try {
            const response = await fetch(url, config);

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw {
                    status: response.status,
                    message: error.message || `Erro ${response.status}`,
                    errors: error.errors || null,
                };
            }

            return await response.json();
        } catch (error) {
            if (error.status) {
                throw error;
            }
            throw {
                status: 0,
                message: 'Erro de rede ou servidor indisponível',
                errors: null,
            };
        }
    },

    async get(url, params = {}, options = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        return this.request(fullUrl, { ...options, method: 'GET' });
    },

    async post(url, data = {}, options = {}) {
        return this.request(url, {
            ...options,
            method: 'POST',
            body: JSON.stringify(data),
        });
    },

    async put(url, data = {}, options = {}) {
        return this.request(url, {
            ...options,
            method: 'PUT',
            body: JSON.stringify(data),
        });
    },

    async delete(url, options = {}) {
        return this.request(url, { ...options, method: 'DELETE' });
    },
};

export default http;