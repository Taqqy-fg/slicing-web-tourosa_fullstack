import { apiClient } from '../api/client';
// Import types for intellisense
import '../types/models';

export const dashboardService = {
    /**
     * Fetch all dashboard data (orders, catalogs, settings)
     * @returns {Promise<import('../types/models').DashboardResponse>}
     */
    async getDashboardData() {
        // Since our interceptor returns response.data, this directly resolves to the payload
        return await apiClient.get('/dashboard');
    },

    /**
     * Create a new order
     * @param {import('../types/models').Order} orderData
     * @returns {Promise<any>}
     */
    async createOrder(orderData) {
        return await apiClient.post('/orders', orderData);
    }
};
