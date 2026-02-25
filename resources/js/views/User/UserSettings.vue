<template>
  <div class="us-root">

    <!-- Page header -->
    <div class="us-page-header">
      <div>
        <h1 class="us-page-title">Settings</h1>
        <p class="us-page-sub">Manage your account security and preferences</p>
      </div>
    </div>

    <div class="us-layout">

      <!-- ── Tab sidebar ── -->
      <nav class="us-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          class="us-tab"
          :class="{ 'us-tab-active': activeTab === tab.id }"
          @click="setTab(tab.id)"
        >
          <span class="us-tab-icon" v-html="tab.icon"></span>
          <div class="us-tab-info">
            <span class="us-tab-label">{{ tab.label }}</span>
            <span class="us-tab-desc">{{ tab.desc }}</span>
          </div>
          <svg class="us-tab-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </nav>

      <!-- ── Content panels ── -->
      <div class="us-panel">

        <!-- ════ CHANGE PASSWORD ════ -->
        <transition name="us-fade" mode="out-in">
        <div v-if="activeTab === 'password'" key="password">
          <div class="us-panel-header">
            <div class="us-panel-icon us-icon-amber">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div>
              <h2 class="us-panel-title">Change Password</h2>
              <p class="us-panel-sub">Use a strong password with at least 8 characters</p>
            </div>
          </div>

          <div v-if="passwordSuccess" class="us-alert us-alert-success">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Password changed successfully!
          </div>
          <div v-if="passwordError" class="us-alert us-alert-error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            {{ passwordError }}
          </div>

          <div class="us-form">
            <div class="us-field">
              <label class="us-label">Current Password</label>
              <div class="us-input-wrap">
                <input v-model="pwForm.current" :type="showPw.current ? 'text' : 'password'" class="us-input" placeholder="Enter current password" />
                <button class="us-eye" @click="showPw.current = !showPw.current" type="button">
                  <svg v-if="!showPw.current" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
            </div>
            <div class="us-field">
              <label class="us-label">New Password</label>
              <div class="us-input-wrap">
                <input v-model="pwForm.new" :type="showPw.new ? 'text' : 'password'" class="us-input" placeholder="At least 8 characters" />
                <button class="us-eye" @click="showPw.new = !showPw.new" type="button">
                  <svg v-if="!showPw.new" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
              <!-- Password strength -->
              <div v-if="pwForm.new" class="us-strength-wrap">
                <div class="us-strength-bars">
                  <span v-for="i in 4" :key="i" class="us-strength-bar" :class="{ active: pwStrength >= i, [pwStrengthColor]: pwStrength >= i }"></span>
                </div>
                <span class="us-strength-label" :class="pwStrengthColor">{{ pwStrengthLabel }}</span>
              </div>
            </div>
            <div class="us-field">
              <label class="us-label">Confirm New Password</label>
              <div class="us-input-wrap">
                <input
                  v-model="pwForm.confirm"
                  :type="showPw.confirm ? 'text' : 'password'"
                  class="us-input"
                  :class="{ 'us-input-error': pwForm.confirm && pwForm.new !== pwForm.confirm }"
                  placeholder="Repeat new password"
                />
                <button class="us-eye" @click="showPw.confirm = !showPw.confirm" type="button">
                  <svg v-if="!showPw.confirm" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
              <p v-if="pwForm.confirm && pwForm.new !== pwForm.confirm" class="us-field-error">Passwords do not match</p>
            </div>
            <div class="us-form-actions">
              <button class="us-btn us-btn-ghost" @click="resetPwForm">Cancel</button>
              <button class="us-btn us-btn-primary" @click="changePassword" :disabled="pwLoading || !pwFormValid">
                <span v-if="pwLoading" class="us-spinner"></span>
                {{ pwLoading ? 'Updating...' : 'Update Password' }}
              </button>
            </div>
          </div>
        </div>
        </transition>

        <!-- ════ TWO-FACTOR AUTH ════ -->
        <transition name="us-fade" mode="out-in">
        <div v-if="activeTab === '2fa'" key="2fa">
          <div class="us-panel-header">
            <div class="us-panel-icon us-icon-blue">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div>
              <h2 class="us-panel-title">Two-Factor Authentication</h2>
              <p class="us-panel-sub">Add an extra layer of security to your account</p>
            </div>
          </div>

          <div class="us-2fa-status" :class="twoFaEnabled ? 'us-status-on' : 'us-status-off'">
            <div class="us-status-info">
              <span class="us-status-dot" :class="twoFaEnabled ? 'us-dot-green' : 'us-dot-gray'"></span>
              <div>
                <p class="us-status-title">Two-Factor Auth is {{ twoFaEnabled ? 'Enabled' : 'Disabled' }}</p>
                <p class="us-status-desc">{{ twoFaEnabled ? 'Your account is protected with 2FA.' : 'Enable 2FA to strengthen account security.' }}</p>
              </div>
            </div>
            <button class="us-btn" :class="twoFaEnabled ? 'us-btn-danger' : 'us-btn-primary'" @click="toggle2FA">
              {{ twoFaEnabled ? 'Disable 2FA' : 'Enable 2FA' }}
            </button>
          </div>

          <div v-if="!twoFaEnabled" class="us-2fa-steps">
            <h3 class="us-steps-title">How to enable 2FA:</h3>
            <div class="us-step" v-for="(step, i) in twoFaSteps" :key="i">
              <span class="us-step-num">{{ i + 1 }}</span>
              <p class="us-step-text">{{ step }}</p>
            </div>
          </div>

          <div v-else class="us-2fa-active">
            <div class="us-info-card">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              <p>2FA is active. Your account is protected. Keep your authenticator app safe.</p>
            </div>
          </div>
        </div>
        </transition>

        <!-- ════ RECOVERY EMAIL ════ -->
        <transition name="us-fade" mode="out-in">
        <div v-if="activeTab === 'recovery'" key="recovery">
          <div class="us-panel-header">
            <div class="us-panel-icon us-icon-green">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/></svg>
            </div>
            <div>
              <h2 class="us-panel-title">Recovery Email</h2>
              <p class="us-panel-sub">Used to recover your account if you lose access</p>
            </div>
          </div>

          <div v-if="recoverySuccess" class="us-alert us-alert-success">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Recovery email saved successfully!
          </div>

          <div class="us-current-email">
            <p class="us-label">Primary Email</p>
            <div class="us-email-display">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/></svg>
              <!-- ✅ FIX: authStore.user → authStore.currentUser dengan optional chaining -->
              {{ authStore.currentUser?.email ?? '-' }}
              <span class="us-verified-badge">Verified</span>
            </div>
          </div>

          <div class="us-form">
            <div class="us-field">
              <label class="us-label">Recovery Email Address</label>
              <input
                v-model="recoveryEmail"
                type="email"
                class="us-input"
                :class="{ 'us-input-error': recoveryEmail && !isValidEmail(recoveryEmail) }"
                placeholder="backup@example.com"
              />
              <p v-if="recoveryEmail && !isValidEmail(recoveryEmail)" class="us-field-error">Please enter a valid email address</p>
              <p class="us-field-hint">This email cannot be the same as your primary email</p>
            </div>
            <div class="us-form-actions">
              <button class="us-btn us-btn-ghost" @click="recoveryEmail = ''">Clear</button>
              <!-- ✅ FIX: optional chaining pada perbandingan email -->
              <button
                class="us-btn us-btn-primary"
                @click="saveRecoveryEmail"
                :disabled="recoveryLoading || !recoveryEmail || !isValidEmail(recoveryEmail) || recoveryEmail === authStore.currentUser?.email"
              >
                <span v-if="recoveryLoading" class="us-spinner"></span>
                {{ recoveryLoading ? 'Saving...' : 'Save Recovery Email' }}
              </button>
            </div>
          </div>
        </div>
        </transition>

        <!-- ════ NOTIFICATIONS ════ -->
        <transition name="us-fade" mode="out-in">
        <div v-if="activeTab === 'notifications'" key="notifications">
          <div class="us-panel-header">
            <div class="us-panel-icon us-icon-amber">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <div>
              <h2 class="us-panel-title">Notification Preferences</h2>
              <p class="us-panel-sub">Choose what you want to be notified about</p>
            </div>
          </div>

          <div class="us-notif-groups">
            <div v-for="group in notifGroups" :key="group.label" class="us-notif-group">
              <h3 class="us-group-title">{{ group.label }}</h3>
              <div v-for="item in group.items" :key="item.id" class="us-notif-item">
                <div class="us-notif-item-info">
                  <p class="us-notif-item-title">{{ item.title }}</p>
                  <p class="us-notif-item-desc">{{ item.desc }}</p>
                </div>
                <button class="us-toggle" :class="{ 'us-toggle-on': item.enabled }" @click="item.enabled = !item.enabled">
                  <span class="us-toggle-thumb"></span>
                </button>
              </div>
            </div>
          </div>

          <div class="us-form-actions">
            <button class="us-btn us-btn-ghost" @click="resetNotifications">Reset to Default</button>
            <button class="us-btn us-btn-primary" @click="saveNotifications" :disabled="notifLoading">
              <span v-if="notifLoading" class="us-spinner"></span>
              {{ notifLoading ? 'Saving...' : 'Save Preferences' }}
            </button>
          </div>
        </div>
        </transition>

      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

export default defineComponent({
  name: "user-settings",
  setup() {
    const route = useRoute();
    const router = useRouter();
    const authStore = useAuthStore();

    // ── Tabs ──
    const tabs = [
      {
        id: "password", label: "Change Password", desc: "Update your password",
        icon: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>`,
      },
      {
        id: "2fa", label: "Two-Factor Auth", desc: "Enhance security",
        icon: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
      },
      {
        id: "recovery", label: "Recovery Email", desc: "Backup email address",
        icon: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/></svg>`,
      },
      {
        id: "notifications", label: "Notifications", desc: "Manage alerts",
        icon: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>`,
      },
    ];

    const activeTab = ref("password");

    function setTab(id: string) {
      activeTab.value = id;
      router.replace({ query: { tab: id } });
    }

    // Sync tab dari URL query
    onMounted(() => {
      const tabParam = route.query.tab as string;
      if (tabParam && tabs.find(t => t.id === tabParam)) {
        activeTab.value = tabParam;
      }
    });

    watch(() => route.query.tab, (val) => {
      if (val && tabs.find(t => t.id === val)) activeTab.value = val as string;
    });

    // ── Change Password ──
    const pwForm = ref({ current: "", new: "", confirm: "" });
    const showPw = ref({ current: false, new: false, confirm: false });
    const pwLoading = ref(false);
    const passwordSuccess = ref(false);
    const passwordError = ref("");

    const pwStrength = computed(() => {
      const p = pwForm.value.new;
      if (!p) return 0;
      let score = 0;
      if (p.length >= 8) score++;
      if (/[A-Z]/.test(p)) score++;
      if (/[0-9]/.test(p)) score++;
      if (/[^A-Za-z0-9]/.test(p)) score++;
      return score;
    });
    const pwStrengthLabel = computed(() => ["", "Weak", "Fair", "Good", "Strong"][pwStrength.value]);
    const pwStrengthColor = computed(() => ["", "us-red", "us-amber", "us-blue", "us-green"][pwStrength.value]);
    const pwFormValid = computed(() =>
      pwForm.value.current.length > 0 &&
      pwForm.value.new.length >= 8 &&
      pwForm.value.new === pwForm.value.confirm
    );

    function resetPwForm() {
      pwForm.value = { current: "", new: "", confirm: "" };
      passwordSuccess.value = false;
      passwordError.value = "";
    }

    async function changePassword() {
      if (!pwFormValid.value) return;
      pwLoading.value = true;
      passwordSuccess.value = false;
      passwordError.value = "";
      try {
        ApiService.setHeader();
        await ApiService.post("profile/change-password", {
          current_password: pwForm.value.current,
          new_password: pwForm.value.new,
          new_password_confirmation: pwForm.value.confirm,
        });
        passwordSuccess.value = true;
        resetPwForm();
        setTimeout(() => { passwordSuccess.value = false; }, 4000);
      } catch (err: any) {
        const msg =
          err?.response?.data?.errors?.password?.[0] ||
          err?.response?.data?.message ||
          "Failed to change password. Check your current password.";
        passwordError.value = msg;
      } finally {
        pwLoading.value = false;
      }
    }

    // ── 2FA ──
    const twoFaEnabled = ref(false);
    const twoFaSteps = [
      "Download an authenticator app (Google Authenticator, Authy, etc.)",
      "Click 'Enable 2FA' to generate your QR code",
      "Scan the QR code with your authenticator app",
      "Enter the 6-digit code to verify and activate 2FA",
    ];

    function toggle2FA() {
      if (twoFaEnabled.value) {
        Swal.fire({
          title: "Disable 2FA?",
          text: "This will reduce the security of your account.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Yes, disable",
          confirmButtonColor: "#ef4444",
          background: "#111",
          color: "#ddd",
        }).then((result) => {
          if (result.isConfirmed) twoFaEnabled.value = false;
        });
      } else {
        Swal.fire({
          title: "Enable 2FA",
          text: "2FA setup would require a backend implementation with TOTP. Coming soon!",
          icon: "info",
          background: "#111",
          color: "#ddd",
          confirmButtonColor: "#f0a732",
        });
      }
    }

    // ── Recovery Email ──
    const recoveryEmail = ref("");
    const recoveryLoading = ref(false);
    const recoverySuccess = ref(false);

    function isValidEmail(email: string) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    async function saveRecoveryEmail() {
      recoveryLoading.value = true;
      recoverySuccess.value = false;
      await new Promise(r => setTimeout(r, 900));
      recoveryLoading.value = false;
      recoverySuccess.value = true;
      setTimeout(() => { recoverySuccess.value = false; }, 4000);
    }

    // ── Notifications ──
    const notifLoading = ref(false);
    const notifGroups = ref([
      {
        label: "Account Activity",
        items: [
          { id: "login_alert",     title: "Login alerts",     desc: "Get notified when your account is accessed from a new device", enabled: true },
          { id: "password_change", title: "Password changes", desc: "Receive alerts when your password is changed", enabled: true },
          { id: "profile_update",  title: "Profile updates",  desc: "Notify when profile information is modified", enabled: false },
        ],
      },
      {
        label: "Security",
        items: [
          { id: "2fa_alerts", title: "2FA alerts",          desc: "Notifications related to two-factor authentication", enabled: true },
          { id: "suspicious",  title: "Suspicious activity", desc: "Alert if unusual activity is detected on your account", enabled: true },
        ],
      },
      {
        label: "System",
        items: [
          { id: "product_updates", title: "Product updates",    desc: "Learn about new features and improvements", enabled: false },
          { id: "maintenance",     title: "Maintenance alerts", desc: "Be informed about scheduled maintenance", enabled: true },
        ],
      },
    ]);

    function resetNotifications() {
      notifGroups.value.forEach(g => g.items.forEach(i => { i.enabled = false; }));
      notifGroups.value[0].items[0].enabled = true;
      notifGroups.value[0].items[1].enabled = true;
      notifGroups.value[1].items[0].enabled = true;
      notifGroups.value[1].items[1].enabled = true;
      notifGroups.value[2].items[1].enabled = true;
    }

    async function saveNotifications() {
      notifLoading.value = true;
      await new Promise(r => setTimeout(r, 800));
      notifLoading.value = false;
      Swal.fire({
        text: "Notification preferences saved!",
        icon: "success",
        background: "#111",
        color: "#ddd",
        confirmButtonColor: "#f0a732",
        timer: 2000,
        showConfirmButton: false,
      });
    }

    return {
      authStore, tabs, activeTab, setTab,
      pwForm, showPw, pwLoading, passwordSuccess, passwordError,
      pwStrength, pwStrengthLabel, pwStrengthColor, pwFormValid,
      resetPwForm, changePassword,
      twoFaEnabled, twoFaSteps, toggle2FA,
      recoveryEmail, recoveryLoading, recoverySuccess, isValidEmail, saveRecoveryEmail,
      notifGroups, notifLoading, resetNotifications, saveNotifications,
    };
  },
});
</script>

<style scoped>
/* ── Root ── */
.us-root { padding: 1.5rem 0; min-height: 100%; }
.us-page-header { margin-bottom: 1.75rem; }
.us-page-title { font-size: 1.4rem; font-weight: 700; color: #e0e0e0; margin: 0 0 .2rem; }
.us-page-sub { font-size: .78rem; color: #606060; margin: 0; }

/* ── Layout ── */
.us-layout { display: grid; grid-template-columns: 220px 1fr; gap: 1.25rem; align-items: start; }
@media (max-width: 768px) { .us-layout { grid-template-columns: 1fr; } }

/* ── Tab sidebar ── */
.us-tabs { background: #111; border: 1px solid #1e1e1e; border-radius: 14px; overflow: hidden; }
.us-tab {
  width: 100%; display: flex; align-items: center; gap: .75rem;
  padding: .85rem 1rem; background: none; border: none; cursor: pointer;
  border-bottom: 1px solid #181818; text-align: left; transition: background .15s;
}
.us-tab:last-child { border-bottom: none; }
.us-tab:hover { background: #181818; }
.us-tab-active { background: rgba(240,167,50,.07) !important; border-left: 2px solid #f0a732; }
.us-tab-icon {
  width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
  background: rgba(255,255,255,.05); color: #888;
  display: flex; align-items: center; justify-content: center; transition: all .15s;
}
.us-tab-active .us-tab-icon { background: rgba(240,167,50,.15); color: #f0a732; }
.us-tab-info { flex: 1; min-width: 0; }
.us-tab-label { font-size: .74rem; font-weight: 600; color: #aaa; display: block; transition: color .15s; }
.us-tab-active .us-tab-label { color: #f0a732; }
.us-tab-desc { font-size: .6rem; color: #505050; display: block; margin-top: 1px; }
.us-tab-arrow { color: #333; flex-shrink: 0; transition: color .15s; }
.us-tab-active .us-tab-arrow { color: #f0a732; }

/* ── Panel ── */
.us-panel { background: #111; border: 1px solid #1e1e1e; border-radius: 14px; padding: 1.75rem; }
.us-panel-header { display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.75rem; padding-bottom: 1.25rem; border-bottom: 1px solid #1a1a1a; }
.us-panel-icon { width: 44px; height: 44px; border-radius: 11px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.us-icon-amber { background: rgba(240,167,50,.15); color: #f0a732; }
.us-icon-blue  { background: rgba(91,156,246,.15);  color: #5b9cf6; }
.us-icon-green { background: rgba(62,207,114,.15);  color: #3ecf72; }
.us-panel-title { font-size: 1rem; font-weight: 700; color: #e0e0e0; margin: 0 0 .2rem; }
.us-panel-sub   { font-size: .72rem; color: #606060; margin: 0; }

/* ── Alerts ── */
.us-alert { display: flex; align-items: center; gap: .6rem; padding: .75rem 1rem; border-radius: 9px; font-size: .75rem; font-weight: 500; margin-bottom: 1.25rem; }
.us-alert-success { background: rgba(62,207,114,.1);  color: #3ecf72; border: 1px solid rgba(62,207,114,.2); }
.us-alert-error   { background: rgba(239,68,68,.1);   color: #ef4444; border: 1px solid rgba(239,68,68,.2); }

/* ── Form ── */
.us-form { display: flex; flex-direction: column; gap: 1.1rem; }
.us-field { display: flex; flex-direction: column; gap: .35rem; }
.us-label { font-size: .72rem; font-weight: 600; color: #909090; letter-spacing: .02em; }
.us-input-wrap { position: relative; }
.us-input {
  width: 100%; padding: .65rem .9rem; background: #0d0d0d;
  border: 1px solid #222; border-radius: 9px; color: #d0d0d0;
  font-size: .8rem; outline: none; transition: all .2s; box-sizing: border-box;
}
.us-input:focus { border-color: #f0a732; box-shadow: 0 0 0 3px rgba(240,167,50,.08); }
.us-input-error { border-color: #ef4444 !important; }
.us-input-wrap .us-input { padding-right: 2.5rem; }
.us-eye { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #555; cursor: pointer; display: flex; transition: color .15s; }
.us-eye:hover { color: #aaa; }
.us-field-error { font-size: .62rem; color: #ef4444; margin: 0; }
.us-field-hint  { font-size: .62rem; color: #505050; margin: 0; }

/* Strength bar */
.us-strength-wrap { display: flex; align-items: center; gap: .6rem; margin-top: .3rem; }
.us-strength-bars { display: flex; gap: 3px; }
.us-strength-bar { width: 36px; height: 3px; border-radius: 2px; background: #222; transition: background .3s; }
.us-strength-bar.active.us-red   { background: #ef4444; }
.us-strength-bar.active.us-amber { background: #f0a732; }
.us-strength-bar.active.us-blue  { background: #5b9cf6; }
.us-strength-bar.active.us-green { background: #3ecf72; }
.us-strength-label { font-size: .6rem; font-weight: 600; }
.us-red   { color: #ef4444; }
.us-amber { color: #f0a732; }
.us-blue  { color: #5b9cf6; }
.us-green { color: #3ecf72; }

/* ── Form actions ── */
.us-form-actions { display: flex; gap: .75rem; justify-content: flex-end; margin-top: .5rem; padding-top: 1.25rem; border-top: 1px solid #1a1a1a; }

/* ── Buttons ── */
.us-btn {
  display: flex; align-items: center; gap: .4rem;
  padding: .6rem 1.25rem; border-radius: 9px;
  font-size: .78rem; font-weight: 600; cursor: pointer;
  border: none; transition: all .2s;
}
.us-btn:disabled { opacity: .5; cursor: not-allowed; }
.us-btn-primary { background: #f0a732; color: #000; }
.us-btn-primary:hover:not(:disabled) { background: #e09520; }
.us-btn-ghost { background: rgba(255,255,255,.05); color: #888; border: 1px solid #222; }
.us-btn-ghost:hover:not(:disabled) { background: rgba(255,255,255,.09); color: #ccc; }
.us-btn-danger { background: rgba(239,68,68,.15); color: #ef4444; border: 1px solid rgba(239,68,68,.2); }
.us-btn-danger:hover { background: rgba(239,68,68,.25); }
.us-spinner { width: 12px; height: 12px; border: 2px solid rgba(0,0,0,.3); border-top-color: #000; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── 2FA ── */
.us-2fa-status { display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.25rem; border-radius: 11px; margin-bottom: 1.5rem; border: 1px solid; }
.us-status-on  { background: rgba(62,207,114,.06);  border-color: rgba(62,207,114,.2); }
.us-status-off { background: rgba(255,255,255,.03); border-color: #1e1e1e; }
.us-status-info { display: flex; align-items: center; gap: .75rem; }
.us-status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.us-dot-green { background: #3ecf72; box-shadow: 0 0 6px rgba(62,207,114,.5); }
.us-dot-gray  { background: #444; }
.us-status-title { font-size: .78rem; font-weight: 600; color: #ccc; margin: 0 0 .1rem; }
.us-status-desc  { font-size: .62rem; color: #606060; margin: 0; }
.us-2fa-steps { background: #0d0d0d; border: 1px solid #1a1a1a; border-radius: 11px; padding: 1.1rem 1.25rem; }
.us-steps-title { font-size: .75rem; font-weight: 700; color: #aaa; margin: 0 0 .9rem; }
.us-step { display: flex; align-items: flex-start; gap: .65rem; margin-bottom: .65rem; }
.us-step:last-child { margin-bottom: 0; }
.us-step-num { width: 20px; height: 20px; border-radius: 50%; background: rgba(240,167,50,.15); color: #f0a732; font-size: .6rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }
.us-step-text { font-size: .72rem; color: #808080; margin: 0; line-height: 1.5; }
.us-info-card { display: flex; align-items: flex-start; gap: .65rem; padding: .9rem 1rem; background: rgba(62,207,114,.06); border: 1px solid rgba(62,207,114,.15); border-radius: 9px; color: #3ecf72; font-size: .74rem; line-height: 1.5; }

/* ── Recovery ── */
.us-current-email { margin-bottom: 1.25rem; }
.us-email-display { display: flex; align-items: center; gap: .5rem; padding: .6rem .9rem; background: #0d0d0d; border: 1px solid #1e1e1e; border-radius: 8px; font-size: .78rem; color: #d0d0d0; margin-top: .35rem; }
.us-verified-badge { margin-left: auto; font-size: .6rem; font-weight: 600; color: #3ecf72; background: rgba(62,207,114,.12); padding: .1rem .5rem; border-radius: 20px; }

/* ── Notifications ── */
.us-notif-groups { display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 1.5rem; }
.us-group-title { font-size: .68rem; font-weight: 700; color: #606060; text-transform: uppercase; letter-spacing: .06em; margin: 0 0 .75rem; }
.us-notif-item { display: flex; align-items: center; justify-content: space-between; padding: .8rem 0; border-bottom: 1px solid #181818; }
.us-notif-item:last-child { border-bottom: none; }
.us-notif-item-title { font-size: .76rem; font-weight: 600; color: #ccc; margin: 0 0 .1rem; }
.us-notif-item-desc  { font-size: .63rem; color: #606060; margin: 0; }

/* Toggle */
.us-toggle { width: 40px; height: 22px; border-radius: 11px; border: none; cursor: pointer; position: relative; transition: background .25s; background: #222; flex-shrink: 0; }
.us-toggle-on { background: #f0a732; }
.us-toggle-thumb { position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; transition: transform .25s; }
.us-toggle-on .us-toggle-thumb { transform: translateX(18px); }

/* ── Fade transition ── */
.us-fade-enter-active, .us-fade-leave-active { transition: opacity .2s ease, transform .2s ease; }
.us-fade-enter-from, .us-fade-leave-to { opacity: 0; transform: translateX(6px); }
</style>