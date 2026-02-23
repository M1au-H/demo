<template>
  <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
      <!-- Header -->
      <div id="kt_app_header" class="app-header">
        <div class="app-container container-fluid d-flex align-items-stretch justify-content-between">
          <!-- Mobile sidebar toggle -->
          <div class="d-flex align-items-center d-lg-none ms-n3 me-1">
            <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
              <KTIcon icon-name="abstract-14" icon-class="fs-2 fs-md-1" />
            </div>
          </div>
          <!-- Logo -->
          <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
            <router-link to="/user/dashboard" class="d-lg-none">
              <img alt="Logo" :src="getAssetPath('media/logos/default-small.svg')" class="h-30px" />
            </router-link>
          </div>
          <!-- Navbar kanan -->
          <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1">
            <div class="d-flex align-items-stretch"></div>
            <!-- User menu kanan atas -->
            <div class="app-navbar flex-shrink-0">
              <div class="app-navbar-item ms-1 ms-md-3">
                <div
                  class="cursor-pointer symbol symbol-35px"
                  data-kt-menu-trigger="{default: 'click'}"
                  data-kt-menu-attach="parent"
                  data-kt-menu-placement="bottom-end"
                >
                  <!--begin::Avatar navbar-->
                  <img v-if="authStore.user.avatar" :src="avatarUrl" alt="user" class="rounded-circle object-fit-cover" />
                  <div v-else class="symbol-label fw-bold bg-primary text-white fs-6">
                    {{ authStore.user.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
                  </div>
                  <!--end::Avatar navbar-->
                </div>
                <!-- Dropdown user -->
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                  <div class="menu-item px-3">
                    <div class="menu-content d-flex align-items-center px-3">
                      <div class="symbol symbol-50px me-5">
                        <!--begin::Avatar dropdown-->
                        <img v-if="authStore.user.avatar" :src="avatarUrl" alt="user" class="rounded-circle object-fit-cover" />
                        <div v-else class="symbol-label fw-bold bg-primary text-white fs-4">
                          {{ authStore.user.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
                        </div>
                        <!--end::Avatar dropdown-->
                      </div>
                      <div class="d-flex flex-column">
                        <div class="fw-bold d-flex align-items-center fs-5">
                          {{ authStore.user.name }}
                          <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">User</span>
                        </div>
                        <span class="fw-semibold text-muted text-hover-primary fs-7">{{ authStore.user.email }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="separator my-2"></div>
                  <div class="menu-item px-5">
                    <router-link to="/user/profile" class="menu-link px-5">My Profile</router-link>
                  </div>
                  <div class="separator my-2"></div>
                  <div class="menu-item px-5">
                    <a @click="onLogout" class="menu-link px-5 cursor-pointer">Sign Out</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Wrapper -->
      <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
        <!-- Sidebar -->
        <div
          ref="sidebarRef"
          id="kt_app_sidebar"
          class="app-sidebar flex-column"
          data-kt-drawer="true"
          data-kt-drawer-name="app-sidebar"
          data-kt-drawer-activate="{default: true, lg: false}"
          data-kt-drawer-overlay="true"
          data-kt-drawer-width="225px"
          data-kt-drawer-direction="start"
          data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle"
        >
          <!-- Logo sidebar -->
          <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
            <router-link to="/user/dashboard">
              <img alt="Logo" :src="getAssetPath('media/logos/default-dark.svg')" class="h-25px app-sidebar-logo-default" />
              <img alt="Logo" :src="getAssetPath('media/logos/default-small.svg')" class="h-20px app-sidebar-logo-minimize" />
            </router-link>
          </div>

          <!-- Menu sidebar -->
          <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
            <div
              id="kt_app_sidebar_menu_wrapper"
              class="app-sidebar-wrapper hover-scroll-overlay-y my-5"
              data-kt-scroll="true"
              data-kt-scroll-activate="true"
              data-kt-scroll-height="auto"
              data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
              data-kt-scroll-wrappers="#kt_app_sidebar_menu"
              data-kt-scroll-offset="5px"
            >
              <div id="#kt_app_sidebar_menu" class="menu menu-column menu-rounded menu-sub-indention px-3" data-kt-menu="true">
                <template v-for="(item, i) in UserMenuConfig" :key="i">
                  <div v-if="item.heading" class="menu-item pt-5">
                    <div class="menu-content">
                      <span class="menu-heading fw-bold text-uppercase fs-7">{{ item.heading }}</span>
                    </div>
                  </div>
                  <template v-for="(menuItem, j) in item.pages" :key="j">
                    <div class="menu-item">
                      <router-link class="menu-link" active-class="active" :to="menuItem.route">
                        <span class="menu-icon"><KTIcon :icon-name="menuItem.keenthemesIcon" icon-class="fs-2" /></span>
                        <span class="menu-title">{{ menuItem.heading }}</span>
                      </router-link>
                    </div>
                  </template>
                </template>
                <!-- Logout -->
                <div class="menu-item pt-5">
                  <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">Account</span>
                  </div>
                </div>
                <div class="menu-item">
                  <a class="menu-link cursor-pointer" @click="onLogout">
                    <span class="menu-icon"><KTIcon icon-name="exit-right" icon-class="fs-2" /></span>
                    <span class="menu-title">Sign Out</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Main content -->
        <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
          <div class="d-flex flex-column flex-column-fluid">
            <!-- Toolbar -->
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
              <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                  <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ currentPageTitle }}
                  </h1>
                </div>
              </div>
            </div>
            <!-- Content -->
            <div id="kt_app_content" class="app-content flex-column-fluid">
              <div id="kt_app_content_container" class="app-container container-fluid">
                <router-view />
              </div>
            </div>
          </div>
          <!-- Footer -->
          <div id="kt_app_footer" class="app-footer">
            <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
              <div class="text-gray-900 order-2 order-md-1">
                <span class="text-muted fw-semibold me-1">{{ currentYear }}&copy;</span>
                <span class="text-gray-800 fw-bold">My App</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, computed, nextTick, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { getAssetPath } from "@/core/helpers/assets";
import { useAuthStore } from "@/stores/auth";
import { reinitializeComponents } from "@/core/plugins/keenthemes";
import LayoutService from "@/core/services/LayoutService";
import UserMenuConfig from "@/layouts/default-layout/config/UserMenuConfig";

export default defineComponent({
  name: "user-layout",
  setup() {
    const route = useRoute();
    const router = useRouter();
    const authStore = useAuthStore();
    const sidebarRef = ref<HTMLElement | null>(null);
    const currentYear = new Date().getFullYear();

    const currentPageTitle = computed(() => route.meta.pageTitle || "Dashboard");

    const avatarUrl = computed(() => {
      if (!authStore.user.avatar) return null;
      return `${import.meta.env.VITE_APP_API_URL?.replace('/api', '')}/storage/${authStore.user.avatar}`;
    });

    onMounted(() => {
      LayoutService.init();
      nextTick(() => { reinitializeComponents(); });
    });

    watch(() => route.path, () => {
      nextTick(() => {
        LayoutService.init();
        reinitializeComponents();
      });
    });

    const onLogout = () => {
      authStore.logout();
      router.push({ name: "sign-in" });
    };

    return { getAssetPath, authStore, avatarUrl, UserMenuConfig, currentPageTitle, currentYear, sidebarRef, onLogout };
  },
});
</script>