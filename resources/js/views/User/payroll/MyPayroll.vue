<template>
  <div class="row g-5">
    <!-- Slip Gaji Bulan Ini -->
    <div class="col-12">
      <div class="card">
        <div class="card-header border-0 pt-6">
          <div class="card-title flex-column">
            <h2 class="fw-bold mb-1">Slip Gaji Saya</h2>
            <span class="text-muted fs-7">Ringkasan penggajian bulan ini</span>
          </div>
          <div class="card-toolbar gap-3">
            <select v-model="filterMonth" class="form-select form-select-sm w-120px" @change="load">
              <option v-for="m in months" :key="m.v" :value="m.v">{{ m.l }}</option>
            </select>
            <select v-model="filterYear" class="form-select form-select-sm w-90px" @change="load">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
        </div>

        <div class="card-body pt-0">
          <div v-if="loading" class="d-flex justify-content-center py-10">
            <div class="spinner-border text-primary"></div>
          </div>

          <!-- Belum ada -->
          <div v-else-if="!current" class="text-center py-10">
            <KTIcon icon-name="document" icon-class="fs-3x text-muted mb-3" />
            <div class="text-muted fs-6">Slip gaji belum tersedia untuk bulan ini.</div>
            <div class="text-muted fs-7 mt-1">Hubungi admin jika sudah melewati tanggal penggajian.</div>
          </div>

          <template v-else>
            <!-- Status badge -->
            <div class="d-flex align-items-center mb-6 gap-3">
              <span class="badge fs-6 px-4 py-2" :class="statusBadge(current.status)">
                {{ statusLabel(current.status) }}
              </span>
              <span class="text-muted fs-7">
                {{ monthName(current.month) }} {{ current.year }}
              </span>
            </div>

            <!-- Summary kehadiran -->
            <div class="row g-3 mb-6">
              <div class="col-6 col-md-3">
                <div class="bg-light rounded p-4 text-center">
                  <div class="fs-2 fw-bold text-primary">{{ current.total_hadir }}</div>
                  <div class="text-muted fs-7">Hari Hadir</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="bg-light-danger rounded p-4 text-center">
                  <div class="fs-2 fw-bold text-danger">{{ current.total_late_minutes }}'</div>
                  <div class="text-muted fs-7">Menit Telat</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="bg-light-warning rounded p-4 text-center">
                  <div class="fs-2 fw-bold text-warning">{{ current.total_early_leave_minutes }}'</div>
                  <div class="text-muted fs-7">Mnt Pulang Cepat</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="bg-light-success rounded p-4 text-center">
                  <div class="fs-2 fw-bold text-success">{{ current.total_overtime_minutes }}'</div>
                  <div class="text-muted fs-7">Menit Lembur</div>
                </div>
              </div>
            </div>

            <!-- Rincian gaji -->
            <div class="separator mb-4"></div>
            <div class="fw-bold text-gray-700 mb-4 fs-5">Rincian Komponen Gaji</div>

            <div v-for="d in current.details" :key="d.description"
              class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <div class="d-flex align-items-center gap-3">
                <span class="bullet bullet-dot w-8px h-8px"
                  :class="d.type === 'earning' ? 'bg-success' : 'bg-danger'"></span>
                <span class="text-gray-700 fw-semibold">{{ d.description }}</span>
              </div>
              <span class="fw-bold fs-6" :class="d.type === 'earning' ? 'text-success' : 'text-danger'">
                {{ d.type === 'earning' ? '+' : '-' }}{{ formatRp(d.amount) }}
              </span>
            </div>

            <!-- Total -->
            <div class="d-flex justify-content-between align-items-center mt-4 p-4 bg-light-primary rounded">
              <span class="fw-bold fs-5 text-gray-800">Total Gaji Bersih</span>
              <span class="fw-bold fs-3 text-primary">{{ formatRp(current.total_salary) }}</span>
            </div>

            <div v-if="current.note" class="mt-4 p-4 bg-light rounded">
              <span class="fw-semibold text-muted fs-7">Catatan: </span>
              <span class="text-gray-700 fs-7">{{ current.note }}</span>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Riwayat Slip Gaji -->
    <div class="col-12">
      <div class="card">
        <div class="card-header border-0 pt-6">
          <div class="card-title">
            <h3 class="fw-bold">Riwayat Slip Gaji</h3>
          </div>
        </div>
        <div class="card-body pt-0">
          <div v-if="!history || history.length === 0" class="text-center text-muted py-6">
            Belum ada riwayat slip gaji.
          </div>
          <div v-else class="table-responsive">
            <table class="table table-row-dashed table-row-gray-300 align-middle">
              <thead>
                <tr class="fw-bold text-muted bg-light">
                  <th class="ps-4 rounded-start">Periode</th>
                  <th>Hadir</th>
                  <th>Potongan</th>
                  <th>Lembur</th>
                  <th>Total Gaji</th>
                  <th class="rounded-end">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in history" :key="p.id" class="cursor-pointer" @click="openDetail(p)">
                  <td class="ps-4">
                    <span class="fw-bold text-gray-900">{{ monthName(p.month) }} {{ p.year }}</span>
                  </td>
                  <td>
                    <span class="text-gray-700">{{ p.total_hadir }} hari</span>
                  </td>
                  <td>
                    <span class="text-danger fw-semibold">-{{ formatRp(p.late_deduction) }}</span>
                  </td>
                  <td>
                    <span class="text-success fw-semibold">+{{ formatRp(p.overtime_pay) }}</span>
                  </td>
                  <td>
                    <span class="fw-bold text-gray-900">{{ formatRp(p.total_salary) }}</span>
                  </td>
                  <td>
                    <span class="badge" :class="statusBadge(p.status)">{{ statusLabel(p.status) }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Detail Riwayat -->
  <div v-if="detailModal && selectedPayroll" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold">Slip Gaji</h5>
            <span class="text-muted fs-7">{{ monthName(selectedPayroll.month) }} {{ selectedPayroll.year }}</span>
          </div>
          <button type="button" class="btn-close" @click="detailModal = false"></button>
        </div>
        <div class="modal-body">
          <div v-for="d in selectedPayroll.details" :key="d.description"
            class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <span class="text-gray-700">{{ d.description }}</span>
            <span class="fw-semibold" :class="d.type === 'earning' ? 'text-success' : 'text-danger'">
              {{ d.type === 'earning' ? '+' : '-' }}{{ formatRp(d.amount) }}
            </span>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-4 p-3 bg-light-primary rounded">
            <span class="fw-bold">Total Bersih</span>
            <span class="fw-bold text-primary fs-5">{{ formatRp(selectedPayroll.total_salary) }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" @click="detailModal = false">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const API     = (import.meta.env.VITE_APP_API_URL || '/api').replace(/\/$/, '')
const headers = () => ({ Authorization: `Bearer ${localStorage.getItem('api_token')}` })

const MONTHS = [
  {v:1,l:'Januari'},{v:2,l:'Februari'},{v:3,l:'Maret'},{v:4,l:'April'},
  {v:5,l:'Mei'},{v:6,l:'Juni'},{v:7,l:'Juli'},{v:8,l:'Agustus'},
  {v:9,l:'September'},{v:10,l:'Oktober'},{v:11,l:'November'},{v:12,l:'Desember'},
]

export default defineComponent({
  name: 'MyPayroll',
  setup() {
    const now         = new Date()
    const current     = ref<any>(null)
    const history     = ref<any[]>([])
    const loading     = ref(false)
    const filterMonth = ref(now.getMonth() + 1)
    const filterYear  = ref(now.getFullYear())
    const detailModal = ref(false)
    const selectedPayroll = ref<any>(null)
    const months      = MONTHS
    const years       = Array.from({ length: 3 }, (_, i) => now.getFullYear() - i)

    // Flag untuk cegah update state setelah komponen unmount
    let isMounted = true

    const load = async () => {
      loading.value = true
      try {
        const res = await axios.get(`${API}/payroll/my`, {
          headers: headers(),
          params: { month: filterMonth.value, year: filterYear.value },
        })
        // Hanya update state jika komponen masih aktif
        if (!isMounted) return
        current.value = res.data.current ?? null
        history.value = Array.isArray(res.data.history) ? res.data.history : []
      } catch (e) {
        if (!isMounted) return
        current.value = null
        history.value = []
      } finally {
        if (isMounted) loading.value = false
      }
    }

    const openDetail = async (p: any) => {
      try {
        const res = await axios.get(`${API}/payroll/my/${p.id}`, { headers: headers() })
        if (!isMounted) return
        selectedPayroll.value = res.data.data
        detailModal.value     = true
      } catch (e) {
        console.error('Gagal load detail payroll:', e)
      }
    }

    const formatRp    = (v: number) => 'Rp ' + (Number(v) || 0).toLocaleString('id-ID')
    const monthName   = (m: number) => MONTHS.find(x => x.v === m)?.l ?? m
    const statusLabel = (s: string) => ({ draft: 'Draft', approved: 'Disetujui', paid: 'Lunas' }[s] ?? s)
    const statusBadge = (s: string) => ({ draft: 'badge-light-warning', approved: 'badge-light-info', paid: 'badge-light-success' }[s] ?? '')

    onMounted(load)

    // Reset flag saat komponen unmount agar request yang masih berjalan tidak update state
    onUnmounted(() => { isMounted = false })

    return {
      current, history, loading, filterMonth, filterYear,
      detailModal, selectedPayroll, months, years,
      load, openDetail, formatRp, monthName, statusLabel, statusBadge,
    }
  },
})
</script>