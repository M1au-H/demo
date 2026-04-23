// resources/js/layouts/default-layout/composables/useTabs.ts
import { ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

export interface Tab {
  label: string;
  route: string;
}

// State global (shared antar komponen)
const tabs = ref<Tab[]>([]);

// Label mapping dari route ke nama yang tampil
const ROUTE_LABELS: Record<string, string> = {
  "/dashboard":                  "Dashboard",
  "/hr/attendance/today":        "Absensi Hari Ini",
  "/hr/attendance/monthly":      "Laporan Bulanan",
  "/hr/leaves":                  "Data Izin & Cuti",
  "/hr/performance":             "Penilaian Performa",
  "/hr/sanctions":               "Sanksi & Tugas",
  "/hr/positions":               "Jabatan",
  "/hr/face-management":         "Face Management",
  "/hr/payroll":                 "Manajemen Payroll",
  "/hr/salary-settings":         "Pengaturan Gaji",
  "/hr/kpi":                     "Manajemen KPI",
  "/crafted/account/overview":   "Account Overview",
  "/crafted/account/settings":   "Settings",
};

function getLabel(path: string): string {
  if (ROUTE_LABELS[path]) return ROUTE_LABELS[path];
  const segment = path.split("/").filter(Boolean).pop() ?? path;
  return segment.charAt(0).toUpperCase() + segment.slice(1).replace(/-/g, " ");
}

export function useTabs() {
  const route  = useRoute();
  const router = useRouter();

  // Tambah tab otomatis saat route berubah
  watch(
    () => route.path,
    (newPath) => {
      const exists = tabs.value.find((t) => t.route === newPath);
      if (!exists) {
        tabs.value.push({ label: getLabel(newPath), route: newPath });
      }
    },
    { immediate: true }
  );

  const closeTab = (routePath: string) => {
    const idx = tabs.value.findIndex((t) => t.route === routePath);
    if (idx === -1) return;

    tabs.value.splice(idx, 1);

    // Jika tab yang ditutup adalah tab aktif → pindah ke tab terdekat
    if (route.path === routePath) {
      if (tabs.value.length > 0) {
        const nextTab = tabs.value[Math.max(0, idx - 1)];
        router.push(nextTab.route);
      } else {
        router.push("/dashboard");
      }
    }
  };

  // Tukar posisi dua tab berdasarkan index
  const reorderTabs = (fromIndex: number, toIndex: number) => {
    if (
      fromIndex === toIndex ||
      fromIndex < 0 || fromIndex >= tabs.value.length ||
      toIndex   < 0 || toIndex   >= tabs.value.length
    ) return;

    const updated = [...tabs.value];
    const [moved] = updated.splice(fromIndex, 1);
    updated.splice(toIndex, 0, moved);
    tabs.value = updated;
  };

  return { tabs, closeTab, reorderTabs };
}