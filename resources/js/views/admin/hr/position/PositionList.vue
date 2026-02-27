<template>
  <div class="pl-wrap">
    <div class="pl-header">
      <div>
        <h2 class="pl-title">Jabatan</h2>
        <p class="pl-sub">Kelola jabatan pegawai</p>
      </div>
      <button @click="openAdd" class="pl-btn-add">+ Tambah Jabatan</button>
    </div>

    <div v-if="loading" class="pl-loading">Memuat...</div>

    <div v-else class="pl-table-wrap">
      <table class="pl-table">
        <thead>
          <tr>
            <th>Nama Jabatan</th>
            <th>Deskripsi</th>
            <th>Jumlah Pegawai</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="positions.length === 0">
            <td colspan="4" class="pl-empty">Belum ada jabatan. Tambahkan jabatan pertama!</td>
          </tr>
          <tr v-for="pos in positions" :key="pos.id">
            <td><strong style="color:#f0f0f5">{{ pos.name }}</strong></td>
            <td class="pl-muted">{{ pos.description ?? '-' }}</td>
            <td>
              <span class="pl-count" style="cursor:pointer" @click="openManage(pos)">
                {{ pos.users_count }} pegawai
              </span>
            </td>
            <td>
              <div class="pl-actions">
                <button @click="openManage(pos)" class="pl-btn-manage">👥 Pegawai</button>
                <button @click="openEdit(pos)" class="pl-btn-edit">Edit</button>
                <button @click="deletePosition(pos)" class="pl-btn-delete">Hapus</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ══ Modal Tambah/Edit Jabatan ══ -->
    <div v-if="showModal" class="pl-modal-overlay" @click.self="closeModal">
      <div class="pl-modal">
        <h3 class="pl-modal-title">{{ isEdit ? 'Edit Jabatan' : 'Tambah Jabatan' }}</h3>
        <div class="pl-field">
          <label class="pl-label">Nama Jabatan</label>
          <input v-model="form.name" class="pl-input" type="text" placeholder="contoh: Kasir, Staff, Supervisor" />
        </div>
        <div class="pl-field">
          <label class="pl-label">Deskripsi (opsional)</label>
          <input v-model="form.description" class="pl-input" type="text" placeholder="Deskripsi jabatan..." />
        </div>
        <div v-if="formMsg" class="pl-msg-error">{{ formMsg }}</div>
        <div class="pl-modal-actions">
          <button @click="closeModal" class="pl-btn-cancel">Batal</button>
          <button @click="submitForm" :disabled="formLoading || !form.name" class="pl-btn-save">
            {{ formLoading ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ══ Modal Kelola Pegawai ══ -->
    <div v-if="showManage" class="pl-modal-overlay" @click.self="closeManage">
      <div class="pl-modal pl-modal-lg">
        <div class="pl-manage-header">
          <div>
            <h3 class="pl-modal-title">👥 Pegawai — {{ selectedPosition?.name }}</h3>
            <p class="pl-modal-sub">Assign atau cabut jabatan pegawai</p>
          </div>
          <button @click="closeManage" class="pl-btn-x">✕</button>
        </div>

        <div class="pl-manage-grid">
          <!-- Kiri: Pegawai yang sudah punya jabatan ini -->
          <div class="pl-manage-col">
            <p class="pl-col-title">✅ Sudah di jabatan ini</p>
            <div v-if="loadingManage" class="pl-loading-sm">Memuat...</div>
            <div v-else-if="assignedEmployees.length === 0" class="pl-col-empty">Belum ada pegawai</div>
            <div v-for="emp in assignedEmployees" :key="emp.id" class="pl-emp-row">
              <div class="pl-emp-avatar">
                <img v-if="emp.avatar" :src="avatarUrl(emp.avatar)" class="pl-emp-photo" />
                <span v-else>{{ emp.name?.charAt(0) }}</span>
              </div>
              <div class="pl-emp-info">
                <p class="pl-emp-name">{{ emp.name }}</p>
                <p class="pl-emp-email">{{ emp.email }}</p>
              </div>
              <button @click="revokeEmployee(emp)" class="pl-btn-revoke" :disabled="actionLoading === emp.id">
                {{ actionLoading === emp.id ? '...' : 'Cabut' }}
              </button>
            </div>
          </div>

          <!-- Kanan: Pegawai tanpa jabatan / jabatan lain -->
          <div class="pl-manage-col">
            <p class="pl-col-title">➕ Assign pegawai</p>
            <input v-model="searchEmp" class="pl-search" placeholder="Cari nama pegawai..." />
            <div v-if="loadingManage" class="pl-loading-sm">Memuat...</div>
            <div v-else-if="filteredUnassigned.length === 0" class="pl-col-empty">Tidak ada pegawai tersedia</div>
            <div v-for="emp in filteredUnassigned" :key="emp.id" class="pl-emp-row">
              <div class="pl-emp-avatar" style="background:#1b84ff">
                <img v-if="emp.avatar" :src="avatarUrl(emp.avatar)" class="pl-emp-photo" />
                <span v-else>{{ emp.name?.charAt(0) }}</span>
              </div>
              <div class="pl-emp-info">
                <p class="pl-emp-name">{{ emp.name }}</p>
                <p class="pl-emp-email">{{ emp.email }}</p>
                <p v-if="emp.position" class="pl-emp-pos">Jabatan saat ini: {{ emp.position.name }}</p>
                <p v-else class="pl-emp-pos" style="color:#3a3a48">Belum ada jabatan</p>
              </div>
              <button @click="assignEmployee(emp)" class="pl-btn-assign" :disabled="actionLoading === emp.id">
                {{ actionLoading === emp.id ? '...' : 'Assign' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'PositionList',
  setup() {
    const positions   = ref<any[]>([])
    const allEmployees = ref<any[]>([])
    const loading     = ref(true)
    const showModal   = ref(false)
    const showManage  = ref(false)
    const isEdit      = ref(false)
    const editId      = ref<number | null>(null)
    const formLoading = ref(false)
    const formMsg     = ref('')
    const form        = ref({ name: '', description: '' })
    const selectedPosition = ref<any>(null)
    const loadingManage = ref(false)
    const actionLoading = ref<number | null>(null)
    const searchEmp   = ref('')

    // Pegawai yang sudah di jabatan ini
    const assignedEmployees = computed(() =>
      allEmployees.value.filter(e => e.position_id === selectedPosition.value?.id)
    )

    // Pegawai yang belum di jabatan ini (bisa diassign)
    const unassignedEmployees = computed(() =>
      allEmployees.value.filter(e => e.position_id !== selectedPosition.value?.id)
    )

    const filteredUnassigned = computed(() => {
      const q = searchEmp.value.toLowerCase()
      return unassignedEmployees.value.filter(e =>
        e.name.toLowerCase().includes(q) || e.email.toLowerCase().includes(q)
      )
    })

    const avatarUrl = (avatar: string) => `${import.meta.env.VITE_APP_API_URL?.replace('/api', '')}/storage/${avatar}`

    const load = async () => {
      loading.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('admin/positions')
        positions.value = data.data
      } catch (_) {} finally { loading.value = false }
    }

    const loadAllEmployees = async () => {
      loadingManage.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('admin/employees')
        allEmployees.value = data.data
      } catch (_) {} finally { loadingManage.value = false }
    }

    const openAdd = () => {
      isEdit.value = false; editId.value = null
      form.value = { name: '', description: '' }
      formMsg.value = ''; showModal.value = true
    }

    const openEdit = (pos: any) => {
      isEdit.value = true; editId.value = pos.id
      form.value = { name: pos.name, description: pos.description ?? '' }
      formMsg.value = ''; showModal.value = true
    }

    const closeModal = () => { showModal.value = false }

    const openManage = async (pos: any) => {
      selectedPosition.value = pos
      searchEmp.value = ''
      showManage.value = true
      await loadAllEmployees()
    }

    const closeManage = () => { showManage.value = false; selectedPosition.value = null }

    const submitForm = async () => {
      formLoading.value = true; formMsg.value = ''
      try {
        ApiService.setHeader()
        if (isEdit.value && editId.value) {
          await ApiService.put(`admin/positions/${editId.value}`, form.value)
        } else {
          await ApiService.post('admin/positions', form.value)
        }
        closeModal(); load()
      } catch (err: any) {
        formMsg.value = err?.response?.data?.message
          ?? Object.values(err?.response?.data?.errors ?? {})[0] as string
          ?? 'Gagal menyimpan'
      } finally { formLoading.value = false }
    }

    const deletePosition = async (pos: any) => {
      if (!confirm(`Hapus jabatan "${pos.name}"?`)) return
      try {
        ApiService.setHeader()
        await ApiService.delete(`admin/positions/${pos.id}`)
        load()
      } catch (err: any) {
        alert(err?.response?.data?.message ?? 'Gagal menghapus')
      }
    }

    const assignEmployee = async (emp: any) => {
      actionLoading.value = emp.id
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/positions/${selectedPosition.value.id}/assign`, { user_id: emp.id })
        // Update local state
        emp.position_id = selectedPosition.value.id
        emp.position = { id: selectedPosition.value.id, name: selectedPosition.value.name }
        // Update count
        const pos = positions.value.find(p => p.id === selectedPosition.value.id)
        if (pos) pos.users_count++
        // Kurangi count jabatan lama jika ada
        if (emp.position_id) {
          const oldPos = positions.value.find(p => p.id === emp.position_id)
          if (oldPos && oldPos.id !== selectedPosition.value.id) oldPos.users_count--
        }
      } catch (err: any) {
        alert(err?.response?.data?.message ?? 'Gagal assign')
      } finally { actionLoading.value = null }
    }

    const revokeEmployee = async (emp: any) => {
      if (!confirm(`Cabut jabatan "${selectedPosition.value.name}" dari ${emp.name}?`)) return
      actionLoading.value = emp.id
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/positions/${selectedPosition.value.id}/revoke`, { user_id: emp.id })
        // Update local state
        emp.position_id = null
        emp.position = null
        const pos = positions.value.find(p => p.id === selectedPosition.value.id)
        if (pos && pos.users_count > 0) pos.users_count--
      } catch (err: any) {
        alert(err?.response?.data?.message ?? 'Gagal cabut jabatan')
      } finally { actionLoading.value = null }
    }

    onMounted(load)

    return {
      positions, loading, showModal, showManage, isEdit, form, formLoading, formMsg,
      selectedPosition, loadingManage, actionLoading, searchEmp,
      assignedEmployees, filteredUnassigned,
      avatarUrl, openAdd, openEdit, closeModal, openManage, closeManage,
      submitForm, deletePosition, assignEmployee, revokeEmployee
    }
  }
})
</script>

<style scoped>
.pl-wrap { padding: 24px; }
.pl-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.pl-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.pl-sub { font-size: 13px; color: #55555e; margin: 0; }
.pl-btn-add { background: #e8262a; color: #fff; border: none; border-radius: 10px; padding: 10px 18px; font-size: 13px; font-weight: 700; cursor: pointer; }
.pl-btn-add:hover { background: #ff3a3d; }
.pl-loading { color: #55555e; padding: 32px 0; }
.pl-loading-sm { color: #55555e; font-size: 13px; padding: 12px 0; }

.pl-table-wrap { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; overflow: hidden; }
.pl-table { width: 100%; border-collapse: collapse; }
.pl-table th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; border-bottom: 1px solid rgba(255,255,255,0.06); }
.pl-table td { padding: 14px 16px; font-size: 13px; color: #aaaabc; border-bottom: 1px solid rgba(255,255,255,0.04); }
.pl-table tr:last-child td { border-bottom: none; }
.pl-empty { text-align: center; color: #3a3a48; padding: 32px !important; }
.pl-muted { color: #3a3a48 !important; }
.pl-count { background: rgba(27,132,255,0.1); color: #1b84ff; padding: 2px 10px; border-radius: 20px; font-size: 12px; }
.pl-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.pl-btn-manage { background: rgba(23,198,83,0.1); color: #17c653; border: 1px solid rgba(23,198,83,0.2); border-radius: 6px; padding: 5px 12px; font-size: 12px; cursor: pointer; }
.pl-btn-edit   { background: rgba(27,132,255,0.1); color: #1b84ff; border: 1px solid rgba(27,132,255,0.2); border-radius: 6px; padding: 5px 12px; font-size: 12px; cursor: pointer; }
.pl-btn-delete { background: rgba(255,107,107,0.1); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.2); border-radius: 6px; padding: 5px 12px; font-size: 12px; cursor: pointer; }

/* Modal */
.pl-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.75); display: flex; align-items: center; justify-content: center; z-index: 999; padding: 16px; }
.pl-modal { background: #15171e; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 28px; width: 100%; max-width: 420px; }
.pl-modal-lg { max-width: 780px; }
.pl-modal-title { font-size: 17px; font-weight: 800; color: #f0f0f5; margin: 0; }
.pl-modal-sub { font-size: 12px; color: #55555e; margin: 4px 0 0; }
.pl-field { margin-bottom: 14px; }
.pl-label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; margin-bottom: 7px; }
.pl-input { width: 100%; background: #0d0f14; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px; color: #e2e2e8; padding: 10px 14px; font-size: 13px; outline: none; box-sizing: border-box; }
.pl-input:focus { border-color: #1b84ff; }
.pl-msg-error { background: rgba(255,107,107,0.1); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.2); padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }
.pl-modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
.pl-btn-cancel { background: #181b22; border: 1.5px solid rgba(255,255,255,0.1); color: #aaaabc; border-radius: 10px; padding: 10px 20px; font-size: 13px; cursor: pointer; }
.pl-btn-save   { background: #e8262a; color: #fff; border: none; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 700; cursor: pointer; }
.pl-btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

/* Manage Modal */
.pl-manage-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
.pl-btn-x { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #aaa; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; font-size: 14px; }
.pl-manage-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; max-height: 480px; }
.pl-manage-col { background: #0d0f14; border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 16px; overflow-y: auto; }
.pl-col-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #55555e; margin: 0 0 12px; }
.pl-col-empty { color: #3a3a48; font-size: 13px; text-align: center; padding: 24px 0; }
.pl-search { width: 100%; background: #15171e; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 8px; color: #e2e2e8; padding: 8px 12px; font-size: 12px; outline: none; margin-bottom: 10px; box-sizing: border-box; }
.pl-search:focus { border-color: #1b84ff; }

.pl-emp-row { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
.pl-emp-row:last-child { border-bottom: none; }
.pl-emp-avatar { width: 34px; height: 34px; border-radius: 50%; background: #e8262a; color: #fff; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pl-emp-info { flex: 1; min-width: 0; }
.pl-emp-name  { font-size: 13px; font-weight: 600; color: #e2e2e8; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pl-emp-email { font-size: 11px; color: #55555e; margin: 1px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pl-emp-pos   { font-size: 11px; color: #1b84ff; margin: 2px 0 0; }
.pl-btn-assign { background: rgba(23,198,83,0.15); color: #17c653; border: 1px solid rgba(23,198,83,0.3); border-radius: 6px; padding: 4px 10px; font-size: 11px; font-weight: 600; cursor: pointer; white-space: nowrap; flex-shrink: 0; }
.pl-btn-revoke { background: rgba(255,107,107,0.15); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.3); border-radius: 6px; padding: 4px 10px; font-size: 11px; font-weight: 600; cursor: pointer; white-space: nowrap; flex-shrink: 0; }
.pl-btn-assign:disabled, .pl-btn-revoke:disabled { opacity: 0.5; cursor: not-allowed; }
.pl-emp-photo { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

@media (max-width: 600px) { .pl-manage-grid { grid-template-columns: 1fr; } }
</style>