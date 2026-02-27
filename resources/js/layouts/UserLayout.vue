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
                  @click.stop="toggleNotif"
                >
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                  <span v-if="unreadCount > 0" class="ul-badge">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
                </button>
                <transition name="ul-pop">
                  <div v-if="showNotif" class="ul-notif-panel" @click.stop>
                    <div class="ul-panel-head">
                      <div>
                        <p class="ul-panel-title">Notifications</p>
                        <p class="ul-panel-sub">{{ unreadCount }} belum dibaca</p>
                      </div>
                      <button v-if="unreadCount > 0" class="ul-mark-btn" @click="markAllRead">Tandai semua dibaca</button>
                    </div>

                    <!-- Loading -->
                    <div v-if="notifLoading" class="ul-notif-empty">
                      <span class="spinner-border spinner-border-sm me-2"></span> Memuat...
                    </div>

                    <!-- Empty -->
                    <div v-else-if="notifications.length === 0" class="ul-notif-empty">
                      <div style="font-size:28px;margin-bottom:6px">🔔</div>
                      <p>Tidak ada notifikasi</p>
                    </div>

                    <!-- List -->
                    <div v-else class="ul-notif-list">
                      <div
                        v-for="n in notifications"
                        :key="n.id"
                        class="ul-notif-row"
                        :class="{ 'ul-notif-unread': !n.is_read }"
                        @click="markRead(n)"
                      >
                        <span class="ul-notif-dot-icon" :style="notifIconStyle(n.type)">
                          {{ notifIcon(n.type) }}
                        </span>
                        <div class="ul-notif-body">
                          <p class="ul-notif-title">{{ n.title }}</p>
                          <p class="ul-notif-desc">{{ n.message }}</p>
                          <p class="ul-notif-time">{{ timeAgo(n.created_at) }}</p>
                        </div>
                        <span v-if="!n.is_read" class="ul-unread-dot"></span>
                      </div>
                    </div>

                    <div class="ul-panel-foot">
                      <span class="ul-view-all" style="cursor:default;color:#505050">
                        HR Notification System
                      </span>
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

                <!-- ══ MAIN ══ -->
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

                <!-- ══ ABSENSI ══ -->
                <div class="menu-item pt-5">
                  <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">Absensi</span>
                  </div>
                </div>
                <div class="menu-item">
                  <router-link class="menu-link" active-class="active" to="/user/attendance/check-in">
                    <span class="menu-icon"><KTIcon icon-name="entrance-right" icon-class="fs-2" /></span>
                    <span class="menu-title">Absen Masuk</span>
                  </router-link>
                </div>
                <div class="menu-item">
                  <router-link class="menu-link" active-class="active" to="/user/attendance/check-out">
                    <span class="menu-icon"><KTIcon icon-name="exit-right" icon-class="fs-2" /></span>
                    <span class="menu-title">Absen Pulang</span>
                  </router-link>
                </div>
                <div class="menu-item">
                  <router-link class="menu-link" active-class="active" to="/user/attendance/history">
                    <span class="menu-icon"><KTIcon icon-name="calendar-8" icon-class="fs-2" /></span>
                    <span class="menu-title">Riwayat Absensi</span>
                  </router-link>
                </div>
                <!-- ✅ Izin & Cuti -->
                <div class="menu-item">
                  <router-link class="menu-link" active-class="active" to="/user/attendance/leave">
                    <span class="menu-icon"><KTIcon icon-name="document" icon-class="fs-2" /></span>
                    <span class="menu-title">Izin & Cuti</span>
                  </router-link>
                </div>

                <!-- ══ PERFORMA ══ -->
                <div class="menu-item pt-5">
                  <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">Performa</span>
                  </div>
                </div>
                <div class="menu-item">
                  <router-link class="menu-link" active-class="active" to="/user/performance">
                    <span class="menu-icon"><KTIcon icon-name="star" icon-class="fs-2" /></span>
                    <span class="menu-title">Performa Saya</span>
                  </router-link>
                </div>

                <!-- ══ ACCOUNT ══ -->
                <div class="menu-item pt-5">
                  <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">Account</span>
                  </div>
                </div>
                <div class="menu-item">
                  <router-link class="menu-link" active-class="active" to="/user/profile">
                    <span class="menu-icon"><KTIcon icon-name="profile-circle" icon-class="fs-2" /></span>
                    <span class="menu-title">My Profile</span>
                  </router-link>
                </div>
                <div class="menu-item">
                  <router-link class="menu-link" active-class="active" to="/user/settings">
                    <span class="menu-icon"><KTIcon icon-name="setting-2" icon-class="fs-2" /></span>
                    <span class="menu-title">Settings</span>
                  </router-link>
                </div>

                <!-- ══ SESSION ══ -->
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
import ApiService from "@/core/services/ApiService";

interface SearchResult { title: string; desc: string; route: string; svgIcon: string; }

const SEARCH_PAGES: SearchResult[] = [
  { title: "Dashboard", desc: "Account overview & activity", route: "/user/dashboard",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>` },
  { title: "Absen Masuk", desc: "Absensi masuk harian", route: "/user/attendance/check-in",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>` },
  { title: "Absen Pulang", desc: "Absensi pulang harian", route: "/user/attendance/check-out",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>` },
  { title: "Riwayat Absensi", desc: "Histori kehadiran kamu", route: "/user/attendance/history",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>` },
  { title: "Izin & Cuti", desc: "Ajukan izin atau cuti", route: "/user/attendance/leave",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
  { title: "Performa Saya", desc: "Penilaian, sanksi & tugas dari admin", route: "/user/performance",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>` },
  { title: "My Profile", desc: "Edit personal information", route: "/user/profile",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>` },
  { title: "Settings", desc: "Update your preferences", route: "/user/settings",
    svgIcon: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>` },
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

    // ── Notifications (dari API) ──
    const showNotif = ref(false);
    const notifLoading = ref(false);
    const notifications = ref<any[]>([]);
    const unreadCount = ref(0);
    let pollInterval: any = null;

    const notifIcon = (type: string) => ({ success: '✅', warning: '⚠️', info: 'ℹ️' }[type] ?? '🔔')
    const notifIconStyle = (type: string) => {
      const map: Record<string, string> = { success: '#3ecf72', warning: '#f0a732', info: '#5b9cf6' }
      const color = map[type] ?? '#aaa'
      return { background: color + '20', color, width: '30px', height: '30px', borderRadius: '7px', flexShrink: '0', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '14px' }
    }

    const timeAgo = (dateStr: string) => {
      const diff = Date.now() - new Date(dateStr).getTime()
      const mins = Math.floor(diff / 60000)
      if (mins < 1)  return "Baru saja"
      if (mins < 60) return `${mins} mnt lalu`
      const hrs = Math.floor(mins / 60)
      if (hrs < 24)  return `${hrs} jam lalu`
      return `${Math.floor(hrs / 24)} hari lalu`
    }

    const fetchNotifications = async () => {
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('notifications')
        notifications.value = data.data
        unreadCount.value   = data.unread
      } catch (_) {}
    }

    const toggleNotif = async () => {
      showNotif.value = !showNotif.value
      if (showNotif.value) {
        notifLoading.value = true
        await fetchNotifications()
        notifLoading.value = false
      }
    }

    const markRead = async (n: any) => {
      if (n.is_read) return
      try {
        ApiService.setHeader()
        await ApiService.post(`notifications/${n.id}/read`, {})
        n.is_read = true
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      } catch (_) {}
    }

    const markAllRead = async () => {
      try {
        ApiService.setHeader()
        await ApiService.post('notifications/read-all', {})
        notifications.value.forEach(n => n.is_read = true)
        unreadCount.value = 0
      } catch (_) {}
    }

    function onClickOutside(e: MouseEvent) {
      if (notifRef.value && !notifRef.value.contains(e.target as Node)) showNotif.value = false;
    }

    onMounted(() => {
      LayoutService.init();
      nextTick(() => { reinitializeComponents(); });
      document.addEventListener("click", onClickOutside);
      // Fetch awal & poll tiap 60 detik
      fetchNotifications();
      pollInterval = setInterval(fetchNotifications, 60000);
    });

    onUnmounted(() => {
      document.removeEventListener("click", onClickOutside);
      clearInterval(pollInterval);
    });

    watch(() => route.path, () => {
      nextTick(() => { LayoutService.init(); reinitializeComponents(); });
      showNotif.value = false;
    });

    const onLogout = () => { authStore.logout(); router.push({ name: "sign-in" }); };

    return {
      getAssetPath, authStore, avatarUrl, currentYear, sidebarRef, onLogout,
      notifRef, showNotif, notifLoading, notifications, unreadCount,
      toggleNotif, markRead, markAllRead, notifIcon, notifIconStyle, timeAgo,
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
.ul-notif-empty { padding: 24px; text-align: center; color: #505050; font-size: .7rem; }
.ul-notif-list { max-height: 260px; overflow-y: auto; }
.ul-notif-list::-webkit-scrollbar { width: 3px; }
.ul-notif-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,.08); border-radius: 2px; }
.ul-notif-row { display: flex; align-items: flex-start; gap: .65rem; padding: .7rem 1rem; cursor: pointer; position: relative; transition: background .15s; border-bottom: 1px solid #161616; }
.ul-notif-row:last-child { border-bottom: none; }
.ul-notif-row:hover { background: #181818; }
.ul-notif-unread { background: rgba(240,167,50,.025); }
.ul-notif-body { flex: 1; min-width: 0; }
.ul-notif-title { font-size: .71rem; font-weight: 600; color: #ccc; margin: 0 0 .1rem; }
.ul-notif-desc { font-size: .61rem; color: #666; margin: 0 0 .12rem; line-height: 1.4; }
.ul-notif-time { font-size: .57rem; color: #444; margin: 0; }
.ul-unread-dot { width: 6px; height: 6px; border-radius: 50%; background: #f0a732; flex-shrink: 0; margin-top: 5px; }
.ul-panel-foot { padding: .6rem 1rem; border-top: 1px solid #1a1a1a; }
.ul-view-all { display: flex; align-items: center; justify-content: center; gap: .35rem; font-size: .67rem; font-weight: 600; color: #505050; text-decoration: none; }
.ul-pop-enter-active, .ul-pop-leave-active { transition: opacity .17s ease, transform .17s ease; }
.ul-pop-enter-from, .ul-pop-leave-to { opacity: 0; transform: translateY(-7px) scale(.97); }
</style>