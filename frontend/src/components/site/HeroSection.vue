<script setup>
import CountUp from 'vue-countup-v3'

defineProps({
  waLink: String,
  siteTagline: String,
  siteStats: Array
})

const getNumericVal = (str) => {
  const parsed = parseInt(str.replace(/[^0-9]/g, ''))
  return isNaN(parsed) ? 0 : parsed
}
const getSuffix = (str) => {
  return str.replace(/[0-9]/g, '')
}
</script>

<template>
  <section id="beranda" style="position:relative;background:linear-gradient(180deg,#fbfaf6 0%,#ffffff 100%);overflow:hidden;">
    <div class="grid-hero" style="max-width:1240px;margin:0 auto;padding:80px 32px 64px;">
      <div data-aos="fade-right">
        <div style="display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #ece4d0;border-radius:100px;padding:7px 14px;margin-bottom:24px;">
          <span class="pulse-dot"></span>
          <span style="font-size:12.5px;font-weight:600;color:#7a6b46;letter-spacing:.02em;">Travel partner untuk grup &amp; korporat</span>
        </div>
        <h1 class="hero-h1">Atur perjalanan<br>grup Anda, dari<br><span class="gradient-text">satu pintu.</span></h1>
        <p class="hero-p">{{ siteTagline }}</p>
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:40px;flex-wrap:wrap;">
          <a :href="waLink" target="_blank" class="tr-btn" style="background:#15294f;color:#fff;font-size:15px;font-weight:700;padding:15px 26px;border-radius:12px;display:flex;align-items:center;gap:10px;box-shadow:0 14px 30px -12px rgba(21,41,79,.5);"><i class="ph-fill ph-whatsapp-logo" style="font-size:20px;color:#c39a4d;"></i>Konsultasi WhatsApp</a>
          <a href="#layanan" class="tr-btn" style="background:#fff;border:1px solid #d9dbe0;color:#15294f;font-size:15px;font-weight:600;padding:15px 24px;border-radius:12px;display:flex;align-items:center;gap:8px;">Lihat Layanan<i class="ph ph-arrow-down" style="font-size:16px;"></i></a>
        </div>
        <div class="hero-stats">
          <div v-for="(st, idx) in siteStats" :key="idx" class="stat-box">
            <div style="font-size:30px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;">
              <CountUp :end-val="getNumericVal(st.n)" :options="{ suffix: getSuffix(st.n), duration: 2.5 }" />
            </div>
            <div style="font-size:12.5px;color:#6a7488;font-weight:500;margin-top:2px;">{{ st.l }}</div>
          </div>
        </div>
      </div>
      <div data-aos="fade-left" data-aos-delay="200" style="position:relative;">
        <div class="hero-img-box" style="width:100%;height:480px;border-radius:20px;box-shadow:0 30px 60px -24px rgba(21,41,79,.4);overflow:hidden;">
            <img src="/assets/Foto_grup_destinasi.png" alt="Foto grup destinasi Tourosa" style="width:100%;height:100%;object-fit:cover;display:block;">
        </div>
        <div data-aos="zoom-in" data-aos-delay="500" class="hero-float-card" style="position:absolute;left:-16px;bottom:34px;background:#fff;border-radius:16px;padding:16px 18px;box-shadow:0 22px 44px -16px rgba(21,41,79,.4);border:1px solid #f0eee6;width:248px;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
            <div style="width:34px;height:34px;border-radius:9px;background:#eef3fb;display:flex;align-items:center;justify-content:center;"><i class="ph-fill ph-airplane-tilt" style="font-size:18px;color:#15294f;"></i></div>
            <div><div style="font-size:13px;font-weight:700;color:#13233f;">Bali Corporate Gathering</div><div style="font-size:11px;color:#8a93a5;font-family:'IBM Plex Mono',monospace;">INV/TRS/2026/0007</div></div>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding-top:11px;border-top:1px solid #f0eee6;">
            <div><div style="font-size:10.5px;color:#8a93a5;font-weight:500;">45 pax · 3 hari</div><div style="font-size:15px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;">Rp 247.500.000</div></div>
            <span style="font-size:11px;font-weight:700;color:#1f7a5c;background:#e6f4ec;padding:5px 10px;border-radius:7px;">Lunas</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #c39a4d;
  box-shadow: 0 0 0 0 rgba(195, 154, 77, 0.7);
  animation: pulse-animation 1.5s infinite cubic-bezier(0.66, 0, 0, 1);
}

@keyframes pulse-animation {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(195, 154, 77, 0.7);
  }
  70% {
    transform: scale(1.1);
    box-shadow: 0 0 0 6px rgba(195, 154, 77, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(195, 154, 77, 0);
  }
}

.hero-stats {
  display: flex;
  gap: 40px;
  flex-wrap: wrap;
}

@media (max-width: 640px) {
  .hero-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px 16px;
  }
}

/* On small screens, clamp the floating card so it doesn't overflow left edge */
@media (max-width: 1024px) {
  .hero-float-card {
    left: 8px !important;
    width: calc(100% - 16px) !important;
    max-width: 248px !important;
  }
}
</style>
