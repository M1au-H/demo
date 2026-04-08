<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-6">
      <div>
        <h2 class="fw-bold mb-1">Manajemen KPI</h2>
        <span class="text-muted fs-7">Kelola & hitung KPI karyawan per bulan</span>
      </div>
      <div class="d-flex gap-3 align-items-center">
        <select v-model="filterMonth" class="form-select form-select-sm w-120px" @change="load">
          <option v-for="m in months" :key="m.v" :value="m.v">{{ m.l }}</option>
        </select>
        <select v-model="filterYear" class="form-select form-select-sm w-90px" @change="load">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
        <button class="btn btn-primary btn-sm" :disabled="calculating" @click="calculateAll">
          <span v-if="calculating" class="spinner-border spinner-border-sm me-2"></span>
          <KTIcon v-else icon-name="calculator" icon-class="fs-5 me-1" />
          Hitung KPI
        </button>
      </div>
    </div>

    <!-- Summary -->
    <div class="row g-4 mb-6">
      <div class="col-6 col-md-3">
        <div class="card card-flush h-100">
          <div class="card-body d-flex align-items-center gap-4 py-5">
            <div class="w-45px h-45px rounded-circle bg-light-primary d-flex align-items-center justify-content-center flex-shrink-0">
              <KTIcon icon-name="people" icon-class="fs-3 text-primary" />
            </div>
            <div>
              <div class="fs-2 fw-bold text-gray-800">{{ kpis.length }}</div>
              <div class="text-muted fs-7">Total Karyawan</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card card-flush h-100">
          <div class="card-body d-flex align-items-center gap-4 py-5">
            <div class="w-45px h-45px rounded-circle bg-light-success d-flex align-items-center justify-content-center flex-shrink-0">
              <KTIcon icon-name="arrow-up" icon-class="fs-3 text-success" />
            </div>
            <div>
              <div class="fs-2 fw-bold text-gray-800">{{ aboveTarget }}</div>
              <div class="text-muted fs-7">Di Atas Target</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card card-flush h-100">
          <div class="card-body d-flex align-items-center gap-4 py-5">
            <div class="w-45px h-45px rounded-circle bg-light-warning d-flex align-items-center justify-content-center flex-shrink-0">
              <KTIcon icon-name="minus" icon-class="fs-3 text-warning" />
            </div>
            <div>
              <div class="fs-2 fw-bold text-gray-800">{{ onTarget }}</div>
              <div class="text-muted fs-7">Mendekati Target</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card card-flush h-100">
          <div class="card-body d-flex align-items-center gap-4 py-5">
            <div class="w-45px h-45px rounded-circle bg-light-danger d-flex align-items-center justify-content-center flex-shrink-0">
              <KTIcon icon-name="arrow-down" icon-class="fs-3 text-danger" />
            </div>
            <div>
              <div class="fs-2 fw-bold text-gray-800">{{ belowTarget }}</div>
              <div class="text-muted fs-7">Di Bawah Target</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error -->
    <div v-if="loadError" class="alert alert-danger d-flex align-items-center mb-4">
      <KTIcon icon-name="information" icon-class="fs-3 text-danger me-2" />
      {{ loadError }}
      <button class="btn btn-sm btn-danger ms-auto" @click="load">Coba Lagi</button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-content-center py-10">
      <div class="spinner-border text-primary"></div>
    </div>

    <!-- Cards -->
    <div v-else class="row g-4">
      <div v-if="kpis.length === 0" class="col-12">
        <div class="card card-flush">
          <div class="card-body text-center py-15 text-muted">
            <KTIcon icon-name="chart-line-up" icon-class="fs-3x mb-4 text-muted" />
            <div class="fs-6">Belum ada data KPI bulan ini.<br/>Klik "Hitung KPI" untuk memulai.</div>
          </div>
        </div>
      </div>

      <div v-for="k in kpis" :key="k.id" class="col-12 col-md-6 col-xl-3">
        <div class="card card-flush h-100 border border-dashed border-gray-200">
          <div class="card-body p-5">

            <!-- Avatar & Nama -->
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="symbol symbol-40px">
                <span class="symbol-label fw-bold text-primary bg-light-primary">
                  {{ (k.name ?? '?').charAt(0).toUpperCase() }}
                </span>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="fw-bold text-gray-800 fs-6 text-truncate">{{ k.name }}</div>
                <div class="text-muted fs-8">{{ k.position ?? '-' }}</div>
              </div>
            </div>

            <div class="separator mb-4"></div>

            <!-- Achievement — hanya angka -->
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="text-muted fs-7">Achievement</span>
              <span class="fw-bold fs-6" :class="scoreColor(k.achievement)">
                {{ k.achievement }}%
                <span class="text-muted fw-normal fs-8">/ {{ k.kpi_target }}%</span>
              </span>
            </div>

            <!-- Kehadiran -->
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="text-muted fs-7">Kehadiran</span>
              <span class="fw-semibold fs-7" :class="scoreColor(k.attendance_score)">
                {{ k.attendance_score }}%
              </span>
            </div>

            <!-- Tugas input -->
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="text-muted fs-7">Tugas (%)</span>
              <input
                v-model.number="k.task_score"
                type="number" min="0" max="100"
                class="form-control form-control-sm text-center p-1"
                style="width:65px; font-size:12px"
                placeholder="0"
              />
            </div>

            <!-- Rating Performa -->
            <div class="d-flex justify-content-between align-items-center mb-4">
              <span class="text-muted fs-7">Rating Performa</span>
              <span v-if="k.admin_rating !== null && k.admin_rating !== undefined"
                class="fw-semibold fs-7" :class="scoreColor(k.admin_rating)">
                {{ k.admin_rating }}%
              </span>
              <span v-else class="text-muted fs-8 fst-italic">Belum dinilai</span>
            </div>

            <!-- Aksi -->
            <div class="d-flex gap-2">
              <button
                class="btn btn-sm btn-light-primary flex-grow-1"
                @click="openDetail(k)"
              >
                <KTIcon icon-name="eye" icon-class="fs-6 me-1" />
                Detail
              </button>
              <button
                class="btn btn-sm btn-primary flex-grow-1"
                :disabled="savingId === k.user_id"
                @click="saveRating(k)"
              >
                <span v-if="savingId === k.user_id" class="spinner-border spinner-border-sm me-1"></span>
                <KTIcon v-else icon-name="check" icon-class="fs-6 me-1" />
                Simpan
              </button>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Detail KPI -->
  <div v-if="detailModal && selectedKpi"
    class="modal fade show d-block" tabindex="-1"
    style="background:rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold">Detail KPI — {{ selectedKpi.name }}</h5>
            <span class="text-muted fs-7">{{ monthName(filterMonth) }} {{ filterYear }}</span>
          </div>
          <button type="button" class="btn-close" @click="detailModal = false"></button>
        </div>
        <div class="modal-body">

          <!-- Achievement -->
          <div class="bg-light rounded p-4 mb-4 text-center">
            <div class="fs-1 fw-bold mb-1" :class="scoreColor(selectedKpi.achievement)">
              {{ selectedKpi.achievement }}%
            </div>
            <div class="text-muted fs-7">Achievement dari target {{ selectedKpi.kpi_target }}%</div>
          </div>

          <!-- Rincian -->
          <div class="mb-4">
            <div class="fw-bold text-gray-700 mb-3">Rincian Komponen</div>

            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <span class="text-gray-600">Kehadiran</span>
              <span class="fw-bold" :class="scoreColor(selectedKpi.attendance_score)">
                {{ selectedKpi.attendance_score }}%
              </span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <span class="text-gray-600">Tugas</span>
              <span class="fw-bold" :class="scoreColor(selectedKpi.task_score)">
                {{ selectedKpi.task_score }}%
              </span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <span class="text-gray-600">Rating Performa</span>
              <span v-if="selectedKpi.admin_rating !== null && selectedKpi.admin_rating !== undefined"
                class="fw-bold" :class="scoreColor(selectedKpi.admin_rating)">
                {{ selectedKpi.admin_rating }}%
              </span>
              <span v-else class="text-muted fst-italic">Belum dinilai</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <span class="text-gray-600 fw-semibold">KPI Final</span>
              <span class="fw-bold fs-5" :class="scoreColor(selectedKpi.final_kpi)">
                {{ selectedKpi.final_kpi }}%
              </span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-3">
              <span class="text-gray-600 fw-semibold">Penyesuaian Gaji</span>
              <span class="fw-bold fs-6"
                :class="selectedKpi.kpi_adjustment >= 0 ? 'text-success' : 'text-danger'">
                {{ selectedKpi.kpi_adjustment >= 0 ? '+' : '' }}{{ formatRp(selectedKpi.kpi_adjustment) }}
              </span>
            </div>
          </div>

          <!-- Catatan -->
          <div>
            <label class="form-label fw-semibold fs-7">Catatan (opsional)</label>
            <textarea
              v-model="noteInput"
              class="form-control form-control-sm"
              rows="2"
              placeholder="Tambahkan catatan untuk karyawan..."
            ></textarea>
          </div>

        </div>
        <div class="modal-footer">
          <button class="btn btn-light btn-sm" @click="detailModal = false">Tutup</button>
          <button
            class="btn btn-primary btn-sm"
            :disabled="savingId === selectedKpi.user_id"
            @click="saveFromModal"
          >
            <span v-if="savingId === selectedKpi.user_id"
              class="spinner-border spinner-border-sm me-1"></span>
            Simpan Catatan
          </button>
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

export default defineComponent({
  name: 'KpiManagement',
  setup() {
    const now          = new Date()
    const kpis         = ref<any[]>([])
    const loading      = ref(false)
    const calculating  = ref(false)
    const savingId     = ref<number | null>(null)
    const loadError    = ref('')
    const filterMonth  = ref(now.getMonth() + 1)
    const filterYear   = ref(now.getFullYear())
    const months       = MONTHS
    const years        = Array.from({ length: 3 }, (_, i) => now.getFullYear() - i)
    const detailModal  = ref(false)
    const selectedKpi  = ref<any>(null)
    const noteInput    = ref('')

    const aboveTarget = computed(() => kpis.value.filter(k => k.achievement >= 100).length)
    const onTarget    = computed(() => kpis.value.filter(k => k.achievement >= 70 && k.achievement < 100).length)
    const belowTarget = computed(() => kpis.value.filter(k => k.achievement < 70).length)

    const load = async () => {
      loading.value   = true
      loadError.value = ''
      try {
        const res = await axios.get(`${API}/admin/kpi`, {
          headers: headers(),
          params: { month: filterMonth.value, year: filterYear.value },
        })
        kpis.value = Array.isArray(res.data?.data) ? res.data.data : []
      } catch (e: any) {
        loadError.value = e?.response?.data?.message ?? 'Gagal memuat data KPI.'
        kpis.value = []
      } finally {
        loading.value = false
      }
    }

    const calculateAll = async () => {
      calculating.value = true
      try {
        await axios.post(`${API}/admin/kpi/calculate-all`, {
          month: filterMonth.value,
          year:  filterYear.value,
        }, { headers: headers() })
        await load()
      } catch (e: any) {
        alert(e?.response?.data?.message ?? 'Gagal menghitung KPI.')
      } finally {
        calculating.value = false
      }
    }

    const saveRating = async (k: any) => {
      savingId.value = k.user_id
      try {
        await axios.put(`${API}/admin/kpi/${k.user_id}/rating`, {
          month:        filterMonth.value,
          year:         filterYear.value,
          task_score:   k.task_score,
          admin_rating: k.admin_rating,
        }, { headers: headers() })
        await load()
      } catch (e: any) {
        alert(e?.response?.data?.message ?? 'Gagal menyimpan.')
      } finally {
        savingId.value = null
      }
    }

    const openDetail = (k: any) => {
      selectedKpi.value = { ...k }
      noteInput.value   = k.notes ?? ''
      detailModal.value = true
    }

    const saveFromModal = async () => {
      if (!selectedKpi.value) return
      savingId.value = selectedKpi.value.user_id
      try {
        await axios.put(`${API}/admin/kpi/${selectedKpi.value.user_id}/rating`, {
          month:        filterMonth.value,
          year:         filterYear.value,
          task_score:   selectedKpi.value.task_score,
          admin_rating: selectedKpi.value.admin_rating,
          notes:        noteInput.value,
        }, { headers: headers() })
        detailModal.value = false
        await load()
      } catch (e: any) {
        alert(e?.response?.data?.message ?? 'Gagal menyimpan.')
      } finally {
        savingId.value = null
      }
    }

    const formatRp = (v: number) =>
      'Rp ' + (Number(v) || 0).toLocaleString('id-ID')

    const monthName = (m: number) =>
      MONTHS.find(x => x.v === m)?.l ?? String(m)

    const scoreColor = (v: number) => {
      if (v >= 90) return 'text-success'
      if (v >= 70) return 'text-primary'
      if (v >= 50) return 'text-warning'
      return 'text-danger'
    }

    const progressColor = (v: number) => {
      if (v >= 100) return 'bg-success'
      if (v >= 85)  return 'bg-primary'
      if (v >= 70)  return 'bg-warning'
      return 'bg-danger'
    }

    onMounted(load)

    return {
      kpis, loading, calculating, savingId, loadError,
      filterMonth, filterYear, months, years,
      aboveTarget, onTarget, belowTarget,
      detailModal, selectedKpi, noteInput,
      load, calculateAll, saveRating, openDetail, saveFromModal,
      formatRp, monthName, scoreColor, progressColor,
    }
  },
})
</script>