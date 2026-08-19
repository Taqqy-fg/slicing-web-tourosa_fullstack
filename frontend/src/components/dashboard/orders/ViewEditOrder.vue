<script setup>
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { useDashboardStore } from '../../../stores/dashboardStore'
import { useDashboardData } from '../../../composables/useDashboardData'
import { dashboardService } from '../../../services/dashboardService'
import DatePicker from '../../DatePicker.vue'

const props = defineProps({
  orders: Array,
  catalog: Array
})

const store = useDashboardStore()
const route = useRoute()
const router = useRouter()
const queryClient = useQueryClient()
const { fmt, calc } = useDashboardData()

const catalog = computed(() => props.catalog ?? store.catalog)
const ef = computed(() => store.editForm)
const invoiceNo = computed(() => store.editInvoiceNo || route.params.id)

watch(() => route.params.id, (id) => {
  const decoded = id ? decodeURIComponent(id) : null
  if (decoded && !ef.value) {
    store.findAndLoadEditForm(decoded)
  }
}, { immediate: true })

const catOptions = computed(() => catalog.value.map(c => c.cat))
const vendorsFor = (cat) => {
  const c = catalog.value.find(x => x.cat === cat)
  return c ? c.items.filter(v => (v || '').trim()) : []
}

const itemRows = computed(() => {
  if (!ef.value) return []
  return ef.value.items.map((it, idx) => ({
    idx, cat: it.cat, vendor: it.vendor || '', desc: it.desc, qty: it.qty, cost: it.cost, price: it.price,
    vendorOptions: vendorsFor(it.cat),
    lineF: fmt((Number(it.qty) || 0) * (Number(it.price) || 0)),
    onCat: e => { store.updateEditFormItem(idx, 'cat', e.target.value); store.updateEditFormItem(idx, 'vendor', '') },
    onVendor: e => { store.updateEditFormItem(idx, 'vendor', e.target.value); if (!it.desc) store.updateEditFormItem(idx, 'desc', e.target.value) },
    onDesc: e => store.updateEditFormItem(idx, 'desc', e.target.value),
    onQty: e => store.updateEditFormItem(idx, 'qty', e.target.value),
    onCost: e => store.updateEditFormItem(idx, 'cost', e.target.value),
    onPrice: e => store.updateEditFormItem(idx, 'price', e.target.value),
    onRemove: () => store.removeItemFromEditForm(idx)
  }))
})

const tCalc = computed(() => {
  if (!ef.value) return {}
  const c = calc(ef.value)
  return {
    tSubtotal: fmt(c.subtotal), tDiscount: fmt(c.discount), tTax: fmt(c.tax), tTotal: fmt(c.total),
    tPerPax: fmt(c.perPax), tDp: fmt(c.dp), tSisa: fmt(c.sisa),
    tCost: fmt(c.totalCost), tProfit: fmt(c.profit), tMargin: Math.round(c.marginPct) + '%'
  }
})

const updateOrderMut = useMutation({
  mutationFn: dashboardService.updateOrder,
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['dashboard'] })
  }
})

const saveOrder = () => {
  if (!ef.value || !invoiceNo.value) return
  const f = ef.value
  const items = f.items.filter(it => (Number(it.qty) || 0) > 0 || (Number(it.price) || 0) > 0 || (it.desc || '').trim())

  const payload = {
    group: (f.group || '').trim() || 'Tanpa Nama Grup',
    pic: f.pic, contact: f.contact, dest: f.dest || '-', depart: f.depart, ret: f.ret, pax: f.pax,
    items: items.length ? items : [{ cat: 'Lainnya', desc: '(belum ada item)', qty: 0, cost: 0, price: 0 }],
    discount: f.discount, taxPercent: f.taxPercent, dpPercent: f.dpPercent, notes: f.notes,
    status: (Number(f.dpPercent) >= 100 ? 'Lunas' : 'DP'),
  }

  updateOrderMut.mutate({ invoiceNo: invoiceNo.value, orderData: payload }, {
    onSuccess: () => {
      store.resetEditForm()
      router.push('/orders')
    }
  })
}

const cancelEdit = () => {
  store.resetEditForm()
  router.push('/orders')
}
const goOrderList = () => { store.resetEditForm(); router.push('/orders') }

const f = computed(() => store.editForm)
const t = tCalc
const addItem = () => store.addItemToEditForm()
</script>

<template>
  <div v-if="f" class="p-mobile grid-cols-1-mobile" style="padding:30px 32px;display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:24px;align-items:start;">
    <div style="display:flex;flex-direction:column;gap:18px;">
      <nav style="display:flex;align-items:center;gap:6px;font-size:13px;flex-wrap:wrap;">
        <a @click.prevent="goOrderList" href="#" style="color:#5d6a82;text-decoration:none;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;"><i class="ph ph-list-checks" style="font-size:15px;"></i>Daftar Pesanan</a>
        <i class="ph ph-caret-right" style="color:#c2c8d4;font-size:13px;"></i>
        <span style="color:#13233f;font-weight:700;">Edit Pesanan</span>
      </nav>
      <!-- group info -->
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:24px;">
        <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0 0 4px;display:flex;align-items:center;gap:9px;"><i class="ph ph-users-three" style="color:#c39a4d;font-size:20px;"></i>Informasi Grup</h3>
        <p style="font-size:13px;color:#8a93a5;margin:0 0 20px;">Edit data pemesan dan perjalanan.</p>
        <div class="grid-cols-1-mobile" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div style="grid-column:span 2;"><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Nama Grup / Instansi</label><input v-model="f.group" placeholder="cth. PT Sinar Abadi — Annual Gathering" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;"></div>
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">PIC / Penanggung Jawab</label><input v-model="f.pic" placeholder="cth. Bpk. Rendra (HRD)" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;"></div>
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">No. HP / WhatsApp</label><input v-model="f.contact" placeholder="cth. 0812-3344-5566" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;"></div>
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Destinasi</label><input v-model="f.dest" placeholder="cth. Bali (Denpasar)" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;"></div>
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Jumlah Peserta (pax)</label><input v-model="f.pax" type="number" placeholder="cth. 45" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;"></div>
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Tanggal Berangkat</label><DatePicker v-model="f.depart" /></div>
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Tanggal Kembali</label><DatePicker v-model="f.ret" /></div>
        </div>
      </div>
      <!-- items -->
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
          <div><h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0 0 2px;display:flex;align-items:center;gap:9px;"><i class="ph ph-list-plus" style="color:#c39a4d;font-size:20px;"></i>Rincian Item</h3><p style="font-size:13px;color:#8a93a5;margin:0;">Tiket, hotel, tour, konsumsi, dan lainnya.</p></div>
          <button @click="addItem" class="tr-btn" style="background:#eef3fb;color:#15294f;border:1px solid #d6e1f2;font-size:13px;font-weight:700;padding:9px 14px;border-radius:9px;cursor:pointer;display:flex;align-items:center;gap:6px;"><i class="ph ph-plus" style="font-size:15px;"></i>Tambah Item</button>
        </div>
        <div class="table-scroll">
          <div class="min-w-table">
            <div class="table-header-mobile" style="display:grid;grid-template-columns:106px 118px 1fr 42px 100px 100px 86px 26px;gap:10px;padding:0 2px 9px;font-size:11px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.03em;">
              <span>Kategori</span><span>Vendor/Produk</span><span>Deskripsi</span><span>Qty</span><span>Beli</span><span>Jual</span><span style="text-align:right;">Jumlah</span><span></span>
            </div>
            <div v-for="(r, idx) in itemRows" :key="idx" class="table-row-mobile invoice-item-row-mobile" style="display:grid;grid-template-columns:106px 118px 1fr 42px 100px 100px 86px 26px;gap:10px;align-items:center;padding:6px 2px;">
          <select class="col-half-mobile" @change="r.onCat" :value="r.cat" style="width:100%;padding:9px 7px;border:1px solid #d8dce4;border-radius:8px;font-size:12.5px;color:#1a2235;background:#fff;outline:none;">
            <option v-for="(co, ci) in catOptions" :key="ci" :value="co">{{ co }}</option>
          </select>
          <select class="col-half-mobile" @change="r.onVendor" :value="r.vendor" style="width:100%;padding:9px 7px;border:1px solid #d8dce4;border-radius:8px;font-size:12.5px;color:#1a2235;background:#fafbfc;outline:none;">
            <option value="">Pilih Vendor...</option>
            <option v-for="(vo, vi) in r.vendorOptions" :key="vi" :value="vo">{{ vo }}</option>
          </select>
          <input class="col-full-mobile" :value="r.desc" @input="r.onDesc" placeholder="Deskripsi (cth. Tiket PP)" style="width:100%;padding:9px 11px;border:1px solid #d8dce4;border-radius:8px;font-size:13px;color:#1a2235;background:#fff;outline:none;">
          <input class="col-full-mobile" :value="r.qty" @input="r.onQty" type="number" placeholder="Qty" style="width:100%;padding:9px 8px;border:1px solid #d8dce4;border-radius:8px;font-size:13px;color:#1a2235;background:#fff;outline:none;text-align:center;font-family:'IBM Plex Mono',monospace;">
          <input class="col-half-mobile text-right-mobile" :value="r.cost" @input="r.onCost" type="number" placeholder="Harga Beli" style="width:100%;padding:9px 9px;border:1px solid #d8dce4;border-radius:8px;font-size:13px;color:#5d6a82;background:#fafbfc;outline:none;text-align:right;font-family:'IBM Plex Mono',monospace;">
          <input class="col-half-mobile text-right-mobile" :value="r.price" @input="r.onPrice" type="number" placeholder="Harga Jual" style="width:100%;padding:9px 9px;border:1px solid #d8dce4;border-radius:8px;font-size:13px;color:#1a2235;background:#fff;outline:none;text-align:right;font-family:'IBM Plex Mono',monospace;">
          <span class="col-full-mobile text-right-mobile" style="font-size:14px;font-weight:700;color:#13233f;text-align:right;font-family:'IBM Plex Mono',monospace;">Sub: {{ r.lineF }}</span>
            <button class="del-btn-mobile tr-btn" @click="r.onRemove" style="background:none;border:none;cursor:pointer;color:#c2603a;display:flex;align-items:center;justify-content:center;padding:6px;border-radius:7px;"><i class="ph ph-trash" style="font-size:16px;"></i></button>
          </div>
        </div>
      </div>
    </div>
      <!-- adjustments -->
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:24px;">
        <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0 0 18px;display:flex;align-items:center;gap:9px;"><i class="ph ph-sliders-horizontal" style="color:#c39a4d;font-size:20px;"></i>Diskon, Pajak &amp; Pembayaran</h3>
        <div class="grid-cols-1-mobile" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Diskon (Rp)</label><input v-model="f.discount" type="number" placeholder="0" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;font-family:'IBM Plex Mono',monospace;"></div>
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Pajak / Service (%)</label><input v-model="f.taxPercent" type="number" placeholder="11" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;font-family:'IBM Plex Mono',monospace;"></div>
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">DP (%)</label><input v-model="f.dpPercent" type="number" placeholder="50" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;font-family:'IBM Plex Mono',monospace;"></div>
          <div style="grid-column:span 3;"><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Catatan / Syarat Pembayaran</label><textarea v-model="f.notes" rows="2" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:13.5px;color:#1a2235;background:#fff;outline:none;resize:vertical;line-height:1.5;"></textarea></div>
        </div>
      </div>
    </div>

    <!-- live summary -->
    <div style="position:sticky;top:96px;background:#fff;border:1px solid #e8e9ee;border-radius:16px;overflow:hidden;box-shadow:0 14px 36px -22px rgba(21,41,79,.3);">
      <div style="background:#13233f;padding:20px 22px;">
        <div style="font-size:12px;color:#9fabc4;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">Edit Pesanan</div>
        <div style="font-size:13px;color:#c39a4d;font-family:'IBM Plex Mono',monospace;margin-top:4px;">{{ invoiceNo }}</div>
      </div>
      <div style="padding:20px 22px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:11px;"><span style="font-size:13.5px;color:#5d6a82;">Subtotal</span><span style="font-size:13.5px;font-weight:600;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ t.tSubtotal }}</span></div>
        <div style="display:flex;justify-content:space-between;margin-bottom:11px;"><span style="font-size:13.5px;color:#5d6a82;">Diskon</span><span style="font-size:13.5px;font-weight:600;color:#c2603a;font-family:'IBM Plex Mono',monospace;">- {{ t.tDiscount }}</span></div>
        <div style="display:flex;justify-content:space-between;margin-bottom:11px;"><span style="font-size:13.5px;color:#5d6a82;">Pajak / Service ({{ f.taxPercent }}%)</span><span style="font-size:13.5px;font-weight:600;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ t.tTax }}</span></div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0;margin-top:6px;border-top:2px solid #eef0f3;"><span style="font-size:15px;font-weight:700;color:#13233f;">Total</span><span style="font-size:20px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ t.tTotal }}</span></div>
        <div style="background:#0d1b30;border-radius:11px;padding:13px 16px;margin:4px 0 10px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span style="font-size:12.5px;color:#9fabc4;">Total modal (HPP)</span><span style="font-size:13px;font-weight:600;color:#cdd6e6;font-family:'IBM Plex Mono',monospace;">{{ t.tCost }}</span></div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:1px solid #24365a;"><span style="font-size:12.5px;color:#f0d79a;font-weight:600;">Estimasi profit</span><span style="font-size:15px;font-weight:800;color:#7ed3a6;font-family:'IBM Plex Mono',monospace;">{{ t.tProfit }}</span></div>
          <div style="display:flex;justify-content:space-between;margin-top:7px;"><span style="font-size:12px;color:#9fabc4;">Margin</span><span style="font-size:12.5px;font-weight:700;color:#fff;font-family:'IBM Plex Mono',monospace;">{{ t.tMargin }}</span></div>
        </div>
        <div style="background:#fafbfc;border-radius:11px;padding:14px 16px;margin-top:4px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:9px;"><span style="font-size:13px;color:#5d6a82;">Per pax</span><span style="font-size:13px;font-weight:700;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ t.tPerPax }}</span></div>
          <div style="display:flex;justify-content:space-between;margin-bottom:9px;"><span style="font-size:13px;color:#5d6a82;">DP ({{ f.dpPercent }}%)</span><span style="font-size:13px;font-weight:700;color:#1f7a5c;font-family:'IBM Plex Mono',monospace;">{{ t.tDp }}</span></div>
          <div style="display:flex;justify-content:space-between;"><span style="font-size:13px;color:#5d6a82;">Sisa pelunasan</span><span style="font-size:13px;font-weight:700;color:#c2603a;font-family:'IBM Plex Mono',monospace;">{{ t.tSisa }}</span></div>
        </div>
        <button @click="saveOrder" :disabled="updateOrderMut.isPending.value" class="tr-btn" style="width:100%;margin-top:18px;background:#15294f;color:#fff;font-size:14.5px;font-weight:700;padding:14px;border-radius:11px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:9px;">
          <span v-if="updateOrderMut.isPending.value" style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;display:inline-block;"></span>
          <i v-else class="ph ph-floppy-disk" style="font-size:18px;color:#c39a4d;"></i>
          {{ updateOrderMut.isPending.value ? 'Menyimpan...' : 'Simpan Perubahan' }}
        </button>
        <button @click="cancelEdit" class="tr-btn" style="width:100%;margin-top:9px;background:#fff;color:#7a8499;font-size:13.5px;font-weight:600;padding:11px;border-radius:11px;border:1px solid #e2e4ea;cursor:pointer;">Batal</button>
      </div>
    </div>
  </div>
</template>
