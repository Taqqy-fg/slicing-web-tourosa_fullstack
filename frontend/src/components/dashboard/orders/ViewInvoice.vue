<script setup>
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDashboardStore } from '../../../stores/dashboardStore'
import { useDashboardData } from '../../../composables/useDashboardData'
const props = defineProps({
  site: Object
})
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
const invData = computed(() => {
  if (!store.activeInvoice) return null
  const o = store.activeInvoice; const c = calc(o); const m = statusMeta(o.status)
  const invTerms = (o.terms || []).map((tm, i) => ({
    no: i + 1, label: tm.label || ('Termin ' + (i + 1)),
    dueF: tm.due ? fmtDate(tm.due) : '-', percentF: (Number(tm.percent) || 0) + '%',
    amountF: fmt(c.grandTotal * (Number(tm.percent) || 0) / 100)
  }))
  return {
    no: o.no, dateF: fmtDate(o.date), group: o.group, pic: o.pic || '-', contact: o.contact || '-',
    statusLabel: o.status, statusBg: m.bg, statusColor: m.color,
    subtotalF: fmt(c.subtotal), discountF: fmt(c.discountAmount),
    discountLabel: c.discountType === '%' ? 'Diskon (' + (Number(o.discount) || 0) + '%)' : 'Diskon',
    serviceFeeF: fmt(c.serviceFeeAmount), hasServiceFee: Number(o.serviceFee) > 0,
    taxPercentF: String(c.taxPercent), taxF: fmt(c.tax),
    grandTotalF: fmt(c.grandTotal), perPaxF: fmt(c.perPax),
    dpPercentF: String(c.dpPercent), dpF: fmt(c.dp), dpDueDateF: fmtDate(o.dpDueDate), hasDpDueDate: !!o.dpDueDate,
    sisaF: fmt(c.sisa),
    notes: o.notes || '-', invTerms, hasTerms: invTerms.length > 0
  }
})
const invItems = computed(() => {
  if (!store.activeInvoice) return []
  const o = store.activeInvoice; const c = calc(o);
  return c.items.map((it, i) => ({
    no: i + 1, desc: (it.desc || '').trim() || it.cat,
    cat: (it.vendor && it.vendor.trim()) ? it.cat + ' · ' + it.vendor : it.cat,
    tripType: it.tripType || '',
    qtyF: String(it.qty || 0), priceF: fmt(it.unitPrice), lineF: fmt(it.line)
  }))
})
const inv = invData
const invTerms = computed(() => invData.value?.invTerms || [])
const hasTerms = computed(() => invData.value?.hasTerms || false)
const siteAddress = computed(() => props.site?.address || store.site.address)
const siteEmail = computed(() => props.site?.email || store.site.email)
const waDisplay = computed(() => props.site?.waNumber || store.site.waNumber)
const goNew = () => router.push('/orders/new')
const goOrderList = () => router.push('/orders')
const goOrderDetail = () => router.push('/orders/detail/' + encodeURIComponent(store.activeInvoice?.no || route.params.id))
const goEditOrder = () => router.push('/orders/edit/' + encodeURIComponent(store.activeInvoice?.no || route.params.id))
const backFromInvoice = () => router.push('/orders/detail/' + encodeURIComponent(store.activeInvoice?.no || route.params.id))
const doPrint = () => window.print()
</script>
<template>
  <div class="p-mobile grid-cols-1-mobile" style="padding:30px 32px;display:flex;flex-direction:column;gap:18px;">
    <nav data-print="hide" style="display:flex;align-items:center;gap:6px;font-size:13px;flex-wrap:wrap;">
      <a @click.prevent="goOrderList" href="#" style="color:#5d6a82;text-decoration:none;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;"><i class="ph ph-list-checks" style="font-size:15px;"></i>Daftar Pesanan</a>
      <i class="ph ph-caret-right" style="color:#c2c8d4;font-size:13px;"></i>
      <a @click.prevent="goOrderDetail" href="#" style="color:#5d6a82;text-decoration:none;font-weight:600;cursor:pointer;">Detail Pesanan</a>
      <i class="ph ph-caret-right" style="color:#c2c8d4;font-size:13px;"></i>
      <span style="color:#13233f;font-weight:700;">Invoice</span>
    </nav>
    <div data-print="hide" style="display:flex;justify-content:flex-end;align-items:center;gap:12px;">
      <button @click="goEditOrder" class="tr-btn" style="background:#fff;color:#15294f;font-size:13.5px;font-weight:700;padding:10px 20px;border-radius:10px;border:1px solid #d6e1f2;cursor:pointer;display:flex;align-items:center;gap:8px;"><i class="ph ph-pencil-simple" style="font-size:17px;color:#15294f;"></i>Edit Invoice</button>
      <button @click="doPrint" class="tr-btn" style="background:#15294f;color:#fff;font-size:13.5px;font-weight:700;padding:10px 20px;border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;"><i class="ph ph-printer" style="font-size:17px;color:#c39a4d;"></i>Cetak / Simpan PDF</button>
    </div>
    <div v-if="inv" data-print="area" class="px-mobile py-mobile"
      style="max-width:820px;margin:0 auto;background:#fff;border:1px solid #e8e9ee;border-radius:14px;padding:48px 52px;box-shadow:0 18px 50px -28px rgba(21,41,79,.3);">
      <!-- inv header -->
      <div data-print="keep" class="flex-col-mobile"
        style="display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:26px;border-bottom:2px solid #13233f;gap:16px;">
        <div>
          <img src="/assets/tourosa-logo.png" alt="Tourosa"
            style="height:26px;width:auto;display:block;margin-bottom:14px;">
          <div style="font-size:12.5px;color:#5d6a82;line-height:1.6;">Tourosa Travel · {{ siteAddress }}<br>{{
            siteEmail }} · {{ waDisplay }}</div>
        </div>
        <div style="text-align:right;" :style="{ textAlign: 'left' }">
          <div style="font-size:30px;font-weight:800;color:#13233f;letter-spacing:.04em;">INVOICE</div>
          <div style="font-size:13px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;margin-top:6px;">{{ inv.no }}
          </div>
          <div
            style="display:inline-block;margin-top:10px;font-size:12px;font-weight:700;padding:6px 13px;border-radius:8px;"
            :style="{ color: inv.statusColor, background: inv.statusBg }">{{ inv.statusLabel }}</div>
        </div>
      </div>
      <!-- bill to -->
      <div data-print="keep" class="grid-cols-1-mobile"
        style="display:grid;grid-template-columns:1fr 1fr;gap:30px;padding:26px 0;border-bottom:1px solid #eef0f3;">
        <div>
          <div
            style="font-size:11px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.06em;margin-bottom:9px;">
            Ditagihkan Kepada</div>
          <div style="font-size:16px;font-weight:800;color:#13233f;margin-bottom:4px;">{{ inv.group }}</div>
          <div style="font-size:13px;color:#5d6a82;line-height:1.6;">PIC: {{ inv.pic }}<br>{{ inv.contact }}</div>
        </div>
        <div style="text-align:left;">
          <div style="display:flex;justify-content:flex-start;gap:30px;margin-bottom:10px;"><span
              style="font-size:12.5px;color:#8a93a5;">Tanggal Invoice</span><span
              style="font-size:12.5px;font-weight:700;color:#13233f;font-family:'IBM Plex Mono',monospace;min-width:120px;">{{
                inv.dateF }}</span></div>
        </div>
      </div>
      <!-- items table -->
      <div style="padding-top:22px;">
        <div class="table-scroll">
          <div>
            <div class="table-header-mobile"
              style="display:grid;grid-template-columns:30px 1fr 60px 130px 130px;gap:10px;padding:10px 0;border-bottom:1.5px solid #e2e4ea;font-size:11px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;">
              <span>#</span><span>Deskripsi</span><span style="text-align:center;">Qty</span><span
                style="text-align:right;">Harga</span><span style="text-align:right;">Jumlah</span>
            </div>
            <div v-for="(it, idx) in invItems" :key="idx" class="table-row-mobile"
              style="display:grid;grid-template-columns:30px 1fr 60px 130px 130px;gap:10px;padding:13px 0;border-bottom:1px solid #f1f2f5;align-items:flex-start;">
              <span class="hide-mobile" style="font-size:13px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;">{{
                it.no }}</span>
              <div class="col-full-mobile">
                <div style="font-size:13.5px;font-weight:600;color:#13233f;white-space:pre-line;">{{ it.desc }}</div>
                <div style="font-size:11.5px;color:#9aa0ad;margin-top:2px;">{{ it.cat }}<span v-if="it.tripType"> · {{ it.tripType }}</span></div>
              </div>
              <span class="col-third-mobile"
                style="font-size:13px;color:#5d6a82;text-align:center;font-family:'IBM Plex Mono',monospace;">{{ it.qtyF
                }}x</span>
              <span class="col-third-mobile text-right-mobile"
                style="font-size:13px;color:#5d6a82;text-align:right;font-family:'IBM Plex Mono',monospace;">{{
                  it.priceF }}</span>
              <span class="col-third-mobile text-right-mobile"
                style="font-size:13px;font-weight:700;color:#13233f;text-align:right;font-family:'IBM Plex Mono',monospace;">=
                {{ it.lineF }}</span>
            </div>
          </div>
        </div>
      </div>
      <!-- totals -->
      <div data-print="keep" class="flex-col-mobile"
        style="display:flex;justify-content:space-between;gap:40px;padding-top:24px;">
        <div style="flex:1;max-width:320px;">
          <div
            style="font-size:11px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.06em;margin-bottom:9px;">
            Catatan</div>
          <p style="font-size:12.5px;color:#5d6a82;line-height:1.6;margin:0 0 18px;">{{ inv.notes }}</p>
          <div style="background:#fafbfc;border:1px solid #eef0f3;border-radius:10px;padding:13px 15px;">
            <div
              style="font-size:11px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.05em;margin-bottom:7px;">
              Pembayaran</div>
            <div style="font-size:12.5px;color:#13233f;line-height:1.7;">Bank Central Asia (BCA)<br>No. Rek <span
                style="font-family:'IBM Plex Mono',monospace;font-weight:700;">1234-567-890</span><br>a.n. PT Tourosa
              Travel</div>
          </div>
        </div>
        <div style="width:300px;flex-shrink:0;">
          <div style="display:flex;justify-content:space-between;padding:8px 0;"><span
              style="font-size:13px;color:#5d6a82;">Subtotal</span><span
              style="font-size:13px;font-weight:600;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{
                inv.subtotalF }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:8px 0;"><span
              style="font-size:13px;color:#5d6a82;">{{ inv.discountLabel }}</span><span
              style="font-size:13px;font-weight:600;color:#c2603a;font-family:'IBM Plex Mono',monospace;">- {{
                inv.discountF }}</span></div>
          <div v-if="inv.hasServiceFee" style="display:flex;justify-content:space-between;padding:8px 0;"><span
              style="font-size:13px;color:#5d6a82;">Service Fee</span><span
              style="font-size:13px;font-weight:600;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{
                inv.serviceFeeF }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eef0f3;"><span
              style="font-size:13px;color:#5d6a82;">Pajak / Service ({{ inv.taxPercentF }}%)</span><span
              style="font-size:13px;font-weight:600;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ inv.taxF
              }}</span></div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0;"><span
              style="font-size:15px;font-weight:800;color:#13233f;">GRAND TOTAL</span><span
              style="font-size:20px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ inv.grandTotalF
              }}</span></div>
          <div style="background:#13233f;border-radius:11px;padding:14px 16px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:9px;"><span
                style="font-size:12px;color:#9fabc4;">Per pax</span><span
                style="font-size:12.5px;font-weight:700;color:#fff;font-family:'IBM Plex Mono',monospace;">{{
                  inv.perPaxF }}</span></div>
            <div style="display:flex;justify-content:space-between;margin-bottom:9px;"><span
                style="font-size:12px;color:#9fabc4;">DP ({{ inv.dpPercentF }}%)</span><span
                style="font-size:12.5px;font-weight:700;color:#7ed3a6;font-family:'IBM Plex Mono',monospace;">{{ inv.dpF
                }}</span></div>
            <div v-if="inv.hasDpDueDate" style="display:flex;justify-content:space-between;margin-bottom:9px;"><span
                style="font-size:12px;color:#9fabc4;">Jatuh Tempo</span><span
                style="font-size:12px;font-weight:600;color:#f0c98a;font-family:'IBM Plex Mono',monospace;">{{ inv.dpDueDateF }}</span></div>
            <div style="display:flex;justify-content:space-between;padding-top:9px;border-top:1px solid #24365a;"><span
                style="font-size:12px;color:#9fabc4;">Sisa pelunasan</span><span
                style="font-size:12.5px;font-weight:700;color:#f0c98a;font-family:'IBM Plex Mono',monospace;">{{
                  inv.sisaF }}</span></div>
          </div>
        </div>
      </div>
      <!-- jadwal termin -->
      <div v-if="hasTerms" data-print="keep" style="margin-top:26px;padding-top:22px;border-top:1px solid #eef0f3;">
        <div
          style="font-size:11px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
          Jadwal Pembayaran (Termin)</div>
        <div class="table-scroll">
          <div>
            <div class="table-header-mobile"
              style="display:grid;grid-template-columns:28px 1fr 150px 64px 150px;gap:10px;padding:8px 0;border-bottom:1.5px solid #e2e4ea;font-size:11px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;">
              <span>#</span><span>Termin</span><span>Jatuh Tempo</span><span style="text-align:center;">%</span><span
                style="text-align:right;">Nominal</span>
            </div>
            <div v-for="(tm, idx) in invTerms" :key="idx" class="table-row-mobile"
              style="display:grid;grid-template-columns:28px 1fr 150px 64px 150px;gap:10px;padding:11px 0;border-bottom:1px solid #f1f2f5;align-items:center;">
              <span class="hide-mobile" style="font-size:13px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;">{{
                tm.no }}</span>
              <span class="col-full-mobile" style="font-size:13px;font-weight:600;color:#13233f;">{{ tm.label }}</span>
              <span class="col-half-mobile"
                style="font-size:12.5px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;">
                <i class="ph ph-calendar"
                  style="font-size:12.5px;color:#5d6a82;vertical-align:middle;vertical-align:middle;padding-right:4px;"></i>{{
                tm.dueF }}</span>
              <span class="col-half-mobile"
                style="font-size:12.5px;color:#5d6a82;text-align:center;font-family:'IBM Plex Mono',monospace;">{{
                  tm.percentF }}</span>
              <span class="col-full-mobile text-right-mobile"
                style="font-size:13px;font-weight:700;color:#13233f;text-align:right;font-family:'IBM Plex Mono',monospace;">{{
                  tm.amountF }}</span>
            </div>
          </div>
        </div>
      </div>
      <!-- footer -->
      <div data-print="keep-footer" class="flex-col-mobile"
        style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:34px;padding-top:24px;border-top:1px solid #eef0f3;gap:20px;">
        <div style="font-size:11.5px;color:#9aa0ad;line-height:1.6;max-width:360px;">Invoice ini dihasilkan oleh sistem
          Tourosa dan sah tanpa tanda tangan basah. Terima kasih atas kepercayaan Anda.</div>
        <div style="text-align:left;">
          <div style="font-size:12.5px;color:#5d6a82;margin-bottom:34px;">Hormat kami,</div>
          <div style="font-size:14px;font-weight:800;color:#13233f;">Tourosa Travel</div>
        </div>
      </div>
    </div>
  </div>
</template>
