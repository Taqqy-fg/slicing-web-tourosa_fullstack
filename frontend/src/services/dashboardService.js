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
     * Create a new customer
     */
    async createCustomer(customerData) {
        return await apiClient.post('/customers', customerData);
    },

    /**
     * Record a term payment
     */
    async payOrderTerm({ invoiceNo, termId, formData }) {
        return await apiClient.post(`/orders/${encodeURIComponent(invoiceNo)}/terms/${termId}/payments`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
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
     * Record a payment for an order (multipart for proof file upload).
     * Status becomes "Lunas" automatically when total paid >= grand total.
     * @param {{ invoiceNo: string, formData: FormData }} payload
     */
    async createOrderPayment({ invoiceNo, formData }) {
        return await apiClient.post(`/orders/${encodeURIComponent(invoiceNo)}/payments`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
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

    /**
     * Download Excel Report
     * @param {Object} params - Query params like start_date, end_date
     */
    async exportExcel(params = {}) {
        const response = await apiClient.get('/reports/excel', { params, responseType: 'blob' });
        return response;
    },

    /**
     * Download PDF Report
     * @param {Object} params - Query params like start_date, end_date
     */
    async exportPdf(params = {}) {
        const response = await apiClient.get('/reports/pdf', { params, responseType: 'blob' });
        return response;
    },

    /**
     * Create a testimonial (multipart for avatar upload)
     */
    async createTestimonial(formData) {
        return await apiClient.post('/testimonials', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
    },

    /**
     * Update a testimonial (multipart for avatar upload)
     */
    async updateTestimonial({ id, formData }) {
        formData.append('_method', 'PUT');
        return await apiClient.post(`/testimonials/${id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
    },

    /**
     * Delete a testimonial
     */
    async deleteTestimonial(id) {
        return await apiClient.delete(`/testimonials/${id}`);
    },

    /**
     * List all admin accounts
     */
    async getAdmins() {
        return await apiClient.get('/admins');
    },

    /**
     * Create a new admin account
     */
    async createAdmin(adminData) {
        return await apiClient.post('/admins', adminData);
    },

    /**
     * Update an admin account
     */
    async updateAdmin({ id, adminData }) {
        return await apiClient.put(`/admins/${id}`, adminData);
    },

    /**
     * Delete an admin account
     */
    async deleteAdmin(id) {
        return await apiClient.delete(`/admins/${id}`);
    },

    // ──── Roles & Permissions ────

    /**
     * List all roles with permissions
     */
    async getRoles() {
        return await apiClient.get('/roles');
    },

    /**
     * Get single role detail
     */
    async getRole(id) {
        return await apiClient.get(`/roles/${id}`);
    },

    /**
     * Create a new role
     */
    async createRole(roleData) {
        return await apiClient.post('/roles', roleData);
    },

    /**
     * Update a role
     */
    async updateRole({ id, roleData }) {
        return await apiClient.put(`/roles/${id}`, roleData);
    },

    /**
     * Delete a role
     */
    async deleteRole(id) {
        return await apiClient.delete(`/roles/${id}`);
    },

    /**
     * List all permissions (grouped)
     */
    async getPermissions() {
        return await apiClient.get('/permissions');
    },

    /**
     * List all permissions flat (for forms)
     */
    async getAllPermissions() {
        return await apiClient.get('/permissions/all');
    },

    // ──── Order Infos ────

    /**
     * Ambil semua informasi pesanan
     */
    async getOrderInfos() {
        return await apiClient.get('/order-infos');
    },

    /**
     * Buat informasi pesanan baru
     */
    async createOrderInfo(infoData) {
        return await apiClient.post('/order-infos', infoData);
    },

    /**
     * Update informasi pesanan berdasarkan ID
     */
    async updateOrderInfo({ id, infoData }) {
        return await apiClient.put(`/order-infos/${id}`, infoData);
    },

    /**
     * Hapus informasi pesanan berdasarkan ID
     */
    async deleteOrderInfo(id) {
        return await apiClient.delete(`/order-infos/${id}`);
    },
};
