<template>
  <!--begin::Navbar-->
  <div class="card mb-5 mb-xl-10">
    <div class="card-body pt-9 pb-0">
      <!--begin::Details-->
      <div class="d-flex flex-wrap flex-sm-nowrap mb-3">
        <!--begin::Pic-->
        <div class="me-7 mb-4">
          <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
            <img
              v-if="user.avatar"
              :src="`/storage/${user.avatar}`"
              alt="avatar"
              style="object-fit: cover;"
            />
            <div
              v-else
              class="symbol-label fs-1 fw-bold text-primary"
              style="text-transform: uppercase;"
            >
              {{ user.name?.charAt(0) || '?' }}
            </div>
            <div class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-white h-20px w-20px"></div>
          </div>
        </div>
        <!--end::Pic-->

        <!--begin::Info-->
        <div class="flex-grow-1">
          <!--begin::Title-->
          <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
            <!--begin::User-->
            <div class="d-flex flex-column">
              <!--begin::Name-->
              <div class="d-flex align-items-center mb-2">
                <span class="text-gray-800 fs-2 fw-bold me-1">{{ user.name || '-' }}</span>
                <span
                  class="badge ms-2 fs-8"
                  :class="user.role === 'admin' ? 'badge-light-danger' : 'badge-light-primary'"
                >
                  {{ user.role === 'admin' ? 'Admin' : 'User' }}
                </span>
              </div>
              <!--end::Name-->

              <!--begin::Info-->
              <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                <span
                  v-if="user.job_title || user.position?.name"
                  class="d-flex align-items-center text-gray-500 me-5 mb-2"
                >
                  <KTIcon icon-name="profile-circle" icon-class="fs-4 me-1" />
                  {{ user.job_title || user.position?.name }}
                </span>
                <span
                  v-if="user.company"
                  class="d-flex align-items-center text-gray-500 me-5 mb-2"
                >
                  <KTIcon icon-name="office-bag" icon-class="fs-4 me-1" />
                  {{ user.company }}
                </span>
                <span class="d-flex align-items-center text-gray-500 mb-2">
                  <KTIcon icon-name="sms" icon-class="fs-4 me-1" />
                  {{ user.email || '-' }}
                </span>
              </div>
              <!--end::Info-->
            </div>
            <!--end::User-->
          </div>
          <!--end::Title-->

          <!--begin::Stats-->
          <div class="d-flex flex-wrap flex-stack">
            <div class="d-flex flex-column flex-grow-1 pe-8">
              <div class="d-flex flex-wrap">
                <!--begin::Stat - Phone-->
                <div
                  v-if="user.phone"
                  class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3"
                >
                  <div class="d-flex align-items-center">
                    <KTIcon icon-name="phone" icon-class="fs-3 text-primary me-2" />
                    <div class="fs-6 fw-bold">{{ user.phone }}</div>
                  </div>
                  <div class="fw-semibold fs-6 text-gray-500">Phone</div>
                </div>
                <!--end::Stat-->

                <!--begin::Stat - Position-->
                <div
                  v-if="user.position?.name"
                  class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3"
                >
                  <div class="d-flex align-items-center">
                    <KTIcon icon-name="briefcase" icon-class="fs-3 text-success me-2" />
                    <div class="fs-6 fw-bold">{{ user.position.name }}</div>
                  </div>
                  <div class="fw-semibold fs-6 text-gray-500">Position</div>
                </div>
                <!--end::Stat-->
              </div>
            </div>
          </div>
          <!--end::Stats-->
        </div>
        <!--end::Info-->
      </div>
      <!--end::Details-->

      <!--begin::Navs-->
      <div class="d-flex overflow-auto h-55px">
        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold flex-nowrap">
          <li class="nav-item">
            <router-link
              to="/crafted/account/overview"
              class="nav-link text-active-primary me-6"
              active-class="active"
            >
              Overview
            </router-link>
          </li>
          <li class="nav-item">
            <router-link
              to="/crafted/account/settings"
              class="nav-link text-active-primary me-6"
              active-class="active"
            >
              Settings
            </router-link>
          </li>
        </ul>
      </div>
      <!--end::Navs-->
    </div>
  </div>
  <!--end::Navbar-->
  <router-view></router-view>
</template>

<script lang="ts">
import { defineComponent, computed } from "vue";
import { useAuthStore } from "@/stores/auth";

export default defineComponent({
  name: "kt-account",
  setup() {
    const authStore = useAuthStore();
    const user = computed(() => authStore.user);

    return { user };
  },
});
</script>