<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'

defineProps({
  pageTitle: String,
  pageSub: String,
  todayF: String,
  goNew: Function
})
const emit = defineEmits(['toggle-sidebar'])

const router = useRouter()
const auth = useAuthStore()

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <header class="topbar-mobile" data-print="hide" style="background:#fff;border-bottom:1px solid #e8e9ee;padding:18px 32px;display:flex;align-items:center;justify-content:space-between;gap:20px;position:sticky;top:0;z-index:20;">
    <div style="display:flex;align-items:center;gap:16px;">
      <button class="show-mobile-flex" @click="emit('toggle-sidebar')" style="background:none;border:none;color:#13233f;cursor:pointer;padding:4px;display:flex;align-items:center;justify-content:center;">
        <i class="ph ph-list" style="font-size:24px;"></i>
      </button>
      <div>
        <h1 style="font-size:21px;font-weight:800;color:#13233f;margin:0;letter-spacing:-.01em;">{{ pageTitle }}</h1>
        <div style="font-size:13px;color:#7a8499;margin-top:2px;">{{ pageSub }}</div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="display:flex;align-items:center;gap:8px;background:#f4f5f8;border:1px solid #e8e9ee;border-radius:10px;padding:9px 13px;font-size:13px;color:#7a8499;font-family:'IBM Plex Mono',monospace;"><i class="ph ph-calendar-blank" style="font-size:15px;"></i>{{ todayF }}</div>
      <button @click="goNew" class="tr-btn" style="background:#15294f;color:#fff;font-size:13.5px;font-weight:700;padding:11px 18px;border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;"><i class="ph ph-plus" style="font-size:16px;"></i>Buat Pesanan</button>
      <button @click="handleLogout" class="tr-btn" style="background:#f4f5f8;color:#7a8499;font-size:13.5px;font-weight:600;padding:11px 14px;border-radius:10px;border:1px solid #e8e9ee;cursor:pointer;display:flex;align-items:center;gap:6px;" title="Keluar">
        <i class="ph ph-sign-out" style="font-size:16px;"></i>
        <span class="hide-mobile">Keluar</span>
      </button>
    </div>
  </header>
</template>
