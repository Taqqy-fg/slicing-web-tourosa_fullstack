<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const rememberMe = ref(true)
const error = ref('')
const loading = ref(false)

const captchaQuestion = ref('')
const captchaAnswer = ref(0)
const userCaptcha = ref('')

function generateCaptcha() {
  const num1 = Math.floor(Math.random() * 10) + 1
  const num2 = Math.floor(Math.random() * 10) + 1
  const isAdd = Math.random() > 0.5
  
  if (isAdd) {
    captchaQuestion.value = `${num1} + ${num2}`
    captchaAnswer.value = num1 + num2
  } else {
    const max = Math.max(num1, num2)
    const min = Math.min(num1, num2)
    captchaQuestion.value = `${max} - ${min}`
    captchaAnswer.value = max - min
  }
  userCaptcha.value = ''
}

onMounted(() => {
  generateCaptcha()
})

async function handleLogin() {
  if (userCaptcha.value.trim() === '' || Number(userCaptcha.value) !== captchaAnswer.value) {
    error.value = 'Jawaban captcha salah.'
    generateCaptcha()
    return
  }

  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value, rememberMe.value)
    router.push('/dashboard')
  } catch (e) {
    const msg = e.response?.data?.message || e.response?.data?.errors?.email?.[0] || 'Terjadi kesalahan. Silakan coba lagi.'
    error.value = msg
    generateCaptcha()
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <!-- Left panel -->
    <div class="login-left">
      <div class="login-brand">
        <img src="/assets/tourosa-logo-white.png" alt="Tourosa" class="brand-logo-img" />
        <p class="brand-tagline">Sistem manajemen pesanan grup travel Anda.</p>
      </div>
      <div class="login-decoration">
        <div class="deco-circle deco-1"></div>
        <div class="deco-circle deco-2"></div>
        <div class="deco-line"></div>
      </div>
    </div>

    <!-- Right panel (form) -->
    <div class="login-right">
      <div class="login-form-wrapper">
        <!-- <div class="login-form-header">
          <h2>Selamat Datang</h2>
          <p>Masuk ke panel administrasi Tourosa</p>
        </div> -->

        <form @submit.prevent="handleLogin" class="login-form">
          <div v-if="error" class="login-error">
            <i class="ph ph-warning-circle"></i>
            {{ error }}
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <div class="input-wrap">
              <i class="ph ph-envelope input-icon"></i>
              <input
                id="email"
                v-model="email"
                type="email"
                placeholder="Masukkan email"
                required
                autocomplete="email"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrap">
              <i class="ph ph-lock-key input-icon"></i>
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Masukkan password"
                required
                autocomplete="current-password"
                style="padding-right: 42px;"
              />
              <button type="button" class="toggle-password" @click="showPassword = !showPassword" tabindex="-1">
                <i :class="showPassword ? 'ph ph-eye-slash' : 'ph ph-eye'"></i>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label for="captcha">Berapa hasil dari {{ captchaQuestion }}?</label>
            <div class="input-wrap">
              <i class="ph ph-math-operations input-icon"></i>
              <input
                id="captcha"
                v-model="userCaptcha"
                type="text"
                inputmode="numeric"
                placeholder="Jawaban"
                required
                autocomplete="off"
              />
            </div>
          </div>

          <div class="form-row-between">
            <label class="remember-check">
              <input type="checkbox" v-model="rememberMe" />
              <span>Ingat saya</span>
            </label>
          </div>

          <button type="submit" class="login-btn" :disabled="loading">
            <span v-if="loading" class="btn-spinner"></span>
            <span v-else>Masuk</span>
          </button>
        </form>

        <div class="login-footer">
          <p>&copy; 2026 Tourosa. All rights reserved.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  display: flex;
  min-height: 100vh;
  min-height: 100dvh;
  background: #f4f5f8;
}

/* ── Left Panel ── */
.login-left {
  flex: 1;
  background: #15294f;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  padding: 40px;
}

.login-brand {
  position: relative;
  z-index: 2;
  text-align: center;
}

.brand-logo-img {
  display: block;
  height: 48px;
  width: auto;
  margin: 0 auto 20px;
  object-fit: contain;
}

.brand-tagline {
  font-size: 15px;
  color: rgba(255, 255, 255, 0.55);
  margin: 0 auto;
  max-width: 260px;
  line-height: 1.5;
  text-align: center;
}

.login-decoration {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.deco-circle {
  position: absolute;
  border-radius: 50%;
  border: 1px solid rgba(195, 154, 77, 0.12);
}

.deco-1 {
  width: 320px;
  height: 320px;
  bottom: -80px;
  left: -80px;
}

.deco-2 {
  width: 200px;
  height: 200px;
  top: -40px;
  right: -40px;
}

.deco-line {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #c39a4d, #d7c69a, #c39a4d);
}

/* ── Right Panel ── */
.login-right {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

.login-form-wrapper {
  width: 100%;
  max-width: 400px;
}

.login-form-header {
  margin-bottom: 32px;
}

.login-form-header h2 {
  font-size: 26px;
  font-weight: 800;
  color: #13233f;
  margin: 0 0 6px;
  letter-spacing: -0.01em;
}

.login-form-header p {
  font-size: 14px;
  color: #7a8499;
  margin: 0;
}

/* ── Error ── */
.login-error {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
  font-size: 13px;
  font-weight: 600;
  padding: 12px 14px;
  border-radius: 10px;
  margin-bottom: 20px;
}

.login-error i {
  font-size: 16px;
  flex-shrink: 0;
}

/* ── Form ── */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: #13233f;
  margin-bottom: 6px;
}

.input-wrap {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 17px;
  color: #9aa3b2;
  pointer-events: none;
}

.input-wrap input {
  width: 100%;
  padding: 13px 14px 13px 42px;
  border: 1.5px solid #e2e4ea;
  border-radius: 10px;
  font-size: 14px;
  color: #13233f;
  background: #fff;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.input-wrap input::placeholder {
  color: #b0b8c8;
}

.input-wrap input:focus {
  border-color: #c39a4d;
  box-shadow: 0 0 0 3px rgba(195, 154, 77, 0.12);
}

.toggle-password {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9aa3b2;
  font-size: 17px;
  transition: color 0.2s;
}

.toggle-password:hover {
  color: #13233f;
}

/* ── Remember Me ── */
.form-row-between {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.remember-check {
  display: flex;
  align-items: center;
  gap: 7px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  color: #5f6b80;
  user-select: none;
}

.remember-check input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: #15294f;
  cursor: pointer;
  border-radius: 4px;
}

/* ── Button ── */
.login-btn {
  width: 100%;
  padding: 14px;
  background: #15294f;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 4px;
}

.login-btn:hover:not(:disabled) {
  background: #1a3360;
  transform: translateY(-1px);
}

.login-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-spinner {
  width: 18px;
  height: 18px;
  border: 2.5px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ── Footer ── */
.login-footer {
  margin-top: 40px;
  text-align: center;
}

.login-footer p {
  font-size: 12px;
  color: #7a8499;
  margin: 0;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .login-page {
    flex-direction: column;
  }

  .login-left {
    flex: none;
    padding: 48px 24px 36px;
    min-height: auto;
  }

  .brand-logo-img {
    height: 38px;
    margin-bottom: 14px;
  }

  .brand-tagline {
    font-size: 13px;
  }

  .deco-1 {
    width: 200px;
    height: 200px;
    bottom: -60px;
    left: -60px;
  }

  .deco-2 {
    width: 120px;
    height: 120px;
    top: -30px;
    right: -30px;
  }

  .login-right {
    flex: 1;
    padding: 32px 24px;
  }

  .login-form-header h2 {
    font-size: 22px;
  }
}
</style>
