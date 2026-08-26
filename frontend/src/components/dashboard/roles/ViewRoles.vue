<script setup>
import { computed, ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { dashboardService } from '../../../services/dashboardService'
import { useToast } from '../../../composables/useToast'
import { useAuthStore } from '../../../stores/authStore'

const auth = useAuthStore()
const queryClient = useQueryClient()
const toast = useToast()

const canManageRoles = computed(() => auth.hasAnyPermission(['roles.view', 'roles.create', 'roles.update', 'roles.delete']))

const { data: rolesData, isLoading } = useQuery({
  queryKey: ['roles'],
  queryFn: dashboardService.getRoles,
  select: (res) => res.roles || [],
  enabled: canManageRoles
})

const { data: permissionsData } = useQuery({
  queryKey: ['allPermissions'],
  queryFn: dashboardService.getAllPermissions,
  select: (res) => res.permissions || [],
  enabled: canManageRoles
})

const roles = computed(() => rolesData.value || [])
const allPermissions = computed(() => permissionsData.value || [])

const permissionGroups = computed(() => {
  const groups = {}
  for (const p of allPermissions.value) {
    if (!groups[p.group]) groups[p.group] = []
    groups[p.group].push(p)
  }
  return groups
})

// --- Form modal ---
const showForm = ref(false)
const editingId = ref(null)
const saving = ref(false)
const form = ref({ name: '', label: '', description: '', permission_ids: [] })

function openCreate() {
  editingId.value = null
  form.value = { name: '', label: '', description: '', permission_ids: [] }
  showForm.value = true
}

function openEdit(role) {
  editingId.value = role.id
  form.value = {
    name: role.name,
    label: role.label,
    description: role.description || '',
    permission_ids: (role.permissions || []).map(p => p.id)
  }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
}

function togglePermission(permId) {
  const idx = form.value.permission_ids.indexOf(permId)
  if (idx === -1) {
    form.value.permission_ids.push(permId)
  } else {
    form.value.permission_ids.splice(idx, 1)
  }
}

function toggleGroup(groupName) {
  const groupPerms = permissionGroups.value[groupName] || []
  const allSelected = groupPerms.every(p => form.value.permission_ids.includes(p.id))
  for (const p of groupPerms) {
    const idx = form.value.permission_ids.indexOf(p.id)
    if (allSelected) {
      if (idx !== -1) form.value.permission_ids.splice(idx, 1)
    } else {
      if (idx === -1) form.value.permission_ids.push(p.id)
    }
  }
}

function isGroupFullySelected(groupName) {
  const groupPerms = permissionGroups.value[groupName] || []
  return groupPerms.length > 0 && groupPerms.every(p => form.value.permission_ids.includes(p.id))
}

function isGroupPartiallySelected(groupName) {
  const groupPerms = permissionGroups.value[groupName] || []
  const selected = groupPerms.filter(p => form.value.permission_ids.includes(p.id))
  return selected.length > 0 && selected.length < groupPerms.length
}

const saveMut = useMutation({
  mutationFn: async () => {
    saving.value = true
    const payload = {
      name: form.value.name,
      label: form.value.label,
      description: form.value.description,
      permissions: form.value.permission_ids
    }
    if (editingId.value) {
      return await dashboardService.updateRole({ id: editingId.value, roleData: payload })
    }
    return await dashboardService.createRole(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['roles'] })
    toast.success(editingId.value ? 'Role berhasil diperbarui.' : 'Role berhasil ditambahkan.')
    closeForm()
  },
  onError: (e) => {
    toast.error(e.response?.data?.message || e.response?.data?.errors?.name?.[0] || 'Gagal menyimpan role.')
  },
  onSettled: () => { saving.value = false }
})

function save() { saveMut.mutate() }

// --- Delete ---
const deleteModal = ref(false)
const deleteTarget = ref(null)

const deleteMut = useMutation({
  mutationFn: (id) => dashboardService.deleteRole(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['roles'] })
    deleteModal.value = false
    deleteTarget.value = null
    toast.success('Role berhasil dihapus.')
  },
  onError: (e) => {
    toast.error(e.response?.data?.message || 'Gagal menghapus role.')
  }
})

function confirmDelete(role) {
  deleteTarget.value = role
  deleteModal.value = true
}

function doDelete() {
  if (deleteTarget.value) deleteMut.mutate(deleteTarget.value.id)
}

function cancelDelete() {
  deleteModal.value = false
  deleteTarget.value = null
}
</script>

<template>
  <div class="p-mobile" style="padding:30px 32px;">
    <div v-if="!canManageRoles" style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:40px;text-align:center;color:#8a93a5;">
      <i class="ph ph-warning-circle" style="font-size:34px;color:#c39a4d;display:block;margin-bottom:10px;"></i>
      Anda tidak memiliki izin untuk mengelola Roles.
    </div>
    <template v-else>
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
      <div>
        <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Semua Role <span style="color:#9aa0ad;font-weight:600;">({{ roles.length }})</span></h3>
        <p style="font-size:13px;color:#8a93a5;margin:4px 0 0;">Kelola role dan tentukan hak akses untuk setiap peran.</p>
      </div>
      <button v-if="auth.hasPermission('roles.create')" @click="openCreate" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:13px;font-weight:700;padding:9px 16px;border-radius:9px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
        <i class="ph ph-plus" style="font-size:15px;color:#c39a4d;"></i>Tambah Role
      </button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:40px;text-align:center;color:#9aa0ad;font-size:13.5px;">
      <i class="ph ph-circle-notch" style="font-size:20px;animation:spin 1s linear infinite;"></i> Memuat...
    </div>

    <!-- Role Cards -->
    <div v-else style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;">
      <div v-for="role in roles" :key="role.id"
        style="background:#fff;border:1px solid #e8e9ee;border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:12px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
              <h4 style="font-size:15px;font-weight:700;color:#13233f;margin:0;">{{ role.label }}</h4>
              <span v-if="role.is_system" style="font-size:10px;font-weight:700;color:#b9892f;background:rgba(195,154,77,.12);padding:3px 8px;border-radius:6px;text-transform:uppercase;letter-spacing:.03em;">System</span>
            </div>
            <div style="font-size:12px;color:#6b7590;font-family:'IBM Plex Mono',monospace;margin-bottom:2px;">{{ role.name }}</div>
            <div v-if="role.description" style="font-size:12.5px;color:#8a93a5;margin-top:4px;">{{ role.description }}</div>
          </div>
          <div style="display:flex;gap:4px;flex-shrink:0;">
            <button v-if="auth.hasPermission('roles.update')" @click="openEdit(role)" class="tr-btn" style="background:#eef3fb;color:#15294f;border:1px solid #d6e1f2;font-size:12px;font-weight:700;padding:7px 12px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
              <i class="ph ph-pencil-simple" style="font-size:13px;"></i>Ubah
            </button>
            <button v-if="auth.hasPermission('roles.delete') && !role.is_system" @click="confirmDelete(role)" class="tr-btn" style="background:#fdf0ed;color:#c2603a;border:1px solid #f0d0c8;font-size:12px;font-weight:700;padding:7px 12px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
              <i class="ph ph-trash" style="font-size:13px;"></i>
            </button>
          </div>
        </div>

        <!-- Permissions preview -->
        <div style="border-top:1px solid #f1f2f5;padding-top:12px;">
          <div style="font-size:11px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Hak Akses ({{ (role.permissions || []).length }})</div>
          <div style="display:flex;flex-wrap:wrap;gap:5px;">
            <span v-for="perm in (role.permissions || []).slice(0, 8)" :key="perm.id"
              style="font-size:11px;font-weight:600;color:#5d6a82;background:#f5f6f8;padding:4px 9px;border-radius:6px;">
              {{ perm.label }}
            </span>
            <span v-if="(role.permissions || []).length > 8" style="font-size:11px;font-weight:600;color:#8a93a5;background:#f5f6f8;padding:4px 9px;border-radius:6px;">
              +{{ role.permissions.length - 8 }} lagi
            </span>
          </div>
        </div>

        <!-- Users count -->
        <div v-if="(role.users || []).length > 0" style="font-size:11.5px;color:#8a93a5;display:flex;align-items:center;gap:5px;">
          <i class="ph ph-users" style="font-size:13px;"></i>
          {{ (role.users || []).length }} admin menggunakan role ini
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="roles.length === 0" style="grid-column:1/-1;background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:40px;text-align:center;">
        <i class="ph ph-shield-star" style="font-size:32px;color:#c2c8d4;display:block;margin-bottom:8px;"></i>
        <div style="font-size:14px;color:#9aa0ad;">Belum ada role.</div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showForm" style="position:fixed;inset:0;background:rgba(15,23,42,.45);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px;" @click.self="closeForm">
      <div style="background:#fff;border-radius:16px;width:100%;max-width:580px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="padding:24px 24px 0;">
          <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0 0 16px;display:flex;align-items:center;gap:9px;">
            <i class="ph ph-shield-star" style="color:#c39a4d;font-size:20px;"></i>
            {{ editingId ? 'Ubah Role' : 'Tambah Role' }}
          </h3>
        </div>

        <div style="flex:1;overflow-y:auto;padding:0 24px;">
          <div style="display:flex;flex-direction:column;gap:14px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Nama (slug)</label>
                <input v-model="form.name" placeholder="contoh: editor" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:13px;font-family:'IBM Plex Mono',monospace;color:#1a2235;background:#fff;outline:none;">
              </div>
              <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Label</label>
                <input v-model="form.label" placeholder="contoh: Editor" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;">
              </div>
            </div>
            <div>
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Deskripsi</label>
              <input v-model="form.description" placeholder="Deskripsi singkat role ini..." style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:13px;color:#1a2235;background:#fff;outline:none;">
            </div>

            <!-- Permission assignment by group -->
            <div>
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:8px;">
                Hak Akses
                <span style="font-weight:400;color:#9aa0ad;">— {{ form.permission_ids.length }} dipilih</span>
              </label>
              <div style="display:flex;flex-direction:column;gap:10px;">
                <div v-for="(perms, groupName) in permissionGroups" :key="groupName"
                  style="border:1px solid #e8e9ee;border-radius:10px;overflow:hidden;">
                  <!-- Group header -->
                  <button type="button" @click="toggleGroup(groupName)"
                    :style="{
                      background: isGroupFullySelected(groupName) ? 'rgba(195,154,77,.08)' : '#fafbfc',
                    }"
                    style="display:flex;align-items:center;gap:10px;width:100%;padding:10px 14px;border:none;border-bottom:1px solid #eef0f3;cursor:pointer;text-align:left;transition:all .15s;">
                    <div :style="{
                      background: isGroupFullySelected(groupName) ? '#15294f' : isGroupPartiallySelected(groupName) ? '#c39a4d' : '#e2e4ea',
                    }" style="width:20px;height:20px;border-radius:5px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s;">
                      <i v-if="isGroupFullySelected(groupName)" class="ph ph-check" style="font-size:13px;color:#fff;"></i>
                      <i v-else-if="isGroupPartiallySelected(groupName)" class="ph ph-minus" style="font-size:13px;color:#fff;"></i>
                    </div>
                    <span style="font-size:13px;font-weight:700;color:#13233f;">{{ groupName }}</span>
                    <span style="font-size:11px;color:#9aa0ad;margin-left:auto;font-weight:600;">{{ perms.filter(p => form.permission_ids.includes(p.id)).length }} / {{ perms.length }}</span>
                  </button>
                  <!-- Permissions in group -->
                  <div style="padding:10px 14px;display:flex;flex-wrap:wrap;gap:6px;">
                    <button v-for="perm in perms" :key="perm.id" type="button" @click="togglePermission(perm.id)"
                      :style="{
                        background: form.permission_ids.includes(perm.id) ? '#15294f' : '#fff',
                        color: form.permission_ids.includes(perm.id) ? '#fff' : '#5d6a82',
                        border: form.permission_ids.includes(perm.id) ? '1px solid #15294f' : '1px solid #e2e4ea',
                      }"
                      style="font-size:11.5px;font-weight:600;padding:6px 12px;border-radius:7px;cursor:pointer;transition:all .15s;display:inline-flex;align-items:center;gap:5px;">
                      <i :class="form.permission_ids.includes(perm.id) ? 'ph ph-check' : 'ph ph-circle'" style="font-size:12px;"></i>
                      {{ perm.label }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div style="padding:16px 24px 24px;border-top:1px solid #eef0f3;display:flex;align-items:center;justify-content:flex-end;gap:12px;">
          <button @click="closeForm" class="tr-btn" style="background:#f1f2f5;color:#5d6a82;border:none;font-size:13.5px;font-weight:700;padding:11px 18px;border-radius:10px;cursor:pointer;">Batal</button>
          <button @click="save" :disabled="saving" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:13.5px;font-weight:700;padding:11px 20px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:7px;" :style="{ opacity: saving ? 0.7 : 1 }">
            <i v-if="saving" class="ph ph-circle-notch" style="font-size:16px;animation:spin 1s linear infinite;"></i>
            <i v-else class="ph ph-floppy-disk" style="font-size:16px;color:#c39a4d;"></i>
            {{ saving ? 'Menyimpan...' : (editingId ? 'Simpan Perubahan' : 'Tambah Role') }}
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
            <h4 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Hapus Role?</h4>
            <p style="font-size:13px;color:#8a93a5;margin:2px 0 0;">Tindakan ini tidak dapat dibatalkan.</p>
          </div>
        </div>
        <div v-if="deleteTarget" style="background:#fafbfc;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
          <div style="font-size:14px;font-weight:700;color:#13233f;">{{ deleteTarget.label }}</div>
          <div style="font-size:12px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;margin-top:2px;">{{ deleteTarget.name }}</div>
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
