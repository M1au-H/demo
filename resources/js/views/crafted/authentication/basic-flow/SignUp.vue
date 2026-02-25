<template>
  <div class="su-wrap">

    <!-- Heading -->
    <h1 class="su-heading">
      Create your<br /><span class="su-heading-red">account</span>
    </h1>

    <p class="su-sub">
      Already have an account?
      <router-link to="/sign-in" class="su-link">Sign in here</router-link>
    </p>

    <!-- Form -->
    <VForm
      id="kt_login_signup_form"
      novalidate
      @submit="onSubmitRegister"
      :validation-schema="registration"
    >

      <!-- First + Last Name -->
      <div class="su-row">
        <div class="su-field">
          <label class="su-label">First Name</label>
          <div class="su-inp-wrap">
            <span class="su-inp-ico">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6"/>
              </svg>
            </span>
            <Field
              class="su-input"
              type="text"
              placeholder="John"
              name="first_name"
              autocomplete="off"
            />
          </div>
          <div class="fv-plugins-message-container mt-1">
            <div class="su-error"><ErrorMessage name="first_name" /></div>
          </div>
        </div>

        <div class="su-field">
          <label class="su-label">Last Name</label>
          <div class="su-inp-wrap">
            <span class="su-inp-ico">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6"/>
              </svg>
            </span>
            <Field
              class="su-input"
              type="text"
              placeholder="Doe"
              name="last_name"
              autocomplete="off"
            />
          </div>
          <div class="fv-plugins-message-container mt-1">
            <div class="su-error"><ErrorMessage name="last_name" /></div>
          </div>
        </div>
      </div>

      <!-- Email -->
      <div class="su-field">
        <label class="su-label">Email address</label>
        <div class="su-inp-wrap">
          <span class="su-inp-ico">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="3" width="14" height="10" rx="2"/><polyline points="1,3 8,9 15,3"/>
            </svg>
          </span>
          <Field
            class="su-input"
            type="email"
            placeholder="john@example.com"
            name="email"
            autocomplete="off"
          />
        </div>
        <div class="fv-plugins-message-container mt-1">
          <div class="su-error"><ErrorMessage name="email" /></div>
        </div>
      </div>

      <!-- Password -->
      <div class="su-field" data-kt-password-meter="true">
        <label class="su-label">Password</label>
        <div class="su-inp-wrap">
          <span class="su-inp-ico">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="7" width="10" height="8" rx="1.5"/>
              <path d="M5 7V5a3 3 0 016 0v2"/>
              <circle cx="8" cy="11" r="1" fill="currentColor" stroke="none"/>
            </svg>
          </span>
          <Field
            class="su-input su-input-pwd"
            type="password"
            placeholder="Min. 8 characters"
            name="password"
            autocomplete="off"
          />
        </div>
        <!-- Password strength meter -->
        <div class="su-meter" data-kt-password-meter-control="highlight">
          <div class="su-meter-bar flex-grow-1 bg-secondary bg-active-success rounded me-2"></div>
          <div class="su-meter-bar flex-grow-1 bg-secondary bg-active-success rounded me-2"></div>
          <div class="su-meter-bar flex-grow-1 bg-secondary bg-active-success rounded me-2"></div>
          <div class="su-meter-bar flex-grow-1 bg-secondary bg-active-success rounded"></div>
        </div>
        <p class="su-hint-small">Use 8 or more characters with a mix of letters, numbers &amp; symbols.</p>
        <div class="fv-plugins-message-container mt-1">
          <div class="su-error"><ErrorMessage name="password" /></div>
        </div>
      </div>

      <!-- Confirm Password -->
      <div class="su-field">
        <label class="su-label">Confirm Password</label>
        <div class="su-inp-wrap">
          <span class="su-inp-ico">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="7" width="10" height="8" rx="1.5"/>
              <path d="M5 7V5a3 3 0 016 0v2"/>
              <circle cx="8" cy="11" r="1" fill="currentColor" stroke="none"/>
            </svg>
          </span>
          <Field
            class="su-input su-input-pwd"
            type="password"
            placeholder="Repeat password"
            name="password_confirmation"
            autocomplete="off"
          />
        </div>
        <div class="fv-plugins-message-container mt-1">
          <div class="su-error"><ErrorMessage name="password_confirmation" /></div>
        </div>
      </div>

      <!-- Terms & Conditions -->
      <div class="su-toc">
        <label class="su-toc-label">
          <Field
            class="su-chk"
            type="checkbox"
            name="toc"
            value="1"
          />
          <span class="su-toc-text">
            I agree to the
            <a href="#" class="su-link">Terms and conditions</a>
          </span>
        </label>
      </div>

      <!-- Submit -->
      <button
        id="kt_sign_up_submit"
        ref="submitButton"
        type="submit"
        class="su-submit-btn"
      >
        <span class="indicator-label su-btn-inner">
          Create Account
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M1 7h12M8 2l5 5-5 5" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        <span class="indicator-progress su-btn-inner">
          <span class="su-spin"></span>
          Please wait...
        </span>
      </button>

    </VForm>

    <!-- Divider -->
    <div class="su-divider"><span>OR</span></div>

    <!-- Social Buttons -->
    <div class="su-social-btns">

      <!-- Google -->
      <button type="button" class="su-social-btn">
        <svg viewBox="0 0 24 24" width="18" height="18" style="flex-shrink:0">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continue with Google
      </button>

      <!-- Facebook -->
      <button type="button" class="su-social-btn">
        <svg viewBox="0 0 24 24" width="18" height="18" style="flex-shrink:0" fill="#1877F2">
          <path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073c0 6.025 4.388 11.02 10.125 11.927v-8.437H7.078v-3.49h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.49h-2.796v8.437C19.612 23.093 24 18.098 24 12.073z"/>
        </svg>
        Continue with Facebook
      </button>

      <!-- Apple -->
      <button type="button" class="su-social-btn">
        <svg viewBox="0 0 24 24" width="18" height="18" style="flex-shrink:0" fill="#fff">
          <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
        </svg>
        Continue with Apple
      </button>

    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent, nextTick, onMounted, ref } from 'vue'
import { ErrorMessage, Field, Form as VForm } from 'vee-validate'
import * as Yup from 'yup'
import { useAuthStore, type User } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { PasswordMeterComponent } from '@/assets/ts/components'
import Swal from 'sweetalert2/dist/sweetalert2.js'

export default defineComponent({
  name: 'sign-up',
  components: { Field, VForm, ErrorMessage },

  setup() {
    const store  = useAuthStore()
    const router = useRouter()

    const submitButton = ref<HTMLButtonElement | null>(null)

    const registration = Yup.object().shape({
      first_name:            Yup.string().required().label('First Name'),
      last_name:             Yup.string().required().label('Last Name'),
      email:                 Yup.string().min(4).required().email().label('Email'),
      password:              Yup.string().required().label('Password'),
      password_confirmation: Yup.string()
        .required()
        .oneOf([Yup.ref('password')], 'Passwords must match')
        .label('Password Confirmation'),
    })

    onMounted(() => {
      nextTick(() => { PasswordMeterComponent.bootstrap() })
    })

    const onSubmitRegister = async (values: any) => {
      values = values as User

      submitButton.value!.disabled = true
      submitButton.value?.setAttribute('data-kt-indicator', 'on')

      try {
        await store.register(values)

        const error = Object.values(store.errors)

        if (error.length === 0) {
          await Swal.fire({
            text: 'You have successfully registered!',
            icon: 'success',
            buttonsStyling: false,
            confirmButtonText: 'Ok, got it!',
            heightAuto: false,
            customClass: { confirmButton: 'btn fw-semibold btn-light-primary' },
          })
          router.push({ name: 'dashboard' })
        } else {
          await Swal.fire({
            text: error[0] as string,
            icon: 'error',
            buttonsStyling: false,
            confirmButtonText: 'Try again!',
            heightAuto: false,
            customClass: { confirmButton: 'btn fw-semibold btn-light-danger' },
          })
        }
      } catch (_) {
        await Swal.fire({
          text: 'Something went wrong. Please try again.',
          icon: 'error',
          buttonsStyling: false,
          confirmButtonText: 'Try again!',
          heightAuto: false,
          customClass: { confirmButton: 'btn fw-semibold btn-light-danger' },
        })
      } finally {
        // Selalu reset tombol apapun yang terjadi
        submitButton.value?.removeAttribute('data-kt-indicator')
        submitButton.value!.disabled = false
      }
    }

    return { registration, onSubmitRegister, submitButton }
  },
})
</script>

<style scoped>
.su-wrap {
  width: 100%;
  animation: su-fadeup 0.4s ease both;
  /* Pastikan konten bisa scroll jika overflow */
  padding-bottom: 24px;
}
@keyframes su-fadeup {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Heading */
.su-heading {
  font-size: 28px;
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.025em;
  color: #f0f0f5;
  margin-bottom: 10px;
}
.su-heading-red { color: #e8262a; }

/* Sub */
.su-sub { font-size: 13px; color: #55555e; margin-bottom: 24px; }
.su-link { color: #1b84ff; font-weight: 600; text-decoration: none; transition: color 0.15s; }
.su-link:hover { color: #5aabff; }

/* Divider */
.su-divider {
  display: flex; align-items: center;
  gap: 12px; margin: 20px 0 16px;
}
.su-divider::before, .su-divider::after {
  content: ''; flex: 1; height: 1px;
  background: rgba(255,255,255,0.07);
}
.su-divider span {
  font-size: 10px; font-weight: 700;
  letter-spacing: 0.1em; color: #3a3a48;
}

/* Two-column row */
.su-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

/* Field */
.su-field { margin-bottom: 14px; }
.su-label {
  display: block;
  font-size: 11px; font-weight: 700;
  letter-spacing: 0.07em; text-transform: uppercase;
  color: #55555e; margin-bottom: 7px;
}

/* Input wrapper */
.su-inp-wrap { position: relative; }
.su-inp-ico {
  position: absolute; left: 13px; top: 50%;
  transform: translateY(-50%);
  color: #3a3a48; pointer-events: none;
  display: flex; align-items: center;
}
.su-input {
  width: 100%;
  background: #181b22 !important;
  border: 1.5px solid rgba(255,255,255,0.08) !important;
  border-radius: 10px !important;
  color: #e2e2e8 !important;
  font-family: 'Inter', sans-serif !important;
  font-size: 13.5px !important;
  padding: 11px 14px 11px 40px !important;
  outline: none !important;
  transition: border-color 0.18s, box-shadow 0.18s, background 0.18s !important;
}
.su-input::placeholder { color: #3a3a48 !important; }
.su-input:focus {
  border-color: #1b84ff !important;
  background: rgba(27,132,255,0.04) !important;
  box-shadow: 0 0 0 3px rgba(27,132,255,0.12) !important;
}
.su-input-pwd { padding-right: 14px !important; }

/* Password strength meter */
.su-meter {
  display: flex;
  align-items: center;
  height: 4px;
  margin-top: 8px;
  margin-bottom: 6px;
  gap: 4px;
}
.su-meter-bar {
  height: 4px;
  border-radius: 2px;
  background: rgba(255,255,255,0.08);
  flex: 1;
  transition: background 0.2s;
}
.su-hint-small {
  font-size: 11px;
  color: #3a3a48;
  margin: 0;
  line-height: 1.5;
}

/* Error */
.su-error { font-size: 11px; color: #ff6b6b; }

/* Terms */
.su-toc { margin-bottom: 20px; }
.su-toc-label {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
}
.su-chk {
  width: 17px; height: 17px;
  border-radius: 5px;
  background: #181b22;
  border: 1.5px solid rgba(255,255,255,0.10);
  appearance: none; -webkit-appearance: none;
  cursor: pointer; flex-shrink: 0;
  margin-top: 1px;
  position: relative; transition: all 0.15s;
}
.su-chk:checked { background: #1b84ff; border-color: #1b84ff; }
.su-chk:checked::after {
  content: ''; position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='10' height='8' viewBox='0 0 10 8' fill='none'%3E%3Cpath d='M1 4l2.5 2.5L9 1' stroke='white' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center no-repeat;
}
.su-toc-text {
  font-size: 12.5px;
  color: #55555e;
  line-height: 1.55;
  user-select: none;
}

/* Submit button */
.su-submit-btn {
  width: 100%;
  background: #e8262a;
  color: #fff; border: none;
  border-radius: 10px;
  font-family: 'Inter', sans-serif;
  font-size: 14px; font-weight: 700;
  padding: 13.5px; cursor: pointer;
  position: relative; overflow: hidden;
  transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
}
.su-submit-btn::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, transparent 55%);
  pointer-events: none;
}
.su-submit-btn:hover:not(:disabled) {
  background: #ff3a3d;
  box-shadow: 0 8px 28px rgba(232,38,42,0.38);
  transform: translateY(-1px);
}
.su-submit-btn:active:not(:disabled) { transform: translateY(0); box-shadow: none; }
.su-submit-btn:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }
.su-btn-inner {
  display: flex; align-items: center;
  justify-content: center; gap: 8px;
  position: relative; z-index: 1;
}

/* Indicator show/hide — default: tampilkan label, sembunyikan progress */
.su-submit-btn .indicator-label { display: flex; }
.su-submit-btn .indicator-progress { display: none; }

/* Saat loading: sembunyikan label, tampilkan progress */
.su-submit-btn[data-kt-indicator="on"] .indicator-label { display: none; }
.su-submit-btn[data-kt-indicator="on"] .indicator-progress { display: flex; }

/* Spinner */
@keyframes su-spin { to { transform: rotate(360deg); } }
.su-spin {
  display: inline-block;
  width: 15px; height: 15px;
  border: 2px solid rgba(255,255,255,0.25);
  border-top-color: #fff;
  border-radius: 50%;
  animation: su-spin 0.65s linear infinite;
}

/* Social buttons */
.su-social-btns {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.su-social-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  background: #181b22;
  border: 1.5px solid rgba(255,255,255,0.09);
  border-radius: 10px;
  color: #aaaabc;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  font-weight: 500;
  padding: 11px 16px;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
}
.su-social-btn:hover {
  background: rgba(255,255,255,0.05);
  border-color: rgba(255,255,255,0.14);
  color: #e2e2e8;
}

/* Footer links */
.su-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 24px;
}
.su-footer-link {
  font-size: 12px;
  color: #3a3a48;
  text-decoration: none;
  transition: color 0.15s;
}
.su-footer-link:hover { color: #aaaabc; }
.su-footer-dot {
  font-size: 12px;
  color: #3a3a48;
}
</style>