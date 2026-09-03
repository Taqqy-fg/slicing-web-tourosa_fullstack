import { apiClient } from '../api/client';

export const siteService = {
    async getSiteSettings() {
        return await apiClient.get('/site');
    }
};
