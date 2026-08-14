<script setup>
</script>

<template>
  <section id="proses" class="process-section">
    <div data-aos="fade-up" style="text-align:center;margin-bottom:56px;">
      <div class="section-subtitle">Cara Kerja</div>
      <h2 class="section-title">Dari ide sampai berangkat, empat langkah</h2>
    </div>
    <div class="grid-4">
      <div data-aos="fade-up" class="process-step">
        <div class="process-step-header">
          <span class="step-num">01</span>
          <span class="process-line"></span>
        </div>
        <h3 class="step-title">Konsultasi</h3>
        <p class="step-desc">Ceritakan tujuan, jumlah peserta, tanggal, dan anggaran grup Anda.</p>
      </div>
      <div data-aos="fade-up" data-aos-delay="100" class="process-step">
        <div class="process-step-header">
          <span class="step-num">02</span>
          <span class="process-line"></span>
        </div>
        <h3 class="step-title">Penawaran</h3>
        <p class="step-desc">Kami susun itinerary &amp; rincian biaya transparan dalam bentuk invoice.</p>
      </div>
      <div data-aos="fade-up" data-aos-delay="200" class="process-step">
        <div class="process-step-header">
          <span class="step-num">03</span>
          <span class="process-line"></span>
        </div>
        <h3 class="step-title">Booking &amp; DP</h3>
        <p class="step-desc">Konfirmasi dengan DP, semua slot tiket &amp; kamar kami amankan.</p>
      </div>
      <div data-aos="fade-up" data-aos-delay="300" class="process-step">
        <div class="process-step-header">
          <span class="step-num step-num-last">04</span>
        </div>
        <h3 class="step-title">Berangkat</h3>
        <p class="step-desc">Tour leader mendampingi, Anda tinggal menikmati perjalanan.</p>
      </div>
    </div>
  </section>
</template>

<style scoped>
.process-section {
  max-width: 1240px;
  margin: 0 auto;
  padding: 96px 32px;
}
.process-step {
  text-align: left;
}
.process-step-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.step-num {
  font-family: 'IBM Plex Mono', monospace;
  font-size: 14px;
  font-weight: 600;
  color: #c39a4d;
  border: 1.5px solid #e6d5ad;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.step-num-last {
  background: #c39a4d;
  color: #fff;
  border-color: #c39a4d;
}
.process-line {
  height: 1.5px;
  flex: 1;
  background: #ece4d2;
}
.step-title {
  font-size: 18px;
  font-weight: 700;
  color: #13233f;
  margin: 0 0 8px;
}
.step-desc {
  font-size: 14px;
  line-height: 1.6;
  color: #5d6a82;
  margin: 0;
}

@media (max-width: 1024px) {
  .process-line { display: none; }
}

@media (max-width: 768px) {
  .process-section { padding: 64px 20px; }
}
</style>
