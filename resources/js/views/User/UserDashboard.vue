<template>
  <div class="row g-5 g-xl-10 mb-5 mb-xl-10">

    <!--begin::Welcome Hero Card-->
    <div class="col-12">
      <div class="card bgi-no-repeat bgi-position-x-end bgi-size-cover"
        style="background-color: #1C325E; background-image: url('/media/patterns/vector-1.png')">
        <div class="card-body py-10 px-10">
          <div class="d-flex align-items-center">
            <!--begin::Avatar-->
            <div class="symbol symbol-65px symbol-circle me-6">
              <img v-if="authStore.user.avatar" :src="avatarUrl" alt="avatar" class="object-fit-cover rounded-circle" />
              <div v-else class="symbol-label fs-1 fw-bold"
                style="background: rgba(255,255,255,0.15); color: #fff;">
                {{ authStore.user.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
              </div>
            </div>
            <!--end::Avatar-->
            <div class="flex-grow-1">
              <h2 class="text-white fw-bolder mb-1 fs-2">
                Selamat Datang, {{ authStore.user.name || 'Pengguna' }}! 👋
              </h2>
              <p class="text-white opacity-75 mb-3 fs-6">{{ authStore.user.email }}</p>
              <div class="d-flex align-items-center gap-3">
                <span class="badge badge-light fw-bold fs-8 px-3 py-2 text-capitalize">
                  <i class="ki-duotone ki-shield-tick fs-7 text-primary me-1">
                    <span class="path1"></span><span class="path2"></span>
                  </i>
                  {{ authStore.user.role || 'User' }}
                </span>
                <span class="badge badge-light-success fw-bold fs-8 px-3 py-2">
                  <span class="bullet bullet-dot bg-success me-1 h-6px w-6px"></span>
                  Akun Aktif
                </span>
              </div>
            </div>
            <div class="ms-auto d-none d-md-flex align-items-center gap-3">
              <router-link to="/user/profile" class="btn btn-light btn-sm fw-bold px-6">
                <i class="ki-duotone ki-pencil fs-4 me-1">
                  <span class="path1"></span><span class="path2"></span>
                </i>
                Edit Profil
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--end::Welcome Hero Card-->

    <!--begin::Profile Completion Stat-->
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body d-flex flex-column justify-content-between p-8">
          <div class="d-flex align-items-center mb-6">
            <div class="symbol symbol-50px me-4">
              <span class="symbol-label bg-light-primary">
                <i class="ki-duotone ki-profile-circle fs-2x text-primary">
                  <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
              </span>
            </div>
            <div>
              <div class="fw-bold text-gray-900 fs-5">Profile Status</div>
              <div class="text-muted fw-semibold fs-7">Kelengkapan profil kamu</div>
            </div>
          </div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="flex-grow-1 me-5">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted fs-7">Progress Kelengkapan</span>
                <span class="text-primary fw-bold fs-6">{{ profileCompletion }}%</span>
              </div>
              <div class="progress h-10px rounded-pill bg-light-primary">
                <div class="progress-bar bg-primary rounded-pill" :style="{ width: profileCompletion + '%' }" role="progressbar"></div>
              </div>
              <div class="text-muted fs-8 mt-2">{{ filledCount }} dari {{ totalFields }} field terisi</div>
            </div>
          </div>
          <div class="separator my-5"></div>
          <div class="row g-3">
            <div v-for="field in fieldStatus" :key="field.key" class="col-6">
              <div class="d-flex align-items-center">
                <span :class="['bullet bullet-dot me-2 h-6px w-6px', field.filled ? 'bg-success' : 'bg-danger']"></span>
                <span class="text-muted fs-8">{{ field.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--end::Profile Completion Stat-->

    <!--begin::Status Card-->
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body p-8">
          <div class="d-flex align-items-center mb-6">
            <div class="symbol symbol-50px me-4">
              <span class="symbol-label bg-light-success">
                <i class="ki-duotone ki-verify fs-2x text-success">
                  <span class="path1"></span><span class="path2"></span>
                </i>
              </span>
            </div>
            <div>
              <div class="fw-bold text-gray-900 fs-5">Status Akun</div>
              <div class="text-muted fw-semibold fs-7">Keamanan & verifikasi</div>
            </div>
          </div>
          <div class="separator my-5"></div>
          <div class="d-flex flex-column gap-4">
            <div class="d-flex align-items-center justify-content-between">
              <span class="text-muted fs-7 fw-semibold">Status</span>
              <span class="badge badge-light-success fw-bold">Aktif</span>
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <span class="text-muted fs-7 fw-semibold">Verifikasi Email</span>
              <span class="badge badge-light-success fw-bold">Terverifikasi</span>
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <span class="text-muted fs-7 fw-semibold">Keamanan</span>
              <span class="badge badge-light-warning fw-bold">Standard</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--end::Status Card-->

    <!--begin::Role Card-->
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body p-8">
          <div class="d-flex align-items-center mb-6">
            <div class="symbol symbol-50px me-4">
              <span class="symbol-label bg-light-warning">
                <i class="ki-duotone ki-shield-tick fs-2x text-warning">
                  <span class="path1"></span><span class="path2"></span>
                </i>
              </span>
            </div>
            <div>
              <div class="fw-bold text-gray-900 fs-5">Role & Akses</div>
              <div class="text-muted fw-semibold fs-7">Hak akses pengguna</div>
            </div>
          </div>
          <div class="separator my-5"></div>
          <div class="d-flex flex-column gap-4">
            <div class="d-flex align-items-center justify-content-between">
              <span class="text-muted fs-7 fw-semibold">Role</span>
              <span class="badge badge-light-warning fw-bold text-capitalize">{{ authStore.user.role || 'user' }}</span>
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <span class="text-muted fs-7 fw-semibold">Bergabung</span>
              <span class="fw-bold fs-7 text-gray-800">{{ joinDate }}</span>
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <span class="text-muted fs-7 fw-semibold">Akses Level</span>
              <span class="badge badge-light fw-bold">Standar</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--end::Role Card-->

    <!--begin::Profile Info Card-->
    <div class="col-md-8">
      <div class="card h-100">
        <div class="card-header border-0 pt-6">
          <div class="card-title flex-column">
            <h3 class="fw-bold mb-1 fs-3">Informasi Profil</h3>
            <div class="fs-7 text-muted fw-semibold">Data akun kamu saat ini</div>
          </div>
          <div class="card-toolbar">
            <router-link to="/user/profile" class="btn btn-sm btn-light-primary fw-bold">
              <i class="ki-duotone ki-pencil fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
              Edit Profil
            </router-link>
          </div>
        </div>
        <div class="card-body py-5">
          <div class="table-responsive">
            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-4">
              <tbody>
                <tr v-for="item in profileFields" :key="item.key">
                  <td class="py-4 ps-0" style="width: 35%">
                    <div class="d-flex align-items-center">
                      <span class="symbol symbol-30px me-3">
                        <span :class="['symbol-label', item.bgClass]">
                          <i :class="['ki-duotone', item.icon, 'fs-4', item.iconColor]">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                          </i>
                        </span>
                      </span>
                      <span class="text-muted fw-semibold fs-7">{{ item.label }}</span>
                    </div>
                  </td>
                  <td class="py-4">
                    <span v-if="item.value" class="text-gray-900 fw-bold fs-6">{{ item.value }}</span>
                    <span v-else class="badge badge-light-danger fw-semibold fs-8">Belum diisi</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!--end::Profile Info Card-->

    <!--begin::Quick Actions & Tips-->
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-header border-0 pt-6">
          <div class="card-title flex-column">
            <h3 class="fw-bold mb-1 fs-3">Quick Actions</h3>
            <div class="fs-7 text-muted fw-semibold">Aksi cepat untuk kamu</div>
          </div>
        </div>
        <div class="card-body pt-3 pb-5">
          <div class="d-flex flex-column gap-3">
            <router-link to="/user/profile" class="btn btn-light-primary w-100 d-flex align-items-center justify-content-start py-4">
              <span class="symbol symbol-35px me-4"><span class="symbol-label bg-primary"><i class="ki-duotone ki-profile-circle fs-4 text-white"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></span></span>
              <div class="text-start"><div class="fw-bold fs-7 text-primary">Update Profil</div><div class="text-muted fs-8">Lengkapi data kamu</div></div>
            </router-link>
            <div class="btn btn-light-warning w-100 d-flex align-items-center justify-content-start py-4 cursor-pointer">
              <span class="symbol symbol-35px me-4"><span class="symbol-label bg-warning"><i class="ki-duotone ki-lock-3 fs-4 text-white"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></span></span>
              <div class="text-start"><div class="fw-bold fs-7 text-warning">Ubah Password</div><div class="text-muted fs-8">Perbarui keamanan akun</div></div>
            </div>
            <div class="btn btn-light-info w-100 d-flex align-items-center justify-content-start py-4 cursor-pointer">
              <span class="symbol symbol-35px me-4"><span class="symbol-label bg-info"><i class="ki-duotone ki-notification fs-4 text-white"><span class="path1"></span><span class="path2"></span></i></span></span>
              <div class="text-start"><div class="fw-bold fs-7 text-info">Notifikasi</div><div class="text-muted fs-8">Atur preferensi notifikasi</div></div>
            </div>
          </div>
          <div class="separator my-6"></div>
          <div v-if="profileCompletion < 100" class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-5">
            <i class="ki-duotone ki-information-5 fs-2x text-warning me-4 flex-shrink-0"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            <div class="d-flex flex-stack flex-grow-1"><div class="fw-semibold"><div class="fs-7 text-gray-900 fw-bold">Profil Belum Lengkap</div><div class="fs-8 text-muted">Lengkapi profil kamu untuk pengalaman terbaik.</div></div></div>
          </div>
          <div v-else class="notice d-flex bg-light-success rounded border-success border border-dashed p-5">
            <i class="ki-duotone ki-verify fs-2x text-success me-4 flex-shrink-0"><span class="path1"></span><span class="path2"></span></i>
            <div class="fw-semibold"><div class="fs-7 text-gray-900 fw-bold">Profil Lengkap! 🎉</div><div class="fs-8 text-muted">Semua informasi profil sudah terisi.</div></div>
          </div>
        </div>
      </div>
    </div>
    <!--end::Quick Actions & Tips-->

  </div>
</template>

<script lang="ts">
import { defineComponent, computed } from "vue";
import { useAuthStore } from "@/stores/auth";

export default defineComponent({
  name: "user-dashboard",
  setup() {
    const authStore = useAuthStore();

    const avatarUrl = computed(() => {
      if (!authStore.user.avatar) return null;
      return `${import.meta.env.VITE_APP_API_URL?.replace('/api', '')}/storage/${authStore.user.avatar}`;
    });

    const fieldConfig = [
      { key: "name",      label: "Nama Lengkap", icon: "ki-profile-circle", bgClass: "bg-light-primary", iconColor: "text-primary" },
      { key: "email",     label: "Email",         icon: "ki-sms",            bgClass: "bg-light-info",    iconColor: "text-info" },
      { key: "phone",     label: "No. Telepon",   icon: "ki-phone",          bgClass: "bg-light-success", iconColor: "text-success" },
      { key: "job_title", label: "Jabatan",       icon: "ki-briefcase",      bgClass: "bg-light-warning", iconColor: "text-warning" },
      { key: "company",   label: "Perusahaan",    icon: "ki-building",       bgClass: "bg-light-danger",  iconColor: "text-danger" },
      { key: "bio",       label: "Bio",           icon: "ki-notepad",        bgClass: "bg-light-dark",    iconColor: "text-dark" },
    ];

    const profileFields = computed(() =>
      fieldConfig.map(f => ({ ...f, value: authStore.user[f.key as keyof typeof authStore.user] || "" }))
    );

    const fieldStatus = computed(() =>
      fieldConfig.map(f => ({ key: f.key, label: f.label, filled: !!authStore.user[f.key as keyof typeof authStore.user] }))
    );

    const filledCount = computed(() => fieldStatus.value.filter(f => f.filled).length);
    const totalFields = fieldConfig.length;
    const profileCompletion = computed(() => Math.round((filledCount.value / totalFields) * 100));

    const joinDate = computed(() => {
      const d = (authStore.user as any).created_at;
      if (!d) return "—";
      return new Date(d).toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });
    });

    return { authStore, avatarUrl, profileCompletion, profileFields, fieldStatus, filledCount, totalFields, joinDate };
  },
});
</script>