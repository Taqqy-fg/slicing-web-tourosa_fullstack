<script setup>
defineProps({
  siteClients: Array
})
</script>

<template>
  <section style="border-top:1px solid #f0efe8;border-bottom:1px solid #f0efe8;background:#fbfaf6;">
    <div class="flex-trust" style="max-width:1240px;margin:0 auto;padding:30px 32px;align-items:center;flex-wrap:nowrap;">
      <span data-aos="fade-right" style="font-size:12.5px;font-weight:600;color:#9aa0ad;letter-spacing:.08em;text-transform:uppercase;white-space:nowrap;flex-shrink:0;">Dipercaya oleh tim dari</span>
      <div data-aos="fade-left" class="trust-logos" style="display:flex;align-items:center;gap:30px;flex-wrap:wrap;">
        <template v-for="(cl, idx) in siteClients" :key="idx">
          <img v-if="cl.img" :src="cl.img" :alt="cl.name" style="height:24px;width:auto;max-width:140px;object-fit:contain;opacity:.92;">
          <span v-else style="font-size:19px;font-weight:700;color:#9aa2b0;letter-spacing:-.01em;">{{ cl.name }}</span>
        </template>
      </div>
    </div>
  </section>
</template>
