<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const visible = ref(false)

const onScroll = () => {
  visible.value = window.scrollY > 400
}

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => window.addEventListener('scroll', onScroll))
onUnmounted(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
  <Transition name="scroll-btn">
    <button
      v-if="visible"
      @click="scrollToTop"
      class="scroll-to-top"
      aria-label="Kembali ke atas"
    >
      <i class="ph ph-arrow-up"></i>
    </button>
  </Transition>
</template>

<style scoped>
.scroll-to-top {
  position: fixed;
  bottom: 32px;
  right: 32px;
  z-index: 80;
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: #15294f;
  color: #c39a4d;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  box-shadow: 0 8px 24px -6px rgba(21, 41, 79, 0.45);
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.scroll-to-top:hover {
  transform: translateY(-3px);
  box-shadow: 0 14px 30px -8px rgba(21, 41, 79, 0.55);
  background: #1c3a6e;
}
.scroll-to-top:active {
  transform: translateY(0);
}

/* Transition */
.scroll-btn-enter-active {
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.scroll-btn-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.scroll-btn-enter-from {
  opacity: 0;
  transform: translateY(16px) scale(0.8);
}
.scroll-btn-leave-to {
  opacity: 0;
  transform: translateY(12px) scale(0.85);
}

@media (max-width: 768px) {
  .scroll-to-top {
    bottom: 20px;
    right: 20px;
    width: 44px;
    height: 44px;
    font-size: 20px;
  }
}
</style>
