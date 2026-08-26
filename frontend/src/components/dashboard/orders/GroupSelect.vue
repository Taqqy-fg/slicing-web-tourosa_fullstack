<script setup>
/**
 * GroupSelect — Custom Select2-style Combobox
 * Memungkinkan user memilih opsi yang ada, ATAU mengetik nama instansi baru.
 */
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useDashboardStore } from '../../../stores/dashboardStore'

const props = defineProps({
  modelValue: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue', 'select'])

const store = useDashboardStore()

const allOptions = computed(() => {
  const map = new Map()
  for (const c of store.customers) {
    map.set(c.name, { name: c.name, pic: c.pic_name || '', contact: c.contact_info || '' })
  }
  for (const o of store.orders) {
    if (o.group && o.group !== 'Tanpa Nama Grup' && !map.has(o.group)) {
      map.set(o.group, { name: o.group, pic: o.pic || '', contact: o.contact || '' })
    }
  }
  return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name, 'id'))
})

const isOpen = ref(false)
const internalValue = ref(props.modelValue)
const containerRef = ref(null)

watch(() => props.modelValue, (newVal) => {
  internalValue.value = newVal
})

const filtered = computed(() => {
  if (!internalValue.value.trim()) return allOptions.value
  const q = internalValue.value.toLowerCase()
  return allOptions.value.filter(o => o.name.toLowerCase().includes(q))
})

function open() {
  isOpen.value = true
}

function close() {
  isOpen.value = false
}

function onInput() {
  isOpen.value = true
  emit('update:modelValue', internalValue.value)
}

function selectOption(opt) {
  internalValue.value = opt.name
  emit('update:modelValue', opt.name)
  emit('select', opt)
  close()
}

function onClickOutside(e) {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    close()
  }
}
onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="containerRef" style="position:relative;">
    <!-- Input Trigger -->
    <div style="position:relative;width:100%;display:flex;align-items:center;">
      <input
        v-model="internalValue"
        @focus="open"
        @input="onInput"
        placeholder="Ketik atau pilih nama grup..."
        style="width:100%;padding:11px 13px;padding-right:36px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;font-weight:600;"
        :style="{ borderColor: isOpen ? '#15294f' : '#d8dce4', boxShadow: isOpen ? '0 0 0 3px rgba(21,41,79,.08)' : 'none' }"
      />
      <div style="position:absolute;right:13px;display:flex;align-items:center;gap:4px;pointer-events:none;">
        <i :class="['ph', isOpen ? 'ph-caret-up' : 'ph-caret-down']" style="font-size:14px;color:#9aa0ad;"></i>
      </div>
    </div>

    <!-- Dropdown panel -->
    <div
      v-if="isOpen"
      style="position:absolute;top:calc(100% + 6px);left:0;right:0;background:#fff;border:1px solid #d8dce4;border-radius:11px;box-shadow:0 8px 28px -6px rgba(21,41,79,.18);z-index:200;overflow:hidden;"
    >
      <div style="max-height:220px;overflow-y:auto;padding:6px 0;">
        <div v-if="!filtered.length" style="padding:10px 16px;text-align:left;color:#9aa0ad;font-size:13px;">
          Grup belum terdaftar. Lanjutkan mengetik untuk menyimpan otomatis.
        </div>
        <div
          v-for="opt in filtered"
          :key="opt.name"
          @mousedown.prevent="selectOption(opt)"
          style="padding:11px 16px;cursor:pointer;transition:background .12s;"
          :style="{ background: opt.name === modelValue ? '#eef3fb' : 'transparent' }"
          @mouseenter="$event.currentTarget.style.background = opt.name === modelValue ? '#dce9f7' : '#f8f9fb'"
          @mouseleave="$event.currentTarget.style.background = opt.name === modelValue ? '#eef3fb' : 'transparent'"
        >
          <div style="font-size:13.5px;font-weight:700;color:#13233f;">{{ opt.name }}</div>
          <div v-if="opt.pic || opt.contact" style="font-size:11.5px;color:#9aa0ad;margin-top:2px;">
            <span v-if="opt.pic">{{ opt.pic }}</span>
            <span v-if="opt.pic && opt.contact"> · </span>
            <span v-if="opt.contact">{{ opt.contact }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
