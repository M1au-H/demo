<template>
  <div class="si-wrap">

    <!-- Heading -->
    <h1 class="si-heading">
      Sign in to your<br /><span class="si-heading-red">account</span>
    </h1>

    <p class="si-sub">
      New here?
      <router-link to="/sign-up" class="si-link">Create an Account</router-link>
    </p>

    <VForm
      id="kt_login_signin_form"
      @submit="onSubmitLogin"
      :validation-schema="loginSchema"
      :initial-values="initialValues"
    >

      <!-- Email -->
      <div class="si-field">
        <label class="si-label">Email address</label>
        <div class="si-inp-wrap">
          <span class="si-inp-ico">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="3" width="14" height="10" rx="2"/><polyline points="1,3 8,9 15,3"/>
            </svg>
          </span>
          <Field
            tabindex="1"
            class="si-input"
            placeholder="Enter your email"
            name="email"
            type="text"
            autocomplete="email"
          />
        </div>
        <!-- Last used email badge -->
        <div v-if="lastEmail" class="si-last-email" @click="useLastEmail">
          <svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 8A6 6 0 1 1 8 2"/><polyline points="14 2 14 8 8 8"/>
          </svg>
          Use last: <strong>{{ lastEmail }}</strong>
        </div>
        <div class="fv-plugins-message-container mt-1">
          <div class="si-error"><ErrorMessage name="email" /></div>
        </div>
      </div>

      <!-- Password -->
      <div class="si-field">
        <div class="si-field-top">
          <label class="si-label" style="margin-bottom:0">Password</label>
          <router-link to="/password-reset" class="si-forgot">Forgot Password?</router-link>
        </div>
        <div class="si-inp-wrap" style="margin-top:7px">
          <span class="si-inp-ico">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="7" width="10" height="8" rx="1.5"/>
              <path d="M5 7V5a3 3 0 016 0v2"/>
              <circle cx="8" cy="11" r="1" fill="currentColor" stroke="none"/>
            </svg>
          </span>
          <Field
            tabindex="2"
            class="si-input si-input-pwd"
            placeholder="Min. 4 characters"
            name="password"
            :type="showPwd ? 'text' : 'password'"
            autocomplete="current-password"
          />
          <button type="button" class="si-eye-btn" @click="showPwd = !showPwd">
            <svg v-if="!showPwd" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>
        <div class="fv-plugins-message-container mt-1">
          <div class="si-error"><ErrorMessage name="password" /></div>
        </div>
      </div>

      <!-- Remember me -->
      <div class="si-remember">
        <input type="checkbox" class="si-chk" id="sf-rem" v-model="rememberMe" />
        <label class="si-chk-label" for="sf-rem">Keep me signed in for 30 days</label>
      </div>

      <!-- Submit -->
      <button
        tabindex="3"
        type="submit"
        ref="submitButton"
        id="kt_sign_in_submit"
        class="si-submit-btn"
        :disabled="isLoading"
      >
        <span v-if="!isLoading" class="si-btn-inner">
          Continue
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M1 7h12M8 2l5 5-5 5" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        <span v-else class="si-btn-inner">
          <span class="si-spin"></span>
          Please wait...
        </span>
      </button>

    </VForm>

    <div class="si-divider"><span>OR</span></div>

    <div class="si-socials">
      <button class="si-social-btn" type="button">
        <svg viewBox="0 0 24 24" width="18" height="18" style="flex-shrink:0">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continue with Google
      </button>
      <button class="si-social-btn" type="button">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="#1877F2" style="flex-shrink:0">
          <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
        </svg>
        Continue with Facebook
      </button>
      <button class="si-social-btn" type="button">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="#f0f0f5" style="flex-shrink:0">
          <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
        </svg>
        Continue with Apple
      </button>
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed } from 'vue'
import { ErrorMessage, Field, Form as VForm } from 'vee-validate'
import { useAuthStore, type User } from '@/stores/auth'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2/dist/sweetalert2.js'
import * as Yup from 'yup'

const LAST_EMAIL_KEY = 'si_last_email'

export default defineComponent({
  name: 'sign-in',
  components: { Field, VForm, ErrorMessage },

  setup() {
    const store  = useAuthStore()
    const router = useRouter()

    const submitButton = ref<HTMLButtonElement | null>(null)
    const isLoading    = ref(false)
    const showPwd      = ref(false)
    const rememberMe   = ref(true)

    // Last used email from localStorage
    const lastEmail = ref<string>(localStorage.getItem(LAST_EMAIL_KEY) || '')

    // Pre-fill email field with last used email if available
    const initialValues = computed(() => ({
      email: lastEmail.value || '',
      password: '',
    }))

    // Clicking the badge fills the field via re-setting initialValues
    // We use a separate ref so VForm reactively picks it up
    const useLastEmail = () => {
      // Trigger native input event on the email field so VeeValidate picks it up
      const input = document.querySelector<HTMLInputElement>('#kt_login_signin_form input[name="email"]')
      if (input) {
        input.value = lastEmail.value
        input.dispatchEvent(new Event('input', { bubbles: true }))
        input.focus()
      }
    }

    const loginSchema = Yup.object().shape({
      email:    Yup.string().email().required().label('Email'),
      password: Yup.string().min(4).required().label('Password'),
    })

    const onSubmitLogin = async (values: any) => {
      values = values as User
      store.logout()
      isLoading.value = true
      if (submitButton.value) {
        submitButton.value.disabled = true
        submitButton.value.setAttribute('data-kt-indicator', 'on')
      }

      await store.adminLogin(values)
      if (Object.values(store.errors).length > 0) {
        store.errors = {}
        await store.login(values)
      }

      const error = Object.values(store.errors)

      if (error.length === 0) {
        // Save last used email on successful login
        localStorage.setItem(LAST_EMAIL_KEY, values.email)
        lastEmail.value = values.email

        Swal.fire({
          text: 'You have successfully logged in!',
          icon: 'success',
          buttonsStyling: false,
          confirmButtonText: 'Ok, got it!',
          heightAuto: false,
          customClass: { confirmButton: 'btn fw-semibold btn-light-primary' },
        }).then(() => {
          store.isAdmin()
            ? router.push({ name: 'dashboard' })
            : router.push({ name: 'user-dashboard' })
        })
      } else {
        Swal.fire({
          text: error[0] as string,
          icon: 'error',
          buttonsStyling: false,
          confirmButtonText: 'Try again!',
          heightAuto: false,
          customClass: { confirmButton: 'btn fw-semibold btn-light-danger' },
        }).then(() => { store.errors = {} })
      }

      isLoading.value = false
      submitButton.value?.removeAttribute('data-kt-indicator')
      if (submitButton.value) submitButton.value.disabled = false
    }

    return {
      onSubmitLogin, loginSchema, submitButton,
      isLoading, showPwd, rememberMe,
      lastEmail, initialValues, useLastEmail,
    }
  },
})
</script>

<style scoped>
.si-wrap {
  width: 100%;
  animation: si-fadeup 0.4s ease both;
}
@keyframes si-fadeup {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}
.si-heading {
  font-size: 28px;
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.025em;
  color: #f0f0f5;
  margin-bottom: 10px;
}
.si-heading-red { color: #e8262a; }
.si-sub { font-size: 13px; color: #55555e; margin-bottom: 28px; }
.si-link { color: #1b84ff; font-weight: 600; text-decoration: none; transition: color 0.15s; }
.si-link:hover { color: #5aabff; }
.si-field { margin-bottom: 16px; }
.si-field-top { display: flex; justify-content: space-between; align-items: center; }
.si-label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; margin-bottom: 7px; }
.si-forgot { font-size: 11.5px; color: #1b84ff; font-weight: 600; text-decoration: none; transition: color 0.15s; }
.si-forgot:hover { color: #5aabff; }
.si-inp-wrap { position: relative; }
.si-inp-ico { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #3a3a48; pointer-events: none; display: flex; align-items: center; }
.si-input {
  width: 100%;
  background: #181b22 !important;
  border: 1.5px solid rgba(255,255,255,0.08) !important;
  border-radius: 10px !important;
  color: #e2e2e8 !important;
  font-family: 'Inter', sans-serif !important;
  font-size: 13.5px !important;
  padding: 12px 14px 12px 42px !important;
  outline: none !important;
  transition: border-color 0.18s, box-shadow 0.18s, background 0.18s !important;
}
.si-input::placeholder { color: #3a3a48 !important; }
.si-input:focus {
  border-color: #1b84ff !important;
  background: rgba(27,132,255,0.04) !important;
  box-shadow: 0 0 0 3px rgba(27,132,255,0.12) !important;
}
.si-input-pwd { padding-right: 44px !important; }
.si-eye-btn { position: absolute; right: 11px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #3a3a48; display: flex; align-items: center; padding: 4px; transition: color 0.15s; z-index: 2; }
.si-eye-btn:hover { color: #72727a; }

/* Last used email badge */
.si-last-email {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: 7px;
  font-size: 11px;
  color: #3a3a48;
  cursor: pointer;
  padding: 4px 10px 4px 8px;
  background: rgba(27,132,255,0.06);
  border: 1px solid rgba(27,132,255,0.14);
  border-radius: 20px;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
  user-select: none;
}
.si-last-email:hover {
  background: rgba(27,132,255,0.12);
  border-color: rgba(27,132,255,0.28);
  color: #7ac0ff;
}
.si-last-email strong { color: #5aabff; font-weight: 600; }

.si-error { font-size: 11px; color: #ff6b6b; }
.si-remember { display: flex; align-items: center; gap: 9px; margin-bottom: 20px; }
.si-chk { width: 17px; height: 17px; border-radius: 5px; background: #181b22; border: 1.5px solid rgba(255,255,255,0.10); appearance: none; -webkit-appearance: none; cursor: pointer; flex-shrink: 0; position: relative; transition: all 0.15s; }
.si-chk:checked { background: #1b84ff; border-color: #1b84ff; }
.si-chk:checked::after { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg width='10' height='8' viewBox='0 0 10 8' fill='none'%3E%3Cpath d='M1 4l2.5 2.5L9 1' stroke='white' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center no-repeat; }
.si-chk-label { font-size: 12.5px; color: #55555e; cursor: pointer; user-select: none; }
.si-submit-btn {
  width: 100%; background: #e8262a; color: #fff; border: none;
  border-radius: 10px; font-family: 'Inter', sans-serif;
  font-size: 14px; font-weight: 700; padding: 13.5px; cursor: pointer;
  margin-bottom: 20px; position: relative; overflow: hidden;
  transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
}
.si-submit-btn::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, transparent 55%); pointer-events: none; }
.si-submit-btn:hover:not(:disabled) { background: #ff3a3d; box-shadow: 0 8px 28px rgba(232,38,42,0.38); transform: translateY(-1px); }
.si-submit-btn:active:not(:disabled) { transform: translateY(0); box-shadow: none; }
.si-submit-btn:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }
.si-btn-inner { display: flex; align-items: center; justify-content: center; gap: 8px; position: relative; z-index: 1; }
@keyframes si-spin { to { transform: rotate(360deg); } }
.si-spin { display: inline-block; width: 15px; height: 15px; border: 2px solid rgba(255,255,255,0.25); border-top-color: #fff; border-radius: 50%; animation: si-spin 0.65s linear infinite; }
.si-divider { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.si-divider::before, .si-divider::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.07); }
.si-divider span { font-size: 10px; font-weight: 700; letter-spacing: 0.1em; color: #3a3a48; }
.si-socials { display: flex; flex-direction: column; gap: 9px; }
.si-social-btn { display: flex; align-items: center; gap: 12px; background: #181b22; border: 1.5px solid rgba(255,255,255,0.07); border-radius: 10px; color: #72727a; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; padding: 11px 16px; cursor: pointer; transition: background 0.15s, border-color 0.15s, color 0.15s; width: 100%; text-align: left; }
.si-social-btn:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.13); color: #e2e2e8; }
</style>