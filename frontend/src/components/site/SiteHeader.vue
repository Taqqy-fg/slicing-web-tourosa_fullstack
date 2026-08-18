<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import AOS from 'aos'

const router = useRouter()
const auth = useAuthStore()
defineProps({
  waLink: String
})

const isMenuOpen = ref(false)

const goDash = () => {
  isMenuOpen.value = false
  router.push('/dashboard')
}
const goLogin = () => {
  isMenuOpen.value = false
  router.push('/login')
}
const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
}
const closeMenu = () => {
  isMenuOpen.value = false
}

const navigateTo = (id) => {
  isMenuOpen.value = false
  const el = document.getElementById(id)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    
    // Force AOS elements in target section to animate, overriding mobile scroll bugs
    setTimeout(() => {
      if (el.hasAttribute('data-aos')) el.classList.add('aos-animate')
      el.querySelectorAll('[data-aos]').forEach(node => node.classList.add('aos-animate'))
      AOS.refresh()
    }, 400)
  }
}
</script>

<template>
  <header class="site-header">
    <div class="site-header-inner">
      <img src="/assets/tourosa-logo.png" alt="Tourosa" style="height:26px;width:auto;display:block;flex-shrink:0;">

      <nav class="nav-desktop">
        <a href="#layanan" class="tr-link" style="font-size:14px;font-weight:500;color:#3a4459;">Layanan</a>
        <a href="#tentang" class="tr-link" style="font-size:14px;font-weight:500;color:#3a4459;">Tentang</a>
        <a href="#proses" class="tr-link" style="font-size:14px;font-weight:500;color:#3a4459;">Cara Kerja</a>
        <a href="#kontak" class="tr-link" style="font-size:14px;font-weight:500;color:#3a4459;">Kontak</a>
      </nav>

      <div class="header-actions">
        <button v-if="auth.isAuthenticated" @click="goDash" class="tr-btn hdr-btn-dash" style="border:1px solid #d9dbe0;background:#fff;color:#15294f;font-size:13.5px;font-weight:600;padding:9px 16px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:7px;">
          <i class="ph ph-squares-four" style="font-size:16px;"></i>Dashboard
        </button>
        <button v-else @click="goLogin" class="tr-btn hdr-btn-dash" style="border:1px solid #d9dbe0;background:#fff;color:#15294f;font-size:13.5px;font-weight:600;padding:9px 16px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:7px;">
          <i class="ph ph-sign-in" style="font-size:16px;"></i>Login
        </button>
        <a :href="waLink" target="_blank" class="tr-btn hdr-btn-wa" style="background:#15294f;color:#fff;font-size:13.5px;font-weight:600;padding:10px 18px;border-radius:10px;display:flex;align-items:center;gap:8px;">
          <i class="ph-fill ph-whatsapp-logo" style="font-size:17px;color:#c39a4d;"></i>Hubungi Kami
        </a>

        <!-- Hamburger — NOT using tr-btn class so it won't be hidden by global CSS -->
        <button @click="toggleMenu" class="hamburger-btn" :class="{ active: isMenuOpen }" aria-label="Buka menu">
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    </div>
  </header>

  <!-- Off-canvas backdrop -->
  <Transition name="fade-bd">
    <div v-if="isMenuOpen" class="offcanvas-bd" @click="closeMenu"></div>
  </Transition>

  <!-- Off-canvas menu -->
  <Transition name="slide-menu">
    <div v-if="isMenuOpen" class="offcanvas-nav">
      <div class="offcanvas-head">
        <img src="/assets/tourosa-logo.png" alt="Tourosa" style="height:24px;width:auto;">
        <button @click="closeMenu" class="offcanvas-close" aria-label="Tutup menu">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <nav class="offcanvas-links">
        <a href="#layanan" @click.prevent="navigateTo('layanan')" class="offcanvas-link">Layanan</a>
        <a href="#tentang" @click.prevent="navigateTo('tentang')" class="offcanvas-link">Tentang</a>
        <a href="#proses" @click.prevent="navigateTo('proses')" class="offcanvas-link">Cara Kerja</a>
        <a href="#kontak" @click.prevent="navigateTo('kontak')" class="offcanvas-link">Kontak</a>
      </nav>
      <!-- Action buttons — di atas nav links -->
    <div class="offcanvas-actions">
        <button v-if="auth.isAuthenticated" @click="goDash" class="offcanvas-btn-outline">
          <i class="ph ph-squares-four"></i> Dashboard Admin
        </button>
        <button v-else @click="goLogin" class="offcanvas-btn-outline">
          <i class="ph ph-sign-in"></i> Login
        </button>
        <a :href="waLink" target="_blank" class="offcanvas-btn-primary">
          <i class="ph-fill ph-whatsapp-logo" style="color:#c39a4d;"></i> WhatsApp
        </a>
      </div>

    </div>
  </Transition>
</template>

<style scoped>
/* ===== HEADER ===== */
.site-header {
  position: sticky;
  top: 0;
  z-index: 60;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid #ecebe4;
}
.site-header-inner {
  max-width: 1240px;
  margin: 0 auto;
  padding: 15px 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

/* ===== HAMBURGER ===== */
.hamburger-btn {
  display: none;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  width: 40px;
  height: 40px;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 8px;
  transition: background 0.2s;
}
.hamburger-btn:hover { background: #f0f2f5; }
.hamburger-btn span {
  display: block;
  width: 22px;
  height: 2px;
  background: #13233f;
  border-radius: 2px;
  transition: transform 0.3s ease, opacity 0.3s ease;
  transform-origin: center;
}
.hamburger-btn.active span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.hamburger-btn.active span:nth-child(2) { opacity: 0; transform: scaleX(0); }
.hamburger-btn.active span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* ===== BACKDROP ===== */
.offcanvas-bd {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 98;
}
.fade-bd-enter-active, .fade-bd-leave-active { transition: opacity 0.3s ease; }
.fade-bd-enter-from, .fade-bd-leave-to { opacity: 0; }

/* ===== OFF-CANVAS MENU ===== */
.offcanvas-nav {
  position: fixed;
  top: 0;
  right: 0;
  width: 300px;
  max-width: 85vw;
  height: 150vh;
  background: #fff;
  z-index: 99;
  display: flex;
  flex-direction: column;
  padding: 0;
  box-shadow: -8px 0 32px rgba(0,0,0,0.12);
  overflow-y: auto;
}
.slide-menu-enter-active { transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-menu-leave-active { transition: transform 0.28s cubic-bezier(0.4, 0, 0.6, 1); }
.slide-menu-enter-from, .slide-menu-leave-to { transform: translateX(100%); }

.offcanvas-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #f0efe8;
}
.offcanvas-close {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: #f4f5f8;
  border: none;
  font-size: 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #13233f;
  transition: background 0.2s;
}
.offcanvas-close:hover { background: #e8e9ee; }

.offcanvas-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 20px 24px;
  border-bottom: 1px solid #f0efe8;
}

.offcanvas-links {
  display: flex;
  flex-direction: column;
  padding: 12px 0;
}
.offcanvas-link {
  font-size: 15px;
  font-weight: 600;
  color: #3a4459;
  padding: 14px 24px;
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
  border-left: 3px solid transparent;
}
.offcanvas-link:hover {
  background: #f8f7f2;
  color: #15294f;
  border-left-color: #c39a4d;
}

.offcanvas-btn-outline {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 16px;
  border-radius: 10px;
  border: 1px solid #d9dbe0;
  background: #fff;
  color: #15294f;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.offcanvas-btn-outline:hover { background: #f4f5f8; }
.offcanvas-btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 16px;
  border-radius: 10px;
  background: #15294f;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.2s;
}
.offcanvas-btn-primary:hover { background: #1c3a6e; }

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .site-header-inner { padding: 14px 20px; }
  .hamburger-btn { display: flex !important; }
}
</style>
