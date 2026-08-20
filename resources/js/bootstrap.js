import axios from 'axios';
import { loadingService } from './Services/loadingService';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Global HTTP request interceptor to trigger loading screen
window.axios.interceptors.request.use(
    (config) => {
        if (!config.headers?.['X-Silent-Loading']) {
            loadingService.start();
        }
        return config;
    },
    (error) => {
        loadingService.finish();
        return Promise.reject(error);
    }
);

// Global HTTP response interceptor to finish loading screen
window.axios.interceptors.response.use(
    (response) => {
        if (!response.config?.headers?.['X-Silent-Loading']) {
            loadingService.finish();
        }
        return response;
    },
    (error) => {
        if (!error.config?.headers?.['X-Silent-Loading']) {
            loadingService.finish();
        }
        return Promise.reject(error);
    }
);

