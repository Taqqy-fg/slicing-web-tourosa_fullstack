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
            this.orders = orders.value
            this.catalog = catalog.value
            this.site = site.value
        },
        setActiveInvoice(invoice) {
            this.activeInvoice = invoice;
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
        }
    }
})
