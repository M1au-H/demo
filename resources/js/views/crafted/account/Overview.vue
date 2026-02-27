<template>
  <!--begin::details View-->
  <div class="card mb-5 mb-xl-10" id="kt_profile_details_view">
    <!--begin::Card header-->
    <div class="card-header cursor-pointer">
      <div class="card-title m-0">
        <h3 class="fw-bold m-0">Profile Details</h3>
      </div>
      <router-link
        to="/account/settings"
        class="btn btn-primary align-self-center"
        >Edit Profile</router-link
      >
    </div>
    <!--end::Card header-->

    <!--begin::Card body-->
    <div class="card-body p-9">

      <!--begin::Row - Full Name-->
      <div class="row mb-7">
        <label class="col-lg-4 fw-semibold text-muted">Full Name</label>
        <div class="col-lg-8">
          <span class="fw-bold fs-6 text-gray-900">{{ user.name || '-' }}</span>
        </div>
      </div>
      <!--end::Row-->

      <!--begin::Row - Email-->
      <div class="row mb-7">
        <label class="col-lg-4 fw-semibold text-muted">Email</label>
        <div class="col-lg-8">
          <span class="fw-bold fs-6 text-gray-900">{{ user.email || '-' }}</span>
        </div>
      </div>
      <!--end::Row-->

      <!--begin::Row - Phone-->
      <div class="row mb-7">
        <label class="col-lg-4 fw-semibold text-muted">
          Contact Phone
          <i
            class="fas fa-exclamation-circle ms-1 fs-7"
            v-tooltip
            title="Phone number must be active"
          ></i>
        </label>
        <div class="col-lg-8 d-flex align-items-center">
          <span class="fw-bold fs-6 me-2">{{ user.phone || '-' }}</span>
        </div>
      </div>
      <!--end::Row-->

      <!--begin::Row - Job Title-->
      <div class="row mb-7">
        <label class="col-lg-4 fw-semibold text-muted">Job Title</label>
        <div class="col-lg-8">
          <span class="fw-semibold fs-6">{{ user.job_title || '-' }}</span>
        </div>
      </div>
      <!--end::Row-->

      <!--begin::Row - Company-->
      <div class="row mb-7">
        <label class="col-lg-4 fw-semibold text-muted">Company</label>
        <div class="col-lg-8">
          <span class="fw-semibold fs-6">{{ user.company || '-' }}</span>
        </div>
      </div>
      <!--end::Row-->

      <!--begin::Row - Position-->
      <div class="row mb-7">
        <label class="col-lg-4 fw-semibold text-muted">Position</label>
        <div class="col-lg-8">
          <span class="fw-semibold fs-6">{{ user.position?.name || '-' }}</span>
        </div>
      </div>
      <!--end::Row-->

      <!--begin::Row - Role-->
      <div class="row mb-7">
        <label class="col-lg-4 fw-semibold text-muted">Role</label>
        <div class="col-lg-8">
          <span
            class="badge"
            :class="user.role === 'admin' ? 'badge-light-danger' : 'badge-light-primary'"
          >
            {{ user.role === 'admin' ? 'Admin' : 'User' }}
          </span>
        </div>
      </div>
      <!--end::Row-->

      <!--begin::Row - Bio-->
      <div class="row mb-7" v-if="user.bio">
        <label class="col-lg-4 fw-semibold text-muted">Bio</label>
        <div class="col-lg-8">
          <span class="fw-semibold fs-6 text-gray-700">{{ user.bio }}</span>
        </div>
      </div>
      <!--end::Row-->

    </div>
    <!--end::Card body-->
  </div>
  <!--end::details View-->

  <!--begin::Row-->
  <div class="row gy-10 gx-xl-10">
    <div class="col-xl-6">
      <KTChartWidget1 widget-classes="card-xxl-stretch mb-5 mb-xl-10"></KTChartWidget1>
    </div>
    <div class="col-xl-6">
      <KTListWidget1 widget-classes="card-xxl-stretch mb-5 mb-xl-10'"></KTListWidget1>
    </div>
  </div>
  <!--end::Row-->

  <!--begin::Row-->
  <div class="row gy-10 gx-xl-10">
    <div class="col-xl-6">
      <KTListWidget5 widget-classes="card-xxl-stretch mb-5 mb-xl-10"></KTListWidget5>
    </div>
    <div class="col-xl-6">
      <KTTableWidget5 widget-classes="card-xxl-stretch mb-5 mb-xl-10"></KTTableWidget5>
    </div>
  </div>
  <!--end::Row-->
</template>

<script lang="ts">
import { defineComponent, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import { getAssetPath } from "@/core/helpers/assets";
import KTChartWidget1 from "@/components/widgets/charts/Widget1.vue";
import KTListWidget5 from "@/components/widgets/lists/Widget5.vue";
import KTTableWidget5 from "@/components/widgets/tables/Widget5.vue";
import KTListWidget1 from "@/components/widgets/lists/Widget1.vue";

export default defineComponent({
  name: "account-overview",
  components: {
    KTChartWidget1,
    KTListWidget5,
    KTTableWidget5,
    KTListWidget1,
  },
  setup() {
    const authStore = useAuthStore();
    const user = computed(() => authStore.user);

    return {
      user,
      getAssetPath,
    };
  },
});
</script>