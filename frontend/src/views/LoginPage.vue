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
        <div class="brand-badge">
          <img src="/favicon.ico" alt="SALN Logo" class="brand-logo" />
        </div>

        <div>
          <div class="brand-title">
            Statement of Assets, Liabilities, and Net Worth
          </div>
        </div>
      </div>
    </header>

    <!-- MAIN -->
    <main class="hero">

      <!-- LEFT CONTENT -->
      <section class="hero-left">

        <h1 class="title">
          Secure SALN Filing Portal
        </h1>

        <p class="subtitle">
          Efficiently create, update, organize, and generate official
          Statement of Assets, Liabilities, and Net Worth (SALN)
          documents through a secure digital filing platform designed
          for government personnel.
        </p>
        <div class="info-grid">

          <div class="info-card">
            <div class="info-icon"></div>

            <div>
              <h3>Secure Authentication</h3>

              <p>
                Email-based OTP verification for secure login and
                protected document management.
              </p>
            </div>
          </div>

          <div class="info-card">
            <div class="info-icon"></div>

            <div>
              <h3>Digital SALN Filing</h3>

              <p>
                Create, edit, organize, export, import, and generate
                official SALN PDF documents online.
              </p>
            </div>
          </div>

          <div class="info-card">
            <div class="info-icon"></div>

            <div>
              <h3>Autosave Drafts</h3>

              <p>
                Your progress is continuously saved so your filing remains safe
                even during interruptions.
              </p>
            </div>
          </div>

          <div class="info-card">
            <div class="info-icon"></div>

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

            <h2>Login to Continue</h2>

            <p>
              Enter your email address to receive a 6-digit
              verification code.
            </p>
          </div>

          <div v-if="status" class="status">
            {{ status }}
          </div>

          <form @submit.prevent="sendCode">

            <div class="form-row">
              <label for="email">Registered Email</label>

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
            Accounts inactive for more than 5 days may be automatically
            deleted from the system for storage management and security
            purposes.
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
  --bg-primary: #f4f6f8;
  --bg-secondary: #eef1f4;

  --surface: #ffffff;
  --surface-light: #fafbfc;

  --border: #d7dde5;
  --border-strong: #b8c2cc;

  --text-primary: #1f2937;
  --text-secondary: #4b5563;
  --text-muted: #6b7280;

  --accent: #1d4ed8;
  --accent-hover: #1e40af;

  --danger: #dc2626;

  --shadow: 0 8px 24px rgba(15, 23, 42, 0.08);

  --radius-xl: 24px;
  --radius-lg: 18px;
  --radius-md: 12px;
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

  background: var(--bg-primary);
  color: var(--text-primary);
}

body {
  overflow-x: hidden;
}

.page {
  width: 100%;
  min-height: 100vh;
  position: relative;
  background:
    linear-gradient(
      to bottom,
      #f8fafc,
      #f1f5f9
    );
}
.container {
  width: 100%;
  max-width: 1280px;

  margin: 0 auto;

  padding-left: 24px;
  padding-right: 24px;
}

/* REMOVE FUTURISTIC GLOWS */

.bg-glow {
  display: none;
}

/* TOPBAR */

.topbar {
  width: 100%;

  background: white;

  border-bottom: 1px solid var(--border);

  position: sticky;
  top: 0;
  z-index: 20;
}



.brand {
  display: flex;
  align-items: center;
  gap: 16px;
}
.logo {
  width: 54px;
  height: 54px;

  object-fit: contain;

  border-radius: 14px;
}

.brand-badge {
  width: 56px;
  height: 56px;

  display: flex;
  align-items: center;
  justify-content: center;


  background: white;

  overflow: hidden;
}

.brand-logo {
  width: 100%;
  height: 100%;

  object-fit: contain;
  padding: 6px;
}

.brand-title {
  font-size: 1rem;
  font-weight: 700;

  color: var(--text-primary);
}

/* HERO */

.hero {
  width: 100%;
  min-height: calc(100vh - 100px);

  padding: 50px 40px;

  display: grid;
  grid-template-columns: 1.1fr 0.9fr;

  gap: 50px;

  align-items: center;
}

/* LEFT */

.hero-left {
  max-width: 760px;
}

.title {
  margin: 0;

  font-size: clamp(2.8rem, 5vw, 4.8rem);

  line-height: 1.08;
  font-weight: 800;

  color: var(--text-primary);

  max-width: 760px;

  letter-spacing: -1px;
}

.subtitle {
  margin-top: 22px;

  color: var(--text-secondary);

  line-height: 1.8;

  font-size: 1.02rem;

  max-width: 700px;
}

/* INFO GRID */

.info-grid {
  margin-top: 36px;

  display: grid;

  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));

  gap: 18px;
}

.info-card {
  background: white;

  border: 1px solid var(--border);

  border-radius: 18px;

  padding: 22px;

  display: flex;
  gap: 16px;

  box-shadow: var(--shadow);

  transition:
    transform 0.18s ease,
    border-color 0.18s ease,
    box-shadow 0.18s ease;
}

.info-card:hover {
  transform: translateY(-2px);

  border-color: var(--border-strong);

  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}


.info-card p {
  margin: 0;

  color: var(--text-secondary);

  line-height: 1.65;

  font-size: 0.92rem;
}

/* RIGHT */

.hero-right {
  display: flex;
  justify-content: center;
}

.login-card {
  width: 100%;
  max-width: 470px;

  background: white;

  border: 1px solid var(--border);

  border-radius: var(--radius-xl);

  padding: 36px;

  box-shadow: var(--shadow);
}

.login-header {
  margin-bottom: 24px;
}

.login-header h2 {
  margin: 0;

  font-size: 2rem;

  color: var(--text-primary);
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

  font-size: 0.78rem;
  font-weight: 700;

  text-transform: uppercase;

  letter-spacing: 0.7px;

  color: var(--text-secondary);
}

input[type='email'],
input[type='text'] {
  width: 100%;

  border: 1px solid var(--border);

  background: white;

  border-radius: 12px;

  padding: 14px;

  color: var(--text-primary);

  font-size: 1rem;

  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease;
}

input::placeholder {
  color: var(--text-muted);
}

input:focus {
  outline: none;

  border-color: var(--accent);

  box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
}

/* BUTTON */

.primary-btn {
  width: 100%;

  border: none;

  border-radius: 12px;

  padding: 15px;

  cursor: pointer;

  font-size: 1rem;
  font-weight: 700;

  background: var(--accent);

  color: white;

  transition:
    background 0.15s ease,
    transform 0.15s ease;
}

.primary-btn:hover {
  background: var(--accent-hover);

  transform: translateY(-1px);
}

/* LOGIN FOOTER */

.login-footer {
  margin-top: 24px;

  padding-top: 18px;

  border-top: 1px solid var(--border);

  color: var(--text-muted);

  line-height: 1.7;

  font-size: 0.86rem;
}

/* STATUS */

.status {
  margin-bottom: 18px;

  padding: 14px;

  border-radius: 12px;

  background: #eff6ff;

  border: 1px solid #bfdbfe;

  color: #1e40af;

  font-weight: 600;
}

/* ERROR */

.error {
  margin-top: 8px;

  color: var(--danger);

  font-size: 0.85rem;

  font-weight: 600;
}

/* MODAL */

.modal-overlay {
  position: fixed;

  inset: 0;

  background: rgba(15, 23, 42, 0.45);

  backdrop-filter: blur(4px);

  display: flex;
  align-items: center;
  justify-content: center;

  z-index: 999;

  padding: 20px;
}

.modal {
  width: 100%;
  max-width: 460px;

  background: white;

  border: 1px solid var(--border);

  border-radius: 24px;

  padding: 28px;

  box-shadow:
    0 20px 40px rgba(15, 23, 42, 0.12);
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

  background: #eff6ff;

  border: 1px solid #bfdbfe;

  color: #1e40af;

  font-size: 0.76rem;

  font-weight: 700;

  margin-bottom: 10px;
}

.modal-header h2 {
  margin: 0;

  color: var(--text-primary);
}

.modal-text {
  margin: 18px 0 24px;

  color: var(--text-secondary);

  line-height: 1.7;
}

.close-btn {
  width: 40px;
  height: 40px;

  border-radius: 10px;

  border: 1px solid var(--border);

  background: white;

  color: var(--text-primary);

  cursor: pointer;

  font-size: 1rem;

  transition:
    background 0.15s ease,
    border-color 0.15s ease;
}

.close-btn:hover {
  background: #f3f4f6;

  border-color: var(--border-strong);
}

/* RESPONSIVE */

@media (max-width: 1100px) {
  .hero {
    grid-template-columns: 1fr;

    padding-top: 30px;
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
    padding: 20px;
  }

  .title {
    font-size: 2.5rem;
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
  background: #cbd5e1;

  border-radius: 999px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
}
</style>