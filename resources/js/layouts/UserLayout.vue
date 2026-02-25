<template>
  <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">

      <!-- ══ HEADER ══ -->
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

          <!-- Navbar -->
          <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1">

            <!-- ── Search bar ── -->
            <div class="d-flex align-items-center flex-grow-1 px-3 px-lg-5">
              <div class="ul-search-wrap" :class="{ 'ul-search-focused': searchFocused }">
                <span class="ul-search-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input
                  v-model="searchQuery"
                  type="text"
                  class="ul-search-input"
                  placeholder="Search pages..."
                  @focus="searchFocused = true"
                  @blur="onSearchBlur"
                  @input="onSearchInput"
                  @keydown.escape="clearSearch"
                />
                <span v-if="searchQuery" class="ul-search-clear" @mousedown.prevent="clearSearch">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </span>
                <div v-if="searchFocused && searchQuery.length > 0" class="ul-search-dropdown">
                  <template v-if="searchResults.length > 0">
                    <div
                      v-for="r in searchResults"
                      :key="r.route"
                      class="ul-search-item"
                      @mousedown.prevent="goToRoute(r.route)"
                    >
                      <span class="ul-search-item-icon" v-html="r.svgIcon"></span>
                      <div>
                        <p class="ul-search-item-title">{{ r.title }}</p>
                        <p class="ul-search-item-desc">{{ r.desc }}</p>
                      </div>
                    </div>
                  </template>
                  <div v-else class="ul-search-empty">No results for "<b>{{ searchQuery }}</b>"</div>
                </div>
              </div>
            </div>

            <!-- Right icons -->
            <div class="app-navbar flex-shrink-0 gap-2 d-flex align-items-center">

              <!-- ── Notification Bell ── -->
              <div class="app-navbar-item position-relative" ref="notifRef">
                <button
                  class="ul-icon-btn"
                  :class="{ 'ul-icon-btn-active': showNotif }"
                  @click.stop="showNotif = !showNotif"
                >
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                  <span v-if="unreadCount > 0" class="ul-badge">{{ unreadCount }}</span>
                </button>
                <transition name="ul-pop">
                  <div v-if="showNotif" class="ul-notif-panel" @click.stop>
                    <div class="ul-panel-head">
                      <div>
                        <p class="ul-panel-title">Notifications</p>
                        <p class="ul-panel-sub">{{ unreadCount }} unread</p>
                      </div>
                      <button class="ul-mark-btn" @click="markAllRead">Mark all read</button>
                    </div>
                    <div class="ul-notif-list">
                      <div
                        v-for="n in notifications"
                        :key="n.id"
                        class="ul-notif-row"
                        :class="{ 'ul-notif-unread': !n.read }"
                        @click="n.read = true"
                      >
                        <span class="ul-notif-dot-icon" :style="{ background: n.color + '20', color: n.color }" v-html="n.svg"></span>
                        <div class="ul-notif-body">
                          <p class="ul-notif-title">{{ n.title }}</p>
                          <p class="ul-notif-desc">{{ n.desc }}</p>
                          <p class="ul-notif-time">{{ n.time }}</p>
                        </div>
                        <span v-if="!n.read" class="ul-unread-dot"></span>
                      </div>
                    </div>
                    <div class="ul-panel-foot">
                      <router-link to="/user/settings?tab=notifications" class="ul-view-all" @click="showNotif = false">
                        Notification settings
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                      </router-link>
                    </div>
                  </div>
                </transition>
              </div>

              <!-- ── Avatar dropdown ── -->
              <div class="app-navbar-item ms-1 ms-md-2">
                <div
                  class="cursor-pointer symbol symbol-35px"
                  data-kt-menu-trigger="{default: 'click'}"
                  data-kt-menu-attach="parent"
                  data-kt-menu-placement="bottom-end"
                >
                  <img v-if="authStore.user.avatar" :src="avatarUrl" alt="user" class="rounded-circle object-fit-cover" />
                  <div v-else class="symbol-label fw-bold bg-primary text-white fs-6">
                    {{ authStore.user.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
                  </div>
                </div>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                  <div class="menu-item px-3">
                    <div class="menu-content d-flex align-items-center px-3">
                      <div class="symbol symbol-50px me-5">
                        <img v-if="authStore.user.avatar" :src="avatarUrl" alt="user" class="rounded-circle object-fit-cover" />
                        <div v-else class="symbol-label fw-bold bg-primary text-white fs-4">
                          {{ authStore.user.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
                        </div>
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
                  <div class="menu-item px-5">
                    <router-link to="/user/settings" class="menu-link px-5">Settings</router-link>
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

      <!-- ══ WRAPPER ══ -->
      <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">

        <!-- ── Sidebar ── -->
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
          <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
            <router-link to="/user/dashboard">
              <img alt="Logo" :src="getAssetPath('media/logos/default-dark.svg')" class="h-25px app-sidebar-logo-default" />
              <img alt="Logo" :src="getAssetPath('media/logos/default-small.svg')" class="h-20px app-sidebar-logo-minimize" />
            </router-link>
          </div>

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

                <!-- ══ NAVIGATION — hanya satu sumber, tidak double ══ -->

                <!-- Dashboard -->
                <div class="menu-item pt-5">
                  <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">Main</span>
                  </div>
                </div>
                <div class="menu-item">
                  <router-link class="menu-link" active-class="active" to="/user/dashboard">
                    <span class="menu-icon"><KTIcon icon-name="element-11" icon-class="fs-2" /></span>
                    <span class="menu-title">Dashboard</span>
                  </router-link>
                </div>

                <!-- ACCOUNT -->
                <div class="menu-item pt-5">
                  <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">Account</span>
                  </div>
                </div>

                <!-- My Profile -->
                <div class="menu-item">
                  <router-link class="menu-link" active-class="active" to="/user/profile">
                    <span class="menu-icon"><KTIcon icon-name="profile-circle" icon-class="fs-2" /></span>
                    <span class="menu-title">My Profile</span>
                  </router-link>
                </div>

               <!-- Settings -->
                <div class="menu-item">
                  <router-link class="menu-link" active-class="active" to="/user/settings">
                    <span class="menu-icon"><KTIcon icon-name="setting-2" icon-class="fs-2" /></span>
                    <span class="menu-title">Settings</span>
                  </router-link>
                </div>

                <!-- Sign Out -->
                <div class="menu-item pt-5">
                  <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">Session</span>
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

        <!-- ── Main ── -->
        <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
          <div class="d-flex flex-column flex-column-fluid">
            <div id="kt_app_content" class="app-content flex-column-fluid">
              <div id="kt_app_content_container" class="app-container container-fluid">
                <router-view />
              </div>
            </div>
          </div>
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
import { defineComponent, computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { getAssetPath } from "@/core/helpers/assets";
import { useAuthStore } from "@/stores/auth";
import { reinitializeComponents } from "@/core/plugins/keenthemes";
import LayoutService from "@/core/services/LayoutService";

interface Notif { id: number; title: string; desc: string; time: string; read: boolean; svg: string; color: string; }
interface SearchResult { title: string; desc: string; route: string; svgIcon: string; }

const SEARCH_PAGES: SearchResult[] = [
  { title: "Dashboard", desc: "Account overview & activity", route: "/user/dashboard",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>` },
  { title: "My Profile", desc: "Edit personal information", route: "/user/profile",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>` },
  { title: "Change Password", desc: "Update your password", route: "/user/settings?tab=password",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>` },
  { title: "Two-Factor Auth", desc: "Enhance account security with 2FA", route: "/user/settings?tab=2fa",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
  { title: "Recovery Email", desc: "Set a backup email address", route: "/user/settings?tab=recovery",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/></svg>` },
  { title: "Notification Settings", desc: "Manage alerts & preferences", route: "/user/settings?tab=notifications",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>` },
];

export default defineComponent({
  name: "user-layout",
  setup() {
    const route = useRoute();
    const router = useRouter();
    const authStore = useAuthStore();
    const sidebarRef = ref<HTMLElement | null>(null);
    const notifRef = ref<HTMLElement | null>(null);
    const currentYear = new Date().getFullYear();

    const avatarUrl = computed(() => {
      if (!authStore.user.avatar) return null;
      return `${import.meta.env.VITE_APP_API_URL?.replace('/api', '')}/storage/${authStore.user.avatar}`;
    });

    const isSettingsActive = computed(() => route.path.includes('/user/settings'));

    // ── Search ──
    const searchQuery = ref("");
    const searchFocused = ref(false);
    const searchResults = ref<SearchResult[]>([]);

    function onSearchInput() {
      const q = searchQuery.value.toLowerCase().trim();
      searchResults.value = !q ? [] : SEARCH_PAGES.filter(
        p => p.title.toLowerCase().includes(q) || p.desc.toLowerCase().includes(q)
      ).slice(0, 6);
    }
    function onSearchBlur() { setTimeout(() => { searchFocused.value = false; }, 150); }
    function clearSearch() { searchQuery.value = ""; searchResults.value = []; searchFocused.value = false; }
    function goToRoute(r: string) {
      clearSearch();
      const [path, query] = r.split("?");
      query ? router.push({ path, query: Object.fromEntries(new URLSearchParams(query)) }) : router.push(path);
    }

    // ── Notifications ──
    const showNotif = ref(false);
    const notifications = ref<Notif[]>([
      { id: 1, title: "Welcome to the platform!", desc: "Your account has been created successfully.", time: "Just now", read: false,
        svg: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`, color: "#3ecf72" },
      { id: 2, title: "Complete your profile", desc: "Fill in all fields to unlock features.", time: "2 min ago", read: false,
        svg: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>`, color: "#f0a732" },
      { id: 3, title: "Enable Two-Factor Auth", desc: "Protect your account with 2FA.", time: "Today", read: true,
        svg: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`, color: "#5b9cf6" },
    ]);

    const unreadCount = computed(() => notifications.value.filter(n => !n.read).length);
    function markAllRead() { notifications.value.forEach(n => (n.read = true)); }

    function onClickOutside(e: MouseEvent) {
      if (notifRef.value && !notifRef.value.contains(e.target as Node)) showNotif.value = false;
    }

    onMounted(() => {
      LayoutService.init();
      nextTick(() => { reinitializeComponents(); });
      document.addEventListener("click", onClickOutside);
    });
    onUnmounted(() => { document.removeEventListener("click", onClickOutside); });

    watch(() => route.path, () => {
      nextTick(() => { LayoutService.init(); reinitializeComponents(); });
      showNotif.value = false;
    });

    const onLogout = () => { authStore.logout(); router.push({ name: "sign-in" }); };

    return {
      getAssetPath, authStore, avatarUrl, currentYear, sidebarRef, onLogout,
      isSettingsActive,
      notifRef, showNotif, notifications, unreadCount, markAllRead,
      searchQuery, searchFocused, searchResults, onSearchInput, onSearchBlur, clearSearch, goToRoute,
    };
  },
});
</script>

<style scoped>
.ul-search-wrap { position: relative; max-width: 360px; width: 100%; }
.ul-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #555; pointer-events: none; display: flex; }
.ul-search-input { width: 100%; padding: .42rem .42rem .42rem 2.1rem; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); border-radius: 8px; color: #d0d0d0; font-size: .77rem; outline: none; transition: all .2s; box-sizing: border-box; }
.ul-search-input::placeholder { color: #444; }
.ul-search-focused .ul-search-input { background: rgba(255,255,255,.07); border-color: #f0a732; box-shadow: 0 0 0 3px rgba(240,167,50,.1); }
.ul-search-clear { position: absolute; right: 9px; top: 50%; transform: translateY(-50%); color: #555; cursor: pointer; display: flex; }
.ul-search-clear:hover { color: #bbb; }
.ul-search-dropdown { position: absolute; top: calc(100% + 5px); left: 0; right: 0; background: #111; border: 1px solid #222; border-radius: 10px; overflow: hidden; z-index: 9999; box-shadow: 0 8px 28px rgba(0,0,0,.55); }
.ul-search-item { display: flex; align-items: center; gap: .65rem; padding: .6rem .9rem; cursor: pointer; transition: background .15s; border-bottom: 1px solid #181818; }
.ul-search-item:last-child { border-bottom: none; }
.ul-search-item:hover { background: #1a1a1a; }
.ul-search-item-icon { width: 27px; height: 27px; border-radius: 6px; flex-shrink: 0; background: rgba(240,167,50,.12); color: #f0a732; display: flex; align-items: center; justify-content: center; }
.ul-search-item-title { font-size: .73rem; font-weight: 600; color: #ddd; margin: 0; }
.ul-search-item-desc { font-size: .6rem; color: #666; margin: .04rem 0 0; }
.ul-search-empty { padding: .9rem 1rem; font-size: .7rem; color: #555; }

.ul-icon-btn { width: 34px; height: 34px; border-radius: 8px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); display: flex; align-items: center; justify-content: center; color: #777; cursor: pointer; position: relative; transition: all .2s; }
.ul-icon-btn:hover, .ul-icon-btn-active { background: rgba(255,255,255,.09); border-color: rgba(255,255,255,.16); color: #ddd; }

.ul-badge { position: absolute; top: -4px; right: -4px; min-width: 16px; height: 16px; border-radius: 8px; background: #f0a732; color: #000; font-size: .5rem; font-weight: 800; display: flex; align-items: center; justify-content: center; padding: 0 3px; border: 2px solid #111; animation: badgePop .25s ease; }
@keyframes badgePop { 0%{transform:scale(0)} 70%{transform:scale(1.3)} 100%{transform:scale(1)} }

.ul-notif-panel { position: absolute; top: calc(100% + 10px); right: -10px; width: 310px; background: #111; border: 1px solid #1e1e1e; border-radius: 14px; overflow: hidden; z-index: 9999; box-shadow: 0 12px 40px rgba(0,0,0,.6); }
.ul-panel-head { display: flex; align-items: flex-start; justify-content: space-between; padding: .85rem 1rem .65rem; border-bottom: 1px solid #1a1a1a; }
.ul-panel-title { font-size: .8rem; font-weight: 700; color: #ddd; margin: 0; }
.ul-panel-sub { font-size: .6rem; color: #505050; margin: .08rem 0 0; }
.ul-mark-btn { font-size: .6rem; font-weight: 600; color: #f0a732; background: none; border: none; cursor: pointer; padding: .15rem .4rem; border-radius: 4px; transition: background .15s; white-space: nowrap; }
.ul-mark-btn:hover { background: rgba(240,167,50,.1); }
.ul-notif-list { max-height: 250px; overflow-y: auto; }
.ul-notif-row { display: flex; align-items: flex-start; gap: .65rem; padding: .7rem 1rem; cursor: pointer; position: relative; transition: background .15s; border-bottom: 1px solid #161616; }
.ul-notif-row:last-child { border-bottom: none; }
.ul-notif-row:hover { background: #181818; }
.ul-notif-unread { background: rgba(240,167,50,.025); }
.ul-notif-dot-icon { width: 30px; height: 30px; border-radius: 7px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; margin-top: 1px; }
.ul-notif-body { flex: 1; min-width: 0; }
.ul-notif-title { font-size: .71rem; font-weight: 600; color: #ccc; margin: 0 0 .1rem; }
.ul-notif-desc { font-size: .61rem; color: #666; margin: 0 0 .12rem; line-height: 1.4; }
.ul-notif-time { font-size: .57rem; color: #444; margin: 0; }
.ul-unread-dot { width: 6px; height: 6px; border-radius: 50%; background: #f0a732; flex-shrink: 0; margin-top: 5px; }
.ul-panel-foot { padding: .6rem 1rem; border-top: 1px solid #1a1a1a; }
.ul-view-all { display: flex; align-items: center; justify-content: center; gap: .35rem; font-size: .67rem; font-weight: 600; color: #f0a732; text-decoration: none; transition: opacity .15s; }
.ul-view-all:hover { opacity: .75; }

.ul-pop-enter-active, .ul-pop-leave-active { transition: opacity .17s ease, transform .17s ease; }
.ul-pop-enter-from, .ul-pop-leave-to { opacity: 0; transform: translateY(-7px) scale(.97); }
</style>