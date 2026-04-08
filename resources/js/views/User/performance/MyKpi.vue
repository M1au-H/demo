<template>
  <div class="card">
    <div class="card-header border-0 pt-6">
      <div class="card-title flex-column">
        <h2 class="fw-bold mb-1">KPI Saya</h2>
        <span class="text-muted fs-7">Perkembangan kinerja kamu per bulan</span>
      </div>
      <div class="card-toolbar gap-3">
        <select v-model="selectedMonth" class="form-select form-select-sm w-120px" @change="load">
          <option v-for="m in months" :key="m.v" :value="m.v">{{ m.l }}</option>
        </select>
        <select v-model="selectedYear" class="form-select form-select-sm w-90px" @change="load">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
    </div>

    <div class="card-body pt-0">

      <!-- Loading -->
      <div v-if="loading" class="d-flex justify-content-center py-15">
        <div class="spinner-border text-primary"></div>
      </div>

      <!-- Belum ada KPI -->
      <div v-else-if="!kpi" class="text-center py-15">
        <KTIcon icon-name="chart-line-up" icon-class="fs-3x text-muted mb-4" />
        <div class="text-muted fs-6">KPI kamu belum dihitung untuk bulan ini.<br/>Hubungi admin untuk info lebih lanjut.</div>
      </div>

      <!-- Data KPI -->
      <div v-else class="row g-6">

        <!-- Kiri: Lingkaran Achievement -->
        <div class="col-12 col-md-4">
          <div class="card card-flush h-100 border border-dashed">
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-10 gap-4">

              <!-- SVG Donut -->
              <div class="position-relative">
                <svg width="180" height="180" viewBox="0 0 180 180">
                  <!-- Track -->
                  <circle cx="90" cy="90" r="75" fill="none" stroke="#f1f1f4" stroke-width="16"/>
                  <!-- Progress -->
                  <circle
                    cx="90" cy="90" r="75"
                    fill="none"
                    :stroke="progressStroke(kpi.achievement)"
                    stroke-width="16"
                    stroke-linecap="round"
                    :stroke-dasharray="circumference"
                    :stroke-dashoffset="dashOffset"
                    transform="rotate(-90 90 90)"
                    style="transition: stroke-dashoffset 1s ease"
                  />
                </svg>
                <div class="position-absolute top-50 start-50 translate-middle text-center">
                  <div class="fs-1 fw-bold lh-1" :class="scoreColor(kpi.achievement)">
                    {{ kpi.achievement }}%
                  </div>
                  <div class="text-muted fs-7 mt-1">Achievement</div>
                </div>
              </div>

              <!-- Status Label -->
              <div class="text-center">
                <div class="fw-bold fs-5 text-gray-800">{{ statusLabel(kpi.achievement) }}</div>
                <div class="text-muted fs-7 mt-1">
                  KPI {{ kpi.final_kpi }}% dari target {{ kpi.kpi_target }}%
                </div>
              </div>

              <!-- Adj Gaji -->
              <div class="w-100 bg-light rounded p-4 text-center">
                <div class="text-muted fs-7 mb-1">Penyesuaian Gaji</div>
                <div class="fs-3 fw-bold" :class="kpi.kpi_adjustment >= 0 ? 'text-success' : 'text-danger'">
                  {{ kpi.kpi_adjustment >= 0 ? '+' : '' }}{{ formatRp(kpi.kpi_adjustment) }}
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Kanan: Komponen & Detail -->
        <div class="col-12 col-md-8">
          <div class="card card-flush h-100 border border-dashed">
            <div class="card-body py-7 px-7">

              <div class="fw-bold text-gray-700 fs-5 mb-6">Rincian Komponen KPI</div>

              <!-- Kehadiran -->
              <div class="mb-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <div class="w-8px h-8px rounded-circle bg-primary"></div>
                    <span class="fw-semibold text-gray-700">Kehadiran</span>
                  </div>
                  <span class="fw-bold" :class="scoreColor(kpi.attendance_score)">
                    {{ kpi.attendance_score }}%
                  </span>
                </div>
                <div class="h-10px bg-light rounded-pill overflow-hidden">
                  <div
                    class="h-100 rounded-pill"
                    :class="progressColor(kpi.attendance_score)"
                    :style="{ width: kpi.attendance_score + '%', transition: 'width 1s ease' }"
                  ></div>
                </div>
              </div>

              <!-- Tugas -->
              <div class="mb-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <div class="w-8px h-8px rounded-circle bg-success"></div>
                    <span class="fw-semibold text-gray-700">Penyelesaian Tugas</span>
                  </div>
                  <span class="fw-bold" :class="scoreColor(kpi.task_score)">
                    {{ kpi.task_score }}%
                  </span>
                </div>
                <div class="h-10px bg-light rounded-pill overflow-hidden">
                  <div
                    class="h-100 rounded-pill"
                    :class="progressColor(kpi.task_score)"
                    :style="{ width: kpi.task_score + '%', transition: 'width 1s ease' }"
                  ></div>
                </div>
              </div>

              <!-- Rating Admin -->
              <div class="mb-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <div class="w-8px h-8px rounded-circle bg-warning"></div>
                    <span class="fw-semibold text-gray-700">Penilaian Atasan</span>
                  </div>
                  <span class="fw-bold" :class="scoreColor(kpi.admin_rating)">
                    {{ kpi.admin_rating }}%
                  </span>
                </div>
                <div class="h-10px bg-light rounded-pill overflow-hidden">
                  <div
                    class="h-100 rounded-pill"
                    :class="progressColor(kpi.admin_rating)"
                    :style="{ width: kpi.admin_rating + '%', transition: 'width 1s ease' }"
                  ></div>
                </div>
              </div>

              <div class="separator my-5"></div>

              <!-- Ringkasan -->
              <div class="row g-4">
                <div class="col-4 text-center">
                  <div class="fs-4 fw-bold text-gray-800">{{ kpi.final_kpi }}%</div>
                  <div class="text-muted fs-7">KPI Final</div>
                </div>
                <div class="col-4 text-center">
                  <div class="fs-4 fw-bold text-primary">{{ kpi.kpi_target }}%</div>
                  <div class="text-muted fs-7">Target Jabatan</div>
                </div>
                <div class="col-4 text-center">
                  <div class="fs-4 fw-bold" :class="kpi.achievement >= 100 ? 'text-success' : kpi.achievement >= 70 ? 'text-warning' : 'text-danger'">
                    {{ kpi.achievement }}%
                  </div>
                  <div class="text-muted fs-7">Pencapaian</div>
                </div>
              </div>

              <!-- Catatan Admin -->
              <div v-if="kpi.notes" class="mt-5 bg-light rounded p-4">
                <div class="fw-semibold text-gray-700 mb-1 fs-7">
                  <KTIcon icon-name="message-text" icon-class="fs-6 me-1" />
                  Catatan dari Atasan
                </div>
                <div class="text-gray-600 fs-7">{{ kpi.notes }}</div>
              </div>

            </div>
          </div>
        </div>

        <!-- Info Status -->
        <div class="col-12">
          <div class="rounded p-4 d-flex align-items-start gap-3" :class="statusBg(kpi.achievement)">
            <KTIcon icon-name="information" icon-class="fs-3 mt-1" :class="statusIconColor(kpi.achievement)" />
            <div>
              <div class="fw-bold fs-6 mb-1" :class="statusIconColor(kpi.achievement)">
                {{ statusTitle(kpi.achievement) }}
              </div>
              <div class="fs-7 text-gray-600">{{ statusDesc(kpi.achievement) }}</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue'
import axios from 'axios'

const API     = (import.meta.env.VITE_APP_API_URL || '/api').replace(/\/$/, '')
const headers = () => ({
  Authorization: `Bearer ${localStorage.getItem('api_token') || localStorage.getItem('id_token') || ''}`
})

const MONTHS = [
  { v:1,  l:'Januari'   }, { v:2,  l:'Februari' }, { v:3,  l:'Maret'    }, { v:4,  l:'April'    },
  { v:5,  l:'Mei'       }, { v:6,  l:'Juni'      }, { v:7,  l:'Juli'     }, { v:8,  l:'Agustus'  },
  { v:9,  l:'September' }, { v:10, l:'Oktober'   }, { v:11, l:'November' }, { v:12, l:'Desember' },
]

const circumference = 2 * Math.PI * 75 // r=75

export default defineComponent({
  name: 'MyKpi',
  setup() {
    const now           = new Date()
    const kpi           = ref<any>(null)
    const loading       = ref(false)
    const selectedMonth = ref(now.getMonth() + 1)
    const selectedYear  = ref(now.getFullYear())
    const months        = MONTHS
    const years         = Array.from({ length: 3 }, (_, i) => now.getFullYear() - i)

    const dashOffset = computed(() => {
      if (!kpi.value) return circumference
      const pct = Math.min(kpi.value.achievement, 100) / 100
      return circumference - (circumference * pct)
    })

    const load = async () => {
      loading.value = true
      kpi.value     = null
      try {
        const res = await axios.get(`${API}/kpi/my`, {
          headers: headers(),
          params: { month: selectedMonth.value, year: selectedYear.value },
        })
        kpi.value = res.data?.data ?? null
      } catch {
        kpi.value = null
      } finally {
        loading.value = false
      }
    }

    const formatRp = (v: number) =>
      'Rp ' + (Number(v) || 0).toLocaleString('id-ID')

    const scoreColor = (v: number) => {
      if (v >= 90) return 'text-success'
      if (v >= 70) return 'text-primary'
      if (v >= 50) return 'text-warning'
      return 'text-danger'
    }

    const progressColor = (v: number) => {
      if (v >= 90) return 'bg-success'
      if (v >= 70) return 'bg-primary'
      if (v >= 50) return 'bg-warning'
      return 'bg-danger'
    }

    const progressStroke = (v: number) => {
      if (v >= 100) return '#50cd89'
      if (v >= 85)  return '#009ef7'
      if (v >= 70)  return '#ffc700'
      return '#f1416c'
    }

    const statusLabel = (v: number) => {
      if (v >= 100) return 'Melampaui Target'
      if (v >= 85)  return 'Mendekati Target'
      if (v >= 70)  return 'Perlu Peningkatan'
      return 'Di Bawah Target'
    }

    const statusBg = (v: number) => {
      if (v >= 100) return 'bg-light-success'
      if (v >= 85)  return 'bg-light-primary'
      if (v >= 70)  return 'bg-light-warning'
      return 'bg-light-danger'
    }

    const statusIconColor = (v: number) => {
      if (v >= 100) return 'text-success'
      if (v >= 85)  return 'text-primary'
      if (v >= 70)  return 'text-warning'
      return 'text-danger'
    }

    const statusTitle = (v: number) => {
      if (v >= 100) return 'Kinerja Luar Biasa!'
      if (v >= 85)  return 'Kinerja Baik'
      if (v >= 70)  return 'Kinerja Cukup'
      return 'Kinerja Perlu Ditingkatkan'
    }

    const statusDesc = (v: number) => {
      if (v >= 100) return 'Pencapaian kamu melebihi target yang ditetapkan. Kamu mendapatkan bonus gaji bulan ini.'
      if (v >= 85)  return 'Pencapaian kamu sudah baik dan mendekati target. Pertahankan kinerja ini.'
      if (v >= 70)  return 'Pencapaian kamu masih di bawah target. Ada sedikit penyesuaian gaji bulan ini.'
      return 'Pencapaian kamu masih jauh dari target. Terdapat penyesuaian gaji bulan ini. Tingkatkan kinerjamu!'
    }

    onMounted(load)

    return {
      kpi, loading, selectedMonth, selectedYear, months, years,
      circumference, dashOffset,
      load, formatRp, scoreColor, progressColor,
      progressStroke, statusLabel, statusBg,
      statusIconColor, statusTitle, statusDesc,
    }
  },
})
</script>