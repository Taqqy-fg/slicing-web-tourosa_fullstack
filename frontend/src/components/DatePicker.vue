<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Pilih tanggal...' },
  altFormat: { type: String, default: 'd M Y' },
  dateFormat: { type: String, default: 'Y-m-d' },
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
let fpInstance = null

onMounted(() => {
  fpInstance = flatpickr(inputRef.value, {
    dateFormat: props.dateFormat,
    altInput: true,
    altFormat: props.altFormat,
    defaultDate: props.modelValue || null,
    disableMobile: true,
    onChange: (_selectedDates, dateStr) => {
      emit('update:modelValue', dateStr)
    },
  })
})

watch(() => props.modelValue, (val) => {
  if (fpInstance) {
    fpInstance.setDate(val || null, false)
  }
})

onBeforeUnmount(() => {
  if (fpInstance) { fpInstance.destroy(); fpInstance = null }
})
</script>

<template>
  <input ref="inputRef" type="text" :placeholder="placeholder" readonly
    class="tr-datepicker" />
</template>
