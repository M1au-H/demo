
<template>
  <div class="sf-wrap">
    <div class="sf-header">
      <h2 class="sf-title">Sanksi & Tugas Tambahan</h2>
      <p class="sf-sub">Kelola sanksi dan tugas untuk pegawai</p>
    </div>

    <div class="sf-grid">
      <!-- Form Sanksi -->
      <div class="sf-card">
        <h3 class="sf-card-title">⚠️ Beri Sanksi</h3>
        <div class="sf-field">
          <label class="sf-label">Pilih Pegawai</label>
          <select v-model="sanctionForm.user_id" class="sf-select">
            <option value="">-- Pilih pegawai --</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
          </select>
        </div>
        <div class="sf-field">
          <label class="sf-label">Jenis Sanksi</label>
          <select v-model="sanctionForm.type" class="sf-select">
            <option value="">-- Pilih jenis --</option>
            <option value="warning">Peringatan</option>
            <option value="mandatory_overtime">Lembur Wajib</option>
            <option value="disciplinary_note">Catatan Disiplin</option>
          </select>
        </div>
        <div class="sf-field">
          <label class="sf-label">Alasan</label>
          <textarea v-model="sanctionForm.reason" class="sf-textarea" rows="3" placeholder="Alasan pemberian sanksi..."></textarea>
        </div>
        <div v-if="sanctionMsg" :class="['sf-msg', sanctionMsgType]">{{ sanctionMsg }}</div>
        <button @click="submitSanction" :disabled="sanctionLoading || !sanctionForm.user_id || !sanctionForm.type || !sanctionForm.reason" class="sf-btn-red">
          {{ sanctionLoading ? 'Menyimpan...' : '⚠️ Beri Sanksi' }}
        </button>
      </div>

      <!-- Form Tugas Tambahan -->
      <div class="sf-card">
        <h3 class="sf-card-title">📋 Tugas Tambahan</h3>
        <div class="sf-field">
          <label class="sf-label">Pilih Pegawai</label>
          <select v-model="taskForm.user_id" class="sf-select">
            <option value="">-- Pilih pegawai --</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
          </select>
        </div>
        <div class="sf-field">
          <label class="sf-label">Judul Tugas</label>
          <input v-model="taskForm.title" class="sf-input" type="text" placeholder="Nama tugas..." />
        </div>
        <div class="sf-field">
          <label class="sf-label">Deskripsi</label>
          <textarea v-model="taskForm.description" class="sf-textarea" rows="2" placeholder="Detail tugas..."></textarea>
        </div>
        <div class="sf-field">
          <label class="sf-label">Deadline</label>
          <input v-model="taskForm.due_date" class="sf-input" type="date" />
        </div>
        <div v-if="taskMsg" :class="['sf-msg', taskMsgType]">{{ taskMsg }}</div>
        <button @click="submitTask" :disabled="taskLoading || !taskForm.user_id || !taskForm.title" class="sf-btn-blue">
          {{ taskLoading ? 'Menyimpan...' : '📋 Beri Tugas' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'SanctionForm',
  setup() {
    const employees = ref<any[]>([])

    const sanctionForm    = ref({ user_id: '', type: '', reason: '' })
    const sanctionLoading = ref(false)
    const sanctionMsg     = ref('')
    const sanctionMsgType = ref('sf-msg-success')

    const taskForm    = ref({ user_id: '', title: '', description: '', due_date: '' })
    const taskLoading = ref(false)
    const taskMsg     = ref('')
    const taskMsgType = ref('sf-msg-success')

    const loadEmployees = async () => {
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('admin/employees')
        employees.value = data.data
      } catch (_) {}
    }

    const submitSanction = async () => {
      sanctionLoading.value = true; sanctionMsg.value = ''
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/sanction/${sanctionForm.value.user_id}`, {
          type: sanctionForm.value.type,
          reason: sanctionForm.value.reason,
        })
        sanctionMsg.value  = 'Sanksi berhasil diberikan!'
        sanctionMsgType.value = 'sf-msg-success'
        sanctionForm.value = { user_id: '', type: '', reason: '' }
      } catch (err: any) {
        sanctionMsg.value  = err?.response?.data?.message ?? 'Gagal menyimpan'
        sanctionMsgType.value = 'sf-msg-error'
      } finally { sanctionLoading.value = false }
    }

    const submitTask = async () => {
      taskLoading.value = true; taskMsg.value = ''
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/task/${taskForm.value.user_id}`, {
          title: taskForm.value.title,
          description: taskForm.value.description,
          due_date: taskForm.value.due_date || null,
        })
        taskMsg.value  = 'Tugas berhasil diberikan!'
        taskMsgType.value = 'sf-msg-success'
        taskForm.value = { user_id: '', title: '', description: '', due_date: '' }
      } catch (err: any) {
        taskMsg.value  = err?.response?.data?.message ?? 'Gagal menyimpan'
        taskMsgType.value = 'sf-msg-error'
      } finally { taskLoading.value = false }
    }

    onMounted(loadEmployees)

    return { employees, sanctionForm, sanctionLoading, sanctionMsg, sanctionMsgType, taskForm, taskLoading, taskMsg, taskMsgType, submitSanction, submitTask }
  }
})
</script>

<style scoped>
.sf-wrap { padding: 24px; }
.sf-header { margin-bottom: 24px; }
.sf-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.sf-sub { font-size: 13px; color: #55555e; margin: 0; }
.sf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.sf-card { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 24px; }
.sf-card-title { font-size: 15px; font-weight: 700; color: #f0f0f5; margin: 0 0 20px; }
.sf-field { margin-bottom: 14px; }
.sf-label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; margin-bottom: 7px; }
.sf-select, .sf-input { width: 100%; background: #0d0f14; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px; color: #e2e2e8; padding: 10px 14px; font-size: 13px; outline: none; }
.sf-select:focus, .sf-input:focus { border-color: #1b84ff; }
.sf-textarea { width: 100%; background: #0d0f14; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px; color: #e2e2e8; padding: 10px 14px; font-size: 13px; outline: none; resize: vertical; font-family: inherit; }
.sf-textarea:focus { border-color: #1b84ff; }
.sf-btn-red  { width: 100%; background: #e8262a; color: #fff; border: none; border-radius: 10px; padding: 13px; font-size: 14px; font-weight: 700; cursor: pointer; transition: background 0.15s; }
.sf-btn-red:hover:not(:disabled)  { background: #ff3a3d; }
.sf-btn-blue { width: 100%; background: #1b84ff; color: #fff; border: none; border-radius: 10px; padding: 13px; font-size: 14px; font-weight: 700; cursor: pointer; transition: background 0.15s; }
.sf-btn-blue:hover:not(:disabled) { background: #3a9dff; }
.sf-btn-red:disabled, .sf-btn-blue:disabled { opacity: 0.5; cursor: not-allowed; }
.sf-msg { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }
.sf-msg-success { background: rgba(23,198,83,0.1); color: #17c653; border: 1px solid rgba(23,198,83,0.2); }
.sf-msg-error   { background: rgba(255,107,107,0.1); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.2); }
@media (max-width: 768px) { .sf-grid { grid-template-columns: 1fr; } }
</style>