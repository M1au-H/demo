<template>
  <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <!-- Welcome Card -->
    <div class="col-12">
      <div class="card bg-primary">
        <div class="card-body d-flex align-items-center py-8">
          <div>
            <h2 class="text-white fw-bold mb-1">Selamat Datang, {{ authStore.user.name }}</h2>
            <p class="text-white opacity-75 mb-0">{{ authStore.user.email }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center mb-5">
            <div class="symbol symbol-50px me-5">
              <span class="symbol-label bg-light-primary">
                <KTIcon icon-name="profile-circle" icon-class="fs-2x text-primary" />
              </span>
            </div>
            <div>
              <span class="text-gray-900 fw-bold fs-6">Profile Status</span>
              <p class="text-muted mb-0 fs-7">Kelengkapan profil kamu</p>
            </div>
          </div>
          <div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted fs-7">Progress</span>
              <span class="text-primary fw-bold fs-7">{{ profileCompletion }}%</span>
            </div>
            <div class="progress h-8px">
              <div
                class="progress-bar bg-primary"
                :style="{ width: profileCompletion + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="symbol symbol-50px me-5">
            <span class="symbol-label bg-light-success">
              <KTIcon icon-name="verify" icon-class="fs-2x text-success" />
            </span>
          </div>
          <div>
            <span class="text-gray-900 fw-bold fs-6 d-block">Status Akun</span>
            <span class="badge badge-light-success fw-bold mt-1">Aktif</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="symbol symbol-50px me-5">
            <span class="symbol-label bg-light-warning">
              <KTIcon icon-name="shield-tick" icon-class="fs-2x text-warning" />
            </span>
          </div>
          <div>
            <span class="text-gray-900 fw-bold fs-6 d-block">Role</span>
            <span class="badge badge-light-warning fw-bold mt-1 text-capitalize">
              {{ authStore.user.role }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile Info Card -->
    <div class="col-md-8">
      <div class="card">
        <div class="card-header border-0 pt-5">
          <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold fs-3 mb-1">Informasi Profil</span>
            <span class="text-muted mt-1 fw-semibold fs-7">Data akun kamu</span>
          </h3>
          <div class="card-toolbar">
            <router-link to="/user/profile" class="btn btn-sm btn-primary">
              Edit Profil
            </router-link>
          </div>
        </div>
        <div class="card-body py-3">
          <div class="row mb-4">
            <label class="col-lg-4 fw-semibold text-muted">Nama Lengkap</label>
            <div class="col-lg-8">
              <span class="fw-bold fs-6 text-gray-800">{{ authStore.user.name || '-' }}</span>
            </div>
          </div>
          <div class="row mb-4">
            <label class="col-lg-4 fw-semibold text-muted">Email</label>
            <div class="col-lg-8">
              <span class="fw-bold fs-6 text-gray-800">{{ authStore.user.email || '-' }}</span>
            </div>
          </div>
          <div class="row mb-4">
            <label class="col-lg-4 fw-semibold text-muted">No. Telepon</label>
            <div class="col-lg-8">
              <span class="fw-bold fs-6 text-gray-800">{{ authStore.user.phone || '-' }}</span>
            </div>
          </div>
          <div class="row mb-4">
            <label class="col-lg-4 fw-semibold text-muted">Jabatan</label>
            <div class="col-lg-8">
              <span class="fw-bold fs-6 text-gray-800">{{ authStore.user.job_title || '-' }}</span>
            </div>
          </div>
          <div class="row mb-4">
            <label class="col-lg-4 fw-semibold text-muted">Perusahaan</label>
            <div class="col-lg-8">
              <span class="fw-bold fs-6 text-gray-800">{{ authStore.user.company || '-' }}</span>
            </div>
          </div>
          <div class="row">
            <label class="col-lg-4 fw-semibold text-muted">Bio</label>
            <div class="col-lg-8">
              <span class="fw-bold fs-6 text-gray-800">{{ authStore.user.bio || '-' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-md-4">
      <div class="card">
        <div class="card-header border-0 pt-5">
          <h3 class="card-title">
            <span class="card-label fw-bold fs-3">Quick Actions</span>
          </h3>
        </div>
        <div class="card-body pt-0">
          <router-link to="/user/profile" class="btn btn-light-primary w-100 mb-3 justify-content-start">
            <KTIcon icon-name="profile-circle" icon-class="fs-3 me-2" />
            Update Profil
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, computed } from "vue";
import { useAuthStore } from "@/stores/auth";

export default defineComponent({
  name: "user-dashboard",
  setup() {
    const authStore = useAuthStore();

    const profileCompletion = computed(() => {
      const fields = ['name', 'email', 'phone', 'bio', 'job_title', 'company'];
      const filled = fields.filter(f => authStore.user[f as keyof typeof authStore.user]);
      return Math.round((filled.length / fields.length) * 100);
    });

    return { authStore, profileCompletion };
  },
});
</script>