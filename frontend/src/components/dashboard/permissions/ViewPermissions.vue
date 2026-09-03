<script setup>
import { computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { dashboardService } from '../../../services/dashboardService'
import { useAuthStore } from '../../../stores/authStore'

const auth = useAuthStore()

const { data: permissionsData, isLoading } = useQuery({
  queryKey: ['permissions'],
  queryFn: dashboardService.getPermissions,
  select: (res) => res.permissions || [],
  enabled: computed(() => auth.hasPermission('permissions.view'))
})

const groups = computed(() => permissionsData.value || [])
</script>

<template>
  <div class="p-mobile" style="padding:30px 32px;">
    <div v-if="!auth.hasPermission('permissions.view')" style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:40px;text-align:center;color:#8a93a5;">
      <i class="ph ph-warning-circle" style="font-size:34px;color:#c39a4d;display:block;margin-bottom:10px;"></i>
      Anda tidak memiliki izin untuk melihat Permissions.
    </div>
    <template v-else>
    <!-- Header -->
    <div style="margin-bottom:20px;">
      <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Semua Permissions</h3>
      <p style="font-size:13px;color:#8a93a5;margin:4px 0 0;">Daftar lengkap hak akses yang tersedia di sistem.</p>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:40px;text-align:center;color:#9aa0ad;font-size:13.5px;">
      <i class="ph ph-circle-notch" style="font-size:20px;animation:spin 1s linear infinite;"></i> Memuat...
    </div>

    <!-- Permission Groups -->
    <div v-else style="display:flex;flex-direction:column;gap:16px;">
      <div v-for="group in groups" :key="group.group"
        style="background:#fff;border:1px solid #e8e9ee;border-radius:14px;overflow:hidden;">
        <!-- Group Header -->
        <div style="padding:14px 20px;background:#fafbfc;border-bottom:1px solid #f1f2f5;display:flex;align-items:center;gap:10px;">
          <i class="ph ph-folder-open" style="font-size:18px;color:#c39a4d;"></i>
          <h4 style="font-size:14px;font-weight:700;color:#13233f;margin:0;">{{ group.group }}</h4>
          <span style="font-size:11.5px;color:#9aa0ad;font-weight:600;background:#eef0f3;padding:3px 9px;border-radius:6px;">{{ group.items.length }}</span>
        </div>
        <!-- Permissions list -->
        <div style="padding:14px 20px;display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px;">
          <div v-for="perm in group.items" :key="perm.id"
            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f9fafb;border:1px solid #f1f2f5;border-radius:9px;">
            <div style="width:8px;height:8px;border-radius:50%;background:#c39a4d;flex-shrink:0;"></div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:13px;font-weight:600;color:#13233f;">{{ perm.label }}</div>
              <div style="font-size:11px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;margin-top:1px;">{{ perm.name }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="groups.length === 0" style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:40px;text-align:center;">
        <i class="ph ph-key" style="font-size:32px;color:#c2c8d4;display:block;margin-bottom:8px;"></i>
        <div style="font-size:14px;color:#9aa0ad;">Belum ada permission.</div>
      </div>
    </div>
    </template>
  </div>
</template>
