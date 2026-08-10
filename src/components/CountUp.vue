<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  value: { type: [String, Number], required: true },
  duration: { type: Number, default: 1000 }
});

const spanRef = ref(null);
let rafId = null;
let observer = null;
let hasAnimated = false;

const getZeroValue = (targetStr) => {
  const match = String(targetStr).match(/^(\D*)(\d[\d\.,]*\d|\d)(\D*)$/);
  if (!match) return targetStr;
  return `${match[1]}0${match[3]}`;
};

const displayValue = ref(getZeroValue(props.value));

const animate = (targetStr) => {
  const match = String(targetStr).match(/^(\D*)(\d[\d\.,]*\d|\d)(\D*)$/);
  if (!match) {
    displayValue.value = targetStr;
    return;
  }
  
  const prefix = match[1];
  const numStr = match[2].replace(/[^\d]/g, '');
  const suffix = match[3];
  const targetNum = parseInt(numStr, 10);
  
  if (isNaN(targetNum)) {
    displayValue.value = targetStr;
    return;
  }

  let startNum = 0;
  const startTime = performance.now();

  const easeOutQuart = (x) => 1 - Math.pow(1 - x, 4);

  const step = (currentTime) => {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / props.duration, 1);
    const easedProgress = easeOutQuart(progress);
    
    const currentNum = Math.round(startNum + (targetNum - startNum) * easedProgress);
    const formattedNum = new Intl.NumberFormat('id-ID').format(currentNum);
    displayValue.value = `${prefix}${formattedNum}${suffix}`;

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      displayValue.value = targetStr;
    }
  };

  if (rafId) cancelAnimationFrame(rafId);
  rafId = requestAnimationFrame(step);
};

onMounted(() => {
  observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && !hasAnimated) {
      hasAnimated = true;
      animate(props.value);
    }
  }, { threshold: 0.1 });
  
  if (spanRef.value) {
    observer.observe(spanRef.value);
  }
});

watch(() => props.value, (newVal) => {
  if (hasAnimated) {
    animate(newVal);
  } else {
    displayValue.value = getZeroValue(newVal);
  }
});

onUnmounted(() => {
  if (rafId) cancelAnimationFrame(rafId);
  if (observer) observer.disconnect();
});
</script>

<template>
  <span ref="spanRef">{{ displayValue }}</span>
</template>
