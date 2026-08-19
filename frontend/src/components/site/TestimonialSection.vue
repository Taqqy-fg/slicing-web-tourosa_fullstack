<script setup>
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay, Pagination } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/pagination'

const props = defineProps({
  testimonials: { type: Array, default: () => [] }
})
</script>

<template>
  <section v-if="testimonials.length" style="background:#13233f;color:#fff;">
    <div class="testimonial-inner">
      <i class="ph-fill ph-quotes" style="font-size:40px;color:#c39a4d;"></i>
      <Swiper
        :modules="[Autoplay, Pagination]"
        :autoplay="{ delay: 5000, disableOnInteraction: false }"
        :pagination="{ clickable: true }"
        :loop="testimonials.length > 1"
        :fade-effect="{ crossFade: true }"
        effect="fade"
        speed="600"
        style="width:100%;"
      >
        <SwiperSlide v-for="(t, idx) in testimonials" :key="t.id || idx">
          <div class="testimonial-slide">
            <p class="testimonial-quote">"{{ t.quote }}"</p>
            <div style="display:flex;align-items:center;justify-content:center;gap:14px;">
              <div class="testimonial-avatar"><img :src="t.avatar_url || '/assets/blank.png'" :alt="t.name" style="width:100%;height:100%;object-fit:cover;object-position:center center;display:block;"></div>
              <div style="text-align:left;"><div style="font-size:15px;font-weight:700;">{{ t.name }}</div><div style="font-size:13px;color:#9fabc4;">{{ t.role }} · {{ t.company }}</div></div>
            </div>
          </div>
        </SwiperSlide>
      </Swiper>
    </div>
  </section>
</template>

<style scoped>
.testimonial-inner {
  max-width: 1000px;
  margin: 0 auto;
  padding: 84px 32px;
  text-align: center;
}
.testimonial-slide {
  padding: 20px 0 32px;
}
.testimonial-quote {
  font-size: 27px;
  line-height: 1.45;
  font-weight: 600;
  letter-spacing: -0.01em;
  margin: 20px 0 30px;
  color: #f3f5f9;
}
.testimonial-avatar {
  width: 52px;
  height: 52px;
  flex-shrink: 0;
  background: #1b2e4a;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  overflow: hidden;
}

:deep(.swiper-pagination) {
  bottom: 8px !important;
}
:deep(.swiper-pagination-bullet) {
  background: #3d5278;
  opacity: 1;
}
:deep(.swiper-pagination-bullet-active) {
  background: #c39a4d;
}

@media (max-width: 768px) {
  .testimonial-inner {
    padding: 56px 24px;
  }
  .testimonial-quote {
    font-size: 18px;
    line-height: 1.55;
  }
}
</style>
