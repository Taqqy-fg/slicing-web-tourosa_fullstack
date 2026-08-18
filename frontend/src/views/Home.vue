<script setup>
import { computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { siteService } from '../services/siteService'

import SiteHeader from '../components/site/SiteHeader.vue'
import HeroSection from '../components/site/HeroSection.vue'
import TrustStrip from '../components/site/TrustStrip.vue'
import ServicesSection from '../components/site/ServicesSection.vue'
import AboutSection from '../components/site/AboutSection.vue'
import ProcessSection from '../components/site/ProcessSection.vue'
import TestimonialSection from '../components/site/TestimonialSection.vue'
import CtaSection from '../components/site/CtaSection.vue'
import SiteFooter from '../components/site/SiteFooter.vue'
import ScrollToTop from '../components/site/ScrollToTop.vue'

const { data, isPending } = useQuery({
  queryKey: ['site'],
  queryFn: siteService.getSiteSettings
})

const site = computed(() => {
  const s = data.value?.site || {};
  return {
    waNumber: s.waNumber || '6281200000000',
    email: s.email || 'halo@tourosa.id',
    address: s.address || 'Jakarta, Indonesia',
    tagline: s.tagline || 'Tiket pesawat, hotel, group tour, hingga gathering korporat.',
    stats: s.stats || [{ n: '12+', l: 'Tahun pengalaman' }, { n: '800+', l: 'Grup diberangkatkan' }, { n: '50+', l: 'Destinasi' }],
    clients: s.clients || []
  }
})

// Computed values based on state logic from the HTML script
const waNum = computed(() => (site.value.waNumber || '6281200000000').replace(/[^0-9]/g, ''))
const waMsg = encodeURIComponent('Halo Tourosa, saya ingin konsultasi perjalanan grup.')
const waLink = computed(() => 'https://wa.me/' + waNum.value + '?text=' + waMsg)
const waDisplay = computed(() => '+' + waNum.value.replace(/^(\d{2})(\d{3,4})(\d{3,4})(\d+)$/, '$1 $2-$3-$4'))

</script>

<template>
  <div style="background:#ffffff;">
    <SiteHeader :wa-link="waLink" />
    <HeroSection :site-tagline="site.tagline" :site-stats="site.stats" :wa-link="waLink" />
    <TrustStrip :site-clients="site.clients" />
    <ServicesSection />
    <AboutSection />
    <ProcessSection />
    <TestimonialSection />
    <CtaSection :wa-link="waLink" :wa-display="waDisplay" />
    <SiteFooter :wa-display="waDisplay" :site-email="site.email" :site-address="site.address" />
    <ScrollToTop />
  </div>
</template>
