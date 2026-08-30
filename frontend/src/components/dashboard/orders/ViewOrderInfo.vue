<script setup>
import { computed, ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { useVueTable, getCoreRowModel, getSortedRowModel, getFilteredRowModel, getPaginationRowModel } from '@tanstack/vue-table'
import { dashboardService } from '../../../services/dashboardService'
import { useToast } from '../../../composables/useToast'
import { useDashboardData } from '../../../composables/useDashboardData'

const queryClient = useQueryClient()
const toast = useToast()
const { fmtDate } = useDashboardData()

// ─── Data ───────────────────────────────────────────────────────────────────
const { data: infoData, isLoading } = useQuery({
  queryKey: ['order-infos'],
  queryFn: async () => {
    const res = await dashboardService.getOrderInfos()
    return res.data ?? []
  }
})

const rawData = computed(() =>
  (infoData.value ?? []).map(i => ({
    id:          i.id,
    group_name:  i.group_name  || '-',
    pic_name:    i.pic_name    || '-',
    contact_info:i.contact_info|| '-',
    email:       i.email       || '-',
    address:     i.address     || '-',
    notes:       i.notes       || '-',
    created_at:  fmtDate(i.created_at) || '-',
    raw: i,
  }))
)

// ─── Table ───────────────────────────────────────────────────────────────────
const columns = [
  { accessorKey: 'group_name',   header: 'Grup / Instansi',        enableSorting: true  },
  { accessorKey: 'pic_name',     header: 'PIC / Penanggung Jawab', enableSorting: true  },
  { accessorKey: 'contact_info', header: 'No. HP / WhatsApp',      enableSorting: false },
  { accessorKey: 'email',        header: 'Email',                   enableSorting: false },
]

const search  = ref('')
const sorting = ref([])
const pagination = ref({ pageIndex: 0, pageSize: 10 })

const table = useVueTable({
  get data() { return rawData.value },
  columns,
  getCoreRowModel:       getCoreRowModel(),
  getSortedRowModel:     getSortedRowModel(),
  getFilteredRowModel:   getFilteredRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  state: {
    get sorting()     { return sorting.value },
    get pagination()  { return pagination.value },
    get globalFilter(){ return search.value },
  },
  onSortingChange:      u => { sorting.value    = typeof u === 'function' ? u(sorting.value)    : u },
  onPaginationChange:   u => { pagination.value = typeof u === 'function' ? u(pagination.value) : u },
  onGlobalFilterChange: u => { search.value     = typeof u === 'function' ? u(search.value)     : u },
  globalFilterFn: (row, _col, val) => {
    const s = String(val).toLowerCase()
    const o = row.original
    return [o.group_name, o.pic_name, o.contact_info, o.email]
      .some(f => String(f).toLowerCase().includes(s))
  },
})

const sortIcon = col => {
  const s = sorting.value.find(x => x.id === col)
  if (!s) return 'ph-arrows-down-up'
  return s.desc ? 'ph-arrow-down' : 'ph-arrow-up'
}

// ─── Form Modal (Create / Edit) ───────────────────────────────────────────────
const formModal  = ref(false)
const isEditing  = ref(false)
const editTarget = ref(null)
const emptyForm  = () => ({ group_name: '', pic_name: '', contact_info: '', email: '', address: '', notes: '' })
const form       = ref(emptyForm())

const openCreate = () => {
  isEditing.value  = false
  editTarget.value = null
  form.value       = emptyForm()
  formModal.value  = true
}

const openEdit = item => {
  isEditing.value  = true
  editTarget.value = item
  form.value = {
    group_name:   item.group_name   === '-' ? '' : item.group_name,
    pic_name:     item.pic_name     === '-' ? '' : item.pic_name,
    contact_info: item.contact_info === '-' ? '' : item.contact_info,
    email:        item.email        === '-' ? '' : item.email,
    address:      item.address      === '-' ? '' : item.address,
    notes:        item.notes        === '-' ? '' : item.notes,
  }
  formModal.value = true
}

const closeForm = () => {
  formModal.value  = false
  editTarget.value = null
}

// Create mutation
const createMut = useMutation({
  mutationFn: dashboardService.createOrderInfo,
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['order-infos'] })
    closeForm()
    toast.success('Informasi pesanan berhasil ditambahkan.')
  },
  onError: () => toast.error('Gagal menyimpan informasi pesanan.'),
})

// Update mutation
const updateMut = useMutation({
  mutationFn: dashboardService.updateOrderInfo,
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['order-infos'] })
    closeForm()
    toast.success('Informasi pesanan berhasil diperbarui.')
  },
  onError: () => toast.error('Gagal memperbarui informasi pesanan.'),
})

const submitForm = () => {
  if (!form.value.group_name.trim()) {
    toast.error('Nama grup wajib diisi.')
    return
  }
  if (isEditing.value && editTarget.value) {
    updateMut.mutate({ id: editTarget.value.id, infoData: form.value })
  } else {
    createMut.mutate(form.value)
  }
}

const isPending = computed(() => createMut.isPending.value || updateMut.isPending.value)

// ─── Delete Modal ─────────────────────────────────────────────────────────────
const deleteModal  = ref(false)
const deleteTarget = ref(null)

const confirmDelete = item => {
  deleteTarget.value = item
  deleteModal.value  = true
}
const cancelDelete = () => {
  deleteModal.value  = false
  deleteTarget.value = null
}

const deleteMut = useMutation({
  mutationFn: dashboardService.deleteOrderInfo,
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['order-infos'] })
    cancelDelete()
    toast.success('Informasi pesanan berhasil dihapus.')
  },
  onError: () => toast.error('Gagal menghapus informasi pesanan.'),
})

const doDelete = () => {
  if (deleteTarget.value) deleteMut.mutate(deleteTarget.value.id)
}
</script>

<template>
  <div class="p-mobile" style="padding:30px 32px;">

    <!-- ── Page Header (sama seperti Daftar Pesanan) ── -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px;gap:20px;flex-wrap:wrap;">
      <div>
        <h1 style="font-size:24px;font-weight:800;color:#13233f;margin:0 0 6px;">Informasi Pesanan</h1>
        <p style="font-size:14px;color:#5d6a82;margin:0;">Data informasi pemesan, grup, PIC, dan kontak.</p>
      </div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <button @click="openCreate" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:13.5px;font-weight:700;padding:10px 20px;border-radius:10px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
          <i class="ph ph-plus" style="font-size:16px;"></i>
          Tambah Info
        </button>
      </div>
    </div>

    <!-- ── Tabel Card ── -->
    <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;">

      <!-- Sub-header: judul + search di kanan -->
      <div style="padding:18px 22px;border-bottom:1px solid #eef0f3;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">
          Semua Info
          <span style="color:#9aa0ad;font-weight:600;">({{ table.getFilteredRowModel().rows.length }})</span>
        </h3>
        <div style="position:relative;">
          <i class="ph ph-magnifying-glass" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:15px;color:#9aa0ad;pointer-events:none;"></i>
          <input
            v-model="search"
            placeholder="Cari grup / PIC / kontak..."
            style="padding:9px 12px 9px 34px;border:1px solid #d8dce4;border-radius:9px;font-size:13px;color:#1a2235;background:#fff;outline:none;width:230px;"
          >
        </div>
      </div>

      <!-- Loading state -->
      <div v-if="isLoading" style="padding:60px;text-align:center;color:#9aa0ad;">
        <i class="ph ph-circle-notch" style="font-size:28px;animation:spin 1s linear infinite;display:block;margin-bottom:8px;"></i>
        Memuat data...
      </div>

      <!-- Table -->
      <div v-else class="table-scroll">
        <table style="width:100%;border-collapse:collapse;min-width:900px;">
          <thead>
            <tr style="background:#fafbfc;border-bottom:1.5px solid #e2e4ea;">
              <th v-for="header in table.getFlatHeaders()" :key="header.id"
                :style="{ cursor: header.column.getCanSort() ? 'pointer' : 'default', padding: '13px 20px', textAlign: 'left', fontSize: '11px', fontWeight: '700', color: '#9aa0ad', textTransform: 'uppercase', letterSpacing: '.04em' }"
                @click="header.column.getToggleSortingHandler()?.($event)">
                <div style="display:flex;align-items:center;gap:5px;">
                  {{ header.column.columnDef.header }}
                  <i v-if="header.column.getCanSort()" :class="['ph', sortIcon(header.id)]" style="font-size:12px;color:#c2c8d4;"></i>
                </div>
              </th>
              <th style="padding:13px 20px;text-align:right;font-size:11px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!table.getRowModel().rows.length">
              <td colspan="6" style="padding:50px;text-align:center;color:#9aa0ad;font-size:14px;">
                <i class="ph ph-magnifying-glass" style="font-size:32px;display:block;margin-bottom:8px;color:#c2c8d4;"></i>
                Belum ada data informasi pesanan.
              </td>
            </tr>
            <tr v-for="row in table.getRowModel().rows" :key="row.id"
              style="border-bottom:1px solid #f1f2f5;transition:background .15s;"
              class="tr-hover">
              <!-- Grup -->
              <td style="padding:15px 20px;">
                <div style="font-size:13.5px;font-weight:700;color:#13233f;">{{ row.original.group_name }}</div>
              </td>
              <!-- PIC -->
              <td style="padding:15px 20px;">
                <span style="font-size:13px;color:#49556c;font-weight:600;">{{ row.original.pic_name }}</span>
              </td>
              <!-- Kontak -->
              <td style="padding:15px 20px;">
                <span style="font-size:13px;color:#49556c;font-family:'IBM Plex Mono',monospace;">{{ row.original.contact_info }}</span>
              </td>
              <!-- Email -->
              <td style="padding:15px 20px;">
                <span style="font-size:12.5px;color:#5d6a82;">{{ row.original.email }}</span>
              </td>
              <!-- Aksi -->
              <td style="padding:15px 20px;text-align:right;">
                <div style="display:flex;gap:6px;justify-content:flex-end;">
                  <button @click="openEdit(row.original.raw)" class="tr-btn"
                    style="background:#eef3fb;color:#15294f;border:1px solid #d6e1f2;font-size:12.5px;font-weight:700;padding:7px 13px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                    <i class="ph ph-pencil-simple" style="font-size:13px;"></i>Edit
                  </button>
                  <button @click="confirmDelete(row.original.raw)" class="tr-btn"
                    style="background:#fdf0ed;color:#c2603a;border:1px solid #f0d0c8;font-size:12.5px;font-weight:700;padding:7px 13px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                    <i class="ph ph-trash" style="font-size:13px;"></i>Hapus
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div style="padding:14px 22px;border-top:1px solid #eef0f3;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <span style="font-size:13px;color:#5d6a82;">
          Menampilkan {{ table.getRowModel().rows.length }} dari {{ table.getFilteredRowModel().rows.length }} data
        </span>
        <div style="display:flex;align-items:center;gap:6px;">
          <button class="tr-btn" @click="table.previousPage()" :disabled="!table.getCanPreviousPage()"
            style="background:#fff;border:1px solid #d8dce4;border-radius:8px;padding:7px 12px;cursor:pointer;font-size:13px;color:#5d6a82;display:flex;align-items:center;gap:4px;"
            :style="{ opacity: table.getCanPreviousPage() ? 1 : 0.4 }">
            <i class="ph ph-caret-left" style="font-size:14px;"></i>
          </button>
          <template v-for="page in table.getPageCount()" :key="page">
            <button class="tr-btn" @click="table.setPageIndex(page - 1)"
              style="min-width:34px;height:34px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;border:none;"
              :style="{ background: table.getState().pagination.pageIndex === page - 1 ? '#15294f' : 'transparent', color: table.getState().pagination.pageIndex === page - 1 ? '#fff' : '#5d6a82' }">
              {{ page }}
            </button>
          </template>
          <button class="tr-btn" @click="table.nextPage()" :disabled="!table.getCanNextPage()"
            style="background:#fff;border:1px solid #d8dce4;border-radius:8px;padding:7px 12px;cursor:pointer;font-size:13px;color:#5d6a82;display:flex;align-items:center;gap:4px;"
            :style="{ opacity: table.getCanNextPage() ? 1 : 0.4 }">
            <i class="ph ph-caret-right" style="font-size:14px;"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         Modal Tambah / Edit
    ══════════════════════════════════════════════════════════ -->
    <div v-if="formModal" style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:16px;">
      <div style="position:absolute;inset:0;background:rgba(13,27,48,.5);backdrop-filter:blur(3px);" @click="closeForm"></div>
      <div style="position:relative;background:#fff;border-radius:18px;width:100%;max-width:560px;max-height:calc(100vh - 32px);box-shadow:0 24px 70px rgba(13,27,48,.3);display:flex;flex-direction:column;overflow:hidden;">

        <!-- Header modal -->
        <div style="background:linear-gradient(135deg,#15294f,#0d1b30);padding:16px 22px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0;">
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="width:38px;height:38px;border-radius:11px;background:rgba(195,154,77,.18);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="ph ph-users-three" style="font-size:20px;color:#c39a4d;"></i>
            </span>
            <div>
              <h4 style="font-size:15.5px;font-weight:800;color:#fff;margin:0;">
                {{ isEditing ? 'Edit Informasi Pesanan' : 'Tambah Informasi Pesanan' }}
              </h4>
              <p style="font-size:11.5px;color:#aeb8cc;margin:2px 0 0;">Isi data grup, PIC, dan kontak pemesan</p>
            </div>
          </div>
          <button @click="closeForm" class="tr-btn" style="background:rgba(255,255,255,.1);border:none;cursor:pointer;color:#fff;padding:6px;border-radius:8px;">
            <i class="ph ph-x" style="font-size:17px;"></i>
          </button>
        </div>

        <!-- Body modal -->
        <div style="padding:22px;display:flex;flex-direction:column;gap:16px;overflow-y:auto;flex:1;">

          <!-- Grup -->
          <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">
              Nama Grup / Instansi <span style="color:#c2603a;">*</span>
            </label>
            <input v-model="form.group_name" placeholder="cth. PT. Maju Bersama" maxlength="255"
              style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;">
          </div>

          <!-- PIC + Kontak -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">PIC / Penanggung Jawab</label>
              <input v-model="form.pic_name" placeholder="cth. Budi Santoso" maxlength="255"
                style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:13.5px;color:#1a2235;background:#fff;outline:none;">
            </div>
            <div>
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">No. HP / WhatsApp</label>
              <input v-model="form.contact_info" placeholder="cth. 0812xxxxxxxx" maxlength="100"
                style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:13.5px;color:#1a2235;background:#fff;outline:none;">
            </div>
          </div>

          <!-- Email -->
          <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Email</label>
            <input v-model="form.email" type="email" placeholder="cth. budi@email.com" maxlength="255"
              style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:13.5px;color:#1a2235;background:#fff;outline:none;">
          </div>

          <!-- Alamat -->
          <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Alamat</label>
            <textarea v-model="form.address" rows="2" placeholder="cth. Jl. Sudirman No. 1, Jakarta Pusat"
              style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:13.5px;color:#1a2235;background:#fff;outline:none;resize:vertical;line-height:1.5;"></textarea>
          </div>

          <!-- Catatan -->
          <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Catatan</label>
            <textarea v-model="form.notes" rows="2" placeholder="Catatan tambahan mengenai pemesan..."
              style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:13.5px;color:#1a2235;background:#fff;outline:none;resize:vertical;line-height:1.5;"></textarea>
          </div>

          <!-- Footer -->
          <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:4px;border-top:1px solid #eef0f3;margin-top:4px;">
            <button @click="closeForm" class="tr-btn"
              style="background:#fff;color:#5f6b80;border:1px solid #e2e4ea;font-size:13px;font-weight:600;padding:9px 18px;border-radius:9px;cursor:pointer;">Batal</button>
            <button @click="submitForm" :disabled="isPending" class="tr-btn"
              style="background:#15294f;color:#fff;border:none;font-size:13px;font-weight:700;padding:9px 18px;border-radius:9px;cursor:pointer;display:flex;align-items:center;gap:7px;">
              <i v-if="isPending" class="ph ph-circle-notch" style="font-size:15px;animation:spin 1s linear infinite;"></i>
              <i v-else class="ph ph-check-circle" style="font-size:15px;color:#7ed3a6;"></i>
              {{ isPending ? 'Menyimpan...' : (isEditing ? 'Perbarui' : 'Simpan') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         Modal Hapus
    ══════════════════════════════════════════════════════════ -->
    <div v-if="deleteModal" style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;">
      <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);" @click="cancelDelete"></div>
      <div style="position:relative;background:#fff;border-radius:16px;padding:32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
          <div style="width:44px;height:44px;border-radius:12px;background:#fdf0ed;display:flex;align-items:center;justify-content:center;">
            <i class="ph ph-warning" style="font-size:22px;color:#c2603a;"></i>
          </div>
          <div>
            <h4 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Hapus Informasi Pesanan?</h4>
            <p style="font-size:13px;color:#8a93a5;margin:2px 0 0;">Tindakan ini tidak dapat dibatalkan.</p>
          </div>
        </div>
        <div v-if="deleteTarget" style="background:#fafbfc;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
          <div style="font-size:14px;font-weight:700;color:#13233f;">{{ deleteTarget.group_name }}</div>
          <div style="font-size:12px;color:#9aa0ad;margin-top:2px;">
            {{ deleteTarget.pic_name || '-' }} · {{ deleteTarget.contact_info || '-' }}
          </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button @click="cancelDelete" class="tr-btn"
            style="background:#fff;color:#5f6b80;border:1px solid #e2e4ea;font-size:13.5px;font-weight:600;padding:10px 20px;border-radius:9px;cursor:pointer;">Batal</button>
          <button @click="doDelete" :disabled="deleteMut.isPending.value" class="tr-btn"
            style="background:#c2603a;color:#fff;border:none;font-size:13.5px;font-weight:700;padding:10px 20px;border-radius:9px;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <i v-if="deleteMut.isPending.value" class="ph ph-circle-notch" style="font-size:16px;animation:spin 1s linear infinite;"></i>
            <i v-else class="ph ph-trash" style="font-size:15px;"></i>
            {{ deleteMut.isPending.value ? 'Menghapus...' : 'Hapus' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
