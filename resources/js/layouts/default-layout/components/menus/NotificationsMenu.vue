<template>
  <!--begin::Menu-->
  <div
    class="menu menu-sub menu-sub-dropdown menu-column w-350px w-lg-375px"
    data-kt-menu="true"
  >
    <!--begin::Heading-->
    <div
      class="d-flex flex-column bgi-no-repeat rounded-top"
      :style="`background-image: url('${getAssetPath('/media/misc/menu-header-bg.jpg')}')`"
    >
      <!--begin::Title-->
      <h3 class="text-white fw-semibold px-9 mt-10 mb-6">
        Notifikasi
        <span v-if="unread > 0" class="fs-8 opacity-75 ps-3">{{ unread }} belum dibaca</span>
        <span v-else class="fs-8 opacity-75 ps-3">Semua sudah dibaca</span>
      </h3>
      <!--end::Title-->

      <!--begin::Tabs-->
      <ul class="nav nav-line-tabs nav-line-tabs-2x nav-stretch fw-semibold px-9">
        <li class="nav-item">
          <a class="nav-link text-white opacity-75 opacity-state-100 pb-4 active"
            data-bs-toggle="tab" href="#kt_topbar_notifications_1">HR</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white opacity-75 opacity-state-100 pb-4"
            data-bs-toggle="tab" href="#kt_topbar_notifications_3">Logs</a>
        </li>
      </ul>
      <!--end::Tabs-->
    </div>
    <!--end::Heading-->

    <!--begin::Tab content-->
    <div class="tab-content">

      <!--begin::Tab HR Notifications-->
      <div class="tab-pane fade show active" id="kt_topbar_notifications_1" role="tabpanel">

        <!-- Loading -->
        <div v-if="loading" class="text-center py-10 text-gray-500 fs-7">
          <span class="spinner-border spinner-border-sm me-2"></span> Memuat...
        </div>

        <!-- Empty -->
        <div v-else-if="notifications.length === 0" class="text-center py-10">
          <div style="font-size:36px">🔔</div>
          <p class="text-gray-500 fs-7 mt-2">Tidak ada notifikasi</p>
        </div>

        <!-- List -->
        <div v-else class="scroll-y mh-325px my-5 px-8">
          <div
            v-for="n in notifications"
            :key="n.id"
            class="d-flex flex-stack py-4"
            :style="!n.is_read ? 'background: rgba(27,132,255,0.04); border-radius: 8px; padding-left: 8px; padding-right: 8px;' : ''"
            @click="markRead(n)"
            style="cursor:pointer"
          >
            <!--begin::Section-->
            <div class="d-flex align-items-center">
              <!--begin::Symbol-->
              <div class="symbol symbol-35px me-4">
                <span class="symbol-label" :class="iconBg(n.type)">
                  <KTIcon :icon-name="iconName(n.type)" :icon-class="`text-${iconColor(n.type)}`" />
                </span>
              </div>
              <!--end::Symbol-->

              <!--begin::Title-->
              <div class="mb-0 me-2">
                <span class="fs-6 fw-bold" :class="!n.is_read ? 'text-gray-900' : 'text-gray-700'">
                  {{ n.title }}
                </span>
                <div class="text-gray-500 fs-7">{{ n.message }}</div>
              </div>
              <!--end::Title-->
            </div>
            <!--end::Section-->

            <!--begin::Right-->
            <div class="d-flex flex-column align-items-end gap-1">
              <span class="badge badge-light fs-8">{{ timeAgo(n.created_at) }}</span>
              <span v-if="!n.is_read" class="badge badge-circle badge-primary w-6px h-6px"></span>
            </div>
            <!--end::Right-->
          </div>
        </div>

        <!--begin::Footer-->
        <div class="py-3 text-center border-top d-flex justify-content-center gap-4">
          <button
            v-if="unread > 0"
            @click="markAllRead"
            class="btn btn-sm btn-color-gray-600 btn-active-color-primary"
          >
            Tandai semua dibaca
          </button>
        </div>
        <!--end::Footer-->
      </div>
      <!--end::Tab HR-->

      <!--begin::Tab Logs-->
      <div class="tab-pane fade" id="kt_topbar_notifications_3" role="tabpanel">
        <div class="scroll-y mh-325px my-5 px-8">
          <template v-for="(item, index) in data2" :key="index">
            <div class="d-flex flex-stack py-4">
              <div class="d-flex align-items-center me-2">
                <span class="w-70px badge me-4" :class="`badge-light-${item.state}`">{{ item.code }}</span>
                <a href="#" class="text-gray-800 text-hover-primary fw-semibold">{{ item.message }}</a>
              </div>
              <span class="badge badge-light fs-8">{{ item.time }}</span>
            </div>
          </template>
        </div>
        <div class="py-3 text-center border-top">
          <a href="#" class="btn btn-color-gray-600 btn-active-color-primary">
            View All <KTIcon icon-name="arrow-right" icon-class="fs-5" />
          </a>
        </div>
      </div>
      <!--end::Tab Logs-->

    </div>
    <!--end::Tab content-->
  </div>
  <!--end::Menu-->
</template>

<script lang="ts">
import { getAssetPath, getIllustrationsPath } from "@/core/helpers/assets";
import { defineComponent, ref, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";

export default defineComponent({
  name: "notifications-menu",
  setup() {
    const notifications = ref<any[]>([])
    const unread        = ref(0)
    const loading       = ref(true)

    const fetchNotifications = async () => {
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get("notifications")
        notifications.value = data.data
        unread.value        = data.unread
      } catch (_) {}
      finally { loading.value = false }
    }

    const markRead = async (n: any) => {
      if (n.is_read) return
      try {
        ApiService.setHeader()
        await ApiService.post(`notifications/${n.id}/read`, {})
        n.is_read = true
        unread.value = Math.max(0, unread.value - 1)
      } catch (_) {}
    }

    const markAllRead = async () => {
      try {
        ApiService.setHeader()
        await ApiService.post("notifications/read-all", {})
        notifications.value.forEach(n => n.is_read = true)
        unread.value = 0
      } catch (_) {}
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

    const iconName = (type: string) => ({ success: "check-circle", warning: "warning-2", info: "information-5" }[type] ?? "bell")
    const iconColor = (type: string) => ({ success: "success", warning: "warning", info: "primary" }[type] ?? "primary")
    const iconBg    = (type: string) => ({ success: "bg-light-success", warning: "bg-light-warning", info: "bg-light-primary" }[type] ?? "bg-light-primary")

    onMounted(() => { fetchNotifications() })

    const data2 = [
      { code: "200 OK",  state: "success", message: "New order",       time: "Just now" },
      { code: "500 ERR", state: "danger",  message: "New customer",    time: "2 hrs"    },
      { code: "200 OK",  state: "success", message: "Payment process", time: "5 hrs"    },
      { code: "300 WRN", state: "warning", message: "Search query",    time: "2 days"   },
      { code: "200 OK",  state: "success", message: "API connection",  time: "1 week"   },
      { code: "200 OK",  state: "success", message: "Database restore",time: "Mar 5"    },
      { code: "300 WRN", state: "warning", message: "System update",   time: "May 15"   },
    ];

    return {
      notifications, unread, loading,
      fetchNotifications, markRead, markAllRead,
      timeAgo, iconName, iconColor, iconBg,
      data2, getIllustrationsPath, getAssetPath,
    };
  },
});
</script>