<template>
  <div class="up-root">
    <canvas ref="particleCanvas" class="up-particle-canvas"></canvas>

    <div class="up-layout">

      <!-- ══ LEFT PANEL ══ -->
      <aside class="up-sidebar up-pop-item" style="--pop-delay:0s">

        <!-- Avatar Block -->
        <div class="up-avatar-block">
          <div class="up-av-outer">
            <div class="up-av-ring"></div>
            <div class="up-av-wrap" @mouseenter="triggerBounce" ref="avatarWrapRef">
              <img v-if="avatarPreview || authStore.user.avatar" :src="avatarPreview || avatarUrl" alt="avatar" class="up-av-img" />
              <div v-else class="up-av-initials">
                {{ authStore.user.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
              </div>
            </div>
            <label for="avatar-input" class="up-av-edit" title="Change photo">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </label>
            <input id="avatar-input" type="file" accept="image/jpg,image/jpeg,image/png,image/webp" class="up-hidden" @change="onAvatarChange" />
            <span class="up-av-online"></span>
          </div>

          <div v-if="avatarPreview" class="up-av-actions">
            <button type="button" class="up-av-btn up-av-save" :disabled="avatarUploading" @click="onAvatarUpload">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              {{ avatarUploading ? 'Uploading...' : 'Save Photo' }}
            </button>
            <button type="button" class="up-av-btn up-av-cancel" @click="onAvatarCancel">Cancel</button>
          </div>

          <h2 class="up-name">{{ authStore.user.name || '—' }}</h2>
          <p class="up-jobtitle">{{ authStore.user.job_title || 'User' }}</p>
          <div class="up-badges">
            <span class="up-badge up-badge-amber">
              <svg width="8" height="8" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0L6.12 3.38H9.51L6.82 5.47L7.94 8.85L5 6.76L2.06 8.85L3.18 5.47L0.49 3.38H3.88L5 0Z"/></svg>
              {{ authStore.user.role || 'User' }}
            </span>
            <span class="up-badge up-badge-green">
              <span class="up-pulse"></span>
              Active
            </span>
          </div>
        </div>

        <div class="up-divider"></div>

        <!-- Profile Completion -->
        <div class="up-section">
          <p class="up-section-label">Profile Completion</p>
          <div class="up-completion-row">
            <div class="up-comp-ring">
              <svg viewBox="0 0 60 60" width="60" height="60">
                <circle cx="30" cy="30" r="24" fill="none" stroke="var(--border)" stroke-width="4"/>
                <circle cx="30" cy="30" r="24" fill="none"
                  :stroke="profileCompletion === 100 ? 'var(--green)' : 'var(--amber)'"
                  stroke-width="4" stroke-linecap="round"
                  stroke-dasharray="150.8"
                  :stroke-dashoffset="150.8 - (150.8 * profileCompletion / 100)"
                  class="up-arc" transform="rotate(-90 30 30)"/>
              </svg>
              <span class="up-comp-pct">{{ profileCompletion }}%</span>
            </div>
            <div class="up-comp-info">
              <p class="up-comp-title">{{ profileCompletion === 100 ? 'Complete!' : 'Incomplete' }}</p>
              <p class="up-comp-sub">{{ filledCount }}/{{ totalFields }} fields filled</p>
              <div class="up-prog-bar"><div class="up-prog-fill" :style="{ width: profileCompletion + '%', background: profileCompletion === 100 ? 'var(--green)' : 'var(--amber)' }"></div></div>
            </div>
          </div>
          <div class="up-field-list">
            <div v-for="f in fieldStatus" :key="f.key" class="up-field-row">
              <span :class="['up-field-dot', f.filled ? 'up-dot-ok' : 'up-dot-no']"></span>
              <span class="up-field-name">{{ f.label }}</span>
              <span :class="['up-field-badge', f.filled ? 'up-fbadge-ok' : 'up-fbadge-no']">{{ f.filled ? '✓' : '—' }}</span>
            </div>
          </div>
        </div>

        <div class="up-divider"></div>

        <!-- Contact Info -->
        <div class="up-section">
          <p class="up-section-label">Contact Info</p>
          <div class="up-contact-list">
            <div class="up-contact-row">
              <span class="up-ci-icon" style="color:var(--blue);background:var(--blue-bg)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/></svg>
              </span>
              <div class="up-ci-info">
                <span class="up-ci-lbl">Email</span>
                <span class="up-ci-val">{{ authStore.user.email || '—' }}</span>
              </div>
            </div>
            <div class="up-contact-row">
              <span class="up-ci-icon" style="color:var(--green);background:var(--green-bg)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.08 4.18 2 2 0 0 1 5.09 2h3a2 2 0 0 1 2 1.72c.13 1 .37 1.97.72 2.9a2 2 0 0 1-.45 2.11L9.09 9.91a16 16 0 0 0 6 6l1.18-1.18a2 2 0 0 1 2.11-.45c.93.35 1.9.59 2.9.72A2 2 0 0 1 22 16.92z"/></svg>
              </span>
              <div class="up-ci-info">
                <span class="up-ci-lbl">Phone</span>
                <span class="up-ci-val">{{ authStore.user.phone || '—' }}</span>
              </div>
            </div>
            <div class="up-contact-row">
              <span class="up-ci-icon" style="color:var(--amber);background:var(--amber-bg)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="18" rx="1"/><path d="M9 3v18"/><path d="M9 8h6"/><path d="M9 12h6"/></svg>
              </span>
              <div class="up-ci-info">
                <span class="up-ci-lbl">Company</span>
                <span class="up-ci-val">{{ authStore.user.company || '—' }}</span>
              </div>
            </div>
            <div class="up-contact-row">
              <span class="up-ci-icon" style="color:var(--amber);background:var(--amber-bg)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2"/></svg>
              </span>
              <div class="up-ci-info">
                <span class="up-ci-lbl">Job Title</span>
                <span class="up-ci-val">{{ authStore.user.job_title || '—' }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="up-divider"></div>

        <!-- Bio -->
        <div class="up-section">
          <p class="up-section-label">Bio</p>
          <div v-if="authStore.user.bio" class="up-bio-text">{{ authStore.user.bio }}</div>
          <div v-else class="up-bio-empty">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            No bio added yet.
          </div>
        </div>

      </aside>

      <!-- ══ MAIN FORM ══ -->
      <main class="up-main">

        <!-- Header -->
        <div class="up-header up-pop-item" style="--pop-delay:0.05s">
          <div class="up-header-glow"></div>
          <div class="up-header-content">
            <p class="up-header-sub">Account Settings</p>
            <h1 class="up-header-title">Edit Profile</h1>
            <p class="up-header-desc">Update your personal information and preferences.</p>
          </div>
          <div class="up-header-meta">
            <span class="up-hbadge up-hbadge-green"><span class="up-pulse"></span>Auto-save on submit</span>
            <span class="up-hbadge up-hbadge-amber">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Encrypted
            </span>
          </div>
        </div>

        <VForm
          :key="formKey"
          :validation-schema="profileSchema"
          :initial-values="initialValues"
          v-slot="{ handleSubmit, resetForm, values }"
          class="up-form-wrap"
        >
          <div v-if="hasUnsavedChanges(values)" class="up-unsaved-notice">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            You have unsaved changes
          </div>

          <!-- Section: Personal Info -->
          <div class="up-form-section up-pop-item" style="--pop-delay:0.10s">
            <div class="up-fs-header">
              <span class="up-fs-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
              </span>
              <div>
                <h3 class="up-fs-title">Personal Information</h3>
                <p class="up-fs-sub">Your name and contact details</p>
              </div>
            </div>
            <div class="up-divider"></div>
            <div class="up-fields-grid">
              <div class="up-field-group">
                <label class="up-label">Full Name <span class="up-required">*</span></label>
                <div class="up-input-wrap">
                  <span class="up-input-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                  </span>
                  <Field type="text" name="name" class="up-input" placeholder="Enter your full name" />
                </div>
                <div class="up-err"><ErrorMessage name="name" /></div>
              </div>
              <div class="up-field-group">
                <label class="up-label">Phone Number</label>
                <div class="up-input-wrap">
                  <span class="up-input-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.08 4.18 2 2 0 0 1 5.09 2h3a2 2 0 0 1 2 1.72c.13 1 .37 1.97.72 2.9a2 2 0 0 1-.45 2.11L9.09 9.91a16 16 0 0 0 6 6l1.18-1.18a2 2 0 0 1 2.11-.45c.93.35 1.9.59 2.9.72A2 2 0 0 1 22 16.92z"/></svg>
                  </span>
                  <Field type="text" name="phone" class="up-input" placeholder="e.g. +62812xxxxxxxx" />
                </div>
                <div class="up-err"><ErrorMessage name="phone" /></div>
              </div>
            </div>
          </div>

          <!-- Section: Work Info -->
          <div class="up-form-section up-pop-item" style="--pop-delay:0.15s">
            <div class="up-fs-header">
              <span class="up-fs-icon up-fs-icon-blue">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2"/></svg>
              </span>
              <div>
                <h3 class="up-fs-title">Work Information</h3>
                <p class="up-fs-sub">Your professional details</p>
              </div>
            </div>
            <div class="up-divider"></div>
            <div class="up-fields-grid">
              <div class="up-field-group">
                <label class="up-label">Job Title</label>
                <div class="up-input-wrap">
                  <span class="up-input-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2"/></svg>
                  </span>
                  <Field type="text" name="job_title" class="up-input" placeholder="Your job title" />
                </div>
                <div class="up-err"><ErrorMessage name="job_title" /></div>
              </div>
              <div class="up-field-group">
                <label class="up-label">Company</label>
                <div class="up-input-wrap">
                  <span class="up-input-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="18" rx="1"/><path d="M9 3v18"/><path d="M9 8h6"/><path d="M9 12h6"/></svg>
                  </span>
                  <Field type="text" name="company" class="up-input" placeholder="Company name" />
                </div>
                <div class="up-err"><ErrorMessage name="company" /></div>
              </div>
            </div>
          </div>

          <!-- Section: About -->
          <div class="up-form-section up-pop-item" style="--pop-delay:0.20s">
            <div class="up-fs-header">
              <span class="up-fs-icon up-fs-icon-amber">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              </span>
              <div>
                <h3 class="up-fs-title">About You</h3>
                <p class="up-fs-sub">Tell others a little about yourself</p>
              </div>
            </div>
            <div class="up-divider"></div>
            <div class="up-field-group up-field-full">
              <div class="up-label-row">
                <label class="up-label">Bio</label>
                <span class="up-optional">Optional · max 300 chars</span>
              </div>
              <div class="up-textarea-wrap">
                <Field as="textarea" name="bio" class="up-textarea" rows="5" placeholder="Write a short bio about yourself..." />
              </div>
              <div class="up-err"><ErrorMessage name="bio" /></div>
            </div>
          </div>

          <!-- Form Footer -->
          <div class="up-form-footer up-pop-item" style="--pop-delay:0.25s">
            <div class="up-footer-note">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Changes will be applied immediately after saving.
            </div>
            <div class="up-footer-btns">
              <button type="button" class="up-btn up-btn-ghost" @click="() => resetForm({ values: initialValues })">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg>
                Reset
              </button>
              <button type="button" ref="submitButton" class="up-btn up-btn-primary" :disabled="isSubmitting" @click="handleSubmit(onSubmitProfile)">
                <span class="up-btn-label" v-if="!isSubmitting">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  Save Changes
                </span>
                <span class="up-btn-loading" v-else>
                  Saving...
                  <span class="up-spinner"></span>
                </span>
              </button>
            </div>
          </div>

        </VForm>

      </main>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, computed, ref, onMounted, onUnmounted, nextTick } from "vue";
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import { useAuthStore } from "@/stores/auth";
import Swal from "sweetalert2/dist/sweetalert2.js";
import * as Yup from "yup";

export default defineComponent({
  name: "user-profile",
  components: { Field, VForm, ErrorMessage },
  setup() {
    const authStore = useAuthStore();
    const submitButton = ref<HTMLButtonElement | null>(null);
    const avatarWrapRef = ref<HTMLElement | null>(null);
    const particleCanvas = ref<HTMLCanvasElement | null>(null);
    const isSubmitting = ref(false);
    const formKey = ref(0);

    // ── Avatar ──
    const avatarPreview = ref<string | null>(null);
    const avatarFile = ref<File | null>(null);
    const avatarUploading = ref(false);

    const avatarUrl = computed(() =>
      authStore.user.avatar
        ? `${import.meta.env.VITE_APP_API_URL?.replace('/api', '')}/storage/${authStore.user.avatar}`
        : null
    );

    const onAvatarChange = (event: Event) => {
      const input = event.target as HTMLInputElement;
      if (!input.files?.[0]) return;
      const file = input.files[0];
      if (file.size > 2 * 1024 * 1024) {
        Swal.fire({ text: "Max photo size is 2MB!", icon: "error", buttonsStyling: false, confirmButtonText: "Ok", heightAuto: false, customClass: { confirmButton: "btn btn-light-danger" } });
        return;
      }
      avatarFile.value = file;
      avatarPreview.value = URL.createObjectURL(file);
    };

    const onAvatarUpload = async () => {
      if (!avatarFile.value) return;
      avatarUploading.value = true;
      try {
        await authStore.uploadAvatar(avatarFile.value);
        const errors = Object.values(authStore.errors ?? {});
        if (errors.length === 0) {
          Swal.fire({ text: "Profile photo updated!", icon: "success", buttonsStyling: false, confirmButtonText: "Ok!", heightAuto: false, customClass: { confirmButton: "btn btn-light-primary" } });
          avatarPreview.value = null;
          avatarFile.value = null;
        } else {
          Swal.fire({ text: errors[0] as string, icon: "error", buttonsStyling: false, confirmButtonText: "Try again", heightAuto: false, customClass: { confirmButton: "btn btn-light-danger" } });
        }
      } catch (e) {
        Swal.fire({ text: "Upload failed. Please try again.", icon: "error", buttonsStyling: false, confirmButtonText: "Ok", heightAuto: false });
      } finally {
        avatarUploading.value = false;
      }
    };

    const onAvatarCancel = () => {
      avatarPreview.value = null;
      avatarFile.value = null;
      const input = document.getElementById('avatar-input') as HTMLInputElement;
      if (input) input.value = '';
    };

    function triggerBounce() {
      const el = avatarWrapRef.value;
      if (!el) return;
      el.classList.remove("up-bounce");
      void el.offsetWidth;
      el.classList.add("up-bounce");
    }

    // ── Form config ──
    const fieldConfig = [
      { key: "name",      label: "Full Name" },
      { key: "email",     label: "Email" },
      { key: "phone",     label: "Phone Number" },
      { key: "job_title", label: "Job Title" },
      { key: "company",   label: "Company" },
      { key: "bio",       label: "Bio" },
    ];

    const fieldStatus = computed(() =>
      fieldConfig.map(f => ({ key: f.key, label: f.label, filled: !!(authStore.user as any)[f.key] }))
    );
    const filledCount = computed(() => fieldStatus.value.filter(f => f.filled).length);
    const totalFields = fieldConfig.length;
    const profileCompletion = computed(() => Math.round((filledCount.value / totalFields) * 100));

    const profileSchema = Yup.object().shape({
      name:      Yup.string().required("Full name is required").label("Name"),
      phone:     Yup.string().nullable().label("Phone"),
      job_title: Yup.string().nullable().label("Job Title"),
      company:   Yup.string().nullable().label("Company"),
      bio:       Yup.string().max(300, "Bio max 300 characters").nullable().label("Bio"),
    });

    const initialValues = computed(() => ({
      name:      (authStore.user as any).name      || "",
      phone:     (authStore.user as any).phone     || "",
      job_title: (authStore.user as any).job_title || "",
      company:   (authStore.user as any).company   || "",
      bio:       (authStore.user as any).bio       || "",
    }));

    function hasUnsavedChanges(currentValues: Record<string, any>): boolean {
      const iv = initialValues.value;
      return (
        (currentValues.name      || "") !== iv.name      ||
        (currentValues.phone     || "") !== iv.phone     ||
        (currentValues.job_title || "") !== iv.job_title ||
        (currentValues.company   || "") !== iv.company   ||
        (currentValues.bio       || "") !== iv.bio
      );
    }

    const onSubmitProfile = async (values: any) => {
      isSubmitting.value = true;
      try {
        const payload = {
          name:      values.name      || null,
          phone:     values.phone     || null,
          job_title: values.job_title || null,
          company:   values.company   || null,
          bio:       values.bio       || null,
        };
        await authStore.updateProfile(payload);
        const errors = Object.values(authStore.errors ?? {});
        if (errors.length === 0) {
          formKey.value++;
          Swal.fire({ text: "Profile updated successfully!", icon: "success", buttonsStyling: false, confirmButtonText: "Great!", heightAuto: false, customClass: { confirmButton: "btn fw-semibold btn-light-primary" } });
        } else {
          Swal.fire({ text: errors[0] as string, icon: "error", buttonsStyling: false, confirmButtonText: "Try Again", heightAuto: false, customClass: { confirmButton: "btn fw-semibold btn-light-danger" } });
        }
      } catch (e: any) {
        const msg = e?.response?.data?.message || "Something went wrong. Please try again.";
        Swal.fire({ text: msg, icon: "error", buttonsStyling: false, confirmButtonText: "Ok", heightAuto: false });
      } finally {
        isSubmitting.value = false;
      }
    };

    // ── Pop Animation ──
    let popObserver: IntersectionObserver | null = null;

    async function initPopAnimation() {
      // Reset semua elemen ke state awal
      document.querySelectorAll('.up-pop-item').forEach(el => {
        el.classList.remove('up-pop-visible');
      });

      await nextTick();
      await new Promise(r => setTimeout(r, 30));

      if (popObserver) {
        popObserver.disconnect();
        popObserver = null;
      }

      popObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            (entry.target as HTMLElement).classList.add('up-pop-visible');
            popObserver?.unobserve(entry.target);
          }
        });
      }, { threshold: 0.08, rootMargin: "0px 0px -20px 0px" });

      document.querySelectorAll('.up-pop-item').forEach(el => popObserver!.observe(el));
    }

    // ── Particles ──
    let animFrame: number;
    let resizeHandler: (() => void) | null = null;

    onMounted(async () => {
      // ✅ Init pop animation saat komponen mount
      await initPopAnimation();

      const canvas = particleCanvas.value;
      if (!canvas) return;
      const ctx = canvas.getContext("2d");
      if (!ctx) return;

      function resize() { if (!canvas) return; canvas.width = canvas.offsetWidth; canvas.height = canvas.offsetHeight; }
      resize();
      resizeHandler = resize;
      window.addEventListener("resize", resizeHandler);

      const COLORS = ["rgba(91,156,246,", "rgba(240,167,50,", "rgba(62,207,114,"];
      const particles = Array.from({ length: 45 }, () => ({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        vx: (Math.random() - 0.5) * 0.25,
        vy: (Math.random() - 0.5) * 0.25,
        r: Math.random() * 1.5 + 0.4,
        alpha: Math.random() * 0.4 + 0.1,
        color: COLORS[Math.floor(Math.random() * COLORS.length)],
      }));

      function draw() {
        if (!canvas || !ctx) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (let i = 0; i < particles.length; i++) {
          const p = particles[i];
          p.x += p.vx; p.y += p.vy;
          if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
          if (p.y < 0 || p.y > canvas.height) p.vy *= -1;
          ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2); ctx.fillStyle = p.color + p.alpha + ")"; ctx.fill();
          for (let j = i + 1; j < particles.length; j++) {
            const q = particles[j]; const dx = p.x - q.x, dy = p.y - q.y; const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < 100) { ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y); ctx.strokeStyle = p.color + (0.05 * (1 - dist / 100)) + ")"; ctx.lineWidth = 0.5; ctx.stroke(); }
          }
        }
        animFrame = requestAnimationFrame(draw);
      }
      draw();
    });

    onUnmounted(() => {
      if (animFrame) cancelAnimationFrame(animFrame);
      if (resizeHandler) window.removeEventListener("resize", resizeHandler);
      // ✅ Cleanup observer
      if (popObserver) {
        popObserver.disconnect();
        popObserver = null;
      }
    });

    return {
      authStore, submitButton, avatarWrapRef, particleCanvas,
      profileSchema, initialValues, onSubmitProfile,
      fieldStatus, filledCount, totalFields, profileCompletion,
      avatarPreview, avatarUploading, avatarUrl,
      onAvatarChange, onAvatarUpload, onAvatarCancel,
      triggerBounce, isSubmitting, formKey, hasUnsavedChanges,
    };
  },
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

/* ── Pop Animation ── */
.up-pop-item {
  opacity: 0;
  transform: translateY(22px) scale(0.97);
  transition: opacity 0.45s cubic-bezier(0.16,1,0.3,1), transform 0.45s cubic-bezier(0.16,1,0.3,1);
  transition-delay: var(--pop-delay, 0s);
}
.up-pop-item.up-pop-visible {
  opacity: 1;
  transform: translateY(0) scale(1);
}

.up-root {
  --bg:        #0a0a0a; --s1: #111111; --s2: #161616; --s3: #1d1d1d;
  --border:    #222222; --border2: #2e2e2e; --muted: #3a3a3a; --soft: #606060;
  --text:      #999999; --head: #e0e0e0; --white: #f0f0f0;
  --green:     #3ecf72; --green-bg: rgba(62,207,114,.1); --green-bd: rgba(62,207,114,.2);
  --blue:      #5b9cf6; --blue-bg: rgba(91,156,246,.1);  --blue-bd: rgba(91,156,246,.2);
  --amber:     #f0a732; --amber-bg: rgba(240,167,50,.08); --amber-bd: rgba(240,167,50,.2);
  font-family: 'Inter', sans-serif; background: var(--bg); min-height: 100vh;
  padding: 1.5rem; color: var(--text); position: relative; overflow-x: hidden;
}

.up-particle-canvas { position: fixed; inset: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; }
.up-layout { display: flex; gap: 1.25rem; max-width: 1200px; margin: 0 auto; align-items: flex-start; position: relative; z-index: 1; }

.up-sidebar { width: 280px; flex-shrink: 0; background: var(--s1); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem 1.25rem; display: flex; flex-direction: column; gap: .85rem; position: sticky; top: 1.5rem; }
.up-avatar-block { display: flex; flex-direction: column; align-items: center; gap: .5rem; }
.up-av-outer { position: relative; width: 86px; height: 86px; }
.up-av-ring { position: absolute; inset: -5px; border-radius: 50%; border: 1.5px solid transparent; background: linear-gradient(135deg, var(--amber), var(--blue), var(--green)) border-box; -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0); -webkit-mask-composite: destination-out; mask-composite: exclude; animation: ringRotate 4s linear infinite; }
@keyframes ringRotate { to { filter: hue-rotate(360deg); } }
.up-av-wrap { width: 86px; height: 86px; border-radius: 50%; overflow: hidden; background: var(--s3); border: 1px solid var(--border2); display: flex; align-items: center; justify-content: center; cursor: pointer; }
@keyframes upBounce { 0%{transform:scale(1)} 35%{transform:scale(1.1)} 65%{transform:scale(.97)} 100%{transform:scale(1)} }
.up-bounce { animation: upBounce .35s ease; }
.up-av-img { width: 100%; height: 100%; object-fit: cover; }
.up-av-initials { font-size: 2rem; font-weight: 700; color: var(--head); }
.up-av-edit { position: absolute; bottom: 2px; right: 2px; width: 24px; height: 24px; border-radius: 50%; background: var(--s3); border: 1px solid var(--border2); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text); transition: background .2s, color .2s; }
.up-av-edit:hover { background: var(--amber); color: #fff; border-color: var(--amber); }
.up-av-online { position: absolute; bottom: 4px; left: 4px; width: 11px; height: 11px; border-radius: 50%; background: var(--green); border: 2px solid var(--s1); }
.up-hidden { display: none; }
.up-av-actions { display: flex; gap: .5rem; }
.up-av-btn { display: inline-flex; align-items: center; gap: .35rem; font-size: .68rem; font-weight: 600; padding: .32rem .75rem; border-radius: 7px; border: none; cursor: pointer; transition: opacity .2s, transform .15s; }
.up-av-save { background: var(--amber); color: #fff; }
.up-av-save:hover { opacity: .88; }
.up-av-save:disabled { opacity: .5; cursor: not-allowed; }
.up-av-cancel { background: var(--s3); color: var(--text); border: 1px solid var(--border2); }
.up-av-cancel:hover { color: var(--head); }
.up-name { font-size: .95rem; font-weight: 700; color: var(--head); margin: .1rem 0 0; text-align: center; }
.up-jobtitle { font-size: .7rem; color: var(--soft); margin: 0; text-align: center; }
.up-badges { display: flex; gap: .45rem; flex-wrap: wrap; justify-content: center; }
.up-badge { display: inline-flex; align-items: center; gap: .28rem; font-size: .62rem; font-weight: 600; padding: .2rem .6rem; border-radius: 100px; text-transform: uppercase; letter-spacing: .05em; }
.up-badge-amber { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-bd); }
.up-badge-green { background: var(--green-bg);  color: var(--green);  border: 1px solid var(--green-bd); }
.up-pulse { width: 6px; height: 6px; border-radius: 50%; background: var(--green); animation: pulse 2s ease-in-out infinite; flex-shrink: 0; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.35} }
.up-divider { height: 1px; background: var(--border); flex-shrink: 0; }
.up-section { display: flex; flex-direction: column; gap: .5rem; }
.up-section-label { font-size: .58rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin: 0; }
.up-completion-row { display: flex; align-items: center; gap: .8rem; }
.up-comp-ring { position: relative; flex-shrink: 0; }
.up-arc { transition: stroke-dashoffset .8s ease; }
.up-comp-pct { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: .68rem; font-weight: 700; color: var(--head); }
.up-comp-info { flex: 1; }
.up-comp-title { font-size: .72rem; font-weight: 600; color: var(--head); margin: 0 0 .1rem; }
.up-comp-sub   { font-size: .62rem; color: var(--soft); margin: 0 0 .4rem; }
.up-prog-bar { height: 3px; background: var(--border); border-radius: 2px; overflow: hidden; }
.up-prog-fill { height: 100%; border-radius: 2px; transition: width .6s ease; }
.up-field-list { display: flex; flex-direction: column; gap: .28rem; }
.up-field-row  { display: flex; align-items: center; gap: .5rem; font-size: .67rem; }
.up-field-dot  { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.up-dot-ok { background: var(--green); }
.up-dot-no { background: var(--muted); }
.up-field-name { flex: 1; color: var(--soft); }
.up-field-badge { font-size: .6rem; font-weight: 700; padding: .1rem .4rem; border-radius: 3px; }
.up-fbadge-ok { background: var(--green-bg); color: var(--green); }
.up-fbadge-no { background: var(--s3); color: var(--muted); }
.up-contact-list { display: flex; flex-direction: column; gap: .5rem; }
.up-contact-row { display: flex; align-items: center; gap: .65rem; }
.up-ci-icon { width: 28px; height: 28px; border-radius: 7px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.up-ci-info { display: flex; flex-direction: column; gap: .05rem; min-width: 0; }
.up-ci-lbl { font-size: .58rem; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }
.up-ci-val { font-size: .7rem; font-weight: 500; color: var(--head); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.up-bio-text { font-size: .72rem; color: var(--text); line-height: 1.6; }
.up-bio-empty { display: flex; align-items: center; gap: .5rem; font-size: .68rem; color: var(--muted); font-style: italic; padding: .6rem .75rem; background: var(--s2); border: 1px dashed var(--border2); border-radius: 8px; }

.up-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1.1rem; }
.up-header { background: var(--s1); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; position: relative; overflow: hidden; }
.up-header-glow { position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; border-radius: 50%; background: radial-gradient(circle, rgba(240,167,50,.07) 0%, transparent 70%); pointer-events: none; }
.up-header-content { flex: 1; }
.up-header-sub   { font-size: .65rem; color: var(--soft); letter-spacing: .07em; text-transform: uppercase; margin: 0 0 .2rem; }
.up-header-title { font-size: 1.4rem; font-weight: 700; color: var(--white); margin: 0 0 .2rem; letter-spacing: -.02em; }
.up-header-desc  { font-size: .7rem; color: var(--muted); margin: 0; }
.up-header-meta  { display: flex; gap: .5rem; flex-shrink: 0; flex-wrap: wrap; justify-content: flex-end; align-items: center; }
.up-hbadge { display: inline-flex; align-items: center; gap: .35rem; font-size: .65rem; font-weight: 600; padding: .28rem .7rem; border-radius: 100px; white-space: nowrap; }
.up-hbadge-green { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-bd); }
.up-hbadge-amber { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-bd); }
.up-unsaved-notice { display: flex; align-items: center; gap: .5rem; font-size: .7rem; font-weight: 500; color: var(--amber); background: var(--amber-bg); border: 1px solid var(--amber-bd); border-radius: 10px; padding: .55rem 1rem; }
.up-form-wrap { display: flex; flex-direction: column; gap: 1rem; }
.up-form-section { background: var(--s1); border: 1px solid var(--border); border-radius: 16px; padding: 1.25rem; display: flex; flex-direction: column; gap: .85rem; }
.up-fs-header { display: flex; align-items: center; gap: .75rem; }
.up-fs-icon { width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0; background: var(--s3); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--soft); }
.up-fs-icon-blue  { background: var(--blue-bg);  border-color: var(--blue-bd);  color: var(--blue); }
.up-fs-icon-amber { background: var(--amber-bg); border-color: var(--amber-bd); color: var(--amber); }
.up-fs-title { font-size: .85rem; font-weight: 600; color: var(--head); margin: 0; }
.up-fs-sub   { font-size: .62rem; color: var(--soft); margin: .06rem 0 0; }
.up-fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 680px) { .up-fields-grid { grid-template-columns: 1fr; } }
.up-field-full { grid-column: 1 / -1; }
.up-field-group { display: flex; flex-direction: column; gap: .45rem; }
.up-label { font-size: .7rem; font-weight: 600; color: var(--text); display: flex; align-items: center; gap: .25rem; }
.up-required { color: #ff5555; font-size: .65rem; }
.up-label-row { display: flex; align-items: center; justify-content: space-between; }
.up-optional { font-size: .62rem; color: var(--muted); font-weight: 400; }
.up-input-wrap { position: relative; }
.up-input-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--soft); display: flex; align-items: center; pointer-events: none; }
.up-input { width: 100%; padding: .65rem .85rem .65rem 2.2rem; background: var(--s2); border: 1px solid var(--border2); border-radius: 9px; color: var(--head); font-size: .8rem; font-family: 'Inter', sans-serif; outline: none; transition: border-color .2s, background .2s, box-shadow .2s; box-sizing: border-box; }
.up-input::placeholder { color: var(--muted); }
.up-input:focus { border-color: var(--amber); box-shadow: 0 0 0 3px rgba(240,167,50,.1); background: var(--s3); }
.up-textarea-wrap { position: relative; }
.up-textarea { width: 100%; padding: .75rem 1rem; background: var(--s2); border: 1px solid var(--border2); border-radius: 9px; color: var(--head); font-size: .8rem; font-family: 'Inter', sans-serif; outline: none; resize: vertical; min-height: 110px; transition: border-color .2s, background .2s, box-shadow .2s; box-sizing: border-box; }
.up-textarea::placeholder { color: var(--muted); }
.up-textarea:focus { border-color: var(--amber); box-shadow: 0 0 0 3px rgba(240,167,50,.1); background: var(--s3); }
.up-err { font-size: .65rem; color: #ff5555; min-height: .9rem; }
.up-form-footer { background: var(--s1); border: 1px solid var(--border); border-radius: 14px; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.up-footer-note { display: flex; align-items: center; gap: .5rem; font-size: .67rem; color: var(--soft); }
.up-footer-btns { display: flex; gap: .65rem; align-items: center; }
.up-btn { display: inline-flex; align-items: center; gap: .45rem; font-size: .75rem; font-weight: 600; padding: .5rem 1.1rem; border-radius: 9px; border: none; cursor: pointer; font-family: 'Inter', sans-serif; transition: opacity .2s, transform .15s, box-shadow .2s; }
.up-btn-ghost { background: var(--s3); color: var(--text); border: 1px solid var(--border2); }
.up-btn-ghost:hover { color: var(--head); border-color: var(--soft); }
.up-btn-primary { background: var(--amber); color: #fff; box-shadow: 0 4px 14px rgba(240,167,50,.3); }
.up-btn-primary:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(240,167,50,.4); }
.up-btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }
.up-btn-loading { display: flex; align-items: center; gap: .4rem; }
.up-spinner { width: 12px; height: 12px; border-radius: 50%; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) { .up-layout { flex-direction: column; } .up-sidebar { width: 100%; position: static; } .up-form-footer { flex-direction: column; align-items: flex-start; } }
</style>