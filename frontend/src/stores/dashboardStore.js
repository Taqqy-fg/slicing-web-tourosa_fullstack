import { defineStore } from 'pinia'
import { ref } from 'vue'

const generatePaymentInfo = (bankAccounts) => {
    const template = 'Bank: \nNo. Rekening: \nAtas Nama (a.n): ';
    if (!bankAccounts || bankAccounts.length === 0) {
        return template;
    }
    return template + '\n\n' + bankAccounts.map(b => `Bank: ${b.bank}\nNo. Rekening: ${b.number}\nAtas Nama (a.n): ${b.name}`).join('\n\n');
}

const createBlankForm = (tax = 0) => ({
    group: '', pic: '', contact: '', email: '',
    invoiceDate: new Date().toISOString().slice(0, 10),
    items: [
      { cat: '', vendor: '', tripType: 'Round Trip', dest: '', depart: '', ret: '', desc: '', qty: '', cost: '', markupCost: '', price: '', markupPrice: '' },
    ],
    discount: '', discountType: 'Rp', serviceFee: '', serviceFeeType: 'Rp',
    taxPercent: tax, dpPercent: '', dpDueDate: '', tenggatDate: '',
    notes: '', 
    payment_info: 'Bank: \nNo. Rekening: \nAtas Nama (a.n): ',
})

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        activeInvoice: null,
        settingsTab: 'website',
        form: createBlankForm(0),
        editForm: null,
        editInvoiceNo: null,
        // Shared query data from Dashboard layout
        orders: [],
        catalog: [],
        catalogVersion: 0,   // bumped every time catalog is edited locally
        customers: [],
        orderInfos: [],
        site: {
            waNumber: '6281200000000',
            email: 'halo@tourosa.id',
            address: 'Jakarta, Indonesia',
            tagline: '',
            bankAccounts: [],
            stats: [],
            clients: []
        },
        testimonials: [],
    }),
    actions: {
        setQueryData({ orders, catalog, site, testimonials, customers, orderInfos }) {
            if (orders && orders.value) this.orders = orders.value;
            // Only overwrite catalog from server on initial load (when it's still empty).
            // After any local edit, catalogVersion > 0, so we skip the server data to
            // avoid a race condition where a background refetch reverts our local changes.
            if (catalog && catalog.value && this.catalogVersion === 0) {
                this.catalog = catalog.value;
            }
            if (customers && customers.value) this.customers = customers.value;
            if (orderInfos && orderInfos.value) this.orderInfos = orderInfos.value;
            
            const clonedSite = JSON.parse(JSON.stringify(site.value || {}));
            if (!clonedSite.stats) clonedSite.stats = [];
            if (!clonedSite.clients) clonedSite.clients = [];
            if (!clonedSite.bankAccounts) clonedSite.bankAccounts = [];
            this.site = clonedSite;

            this.testimonials = JSON.parse(JSON.stringify(testimonials.value || []));
            
            this.updateDefaultPaymentInfo();
        },
        updateDefaultPaymentInfo() {
            if (!this.form.payment_info || this.form.payment_info === 'Bank: \nNo. Rekening: \nAtas Nama (a.n): ') {
                this.form.payment_info = generatePaymentInfo(this.site.bankAccounts);
            }
        },
        setActiveInvoice(invoice) {
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
            this.form.payment_info = generatePaymentInfo(this.site.bankAccounts);
        },
        addItemToForm() {
            this.form.items.push({ cat: '', vendor: '', tripType: 'Round Trip', dest: '', depart: '', ret: '', desc: '', qty: '', cost: '', markupCost: '', price: '', markupPrice: '' });
        },
        removeItemFromForm(idx) {
            if (this.form.items.length > 1) {
                this.form.items.splice(idx, 1);
            }
        },
        duplicateItemFromForm(idx) {
            const items = [...this.form.items.map(i => ({ ...i }))];
            items.splice(idx + 1, 0, { ...this.form.items[idx] });
            this.form.items = items;
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
                const allocated = (this.activeInvoice.terms || []).reduce((a, tm) => a + (Number(tm.percent) || 0), 0)
                const remaining = Math.max(0, 100 - allocated)
                this.activeInvoice.terms.push({ label: '', percent: remaining, due: '' })
            }
        },
        removeTermFromInvoice(idx) {
            if (this.activeInvoice && this.activeInvoice.terms) {
                this.activeInvoice.terms.splice(idx, 1);
            }
        },
        updateTerm(idx, field, val) {
            if (this.activeInvoice && this.activeInvoice.terms) {
                if (field === 'percent') {
                    const others = (this.activeInvoice.terms || []).reduce((a, tm, i) => a + (i === idx ? 0 : (Number(tm.percent) || 0)), 0)
                    const maxAllowed = Math.max(0, 100 - others)
                    val = Math.min(Number(val) || 0, maxAllowed)
                }
                this.activeInvoice.terms[idx][field] = val;
            }
        },
        loadEditForm(order) {
            const o = JSON.parse(JSON.stringify(order));
            this.editInvoiceNo = o.no;
            this.editForm = {
                group: o.group || '',
                pic: o.pic || '',
                contact: o.contact || '',
                email: o.email || '',
                invoiceDate: o.date || new Date().toISOString().slice(0, 10),
                dest: o.dest || '',
                depart: o.depart || '',
                ret: o.ret || '',
                pax: o.pax || '',
                items: (o.items && o.items.length) ? o.items.map(it => ({
                    cat: it.cat || 'Lainnya',
                    vendor: it.vendor || '',
                    tripType: (it.tripType === 'Pergi-Pulang' ? 'Round Trip' : it.tripType === 'Satuan' ? 'One Way' : it.tripType) || 'Round Trip',
                    dest: it.dest || '',
                    depart: it.depart || '',
                    ret: it.ret || '',
                    desc: it.desc || '',
                    qty: it.qty ?? '',
                    cost: it.cost ?? '',
                    markupCost: it.markupCost ?? '',
                    price: it.price ?? '',
                    markupPrice: it.markupPrice ?? '',
                })) : [{ cat: '', vendor: '', tripType: 'Round Trip', dest: '', depart: '', ret: '', desc: '', qty: '', cost: '', markupCost: '', price: '', markupPrice: '' }],
                discount: o.discount ?? '',
                discountType: o.discountType ?? 'Rp',
                serviceFee: o.serviceFee ?? '',
                serviceFeeType: o.serviceFeeType ?? 'Rp',
                taxPercent: o.taxPercent ?? 0,
                dpPercent: o.dpPercent ?? 0,
                dpDueDate: o.dpDueDate ?? '',
                tenggatDate: o.tenggatDate ?? '',
                notes: o.notes || '',
                payment_info: (!o.payment_info || o.payment_info === 'Bank: \nNo. Rekening: \nAtas Nama (a.n): ') ? generatePaymentInfo(this.site.bankAccounts) : o.payment_info,
                status: o.status || 'DP',
            }
        },
        resetEditForm() {
            this.editForm = null;
            this.editInvoiceNo = null;
        },
        addItemToEditForm() {
            if (this.editForm) {
                this.editForm.items.push({ cat: '', vendor: '', tripType: 'Round Trip', dest: '', depart: '', ret: '', desc: '', qty: '', cost: '', markupCost: '', price: '', markupPrice: '' });
            }
        },
        removeItemFromEditForm(idx) {
            if (this.editForm && this.editForm.items.length > 1) {
                this.editForm.items.splice(idx, 1);
            }
        },
        duplicateItemFromEditForm(idx) {
            if (this.editForm) {
                const items = [...this.editForm.items.map(i => ({ ...i }))];
                items.splice(idx + 1, 0, { ...this.editForm.items[idx] });
                this.editForm.items = items;
            }
        },
        updateEditFormItem(idx, field, val) {
            if (this.editForm) {
                this.editForm.items[idx][field] = val;
            }
        },
        syncActiveInvoiceFromOrders() {
            if (this.activeInvoice && this.orders && this.orders.length) {
                const match = this.orders.find(o => o.no === this.activeInvoice.no)
                if (match) {
                    const cloned = JSON.parse(JSON.stringify(match));
                    if (!cloned.expenses) cloned.expenses = [];
                    if (!cloned.terms) cloned.terms = [];
                    this.activeInvoice = cloned;
                    return true
                }
            }
            return false
        },
        findOrderById(id) {
            const match = this.orders.find(o => o.no === id)
            if (match) this.setActiveInvoice(match)
            return match || null
        },
        findAndLoadEditForm(id) {
            const match = this.orders.find(o => o.no === id)
            if (match) this.loadEditForm(match)
            return match || null
        }
    }
})
