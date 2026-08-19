import { apiClient } from '../api/client';
// Import types for intellisense
import '../types/models';

export const dashboardService = {
    /**
     * Fetch all dashboard data (orders, catalogs, settings)
     * @returns {Promise<import('../types/models').DashboardResponse>}
     */
    async getDashboardData() {
        return await apiClient.get('/dashboard');
    },

    /**
     * Create a new order
     * @param {import('../types/models').Order} orderData
     */
    async createOrder(orderData) {
        return await apiClient.post('/orders', orderData);
    },

    /**
     * Update an existing order (full: header fields, items, expenses, terms)
     * @param {{ invoiceNo: string, orderData: Object }} payload
     */
    async updateOrder({ invoiceNo, orderData }) {
        return await apiClient.put(`/orders/${encodeURIComponent(invoiceNo)}`, orderData);
    },

    /**
     * Delete an order by invoice_no
     * @param {string} invoiceNo
     */
    async deleteOrder(invoiceNo) {
        return await apiClient.delete(`/orders/${encodeURIComponent(invoiceNo)}`);
    },

    /**
     * Save site settings (waNumber, email, address, tagline, stats, clients)
     * @param {Object} settingsData
     */
    async updateSettings(settingsData) {
        return await apiClient.put('/settings', settingsData);
    },

    /**
     * Replace full catalog (categories + vendors)
     * @param {Array} catalog
     */
    async updateCatalog(catalog) {
        return await apiClient.put('/catalog', { catalog });
    },
};
