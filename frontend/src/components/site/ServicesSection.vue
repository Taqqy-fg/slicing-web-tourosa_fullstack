<script setup>
</script>

<template>
  <section id="layanan" class="container-site" style="max-width:1240px;margin:0 auto;padding:96px 32px;">
    <div class="flex-services" style="margin-bottom:48px;">
      <div data-aos="fade-right">
        <div class="section-subtitle">Layanan Kami</div>
        <h2 class="section-title" style="max-width:560px;">Satu agen untuk seluruh kebutuhan perjalanan grup</h2>
      </div>
      <p data-aos="fade-left" style="font-size:15.5px;line-height:1.6;color:#52607a;max-width:340px;margin:0;">Mulai dari rombongan komunitas sampai outing kantor ratusan peserta — kami rancang paket yang pas dengan anggaran Anda.</p>
    </div>
    <div class="grid-4">
      <div data-aos="fade-up" data-aos-delay="100" class="tr-card" style="background:#fff;border:1px solid #ecebe4;border-radius:18px;padding:28px;">
        <div style="width:52px;height:52px;border-radius:13px;background:#13233f;display:flex;align-items:center;justify-content:center;margin-bottom:22px;"><i class="ph ph-airplane-tilt" style="font-size:26px;color:#c39a4d;"></i></div>
        <h3 style="font-size:19px;font-weight:700;color:#13233f;margin:0 0 9px;">Tiket Pesawat</h3>
        <p style="font-size:14px;line-height:1.6;color:#5d6a82;margin:0 0 16px;">Penerbangan domestik &amp; internasional, termasuk group fare untuk rombongan besar.</p>
        <div style="font-size:12.5px;color:#8a93a5;font-weight:500;">Group fare · reschedule · seat request</div>
      </div>
      <div data-aos="fade-up" data-aos-delay="200" class="tr-card" style="background:#fff;border:1px solid #ecebe4;border-radius:18px;padding:28px;">
        <div style="width:52px;height:52px;border-radius:13px;background:#13233f;display:flex;align-items:center;justify-content:center;margin-bottom:22px;"><i class="ph ph-bed" style="font-size:26px;color:#c39a4d;"></i></div>
        <h3 style="font-size:19px;font-weight:700;color:#13233f;margin:0 0 9px;">Hotel &amp; Akomodasi</h3>
        <p style="font-size:14px;line-height:1.6;color:#5d6a82;margin:0 0 16px;">Blok kamar untuk rombongan dengan harga korporat dan lokasi strategis.</p>
        <div style="font-size:12.5px;color:#8a93a5;font-weight:500;">Room block · meeting room · meal plan</div>
      </div>
      <div data-aos="fade-up" data-aos-delay="300" class="tr-card" style="background:#fff;border:1px solid #ecebe4;border-radius:18px;padding:28px;">
        <div style="width:52px;height:52px;border-radius:13px;background:#13233f;display:flex;align-items:center;justify-content:center;margin-bottom:22px;"><i class="ph ph-path" style="font-size:26px;color:#c39a4d;"></i></div>
        <h3 style="font-size:19px;font-weight:700;color:#13233f;margin:0 0 9px;">Group Tour</h3>
        <p style="font-size:14px;line-height:1.6;color:#5d6a82;margin:0 0 16px;">Paket wisata lengkap dengan tour leader, itinerary, konsumsi, dan transport.</p>
        <div style="font-size:12.5px;color:#8a93a5;font-weight:500;">Land tour · tour leader · itinerary custom</div>
      </div>
      <div data-aos="fade-up" data-aos-delay="400" class="tr-card" style="background:#fff;border:1px solid #ecebe4;border-radius:18px;padding:28px;">
        <div style="width:52px;height:52px;border-radius:13px;background:#13233f;display:flex;align-items:center;justify-content:center;margin-bottom:22px;"><i class="ph ph-confetti" style="font-size:26px;color:#c39a4d;"></i></div>
        <h3 style="font-size:19px;font-weight:700;color:#13233f;margin:0 0 9px;">Gathering &amp; Event</h3>
        <p style="font-size:14px;line-height:1.6;color:#5d6a82;margin:0 0 16px;">Outing kantor, family gathering, dan corporate event dari konsep sampai eksekusi.</p>
        <div style="font-size:12.5px;color:#8a93a5;font-weight:500;">MICE · team building · gala dinner</div>
      </div>
    </div>
    <div data-aos="zoom-in" class="extra-services-container">
      <div class="extra-services-list">
        <span class="es-label">Juga tersedia:</span>
        <div class="es-items-wrapper">
          <span class="es-item"><i class="ph ph-identification-card" style="color:#c39a4d;font-size:18px;"></i>Visa &amp; Dokumen</span>
          <span class="es-item"><i class="ph ph-van" style="color:#c39a4d;font-size:18px;"></i>Sewa Transport</span>
          <span class="es-item"><i class="ph ph-bowl-food" style="color:#c39a4d;font-size:18px;"></i>Konsumsi &amp; Catering</span>
          <span class="es-item"><i class="ph ph-shield-check" style="color:#c39a4d;font-size:18px;"></i>Travel Insurance</span>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.extra-services-container {
  margin-top: 22px;
  background: #13233f;
  border-radius: 18px;
  padding: 26px 32px;
}
.extra-services-list {
  display: flex;
  flex-direction: row;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px 28px;
}
.es-items-wrapper {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 14px 28px;
  align-items: center;
}
.es-label {
  font-size: 14px;
  color: #aeb8cc;
  font-weight: 500;
}
.es-item {
  font-size: 16px;
  color: #fff;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 12px;
}
.es-item i {
  font-size: 20px;
}

@media (max-width: 600px) {
  .extra-services-list {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .es-items-wrapper {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }
}
</style>
