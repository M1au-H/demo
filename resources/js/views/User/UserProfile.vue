<template>
  <div class="row g-5 g-xl-10">

    <!--begin::Sidebar-->
    <div class="col-xl-4">

      <!--begin::Profile Card-->
      <div class="card mb-5 mb-xl-8">
        <div class="card-body">

          <!--begin::Avatar Upload Section-->
          <div class="d-flex flex-center flex-column py-5">

            <!--begin::Avatar-->
            <div class="position-relative mb-7">
              <div class="symbol symbol-100px symbol-circle">
                <img
                  v-if="avatarPreview || authStore.user.avatar"
                  :src="avatarPreview || avatarUrl"
                  alt="avatar"
                  class="object-fit-cover"
                />
                <div v-else class="symbol-label fs-1 fw-bold bg-light-primary text-primary">
                  {{ authStore.user.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
                </div>
              </div>
              <label
                for="avatar-input"
                class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow position-absolute bottom-0 end-0 cursor-pointer"
                title="Ganti foto profil"
              >
                <i class="ki-duotone ki-pencil fs-7">
                  <span class="path1"></span><span class="path2"></span>
                </i>
              </label>
              <input
                id="avatar-input"
                type="file"
                accept="image/jpg,image/jpeg,image/png,image/webp"
                class="d-none"
                @change="onAvatarChange"
              />
            </div>
            <!--end::Avatar-->

            <!--begin::Avatar actions-->
            <div v-if="avatarPreview" class="d-flex gap-2 mb-4">
              <button type="button" class="btn btn-sm btn-primary fw-semibold" :disabled="avatarUploading" @click="onAvatarUpload">
                <span v-if="!avatarUploading">
                  <i class="ki-duotone ki-check fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                  Simpan Foto
                </span>
                <span v-else>Mengupload... <span class="spinner-border spinner-border-sm ms-2"></span></span>
              </button>
              <button type="button" class="btn btn-sm btn-light-danger fw-semibold" @click="onAvatarCancel">Batal</button>
            </div>
            <!--end::Avatar actions-->

            <span class="fs-3 text-gray-800 fw-bold mb-1">{{ authStore.user.name || '—' }}</span>
            <div class="fs-5 fw-semibold text-muted mb-3">{{ authStore.user.job_title || 'User' }}</div>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
              <span class="badge badge-light-primary fw-bold fs-7 text-capitalize">
                <i class="ki-duotone ki-shield-tick fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ authStore.user.role || 'user' }}
              </span>
              <span class="badge badge-light-success fw-bold fs-7">
                <span class="bullet bullet-dot bg-success h-6px w-6px me-1"></span>Aktif
              </span>
            </div>
          </div>
          <!--end::Avatar Upload Section-->

          <div class="separator"></div>

          <div class="pb-5 pt-5 px-2">
            <div class="fw-bold fs-7 text-muted text-uppercase mb-4 ps-1">Informasi Kontak</div>
            <div class="d-flex align-items-center mb-4">
              <span class="symbol symbol-30px me-4"><span class="symbol-label bg-light-primary"><i class="ki-duotone ki-sms fs-4 text-primary"><span class="path1"></span><span class="path2"></span></i></span></span>
              <div class="d-flex flex-column"><span class="text-muted fs-8 fw-semibold">Email</span><span class="text-gray-800 fs-7 fw-bold">{{ authStore.user.email || '—' }}</span></div>
            </div>
            <div class="d-flex align-items-center mb-4">
              <span class="symbol symbol-30px me-4"><span class="symbol-label bg-light-success"><i class="ki-duotone ki-phone fs-4 text-success"><span class="path1"></span><span class="path2"></span></i></span></span>
              <div class="d-flex flex-column"><span class="text-muted fs-8 fw-semibold">Telepon</span><span class="text-gray-800 fs-7 fw-bold">{{ authStore.user.phone || '—' }}</span></div>
            </div>
            <div class="d-flex align-items-center mb-4">
              <span class="symbol symbol-30px me-4"><span class="symbol-label bg-light-warning"><i class="ki-duotone ki-building fs-4 text-warning"><span class="path1"></span><span class="path2"></span></i></span></span>
              <div class="d-flex flex-column"><span class="text-muted fs-8 fw-semibold">Perusahaan</span><span class="text-gray-800 fs-7 fw-bold">{{ authStore.user.company || '—' }}</span></div>
            </div>
            <div class="d-flex align-items-center">
              <span class="symbol symbol-30px me-4"><span class="symbol-label bg-light-info"><i class="ki-duotone ki-briefcase fs-4 text-info"><span class="path1"></span><span class="path2"></span></i></span></span>
              <div class="d-flex flex-column"><span class="text-muted fs-8 fw-semibold">Jabatan</span><span class="text-gray-800 fs-7 fw-bold">{{ authStore.user.job_title || '—' }}</span></div>
            </div>
          </div>

          <div class="separator mb-5"></div>

          <div v-if="authStore.user.bio" class="px-2 pb-5">
            <div class="fw-bold fs-7 text-muted text-uppercase mb-3 ps-1">Bio</div>
            <p class="text-gray-700 fs-7 fw-semibold mb-0 lh-base">{{ authStore.user.bio }}</p>
          </div>
          <div v-else class="px-2 pb-5">
            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4">
              <i class="ki-duotone ki-information-5 fs-2x text-warning me-3 flex-shrink-0"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
              <div class="fs-8 text-muted fw-semibold">Bio belum diisi. Tambahkan bio untuk melengkapi profil kamu.</div>
            </div>
          </div>
        </div>
      </div>
      <!--end::Profile Card-->

      <!--begin::Profile Completion-->
      <div class="card mb-5 mb-xl-8">
        <div class="card-header border-0 pt-5">
          <div class="card-title flex-column">
            <h3 class="fw-bold mb-1 fs-6">Kelengkapan Profil</h3>
            <div class="fs-8 text-muted fw-semibold">{{ filledCount }} dari {{ totalFields }} field terisi</div>
          </div>
          <div class="card-toolbar">
            <span class="badge badge-light-primary fw-bold fs-7">{{ profileCompletion }}%</span>
          </div>
        </div>
        <div class="card-body pt-2 pb-6">
          <div class="progress h-8px mb-5 rounded-pill bg-light-primary">
            <div class="progress-bar bg-primary rounded-pill" :style="{ width: profileCompletion + '%' }"></div>
          </div>
          <div class="d-flex flex-column gap-3">
            <div v-for="field in fieldStatus" :key="field.key" class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center">
                <span :class="['bullet bullet-dot me-3 h-7px w-7px', field.filled ? 'bg-success' : 'bg-danger']"></span>
                <span class="text-gray-700 fw-semibold fs-8">{{ field.label }}</span>
              </div>
              <span v-if="field.filled" class="badge badge-light-success fw-bold fs-9">✓ Terisi</span>
              <span v-else class="badge badge-light-danger fw-bold fs-9">Kosong</span>
            </div>
          </div>
        </div>
      </div>
      <!--end::Profile Completion-->

    </div>
    <!--end::Sidebar-->

    <!--begin::Main Form-->
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header border-0 pt-6">
          <div class="card-title flex-column">
            <h3 class="fw-bold mb-1 fs-3">Edit Profil</h3>
            <div class="fs-7 text-muted fw-semibold">Perbarui informasi akun kamu</div>
          </div>
        </div>
        <div class="card-body pt-0">
          <VForm @submit="onSubmitProfile" :validation-schema="profileSchema" :initial-values="initialValues">

            <div class="separator separator-dashed mb-8">
              <span class="separator-content text-muted fw-bold fs-8 text-uppercase px-4 bg-body">Data Pribadi</span>
            </div>

            <div class="row mb-6">
              <label class="col-lg-4 col-form-label required fw-semibold fs-6">Nama Lengkap</label>
              <div class="col-lg-8 fv-row">
                <div class="position-relative">
                  <span class="position-absolute top-50 translate-middle-y ms-4"><i class="ki-duotone ki-profile-circle fs-4 text-muted"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></span>
                  <Field type="text" name="name" class="form-control form-control-lg form-control-solid ps-12" placeholder="Masukkan nama lengkap" />
                </div>
                <div class="fv-plugins-message-container invalid-feedback"><ErrorMessage name="name" /></div>
              </div>
            </div>

            <div class="row mb-6">
              <label class="col-lg-4 col-form-label fw-semibold fs-6">No. Telepon</label>
              <div class="col-lg-8 fv-row">
                <div class="position-relative">
                  <span class="position-absolute top-50 translate-middle-y ms-4"><i class="ki-duotone ki-phone fs-4 text-muted"><span class="path1"></span><span class="path2"></span></i></span>
                  <Field type="text" name="phone" class="form-control form-control-lg form-control-solid ps-12" placeholder="Contoh: +62812xxxxxxxx" />
                </div>
              </div>
            </div>

            <div class="separator separator-dashed mb-8 mt-2">
              <span class="separator-content text-muted fw-bold fs-8 text-uppercase px-4 bg-body">Informasi Pekerjaan</span>
            </div>

            <div class="row mb-6">
              <label class="col-lg-4 col-form-label fw-semibold fs-6">Jabatan</label>
              <div class="col-lg-8 fv-row">
                <div class="position-relative">
                  <span class="position-absolute top-50 translate-middle-y ms-4"><i class="ki-duotone ki-briefcase fs-4 text-muted"><span class="path1"></span><span class="path2"></span></i></span>
                  <Field type="text" name="job_title" class="form-control form-control-lg form-control-solid ps-12" placeholder="Jabatan kamu" />
                </div>
              </div>
            </div>

            <div class="row mb-6">
              <label class="col-lg-4 col-form-label fw-semibold fs-6">Perusahaan</label>
              <div class="col-lg-8 fv-row">
                <div class="position-relative">
                  <span class="position-absolute top-50 translate-middle-y ms-4"><i class="ki-duotone ki-building fs-4 text-muted"><span class="path1"></span><span class="path2"></span></i></span>
                  <Field type="text" name="company" class="form-control form-control-lg form-control-solid ps-12" placeholder="Nama perusahaan" />
                </div>
              </div>
            </div>

            <div class="separator separator-dashed mb-8 mt-2">
              <span class="separator-content text-muted fw-bold fs-8 text-uppercase px-4 bg-body">Tentang Kamu</span>
            </div>

            <div class="row mb-8">
              <label class="col-lg-4 col-form-label fw-semibold fs-6">Bio <span class="ms-1 fs-8 text-muted fw-normal d-block">Opsional</span></label>
              <div class="col-lg-8 fv-row">
                <Field as="textarea" name="bio" class="form-control form-control-lg form-control-solid" rows="5" placeholder="Ceritakan sedikit tentang kamu..." />
                <div class="text-muted fs-8 mt-2">Maksimal 300 karakter</div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-5 border-top">
              <div class="text-muted fw-semibold fs-8">
                <i class="ki-duotone ki-information-5 fs-7 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                Perubahan akan langsung diterapkan setelah disimpan.
              </div>
              <div class="d-flex gap-3">
                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold">Reset</button>
                <button type="submit" ref="submitButton" class="btn btn-primary fw-semibold">
                  <span class="indicator-label"><i class="ki-duotone ki-check fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>Simpan Perubahan</span>
                  <span class="indicator-progress">Menyimpan... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
              </div>
            </div>

          </VForm>
        </div>
      </div>
    </div>
    <!--end::Main Form-->

  </div>
</template>

<script lang="ts">
import { defineComponent, computed, ref } from "vue";
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

    // ===== AVATAR =====
    const avatarPreview = ref<string | null>(null);
    const avatarFile = ref<File | null>(null);
    const avatarUploading = ref(false);

    const avatarUrl = computed(() => {
      if (!authStore.user.avatar) return null;
      return `${import.meta.env.VITE_APP_API_URL?.replace('/api', '')}/storage/${authStore.user.avatar}`;
    });

    const onAvatarChange = (event: Event) => {
      const input = event.target as HTMLInputElement;
      if (!input.files || !input.files[0]) return;
      const file = input.files[0];
      if (file.size > 2 * 1024 * 1024) {
        Swal.fire({ text: "Ukuran foto maksimal 2MB!", icon: "error", buttonsStyling: false, confirmButtonText: "Ok", heightAuto: false, customClass: { confirmButton: "btn btn-light-danger" } });
        return;
      }
      avatarFile.value = file;
      avatarPreview.value = URL.createObjectURL(file);
    };

    const onAvatarUpload = async () => {
      if (!avatarFile.value) return;
      avatarUploading.value = true;
      await authStore.uploadAvatar(avatarFile.value);
      const error = Object.values(authStore.errors);
      if (error.length === 0) {
        Swal.fire({ text: "Foto profil berhasil diperbarui!", icon: "success", buttonsStyling: false, confirmButtonText: "Ok!", heightAuto: false, customClass: { confirmButton: "btn btn-light-primary" } });
        avatarPreview.value = null;
        avatarFile.value = null;
      } else {
        Swal.fire({ text: error[0] as string, icon: "error", buttonsStyling: false, confirmButtonText: "Coba lagi!", heightAuto: false, customClass: { confirmButton: "btn btn-light-danger" } });
      }
      avatarUploading.value = false;
    };

    const onAvatarCancel = () => {
      avatarPreview.value = null;
      avatarFile.value = null;
      const input = document.getElementById('avatar-input') as HTMLInputElement;
      if (input) input.value = '';
    };

    // ===== PROFILE FORM =====
    const fieldConfig = [
      { key: "name",      label: "Nama Lengkap" },
      { key: "email",     label: "Email" },
      { key: "phone",     label: "No. Telepon" },
      { key: "job_title", label: "Jabatan" },
      { key: "company",   label: "Perusahaan" },
      { key: "bio",       label: "Bio" },
    ];

    const fieldStatus = computed(() =>
      fieldConfig.map(f => ({ key: f.key, label: f.label, filled: !!authStore.user[f.key as keyof typeof authStore.user] }))
    );
    const filledCount = computed(() => fieldStatus.value.filter(f => f.filled).length);
    const totalFields = fieldConfig.length;
    const profileCompletion = computed(() => Math.round((filledCount.value / totalFields) * 100));

    const profileSchema = Yup.object().shape({
      name: Yup.string().required("Nama wajib diisi").label("Nama"),
      phone: Yup.string().nullable().label("Telepon"),
      job_title: Yup.string().nullable().label("Jabatan"),
      company: Yup.string().nullable().label("Perusahaan"),
      bio: Yup.string().max(300, "Bio maksimal 300 karakter").nullable().label("Bio"),
    });

    const initialValues = computed(() => ({
      name: authStore.user.name || "",
      phone: authStore.user.phone || "",
      job_title: authStore.user.job_title || "",
      company: authStore.user.company || "",
      bio: authStore.user.bio || "",
    }));

    const onSubmitProfile = async (values: any) => {
      if (submitButton.value) { submitButton.value.disabled = true; submitButton.value.setAttribute("data-kt-indicator", "on"); }
      await authStore.updateProfile(values);
      const error = Object.values(authStore.errors);
      if (error.length === 0) {
        Swal.fire({ text: "Profil berhasil diperbarui!", icon: "success", buttonsStyling: false, confirmButtonText: "Ok, Lanjutkan!", heightAuto: false, customClass: { confirmButton: "btn fw-semibold btn-light-primary" } });
      } else {
        Swal.fire({ text: error[0] as string, icon: "error", buttonsStyling: false, confirmButtonText: "Coba Lagi", heightAuto: false, customClass: { confirmButton: "btn fw-semibold btn-light-danger" } });
      }
      submitButton.value?.removeAttribute("data-kt-indicator");
      submitButton.value!.disabled = false;
    };

    return { authStore, submitButton, profileSchema, initialValues, onSubmitProfile, fieldStatus, filledCount, totalFields, profileCompletion, avatarPreview, avatarUploading, avatarUrl, onAvatarChange, onAvatarUpload, onAvatarCancel };
  },
});
</script>