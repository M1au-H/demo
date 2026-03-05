import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw,
} from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useConfigStore } from "@/stores/config";

const routes: Array<RouteRecordRaw> = [
  // ===== ADMIN ROUTES =====
  {
    path: "/",
    redirect: "/dashboard",
    component: () => import("@/layouts/default-layout/DefaultLayout.vue"),
    meta: { middleware: "auth" },
    children: [
      {
        path: "/dashboard",
        name: "dashboard",
        component: () => import("@/views/Dashboard.vue"),
        meta: { pageTitle: "Dashboard", breadcrumbs: ["Dashboards"] },
      },
      {
        path: "/builder",
        name: "builder",
        component: () => import("@/views/LayoutBuilder.vue"),
        meta: { pageTitle: "Layout Builder", breadcrumbs: ["Layout"] },
      },
      {
        path: "/hr/attendance/today",
        name: "hr-attendance-today",
        component: () => import("@/views/admin/hr/attendance/TodayAttendance.vue"),
        meta: { pageTitle: "Absensi Hari Ini", breadcrumbs: ["HR", "Absensi"] },
      },
      {
        path: "/hr/attendance/monthly",
        name: "hr-attendance-monthly",
        component: () => import("@/views/admin/hr/attendance/MonthlyAttendance.vue"),
        meta: { pageTitle: "Laporan Bulanan", breadcrumbs: ["HR", "Absensi"] },
      },
      {
        path: "/hr/performance",
        name: "hr-performance",
        component: () => import("@/views/admin/hr/performance/ReviewForm.vue"),
        meta: { pageTitle: "Penilaian Performa", breadcrumbs: ["HR", "Performa"] },
      },
      {
        path: "/hr/sanctions",
        name: "hr-sanctions",
        component: () => import("@/views/admin/hr/sanction/SanctionForm.vue"),
        meta: { pageTitle: "Sanksi", breadcrumbs: ["HR", "Sanksi"] },
      },
      {
        path: "/hr/positions",
        name: "hr-positions",
        component: () => import("@/views/admin/hr/position/PositionList.vue"),
        meta: { pageTitle: "Jabatan", breadcrumbs: ["HR", "Jabatan"] },
      },
      {
        path: "/hr/leaves",
        name: "hr-leaves",
        component: () => import("@/views/admin/hr/leave/AdminLeaveList.vue"),
        meta: { pageTitle: "Data Izin & Cuti", breadcrumbs: ["HR", "Izin & Cuti"] },
      },
      {
        path: "/hr/face-management",
        name: "face-management",
        component: () => import("@/views/admin/hr/face/FaceManagement.vue"),
        meta: { pageTitle: "Face Management", breadcrumbs: ["HR", "Face Management"] },
      },
      {
        path: "/crafted/pages/profile",
        name: "profile",
        component: () => import("@/components/page-layouts/Profile.vue"),
        meta: { breadcrumbs: ["Pages", "Profile"] },
        children: [
          { path: "overview",    name: "profile-overview",    component: () => import("@/views/crafted/pages/profile/Overview.vue"),    meta: { pageTitle: "Overview" } },
          { path: "projects",    name: "profile-projects",    component: () => import("@/views/crafted/pages/profile/Projects.vue"),    meta: { pageTitle: "Projects" } },
          { path: "campaigns",   name: "profile-campaigns",   component: () => import("@/views/crafted/pages/profile/Campaigns.vue"),   meta: { pageTitle: "Campaigns" } },
          { path: "documents",   name: "profile-documents",   component: () => import("@/views/crafted/pages/profile/Documents.vue"),   meta: { pageTitle: "Documents" } },
          { path: "connections", name: "profile-connections", component: () => import("@/views/crafted/pages/profile/Connections.vue"), meta: { pageTitle: "Connections" } },
          { path: "activity",    name: "profile-activity",    component: () => import("@/views/crafted/pages/profile/Activity.vue"),    meta: { pageTitle: "Activity" } },
        ],
      },
      {
        path: "/crafted/pages/wizards/horizontal",
        name: "horizontal-wizard",
        component: () => import("@/views/crafted/pages/wizards/HorizontalWizardPage.vue"),
        meta: { pageTitle: "Horizontal", breadcrumbs: ["Pages", "Wizard"] },
      },
      {
        path: "/crafted/pages/wizards/vertical",
        name: "vertical-wizard",
        component: () => import("@/views/crafted/pages/wizards/VerticalWizardPage.vue"),
        meta: { pageTitle: "Vertical", breadcrumbs: ["Pages", "Wizard"] },
      },
      {
        path: "/crafted/account",
        name: "account",
        component: () => import("@/views/crafted/account/Account.vue"),
        meta: { breadcrumbs: ["Crafted", "Account"] },
        children: [
          { path: "overview", name: "account-overview", component: () => import("@/views/crafted/account/Overview.vue"), meta: { pageTitle: "Overview" } },
          { path: "settings", name: "account-settings", component: () => import("@/views/crafted/account/Settings.vue"), meta: { pageTitle: "Settings" } },
        ],
      },
      { path: "/apps/customers/getting-started",       name: "apps-customers-getting-started",       component: () => import("@/views/apps/customers/GettingStarted.vue"),        meta: { pageTitle: "Getting Started", breadcrumbs: ["Apps", "Customers"] } },
      { path: "/apps/customers/customers-listing",     name: "apps-customers-listing",               component: () => import("@/views/apps/customers/CustomersListing.vue"),      meta: { pageTitle: "Customers Listing", breadcrumbs: ["Apps", "Customers"] } },
      { path: "/apps/customers/customer-details",      name: "apps-customers-details",               component: () => import("@/views/apps/customers/CustomerDetails.vue"),       meta: { pageTitle: "Customers Details", breadcrumbs: ["Apps", "Customers"] } },
      { path: "/apps/subscriptions/getting-started",   name: "apps-subscriptions-getting-started",   component: () => import("@/views/apps/subscriptions/GettingStarted.vue"),   meta: { pageTitle: "Getting Started", breadcrumbs: ["Apps", "Subscriptions"] } },
      { path: "/apps/subscriptions/subscription-list", name: "apps-subscriptions-subscription-list", component: () => import("@/views/apps/subscriptions/SubscriptionList.vue"), meta: { pageTitle: "Subscription List", breadcrumbs: ["Apps", "Subscriptions"] } },
      { path: "/apps/subscriptions/add-subscription",  name: "apps-subscriptions-add-subscription",  component: () => import("@/views/apps/subscriptions/AddSubscription.vue"),  meta: { pageTitle: "Add Subscription", breadcrumbs: ["Apps", "Subscriptions"] } },
      { path: "/apps/subscriptions/view-subscription", name: "apps-subscriptions-view-subscription", component: () => import("@/views/apps/subscriptions/ViewSubscription.vue"), meta: { pageTitle: "View Subscription", breadcrumbs: ["Apps", "Subscriptions"] } },
      { path: "/apps/calendar",           name: "apps-calendar",      component: () => import("@/views/apps/Calendar.vue"),              meta: { pageTitle: "Calendar", breadcrumbs: ["Apps"] } },
      { path: "/apps/chat/private-chat",  name: "apps-private-chat",  component: () => import("@/views/apps/chat/Chat.vue"),             meta: { pageTitle: "Private Chat", breadcrumbs: ["Apps", "Chat"] } },
      { path: "/apps/chat/group-chat",    name: "apps-group-chat",    component: () => import("@/views/apps/chat/Chat.vue"),             meta: { pageTitle: "Group Chat", breadcrumbs: ["Apps", "Chat"] } },
      { path: "/apps/chat/drawer-chat",   name: "apps-drawer-chat",   component: () => import("@/views/apps/chat/DrawerChat.vue"),       meta: { pageTitle: "Drawer Chat", breadcrumbs: ["Apps", "Chat"] } },
      { path: "/crafted/modals/general/invite-friends",      name: "modals-general-invite-friends",  component: () => import("@/views/crafted/modals/general/InviteFriends.vue"), meta: { pageTitle: "Invite Friends" } },
      { path: "/crafted/modals/general/view-user",           name: "modals-general-view-user",       component: () => import("@/views/crafted/modals/general/ViewUsers.vue"),     meta: { pageTitle: "View User" } },
      { path: "/crafted/modals/general/upgrade-plan",        name: "modals-general-upgrade-plan",    component: () => import("@/views/crafted/modals/general/UpgradePlan.vue"),   meta: { pageTitle: "Upgrade Plan" } },
      { path: "/crafted/modals/general/share-and-earn",      name: "modals-general-share-and-earn",  component: () => import("@/views/crafted/modals/general/ShareAndEarn.vue"),  meta: { pageTitle: "Share And Earn" } },
      { path: "/crafted/modals/forms/new-target",            name: "modals-forms-new-target",        component: () => import("@/views/crafted/modals/forms/NewTarget.vue"),       meta: { pageTitle: "New Target" } },
      { path: "/crafted/modals/forms/new-card",              name: "modals-forms-new-card",          component: () => import("@/views/crafted/modals/forms/NewCard.vue"),         meta: { pageTitle: "New Card" } },
      { path: "/crafted/modals/forms/new-address",           name: "modals-forms-new-address",       component: () => import("@/views/crafted/modals/forms/NewAddress.vue"),      meta: { pageTitle: "New Address" } },
      { path: "/crafted/modals/forms/create-api-key",        name: "modals-forms-create-api-key",    component: () => import("@/views/crafted/modals/forms/CreateApiKey.vue"),    meta: { pageTitle: "Create Api Key" } },
      { path: "/crafted/modals/wizards/two-factor-auth",     name: "modals-wizards-two-factor-auth", component: () => import("@/views/crafted/modals/wizards/TwoFactorAuth.vue"), meta: { pageTitle: "Two Factory Auth" } },
      { path: "/crafted/modals/wizards/create-app",          name: "modals-wizards-create-app",      component: () => import("@/views/crafted/modals/wizards/CreateApp.vue"),     meta: { pageTitle: "Create App" } },
      { path: "/crafted/modals/wizards/create-account",      name: "modals-wizards-create-account",  component: () => import("@/views/crafted/modals/wizards/CreateAccount.vue"), meta: { pageTitle: "Create Account" } },
      { path: "/crafted/widgets/lists",      name: "widgets-list",       component: () => import("@/views/crafted/widgets/Lists.vue"),      meta: { pageTitle: "Lists" } },
      { path: "/crafted/widgets/statistics", name: "widgets-statistics", component: () => import("@/views/crafted/widgets/Statistics.vue"), meta: { pageTitle: "Statistics" } },
      { path: "/crafted/widgets/charts",     name: "widgets-charts",     component: () => import("@/views/crafted/widgets/Charts.vue"),     meta: { pageTitle: "Charts" } },
      { path: "/crafted/widgets/mixed",      name: "widgets-mixed",      component: () => import("@/views/crafted/widgets/Mixed.vue"),      meta: { pageTitle: "Mixed" } },
      { path: "/crafted/widgets/tables",     name: "widgets-tables",     component: () => import("@/views/crafted/widgets/Tables.vue"),     meta: { pageTitle: "Tables" } },
      { path: "/crafted/widgets/feeds",      name: "widgets-feeds",      component: () => import("@/views/crafted/widgets/Feeds.vue"),      meta: { pageTitle: "Feeds" } },
    ],
  },

  // ===== USER ROUTES =====
  {
    path: "/user",
    redirect: "/user/dashboard",
    component: () => import("@/layouts/UserLayout.vue"),
    meta: { middleware: "auth-user" },
    children: [
      {
        path: "/user/dashboard",
        name: "user-dashboard",
        component: () => import("@/views/user/UserDashboard.vue"),
        meta: { pageTitle: "Dashboard", middleware: "auth-user" },
      },
      {
        path: "/user/profile",
        name: "user-profile",
        component: () => import("@/views/user/UserProfile.vue"),
        meta: { pageTitle: "My Profile", middleware: "auth-user" },
      },
      {
        path: "/user/settings",
        name: "user-settings",
        component: () => import("@/views/user/UserSettings.vue"),
        meta: { pageTitle: "Settings", middleware: "auth-user" },
      },
      // ✅ ROUTE BARU: Absen Pulang via face recognition
      {
        path: "/user/attendance/check-out",
        name: "user-check-out",
        component: () => import("@/views/user/attendance/CheckOut.vue"),
        meta: { pageTitle: "Absen Pulang", middleware: "auth-user" },
      },
      {
        path: "/user/attendance/history",
        name: "user-attendance-history",
        component: () => import("@/views/user/attendance/MyHistory.vue"),
        meta: { pageTitle: "Riwayat Absensi", middleware: "auth-user" },
      },
      {
        path: "/user/attendance/leave",
        name: "user-leave",
        component: () => import("@/views/user/attendance/MyLeave.vue"),
        meta: { pageTitle: "Izin & Cuti", middleware: "auth-user" },
      },
      {
        path: "/user/performance",
        name: "user-performance",
        component: () => import("@/views/user/performance/MyPerformance.vue"),
        meta: { pageTitle: "Performa Saya", middleware: "auth-user" },
      },
    ],
  },

  // ===== AUTH ROUTES =====
  {
    path: "/",
    component: () => import("@/layouts/AuthLayout.vue"),
    children: [
      {
        path: "/sign-in",
        name: "sign-in",
        component: () => import("@/views/crafted/authentication/basic-flow/SignIn.vue"),
        meta: { pageTitle: "Sign In" },
      },
      {
        path: "/sign-up",
        name: "sign-up",
        component: () => import("@/views/crafted/authentication/basic-flow/SignUp.vue"),
        meta: { pageTitle: "Sign Up" },
      },
      {
        path: "/password-reset",
        name: "password-reset",
        component: () => import("@/views/crafted/authentication/basic-flow/PasswordReset.vue"),
        meta: { pageTitle: "Password reset" },
      },
    ],
  },

  // ===== SYSTEM ROUTES =====
  {
    path: "/",
    component: () => import("@/layouts/SystemLayout.vue"),
    children: [
      {
        path: "/404",
        name: "404",
        component: () => import("@/views/crafted/authentication/Error404.vue"),
        meta: { pageTitle: "Error 404" },
      },
      {
        path: "/500",
        name: "500",
        component: () => import("@/views/crafted/authentication/Error500.vue"),
        meta: { pageTitle: "Error 500" },
      },
    ],
  },

  { path: "/:pathMatch(.*)*", redirect: "/404" },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to) {
    if (to.hash) {
      return { el: to.hash, top: 80, behavior: "smooth" };
    }
    return { top: 0, left: 0, behavior: "smooth" };
  },
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  const configStore = useConfigStore();

  document.title = `${to.meta.pageTitle ?? "Page"} - ${import.meta.env.VITE_APP_NAME}`;
  configStore.resetLayoutConfig();

  const publicPages = ["sign-in", "sign-up", "password-reset", "404", "500"];
  const isPublicPage = publicPages.includes(to.name as string);

  // Hanya panggil verifyAuth jika belum authenticated
  if (!isPublicPage && !authStore.isAuthenticated) {
    await authStore.verifyAuth();
  }

  if (to.meta.middleware === "auth") {
    if (authStore.isAuthenticated && authStore.isAdmin()) {
      next();
    } else if (authStore.isAuthenticated && authStore.isUser()) {
      next({ name: "user-dashboard" });
    } else {
      next({ name: "sign-in" });
    }
  } else if (to.meta.middleware === "auth-user") {
    if (authStore.isAuthenticated && authStore.isUser()) {
      next();
    } else if (authStore.isAuthenticated && authStore.isAdmin()) {
      next({ name: "dashboard" });
    } else {
      next({ name: "sign-in" });
    }
  } else {
    next();
  }
});

export default router;