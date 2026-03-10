import type { MenuItem } from "@/layouts/default-layout/config/types";

const MainMenuConfig: Array<MenuItem> = [
  // ── Dashboard ──
  {
    pages: [
      {
        heading: "dashboard",
        route: "/dashboard",
        keenthemesIcon: "element-11",
        bootstrapIcon: "bi-app-indicator",
      },
    ],
  },

  // ── HR Management ──
  {
    heading: "HR Management",
    route: "/hr",
    pages: [
      {
        sectionTitle: "Absensi",
        route: "/hr/attendance",
        keenthemesIcon: "time",
        bootstrapIcon: "bi-clock",
        sub: [
          { heading: "Hari Ini",        route: "/hr/attendance/today" },
          { heading: "Laporan Bulanan", route: "/hr/attendance/monthly" },
        ],
      },
      {
        heading: "Data Izin & Cuti",
        route: "/hr/leaves",
        keenthemesIcon: "calendar",
        bootstrapIcon: "bi-calendar-check",
      },
      {
        heading: "Penilaian Performa",
        route: "/hr/performance",
        keenthemesIcon: "star",
        bootstrapIcon: "bi-star",
      },
      {
        heading: "Sanksi & Tugas",
        route: "/hr/sanctions",
        keenthemesIcon: "shield-tick",
        bootstrapIcon: "bi-shield-exclamation",
      },
      {
        heading: "Jabatan",
        route: "/hr/positions",
        keenthemesIcon: "briefcase",
        bootstrapIcon: "bi-briefcase",
      },
      {
        heading: "Face Management",
        route: "/hr/face-management",  // ← diperbaiki, sesuai router
        keenthemesIcon: "fingerprint-scanning",
        bootstrapIcon: "bi-camera-video",
      },
    ],
  },

  // ── Account ──
  {
    heading: "Account",
    route: "/crafted",
    pages: [
      {
        sectionTitle: "account",
        route: "/account",
        keenthemesIcon: "profile-circle",
        bootstrapIcon: "bi-person",
        sub: [
          { heading: "accountOverview", route: "/crafted/account/overview" },
          { heading: "settings",        route: "/crafted/account/settings" },
        ],
      },
    ],
  },
];

export default MainMenuConfig;