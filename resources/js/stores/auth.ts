import { ref } from "vue";
import { defineStore } from "pinia";
import ApiService from "@/core/services/ApiService";
import JwtService from "@/core/services/JwtService";

export interface User {
  name: string;
  email: string;
  password: string;
  api_token: string;
  role: string;
  phone?: string;
  avatar?: string;
  bio?: string;
  job_title?: string;
  company?: string;
}

export const useAuthStore = defineStore("auth", () => {
  const errors = ref({});
  const user = ref<User>({} as User);
  const isAuthenticated = ref(!!JwtService.getToken());

  function setAuth(authUser: User) {
    isAuthenticated.value = true;
    user.value = authUser;
    errors.value = {};
    JwtService.saveToken(user.value.api_token);
  }

  function setError(error: any) {
    errors.value = { ...error };
  }

  function purgeAuth() {
    isAuthenticated.value = false;
    user.value = {} as User;
    errors.value = [];
    JwtService.destroyToken();
  }

  // Login user biasa
  function login(credentials: { email: string; password: string }) {
    return ApiService.post("login", credentials)
      .then(({ data }) => { setAuth(data); })
      .catch(({ response }) => { setError(response.data.errors); });
  }

  // Login admin
  function adminLogin(credentials: { email: string; password: string }) {
    return ApiService.post("admin/login", credentials)
      .then(({ data }) => { setAuth(data); })
      .catch(({ response }) => { setError(response.data.errors); });
  }

  function logout() {
    purgeAuth();
  }

  function register(credentials: User) {
    return ApiService.post("register", credentials)
      .then(({ data }) => { setAuth(data); })
      .catch(({ response }) => { setError(response.data.errors); });
  }

  function updateProfile(profileData: Partial<User>) {
    return ApiService.post("profile/update", profileData)
      .then(({ data }) => {
        user.value = data;
        errors.value = {};
      })
      .catch(({ response }) => { setError(response.data.errors); });
  }

  function verifyAuth() {
    if (JwtService.getToken()) {
      ApiService.setHeader();
      ApiService.post("verify_token", { api_token: JwtService.getToken() })
        .then(({ data }) => { setAuth(data); })
        .catch(({ response }) => {
          setError(response.data.errors);
          purgeAuth();
        });
    } else {
      purgeAuth();
    }
  }

  function isAdmin() {
    return user.value.role === "admin";
  }

  function isUser() {
    return user.value.role === "user";
  }

  return {
    errors,
    user,
    isAuthenticated,
    login,
    adminLogin,
    logout,
    register,
    updateProfile,
    verifyAuth,
    isAdmin,
    isUser,
  };
});