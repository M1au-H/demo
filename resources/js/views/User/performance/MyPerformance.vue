<template>
  <div class="mp-wrap">
    <div class="mp-header">
      <h2 class="mp-title">Performa Saya</h2>
      <p class="mp-sub">Riwayat penilaian, sanksi, dan tugas dari admin</p>
    </div>

    <!-- Tabs -->
    <div class="mp-tabs">
      <button @click="activeTab = 'performance'" :class="['mp-tab', activeTab === 'performance' && 'active']">⭐ Penilaian</button>
      <button @click="activeTab = 'sanctions'"   :class="['mp-tab', activeTab === 'sanctions'   && 'active']">⚠️ Sanksi</button>
      <button @click="activeTab = 'tasks'"       :class="['mp-tab', activeTab === 'tasks'       && 'active']">📋 Tugas</button>
    </div>

    <!-- ═══════════════════ PENILAIAN ═══════════════════ -->
    <div v-if="activeTab === 'performance'">
      <div v-if="loadingPerf" class="mp-loading">Memuat...</div>
      <div v-else-if="reviews.length === 0" class="mp-empty">Belum ada penilaian</div>
      <div v-for="r in reviews" :key="r.id" class="mp-card">
        <div class="mp-card-top">
          <span class="mp-date">{{ formatDate(r.review_date) }}</span>
          <div style="display:flex;align-items:center;gap:10px;">
            <div class="mp-stars">{{ '★'.repeat(r.rating) }}<span class="mp-stars-empty">{{ '★'.repeat(5 - r.rating) }}</span></div>
            <button class="btn-icon btn-danger" @click="confirmDelete('review', r.id)" title="Hapus penilaian">🗑</button>
          </div>
        </div>
        <p v-if="r.comment" class="mp-comment">{{ r.comment }}</p>
        <p class="mp-by">Dinilai oleh: {{ r.reviewer?.name }}</p>
      </div>
    </div>

    <!-- ═══════════════════ SANKSI ═══════════════════ -->
    <div v-if="activeTab === 'sanctions'">
      <div v-if="loadingSanc" class="mp-loading">Memuat...</div>
      <div v-else-if="sanctions.length === 0" class="mp-empty">Tidak ada sanksi 🎉</div>
      <div v-for="s in sanctions" :key="s.id" class="mp-card mp-card-red">
        <div class="mp-card-top">
          <span class="mp-date">{{ formatDate(s.sanction_date) }}</span>
          <span :class="sanctionBadge(s.type)">{{ sanctionLabel(s.type) }}</span>
        </div>
        <p class="mp-comment">{{ s.reason }}</p>
        <p class="mp-by">Diberikan oleh: {{ s.giver?.name }}</p>

        <!-- Status & tombol selesaikan -->
        <div class="mp-action-row">
          <span v-if="s.completed_at" class="badge-done">✓ Diselesaikan {{ formatDate(s.completed_at) }}</span>
          <div v-else style="display:flex;gap:8px;flex-wrap:wrap;">
            <label class="btn-upload">
              📷 Upload Bukti
              <input type="file" accept="image/*" @change="e => onFileSelected(e, 'sanction', s.id)" hidden />
            </label>
            <span v-if="pendingFiles['sanction_' + s.id]" class="file-name">{{ pendingFiles['sanction_' + s.id].name }}</span>
            <button class="btn-complete" :disabled="completing['sanction_' + s.id]" @click="completeItem('sanction', s.id)">
              {{ completing['sanction_' + s.id] ? 'Menyimpan...' : '✓ Selesaikan' }}
            </button>
          </div>
        </div>

        <!-- Preview foto bukti -->
        <div v-if="s.proof_photo_url" class="mp-proof">
          <p class="mp-by" style="margin-bottom:4px;">Foto bukti:</p>
          <img :src="s.proof_photo_url" class="proof-img" @click="openPhoto(s.proof_photo_url)" />
        </div>
      </div>
    </div>

    <!-- ═══════════════════ TUGAS ═══════════════════ -->
    <div v-if="activeTab === 'tasks'">
      <div v-if="loadingTask" class="mp-loading">Memuat...</div>
      <div v-else-if="tasks.length === 0" class="mp-empty">Tidak ada tugas tambahan</div>
      <div v-for="t in tasks" :key="t.id" class="mp-card">
        <div class="mp-card-top">
          <strong class="mp-task-title">{{ t.title }}</strong>
          <span :class="t.status === 'done' ? 'badge-done' : 'badge-pending'">{{ t.status === 'done' ? '✓ Selesai' : 'Pending' }}</span>
        </div>
        <p v-if="t.description" class="mp-comment">{{ t.description }}</p>
        <p class="mp-by">
          Deadline: {{ t.due_date ? formatDate(t.due_date) : '-' }} · Dari: {{ t.assigner?.name }}
        </p>

        <!-- Status & tombol selesaikan -->
        <div class="mp-action-row">
          <span v-if="t.status === 'done'" class="badge-done">✓ Diselesaikan {{ t.completed_at ? formatDate(t.completed_at) : '' }}</span>
          <div v-else style="display:flex;gap:8px;flex-wrap:wrap;">
            <label class="btn-upload">
              📷 Upload Bukti
              <input type="file" accept="image/*" @change="e => onFileSelected(e, 'task', t.id)" hidden />
            </label>
            <span v-if="pendingFiles['task_' + t.id]" class="file-name">{{ pendingFiles['task_' + t.id].name }}</span>
            <button class="btn-complete" :disabled="completing['task_' + t.id]" @click="completeItem('task', t.id)">
              {{ completing['task_' + t.id] ? 'Menyimpan...' : '✓ Selesaikan' }}
            </button>
          </div>
        </div>

        <!-- Preview foto bukti -->
        <div v-if="t.proof_photo_url" class="mp-proof">
          <p class="mp-by" style="margin-bottom:4px;">Foto bukti:</p>
          <img :src="t.proof_photo_url" class="proof-img" @click="openPhoto(t.proof_photo_url)" />
        </div>
      </div>
    </div>

    <!-- ═══════════════════ MODAL KONFIRMASI HAPUS ═══════════════════ -->
    <div v-if="deleteModal.show" class="modal-overlay" @click.self="deleteModal.show = false">
      <div class="modal-box">
        <h3 class="modal-title">Hapus Penilaian?</h3>
        <p class="modal-desc">Penilaian ini akan dihapus permanen dan tidak bisa dikembalikan.</p>
        <div class="modal-actions">
          <button class="btn-cancel" @click="deleteModal.show = false">Batal</button>
          <button class="btn-confirm-delete" :disabled="deleteModal.loading" @click="doDelete">
            {{ deleteModal.loading ? 'Menghapus...' : 'Ya, Hapus' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════════════ LIGHTBOX FOTO ═══════════════════ -->
    <div v-if="lightbox" class="modal-overlay" @click="lightbox = null">
      <img :src="lightbox" class="lightbox-img" />
    </div>

    <!-- Toast notifikasi -->
    <div v-if="toast.show" :class="['mp-toast', toast.type]">{{ toast.msg }}</div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, reactive, onMounted } from 'vue'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'MyPerformance',
  setup() {
    const activeTab   = ref('performance')
    const reviews     = ref<any[]>([])
    const sanctions   = ref<any[]>([])
    const tasks       = ref<any[]>([])
    const loadingPerf = ref(true)
    const loadingSanc = ref(true)
    const loadingTask = ref(true)
    const lightbox    = ref<string | null>(null)

    // key: 'sanction_ID' | 'task_ID'
    const pendingFiles = reactive<Record<string, File>>({})
    const completing   = reactive<Record<string, boolean>>({})

    const deleteModal = reactive({ show: false, type: '', id: 0, loading: false })
    const toast       = reactive({ show: false, msg: '', type: 'success' })

    // ─── Load data ───────────────────────────────────────
    const loadAll = async () => {
      ApiService.setHeader()
      try { const { data } = await ApiService.get('performance/my'); reviews.value   = data.data } catch (_) {} finally { loadingPerf.value = false }
      try { const { data } = await ApiService.get('sanctions/my');   sanctions.value = data.data } catch (_) {} finally { loadingSanc.value = false }
      try { const { data } = await ApiService.get('tasks/my');       tasks.value     = data.data } catch (_) {} finally { loadingTask.value = false }
    }
    onMounted(loadAll)

    // ─── Helpers ─────────────────────────────────────────
    const formatDate    = (d: string) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    const sanctionBadge = (t: string) => t === 'warning' ? 'badge-warn' : t === 'mandatory_overtime' ? 'badge-ot' : 'badge-disc'
    const sanctionLabel = (t: string) => t === 'warning' ? 'Peringatan' : t === 'mandatory_overtime' ? 'Lembur Wajib' : 'Catatan Disiplin'
    const openPhoto     = (url: string) => { lightbox.value = url }

    const showToast = (msg: string, type = 'success') => {
      toast.msg = msg; toast.type = type; toast.show = true
      setTimeout(() => { toast.show = false }, 3000)
    }

    // ─── Hapus Penilaian ─────────────────────────────────
    const confirmDelete = (type: string, id: number) => {
      deleteModal.type = type; deleteModal.id = id; deleteModal.show = true
    }

    const doDelete = async () => {
      deleteModal.loading = true
      try {
        await ApiService.delete(`performance/${deleteModal.id}`)
        reviews.value = reviews.value.filter(r => r.id !== deleteModal.id)
        showToast('Penilaian berhasil dihapus')
        deleteModal.show = false
      } catch (_) {
        showToast('Gagal menghapus penilaian', 'error')
      } finally {
        deleteModal.loading = false
      }
    }

    // ─── Pilih file foto bukti ────────────────────────────
    const onFileSelected = (e: Event, type: string, id: number) => {
      const file = (e.target as HTMLInputElement).files?.[0]
      if (file) pendingFiles[`${type}_${id}`] = file
    }

    // ─── Selesaikan sanksi / tugas ────────────────────────
    const completeItem = async (type: 'sanction' | 'task', id: number) => {
      const key  = `${type}_${id}`
      completing[key] = true

      try {
        const formData = new FormData()
        if (pendingFiles[key]) formData.append('proof_photo', pendingFiles[key])

        const endpoint = type === 'sanction'
          ? `sanctions/${id}/complete`
          : `tasks/${id}/complete`

        const { data } = await ApiService.post(endpoint, formData)

        if (type === 'sanction') {
          const idx = sanctions.value.findIndex(s => s.id === id)
          if (idx !== -1) sanctions.value[idx] = { ...sanctions.value[idx], ...data.data }
        } else {
          const idx = tasks.value.findIndex(t => t.id === id)
          if (idx !== -1) tasks.value[idx] = { ...tasks.value[idx], ...data.data }
        }

        delete pendingFiles[key]
        showToast('Berhasil diselesaikan!')
      } catch (_) {
        showToast('Gagal menyimpan. Coba lagi.', 'error')
      } finally {
        completing[key] = false
      }
    }

    return {
      activeTab, reviews, sanctions, tasks,
      loadingPerf, loadingSanc, loadingTask,
      pendingFiles, completing,
      deleteModal, toast, lightbox,
      formatDate, sanctionBadge, sanctionLabel,
      confirmDelete, doDelete,
      onFileSelected, completeItem, openPhoto,
    }
  }
})
</script>

<style scoped>
.mp-wrap { max-width: 640px; margin: 0 auto; padding: 24px 16px; }
.mp-header { margin-bottom: 24px; }
.mp-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.mp-sub { font-size: 13px; color: #55555e; margin: 0; }
.mp-tabs { display: flex; gap: 8px; margin-bottom: 20px; }
.mp-tab { background: #181b22; border: 1.5px solid rgba(255,255,255,0.08); color: #55555e; border-radius: 10px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.mp-tab.active { background: #e8262a; border-color: #e8262a; color: #fff; }
.mp-loading { color: #55555e; font-size: 13px; padding: 24px 0; }
.mp-empty { text-align: center; color: #3a3a48; padding: 48px 0; font-size: 14px; }
.mp-card { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 16px 20px; margin-bottom: 10px; }
.mp-card-red { border-color: rgba(255,107,107,0.2); }
.mp-card-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.mp-date { font-size: 12px; color: #55555e; }
.mp-stars { font-size: 18px; color: #fbbf24; }
.mp-stars-empty { color: #3a3a48; }
.mp-comment { font-size: 13px; color: #aaaabc; margin: 0 0 6px; }
.mp-by { font-size: 11px; color: #3a3a48; margin: 0; }
.mp-task-title { font-size: 14px; color: #f0f0f5; }
.mp-action-row { margin-top: 12px; display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
.mp-proof { margin-top: 10px; }
.proof-img { width: 100%; max-width: 300px; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); transition: opacity 0.15s; }
.proof-img:hover { opacity: 0.85; }

/* Tombol aksi */
.btn-icon { background: none; border: none; cursor: pointer; font-size: 15px; padding: 2px 6px; border-radius: 6px; transition: background 0.15s; }
.btn-danger:hover { background: rgba(232,38,42,0.15); }
.btn-complete { background: #17c653; color: #fff; border: none; border-radius: 8px; padding: 7px 14px; font-size: 12px; font-weight: 700; cursor: pointer; transition: opacity 0.15s; }
.btn-complete:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-upload { background: #1b84ff22; color: #1b84ff; border: 1px dashed #1b84ff55; border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; transition: background 0.15s; }
.btn-upload:hover { background: #1b84ff33; }
.file-name { font-size: 11px; color: #aaaabc; align-self: center; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Badge */
.badge-done    { background: rgba(23,198,83,0.15);  color: #17c653; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-pending { background: rgba(255,165,0,0.15);  color: #ffa500; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-warn    { background: rgba(255,107,107,0.15); color: #ff6b6b; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-ot      { background: rgba(27,132,255,0.15);  color: #1b84ff; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-disc    { background: rgba(114,57,234,0.15);  color: #7239ea; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 16px; }
.modal-box { background: #1a1d26; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 28px 24px; width: 100%; max-width: 360px; }
.modal-title { font-size: 17px; font-weight: 800; color: #f0f0f5; margin: 0 0 8px; }
.modal-desc { font-size: 13px; color: #aaaabc; margin: 0 0 20px; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
.btn-cancel { background: #2a2d38; color: #aaaabc; border: none; border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-confirm-delete { background: #e8262a; color: #fff; border: none; border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 700; cursor: pointer; }
.btn-confirm-delete:disabled { opacity: 0.5; cursor: not-allowed; }

/* Lightbox */
.lightbox-img { max-width: 90vw; max-height: 90vh; border-radius: 12px; object-fit: contain; }

/* Toast */
.mp-toast { position: fixed; bottom: 24px; right: 24px; padding: 12px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; z-index: 9999; animation: fadeIn 0.2s; }
.mp-toast.success { background: #17c653; color: #fff; }
.mp-toast.error   { background: #e8262a; color: #fff; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>