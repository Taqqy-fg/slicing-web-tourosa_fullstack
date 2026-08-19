<script setup>
import { computed, ref } from 'vue'
import { useDashboardStore } from '../../stores/dashboardStore'
import { useAuthStore } from '../../stores/authStore'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { dashboardService } from '../../services/dashboardService'


const props = defineProps({
  site: Object,
  catalog: Array
})

const store = useDashboardStore()
const auth = useAuthStore()
const queryClient = useQueryClient()

const tabWebsite = () => store.setSettingsTab('website')
const tabCatalog = () => store.setSettingsTab('catalog')
const tabProfile = () => store.setSettingsTab('profile')
const tabWebBg = computed(() => store.settingsTab === 'website' ? '#15294f' : 'transparent')
const tabWebColor = computed(() => store.settingsTab === 'website' ? '#fff' : '#5d6a82')
const tabCatBg = computed(() => store.settingsTab === 'catalog' ? '#15294f' : 'transparent')
const tabCatColor = computed(() => store.settingsTab === 'catalog' ? '#fff' : '#5d6a82')
const tabProfBg = computed(() => store.settingsTab === 'profile' ? '#15294f' : 'transparent')
const tabProfColor = computed(() => store.settingsTab === 'profile' ? '#fff' : '#5d6a82')
const isTabWebsite = computed(() => store.settingsTab === 'website')
const isTabCatalog = computed(() => store.settingsTab === 'catalog')
const isTabProfile = computed(() => store.settingsTab === 'profile')

// Profile form
const profileName = ref(auth.user?.name || '')
const profileEmail = ref(auth.user?.email || '')
const profilePassword = ref('')
const profilePasswordConfirm = ref('')
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const profileLoading = ref(false)
const profileSuccess = ref('')
const profileError = ref('')

async function saveProfile() {
  profileLoading.value = true
  profileSuccess.value = ''
  profileError.value = ''
  try {
    const payload = {
      name: profileName.value,
      email: profileEmail.value,
    }
    if (profilePassword.value) {
      payload.password = profilePassword.value
      payload.password_confirmation = profilePasswordConfirm.value
    }
    await auth.updateProfile(payload)
    profilePassword.value = ''
    profilePasswordConfirm.value = ''
    profileSuccess.value = 'Profil berhasil diperbarui.'
  } catch (e) {
    profileError.value = e.response?.data?.message || e.response?.data?.errors?.email?.[0] || e.response?.data?.errors?.password?.[0] || 'Gagal memperbarui profil.'
  } finally {
    profileLoading.value = false
  }
}

const statEdit = computed(() => (store.site.stats || []).map((st, i) => ({ 
  idx: i, n: st.n, l: st.l, 
  onN: ev => { store.site.stats[i].n = ev.target.value }, 
  onL: ev => { store.site.stats[i].l = ev.target.value } 
})))

const clientsEdit = computed(() => (store.site.clients || []).map((c, i) => ({ 
  idx: i, name: c.name, img: c.img, hasImg: !!c.img, notImg: !c.img, 
  onName: ev => {
    const updated = [...store.site.clients];
    updated[i] = { ...updated[i], name: ev.target.value };
    store.site.clients = updated;
  }, 
  onRemove: () => { 
    const updated = [...store.site.clients];
    updated.splice(i, 1);
    store.site.clients = updated;
  } 
})))

// Standalone upload handler — called directly from template with current v-for index
// This avoids stale closure issues when file input @change is not rebound by Vue
// after array reassignment (which happens when using :key="idx").
const uploadLogo = (ev, idx) => {
  const file = ev.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const updated = [...store.site.clients];
    updated[idx] = { ...updated[idx], img: e.target.result };
    store.site.clients = updated;
    // Reset the input so the same file can be re-selected if needed
    ev.target.value = '';
  };
  reader.readAsDataURL(file);
}

const addClient = () => {
  const clients = store.site.clients || [];
  store.site.clients = [...clients, { name: '', img: '' }];
}

const catalogEdit = computed(() => store.catalog.map((c, ci) => ({
  idx: ci, cat: c.cat, 
  onName: ev => { 
    const updated = [...store.catalog];
    updated[ci] = { ...updated[ci], cat: ev.target.value };
    store.catalog = updated;
  }, 
  onRemove: () => { 
    const updated = [...store.catalog];
    updated.splice(ci, 1);
    store.catalog = updated;
  }, 
  onAddVendor: () => { 
    const updated = [...store.catalog];
    const items = [...(updated[ci].items || []), ''];
    updated[ci] = { ...updated[ci], items };
    store.catalog = updated;
  },
  vendors: (c.items || []).map((v, vi) => ({ 
    vidx: vi, val: v, 
    onVal: ev => { 
      const updated = [...store.catalog];
      const items = [...updated[ci].items];
      items[vi] = ev.target.value;
      updated[ci] = { ...updated[ci], items };
      store.catalog = updated;
    }, 
    onRemove: () => { 
      const updated = [...store.catalog];
      const items = [...updated[ci].items];
      items.splice(vi, 1);
      updated[ci] = { ...updated[ci], items };
      store.catalog = updated;
    } 
  }))
})))
const addCat = () => {
  store.catalog = [...store.catalog, { cat: 'Kategori Baru', items: [] }];
}

// --- Save Settings ---
const saveSettingsStatus = ref('')
const saveSettingsMut = useMutation({
  mutationFn: dashboardService.updateSettings,
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    saveSettingsStatus.value = 'saved'
    setTimeout(() => { saveSettingsStatus.value = '' }, 2500)
  },
  onError: () => {
    saveSettingsStatus.value = 'error'
    setTimeout(() => { saveSettingsStatus.value = '' }, 3000)
  }
})
const saveSettings = () => {
  saveSettingsStatus.value = 'saving'
  saveSettingsMut.mutate({
    waNumber: store.site.waNumber,
    email: store.site.email,
    address: store.site.address,
    tagline: store.site.tagline,
    stats: store.site.stats,
    clients: store.site.clients,
  })
}

// --- Save Catalog ---
const saveCatalogStatus = ref('')
const saveCatalogMut = useMutation({
  mutationFn: dashboardService.updateCatalog,
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    saveCatalogStatus.value = 'saved'
    setTimeout(() => { saveCatalogStatus.value = '' }, 2500)
  },
  onError: () => {
    saveCatalogStatus.value = 'error'
    setTimeout(() => { saveCatalogStatus.value = '' }, 3000)
  }
})
const saveCatalog = () => {
  saveCatalogStatus.value = 'saving'
  saveCatalogMut.mutate(store.catalog)
}
</script>

<template>
  <div class="p-mobile" style="padding:30px 32px;">
    <div style="display:inline-flex;flex-wrap:wrap;background:#fff;border:1px solid #e8e9ee;border-radius:12px;padding:5px;margin-bottom:22px;gap:4px;">
      <button @click="tabWebsite" class="tr-btn" :style="{ background: tabWebBg, color: tabWebColor, border:'none', borderRadius:'9px', cursor:'pointer', fontSize:'13.5px', fontWeight:'700', padding:'9px 18px', display:'flex', alignItems:'center', gap:'7px' }"><i class="ph ph-globe-hemisphere-west" style="font-size:16px;"></i>Konten Website</button>
      <button @click="tabCatalog" class="tr-btn" :style="{ background: tabCatBg, color: tabCatColor, border:'none', borderRadius:'9px', cursor:'pointer', fontSize:'13.5px', fontWeight:'700', padding:'9px 18px', display:'flex', alignItems:'center', gap:'7px' }"><i class="ph ph-tag" style="font-size:16px;"></i>Kategori &amp; Vendor</button>
      <button @click="tabProfile" class="tr-btn" :style="{ background: tabProfBg, color: tabProfColor, border:'none', borderRadius:'9px', cursor:'pointer', fontSize:'13.5px', fontWeight:'700', padding:'9px 18px', display:'flex', alignItems:'center', gap:'7px' }"><i class="ph ph-user-circle" style="font-size:16px;"></i>Profil Admin</button>
    </div>

    <div v-if="isTabWebsite" style="display:flex;flex-direction:column;gap:18px;">
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:24px;">
        <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0 0 4px;display:flex;align-items:center;gap:9px;"><i class="ph ph-address-book" style="color:#c39a4d;font-size:20px;"></i>Kontak</h3>
        <p style="font-size:13px;color:#8a93a5;margin:0 0 18px;">Tampil di tombol WhatsApp, footer, dan invoice.</p>
        <div class="grid-cols-1-mobile" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Nomor WhatsApp</label><input v-model="site.waNumber" placeholder="6281200000000" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;font-family:'IBM Plex Mono',monospace;"></div>
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Email</label><input v-model="site.email" placeholder="halo@tourosa.id" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;"></div>
          <div><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Alamat / Kota</label><input v-model="site.address" placeholder="Jakarta, Indonesia" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;"></div>
        </div>
      </div>

      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:24px;">
        <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0 0 4px;display:flex;align-items:center;gap:9px;"><i class="ph ph-textbox" style="color:#c39a4d;font-size:20px;"></i>Hero &amp; Statistik</h3>
        <p style="font-size:13px;color:#8a93a5;margin:0 0 18px;">Subjudul dan angka pencapaian di halaman depan.</p>
        <div style="margin-bottom:16px;"><label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Subjudul Hero</label><textarea v-model="site.tagline" rows="2" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:13.5px;color:#1a2235;background:#fff;outline:none;resize:vertical;line-height:1.5;"></textarea></div>
        <div class="grid-cols-1-mobile" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
          <div v-for="(st, idx) in statEdit" :key="idx" style="background:#fafbfc;border:1px solid #eef0f3;border-radius:11px;padding:14px;">
            <input :value="st.n" @input="st.onN" placeholder="12+" style="width:100%;padding:9px 11px;border:1px solid #d8dce4;border-radius:8px;font-size:18px;font-weight:800;color:#13233f;background:#fff;outline:none;font-family:'IBM Plex Mono',monospace;margin-bottom:8px;">
            <input :value="st.l" @input="st.onL" placeholder="Keterangan" style="width:100%;padding:8px 11px;border:1px solid #d8dce4;border-radius:8px;font-size:13px;color:#5d6a82;background:#fff;outline:none;">
          </div>
        </div>
      </div>

      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:6px;flex-wrap:wrap;">
          <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;display:flex;align-items:center;gap:9px;"><i class="ph ph-buildings" style="color:#c39a4d;font-size:20px;"></i>Logo Klien</h3>
          <button @click="addClient" class="tr-btn" style="background:#eef3fb;color:#15294f;border:1px solid #d6e1f2;font-size:13px;font-weight:700;padding:9px 14px;border-radius:9px;cursor:pointer;display:flex;align-items:center;gap:6px;"><i class="ph ph-plus" style="font-size:15px;"></i>Tambah Klien</button>
        </div>
        <p style="font-size:13px;color:#8a93a5;margin:0 0 14px;">Unggah logo (PNG/JPG). Jika kosong, ditampilkan sebagai teks nama. Ukuran gambar: 140 x 36 pixel.</p>
        <div v-for="(cl, idx) in clientsEdit" :key="'client-' + idx" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;padding:9px 0;border-top:1px solid #f1f2f5;">
          <label style="cursor:pointer;display:block;flex-shrink:0;">
            <img v-if="cl.hasImg" :src="cl.img" style="height:44px;width:130px;object-fit:contain;border:1px solid #e2e4ea;border-radius:8px;background:#fafbfc;display:block;">
            <div v-if="cl.notImg" style="height:44px;width:130px;border:1px dashed #cfd3da;border-radius:8px;display:flex;align-items:center;justify-content:center;gap:6px;font-size:11.5px;color:#9aa0ad;font-weight:600;"><i class="ph ph-upload-simple" style="font-size:15px;"></i>Upload Logo</div>
            <input type="file" accept="image/*" @change="(ev) => uploadLogo(ev, idx)" style="display:none;">
          </label>
          <input :value="cl.name" @input="cl.onName" placeholder="Nama klien" style="flex:1;min-width:150px;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;">
          <button @click="cl.onRemove" class="tr-btn" style="background:none;border:none;cursor:pointer;color:#c2603a;padding:6px;display:flex;align-items:center;justify-content:center;"><i class="ph ph-trash" style="font-size:17px;"></i></button>
        </div>
      </div>
      <!-- Save website settings button -->
      <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;padding-top:4px;">
        <transition name="fade">
          <span v-if="saveSettingsStatus === 'saved'" style="font-size:13px;color:#1f7a5c;font-weight:600;display:flex;align-items:center;gap:5px;"><i class="ph-fill ph-check-circle" style="font-size:16px;"></i>Pengaturan Tersimpan</span>
          <span v-else-if="saveSettingsStatus === 'error'" style="font-size:13px;color:#c2603a;font-weight:600;display:flex;align-items:center;gap:5px;"><i class="ph-fill ph-warning-circle" style="font-size:16px;"></i>Gagal menyimpan</span>
        </transition>
        <button @click="saveSettings" :disabled="saveSettingsStatus === 'saving'" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:13.5px;font-weight:700;padding:11px 20px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:8px;" :style="{ opacity: saveSettingsStatus === 'saving' ? 0.7 : 1 }">
          <i v-if="saveSettingsStatus === 'saving'" class="ph ph-circle-notch" style="font-size:16px;animation:spin 1s linear infinite;"></i>
          <i v-else class="ph ph-floppy-disk" style="font-size:16px;color:#c39a4d;"></i>
          {{ saveSettingsStatus === 'saving' ? 'Menyimpan...' : 'Simpan Pengaturan' }}
        </button>
      </div>
    </div>

    <div v-if="isTabCatalog" style="display:flex;flex-direction:column;gap:16px;">
      <p style="font-size:13.5px;color:#5d6a82;margin:0;line-height:1.5;">Kategori &amp; vendor di sini muncul sebagai pilihan saat membuat pesanan. Edit nama dengan mengetik langsung.</p>
      <div v-for="(cat, idx) in catalogEdit" :key="idx" style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:18px 20px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
          <i class="ph ph-folder" style="color:#c39a4d;font-size:19px;"></i>
          <input :value="cat.cat" @input="cat.onName" style="flex:1;min-width:180px;padding:9px 12px;border:1px solid #d8dce4;border-radius:9px;font-size:14.5px;font-weight:700;color:#13233f;background:#fff;outline:none;">
          <button @click="cat.onAddVendor" class="tr-btn" style="background:#eef3fb;color:#15294f;border:1px solid #d6e1f2;font-size:12.5px;font-weight:700;padding:8px 13px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:5px;white-space:nowrap;"><i class="ph ph-plus" style="font-size:14px;"></i>Vendor</button>
          <button @click="cat.onRemove" class="tr-btn" style="background:none;border:none;cursor:pointer;color:#c2603a;padding:6px;display:flex;"><i class="ph ph-trash" style="font-size:17px;"></i></button>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;padding-left:29px;">
          <div v-for="(v, vidx) in cat.vendors" :key="vidx" style="display:flex;align-items:center;gap:2px;background:#fafbfc;border:1px solid #e2e4ea;border-radius:8px;padding:3px 4px 3px 10px;">
            <input :value="v.val" @input="v.onVal" placeholder="Vendor/produk" style="border:none;background:none;outline:none;font-size:13px;color:#1a2235;width:128px;">
            <button @click="v.onRemove" class="tr-btn" style="background:none;border:none;cursor:pointer;color:#9aa0ad;padding:4px;display:flex;"><i class="ph ph-x" style="font-size:13px;"></i></button>
          </div>
        </div>
      </div>
      <!-- Bottom actions row: tambah kategori + simpan katalog -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <button @click="addCat" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:13.5px;font-weight:700;padding:11px 18px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:7px;"><i class="ph ph-plus" style="font-size:16px;color:#c39a4d;"></i>Tambah Kategori</button>
        <div style="display:flex;align-items:center;gap:12px;">
          <transition name="fade">
            <span v-if="saveCatalogStatus === 'saved'" style="font-size:13px;color:#1f7a5c;font-weight:600;display:flex;align-items:center;gap:5px;"><i class="ph-fill ph-check-circle" style="font-size:16px;"></i>Katalog Tersimpan</span>
            <span v-else-if="saveCatalogStatus === 'error'" style="font-size:13px;color:#c2603a;font-weight:600;display:flex;align-items:center;gap:5px;"><i class="ph-fill ph-warning-circle" style="font-size:16px;"></i>Gagal menyimpan</span>
          </transition>
          <button @click="saveCatalog" :disabled="saveCatalogStatus === 'saving'" class="tr-btn" style="background:#1f7a5c;color:#fff;border:none;font-size:13.5px;font-weight:700;padding:11px 20px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:8px;" :style="{ opacity: saveCatalogStatus === 'saving' ? 0.7 : 1 }">
            <i v-if="saveCatalogStatus === 'saving'" class="ph ph-circle-notch" style="font-size:16px;animation:spin 1s linear infinite;"></i>
            <i v-else class="ph ph-floppy-disk" style="font-size:16px;"></i>
            {{ saveCatalogStatus === 'saving' ? 'Menyimpan...' : 'Simpan Katalog' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="isTabProfile" style="display:flex;flex-direction:column;gap:18px;">
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:24px;">
        <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0 0 4px;display:flex;align-items:center;gap:9px;"><i class="ph ph-user-circle" style="color:#c39a4d;font-size:20px;"></i>Profil Admin</h3>
        <p style="font-size:13px;color:#8a93a5;margin:0 0 18px;">Perbarui nama, email, dan password akun Anda.</p>

        <div v-if="profileSuccess" style="display:flex;align-items:center;gap:8px;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;font-size:13px;font-weight:600;padding:12px 14px;border-radius:10px;margin-bottom:16px;">
          <i class="ph ph-check-circle" style="font-size:16px;flex-shrink:0;"></i>{{ profileSuccess }}
        </div>
        <div v-if="profileError" style="display:flex;align-items:center;gap:8px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:13px;font-weight:600;padding:12px 14px;border-radius:10px;margin-bottom:16px;">
          <i class="ph ph-warning-circle" style="font-size:16px;flex-shrink:0;"></i>{{ profileError }}
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div style="grid-column:span 2;">
            <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Nama Lengkap</label>
            <input v-model="profileName" placeholder="Nama admin" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;">
          </div>
          <div style="grid-column:span 2;">
            <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Email</label>
            <input v-model="profileEmail" type="email" placeholder="admin@tourosa.id" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;">
          </div>
          <div style="position:relative;">
            <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Password Baru <span style="color:#b0b8c8;font-weight:400;">(opsional)</span></label>
            <input v-model="profilePassword" :type="showPassword ? 'text' : 'password'" placeholder="Kosongkan jika tidak diubah" style="width:100%;padding:11px 38px 11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;">
            <button type="button" @click="showPassword = !showPassword" tabindex="-1" style="position:absolute;right:8px;bottom:8px;background:none;border:none;cursor:pointer;padding:4px;display:flex;align-items:center;color:#9aa3b2;font-size:16px;transition:color .2s;"><i :class="showPassword ? 'ph ph-eye-slash' : 'ph ph-eye'"></i></button>
          </div>
          <div style="position:relative;">
            <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Konfirmasi Password</label>
            <input v-model="profilePasswordConfirm" :type="showPasswordConfirm ? 'text' : 'password'" placeholder="Ulangi password baru" style="width:100%;padding:11px 38px 11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;color:#1a2235;background:#fff;outline:none;">
            <button type="button" @click="showPasswordConfirm = !showPasswordConfirm" tabindex="-1" style="position:absolute;right:8px;bottom:8px;background:none;border:none;cursor:pointer;padding:4px;display:flex;align-items:center;color:#9aa3b2;font-size:16px;transition:color .2s;"><i :class="showPasswordConfirm ? 'ph ph-eye-slash' : 'ph ph-eye'"></i></button>
          </div>
        </div>
        <button @click="saveProfile" :disabled="profileLoading" class="tr-btn" style="margin-top:18px;background:#15294f;color:#fff;border:none;font-size:13.5px;font-weight:700;padding:11px 22px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:7px;">
          <span v-if="profileLoading" style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;display:inline-block;"></span>
          <i v-else class="ph ph-floppy-disk" style="font-size:16px;"></i>
          {{ profileLoading ? 'Menyimpan...' : 'Simpan Profil' }}
        </button>
      </div>
    </div>
  </div>
</template>
