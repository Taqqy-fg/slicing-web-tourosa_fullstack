<script setup>
defineProps({
  waLink: String,
  waDisplay: String
})
</script>

<template>
  <section id="kontak" class="cta-section">
    <div data-aos="zoom-in" class="cta-box">
      <div class="cta-text">
        <h2 class="cta-title">Siap rencanakan perjalanan grup Anda?</h2>
        <p class="cta-desc">Chat tim kami via WhatsApp untuk penawaran &amp; itinerary tanpa biaya. Balasan cepat di jam kerja.</p>
      </div>
      <div class="cta-action">
        <a :href="waLink" target="_blank" class="tr-btn cta-btn">
          <i class="ph-fill ph-whatsapp-logo" style="font-size:22px;color:#c39a4d;"></i>Chat WhatsApp Sekarang
        </a>
        <div class="cta-wa-num">{{ waDisplay }}</div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.cta-section {
  max-width: 1240px;
  margin: 0 auto;
  padding: 88px 32px;
}
.cta-box {
  background: linear-gradient(120deg,#fbf5e8 0%,#fcfaf4 100%);
  border: 1px solid #efe6d2;
  border-radius: 24px;
  padding: 56px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 32px;
  flex-wrap: wrap;
}
.cta-text { max-width: 560px; }
.cta-title {
  font-size: 36px;
  line-height: 1.12;
  letter-spacing: -0.02em;
  font-weight: 800;
  color: #13233f;
  margin: 0 0 14px;
}
.cta-desc {
  font-size: 16px;
  line-height: 1.6;
  color: #5d6a82;
  margin: 0;
}
.cta-action {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.cta-btn {
  background: #15294f;
  color: #fff;
  font-size: 16px;
  font-weight: 700;
  padding: 17px 32px;
  border-radius: 13px;
  display: flex;
  align-items: center;
  gap: 11px;
  justify-content: center;
  box-shadow: 0 16px 34px -12px rgba(21,41,79,.5);
}
.cta-wa-num {
  font-size: 13px;
  color: #8a7c5c;
  text-align: center;
  font-weight: 500;
  font-family: 'IBM Plex Mono', monospace;
}

@media (max-width: 768px) {
  .cta-section { padding: 56px 20px; }
  .cta-box {
    padding: 36px 28px;
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }
  .cta-text { max-width: 100%; }
  .cta-title { font-size: 26px; }
  .cta-desc { font-size: 15px; }
  .cta-btn { width: 100%; }
  .cta-wa-num { text-align: center; }
}
</style>
