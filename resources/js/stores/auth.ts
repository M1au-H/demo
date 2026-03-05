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
  created_at?: string;
}

export const useAuthStore = defineStore("auth", () => {
  const errors = ref({});
  const user = ref<User>({} as User);
  const isAuthenticated = ref(!!JwtService.getToken());

  function setAuth(authUser: User) {
    isAuthenticated.value = true;
    user.value = { ...user.value, ...authUser };
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

  function login(credentials: { email: string; password: string }) {
    return ApiService.post("login", credentials)
      .then(({ data }) => { setAuth(data); })
      .catch(({ response }) => {
        setError(response?.data?.errors ?? { general: "Login gagal" });
      });
  }

  function adminLogin(credentials: { email: string; password: string }) {
    return ApiService.post("admin/login", credentials)
      .then(({ data }) => { setAuth(data); })
      .catch(({ response }) => {
        setError(response?.data?.errors ?? { general: "Login gagal" });
      });
  }

  async function logout() {
    try {
      ApiService.setHeader();
      await ApiService.post("logout", {});
    } catch (_) {
      // tetap lanjut purge meskipun request gagal
    } finally {
      purgeAuth();
    }
  }

  function register(credentials: User) {
    return ApiService.post("register", credentials)
      .then(({ data }) => { setAuth(data); })
      .catch(({ response }) => {
        setError(response?.data?.errors ?? { general: "Register gagal" });
      });
  }

  function updateProfile(profileData: Partial<User>) {
    ApiService.setHeader();
    const payload: Record<string, any> = {};
    const fields = ["name", "phone", "bio", "job_title", "company"] as const;
    fields.forEach((key) => {
      const val = (profileData as any)[key];
      if (val !== undefined && val !== null) {
        payload[key] = val === "" ? null : val;
      }
    });
    return ApiService.post("profile/update", payload)
      .then(({ data }) => {
        user.value = { ...user.value, ...data };
        errors.value = {};
      })
      .catch((err) => {
        const response = err?.response;
        setError(response?.data?.errors ?? { general: "Update gagal, coba lagi." });
      });
  }

  function uploadAvatar(file: File) {
    ApiService.setHeader();
    const formData = new FormData();
    formData.append("avatar", file);
    return ApiService.post("profile/avatar", formData)
      .then(({ data }) => {
        if (data.user) {
          user.value = { ...user.value, ...data.user };
        } else if (data.avatar) {
          user.value = { ...user.value, avatar: data.avatar };
        }
        errors.value = {};
        return data;
      })
      .catch((err) => {
        const response = err?.response;
        setError(response?.data?.errors ?? { general: "Upload gagal, coba lagi." });
        throw err;
      });
  }

  async function verifyAuth() {
    const token = JwtService.getToken();
    if (!token) {
      purgeAuth();
      return;
    }
    ApiService.setHeader();
    try {
      const { data } = await ApiService.post("verify_token", { api_token: token });
      setAuth(data);
    } catch (err: any) {
      const response = err?.response;
      setError(response?.data?.errors ?? {});
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
    setAuth,
    login,
    adminLogin,
    logout,
    register,
    updateProfile,
    uploadAvatar,
    verifyAuth,
    isAdmin,
    isUser,
  };
});