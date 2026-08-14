<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

defineProps({
  waDisplay: String,
  siteEmail: String,
  siteAddress: String
})

const footerInner = ref(null)
let observer = null

onMounted(() => {
  observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
      footerInner.value?.classList.add('is-visible')
      observer.disconnect()
    }
  }, { threshold: 0.1 })
  if (footerInner.value) observer.observe(footerInner.value)
})

onUnmounted(() => { if (observer) observer.disconnect() })
</script>

<template>
  <footer class="site-footer">
    <div class="footer-inner" ref="footerInner">
      <img src="/assets/tourosa-logo-white.png" alt="Tourosa" class="footer-logo">
      <p class="footer-tagline">Agen perjalanan untuk grup &amp; korporat. Tiket, hotel, tour, dan gathering dalam satu layanan terpercaya.</p>
      <p class="footer-copy">© 2026 Tourosa Travel — Hak cipta dilindungi.</p>
    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  background: #0d1b30;
  border-top: 1px solid #1c2b44;
}
.footer-inner {
  max-width: 600px;
  margin: 0 auto;
  padding: 56px 32px 48px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 16px;
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.8s ease, transform 0.8s ease;
}
.footer-inner.is-visible {
  opacity: 1;
  transform: translateY(0);
}
.footer-logo {
  height: 28px;
  width: auto;
  display: block;
}
.footer-tagline {
  font-size: 14px;
  line-height: 1.65;
  color: #6f7c95;
  margin: 0;
  max-width: 400px;
}
.footer-copy {
  font-size: 13px;
  color: #4a566b;
  margin: 8px 0 0;
  letter-spacing: 0.01em;
}

@media (max-width: 768px) {
  .footer-inner {
    padding: 44px 24px 36px;
  }
}
</style>
