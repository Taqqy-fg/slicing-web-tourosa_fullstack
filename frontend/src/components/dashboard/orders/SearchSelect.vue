<script setup>
/**
 * SearchSelect — Custom Select2-style Combobox (mirip GroupSelect)
 * Dipakai untuk Kategori & Vendor/Produk: ketik untuk mencari, klik untuk pilih,
 * tombol wallet "Tambah ... Baru" untuk shortcut membuat opsi baru.
 */
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Pilih...' },
  addNewLabel: { type: String, default: '' },
  emptyText: { type: String, default: 'Tidak ada hasil' }
})

const emit = defineEmits(['update:modelValue', 'add-new'])

const isOpen = ref(false)
const query = ref(props.modelValue || '')
const highlight = ref(0)
const containerRef = ref(null)

watch(() => props.modelValue, (v) => {
  query.value = v || ''
})

const filtered = computed(() => {
  const list = props.options.filter(o => String(o || '').trim())
  const q = query.value.trim().toLowerCase()
  if (!q) return list
  return list.filter(o => o.toLowerCase().includes(q))
})

function open() {
  isOpen.value = true
  highlight.value = 0
}

function close() {
  isOpen.value = false
  query.value = props.modelValue || ''
}

function onInput(e) {
  query.value = e.target.value
  isOpen.value = true
  highlight.value = 0
}

function selectOption(opt) {
  query.value = opt
  emit('update:modelValue', opt)
  isOpen.value = false
}

function onAddNew() {
  isOpen.value = false
  emit('add-new')
}

function onKeydown(e) {
  if (!isOpen.value) {
    if (e.key === 'Enter' || e.key === 'ArrowDown') { open(); e.preventDefault() }
    return
  }
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    highlight.value = Math.min(highlight.value + 1, filtered.value.length - 1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    highlight.value = Math.max(highlight.value - 1, 0)
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (filtered.value[highlight.value]) selectOption(filtered.value[highlight.value])
  } else if (e.key === 'Escape') {
    close()
  }
}

function onClickOutside(e) {
  if (containerRef.value && !containerRef.value.contains(e.target)) close()
}
onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="containerRef" style="position:relative;flex:1;min-width:0;">
    <!-- Input Trigger -->
    <div style="position:relative;width:100%;display:flex;align-items:center;">
      <input
        :value="query"
        @input="onInput"
        @focus="open"
        @keydown="onKeydown"
        :placeholder="placeholder"
        style="width:100%;padding:10px 12px;padding-right:32px;border:1px solid #d8dce4;border-radius:9px;font-size:13px;color:#1a2235;background:#fff;outline:none;font-weight:600;"
        :style="{ borderColor: isOpen ? '#15294f' : '#d8dce4', boxShadow: isOpen ? '0 0 0 3px rgba(21,41,79,.08)' : 'none' }"
      />
      <div style="position:absolute;right:11px;display:flex;align-items:center;pointer-events:none;">
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
          {{ emptyText }}.<template v-if="addNewLabel"> Gunakan '<b style="color:#15294f;">{{ addNewLabel }}</b>' di bawah untuk menyimpan baru.</template>
        </div>
        <div
          v-for="(opt, oi) in filtered"
          :key="opt"
          @mousedown.prevent="selectOption(opt)"
          style="padding:10px 16px;cursor:pointer;font-size:13.5px;font-weight:600;color:#13233f;transition:background .12s;"
          :style="{ background: opt === modelValue ? '#eef3fb' : (oi === highlight ? '#f8f9fb' : 'transparent') }"
          @mouseenter="$event.currentTarget.style.background = '#eef3fb'"
          @mouseleave="$event.currentTarget.style.background = opt === modelValue ? '#eef3fb' : 'transparent'"
        >
          {{ opt }}
        </div>
      </div>
      <!-- Footer: Tambah opsi baru -->
      <div
        v-if="addNewLabel"
        @mousedown.prevent="onAddNew"
        style="border-top:1px solid #eef0f3;padding:10px 16px;cursor:pointer;display:flex;align-items:center;gap:8px;background:#fafbfc;transition:background .12s;"
        @mouseenter="$event.currentTarget.style.background='#eef3fb'"
        @mouseleave="$event.currentTarget.style.background='#fafbfc'"
      >
        <i class="ph ph-plus-circle" style="font-size:16px;color:#c39a4d;flex-shrink:0;"></i>
        <span style="font-size:13px;font-weight:700;color:#15294f;">{{ addNewLabel }}</span>
      </div>
    </div>
  </div>
</template>