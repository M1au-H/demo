<template>
  <Transition name="tabbar-slide">
    <div v-if="tabs.length > 0 && visible" class="kt-tabbar">
      <div class="kt-tabbar__scroll" ref="scrollEl">
        <div class="kt-tabbar__inner" ref="innerEl">

          <div
            v-for="(tab, index) in tabs"
            :key="tab.route"
            class="kt-tab"
            :class="{
              'kt-tab--active':  activeRoute === tab.route,
              'kt-tab--dragging': dragIndex === index,
              'kt-tab--ghost':    dragIndex === index,
            }"
            :style="getTabStyle(index)"
            @mousedown="onTabMouseDown($event, index)"
            @click="onTabClick(tab.route, index)"
          >
            <span class="kt-tab__dot"></span>
            <span class="kt-tab__title">{{ tab.label }}</span>
            <button
              class="kt-tab__close"
              @mousedown.stop
              @click.stop="closeTab(tab.route)"
              title="Tutup tab"
              aria-label="Tutup tab"
            >
              <svg width="10" height="10" viewBox="0 0 8 8" fill="none">
                <path d="M1 1L7 7M7 1L1 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
            </button>
          </div>

          <!-- Tombol tambah tab (opsional, bisa dihapus) -->
          <button class="kt-tab__add" title="Buka halaman baru" @click="$router.push('/dashboard')">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
              <path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </button>

        </div>
      </div>
    </div>
  </Transition>

  <!-- Ghost tab yang mengikuti mouse saat drag -->
  <Teleport to="body">
    <div
      v-if="isDragging && ghostTab"
      class="kt-tab-ghost"
      :style="ghostStyle"
    >
      <span class="kt-tab__dot" style="background:#009ef7"></span>
      <span class="kt-tab__title" style="color:rgba(255,255,255,0.9)">{{ ghostTab.label }}</span>
    </div>
  </Teleport>
</template>

<script lang="ts">
import { defineComponent, computed, ref, onMounted, onUnmounted, reactive } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useTabs } from "@/layouts/default-layout/composables/useTabs";

export default defineComponent({
  name: "KTTabBar",
  setup() {
    const router = useRouter();
    const route  = useRoute();
    const { tabs, closeTab, reorderTabs } = useTabs();

    const activeRoute = computed(() => route.path);
    const visible     = ref(true);
    const scrollEl    = ref<HTMLElement | null>(null);
    const innerEl     = ref<HTMLElement | null>(null);

    // ── Page scroll hide/show ─────────────────────────────────────────────────
    let lastScrollY = window.scrollY;
    const handlePageScroll = () => {
      const y = window.scrollY;
      visible.value = y <= lastScrollY || y <= 60;
      lastScrollY = y;
    };

    // ── Drag-to-reorder state ─────────────────────────────────────────────────
    const isDragging  = ref(false);
    const dragIndex   = ref<number>(-1);
    const ghostTab    = computed(() => dragIndex.value >= 0 ? tabs.value[dragIndex.value] : null);

    const ghostStyle  = reactive({
      left:     "0px",
      top:      "0px",
      width:    "0px",
      height:   "0px",
      opacity:  "0",
    });

    let mouseStartX   = 0;
    let mouseStartY   = 0;
    let tabStartLeft  = 0;
    let didDrag       = false;
    let clickIndex    = -1;
    const DRAG_THRESH = 6; // px sebelum dianggap drag

    // Kumpulkan rect setiap tab saat drag mulai
    let tabRects: DOMRect[] = [];

    const getTabElements = (): HTMLElement[] => {
      if (!innerEl.value) return [];
      return Array.from(innerEl.value.querySelectorAll(".kt-tab")) as HTMLElement[];
    };

    const onTabMouseDown = (e: MouseEvent, index: number) => {
      if (e.button !== 0) return; // hanya klik kiri
      mouseStartX = e.clientX;
      mouseStartY = e.clientY;
      clickIndex  = index;
      didDrag     = false;

      // Rekam rect semua tab
      const els = getTabElements();
      tabRects   = els.map(el => el.getBoundingClientRect());

      // Ghost: ukuran & posisi awal = tab asli
      if (tabRects[index]) {
        const r = tabRects[index];
        tabStartLeft = r.left;
        Object.assign(ghostStyle, {
          left:    r.left + "px",
          top:     r.top  + "px",
          width:   r.width  + "px",
          height:  r.height + "px",
          opacity: "0",
        });
      }

      e.preventDefault();
    };

    const handleWindowMouseMove = (e: MouseEvent) => {
      if (clickIndex < 0) return;

      const dx = e.clientX - mouseStartX;
      const dy = e.clientY - mouseStartY;

      if (!didDrag && Math.sqrt(dx * dx + dy * dy) < DRAG_THRESH) return;

      // Mulai drag
      if (!isDragging.value) {
        isDragging.value  = true;
        didDrag           = true;
        dragIndex.value   = clickIndex;
        ghostStyle.opacity = "1";
      }

      // Pindahkan ghost
      ghostStyle.left = (tabStartLeft + dx) + "px";

      // Cek apakah ghost melewati tab lain → reorder
      const ghostCenterX = tabStartLeft + dx + parseFloat(ghostStyle.width) / 2;

      // Refresh rects saat drag
      const els = getTabElements();
      for (let i = 0; i < els.length; i++) {
        if (i === dragIndex.value) continue;
        const r = els[i].getBoundingClientRect();
        const centerX = r.left + r.width / 2;

        // Geser ke kanan: ghost center melewati center tab di sebelah kanan
        if (dragIndex.value < i && ghostCenterX > centerX) {
          reorderTabs(dragIndex.value, i);
          dragIndex.value = i;
          // Update rects setelah reorder
          tabRects = getTabElements().map(el => el.getBoundingClientRect());
          break;
        }
        // Geser ke kiri: ghost center melewati center tab di sebelah kiri
        if (dragIndex.value > i && ghostCenterX < centerX) {
          reorderTabs(dragIndex.value, i);
          dragIndex.value = i;
          tabRects = getTabElements().map(el => el.getBoundingClientRect());
          break;
        }
      }
    };

    const handleWindowMouseUp = (e: MouseEvent) => {
      if (clickIndex < 0) return;

      if (!didDrag) {
        // Klik biasa → navigasi
        const tab = tabs.value[clickIndex];
        if (tab) router.push(tab.route);
      }

      // Reset semua state
      isDragging.value   = false;
      dragIndex.value    = -1;
      ghostStyle.opacity = "0";
      clickIndex         = -1;
      didDrag            = false;
    };

    // Tab click hanya dari template (bukan dari mousedown handler)
    const onTabClick = (_route: string, _index: number) => {
      // Navigasi sudah ditangani di handleWindowMouseUp
      // Fungsi ini sengaja kosong — diperlukan agar @click.stop pada close button bisa bekerja
    };

    const tabStyle = computed(() => {
      const n = tabs.value.length;
      if (n <= 3)  return { minWidth: "180px", maxWidth: "240px", padding: "9px 16px",  fontSize: "13.5px" };
      if (n <= 6)  return { minWidth: "140px", maxWidth: "190px", padding: "9px 14px",  fontSize: "13px"   };
      if (n <= 10) return { minWidth: "100px", maxWidth: "150px", padding: "8px 12px",  fontSize: "12px"   };
      return           { minWidth: "76px",  maxWidth: "110px", padding: "7px 10px",  fontSize: "11.5px" };
    });

    const getTabStyle = (index: number) => {
      const base = tabStyle.value;
      return {
        ...base,
        // Tab yang sedang di-drag: transparan (ghost yang kelihatan)
        opacity: isDragging.value && dragIndex.value === index ? "0.35" : "1",
        transition: isDragging.value ? "none" : "opacity 0.15s ease",
      };
    };

    onMounted(() => {
      window.addEventListener("scroll",    handlePageScroll,      { passive: true  });
      window.addEventListener("mousemove", handleWindowMouseMove, { passive: false });
      window.addEventListener("mouseup",   handleWindowMouseUp);
    });

    onUnmounted(() => {
      window.removeEventListener("scroll",    handlePageScroll);
      window.removeEventListener("mousemove", handleWindowMouseMove);
      window.removeEventListener("mouseup",   handleWindowMouseUp);
    });

    return {
      tabs, activeRoute, onTabClick, onTabMouseDown, closeTab,
      tabStyle, getTabStyle, visible, scrollEl, innerEl,
      isDragging, dragIndex, ghostTab, ghostStyle,
    };
  },
});
</script>

<style scoped>
.kt-tabbar {
  width: 100%;
  background: transparent;
  overflow: hidden;
  padding-top: 8px;
}

.kt-tabbar__scroll {
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: none;
}

.kt-tabbar__scroll::-webkit-scrollbar {
  display: none;
}

.kt-tabbar__inner {
  display: flex;
  align-items: flex-end;
  padding: 0 16px;
  gap: 2px;
  min-height: 46px;
  width: max-content;
  min-width: 100%;
}

.kt-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  border-radius: 8px 8px 0 0;
  background: transparent;
  border: 1px solid transparent;
  border-bottom: none;
  cursor: pointer;
  user-select: none;
  -webkit-user-drag: none;
  white-space: nowrap;
  position: relative;
  overflow: hidden;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.kt-tab:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.06);
}

.kt-tab--active {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.08);
}

.kt-tab--active::after {
  content: "";
  position: absolute;
  bottom: -1px;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--kt-app-header-base-bg-color, #1e1e2d);
}

/* Tab yang sedang di-drag — sedikit transparan */
.kt-tab--dragging {
  outline: 1px dashed rgba(255, 255, 255, 0.15);
  outline-offset: -2px;
}

.kt-tab__dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  flex-shrink: 0;
  transition: background 0.15s;
}

.kt-tab--active .kt-tab__dot {
  background: #009ef7;
}

.kt-tab__title {
  font-weight: 500;
  color: rgba(255, 255, 255, 0.38);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  transition: color 0.15s;
  pointer-events: none;
}

.kt-tab--active .kt-tab__title,
.kt-tab:hover   .kt-tab__title {
  color: rgba(255, 255, 255, 0.88);
}

.kt-tab__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border: none;
  background: transparent;
  border-radius: 4px;
  color: rgba(255, 255, 255, 0.2);
  padding: 0;
  cursor: pointer;
  flex-shrink: 0;
  opacity: 0;
  transition: background 0.12s, color 0.12s, opacity 0.12s;
  pointer-events: auto;
}

.kt-tab--active .kt-tab__close { opacity: 0.45; }
.kt-tab:hover   .kt-tab__close { opacity: 0.55; }

.kt-tab__close:hover {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.9);
  opacity: 1 !important;
}

/* Tombol tambah tab */
.kt-tab__add {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: transparent;
  border-radius: 6px;
  color: rgba(255, 255, 255, 0.3);
  cursor: pointer;
  margin-left: 4px;
  margin-bottom: 6px;
  flex-shrink: 0;
  transition: background 0.13s, color 0.13s, border-color 0.13s;
}

.kt-tab__add:hover {
  background: rgba(255, 255, 255, 0.06);
  color: rgba(255, 255, 255, 0.7);
  border-color: rgba(255, 255, 255, 0.15);
}

/* tabbar transition */
.tabbar-slide-enter-active,
.tabbar-slide-leave-active {
  transition: max-height 0.25s ease, opacity 0.25s ease, padding 0.25s ease;
  max-height: 80px;
  overflow: hidden;
}
.tabbar-slide-enter-from,
.tabbar-slide-leave-to { max-height: 0; opacity: 0; padding-top: 0; }

/* tab add/remove transition */
.tab-enter-active, .tab-leave-active { transition: all 0.18s ease; }
.tab-enter-from { opacity: 0; transform: translateY(4px) scaleX(0.9); }
.tab-leave-to   { opacity: 0; transform: translateY(4px) scaleX(0.9); }
</style>

<!-- Ghost tab: style TIDAK scoped karena di-teleport ke body -->
<style>
.kt-tab-ghost {
  position: fixed;
  z-index: 99999;
  pointer-events: none;
  display: flex;
  align-items: center;
  gap: 8px;
  border-radius: 8px 8px 0 0;
  background: rgba(255, 255, 255, 0.10);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-bottom: none;
  padding: 9px 16px;
  white-space: nowrap;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
  transition: opacity 0.1s ease;
  font-size: 13px;
  font-weight: 500;
  font-family: inherit;
}

.kt-tab-ghost .kt-tab__dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.kt-tab-ghost .kt-tab__title {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  font-size: 13px;
  font-weight: 500;
}
</style>