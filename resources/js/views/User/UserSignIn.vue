<template>
  <div class="w-lg-500px p-10">
    <VForm
      class="form w-100"
      id="kt_user_signin_form"
      @submit="onSubmitLogin"
      :validation-schema="loginSchema"
    >
      <div class="text-center mb-10">
        <h1 class="text-gray-900 mb-3">User Sign In</h1>
        <div class="text-gray-500 fw-semibold fs-4">
          Belum punya akun?
          <router-link to="/sign-up" class="link-primary fw-bold">Daftar di sini</router-link>
        </div>
      </div>

      <div class="fv-row mb-10">
        <label class="form-label fs-6 fw-bold text-gray-900">Email</label>
        <Field
          class="form-control form-control-lg form-control-solid"
          type="text"
          name="email"
          autocomplete="off"
        />
        <div class="fv-plugins-message-container">
          <div class="fv-help-block"><ErrorMessage name="email" /></div>
        </div>
      </div>

      <div class="fv-row mb-10">
        <div class="d-flex flex-stack mb-2">
          <label class="form-label fw-bold text-gray-900 fs-6 mb-0">Password</label>
        </div>
        <Field
          class="form-control form-control-lg form-control-solid"
          type="password"
          name="password"
          autocomplete="off"
        />
        <div class="fv-plugins-message-container">
          <div class="fv-help-block"><ErrorMessage name="password" /></div>
        </div>
      </div>

      <div class="text-center">
        <button
          type="submit"
          ref="submitButton"
          class="btn btn-lg btn-primary w-100 mb-5"
        >
          <span class="indicator-label">Masuk</span>
          <span class="indicator-progress">
            Mohon tunggu...
            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
          </span>
        </button>
      </div>
    </VForm>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref } from "vue";
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import Swal from "sweetalert2/dist/sweetalert2.js";
import * as Yup from "yup";

export default defineComponent({
  name: "user-sign-in",
  components: { Field, VForm, ErrorMessage },
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    const submitButton = ref<HTMLButtonElement | null>(null);

    const loginSchema = Yup.object().shape({
      email: Yup.string().email().required().label("Email"),
      password: Yup.string().min(4).required().label("Password"),
    });

    const onSubmitLogin = async (values: any) => {
      authStore.logout();

      if (submitButton.value) {
        submitButton.value.disabled = true;
        submitButton.value.setAttribute("data-kt-indicator", "on");
      }

      await authStore.login(values);
      const error = Object.values(authStore.errors);

      if (error.length === 0) {
        Swal.fire({
          text: "Login berhasil!",
          icon: "success",
          buttonsStyling: false,
          confirmButtonText: "Ok!",
          heightAuto: false,
          customClass: { confirmButton: "btn fw-semibold btn-light-primary" },
        }).then(() => {
          router.push({ name: "user-dashboard" });
        });
      } else {
        Swal.fire({
          text: error[0] as string,
          icon: "error",
          buttonsStyling: false,
          confirmButtonText: "Coba lagi!",
          heightAuto: false,
          customClass: { confirmButton: "btn fw-semibold btn-light-danger" },
        }).then(() => { authStore.errors = {}; });
      }

      submitButton.value?.removeAttribute("data-kt-indicator");
      submitButton.value!.disabled = false;
    };

    return { onSubmitLogin, loginSchema, submitButton };
  },
});
</script>