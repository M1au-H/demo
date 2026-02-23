<template>
  <div class="row g-5 g-xl-10">
    <div class="col-xl-4">
      <!-- Profile Card -->
      <div class="card mb-5">
        <div class="card-body pt-9 pb-0">
          <div class="d-flex flex-wrap flex-sm-nowrap mb-3 justify-content-center flex-column align-items-center">
            <div class="symbol symbol-100px symbol-lg-160px symbol-fixed mb-4">
              <img :src="getAssetPath('media/avatars/300-1.jpg')" alt="avatar" />
            </div>
            <div class="text-center">
              <span class="text-gray-800 fw-bold fs-4">{{ authStore.user.name }}</span>
              <span class="text-muted d-block fw-semibold mt-1">{{ authStore.user.job_title || 'User' }}</span>
              <span class="badge badge-light-primary mt-2">{{ authStore.user.role }}</span>
            </div>
          </div>
          <div class="separator"></div>
          <div class="pb-5 pt-4">
            <div class="d-flex align-items-center mb-3">
              <KTIcon icon-name="sms" icon-class="fs-4 me-2 text-muted" />
              <span class="text-gray-600 fs-6">{{ authStore.user.email }}</span>
            </div>
            <div v-if="authStore.user.phone" class="d-flex align-items-center mb-3">
              <KTIcon icon-name="phone" icon-class="fs-4 me-2 text-muted" />
              <span class="text-gray-600 fs-6">{{ authStore.user.phone }}</span>
            </div>
            <div v-if="authStore.user.company" class="d-flex align-items-center">
              <KTIcon icon-name="building" icon-class="fs-4 me-2 text-muted" />
              <span class="text-gray-600 fs-6">{{ authStore.user.company }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-8">
      <!-- Edit Profile Form -->
      <div class="card">
        <div class="card-header border-0 pt-5">
          <h3 class="card-title">
            <span class="card-label fw-bold fs-3">Edit Profil</span>
          </h3>
        </div>
        <div class="card-body">
          <VForm @submit="onSubmitProfile" :validation-schema="profileSchema" :initial-values="initialValues">
            <div class="row mb-6">
              <label class="col-lg-4 col-form-label required fw-semibold fs-6">Nama Lengkap</label>
              <div class="col-lg-8">
                <Field type="text" name="name" class="form-control form-control-lg form-control-solid" placeholder="Nama lengkap" />
                <div class="fv-plugins-message-container">
                  <div class="fv-help-block"><ErrorMessage name="name" /></div>
                </div>
              </div>
            </div>

            <div class="row mb-6">
              <label class="col-lg-4 col-form-label fw-semibold fs-6">No. Telepon</label>
              <div class="col-lg-8">
                <Field type="text" name="phone" class="form-control form-control-lg form-control-solid" placeholder="Nomor telepon" />
              </div>
            </div>

            <div class="row mb-6">
              <label class="col-lg-4 col-form-label fw-semibold fs-6">Jabatan</label>
              <div class="col-lg-8">
                <Field type="text" name="job_title" class="form-control form-control-lg form-control-solid" placeholder="Jabatan kamu" />
              </div>
            </div>

            <div class="row mb-6">
              <label class="col-lg-4 col-form-label fw-semibold fs-6">Perusahaan</label>
              <div class="col-lg-8">
                <Field type="text" name="company" class="form-control form-control-lg form-control-solid" placeholder="Nama perusahaan" />
              </div>
            </div>

            <div class="row mb-6">
              <label class="col-lg-4 col-form-label fw-semibold fs-6">Bio</label>
              <div class="col-lg-8">
                <Field as="textarea" name="bio" class="form-control form-control-lg form-control-solid" rows="4" placeholder="Ceritakan tentang kamu" />
              </div>
            </div>

            <div class="d-flex justify-content-end">
              <button type="submit" ref="submitButton" class="btn btn-primary">
                <span class="indicator-label">Simpan Perubahan</span>
                <span class="indicator-progress">
                  Menyimpan...
                  <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
              </button>
            </div>
          </VForm>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, computed, ref } from "vue";
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import { useAuthStore } from "@/stores/auth";
import { getAssetPath } from "@/core/helpers/assets";
import Swal from "sweetalert2/dist/sweetalert2.js";
import * as Yup from "yup";

export default defineComponent({
  name: "user-profile",
  components: { Field, VForm, ErrorMessage },
  setup() {
    const authStore = useAuthStore();
    const submitButton = ref<HTMLButtonElement | null>(null);

    const profileSchema = Yup.object().shape({
      name: Yup.string().required().label("Nama"),
      phone: Yup.string().nullable().label("Telepon"),
      job_title: Yup.string().nullable().label("Jabatan"),
      company: Yup.string().nullable().label("Perusahaan"),
      bio: Yup.string().nullable().label("Bio"),
    });

    const initialValues = computed(() => ({
      name: authStore.user.name || "",
      phone: authStore.user.phone || "",
      job_title: authStore.user.job_title || "",
      company: authStore.user.company || "",
      bio: authStore.user.bio || "",
    }));

    const onSubmitProfile = async (values: any) => {
      if (submitButton.value) {
        submitButton.value.disabled = true;
        submitButton.value.setAttribute("data-kt-indicator", "on");
      }

      await authStore.updateProfile(values);
      const error = Object.values(authStore.errors);

      if (error.length === 0) {
        Swal.fire({
          text: "Profil berhasil diperbarui!",
          icon: "success",
          buttonsStyling: false,
          confirmButtonText: "Ok!",
          heightAuto: false,
          customClass: { confirmButton: "btn fw-semibold btn-light-primary" },
        });
      } else {
        Swal.fire({
          text: error[0] as string,
          icon: "error",
          buttonsStyling: false,
          confirmButtonText: "Coba lagi!",
          heightAuto: false,
          customClass: { confirmButton: "btn fw-semibold btn-light-danger" },
        });
      }

      submitButton.value?.removeAttribute("data-kt-indicator");
      submitButton.value!.disabled = false;
    };

    return { authStore, getAssetPath, profileSchema, initialValues, onSubmitProfile, submitButton };
  },
});
</script>