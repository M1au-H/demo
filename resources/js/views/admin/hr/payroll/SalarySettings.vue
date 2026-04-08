<template>
  <div class="card">
    <div class="card-header border-0 pt-6">
      <div class="card-title">
        <h2 class="fw-bold">Pengaturan Gaji Pegawai</h2>
      </div>
    </div>

    <div class="card-body pt-0">
      <!-- Error -->
      <div v-if="loadError" class="alert alert-danger d-flex align-items-center mb-4">
        <KTIcon icon-name="information" icon-class="fs-3 text-danger me-2" />
        {{ loadError }}
        <button class="btn btn-sm btn-danger ms-auto" @click="load">Coba Lagi</button>
      </div>

      <div v-if="loading" class="d-flex justify-content-center py-10">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <template v-else>
        <div class="d-flex align-items-center mb-5">
          <div class="position-relative w-300px">
            <KTIcon icon-name="magnifier" icon-class="fs-3 text-gray-500 position-absolute top-50 translate-middle-y ms-4" />
            <input
              v-model="search"
              type="text"
              class="form-control form-control-solid ps-12"
              placeholder="Cari pegawai..."
            />
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
            <thead>
              <tr class="fw-bold text-muted bg-light">
                <th class="ps-4 min-w-200px rounded-start">Pegawai</th>
                <th class="min-w-150px">Gaji Pokok</th>
                <th class="min-w-150px">Tunjangan Jabatan</th>
                <th class="min-w-150px">Tarif Lembur/mnt</th>
                <th class="min-w-150px">Tarif Potongan/mnt</th>
                <th class="min-w-100px text-end rounded-end pe-4">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="emp in filteredEmployees" :key="emp.id">
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-3">
                      <span class="symbol-label bg-light-primary text-primary fw-bold fs-6">
                        {{ (emp.name ?? '?').charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div>
                      <span class="text-gray-900 fw-bold fs-6">{{ emp.name ?? '-' }}</span>
                      <span class="text-muted fw-semibold d-block fs-7">{{ emp.position ?? emp.job_title ?? '-' }}</span>
                    </div>
                  </div>
                </td>
                <td><span class="text-gray-900 fw-semibold">{{ formatRp(emp.salary?.base_salary ?? 0) }}</span></td>
                <td><span class="text-gray-900 fw-semibold">{{ formatRp(emp.salary?.position_allowance ?? 0) }}</span></td>
                <td><span class="badge badge-light-success">{{ formatRp(emp.salary?.overtime_rate ?? 0) }}/mnt</span></td>
                <td><span class="badge badge-light-danger">{{ formatRp(emp.salary?.late_rate ?? 0) }}/mnt</span></td>
                <td class="text-end pe-4">
                  <button class="btn btn-sm btn-light-primary" @click="openEdit(emp)">
                    <KTIcon icon-name="pencil" icon-class="fs-5 me-1" />
                    Edit
                  </button>
                </td>
              </tr>
              <tr v-if="filteredEmployees.length === 0">
                <td colspan="6" class="text-center text-muted py-8">Tidak ada data pegawai.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>
  </div>

  <!-- Modal Edit -->
  <div v-if="editModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit Gaji — {{ editData.name ?? '-' }}</h5>
          <button type="button" class="btn-close" @click="editModal = false"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4">
            <div class="col-12">
              <label class="form-label fw-semibold required">Gaji Pokok</label>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input v-model.number="editForm.base_salary" type="number" min="0" class="form-control" placeholder="0" />
              </div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold required">Tunjangan Jabatan</label>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input v-model.number="editForm.position_allowance" type="number" min="0" class="form-control" placeholder="0" />
              </div>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold required">Tarif Lembur</label>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input v-model.number="editForm.overtime_rate" type="number" min="0" class="form-control" placeholder="0" />
                <span class="input-group-text">/mnt</span>
              </div>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold required">Tarif Potongan</label>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input v-model.number="editForm.late_rate" type="number" min="0" class="form-control" placeholder="0" />
                <span class="input-group-text">/mnt</span>
              </div>
              <div class="text-muted fs-7 mt-1">Berlaku untuk telat & pulang cepat</div>
            </div>

            <!-- Preview -->
            <div class="col-12">
              <div class="bg-light rounded p-4">
                <div class="fw-bold text-gray-700 mb-3">Preview Komponen Gaji</div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Gaji Pokok</span>
                  <span class="fw-semibold">{{ formatRp(editForm.base_salary) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Tunjangan Jabatan</span>
                  <span class="fw-semibold">{{ formatRp(editForm.position_allowance) }}</span>
                </div>
                <div class="separator my-3"></div>
                <div class="d-flex justify-content-between">
                  <span class="fw-bold">Total Gaji Dasar</span>
                  <span class="fw-bold text-primary">{{ formatRp(editForm.base_salary + editForm.position_allowance) }}</span>
                </div>
              </div>
            </div>

            <div v-if="saveError" class="col-12">
              <div class="alert alert-danger py-2 mb-0">{{ saveError }}</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" @click="editModal = false">Batal</button>
          <button class="btn btn-primary" :disabled="saving" @click="saveEdit">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
            Simpan
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

export default defineComponent({
  name: 'SalarySettings',
  setup() {
    const employees = ref<any[]>([])   // ← selalu array
    const loading   = ref(false)
    const saving    = ref(false)
    const search    = ref('')
    const editModal = ref(false)
    const editData  = ref<any>({})
    const editForm  = ref({ base_salary: 0, position_allowance: 0, overtime_rate: 0, late_rate: 0 })
    const loadError = ref('')
    const saveError = ref('')

    // ── filteredEmployees aman karena employees selalu array ──
    const filteredEmployees = computed(() =>
      employees.value.filter(e =>
        (e.name ?? '').toLowerCase().includes(search.value.toLowerCase()) ||
        (e.position ?? e.job_title ?? '').toLowerCase().includes(search.value.toLowerCase())
      )
    )

    const load = async () => {
      loading.value   = true
      loadError.value = ''
      try {
        const res = await axios.get(`${API}/admin/salary-settings`, { headers: headers() })
        // ── Defensive guard: pastikan selalu array ──
        employees.value = Array.isArray(res.data?.data) ? res.data.data : []
      } catch (e: any) {
        loadError.value = e?.response?.data?.message ?? 'Gagal memuat data pegawai.'
        employees.value = []
      } finally {
        loading.value = false
      }
    }

    const openEdit = (emp: any) => {
      editData.value = emp
      editForm.value = {
        base_salary:        emp.salary?.base_salary        ?? 0,
        position_allowance: emp.salary?.position_allowance ?? 0,
        overtime_rate:      emp.salary?.overtime_rate      ?? 0,
        late_rate:          emp.salary?.late_rate          ?? 0,
      }
      saveError.value = ''
      editModal.value = true
    }

    const saveEdit = async () => {
      saving.value    = true
      saveError.value = ''
      try {
        await axios.put(`${API}/admin/salary-settings/${editData.value.id}`, editForm.value, { headers: headers() })
        editModal.value = false
        await load()
      } catch (e: any) {
        saveError.value = e?.response?.data?.message ?? 'Gagal menyimpan. Coba lagi.'
      } finally {
        saving.value = false
      }
    }

    const formatRp = (v: number) => 'Rp ' + (Number(v) || 0).toLocaleString('id-ID')

    onMounted(load)

    return {
      employees, loading, saving, search,
      editModal, editData, editForm,
      loadError, saveError,
      filteredEmployees,
      openEdit, saveEdit, formatRp, load,
    }
  },
})
</script>