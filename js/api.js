/**
 * Core API Helper for asynchronous CMS operations
 */

const API = {
    csrfToken: '',

    /**
     * Set CSRF Token for sessions
     * @param {string} token 
     */
    setCSRF(token) {
        this.csrfToken = token;
    },

    /**
     * Base Fetch Wrapper
     * @param {string} endpoint 
     * @param {object} options 
     */
    async request(endpoint, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            ...options
        };

        try {
            const response = await fetch(`api/${endpoint}`, defaultOptions);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Request failed');
            }

            return data;
        } catch (error) {
            console.error(`API Error [${endpoint}]:`, error.message);
            throw error;
        }
    }
};
