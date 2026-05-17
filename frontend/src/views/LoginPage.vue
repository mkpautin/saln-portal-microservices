<script setup>
import { ref } from 'vue'
import authAPI from '@/services/api'
import { useRouter } from 'vue-router'

const router = useRouter()

const showModal = ref(false)
const status = ref('')
const email = ref('')
const send_code_error = ref('')
const otpCode = ref('')
const otpError = ref('')

function toggle() {
  showModal.value = !showModal.value
}

async function sendCode() {
  send_code_error.value = ''

  try {
    const response = await authAPI.post('/login/send-code', {
      email: email.value,
    })

    showModal.value = response.data.otp_sent
    status.value = response.data.status
  } catch (error) {
    send_code_error.value =
      error.response?.data?.message || 'Unable to send verification code.'
  }
}

async function verifyCode() {
  otpError.value = ''

  try {
    const response = await authAPI.post('/login/verify', {
      email: email.value,
      code: otpCode.value,
      otp_sent: showModal.value,
    })

    if (response.status === 200) {
      localStorage.setItem('access_token', response.data.access_token)
      router.replace({ path: '/saln' })
    }
  } catch (error) {
    otpError.value =
      error.response?.data?.message || 'Verification failed.'
  }
}
</script>

<template>
  <div class="page">

    <!-- BACKGROUND GLOW -->
    <div class="bg-glow glow-1"></div>
    <div class="bg-glow glow-2"></div>

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="brand">
        <div class="brand-badge">SALN</div>

        <div>
          <div class="brand-title">
            Statement of Assets, Liabilities, and Net Worth
          </div>

          <div class="brand-subtitle">
            Secure Government Filing Portal
          </div>
        </div>
      </div>

      <div class="topbar-right">
        Republic Compliance System
      </div>
    </header>

    <!-- MAIN -->
    <main class="hero">

      <!-- LEFT CONTENT -->
      <section class="hero-left">

        <div class="hero-chip">
          Official Digital Filing Platform
        </div>

        <h1 class="title">
          Secure SALN Filing and Verification Portal
        </h1>

        <p class="subtitle">
          Access your Statement of Assets, Liabilities, and Net Worth records
          through a secure one-time verification system designed for government
          employees and authorized personnel.
        </p>

        <div class="info-grid">

          <div class="info-card">
            <div class="info-icon">✓</div>

            <div>
              <h3>Secure Authentication</h3>

              <p>
                Email-based OTP verification for protected account access and
                secure document handling.
              </p>
            </div>
          </div>

          <div class="info-card">
            <div class="info-icon">📄</div>

            <div>
              <h3>Digital SALN Filing</h3>

              <p>
                Create, update, export, import, and generate official SALN PDF
                documents online.
              </p>
            </div>
          </div>

          <div class="info-card">
            <div class="info-icon">⚡</div>

            <div>
              <h3>Autosave Drafts</h3>

              <p>
                Your progress is continuously saved so your filing remains safe
                even during interruptions.
              </p>
            </div>
          </div>

          <div class="info-card">
            <div class="info-icon">🔒</div>

            <div>
              <h3>Protected Access</h3>

              <p>
                Built with secure API authentication and encrypted verification
                workflows.
              </p>
            </div>
          </div>

        </div>
      </section>

      <!-- LOGIN CARD -->
      <section class="hero-right">

        <div class="login-card">

          <div class="login-header">
            <div class="login-badge">
              Government Portal Access
            </div>

            <h2>Login to Continue</h2>

            <p>
              Enter your registered email address to receive a 6-digit
              verification code.
            </p>
          </div>

          <div v-if="status" class="status">
            {{ status }}
          </div>

          <form @submit.prevent="sendCode">

            <div class="form-row">
              <label for="email">Government / Registered Email</label>

              <input
                v-model="email"
                id="email"
                name="email"
                type="email"
                placeholder="name@example.gov.ph"
                required
              />

              <div v-if="send_code_error" class="error">
                {{ send_code_error }}
              </div>
            </div>

            <button type="submit" class="primary-btn">
              Send Verification Code
            </button>

          </form>

          <div class="login-footer">
            Authorized personnel only. All login attempts are monitored and
            recorded for security purposes.
          </div>

        </div>

      </section>

    </main>

    <!-- OTP MODAL -->

    <div
      id="verifyModal"
      class="modal-overlay"
      :aria-hidden="!showModal"
      v-if="showModal"
    >
      <div class="modal">

        <div class="modal-header">

          <div>
            <div class="modal-chip">
              Verification Required
            </div>

            <h2>Enter Verification Code</h2>
          </div>

          <button
            type="button"
            class="close-btn"
            aria-label="Close verification modal"
            @click="toggle"
          >
            ✕
          </button>

        </div>

        <p class="modal-text">
          A 6-digit verification code has been sent to your email address.
        </p>

        <form @submit.prevent="verifyCode">

          <div class="form-row">
            <label for="code">Verification Code</label>

            <input
              v-model="otpCode"
              id="code"
              name="code"
              type="text"
              inputmode="numeric"
              maxlength="6"
              placeholder="Enter 6-digit code"
              required
            />

            <div v-if="otpError" class="error">
              {{ otpError }}
            </div>
          </div>

          <button type="submit" class="primary-btn">
            Verify and Login
          </button>

        </form>

      </div>
    </div>

  </div>
</template>

<style>
:root {
  --bg-primary: #071614;
  --bg-secondary: #0d2622;

  --surface: rgba(16, 36, 32, 0.94);
  --surface-light: rgba(24, 48, 44, 0.96);

  --border: #2f5f56;
  --border-strong: #56e3bf;

  --text-primary: #f5fffc;
  --text-secondary: #b6d6cf;
  --text-muted: #8fb5ac;

  --accent: #1ad1a5;
  --accent-hover: #11b68e;

  --danger: #ff7085;

  --shadow: 0 10px 30px rgba(0, 0, 0, 0.35);

  --radius-xl: 28px;
  --radius-lg: 22px;
  --radius-md: 16px;
}

* {
  box-sizing: border-box;
}

html,
body,
#app {
  margin: 0;
  width: 100%;
  min-height: 100%;
  font-family:
    Inter,
    'Segoe UI',
    sans-serif;
  background:
    radial-gradient(circle at top left, #103b33 0%, transparent 30%),
    radial-gradient(circle at bottom right, #124c41 0%, transparent 30%),
    linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
  color: var(--text-primary);
}

body {
  overflow-x: hidden;
}

.page {
  width: 100%;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

/* GLOW EFFECTS */

.bg-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
  opacity: 0.16;
  pointer-events: none;
}

.glow-1 {
  width: 320px;
  height: 320px;
  background: #12d8a8;
  top: -80px;
  left: -60px;
}

.glow-2 {
  width: 360px;
  height: 360px;
  background: #3d8dff;
  bottom: -120px;
  right: -80px;
}

/* TOPBAR */

.topbar {
  width: 100%;
  padding: 22px 36px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  z-index: 2;
}

.brand {
  display: flex;
  align-items: center;
  gap: 16px;
}

.brand-badge {
  width: 58px;
  height: 58px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    linear-gradient(135deg, #1ad1a5, #0f9c7b);
  color: white;
  font-weight: 1000;
  font-size: 1rem;
  box-shadow: 0 8px 22px rgba(26, 209, 165, 0.35);
}

.brand-title {
  font-size: 1rem;
  font-weight: 900;
  color: white;
}

.brand-subtitle {
  color: var(--text-muted);
  font-size: 0.88rem;
  margin-top: 2px;
}

.topbar-right {
  color: var(--text-secondary);
  font-size: 0.92rem;
  font-weight: 700;
}

/* HERO */

.hero {
  width: 100%;
  min-height: calc(100vh - 100px);
  padding: 30px 40px 50px;
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 40px;
  align-items: center;
  position: relative;
  z-index: 2;
}

/* LEFT */

.hero-left {
  max-width: 760px;
}

.hero-chip {
  display: inline-flex;
  align-items: center;
  padding: 10px 18px;
  border-radius: 999px;
  background: rgba(26, 209, 165, 0.14);
  border: 1px solid rgba(86, 227, 191, 0.45);
  color: #dffff7;
  font-weight: 800;
  margin-bottom: 24px;
}

.title {
  margin: 0;
  font-size: clamp(3rem, 5vw, 5.2rem);
  line-height: 1.05;
  font-weight: 1000;
  color: white;
  max-width: 760px;
}

.subtitle {
  margin-top: 22px;
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 1.05rem;
  max-width: 700px;
}

/* INFO GRID */

.info-grid {
  margin-top: 34px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 18px;
}

.info-card {
  background: rgba(18, 39, 35, 0.92);
  border: 1px solid #2e5f56;
  border-radius: 22px;
  padding: 22px;
  display: flex;
  gap: 16px;
  transition:
    transform 0.2s ease,
    border-color 0.2s ease;
}

.info-card:hover {
  transform: translateY(-3px);
  border-color: #5ce8c3;
}

.info-icon {
  width: 52px;
  height: 52px;
  border-radius: 16px;
  background:
    linear-gradient(135deg, #1ad1a5, #0f9c7b);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
  color: white;
  font-weight: 900;
}

.info-card h3 {
  margin: 0 0 8px;
  font-size: 1rem;
  color: white;
}

.info-card p {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.6;
  font-size: 0.93rem;
}

/* RIGHT */

.hero-right {
  display: flex;
  justify-content: center;
}

.login-card {
  width: 100%;
  max-width: 470px;
  background: rgba(12, 28, 25, 0.96);
  border: 1px solid #2d6057;
  border-radius: var(--radius-xl);
  padding: 34px;
  box-shadow: var(--shadow);
  backdrop-filter: blur(16px);
}

.login-header {
  margin-bottom: 24px;
}

.login-badge {
  display: inline-flex;
  padding: 8px 14px;
  border-radius: 999px;
  background: rgba(26, 209, 165, 0.14);
  border: 1px solid rgba(86, 227, 191, 0.4);
  color: #dffff7;
  font-size: 0.82rem;
  font-weight: 900;
  margin-bottom: 16px;
}

.login-header h2 {
  margin: 0;
  font-size: 2rem;
  color: white;
}

.login-header p {
  margin-top: 12px;
  color: var(--text-secondary);
  line-height: 1.7;
}

/* FORM */

.form-row {
  margin-bottom: 18px;
}

label {
  display: block;
  margin-bottom: 8px;
  font-size: 0.82rem;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #effffb;
}

input[type='email'],
input[type='text'] {
  width: 100%;
  border: 2px solid #315d55;
  background: rgba(7, 20, 18, 0.98);
  border-radius: 16px;
  padding: 15px;
  color: white;
  font-size: 1rem;
  transition:
    border-color 0.18s ease,
    box-shadow 0.18s ease;
}

input::placeholder {
  color: #88aea5;
}

input:focus {
  outline: none;
  border-color: #1de4b5;
  box-shadow: 0 0 0 4px rgba(29, 228, 181, 0.15);
}

/* BUTTON */

.primary-btn {
  width: 100%;
  border: none;
  border-radius: 18px;
  padding: 15px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 900;
  letter-spacing: 0.4px;
  background:
    linear-gradient(135deg, #1ad1a5, #0f9c7b);
  color: white;
  transition:
    transform 0.18s ease,
    box-shadow 0.18s ease;
  box-shadow: 0 10px 24px rgba(26, 209, 165, 0.28);
}

.primary-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(26, 209, 165, 0.4);
}

/* FOOTER */

.login-footer {
  margin-top: 22px;
  padding-top: 18px;
  border-top: 1px solid rgba(255,255,255,0.08);
  color: var(--text-muted);
  line-height: 1.7;
  font-size: 0.86rem;
}

/* STATUS */

.status {
  margin-bottom: 18px;
  padding: 14px;
  border-radius: 14px;
  background: rgba(26, 209, 165, 0.14);
  border: 1px solid rgba(86, 227, 191, 0.38);
  color: #dbfff7;
  font-weight: 700;
}

/* ERROR */

.error {
  margin-top: 8px;
  color: var(--danger);
  font-size: 0.85rem;
  font-weight: 700;
}

/* MODAL */

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.72);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
  padding: 20px;
}

.modal {
  width: 100%;
  max-width: 460px;
  background: rgba(12, 28, 25, 0.98);
  border: 1px solid #2d6057;
  border-radius: 28px;
  padding: 28px;
  box-shadow: var(--shadow);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  gap: 20px;
}

.modal-chip {
  display: inline-flex;
  padding: 7px 12px;
  border-radius: 999px;
  background: rgba(26, 209, 165, 0.14);
  border: 1px solid rgba(86, 227, 191, 0.38);
  color: #dbfff7;
  font-size: 0.78rem;
  font-weight: 900;
  margin-bottom: 10px;
}

.modal-header h2 {
  margin: 0;
  color: white;
}

.modal-text {
  margin: 18px 0 24px;
  color: var(--text-secondary);
  line-height: 1.7;
}

.close-btn {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  border: 1px solid #3a6a61;
  background: rgba(255,255,255,0.04);
  color: white;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.18s ease;
}

.close-btn:hover {
  background: rgba(255,255,255,0.1);
}

/* RESPONSIVE */

@media (max-width: 1100px) {
  .hero {
    grid-template-columns: 1fr;
    padding-top: 20px;
  }

  .hero-left {
    max-width: 100%;
  }

  .hero-right {
    width: 100%;
  }

  .login-card {
    max-width: 100%;
  }
}

@media (max-width: 760px) {
  .topbar {
    padding: 20px;
    flex-direction: column;
    gap: 16px;
    align-items: flex-start;
  }

  .hero {
    padding: 10px 20px 40px;
  }

  .title {
    font-size: 2.7rem;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .login-card {
    padding: 24px;
  }
}

/* SCROLLBAR */

::-webkit-scrollbar {
  width: 10px;
}

::-webkit-scrollbar-thumb {
  background: #1a5c50;
  border-radius: 999px;
}

::-webkit-scrollbar-track {
  background: #081613;
}
</style>