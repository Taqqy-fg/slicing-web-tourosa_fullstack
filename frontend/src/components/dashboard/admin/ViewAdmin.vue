<script setup>
import { computed, ref, watch } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { useVueTable, getCoreRowModel, getSortedRowModel, getFilteredRowModel, getPaginationRowModel } from '@tanstack/vue-table'
import { dashboardService } from '../../../services/dashboardService'
import { useToast } from '../../../composables/useToast'
import { useAuthStore } from '../../../stores/authStore'

const auth = useAuthStore()
const queryClient = useQueryClient()
const toast = useToast()

const canManageAdmins = computed(() => auth.hasPermission('admins.view'))

const { data: adminsData, isLoading } = useQuery({
  queryKey: ['admins'],
  queryFn: dashboardService.getAdmins,
  select: (res) => res.admins || [],
  enabled: canManageAdmins
})

const { data: rolesData } = useQuery({
  queryKey: ['roles'],
  queryFn: dashboardService.getRoles,
  select: (res) => res.roles || [],
  enabled: canManageAdmins
})

const { data: permissionsData } = useQuery({
  queryKey: ['allPermissions'],
  queryFn: dashboardService.getAllPermissions,
  select: (res) => res.permissions || [],
  enabled: canManageAdmins
})

const allRoles = computed(() => rolesData.value || [])
const allPermissions = computed(() => permissionsData.value || [])
const permGroups = computed(() => {
  const g = {}
  for (const p of allPermissions.value) {
    if (!g[p.group]) g[p.group] = []
    g[p.group].push(p)
  }
  return g
})
const admins = computed(() => adminsData.value || [])

// --- Table data ---
const rawData = computed(() => admins.value.map(a => {
  const isSA = !!a.is_superadmin
  const roleName = (a.roles || []).length > 0 ? a.roles[0].label : (isSA ? 'Super Admin' : 'Admin')
  const directCount = (a.direct_permissions || []).length
  return {
    name: a.name,
    email: a.email,
    role: roleName,
    roleBg: isSA ? 'rgba(195,154,77,.15)' : '#eef3fb',
    roleColor: isSA ? '#b9892f' : '#3a5a8a',
    hasCustom: directCount > 0,
    directCount,
    isSelf: a.id === auth.user?.id,
    raw: a
  }
}))

const columns = [
  { accessorKey: 'name', header: 'Nama', enableSorting: true },
  { accessorKey: 'email', header: 'Email', enableSorting: true },
  { accessorKey: 'role', header: 'Role', enableSorting: true },
]

const search = ref('')
const sorting = ref([])
const pagination = ref({ pageIndex: 0, pageSize: 10 })

const table = useVueTable({
  get data() { return rawData.value },
  columns,
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  state: {
    get sorting() { return sorting.value },
    get pagination() { return pagination.value },
    get globalFilter() { return search.value },
  },
  onSortingChange: (updater) => { sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater },
  onPaginationChange: (updater) => { pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater },
  onGlobalFilterChange: (updater) => { search.value = typeof updater === 'function' ? updater(search.value) : updater },
  globalFilterFn: (row, _columnId, filterValue) => {
    const s = String(filterValue).toLowerCase()
    return row.original.name.toLowerCase().includes(s) || row.original.email.toLowerCase().includes(s)
  },
})

const sortIcon = (col) => {
  const s = sorting.value.find(x => x.id === col)
  if (!s) return 'ph-arrows-down-up'
  return s.desc ? 'ph-arrow-down' : 'ph-arrow-up'
}

// --- Form modal ---
const showForm = ref(false)
const editingId = ref(null)
const saving = ref(false)
const form = ref({ name: '', email: '', password: '', password_confirmation: '', role_id: null, direct_permission_ids: [] })
const showPassword = ref(false)
const showPasswordConfirm = ref(false)

function openCreate() {
  editingId.value = null
  form.value = { name: '', email: '', password: '', password_confirmation: '', role_id: null, direct_permission_ids: [] }
  showForm.value = true
}

function openEdit(admin) {
  editingId.value = admin.id
  form.value = {
    name: admin.name,
    email: admin.email,
    password: '',
    password_confirmation: '',
    role_id: (admin.roles || []).length > 0 ? admin.roles[0].id : null,
    direct_permission_ids: (admin.direct_permissions || []).map(p => p.id)
  }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
}

const saveMut = useMutation({
  mutationFn: async () => {
    saving.value = true
    const payload = {
      name: form.value.name,
      email: form.value.email,
      role_id: form.value.role_id,
      direct_permission_ids: form.value.direct_permission_ids
    }
    if (form.value.password) {
      payload.password = form.value.password
      payload.password_confirmation = form.value.password_confirmation
    }
    if (editingId.value) {
      return await dashboardService.updateAdmin({ id: editingId.value, adminData: payload })
    }
    return await dashboardService.createAdmin(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['admins'] })
    toast.success(editingId.value ? 'Admin berhasil diperbarui.' : 'Admin berhasil ditambahkan.')
    closeForm()
  },
  onError: (e) => {
    toast.error(
      e.response?.data?.message ||
      e.response?.data?.errors?.email?.[0] ||
      e.response?.data?.errors?.password?.[0] ||
      'Gagal menyimpan admin.'
    )
  },
  onSettled: () => { saving.value = false }
})

function save() {
  saveMut.mutate()
}

// --- Delete confirmation ---
const deleteModal = ref(false)
const deleteTarget = ref(null)

const deleteMut = useMutation({
  mutationFn: (id) => dashboardService.deleteAdmin(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['admins'] })
    deleteModal.value = false
    deleteTarget.value = null
    toast.success('Admin berhasil dihapus.')
  },
  onError: (e) => {
    toast.error(e.response?.data?.message || e.response?.data?.errors?.email?.[0] || 'Gagal menghapus admin.')
  }
})

function confirmDelete(admin) {
  if (admin.id === auth.user?.id) {
    toast.error('Anda tidak dapat menghapus akun Anda sendiri.')
    return
  }
  deleteTarget.value = admin
  deleteModal.value = true
}

function doDelete() {
  if (deleteTarget.value) {
    deleteMut.mutate(deleteTarget.value.id)
  }
}

function cancelDelete() {
  deleteModal.value = false
  deleteTarget.value = null
}

function toggleRole(roleId) {
  form.value.role_id = form.value.role_id === roleId ? null : roleId
}

function toggleDirectPerm(permId) {
  if (rolePermIds.value.has(permId)) return
  const idx = form.value.direct_permission_ids.indexOf(permId)
  if (idx === -1) {
    form.value.direct_permission_ids.push(permId)
  } else {
    form.value.direct_permission_ids.splice(idx, 1)
  }
}

const selectedRole = computed(() => {
  if (!form.value.role_id) return null
  return allRoles.value.find(r => r.id === form.value.role_id) || null
})

const isSuperAdminRole = computed(() => selectedRole.value?.name === 'super_admin')

const rolePermIds = computed(() => {
  if (!selectedRole.value) return new Set()
  return new Set((selectedRole.value.permissions || []).map(p => p.id))
})

function permStatus(permId) {
  if (rolePermIds.value.has(permId)) return 'role'
  if (form.value.direct_permission_ids.includes(permId)) return 'custom'
  return 'none'
}
</script>

<template>
  <div class="p-mobile" style="padding:30px 32px;">
    <div v-if="!canManageAdmins" style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:40px;text-align:center;color:#8a93a5;">
      <i class="ph ph-warning-circle" style="font-size:34px;color:#c39a4d;display:block;margin-bottom:10px;"></i>
      Anda tidak memiliki izin untuk mengakses menu Admin.
    </div>
    <template v-else>
    <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;">
      <!-- header: title + search + add -->
      <div style="padding:18px 22px;border-bottom:1px solid #eef0f3;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Semua Admin <span style="color:#9aa0ad;font-weight:600;">({{ table.getFilteredRowModel().rows.length }})</span></h3>
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="position:relative;">
            <i class="ph ph-magnifying-glass" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:15px;color:#9aa0ad;pointer-events:none;"></i>
            <input v-model="search" placeholder="Cari nama / email..." style="padding:9px 12px 9px 34px;border:1px solid #d8dce4;border-radius:9px;font-size:13px;color:#1a2235;background:#fff;outline:none;width:220px;">
          </div>
          <button v-if="auth.hasPermission('admins.create')" @click="openCreate" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:13px;font-weight:700;padding:9px 16px;border-radius:9px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
            <i class="ph ph-plus" style="font-size:15px;color:#c39a4d;"></i>Tambah
          </button>
        </div>
      </div>

      <!-- table -->
      <div class="table-scroll">
        <div class="min-w-table">
          <div class="table-header-mobile" style="display:grid;grid-template-columns:1.4fr 1.6fr .9fr 1.6fr;gap:12px;padding:12px 22px;background:#fafbfc;font-size:11.5px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;">
            <span v-for="header in table.getHeaderGroups()[0].headers" :key="header.id"
              :style="{ cursor: header.column.getCanSort() ? 'pointer' : 'default' }"
              @click="header.column.getToggleSortingHandler()?.($event)">
              {{ header.column.columnDef.header }}
              <i v-if="header.column.getCanSort()" :class="['ph', sortIcon(header.column.id)]" style="font-size:12px;vertical-align:middle;margin-left:3px;"></i>
            </span>
            <span style="text-align:right;">Aksi</span>
          </div>

          <div v-if="isLoading" style="padding:40px 22px;text-align:center;color:#9aa0ad;font-size:13.5px;">
            <i class="ph ph-circle-notch" style="font-size:20px;animation:spin 1s linear infinite;"></i> Memuat...
          </div>

          <template v-else>
          <div v-for="row in table.getRowModel().rows" :key="row.id" class="table-row-mobile" style="display:grid;grid-template-columns:1.4fr 1.6fr .9fr 1.6fr;gap:12px;padding:15px 22px;border-top:1px solid #f1f2f5;align-items:center;">
            <div class="col-full-mobile" style="min-width:0;display:flex;align-items:center;gap:10px;">
              <div style="width:36px;height:36px;border-radius:50%;background:#15294f;color:#c39a4d;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;flex-shrink:0;">
                {{ (row.original.name || '?').charAt(0).toUpperCase() }}
              </div>
              <div style="font-size:14px;font-weight:700;color:#13233f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ row.original.name }}</div>
            </div>
            <span class="col-half-mobile" style="font-size:13.5px;color:#5d6a82;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ row.original.email }}</span>
            <span class="col-half-mobile">
              <span :style="{ color: row.original.roleColor, background: row.original.roleBg }" style="font-size:11.5px;font-weight:700;padding:5px 10px;border-radius:7px;">{{ row.original.role }}</span>
              <span v-if="row.original.hasCustom" style="display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:700;padding:4px 8px;border-radius:7px;background:rgba(195,154,77,.12);color:#b9892f;margin-left:5px;">
                <i class="ph ph-plug" style="font-size:10px;"></i>{{ row.original.directCount }}
              </span>
            </span>
            <span class="col-full-mobile" style="text-align:right;display:flex;gap:6px;justify-content:flex-end;">
              <button v-if="auth.hasPermission('admins.update')" @click="openEdit(row.original.raw)" class="tr-btn" style="background:#eef3fb;color:#15294f;border:1px solid #d6e1f2;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-pencil-simple" style="font-size:14px;color:#15294f;"></i>Ubah</button>
              <button v-if="auth.hasPermission('admins.delete')" @click="confirmDelete(row.original.raw)" class="tr-btn" style="background:#fdf0ed;color:#c2603a;border:1px solid #f0d0c8;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-trash" style="font-size:14px;"></i>Hapus</button>
            </span>
          </div>

          <div v-if="table.getRowModel().rows.length === 0" style="padding:40px 22px;text-align:center;">
            <i class="ph ph-users-three" style="font-size:32px;color:#c2c8d4;display:block;margin-bottom:8px;"></i>
            <div style="font-size:14px;color:#9aa0ad;">Belum ada akun admin.</div>
          </div>
          </template>
        </div>
      </div>

      <!-- pagination -->
      <div style="padding:14px 22px;border-top:1px solid #eef0f3;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <span style="font-size:13px;color:#5d6a82;">
          Menampilkan {{ table.getRowModel().rows.length }} dari {{ table.getFilteredRowModel().rows.length }} admin
        </span>
        <div style="display:flex;align-items:center;gap:6px;">
          <button class="tr-btn" @click="table.previousPage()" :disabled="!table.getCanPreviousPage()" style="background:#fff;border:1px solid #d8dce4;border-radius:8px;padding:7px 12px;cursor:pointer;font-size:13px;color:#5d6a82;display:flex;align-items:center;gap:4px;" :style="{ opacity: table.getCanPreviousPage() ? 1 : 0.4 }">
            <i class="ph ph-caret-left" style="font-size:14px;"></i>
          </button>
          <template v-for="page in table.getPageCount()" :key="page">
            <button class="tr-btn" @click="table.setPageIndex(page - 1)"
              style="min-width:34px;height:34px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;border:none;"
              :style="{
                background: table.getState().pagination.pageIndex === page - 1 ? '#15294f' : 'transparent',
                color: table.getState().pagination.pageIndex === page - 1 ? '#fff' : '#5d6a82'
              }">
              {{ page }}
            </button>
          </template>
          <button class="tr-btn" @click="table.nextPage()" :disabled="!table.getCanNextPage()" style="background:#fff;border:1px solid #d8dce4;border-radius:8px;padding:7px 12px;cursor:pointer;font-size:13px;color:#5d6a82;display:flex;align-items:center;gap:4px;" :style="{ opacity: table.getCanNextPage() ? 1 : 0.4 }">
            <i class="ph ph-caret-right" style="font-size:14px;"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showForm" style="position:fixed;inset:0;background:rgba(15,23,42,.45);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px;" @click.self="closeForm">
      <div style="background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="padding:24px 24px 0;">
          <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0 0 16px;display:flex;align-items:center;gap:9px;">
            <i class="ph ph-user-circle" style="color:#c39a4d;font-size:20px;"></i>
            {{ editingId ? 'Ubah Admin' : 'Tambah Admin' }}
          </h3>
        </div>

        <div style="flex:1;overflow-y:auto;padding:0 24px;">
          <div style="display:flex;flex-direction:column;gap:14px;">
            <div>
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Nama Lengkap</label>
              <input v-model="form.name" placeholder="Nama admin" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;">
            </div>
            <div>
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Email</label>
              <input v-model="form.email" type="email" placeholder="admin@tourosa.id" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;">
            </div>
            <div>
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">
                Password {{ editingId ? '(opsional, kosongkan jika tidak diubah)' : '' }}
              </label>
              <div style="position:relative;">
                <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Minimal 6 karakter" style="width:100%;padding:11px 38px 11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;">
                <button type="button" @click="showPassword = !showPassword" tabindex="-1" style="position:absolute;right:8px;bottom:8px;background:none;border:none;cursor:pointer;padding:4px;display:flex;align-items:center;color:#9aa3b2;font-size:16px;transition:color .2s;"><i :class="showPassword ? 'ph ph-eye-slash' : 'ph ph-eye'"></i></button>
              </div>
            </div>
            <div v-if="!editingId || form.password">
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Konfirmasi Password</label>
              <div style="position:relative;">
                <input v-model="form.password_confirmation" :type="showPasswordConfirm ? 'text' : 'password'" placeholder="Ulangi password" style="width:100%;padding:11px 38px 11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;">
                <button type="button" @click="showPasswordConfirm = !showPasswordConfirm" tabindex="-1" style="position:absolute;right:8px;bottom:8px;background:none;border:none;cursor:pointer;padding:4px;display:flex;align-items:center;color:#9aa3b2;font-size:16px;transition:color .2s;"><i :class="showPasswordConfirm ? 'ph ph-eye-slash' : 'ph ph-eye'"></i></button>
              </div>
            </div>
            <!-- Role Assignment -->
            <div v-if="allRoles.length > 0">
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:8px;">Role</label>
              <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <button v-for="role in allRoles" :key="role.id" @click="toggleRole(role.id)" type="button"
                  :style="{
                    background: form.role_id === role.id
                      ? (role.name === 'super_admin' ? 'rgba(194,96,58,.12)' : 'rgba(195,154,77,.15)')
                      : '#f5f6f8',
                    color: form.role_id === role.id
                      ? (role.name === 'super_admin' ? '#c2603a' : '#b9892f')
                      : '#5d6a82',
                    border: form.role_id === role.id
                      ? (role.name === 'super_admin' ? '1.5px solid #c2603a' : '1.5px solid #c39a4d')
                      : '1.5px solid #e2e4ea',
                  }"
                  style="font-size:12.5px;font-weight:600;padding:7px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .15s;">
                  <i :class="role.name === 'super_admin' ? 'ph ph-shield-star' : (form.role_id === role.id ? 'ph ph-check-circle' : 'ph ph-circle')" style="font-size:14px;"></i>
                  {{ role.label }}
                </button>
              </div>
            </div>

            <!-- Custom Permissions -->
            <div v-if="!isSuperAdminRole && allPermissions.length > 0">
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:8px;">
                Custom Permissions
              </label>
              <div v-if="selectedRole" style="background:#f5f7fb;border:1px solid #e8e9ee;border-radius:10px;padding:10px 14px;margin-bottom:10px;display:flex;align-items:center;gap:8px;">
                <i class="ph ph-info" style="font-size:14px;color:#3a5a8a;flex-shrink:0;"></i>
                <span style="font-size:11.5px;color:#5d6a82;line-height:1.45;">
                  Role <strong style="color:#13233f;">{{ selectedRole.label }}</strong> sudah memberikan
                  <strong style="color:#13233f;">{{ (selectedRole.permissions || []).length }}</strong> permission (ditampilkan abu-abu di bawah). Pilih permission tambahan di luar role jika diperlukan.
                </span>
              </div>
              <div v-else style="background:#fff9f0;border:1px solid #f0e4cd;border-radius:10px;padding:10px 14px;margin-bottom:10px;display:flex;align-items:center;gap:8px;">
                <i class="ph ph-warning-circle" style="font-size:14px;color:#c39a4d;flex-shrink:0;"></i>
                <span style="font-size:11.5px;color:#5d6a82;line-height:1.45;">
                  Belum ada role dipilih. Pilih role terlebih dahulu agar permission bawaan role tidak perlu dipilih ulang.
                </span>
              </div>
              <div style="display:flex;flex-direction:column;gap:8px;">
                <div v-for="(perms, groupName) in permGroups" :key="groupName"
                  style="border:1px solid #e8e9ee;border-radius:10px;overflow:hidden;">
                  <div style="display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid #eef0f3;background:#fafbfc;">
                    <span style="font-size:12.5px;font-weight:700;color:#13233f;">{{ groupName }}</span>
                    <span style="font-size:10.5px;color:#9aa0ad;margin-left:auto;font-weight:600;">
                      {{ perms.filter(p => permStatus(p.id) !== 'none').length }}/{{ perms.length }}
                    </span>
                  </div>
                  <div style="padding:8px 14px;display:flex;flex-wrap:wrap;gap:5px;">
                    <button v-for="perm in perms" :key="perm.id" type="button" @click="toggleDirectPerm(perm.id)"
                      :disabled="permStatus(perm.id) === 'role'"
                      :title="permStatus(perm.id) === 'role' ? 'Dari role ' + (selectedRole?.label || '') : ''"
                      :style="{
                        background: permStatus(perm.id) === 'role' ? '#f0f2f5' : permStatus(perm.id) === 'custom' ? '#15294f' : '#fff',
                        color: permStatus(perm.id) === 'role' ? '#9aa0ad' : permStatus(perm.id) === 'custom' ? '#fff' : '#5d6a82',
                        border: permStatus(perm.id) === 'role' ? '1px solid #e8e9ee' : permStatus(perm.id) === 'custom' ? '1px solid #15294f' : '1px solid #e2e4ea',
                        cursor: permStatus(perm.id) === 'role' ? 'not-allowed' : 'pointer',
                        opacity: permStatus(perm.id) === 'role' ? 0.7 : 1,
                      }"
                      style="font-size:11px;font-weight:600;padding:5px 10px;border-radius:7px;transition:all .15s;display:inline-flex;align-items:center;gap:4px;">
                      <i :class="permStatus(perm.id) === 'role' ? 'ph ph-lock-key' : permStatus(perm.id) === 'custom' ? 'ph ph-check' : 'ph ph-circle'" style="font-size:11px;"></i>
                      {{ perm.label }}
                    </button>
                  </div>
                </div>
              </div>
              <div v-if="form.direct_permission_ids.length > 0" style="margin-top:6px;font-size:11.5px;color:#8a93a5;">
                {{ form.direct_permission_ids.length }} custom permission ditambahkan (di luar role)
              </div>
            </div>
          </div>
        </div>

        <div style="padding:16px 24px 24px;border-top:1px solid #eef0f3;display:flex;align-items:center;justify-content:flex-end;gap:12px;">
          <button @click="closeForm" class="tr-btn" style="background:#f1f2f5;color:#5d6a82;border:none;font-size:13.5px;font-weight:700;padding:11px 18px;border-radius:10px;cursor:pointer;">Batal</button>
          <button @click="save" :disabled="saving" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:13.5px;font-weight:700;padding:11px 20px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:7px;" :style="{ opacity: saving ? 0.7 : 1 }">
            <i v-if="saving" class="ph ph-circle-notch" style="font-size:16px;animation:spin 1s linear infinite;"></i>
            <i v-else class="ph ph-floppy-disk" style="font-size:16px;color:#c39a4d;"></i>
            {{ saving ? 'Menyimpan...' : (editingId ? 'Simpan Perubahan' : 'Tambah Admin') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deleteModal" style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;">
      <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);" @click="cancelDelete"></div>
      <div style="position:relative;background:#fff;border-radius:16px;padding:32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
          <div style="width:44px;height:44px;border-radius:12px;background:#fdf0ed;display:flex;align-items:center;justify-content:center;">
            <i class="ph ph-warning" style="font-size:22px;color:#c2603a;"></i>
          </div>
          <div>
            <h4 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Hapus Admin?</h4>
            <p style="font-size:13px;color:#8a93a5;margin:2px 0 0;">Tindakan ini tidak dapat dibatalkan.</p>
          </div>
        </div>
        <div v-if="deleteTarget" style="background:#fafbfc;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
          <div style="font-size:14px;font-weight:700;color:#13233f;">{{ deleteTarget.name }}</div>
          <div style="font-size:12px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;margin-top:2px;">{{ deleteTarget.email }}</div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button @click="cancelDelete" class="tr-btn" style="background:#fff;color:#5f6b80;border:1px solid #e2e4ea;font-size:13.5px;font-weight:600;padding:10px 20px;border-radius:9px;cursor:pointer;">Batal</button>
          <button @click="doDelete" :disabled="deleteMut.isPending.value" class="tr-btn" style="background:#c2603a;color:#fff;border:none;font-size:13.5px;font-weight:700;padding:10px 20px;border-radius:9px;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <i v-if="deleteMut.isPending.value" class="ph ph-circle-notch" style="font-size:16px;animation:spin 1s linear infinite;"></i>
            <i v-else class="ph ph-trash" style="font-size:15px;"></i>
            {{ deleteMut.isPending.value ? 'Menghapus...' : 'Hapus' }}
          </button>
        </div>
      </div>
    </div>
    </template>
  </div>
</template>
