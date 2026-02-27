<template>
  <div
    class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold py-4 fs-6 w-275px"
    data-kt-menu="true"
  >
    <div class="menu-item px-3">
      <div class="menu-content d-flex align-items-center px-3">
        <div class="symbol symbol-50px me-5">
          <img
            v-if="user.avatar"
            alt="Avatar"
            :src="`/storage/${user.avatar}`"
            style="width:50px;height:50px;border-radius:50%;object-fit:cover;"
          />
          <img v-else alt="Logo" :src="getAssetPath('media/avatars/300-3.jpg')" />
        </div>
        <div class="d-flex flex-column">
          <div class="fw-bold d-flex align-items-center fs-5">
            {{ user.name || 'User' }}
            <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">
              {{ user.role === 'admin' ? 'Admin' : 'User' }}
            </span>
          </div>
          <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">{{ user.email }}</a>
        </div>
      </div>
    </div>

    <div class="separator my-2"></div>

    <div class="menu-item px-5">
      <router-link to="/pages/profile/overview" class="menu-link px-5">My Profile</router-link>
    </div>

    <div class="separator my-2"></div>

    <div
      class="menu-item px-5"
      data-kt-menu-trigger="hover"
      data-kt-menu-placement="left-start"
      data-kt-menu-flip="center, top"
    >
      <a href="#" class="menu-link px-5">
        <span class="menu-title position-relative">
          Language
          <span class="fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0">
            {{ currentLangugeLocale.name }}
            <img class="w-15px h-15px rounded-1 ms-2" :src="currentLangugeLocale.flag" alt="flag" />
          </span>
        </span>
      </a>
      <div class="menu-sub menu-sub-dropdown w-175px py-4">
        <div class="menu-item px-3">
          <a @click="setLang('en')" href="#" class="menu-link d-flex px-5" :class="{ active: currentLanguage === 'en' }">
            <span class="symbol symbol-20px me-4"><img class="rounded-1" :src="getAssetPath('media/flags/united-states.svg')" /></span>
            English
          </a>
        </div>
        <div class="menu-item px-3">
          <a @click="setLang('es')" href="#" class="menu-link d-flex px-5" :class="{ active: currentLanguage === 'es' }">
            <span class="symbol symbol-20px me-4"><img class="rounded-1" :src="getAssetPath('media/flags/spain.svg')" /></span>
            Spanish
          </a>
        </div>
        <div class="menu-item px-3">
          <a @click="setLang('de')" href="#" class="menu-link d-flex px-5" :class="{ active: currentLanguage === 'de' }">
            <span class="symbol symbol-20px me-4"><img class="rounded-1" :src="getAssetPath('media/flags/germany.svg')" /></span>
            German
          </a>
        </div>
        <div class="menu-item px-3">
          <a @click="setLang('ja')" href="#" class="menu-link d-flex px-5" :class="{ active: currentLanguage === 'ja' }">
            <span class="symbol symbol-20px me-4"><img class="rounded-1" :src="getAssetPath('media/flags/japan.svg')" /></span>
            Japanese
          </a>
        </div>
        <div class="menu-item px-3">
          <a @click="setLang('fr')" href="#" class="menu-link d-flex px-5" :class="{ active: currentLanguage === 'fr' }">
            <span class="symbol symbol-20px me-4"><img class="rounded-1" :src="getAssetPath('media/flags/france.svg')" /></span>
            French
          </a>
        </div>
      </div>
    </div>

    <div class="separator my-2"></div>

    <div class="menu-item px-5 my-1">
      <router-link to="/crafted/account/settings" class="menu-link px-5">Account Settings</router-link>
    </div>

    <div class="menu-item px-5">
      <a @click="signOut()" class="menu-link px-5">Sign Out</a>
    </div>
  </div>
</template>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { computed, defineComponent } from "vue";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";

export default defineComponent({
  name: "kt-user-menu",
  components: {},
  setup() {
    const router = useRouter();
    const i18n = useI18n();
    const authStore = useAuthStore();

    i18n.locale.value = localStorage.getItem("lang") ?? "en";

    const user = computed(() => authStore.user);

    const countries = {
      en: { flag: getAssetPath("media/flags/united-states.svg"), name: "English" },
      es: { flag: getAssetPath("media/flags/spain.svg"),         name: "Spanish" },
      de: { flag: getAssetPath("media/flags/germany.svg"),       name: "German" },
      ja: { flag: getAssetPath("media/flags/japan.svg"),         name: "Japanese" },
      fr: { flag: getAssetPath("media/flags/france.svg"),        name: "French" },
    };

    const signOut = () => {
      authStore.logout();
      router.push({ name: "sign-in" });
    };

    const setLang = (lang: string) => {
      localStorage.setItem("lang", lang);
      i18n.locale.value = lang;
    };

    const currentLanguage = computed(() => i18n.locale.value);
    const currentLangugeLocale = computed(() => {
      return countries[i18n.locale.value as keyof typeof countries] ?? countries["en"];
    });

    return {
      user,
      signOut,
      setLang,
      currentLanguage,
      currentLangugeLocale,
      getAssetPath,
    };
  },
});
</script>