<template>
  <div class="card">
    <div class="card-header border-0 pt-6">
      <div class="card-title flex-column">
        <h2 class="fw-bold mb-1">Manajemen Penggajian</h2>
        <span class="text-muted fs-7">Generate & kelola slip gaji pegawai</span>
      </div>
      <div class="card-toolbar gap-3">
        <select v-model="filterMonth" class="form-select form-select-sm w-120px" @change="load">
          <option v-for="m in months" :key="m.v" :value="m.v">{{ m.l }}</option>
        </select>
        <select v-model="filterYear" class="form-select form-select-sm w-90px" @change="load">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
        <button class="btn btn-primary btn-sm" @click="generateModal = true">
          <KTIcon icon-name="plus" icon-class="fs-4 me-1" />
          Generate Payroll
        </button>
      </div>
    </div>

    <div class="card-body pt-0">
      <!-- Summary cards -->
      <div class="row g-4 mb-6">
        <div class="col-6 col-md-3">
          <div class="bg-light-primary rounded p-4 text-center">
            <div class="fs-2 fw-bold text-primary">
              <span v-if="loading && !payrolls.length" class="placeholder col-4 bg-primary opacity-25 rounded"></span>
              <span v-else>{{ summary?.total_pegawai ?? payrolls.length }}</span>
            </div>
            <div class="text-muted fs-7">Total Pegawai</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="bg-light-success rounded p-4 text-center">
            <div class="fs-5 fw-bold text-success">
              <span v-if="loading && !payrolls.length" class="placeholder col-6 bg-success opacity-25 rounded"></span>
              <span v-else>{{ formatRp(summary?.total_gaji ?? totalGaji) }}</span>
            </div>
            <div class="text-muted fs-7">Total Gaji</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="bg-light-warning rounded p-4 text-center">
            <div class="fs-2 fw-bold text-warning">
              <span v-if="loading && !payrolls.length" class="placeholder col-4 bg-warning opacity-25 rounded"></span>
              <span v-else>{{ summary?.total_draft ?? payrolls.filter(p => p.status === 'draft').length }}</span>
            </div>
            <div class="text-muted fs-7">Draft</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="bg-light-info rounded p-4 text-center">
            <div class="fs-2 fw-bold text-info">
              <span v-if="loading && !payrolls.length" class="placeholder col-4 bg-info opacity-25 rounded"></span>
              <span v-else>{{ summary?.total_paid ?? payrolls.filter(p => p.status === 'paid').length }}</span>
            </div>
            <div class="text-muted fs-7">Lunas</div>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="loadError" class="alert alert-danger d-flex align-items-center mb-4">
        <KTIcon icon-name="information" icon-class="fs-3 text-danger me-2" />
        {{ loadError }}
        <button class="btn btn-sm btn-danger ms-auto" @click="load">Coba Lagi</button>
      </div>

      <div class="table-responsive">
        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
          <thead>
            <tr class="fw-bold text-muted bg-light">
              <th class="ps-4 min-w-200px rounded-start">Pegawai</th>
              <th class="min-w-120px">Gaji Inti</th>
              <th class="min-w-120px">Lembur</th>
              <th class="min-w-120px">Potongan</th>
              <th class="min-w-130px">Total Gaji</th>
              <th class="min-w-100px">Status</th>
              <th class="min-w-120px text-end rounded-end pe-4">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <!-- Skeleton rows saat loading pertama -->
            <template v-if="loading && payrolls.length === 0">
              <tr v-for="n in 5" :key="'skel-' + n">
                <td class="ps-4">
                  <div class="d-flex align-items-center gap-3">
                    <div class="placeholder bg-secondary rounded-circle" style="width:40px;height:40px;opacity:0.15"></div>
                    <div>
                      <div class="placeholder col-8 bg-secondary rounded mb-1" style="height:14px;opacity:0.15;display:block"></div>
                      <div class="placeholder col-5 bg-secondary rounded" style="height:10px;opacity:0.1;display:block"></div>
                    </div>
                  </div>
                </td>
                <td v-for="i in 5" :key="i">
                  <div class="placeholder bg-secondary rounded" style="height:14px;width:80px;opacity:0.12;display:block"></div>
                </td>
                <td>
                  <div class="placeholder bg-secondary rounded" style="height:28px;width:60px;opacity:0.12;display:block"></div>
                </td>
              </tr>
            </template>

            <!-- Data rows -->
            <template v-else>
              <tr v-for="p in payrolls" :key="p.id" :class="{ 'opacity-50': p._loading }">
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-3">
                      <img v-if="p.user_avatar" :src="p.user_avatar" class="symbol-label" />
                      <span v-else class="symbol-label bg-light-primary text-primary fw-bold">
                        {{ (p.user_name ?? '?').charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div>
                      <span class="text-gray-900 fw-bold fs-6">{{ p.user_name ?? '-' }}</span>
                      <span class="text-muted fw-semibold d-block fs-7">{{ p.position ?? '-' }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="fw-semibold text-gray-900">{{ formatRp(p.base_salary ?? 0) }}</span>
                  <span class="text-muted d-block fs-7">+{{ formatRp(p.position_allowance ?? 0) }} tunjangan</span>
                </td>
                <td>
                  <span class="text-success fw-semibold">+{{ formatRp(p.overtime_pay ?? 0) }}</span>
                  <span class="text-muted d-block fs-7">{{ p.total_overtime_minutes ?? 0 }} mnt</span>
                </td>
                <td>
                  <span class="text-danger fw-semibold">-{{ formatRp(p.late_deduction ?? 0) }}</span>
                  <span class="text-muted d-block fs-7">
                    {{ p.total_late_minutes ?? 0 }}mnt telat / {{ p.total_early_leave_minutes ?? 0 }}mnt cepat
                  </span>
                </td>
                <td>
                  <span class="fw-bold text-gray-900 fs-6">{{ formatRp(p.total_salary ?? 0) }}</span>
                  <span class="text-muted d-block fs-7">Hadir {{ p.total_work_days ?? 0 }}x</span>
                </td>
                <td>
                  <span class="badge" :class="statusBadge(p.status)">{{ statusLabel(p.status) }}</span>
                </td>
                <td class="text-end pe-4">
                  <button class="btn btn-sm btn-icon btn-light-info me-1" title="Detail" @click="openDetail(p)" :disabled="p._loading">
                    <KTIcon icon-name="eye" icon-class="fs-5" />
                  </button>
                  <button v-if="p.status === 'draft'" class="btn btn-sm btn-icon btn-light-success me-1" title="Approve" @click="approve(p)" :disabled="p._loading">
                    <span v-if="p._loading" class="spinner-border spinner-border-sm"></span>
                    <KTIcon v-else icon-name="check" icon-class="fs-5" />
                  </button>
                  <button v-if="p.status === 'approved'" class="btn btn-sm btn-icon btn-light-primary me-1" title="Mark Paid" @click="markPaid(p)" :disabled="p._loading">
                    <span v-if="p._loading" class="spinner-border spinner-border-sm"></span>
                    <KTIcon v-else icon-name="dollar" icon-class="fs-5" />
                  </button>
                  <button v-if="p.status !== 'paid'" class="btn btn-sm btn-icon btn-light-danger" title="Hapus" @click="destroy(p)" :disabled="p._loading">
                    <span v-if="p._loading" class="spinner-border spinner-border-sm"></span>
                    <KTIcon v-else icon-name="trash" icon-class="fs-5" />
                  </button>
                </td>
              </tr>
              <tr v-if="payrolls.length === 0 && !loading">
                <td colspan="7" class="text-center text-muted py-10">
                  Belum ada data payroll bulan ini. Klik "Generate Payroll" untuk membuat.
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Subtle refresh indicator -->
      <div v-if="loading && payrolls.length > 0" class="text-center py-2">
        <span class="spinner-border spinner-border-sm text-primary me-2"></span>
        <span class="text-muted fs-7">Memperbarui data...</span>
      </div>
    </div>
  </div>

  <!-- Modal Generate -->
  <div v-if="generateModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Generate Payroll</h5>
          <button type="button" class="btn-close" @click="generateModal = false"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4">
            <div class="col-6">
              <label class="form-label fw-semibold required">Bulan</label>
              <select v-model="genForm.month" class="form-select">
                <option v-for="m in months" :key="m.v" :value="m.v">{{ m.l }}</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold required">Tahun</label>
              <select v-model="genForm.year" class="form-select">
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
            <div class="col-12">
              <div class="notice d-flex bg-light-info rounded p-4">
                <KTIcon icon-name="information" icon-class="fs-2 text-info me-3 mt-1" />
                <div class="fs-7 text-gray-700">
                  Generate akan menghitung gaji semua pegawai berdasarkan data absensi bulan yang dipilih.
                  Data yang sudah ada akan diperbarui.
                </div>
              </div>
            </div>
            <div v-if="genError" class="col-12">
              <div class="alert alert-danger py-2 mb-0">{{ genError }}</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" @click="generateModal = false">Batal</button>
          <button class="btn btn-primary" :disabled="generating" @click="generate">
            <span v-if="generating" class="spinner-border spinner-border-sm me-2"></span>
            {{ generating ? 'Memproses...' : 'Generate' }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Detail -->
  <div v-if="detailModal && selectedPayroll" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold">Slip Gaji — {{ selectedPayroll.user_name ?? '-' }}</h5>
            <span class="text-muted fs-7">{{ monthName(selectedPayroll.month) }} {{ selectedPayroll.year }}</span>
          </div>
          <button type="button" class="btn-close" @click="detailModal = false"></button>
        </div>
        <div class="modal-body">
          <!-- Loading detail -->
          <div v-if="detailLoading" class="d-flex justify-content-center py-6">
            <div class="spinner-border text-primary"></div>
          </div>
          <div v-else class="row g-4">
            <div class="col-12">
              <div class="row g-3">
                <div class="col-3">
                  <div class="bg-light rounded p-3 text-center">
                    <div class="fs-3 fw-bold text-primary">{{ selectedPayroll.total_work_days ?? 0 }}</div>
                    <div class="fs-8 text-muted">Hari Hadir</div>
                  </div>
                </div>
                <div class="col-3">
                  <div class="bg-light-danger rounded p-3 text-center">
                    <div class="fs-3 fw-bold text-danger">{{ selectedPayroll.total_late_minutes ?? 0 }}</div>
                    <div class="fs-8 text-muted">Mnt Telat</div>
                  </div>
                </div>
                <div class="col-3">
                  <div class="bg-light-warning rounded p-3 text-center">
                    <div class="fs-3 fw-bold text-warning">{{ selectedPayroll.total_early_leave_minutes ?? 0 }}</div>
                    <div class="fs-8 text-muted">Mnt Pulang Cepat</div>
                  </div>
                </div>
                <div class="col-3">
                  <div class="bg-light-success rounded p-3 text-center">
                    <div class="fs-3 fw-bold text-success">{{ selectedPayroll.total_overtime_minutes ?? 0 }}</div>
                    <div class="fs-8 text-muted">Mnt Lembur</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="fw-bold text-gray-700 mb-3">Rincian Gaji</div>

              <!-- Rincian otomatis dari field payroll -->
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-gray-700">
                  <span class="badge badge-light-success me-2">+</span>Gaji Pokok
                </span>
                <span class="fw-semibold text-success">+{{ formatRp(selectedPayroll.base_salary ?? 0) }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-gray-700">
                  <span class="badge badge-light-success me-2">+</span>Tunjangan Jabatan
                </span>
                <span class="fw-semibold text-success">+{{ formatRp(selectedPayroll.position_allowance ?? 0) }}</span>
              </div>
              <div v-if="(selectedPayroll.overtime_pay ?? 0) > 0" class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-gray-700">
                  <span class="badge badge-light-success me-2">+</span>
                  Lembur ({{ selectedPayroll.total_overtime_minutes ?? 0 }} mnt)
                </span>
                <span class="fw-semibold text-success">+{{ formatRp(selectedPayroll.overtime_pay ?? 0) }}</span>
              </div>
              <div v-if="(selectedPayroll.late_deduction ?? 0) > 0" class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-gray-700">
                  <span class="badge badge-light-danger me-2">-</span>
                  Potongan Telat ({{ selectedPayroll.total_late_minutes ?? 0 }} mnt)
                </span>
                <span class="fw-semibold text-danger">-{{ formatRp(selectedPayroll.late_deduction ?? 0) }}</span>
              </div>
              <div v-if="(selectedPayroll.early_leave_deduction ?? 0) > 0" class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-gray-700">
                  <span class="badge badge-light-danger me-2">-</span>
                  Potongan Pulang Cepat ({{ selectedPayroll.total_early_leave_minutes ?? 0 }} mnt)
                </span>
                <span class="fw-semibold text-danger">-{{ formatRp(selectedPayroll.early_leave_deduction ?? 0) }}</span>
              </div>

              <div class="d-flex justify-content-between align-items-center pt-3">
                <span class="fw-bold fs-5">Total Gaji Bersih</span>
                <span class="fw-bold fs-4 text-primary">{{ formatRp(selectedPayroll.total_salary ?? 0) }}</span>
              </div>
            </div>

            <div class="col-12" v-if="selectedPayroll.status === 'draft'">
              <div class="separator my-2"></div>
              <label class="form-label fw-semibold">Catatan</label>
              <textarea v-model="noteInput" class="form-control" rows="2" placeholder="Catatan (opsional)"></textarea>
            </div>

            <div v-if="selectedPayroll.note && selectedPayroll.status !== 'draft'" class="col-12">
              <div class="separator my-2"></div>
              <div class="text-muted fs-7 fw-semibold mb-1">Catatan:</div>
              <div class="text-gray-700">{{ selectedPayroll.note }}</div>
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <span class="badge fs-6 px-4 py-2" :class="statusBadge(selectedPayroll.status)">
            {{ statusLabel(selectedPayroll.status) }}
          </span>
          <div class="d-flex gap-2">
            <button class="btn btn-light" @click="detailModal = false">Tutup</button>
            <button v-if="selectedPayroll.status === 'draft'" class="btn btn-info" :disabled="saving" @click="saveNote">
              <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
              Simpan Catatan
            </button>
            <button v-if="selectedPayroll.status === 'draft'" class="btn btn-success" @click="approve(selectedPayroll); detailModal = false">
              Approve
            </button>
            <button v-if="selectedPayroll.status === 'approved'" class="btn btn-primary" @click="markPaid(selectedPayroll); detailModal = false">
              Mark Paid
            </button>
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
  { v:1, l:'Januari'   }, { v:2,  l:'Februari' }, { v:3,  l:'Maret'    }, { v:4,  l:'April'    },
  { v:5, l:'Mei'       }, { v:6,  l:'Juni'      }, { v:7,  l:'Juli'     }, { v:8,  l:'Agustus'  },
  { v:9, l:'September' }, { v:10, l:'Oktober'   }, { v:11, l:'November' }, { v:12, l:'Desember' },
]

export default defineComponent({
  name: 'PayrollManagement',
  setup() {
    const now             = new Date()
    const payrolls        = ref<any[]>([])
    const summary         = ref<any>(null)
    const loading         = ref(false)
    const generating      = ref(false)
    const saving          = ref(false)
    const detailLoading   = ref(false)
    const loadError       = ref('')
    const genError        = ref('')
    const filterMonth     = ref(now.getMonth() + 1)
    const filterYear      = ref(now.getFullYear())
    const generateModal   = ref(false)
    const detailModal     = ref(false)
    const selectedPayroll = ref<any>(null)
    const noteInput       = ref('')
    const genForm         = ref({ month: now.getMonth() + 1, year: now.getFullYear() })
    const months          = MONTHS
    const years           = Array.from({ length: 3 }, (_, i) => now.getFullYear() - i)

    // ── Computed summary dari data lokal jika API tidak return summary ──
    const totalGaji = computed(() => payrolls.value.reduce((s, p) => s + (p.total_salary ?? 0), 0))

    // ── Load: tampilkan data lama dulu, baru replace setelah selesai ──
    const load = async () => {
      loading.value   = true
      loadError.value = ''
      try {
        const res = await axios.get(`${API}/admin/payroll`, {
          headers: headers(),
          params: { month: filterMonth.value, year: filterYear.value },
        })
        payrolls.value = Array.isArray(res.data?.data) ? res.data.data : []
        summary.value  = res.data?.summary ?? null
      } catch (e: any) {
        loadError.value = e?.response?.data?.message ?? 'Gagal memuat data payroll.'
      } finally {
        loading.value = false
      }
    }

    const generate = async () => {
      generating.value = true
      genError.value   = ''
      try {
        await axios.post(`${API}/admin/payroll/generate`, genForm.value, { headers: headers() })
        generateModal.value = false
        filterMonth.value   = genForm.value.month
        filterYear.value    = genForm.value.year
        await load()
      } catch (e: any) {
        genError.value = e?.response?.data?.message ?? 'Generate gagal.'
      } finally {
        generating.value = false
      }
    }

    // ── openDetail: tampilkan data row dulu, fetch detail di background ──
    const openDetail = async (p: any) => {
      selectedPayroll.value = p      // ✅ langsung tampil tanpa tunggu
      noteInput.value       = p.note ?? ''
      detailModal.value     = true
      detailLoading.value   = true

      try {
        const res = await axios.get(`${API}/admin/payroll/${p.id}`, { headers: headers() })
        selectedPayroll.value = { ...res.data?.data, _loading: false }
        noteInput.value       = selectedPayroll.value.note ?? ''
      } catch {
        // biarkan data row yang sudah ditampilkan
      } finally {
        detailLoading.value = false
      }
    }

    const saveNote = async () => {
      saving.value = true
      try {
        await axios.put(`${API}/admin/payroll/${selectedPayroll.value.id}`, {
          note: noteInput.value,
        }, { headers: headers() })
        // Update lokal tanpa reload penuh
        const idx = payrolls.value.findIndex(p => p.id === selectedPayroll.value.id)
        if (idx !== -1) payrolls.value[idx].note = noteInput.value
        selectedPayroll.value.note = noteInput.value
      } finally {
        saving.value = false
      }
    }

    // ── Optimistic update: ubah status di UI dulu, baru hit API ──
    const approve = async (p: any) => {
      const oldStatus = p.status
      p.status   = 'approved'   // optimistic
      p._loading = true
      try {
        await axios.post(`${API}/admin/payroll/${p.id}/approve`, {}, { headers: headers() })
        // Update summary lokal
        if (summary.value) {
          summary.value.total_draft    = Math.max(0, (summary.value.total_draft ?? 1) - 1)
          summary.value.total_approved = (summary.value.total_approved ?? 0) + 1
        }
      } catch (e: any) {
        p.status = oldStatus    // rollback jika gagal
        alert(e?.response?.data?.message ?? 'Gagal approve.')
      } finally {
        p._loading = false
      }
    }

    const markPaid = async (p: any) => {
      const oldStatus = p.status
      p.status   = 'paid'       // optimistic
      p._loading = true
      try {
        await axios.post(`${API}/admin/payroll/${p.id}/mark-paid`, {}, { headers: headers() })
        if (summary.value) {
          summary.value.total_approved = Math.max(0, (summary.value.total_approved ?? 1) - 1)
          summary.value.total_paid     = (summary.value.total_paid ?? 0) + 1
        }
      } catch (e: any) {
        p.status = oldStatus
        alert(e?.response?.data?.message ?? 'Gagal mark paid.')
      } finally {
        p._loading = false
      }
    }

    const destroy = async (p: any) => {
      if (!confirm('Hapus payroll ini?')) return
      p._loading = true
      try {
        await axios.delete(`${API}/admin/payroll/${p.id}`, { headers: headers() })
        // Hapus dari array lokal tanpa reload
        payrolls.value = payrolls.value.filter(x => x.id !== p.id)
        if (summary.value) {
          summary.value.total_pegawai = Math.max(0, (summary.value.total_pegawai ?? 1) - 1)
          summary.value.total_gaji    = Math.max(0, (summary.value.total_gaji ?? 0) - (p.total_salary ?? 0))
          if (p.status === 'draft') summary.value.total_draft = Math.max(0, (summary.value.total_draft ?? 1) - 1)
        }
      } catch (e: any) {
        p._loading = false
        alert(e?.response?.data?.message ?? 'Gagal menghapus.')
      }
    }

    const formatRp    = (v: number) => 'Rp ' + (Number(v) || 0).toLocaleString('id-ID')
    const monthName   = (m: number) => MONTHS.find(x => x.v === m)?.l ?? String(m)
    const statusLabel = (s: string) => ({ draft: 'Draft', approved: 'Approved', paid: 'Lunas' }[s] ?? s)
    const statusBadge = (s: string) => ({ draft: 'badge-light-warning', approved: 'badge-light-info', paid: 'badge-light-success' }[s] ?? '')

    onMounted(load)

    return {
      payrolls, summary, loading, generating, saving, detailLoading,
      loadError, genError, totalGaji,
      filterMonth, filterYear, generateModal, detailModal,
      selectedPayroll, noteInput, genForm,
      months, years,
      load, generate, openDetail, saveNote, approve, markPaid, destroy,
      formatRp, monthName, statusLabel, statusBadge,
    }
  },
})
</script>