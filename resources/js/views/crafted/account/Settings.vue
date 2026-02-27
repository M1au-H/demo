<template>
  <!--begin::Basic info-->
  <div class="card mb-5 mb-xl-10">
    <div
      class="card-header border-0 cursor-pointer"
      role="button"
      data-bs-toggle="collapse"
      data-bs-target="#kt_account_profile_details"
      aria-expanded="true"
    >
      <div class="card-title m-0">
        <h3 class="fw-bold m-0">Profile Details</h3>
      </div>
    </div>

    <div id="kt_account_profile_details" class="collapse show">
      <form class="form" @submit.prevent="saveProfile">
        <div class="card-body border-top p-9">

          <!--begin::Avatar-->
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Avatar</label>
            <div class="col-lg-8">
              <div class="d-flex align-items-center gap-4">
                <!--Preview-->
                <div class="symbol symbol-75px">
                  <img
                    v-if="avatarPreview"
                    :src="avatarPreview"
                    alt="avatar"
                    style="object-fit:cover; border-radius:8px;"
                  />
                  <div v-else class="symbol-label fs-2 fw-bold text-primary">
                    {{ form.name?.charAt(0)?.toUpperCase() || '?' }}
                  </div>
                </div>
                <!--Upload button-->
                <div>
                  <label class="btn btn-sm btn-light-primary fw-bold" for="avatarInput">
                    <KTIcon icon-name="pencil" icon-class="fs-5 me-1" />
                    Ganti Foto
                  </label>
                  <input
                    type="file"
                    id="avatarInput"
                    accept=".png,.jpg,.jpeg"
                    class="d-none"
                    @change="onAvatarChange"
                  />
                  <div class="form-text mt-1">PNG, JPG, JPEG. Maks 2MB.</div>
                </div>
              </div>
            </div>
          </div>
          <!--end::Avatar-->

          <!--begin::Name-->
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Nama Lengkap</label>
            <div class="col-lg-8 fv-row">
              <input
                type="text"
                class="form-control form-control-lg form-control-solid"
                placeholder="Nama lengkap"
                v-model="form.name"
                required
              />
            </div>
          </div>
          <!--end::Name-->

          <!--begin::Phone-->
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">No. Telepon</label>
            <div class="col-lg-8 fv-row">
              <input
                type="tel"
                class="form-control form-control-lg form-control-solid"
                placeholder="Nomor telepon"
                v-model="form.phone"
              />
            </div>
          </div>
          <!--end::Phone-->

          <!--begin::Job Title-->
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Job Title</label>
            <div class="col-lg-8 fv-row">
              <input
                type="text"
                class="form-control form-control-lg form-control-solid"
                placeholder="Jabatan / job title"
                v-model="form.job_title"
              />
            </div>
          </div>
          <!--end::Job Title-->

          <!--begin::Company-->
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Perusahaan</label>
            <div class="col-lg-8 fv-row">
              <input
                type="text"
                class="form-control form-control-lg form-control-solid"
                placeholder="Nama perusahaan"
                v-model="form.company"
              />
            </div>
          </div>
          <!--end::Company-->

          <!--begin::Bio-->
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Bio</label>
            <div class="col-lg-8 fv-row">
              <textarea
                class="form-control form-control-lg form-control-solid"
                rows="3"
                placeholder="Tentang kamu..."
                v-model="form.bio"
              ></textarea>
            </div>
          </div>
          <!--end::Bio-->

        </div>

        <div class="card-footer d-flex justify-content-end py-6 px-9">
          <button type="button" class="btn btn-light btn-active-light-primary me-2" @click="resetForm">
            Batal
          </button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <span v-if="!saving">Simpan Perubahan</span>
            <span v-else>
              Menyimpan...
              <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
  <!--end::Basic info-->

  <!--begin::Sign-in Method-->
  <div class="card mb-5 mb-xl-10">
    <div
      class="card-header border-0 cursor-pointer"
      role="button"
      data-bs-toggle="collapse"
      data-bs-target="#kt_account_signin_method"
    >
      <div class="card-title m-0">
        <h3 class="fw-bolder m-0">Sign-in Method</h3>
      </div>
    </div>

    <div id="kt_account_signin_method" class="collapse show">
      <div class="card-body border-top p-9">

        <!--begin::Email Address-->
        <div class="d-flex flex-wrap align-items-center mb-8">
          <div :class="{ 'd-none': emailFormDisplay }">
            <div class="fs-4 fw-bolder mb-1">Email Address</div>
            <div class="fs-6 fw-semibold text-gray-600">{{ user.email }}</div>
          </div>

          <div :class="{ 'd-none': !emailFormDisplay }" class="flex-row-fluid">
            <div class="row mb-6">
              <div class="col-lg-6 mb-4 mb-lg-0">
                <label class="form-label fs-6 fw-bold mb-3">Email Baru</label>
                <input
                  type="email"
                  class="form-control form-control-lg form-control-solid"
                  v-model="emailForm.email"
                  placeholder="Email baru"
                />
              </div>
              <div class="col-lg-6">
                <label class="form-label fs-6 fw-bold mb-3">Konfirmasi Password</label>
                <input
                  type="password"
                  class="form-control form-control-lg form-control-solid"
                  v-model="emailForm.password"
                  placeholder="Password saat ini"
                />
              </div>
            </div>
            <div class="d-flex gap-3">
              <button class="btn btn-primary px-6" @click="updateEmail" :disabled="savingEmail">
                <span v-if="!savingEmail">Update Email</span>
                <span v-else>Menyimpan... <span class="spinner-border spinner-border-sm ms-2"></span></span>
              </button>
              <button type="button" class="btn btn-light px-6" @click="emailFormDisplay = false">Batal</button>
            </div>
          </div>

          <div :class="{ 'd-none': emailFormDisplay }" class="ms-auto">
            <button class="btn btn-light fw-bolder px-6" @click="emailFormDisplay = true">
              Ganti Email
            </button>
          </div>
        </div>
        <!--end::Email Address-->

        <!--begin::Password-->
        <div class="d-flex flex-wrap align-items-center">
          <div :class="{ 'd-none': passwordFormDisplay }">
            <div class="fs-4 fw-bolder mb-1">Password</div>
            <div class="fs-6 fw-semibold text-gray-600">************</div>
          </div>

          <div :class="{ 'd-none': !passwordFormDisplay }" class="flex-row-fluid">
            <div class="fs-6 fw-semibold text-gray-600 mb-4">
              Password minimal 8 karakter.
            </div>
            <div class="row mb-6">
              <div class="col-lg-4">
                <label class="form-label fs-6 fw-bold mb-3">Password Saat Ini</label>
                <input
                  type="password"
                  class="form-control form-control-lg form-control-solid"
                  v-model="passwordForm.current_password"
                />
              </div>
              <div class="col-lg-4">
                <label class="form-label fs-6 fw-bold mb-3">Password Baru</label>
                <input
                  type="password"
                  class="form-control form-control-lg form-control-solid"
                  v-model="passwordForm.new_password"
                />
              </div>
              <div class="col-lg-4">
                <label class="form-label fs-6 fw-bold mb-3">Konfirmasi Password Baru</label>
                <input
                  type="password"
                  class="form-control form-control-lg form-control-solid"
                  v-model="passwordForm.new_password_confirmation"
                />
              </div>
            </div>
            <div class="d-flex gap-3">
              <button class="btn btn-primary px-6" @click="updatePassword" :disabled="savingPassword">
                <span v-if="!savingPassword">Update Password</span>
                <span v-else>Menyimpan... <span class="spinner-border spinner-border-sm ms-2"></span></span>
              </button>
              <button type="button" class="btn btn-light px-6" @click="passwordFormDisplay = false">Batal</button>
            </div>
          </div>

          <div :class="{ 'd-none': passwordFormDisplay }" class="ms-auto">
            <button class="btn btn-light fw-bolder" @click="passwordFormDisplay = true">
              Ganti Password
            </button>
          </div>
        </div>
        <!--end::Password-->

      </div>
    </div>
  </div>
  <!--end::Sign-in Method-->
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2/dist/sweetalert2.js";

export default defineComponent({
  name: "account-settings",
  setup() {
    const authStore = useAuthStore();
    const user = computed(() => authStore.user);

    // --- Profile Form ---
    const saving = ref(false);
    const avatarFile = ref<File | null>(null);
    const avatarPreview = ref<string>("");

    const form = ref({
      name: "",
      phone: "",
      job_title: "",
      company: "",
      bio: "",
    });

    const initForm = () => {
      form.value = {
        name: user.value.name || "",
        phone: user.value.phone || "",
        job_title: user.value.job_title || "",
        company: user.value.company || "",
        bio: user.value.bio || "",
      };
      avatarPreview.value = user.value.avatar ? `/storage/${user.value.avatar}` : "";
    };

    onMounted(() => initForm());

    const resetForm = () => initForm();

    const onAvatarChange = (e: Event) => {
      const file = (e.target as HTMLInputElement).files?.[0];
      if (!file) return;
      avatarFile.value = file;
      avatarPreview.value = URL.createObjectURL(file);
    };

    const saveProfile = async () => {
      saving.value = true;
      try {
        // Upload avatar dulu jika ada
        if (avatarFile.value) {
          const formData = new FormData();
          formData.append("avatar", avatarFile.value);
          ApiService.setHeader();
          await ApiService.post("/profile/avatar", formData);
        }

        // Update profile data
        ApiService.setHeader();
        const res = await ApiService.post("/profile/update", form.value);

        // Update store
        authStore.user = { ...authStore.user, ...res.data.user };

        Swal.fire({
          icon: "success",
          title: "Berhasil!",
          text: "Profil berhasil diperbarui.",
          confirmButtonText: "OK",
          buttonsStyling: false,
          customClass: { confirmButton: "btn btn-primary" },
        });
      } catch (err: any) {
        Swal.fire({
          icon: "error",
          title: "Gagal",
          text: err?.response?.data?.message || "Terjadi kesalahan.",
          buttonsStyling: false,
          customClass: { confirmButton: "btn btn-danger" },
        });
      } finally {
        saving.value = false;
      }
    };

    // --- Email Form ---
    const emailFormDisplay = ref(false);
    const savingEmail = ref(false);
    const emailForm = ref({ email: "", password: "" });

    const updateEmail = async () => {
      if (!emailForm.value.email || !emailForm.value.password) {
        Swal.fire({ icon: "warning", text: "Email dan password wajib diisi.", buttonsStyling: false, customClass: { confirmButton: "btn btn-warning" } });
        return;
      }
      savingEmail.value = true;
      try {
        ApiService.setHeader();
        await ApiService.post("/profile/update", { email: emailForm.value.email });
        authStore.user = { ...authStore.user, email: emailForm.value.email };
        emailFormDisplay.value = false;
        emailForm.value = { email: "", password: "" };
        Swal.fire({ icon: "success", text: "Email berhasil diperbarui.", buttonsStyling: false, customClass: { confirmButton: "btn btn-primary" } });
      } catch (err: any) {
        Swal.fire({ icon: "error", text: err?.response?.data?.message || "Gagal update email.", buttonsStyling: false, customClass: { confirmButton: "btn btn-danger" } });
      } finally {
        savingEmail.value = false;
      }
    };

    // --- Password Form ---
    const passwordFormDisplay = ref(false);
    const savingPassword = ref(false);
    const passwordForm = ref({
      current_password: "",
      new_password: "",
      new_password_confirmation: "",
    });

    const updatePassword = async () => {
      if (!passwordForm.value.current_password || !passwordForm.value.new_password) {
        Swal.fire({ icon: "warning", text: "Semua field password wajib diisi.", buttonsStyling: false, customClass: { confirmButton: "btn btn-warning" } });
        return;
      }
      if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
        Swal.fire({ icon: "warning", text: "Konfirmasi password tidak cocok.", buttonsStyling: false, customClass: { confirmButton: "btn btn-warning" } });
        return;
      }
      savingPassword.value = true;
      try {
        ApiService.setHeader();
        await ApiService.post("/profile/change-password", passwordForm.value);
        passwordFormDisplay.value = false;
        passwordForm.value = { current_password: "", new_password: "", new_password_confirmation: "" };
        Swal.fire({ icon: "success", text: "Password berhasil diperbarui.", buttonsStyling: false, customClass: { confirmButton: "btn btn-primary" } });
      } catch (err: any) {
        Swal.fire({ icon: "error", text: err?.response?.data?.message || "Gagal update password.", buttonsStyling: false, customClass: { confirmButton: "btn btn-danger" } });
      } finally {
        savingPassword.value = false;
      }
    };

    return {
      user,
      form,
      saving,
      avatarPreview,
      onAvatarChange,
      saveProfile,
      resetForm,
      emailFormDisplay,
      savingEmail,
      emailForm,
      updateEmail,
      passwordFormDisplay,
      savingPassword,
      passwordForm,
      updatePassword,
    };
  },
});
</script>