<script setup>
import { computed } from 'vue'

const props = defineProps({
  siteClients: Array
})

const hasOverflow = computed(() => (props.siteClients?.length || 0) > 6)
const doubledClients = computed(() => [
  ...(props.siteClients || []),
  ...(props.siteClients || [])
])
</script>

<template>
  <section class="trust-section"
    style="border-top:1px solid #f0efe8;border-bottom:1px solid #f0efe8;background:#fbfaf6;">
    <div class="trust-inner">
      <span data-aos="fade-right" class="trust-label">Dipercaya oleh tim dari</span>

      <div class="trust-logos-area" data-aos="fade-left">
        <!-- Baris statis: dipakai desktop & tablet portrait, hanya jika logo <= 6 -->
        <div class="trust-track trust-static" :class="{ 'is-hidden-overflow': hasOverflow }">
          <template v-for="(cl, idx) in siteClients" :key="'s' + idx">
            <img v-if="cl.img" :src="cl.img" :alt="cl.name" class="trust-logo-img">
            <span v-else class="trust-logo-text">{{ cl.name }}</span>
          </template>
        </div>

        <!-- Baris marquee: selalu tampil di mobile, atau di desktop/tablet jika logo > 6 -->
        <div class="trust-marquee" :class="{ 'is-visible-overflow': hasOverflow }">
          <div class="trust-marquee-track" :style="{ animationDuration: (doubledClients.length * 1.25) + 's' }">
            <template v-for="(cl, idx) in doubledClients" :key="'m' + idx">
              <img v-if="cl.img" :src="cl.img" :alt="cl.name" class="trust-logo-img">
              <span v-else class="trust-logo-text">{{ cl.name }}</span>
            </template>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.trust-inner {
  max-width: 1240px;
  margin: 0 auto;
  padding: 30px 32px;
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  gap: 24px;
}

.trust-label {
  font-size: 12.5px;
  font-weight: 600;
  color: #9aa0ad;
  letter-spacing: .08em;
  text-transform: uppercase;
  white-space: nowrap;
  flex-shrink: 0;
}

.trust-logos-area {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  display: flex;
  justify-content: flex-end;
}

.trust-track {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 30px;
  flex-wrap: wrap;
  width: 100%;
}

.trust-logo-img {
  height: 24px;
  width: auto;
  max-width: 140px;
  object-fit: contain;
  opacity: .92;
  flex-shrink: 0;
}

.trust-logo-text {
  font-size: 19px;
  font-weight: 700;
  color: #9aa2b0;
  letter-spacing: -.01em;
  white-space: nowrap;
  flex-shrink: 0;
}

/* Marquee tersembunyi secara default, muncul otomatis kalau logo > 6 */
.trust-marquee {
  display: none;
  overflow: hidden;
  width: 100%;
}

.trust-marquee.is-visible-overflow {
  display: block;
}

.trust-marquee-track {
  display: flex;
  align-items: center;
  gap: 30px;
  width: max-content;
  flex-wrap: nowrap;
  animation: trust-marquee-scroll 25s linear infinite;
}

.trust-marquee-track:hover {
  animation-play-state: paused;
}

.trust-static.is-hidden-overflow {
  display: none;
}

@keyframes trust-marquee-scroll {
  from {
    transform: translateX(0);
  }

  to {
    transform: translateX(-50%);
  }
}

/* Tablet portrait: row sejajar seperti desktop, tapi height diturunin */
@media (min-width: 641px) and (max-width: 1024px) {
  .trust-inner {
    flex-wrap: nowrap;
    justify-content: flex-start;
    text-align: left;
  }

  /* Paksa selalu animasi marquee di tablet portrait, apa pun jumlah logonya */
  .trust-static {
    display: none !important;
  }

  .trust-marquee {
    display: block !important;
  }

  .trust-marquee-track {
    animation-duration: 22s;
  }
}

/* Mobile: label di atas, logo di bawah, kasih jarak, selalu marquee */
@media (max-width: 640px) {
  .trust-inner {
    flex-direction: column;
    text-align: left;
  }

  .trust-logos-area {
    width: 100%;
    margin-top: 6px;
    /* jarak tambahan antara text & logo */
    justify-content: flex-start;
  }

  .trust-static {
    display: none !important;
  }

  .trust-marquee {
    display: block !important;
  }

  .trust-marquee-track {
    animation-duration: 18s;
  }
}
</style>