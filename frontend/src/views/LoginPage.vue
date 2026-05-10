<script setup>
import { ref } from 'vue'
import authAPI from '@/services/api'
import { useRouter } from 'vue-router'

const router = useRouter()

const showModal = ref(false)
const status = ref(false)
const email = ref('')
const send_code_error = ref('')
const otpCode = ref('')
const otpError = ref('')

function toggle() {
  showModal.value = !showModal.value
}

async function sendCode() {
  try {
    const response = await authAPI.post('/login/send-code', { email: email.value })

    showModal.value = response.data.otp_sent
    status.value = response.data.status
  } catch (error) {
    send_code_error.value = error.response?.data?.message || 'Login Failed'
  }
}

async function verifyCode() {
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
    otpError.value = error.response?.data?.message || 'Verification failed'
  }
}
</script>

<template>
  <div class="container">
    <h1>SALN Portal</h1>
    <p>Login using your email and a 6-digit verification code.</p>

    <div class="status" v-if="status">{{ status }}</div>

    <div class="card">
      <form @submit.prevent="sendCode">
        <!-- @csrf -->
        <div class="form-row">
          <label for="email">Email Address</label>
          <input v-model="email" id="email" name="email" type="email" required />
          <div v-if="send_code_error" class="error">{{ send_code_error }}</div>
        </div>

        <button type="submit">Send 6-Digit Code</button>
      </form>
    </div>
  </div>

  <div id="verifyModal" class="modal-overlay show" :aria-hidden="!showModal" v-if="showModal">
    <div class="modal">
      <div class="modal-header">
        <h2>Verify Login</h2>
        <button
          type="button"
          id="closeVerifyModal"
          class="close-btn"
          aria-label="Close verification modal"
          @click="toggle"
        >
          X
        </button>
      </div>
      <p>Enter the 6-digit code sent to your email.</p>

      <form @submit.prevent="verifyCode">
        <!-- @csrf -->
        <div class="form-row">
          <label for="code">Verification Code</label>
          <input
            v-model="otpCode"
            id="code"
            name="code"
            type="text"
            inputmode="numeric"
            maxlength="6"
            required
          />
          <!-- @error('code') -->
          <div class="error">{{ otpError }}</div>
          <!-- @enderror -->
        </div>
        <button type="submit">Verify and Login</button>
      </form>
    </div>
  </div>
  <button @click="toggle">Toggle</button>
</template>

<style>
.container {
  width: min(100%, 540px);
}

h1 {
  margin: 0 0 8px;
  letter-spacing: 0.02em;
  font-size: clamp(1.8rem, 4vw, 2.4rem);
}

p {
  margin: 0 0 18px;
  color: var(--muted);
}

.card {
  border: 1px solid var(--line);
  background: var(--card-bg);
  box-shadow: 0 10px 34px rgba(25, 42, 52, 0.09);
  padding: 20px;
  border-radius: var(--radius);
}

.form-row {
  margin-bottom: 14px;
}

label {
  display: block;
  margin-bottom: 7px;
  font-weight: 650;
}

input[type='email'],
input[type='text'] {
  width: 100%;
  padding: 11px 12px;
  border-radius: 10px;
  border: 1px solid #c6d0ca;
  background: #fff;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

input[type='email']:focus,
input[type='text']:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
}

button {
  border: 0;
  border-radius: 11px;
  padding: 10px 16px;
  font-weight: 650;
  cursor: pointer;
  background: var(--accent);
  color: #fff;
  transition:
    transform 0.12s ease,
    background 0.2s ease;
}

button:hover {
  background: var(--accent-hover);
  transform: translateY(-1px);
}

button:active {
  transform: translateY(0);
}

.status {
  border: 1px solid #b2d6cf;
  background: #ecfbf7;
  color: #11443e;
  border-radius: 10px;
  padding: 11px 12px;
  margin-bottom: 14px;
}

.error {
  color: var(--danger);
  font-size: 14px;
  margin-top: 5px;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 21, 26, 0.5);
  backdrop-filter: blur(2px);
  display: none;
  align-items: center;
  justify-content: center;
  padding: 16px;
}
.modal-overlay.show {
  display: flex;
}
.modal {
  width: 100%;
  max-width: 420px;
  background: #fff;
  border: 1px solid #d4ddda;
  border-radius: var(--radius);
  box-shadow: 0 18px 38px rgba(0, 0, 0, 0.22);
  padding: 18px;
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 10px;
}
.close-btn {
  border: 1px solid #c8d2ce;
  background: #fff;
  color: #35454b;
  padding: 5px 9px;
  line-height: 1;
}

@media (max-width: 640px) {
  body {
    padding: 16px;
  }

  .card {
    padding: 16px;
  }
}
</style>
