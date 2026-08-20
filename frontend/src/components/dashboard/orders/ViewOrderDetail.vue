<script setup>
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDashboardStore } from '../../../stores/dashboardStore'
import { useDashboardData } from '../../../composables/useDashboardData'

const store = useDashboardStore()
const route = useRoute()
const router = useRouter()
const { fmt, fmtDate, calc, statusMeta } = useDashboardData()

watch(() => route.params.id, (id) => {
  const decoded = id ? decodeURIComponent(id) : null
  if (decoded && (!store.activeInvoice || store.activeInvoice.no !== decoded)) {
    store.findOrderById(decoded)
  }
}, { immediate: true })

const detailData = computed(() => {
  if (!store.activeInvoice) return null
  const o = store.activeInvoice; const c = calc(o); const m = statusMeta(o.status);
  return {
    no: o.no, dateF: fmtDate(o.date), group: o.group, pic: o.pic || '-', contact: o.contact || '-', dest: o.dest || '-',
    tripF: (o.depart ? fmtDate(o.depart) : '-') + (o.ret ? '  –  ' + fmtDate(o.ret) : ''), paxF: (o.pax || '-') + ' pax',
    statusLabel: o.status, statusBg: m.bg, statusColor: m.color,
    revenueF: fmt(c.grandTotal), costF: fmt(c.totalCost), totalExpensesF: fmt(c.totalExpenses), profitF: fmt(c.profit), marginF: Math.round(c.marginPct) + '%',
    subtotalF: fmt(c.subtotal), discountF: fmt(c.discountAmount),
    discountLabel: c.discountType === '%' ? 'Diskon (' + (Number(o.discount) || 0) + '%)' : 'Diskon',
    serviceFeeF: fmt(c.serviceFee), hasServiceFee: Number(o.serviceFee) > 0,
    taxF: fmt(c.tax), taxPercentF: String(c.taxPercent),
    grandTotalF: fmt(c.grandTotal), perPaxF: fmt(c.perPax), dpPercentF: String(c.dpPercent), dpF: fmt(c.dp), dpDueDateF: fmtDate(o.dpDueDate), hasDpDueDate: !!o.dpDueDate, sisaF: fmt(c.sisa),
    profitColor: c.profit >= 0 ? '#1f7a5c' : '#c2603a'
  }
})

const detailItems = computed(() => {
  if (!store.activeInvoice) return []
  const o = store.activeInvoice; const c = calc(o);
  return c.items.map((it, i) => ({
    no: i + 1, desc: (it.desc || '').trim() || it.cat,
    cat: (it.vendor && it.vendor.trim()) ? it.cat + ' · ' + it.vendor : it.cat,
    tripType: it.tripType || '',
    dest: it.dest || '', depart: it.depart ? fmtDate(it.depart) : '', ret: it.ret ? fmtDate(it.ret) : '',
    qtyF: String(it.qty || 0),
    costF: fmt(it.unitCost), markupCostF: fmt(it.markupCost),
    priceF: fmt(it.unitPrice), markupPriceF: fmt(it.markupPrice),
    lineSellF: fmt(it.line), lineProfitF: fmt(it.lineProfit),
    profitColor: it.lineProfit >= 0 ? '#1f7a5c' : '#c2603a'
  }))
})

const detailExpenses = computed(() => {
  if (!store.activeInvoice) return []
  return (store.activeInvoice.expenses || []).map((e, idx) => ({
    idx, label: e.label, amount: e.amount, amountF: fmt(e.amount),
    onLabel: ev => { store.updateExpense(idx, 'label', ev.target.value) }, 
    onAmount: ev => { store.updateExpense(idx, 'amount', ev.target.value) }, 
    onRemove: () => { store.removeExpenseFromInvoice(idx) }
  }))
})
const addExpense = () => { store.addExpenseToInvoice() }

const detailTerms = computed(() => {
  if (!store.activeInvoice) return []
  const o = store.activeInvoice; const c = calc(o);
  return (o.terms || []).map((tm, idx) => ({
    idx, label: tm.label, percent: tm.percent, due: tm.due, dueF: tm.due ? fmtDate(tm.due) : '—',
    amountF: fmt(c.grandTotal * (Number(tm.percent) || 0) / 100),
    onLabel: ev => { store.updateTerm(idx, 'label', ev.target.value) }, 
    onPercent: ev => { store.updateTerm(idx, 'percent', ev.target.value) }, 
    onDue: ev => { store.updateTerm(idx, 'due', ev.target.value) }, 
    onRemove: () => { store.removeTermFromInvoice(idx) }
  }))
})
const addTerm = () => { store.addTermToInvoice() }

const termSummary = computed(() => {
  if (!store.activeInvoice) return {}
  const o = store.activeInvoice; const c = calc(o);
  const tPct = (o.terms || []).reduce((a, tm) => a + (Number(tm.percent) || 0), 0)
  return { totalPctF: tPct + '%', totalAmtF: fmt(c.grandTotal * tPct / 100), ok: tPct === 100, balanced: tPct === 100, remPctF: (100 - tPct) + '%', hasTerms: (o.terms || []).length > 0 }
})

const detail = detailData
const invoiceId = computed(() => store.activeInvoice?.no || (route.params.id ? decodeURIComponent(route.params.id) : ''))
const goList = () => router.push('/orders')
const goInvoiceFromDetail = () => router.push('/orders/invoice/' + encodeURIComponent(invoiceId.value))
const goEditFromDetail = () => router.push('/orders/edit/' + encodeURIComponent(invoiceId.value))
</script>

<template>
  <div v-if="detail" class="p-mobile grid-cols-1-mobile" style="padding:30px 32px;display:flex;flex-direction:column;gap:18px;">
    <nav style="display:flex;align-items:center;gap:6px;font-size:13px;flex-wrap:wrap;">
      <a @click.prevent="goList" href="#" style="color:#5d6a82;text-decoration:none;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;"><i class="ph ph-list-checks" style="font-size:15px;"></i>Daftar Pesanan</a>
      <i class="ph ph-caret-right" style="color:#c2c8d4;font-size:13px;"></i>
      <span style="color:#13233f;font-weight:700;">Detail Pesanan</span>
    </nav>
    <div style="display:flex;justify-content:flex-end;align-items:center;gap:12px;">
      <button @click="goEditFromDetail" class="tr-btn" style="background:#fff;color:#15294f;font-size:13.5px;font-weight:700;padding:10px 20px;border-radius:10px;border:1px solid #d6e1f2;cursor:pointer;display:flex;align-items:center;gap:8px;"><i class="ph ph-pencil-simple" style="font-size:17px;color:#15294f;"></i>Edit Pesanan</button>
      <button @click="goInvoiceFromDetail" class="tr-btn" style="background:#15294f;color:#fff;font-size:13.5px;font-weight:700;padding:10px 20px;border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;"><i class="ph ph-receipt" style="font-size:17px;color:#c39a4d;"></i>Lihat / Cetak Invoice</button>
    </div>

    <div style="background:linear-gradient(135deg,#15294f,#0d1b30);border-radius:18px;padding:28px 30px;color:#fff;">
      <div class="flex-col-mobile" style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;">
        <div>
          <div style="font-size:12.5px;color:#c39a4d;font-family:'IBM Plex Mono',monospace;margin-bottom:8px;">{{ detail.no }}</div>
          <h2 style="font-size:24px;font-weight:800;margin:0 0 8px;letter-spacing:-.01em;">{{ detail.group }}</h2>
          <div style="font-size:13.5px;color:#aeb8cc;">PIC: {{ detail.pic }} · {{ detail.contact }}</div>
        </div>
        <span :style="{ color: detail.statusColor, background: detail.statusBg }" style="font-size:12px;font-weight:700;padding:7px 14px;border-radius:8px;white-space:nowrap;">{{ detail.statusLabel }}</span>
      </div>
    </div>
    
    <div class="grid-cols-1-mobile" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:14px;padding:18px 20px;"><div style="font-size:12px;color:#7a8499;font-weight:600;margin-bottom:9px;">Pendapatan</div><div style="font-size:19px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ detail.revenueF }}</div></div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:14px;padding:18px 20px;"><div style="font-size:12px;color:#7a8499;font-weight:600;margin-bottom:9px;">Total Modal (HPP)</div><div style="font-size:19px;font-weight:800;color:#5d6a82;font-family:'IBM Plex Mono',monospace;">{{ detail.costF }}</div></div>
      <div style="background:linear-gradient(135deg,#15294f,#0d1b30);border-radius:14px;padding:18px 20px;"><div style="font-size:12px;color:#f0d79a;font-weight:600;margin-bottom:9px;">Estimasi Profit</div><div style="font-size:19px;font-weight:800;color:#7ed3a6;font-family:'IBM Plex Mono',monospace;">{{ detail.profitF }}</div></div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:14px;padding:18px 20px;"><div style="font-size:12px;color:#7a8499;font-weight:600;margin-bottom:9px;">Margin</div><div style="font-size:19px;font-weight:800;color:#1f7a5c;font-family:'IBM Plex Mono',monospace;">{{ detail.marginF }}</div></div>
    </div>
    
    <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;overflow:hidden;">
      <div style="padding:24px;border-bottom:1px solid #eef0f3;display:flex;align-items:center;gap:9px;flex-wrap:wrap;"><i class="ph ph-list-checks" style="color:#c39a4d;font-size:19px;"></i><h3 style="font-size:15px;font-weight:700;color:#13233f;margin:0;">Rincian Item &amp; Margin</h3><span style="font-size:11px;color:#9aa0ad;background:#f4f5f8;padding:4px 10px;border-radius:6px;">Internal — tidak tampil di invoice</span></div>
      <div class="table-scroll">
        <div class="min-w-table" style="min-width:1100px;">
          <div class="table-header-mobile" style="display:grid;grid-template-columns:30px 1fr 50px 70px 80px 80px 116px 116px 120px 116px;gap:8px;padding:11px 24px;background:#fafbfc;font-size:10.5px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.03em;">
            <span>#</span><span>Deskripsi</span><span>Tipe</span><span>Qty</span><span>Beli (HPP)</span><span>Markup Beli</span><span>Jual</span><span>Markup Jual</span><span style="text-align:right;">Subtotal</span><span style="text-align:right;">Profit</span>
          </div>
          <div v-for="(it, idx) in detailItems" :key="idx" class="table-row-mobile" style="display:grid;grid-template-columns:30px 1fr 50px 70px 80px 80px 116px 116px 120px 116px;gap:8px;padding:13px 24px;border-top:1px solid #f1f2f5;align-items:flex-start;">
            <span class="hide-mobile" style="font-size:13px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;">{{ it.no }}</span>
            <div class="col-full-mobile"><div style="font-size:13.5px;font-weight:600;color:#13233f;">{{ it.desc }}</div><div style="font-size:11.5px;color:#9aa0ad;margin-top:2px;">{{ it.cat }}<span v-if="it.dest"> · {{ it.dest }}</span></div></div>
            <span class="col-half-mobile" style="font-size:11.5px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;">{{ it.tripType || '-' }}</span>
            <span class="col-third-mobile text-right-mobile" style="font-size:13px;color:#5d6a82;text-align:center;font-family:'IBM Plex Mono',monospace;">{{ it.qtyF }}</span>
            <span class="col-third-mobile text-right-mobile" style="font-size:12px;color:#9aa0ad;text-align:right;font-family:'IBM Plex Mono',monospace;">{{ it.costF }}</span>
            <span class="col-third-mobile text-right-mobile" style="font-size:12px;color:#9aa0ad;text-align:right;font-family:'IBM Plex Mono',monospace;">{{ it.markupCostF }}</span>
            <span class="col-third-mobile text-right-mobile" style="font-size:12.5px;color:#5d6a82;text-align:right;font-family:'IBM Plex Mono',monospace;">{{ it.priceF }}</span>
            <span class="col-third-mobile text-right-mobile" style="font-size:12.5px;color:#5d6a82;text-align:right;font-family:'IBM Plex Mono',monospace;">{{ it.markupPriceF }}</span>
            <span class="col-half-mobile text-right-mobile" style="font-size:13px;font-weight:700;color:#13233f;text-align:right;font-family:'IBM Plex Mono',monospace;">{{ it.lineSellF }}</span>
            <span class="col-half-mobile text-right-mobile" :style="{ color: it.profitColor }" style="font-size:13px;font-weight:700;text-align:right;font-family:'IBM Plex Mono',monospace;">{{ it.lineProfitF }}</span>
          </div>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;padding:24px;border-top:2px solid #eef0f3;background:#fafbfc;">
        <div style="width:320px;">
          <div style="display:flex;justify-content:space-between;padding:5px 0;"><span style="font-size:13px;color:#5d6a82;">Subtotal penjualan</span><span style="font-size:13px;font-weight:700;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ detail.subtotalF }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:5px 0;"><span style="font-size:13px;color:#5d6a82;">Total modal (HPP)</span><span style="font-size:13px;font-weight:600;color:#5d6a82;font-family:'IBM Plex Mono',monospace;">{{ detail.costF }}</span></div>
        </div>
      </div>
    </div>

    <!-- pengeluaran lainnya -->
    <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;overflow:hidden;">
      <div style="padding:24px;border-bottom:1px solid #eef0f3;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap;"><i class="ph ph-coins" style="color:#c39a4d;font-size:19px;"></i><h3 style="font-size:15px;font-weight:700;color:#13233f;margin:0;">Pengeluaran Lainnya</h3><span style="font-size:11px;color:#9aa0ad;background:#f4f5f8;padding:4px 10px;border-radius:6px;">Biaya operasional di luar HPP</span></div>
        <button @click="addExpense" class="tr-btn" style="background:#eef3fb;color:#15294f;border:1px solid #d6e1f2;font-size:13px;font-weight:700;padding:9px 14px;border-radius:9px;cursor:pointer;display:flex;align-items:center;gap:6px;"><i class="ph ph-plus" style="font-size:15px;"></i>Tambah Pengeluaran</button>
      </div>
      <div style="padding:14px 24px 8px;">
        <div class="table-scroll">
          <div style="min-width: 500px;">
            <div class="table-header-mobile" style="display:grid;grid-template-columns:1fr 34px 180px;gap:10px;padding:0 2px 8px;font-size:11px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.03em;">
              <span>Keterangan</span><span></span><span style="text-align:right;">Nominal</span>
            </div>
            <div v-for="(e, idx) in detailExpenses" :key="idx" class="table-row-mobile" style="display:grid;grid-template-columns:1fr 34px 180px;gap:10px;align-items:center;padding:5px 2px;">
              <input class="col-full-mobile" :value="e.label" @input="e.onLabel" placeholder="Keterangan Pengeluaran" style="width:100%;padding:9px 11px;border:1px solid #d8dce4;border-radius:8px;font-size:13px;color:#1a2235;background:#fff;outline:none;">
              <button class="del-btn-mobile tr-btn" @click="e.onRemove" style="background:none;border:none;cursor:pointer;color:#c2603a;display:flex;align-items:center;justify-content:center;padding:6px;border-radius:7px;"><i class="ph ph-trash" style="font-size:16px;"></i></button>
              <input class="col-full-mobile text-right-mobile" :value="e.amount" @input="e.onAmount" type="number" placeholder="Nominal Rp" style="width:100%;padding:9px 11px;border:1px solid #d8dce4;border-radius:8px;font-size:13px;color:#1a2235;background:#fff;outline:none;text-align:right;font-family:'IBM Plex Mono',monospace;">
            </div>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 2px 6px;margin-top:4px;border-top:1px solid #f1f2f5;"><span style="font-size:13px;color:#5d6a82;font-weight:600;">Total pengeluaran lainnya</span><span style="font-size:14px;font-weight:800;color:#c2603a;font-family:'IBM Plex Mono',monospace;">{{ detail.totalExpensesF }}</span></div>
      </div>
      <div style="display:flex;justify-content:flex-end;padding:18px 24px;border-top:2px solid #eef0f3;background:#fafbfc;">
        <div style="width:340px;">
          <div style="display:flex;justify-content:space-between;padding:5px 0;"><span style="font-size:13px;color:#5d6a82;">Subtotal penjualan</span><span style="font-size:13px;font-weight:600;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ detail.subtotalF }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:5px 0;"><span style="font-size:13px;color:#5d6a82;">{{ detail.discountLabel }}</span><span style="font-size:13px;font-weight:600;color:#c2603a;font-family:'IBM Plex Mono',monospace;">- {{ detail.discountF }}</span></div>
          <div v-if="detail.hasServiceFee" style="display:flex;justify-content:space-between;padding:5px 0;"><span style="font-size:13px;color:#5d6a82;">Service Fee</span><span style="font-size:13px;font-weight:600;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ detail.serviceFeeF }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #e2e4ea;"><span style="font-size:13px;color:#5d6a82;">Total modal (HPP)</span><span style="font-size:13px;font-weight:600;color:#5d6a82;font-family:'IBM Plex Mono',monospace;">- {{ detail.costF }}</span></div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:11px 0 2px;"><span style="font-size:14px;font-weight:800;color:#13233f;">Estimasi profit bersih</span><span :style="{ color: detail.profitColor }" style="font-size:18px;font-weight:800;font-family:'IBM Plex Mono',monospace;">{{ detail.profitF }}</span></div>
          <div style="display:flex;justify-content:space-between;align-items:center;"><span style="font-size:12px;color:#9aa0ad;">Margin</span><span style="font-size:12.5px;font-weight:700;color:#1f7a5c;font-family:'IBM Plex Mono',monospace;">{{ detail.marginF }}</span></div>
        </div>
      </div>
    </div>

    <div class="grid-cols-1-mobile" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:14px;padding:18px 20px;"><div style="font-size:12px;color:#7a8499;font-weight:600;margin-bottom:8px;">Grand Total</div><div style="font-size:17px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ detail.grandTotalF }}</div></div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:14px;padding:18px 20px;"><div style="font-size:12px;color:#7a8499;font-weight:600;margin-bottom:8px;">Sudah Dibayar (DP {{ detail.dpPercentF }}%)</div><div style="font-size:17px;font-weight:800;color:#1f7a5c;font-family:'IBM Plex Mono',monospace;">{{ detail.dpF }}</div><div v-if="detail.hasDpDueDate" style="font-size:11px;color:#9aa0ad;margin-top:4px;">Jatuh tempo: {{ detail.dpDueDateF }}</div></div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:14px;padding:18px 20px;"><div style="font-size:12px;color:#7a8499;font-weight:600;margin-bottom:8px;">Sisa Pelunasan</div><div style="font-size:17px;font-weight:800;color:#c2603a;font-family:'IBM Plex Mono',monospace;">{{ detail.sisaF }}</div></div>
    </div>

    <!-- termin pembayaran / split invoice -->
    <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;overflow:hidden;">
      <div style="padding:24px;border-bottom:1px solid #eef0f3;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap;"><i class="ph ph-list-numbers" style="color:#c39a4d;font-size:19px;"></i><h3 style="font-size:15px;font-weight:700;color:#13233f;margin:0;">Termin Pembayaran</h3><span style="font-size:11px;color:#9aa0ad;background:#f4f5f8;padding:4px 10px;border-radius:6px;">Split invoice — nominal dihitung dari grand total</span></div>
        <button @click="addTerm" class="tr-btn" style="background:#eef3fb;color:#15294f;border:1px solid #d6e1f2;font-size:13px;font-weight:700;padding:9px 14px;border-radius:9px;cursor:pointer;display:flex;align-items:center;gap:6px;"><i class="ph ph-plus" style="font-size:15px;"></i>Tambah Termin</button>
      </div>
      <div style="padding:14px 24px 10px;">
        <div class="table-scroll">
          <div style="min-width: 550px;">
            <div class="table-header-mobile" style="display:grid;grid-template-columns:1fr 150px 86px 140px 32px;gap:10px;padding:0 2px 8px;font-size:11px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.03em;">
              <span>Keterangan Termin</span><span>Jatuh Tempo</span><span style="text-align:center;">%</span><span style="text-align:right;">Nominal</span><span></span>
            </div>
            <div v-for="(tm, idx) in detailTerms" :key="idx" class="table-row-mobile" style="display:grid;grid-template-columns:1fr 150px 86px 140px 32px;gap:10px;align-items:center;padding:5px 2px;">
              <input class="col-full-mobile" :value="tm.label" @input="tm.onLabel" placeholder="Keterangan (cth. DP)" style="width:100%;padding:9px 11px;border:1px solid #d8dce4;border-radius:8px;font-size:13px;color:#1a2235;background:#fff;outline:none;">
              <input class="col-half-mobile" :value="tm.due" @input="tm.onDue" type="date" style="width:100%;padding:9px 9px;border:1px solid #d8dce4;border-radius:8px;font-size:12.5px;color:#1a2235;background:#fff;outline:none;">
              <input class="col-half-mobile" :value="tm.percent" @input="tm.onPercent" type="number" placeholder="Persentase (%)" style="width:100%;padding:9px 9px;border:1px solid #d8dce4;border-radius:8px;font-size:13px;color:#1a2235;background:#fff;outline:none;text-align:center;font-family:'IBM Plex Mono',monospace;">
              <span class="col-full-mobile text-right-mobile" style="font-size:13px;font-weight:700;color:#13233f;text-align:right;font-family:'IBM Plex Mono',monospace;">Rp: {{ tm.amountF }}</span>
              <button class="del-btn-mobile tr-btn" @click="tm.onRemove" style="background:none;border:none;cursor:pointer;color:#c2603a;display:flex;align-items:center;justify-content:center;padding:6px;border-radius:7px;"><i class="ph ph-trash" style="font-size:16px;"></i></button>
            </div>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 2px 6px;margin-top:4px;border-top:1px solid #f1f2f5;">
          <span style="font-size:12.5px;color:#5d6a82;">Total teralokasi <span style="font-weight:700;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ termSummary.totalPctF }}</span> · sisa <span style="font-weight:700;color:#c2603a;font-family:'IBM Plex Mono',monospace;">{{ termSummary.remPctF }}</span></span>
          <span style="font-size:14px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ termSummary.totalAmtF }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
