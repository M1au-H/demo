<template>
  <div class="sf-wrap">
    <div class="sf-header">
      <h2 class="sf-title">Sanksi & Tugas Tambahan</h2>
      <p class="sf-sub">Kelola sanksi dan tugas untuk pegawai</p>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="sf-tabs">
      <button :class="['sf-tab', mainTab === 'form' && 'active']"    @click="mainTab = 'form'">✏️ Beri Sanksi / Tugas</button>
      <button :class="['sf-tab', mainTab === 'monitor' && 'active']" @click="mainTab = 'monitor'; loadMonitor()">
        📊 Monitor Penyelesaian
        <span v-if="unreadCount > 0" class="sf-badge">{{ unreadCount }}</span>
      </button>
    </div>

    <!-- ═══════════════════ TAB: FORM ═══════════════════ -->
    <div v-if="mainTab === 'form'" class="sf-grid">
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

    <!-- ═══════════════════ TAB: MONITOR ═══════════════════ -->
    <div v-if="mainTab === 'monitor'">

      <!-- Sub-tabs -->
      <div class="sf-subtabs">
        <button :class="['sf-subtab', monitorTab === 'sanctions' && 'active']" @click="monitorTab = 'sanctions'">
          ⚠️ Sanksi
          <span v-if="completedSanctions.filter(s => !s.admin_seen).length > 0" class="sf-badge sf-badge-sm">
            {{ completedSanctions.filter(s => !s.admin_seen).length }}
          </span>
        </button>
        <button :class="['sf-subtab', monitorTab === 'tasks' && 'active']" @click="monitorTab = 'tasks'">
          📋 Tugas
          <span v-if="completedTasks.filter(t => !t.admin_seen).length > 0" class="sf-badge sf-badge-sm">
            {{ completedTasks.filter(t => !t.admin_seen).length }}
          </span>
        </button>
      </div>

      <!-- Filter pegawai -->
      <div style="margin-bottom:16px;">
        <select v-model="filterUserId" class="sf-select" style="max-width:280px;">
          <option value="">Semua Pegawai</option>
          <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
        </select>
      </div>

      <!-- Loading -->
      <div v-if="loadingMonitor" class="sf-loading-full">Memuat data...</div>

      <!-- ── Sanksi selesai ── -->
      <div v-else-if="monitorTab === 'sanctions'">
        <div v-if="filteredSanctions.length === 0" class="sf-empty-full">Belum ada sanksi yang diselesaikan</div>
        <div v-for="s in filteredSanctions" :key="s.id" :class="['mon-card', !s.admin_seen && 'mon-card-new']">
          <!-- Badge NEW -->
          <span v-if="!s.admin_seen" class="mon-new-badge">BARU</span>

          <div class="mon-top">
            <div>
              <span class="mon-name">{{ s.user?.name }}</span>
              <span :class="sanctionBadge(s.type)" style="margin-left:8px;">{{ sanctionLabel(s.type) }}</span>
            </div>
            <span class="mon-date">Selesai: {{ formatDate(s.completed_at) }}</span>
          </div>

          <p class="mon-reason"><span class="mon-label">Alasan:</span> {{ s.reason }}</p>
          <p class="mon-meta">Diberikan: {{ formatDate(s.sanction_date) }} · Oleh: {{ s.giver?.name }}</p>

          <!-- Foto bukti -->
          <div v-if="s.proof_photo_url" class="mon-proof">
            <p class="mon-label">📷 Foto Bukti:</p>
            <img :src="s.proof_photo_url" class="mon-proof-img" @click="lightbox = s.proof_photo_url" />
          </div>
          <div v-else class="mon-no-proof">Tidak ada foto bukti</div>

          <!-- Tombol tandai sudah dilihat -->
          <button v-if="!s.admin_seen" class="btn-seen" @click="markSeen('sanction', s.id)">✓ Tandai Sudah Dilihat</button>
        </div>
      </div>

      <!-- ── Tugas selesai ── -->
      <div v-else-if="monitorTab === 'tasks'">
        <div v-if="filteredTasks.length === 0" class="sf-empty-full">Belum ada tugas yang diselesaikan</div>
        <div v-for="t in filteredTasks" :key="t.id" :class="['mon-card', !t.admin_seen && 'mon-card-new']">
          <!-- Badge NEW -->
          <span v-if="!t.admin_seen" class="mon-new-badge">BARU</span>

          <div class="mon-top">
            <div>
              <span class="mon-name">{{ t.user?.name }}</span>
              <span class="badge-done" style="margin-left:8px;">✓ Selesai</span>
            </div>
            <span class="mon-date">Selesai: {{ formatDate(t.completed_at) }}</span>
          </div>

          <p class="mon-task-title">{{ t.title }}</p>
          <p v-if="t.description" class="mon-reason">{{ t.description }}</p>
          <p class="mon-meta">
            Deadline: {{ t.due_date ? formatDate(t.due_date) : '-' }} · Dari: {{ t.assigner?.name }}
          </p>

          <!-- Foto bukti -->
          <div v-if="t.proof_photo_url" class="mon-proof">
            <p class="mon-label">📷 Foto Bukti:</p>
            <img :src="t.proof_photo_url" class="mon-proof-img" @click="lightbox = t.proof_photo_url" />
          </div>
          <div v-else class="mon-no-proof">Tidak ada foto bukti</div>

          <!-- Tombol tandai sudah dilihat -->
          <button v-if="!t.admin_seen" class="btn-seen" @click="markSeen('task', t.id)">✓ Tandai Sudah Dilihat</button>
        </div>
      </div>
    </div>

    <!-- Lightbox -->
    <div v-if="lightbox" class="modal-overlay" @click="lightbox = null">
      <img :src="lightbox" class="lightbox-img" />
      <p style="color:#fff;font-size:12px;margin-top:8px;">Klik untuk menutup</p>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'SanctionForm',
  setup() {
    const employees = ref<any[]>([])
    const mainTab   = ref('form')
    const monitorTab = ref('sanctions')
    const filterUserId = ref('')
    const lightbox  = ref<string | null>(null)

    // ── Form state ──
    const sanctionForm    = ref({ user_id: '', type: '', reason: '' })
    const sanctionLoading = ref(false)
    const sanctionMsg     = ref('')
    const sanctionMsgType = ref('sf-msg-success')

    const taskForm    = ref({ user_id: '', title: '', description: '', due_date: '' })
    const taskLoading = ref(false)
    const taskMsg     = ref('')
    const taskMsgType = ref('sf-msg-success')

    // ── Monitor state ──
    const completedSanctions = ref<any[]>([])
    const completedTasks     = ref<any[]>([])
    const loadingMonitor     = ref(false)

    const unreadCount = computed(() =>
      completedSanctions.value.filter(s => !s.admin_seen).length +
      completedTasks.value.filter(t => !t.admin_seen).length
    )

    const filteredSanctions = computed(() =>
      filterUserId.value
        ? completedSanctions.value.filter(s => s.user_id == filterUserId.value)
        : completedSanctions.value
    )
    const filteredTasks = computed(() =>
      filterUserId.value
        ? completedTasks.value.filter(t => t.user_id == filterUserId.value)
        : completedTasks.value
    )

    // ── Load ──
    const loadEmployees = async () => {
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('admin/employees')
        employees.value = data.data
      } catch (_) {}
    }

    const loadMonitor = async () => {
      loadingMonitor.value = true
      try {
        ApiService.setHeader()
        const [sRes, tRes] = await Promise.all([
          ApiService.get('admin/sanctions/completed'),
          ApiService.get('admin/tasks/completed'),
        ])
        completedSanctions.value = sRes.data.data
        completedTasks.value     = tRes.data.data
      } catch (_) {} finally { loadingMonitor.value = false }
    }

    // ── Submit sanksi ──
    const submitSanction = async () => {
      sanctionLoading.value = true; sanctionMsg.value = ''
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/sanction/${sanctionForm.value.user_id}`, {
          type: sanctionForm.value.type, reason: sanctionForm.value.reason,
        })
        sanctionMsg.value = 'Sanksi berhasil diberikan!'
        sanctionMsgType.value = 'sf-msg-success'
        sanctionForm.value = { user_id: '', type: '', reason: '' }
      } catch (err: any) {
        sanctionMsg.value = err?.response?.data?.message ?? 'Gagal menyimpan'
        sanctionMsgType.value = 'sf-msg-error'
      } finally { sanctionLoading.value = false }
    }

    // ── Submit tugas ──
    const submitTask = async () => {
      taskLoading.value = true; taskMsg.value = ''
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/task/${taskForm.value.user_id}`, {
          title: taskForm.value.title, description: taskForm.value.description,
          due_date: taskForm.value.due_date || null,
        })
        taskMsg.value = 'Tugas berhasil diberikan!'
        taskMsgType.value = 'sf-msg-success'
        taskForm.value = { user_id: '', title: '', description: '', due_date: '' }
      } catch (err: any) {
        taskMsg.value = err?.response?.data?.message ?? 'Gagal menyimpan'
        taskMsgType.value = 'sf-msg-error'
      } finally { taskLoading.value = false }
    }

    // ── Tandai sudah dilihat ──
    const markSeen = async (type: 'sanction' | 'task', id: number) => {
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/${type}s/${id}/seen`, {})
        if (type === 'sanction') {
          const idx = completedSanctions.value.findIndex(s => s.id === id)
          if (idx !== -1) completedSanctions.value[idx].admin_seen = true
        } else {
          const idx = completedTasks.value.findIndex(t => t.id === id)
          if (idx !== -1) completedTasks.value[idx].admin_seen = true
        }
      } catch (_) {}
    }

    // ── Helpers ──
    const formatDate    = (d: string) => d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'
    const sanctionBadge = (t: string) => t === 'warning' ? 'badge-warn' : t === 'mandatory_overtime' ? 'badge-ot' : 'badge-disc'
    const sanctionLabel = (t: string) => t === 'warning' ? 'Peringatan' : t === 'mandatory_overtime' ? 'Lembur Wajib' : 'Catatan Disiplin'

    onMounted(loadEmployees)

    return {
      employees, mainTab, monitorTab, filterUserId, lightbox,
      sanctionForm, sanctionLoading, sanctionMsg, sanctionMsgType,
      taskForm, taskLoading, taskMsg, taskMsgType,
      completedSanctions, completedTasks, loadingMonitor,
      unreadCount, filteredSanctions, filteredTasks,
      submitSanction, submitTask, loadMonitor, markSeen,
      formatDate, sanctionBadge, sanctionLabel,
    }
  }
})
</script>

<style scoped>
.sf-wrap { padding: 24px; }
.sf-header { margin-bottom: 20px; }
.sf-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.sf-sub { font-size: 13px; color: #55555e; margin: 0; }

/* Tabs */
.sf-tabs { display: flex; gap: 8px; margin-bottom: 20px; }
.sf-tab { background: #181b22; border: 1.5px solid rgba(255,255,255,0.08); color: #55555e; border-radius: 10px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s; position: relative; display: flex; align-items: center; gap: 6px; }
.sf-tab.active { background: #e8262a; border-color: #e8262a; color: #fff; }
.sf-subtabs { display: flex; gap: 8px; margin-bottom: 16px; }
.sf-subtab { background: #181b22; border: 1.5px solid rgba(255,255,255,0.08); color: #55555e; border-radius: 8px; padding: 7px 16px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; gap: 6px; }
.sf-subtab.active { background: #1b84ff22; border-color: #1b84ff; color: #1b84ff; }
.sf-badge { background: #e8262a; color: #fff; border-radius: 20px; padding: 1px 7px; font-size: 10px; font-weight: 800; }
.sf-badge-sm { font-size: 9px; padding: 1px 5px; }

/* Form grid */
.sf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.sf-card { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 24px; }
.sf-card-title { font-size: 15px; font-weight: 700; color: #f0f0f5; margin: 0 0 20px; }
.sf-field { margin-bottom: 14px; }
.sf-label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; margin-bottom: 7px; }
.sf-select, .sf-input { width: 100%; background: #0d0f14; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px; color: #e2e2e8; padding: 10px 14px; font-size: 13px; outline: none; box-sizing: border-box; }
.sf-select:focus, .sf-input:focus { border-color: #1b84ff; }
.sf-textarea { width: 100%; background: #0d0f14; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px; color: #e2e2e8; padding: 10px 14px; font-size: 13px; outline: none; resize: vertical; font-family: inherit; box-sizing: border-box; }
.sf-textarea:focus { border-color: #1b84ff; }
.sf-btn-red  { width: 100%; background: #e8262a; color: #fff; border: none; border-radius: 10px; padding: 13px; font-size: 14px; font-weight: 700; cursor: pointer; transition: background 0.15s; }
.sf-btn-red:hover:not(:disabled)  { background: #ff3a3d; }
.sf-btn-blue { width: 100%; background: #1b84ff; color: #fff; border: none; border-radius: 10px; padding: 13px; font-size: 14px; font-weight: 700; cursor: pointer; transition: background 0.15s; }
.sf-btn-blue:hover:not(:disabled) { background: #3a9dff; }
.sf-btn-red:disabled, .sf-btn-blue:disabled { opacity: 0.5; cursor: not-allowed; }
.sf-msg { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }
.sf-msg-success { background: rgba(23,198,83,0.1); color: #17c653; border: 1px solid rgba(23,198,83,0.2); }
.sf-msg-error   { background: rgba(255,107,107,0.1); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.2); }
.sf-loading-full { color: #55555e; padding: 32px 0; text-align: center; font-size: 13px; }
.sf-empty-full   { color: #3a3a48; padding: 48px 0; text-align: center; font-size: 14px; }

/* Monitor cards */
.mon-card { background: #15171e; border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; padding: 18px 20px; margin-bottom: 12px; position: relative; }
.mon-card-new { border-color: rgba(27,132,255,0.35); background: #15171e; box-shadow: 0 0 0 1px rgba(27,132,255,0.15); }
.mon-new-badge { position: absolute; top: 14px; right: 14px; background: #1b84ff; color: #fff; font-size: 9px; font-weight: 800; padding: 2px 8px; border-radius: 20px; letter-spacing: 0.05em; }
.mon-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; flex-wrap: wrap; gap: 6px; }
.mon-name { font-size: 14px; font-weight: 700; color: #f0f0f5; }
.mon-date { font-size: 11px; color: #55555e; }
.mon-task-title { font-size: 14px; font-weight: 600; color: #f0f0f5; margin: 0 0 6px; }
.mon-reason { font-size: 13px; color: #aaaabc; margin: 0 0 6px; }
.mon-meta { font-size: 11px; color: #3a3a48; margin: 0 0 10px; }
.mon-label { font-size: 11px; font-weight: 700; color: #55555e; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 6px; display: block; }
.mon-proof { margin-top: 10px; }
.mon-proof-img { width: 100%; max-width: 280px; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); transition: opacity 0.15s; display: block; }
.mon-proof-img:hover { opacity: 0.8; }
.mon-no-proof { font-size: 12px; color: #3a3a48; margin-top: 8px; font-style: italic; }
.btn-seen { margin-top: 12px; background: #1b84ff22; color: #1b84ff; border: 1px solid #1b84ff44; border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; transition: background 0.15s; }
.btn-seen:hover { background: #1b84ff33; }

/* Badge */
.badge-done { background: rgba(23,198,83,0.15);  color: #17c653; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-warn { background: rgba(255,107,107,0.15); color: #ff6b6b; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-ot   { background: rgba(27,132,255,0.15);  color: #1b84ff; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-disc { background: rgba(114,57,234,0.15);  color: #7239ea; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }

/* Lightbox */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; cursor: pointer; }
.lightbox-img  { max-width: 90vw; max-height: 85vh; border-radius: 12px; object-fit: contain; }

@media (max-width: 768px) { .sf-grid { grid-template-columns: 1fr; } }
</style>