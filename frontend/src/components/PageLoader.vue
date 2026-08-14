<script setup>
import { ref, onMounted } from 'vue'

const visible = ref(true)
const hiding = ref(false)

onMounted(() => {
  // Start hiding after page is ready (min 1.2s for effect)
  const hide = () => {
    hiding.value = true
    setTimeout(() => { visible.value = false }, 600)
  }

  if (document.readyState === 'complete') {
    setTimeout(hide, 1200)
  } else {
    window.addEventListener('load', () => setTimeout(hide, 800), { once: true })
    // Fallback: hide after max 3s regardless
    setTimeout(hide, 3000)
  }
})
</script>

<template>
  <Transition name="loader-fade">
    <div v-if="visible" class="page-loader" :class="{ hiding }">
      <div class="loader-content">
        <!-- Logo -->
        <div class="loader-logo-wrap">
          <img src="/assets/logo-src.png" alt="Tourosa" class="loader-logo" />
        </div>

        <!-- Animated progress bar -->
        <div class="loader-bar-track">
          <div class="loader-bar"></div>
        </div>

        <!-- Tagline -->
        <p class="loader-tagline">Menyiapkan pengalaman terbaik&hellip;</p>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.page-loader {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.6s ease, transform 0.6s ease;
}

.page-loader.hiding {
  opacity: 0;
  transform: scale(1.03);
}

.loader-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 32px;
}

/* Logo */
.loader-logo-wrap {
  animation: logo-pop 0.7s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

.loader-logo {
  height: 84px;
  width: auto;
  display: block;
}

@keyframes logo-pop {
  from { opacity: 0; transform: scale(0.75) translateY(12px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}

/* Progress bar */
.loader-bar-track {
  width: 180px;
  height: 3px;
  background: rgba(19, 35, 63, 0.1);
  border-radius: 99px;
  overflow: hidden;
  animation: bar-appear 0.4s 0.3s ease both;
}

@keyframes bar-appear {
  from { opacity: 0; transform: scaleX(0.5); }
  to   { opacity: 1; transform: scaleX(1); }
}

.loader-bar {
  height: 100%;
  width: 0%;
  background: linear-gradient(90deg, #c39a4d, #f0d79a, #c39a4d);
  background-size: 200% 100%;
  border-radius: 99px;
  animation:
    bar-fill 1.8s 0.2s cubic-bezier(0.4, 0, 0.2, 1) forwards,
    bar-shimmer 1.4s 0.2s linear infinite;
}

@keyframes bar-fill {
  0%   { width: 0%; }
  60%  { width: 85%; }
  100% { width: 100%; }
}

@keyframes bar-shimmer {
  0%   { background-position: 200% center; }
  100% { background-position: -200% center; }
}

/* Tagline */
.loader-tagline {
  font-size: 12.5px;
  color: rgba(90, 106, 130, 0.8);
  font-weight: 500;
  letter-spacing: 0.04em;
  margin: 0;
  animation: tag-fade 0.6s 0.5s ease both;
}

@keyframes tag-fade {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Vue transition fallback */
.loader-fade-leave-active { transition: opacity 0.6s ease; }
.loader-fade-leave-to { opacity: 0; }
</style>
