<script setup>
import { useToast } from '../composables/useToast'
const { toasts } = useToast()
</script>

<template>
  <div class="toast-container">
    <TransitionGroup name="toast">
      <div v-for="t in toasts" :key="t.id" :class="['toast-item', 'toast-' + t.type]">
        <i :class="t.type === 'success' ? 'ph-fill ph-check-circle' : t.type === 'error' ? 'ph-fill ph-warning-circle' : 'ph-fill ph-info'" style="font-size:18px;flex-shrink:0;"></i>
        <span>{{ t.message }}</span>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}
.toast-item {
  pointer-events: auto;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 18px;
  border-radius: 10px;
  font-size: 13.5px;
  font-weight: 600;
  box-shadow: 0 8px 24px -6px rgba(0,0,0,.2);
  max-width: 380px;
}
.toast-success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #15803d;
}
.toast-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
}
.toast-info {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
}

.toast-enter-active { animation: toast-in .3s ease; }
.toast-leave-active { animation: toast-out .3s ease; }

@keyframes toast-in {
  from { opacity: 0; transform: translateX(40px); }
  to { opacity: 1; transform: translateX(0); }
}
@keyframes toast-out {
  from { opacity: 1; transform: translateX(0); }
  to { opacity: 0; transform: translateX(40px); }
}
</style>
