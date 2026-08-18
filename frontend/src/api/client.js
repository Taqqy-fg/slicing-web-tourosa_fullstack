import axios from 'axios';

/**
 * Axios instance configured for the Tourosa API.
 */
export const apiClient = axios.create({
    baseURL: 'http://127.0.0.1:8000/api',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Request interceptor — attach Bearer token
apiClient.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor
apiClient.interceptors.response.use(
    (response) => response.data,
    (error) => {
        if (error.response?.status === 422) {
            return Promise.reject(error);
        }
        console.error('API Error:', error.response?.data || error.message);
        return Promise.reject(error);
    }
);
