<script setup>
import { useAuthStore } from '../../../stores/authStore'

defineProps({
  navItems: Array,
  isOpen: Boolean
})

const emit = defineEmits(['close'])

const auth = useAuthStore()

const userInitial = (name) => {
  if (!name) return 'A'
  return name.charAt(0).toUpperCase()
}
</script>

<template>
  <aside class="dash-sidebar" :class="{ open: isOpen }" data-print="hide" style="width:252px;flex-shrink:0;background:#0d1b30;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;height:100dvh;">
    <div style="padding:24px 22px 22px;border-bottom:1px solid #1b2942;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <img src="/assets/tourosa-logo-white.png" alt="Tourosa" style="height:21px;width:auto;display:block;">
        <div style="font-size:11px;color:#7c89a3;font-weight:600;letter-spacing:.12em;text-transform:uppercase;margin-top:9px;">Admin Dashboard</div>
      </div>
      <button class="show-mobile-flex" @click="emit('close')" style="background:none;border:none;color:#fff;cursor:pointer;padding:4px;">
        <i class="ph ph-x" style="font-size:24px;"></i>
      </button>
    </div>
    <nav style="padding:16px 14px;display:flex;flex-direction:column;gap:4px;flex:1;overflow-y:auto;">
      <router-link v-for="(n, idx) in navItems" :key="idx" :to="n.route" @click="emit('close')" class="tr-nav" :style="{ background: n.bg, color: n.color, display:'flex', alignItems:'center', gap:'12px', padding:'11px 14px', border:'none', borderRadius:'10px', cursor:'pointer', textAlign:'left', width:'100%', fontSize:'14px', fontWeight:'600', textDecoration:'none' }">
        <i :class="['ph', n.icon]" style="font-size:19px;"></i>{{ n.label }}
      </router-link>
    </nav>
    <div style="padding:14px;border-top:1px solid #1b2942;">
      <router-link to="/" class="tr-nav" style="display:flex;align-items:center;gap:11px;padding:11px 14px;border:none;border-radius:10px;cursor:pointer;text-align:left;width:100%;background:transparent;color:#9aa6bd;font-size:13.5px;font-weight:600;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-decoration:none;"><i class="ph ph-globe-hemisphere-west" style="font-size:18px;flex-shrink:0;"></i>Lihat Website</router-link>
      <div v-if="auth.user" style="display:flex;align-items:center;gap:11px;padding:10px 12px;background:#142340;border-radius:11px;">
        <div style="width:34px;height:34px;border-radius:9px;background:#c39a4d;display:flex;align-items:center;justify-content:center;font-weight:800;color:#13233f;font-size:14px;flex-shrink:0;">{{ userInitial(auth.user.name) }}</div>
        <div style="line-height:1.3;min-width:0;">
          <div style="font-size:13px;font-weight:700;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ auth.user.name }}</div>
          <div style="font-size:11px;color:#7c89a3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ auth.user.email }}</div>
        </div>
      </div>
    </div>
  </aside>
</template>
