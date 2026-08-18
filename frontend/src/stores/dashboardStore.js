import { defineStore } from 'pinia'
import { ref } from 'vue'

const createBlankForm = (tax = 11) => ({
    group: '', pic: '', contact: '', dest: '', depart: '', ret: '', pax: '',
    items: [
      { cat: 'Tiket Pesawat', vendor: '', desc: '', qty: '', cost: '', price: '' },
      { cat: 'Hotel', vendor: '', desc: '', qty: '', cost: '', price: '' },
    ],
    discount: '', taxPercent: tax, dpPercent: '50',
    notes: 'Pembayaran DP 50% saat konfirmasi booking. Pelunasan paling lambat H-14 sebelum keberangkatan.',
})

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        activeInvoice: null,
        settingsTab: 'website',
        form: createBlankForm(11),
        // Shared query data from Dashboard layout
        orders: [],
        catalog: [],
        site: {
            waNumber: '6281200000000',
            email: 'halo@tourosa.id',
            address: 'Jakarta, Indonesia',
            tagline: '',
            stats: [],
            clients: []
        },
    }),
    actions: {
        setQueryData({ orders, catalog, site }) {
            // Watch the computed refs and sync to store state
            this.orders = orders.value;
            this.catalog = catalog.value;
            
            // Deep clone site to ensure Vue reactivity tracks everything
            const clonedSite = JSON.parse(JSON.stringify(site.value || {}));
            if (!clonedSite.stats) clonedSite.stats = [];
            if (!clonedSite.clients) clonedSite.clients = [];
            this.site = clonedSite;
        },
        setActiveInvoice(invoice) {
            // Deep-clone so we own the object and Vue reactivity can track all fields.
            // Pre-initialize expenses and terms arrays so adding items always works.
            const cloned = JSON.parse(JSON.stringify(invoice));
            if (!cloned.expenses) cloned.expenses = [];
            if (!cloned.terms) cloned.terms = [];
            this.activeInvoice = cloned;
        },
        setSettingsTab(tab) {
            this.settingsTab = tab;
        },
        resetForm() {
            this.form = createBlankForm(this.form.taxPercent);
        },
        addItemToForm() {
            this.form.items.push({ cat: 'Lainnya', vendor: '', desc: '', qty: '', cost: '', price: '' });
        },
        removeItemFromForm(idx) {
            if (this.form.items.length > 1) {
                this.form.items.splice(idx, 1);
            }
        },
        updateFormItem(idx, field, val) {
            this.form.items[idx][field] = val;
        },
        addExpenseToInvoice() {
            if (this.activeInvoice) {
                if (!this.activeInvoice.expenses) this.activeInvoice.expenses = [];
                this.activeInvoice.expenses.push({ label: '', amount: 0 });
            }
        },
        removeExpenseFromInvoice(idx) {
            if (this.activeInvoice && this.activeInvoice.expenses) {
                this.activeInvoice.expenses.splice(idx, 1);
            }
        },
        updateExpense(idx, field, val) {
            if (this.activeInvoice && this.activeInvoice.expenses) {
                this.activeInvoice.expenses[idx][field] = val;
            }
        },
        addTermToInvoice() {
            if (this.activeInvoice) {
                if (!this.activeInvoice.terms) this.activeInvoice.terms = [];
                this.activeInvoice.terms.push({ label: '', percent: 0, due: '' });
            }
        },
        removeTermFromInvoice(idx) {
            if (this.activeInvoice && this.activeInvoice.terms) {
                this.activeInvoice.terms.splice(idx, 1);
            }
        },
        updateTerm(idx, field, val) {
            if (this.activeInvoice && this.activeInvoice.terms) {
                this.activeInvoice.terms[idx][field] = val;
            }
        }
    }
})
