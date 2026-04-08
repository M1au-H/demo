<template>
  <div class="lv-wrap">

    <!-- Header -->
    <div class="lv-header">
      <h2 class="lv-title">Izin & Cuti</h2>
      <p class="lv-sub">{{ today }}</p>
    </div>

    <!-- Form Ajukan Izin -->
    <div class="lv-card">
      <h3 class="lv-section-title">Ajukan Izin / Cuti</h3>

      <div class="lv-form">
        <div class="lv-field">
          <label>Tanggal</label>
          <input type="date" v-model="form.date" :min="minDate" class="lv-input" />
        </div>

        <div class="lv-field">
          <label>Jenis Izin</label>
          <select v-model="form.type" class="lv-input">
            <option value="">-- Pilih Jenis --</option>
            <option value="sakit">🤒 Sakit</option>
            <option value="cuti">🏖️ Cuti Tahunan</option>
            <option value="keluarga">👨‍👩‍👧 Keperluan Keluarga</option>
            <option value="mendadak">⚡ Izin Mendadak</option>
          </select>
        </div>

        <div class="lv-field">
          <label>Keterangan <span class="lv-optional">(opsional)</span></label>
          <textarea v-model="form.reason" class="lv-input lv-textarea" placeholder="Tulis keterangan izin..." rows="3" />
        </div>

        <!-- Surat Dokter Upload -->
        <div class="lv-field">
          <label>Surat Dokter <span class="lv-optional">(opsional, jpg/png/pdf maks 5MB)</span></label>
          <div class="lv-file-wrap" @click="triggerFileInput" @dragover.prevent @drop.prevent="onFileDrop">
            <input
              ref="fileInput"
              type="file"
              accept=".jpg,.jpeg,.png,.pdf"
              style="display:none"
              @change="onFileChange"
            />
            <div v-if="!suratDokterFile" class="lv-file-placeholder">
              <span class="lv-file-icon">📎</span>
              <span>Klik atau drag file ke sini</span>
            </div>
            <div v-else class="lv-file-selected">
              <span class="lv-file-icon">✅</span>
              <span>{{ suratDokterFile.name }}</span>
              <button @click.stop="clearFile" class="lv-file-clear">✕</button>
            </div>
          </div>
        </div>

        <div v-if="formError" class="lv-error">{{ formError }}</div>
        <div v-if="formSuccess" class="lv-success">{{ formSuccess }}</div>

        <button @click="submitLeave" :disabled="submitting" class="lv-btn-submit">
          <span v-if="!submitting">✓ Ajukan Izin</span>
          <span v-else class="lv-loading"><span class="lv-spinner"></span> Menyimpan...</span>
        </button>
      </div>
    </div>

    <!-- Riwayat Izin -->
    <div class="lv-card" style="margin-top: 20px;">
      <h3 class="lv-section-title">Riwayat Izin</h3>

      <div v-if="loadingHistory" class="lv-empty">Memuat...</div>

      <div v-else-if="leaves.length === 0" class="lv-empty">
        <div class="lv-empty-icon">📋</div>
        <p>Belum ada riwayat izin</p>
      </div>

      <div v-else class="lv-list">
        <div v-for="leave in leaves" :key="leave.id" class="lv-item">
          <div class="lv-item-left">
            <span class="lv-item-icon">{{ typeIcon(leave.type) }}</span>
            <div>
              <div class="lv-item-type">{{ typeLabel(leave.type) }}</div>
              <div class="lv-item-date">{{ formatDate(leave.date) }}</div>
              <div v-if="leave.reason" class="lv-item-reason">{{ leave.reason }}</div>
              <a v-if="leave.surat_dokter" :href="leave.surat_dokter" target="_blank" class="lv-item-surat">
                📄 Lihat Surat Dokter
              </a>
              <span v-else class="lv-item-nosurat">Tidak ada surat dokter</span>
            </div>
          </div>
          <button
            v-if="leave.date >= todayRaw"
            @click="deleteLeave(leave.id)"
            class="lv-btn-cancel"
            title="Batalkan izin"
          >✕</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue'
import ApiService from '@/core/services/ApiService'
import axios from 'axios'

export default defineComponent({
  name: 'MyLeave',
  setup() {
    const today    = new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
    const todayRaw = new Date().toISOString().split('T')[0]
    const minDate  = todayRaw

    const form           = ref({ date: todayRaw, type: '', reason: '' })
    const submitting     = ref(false)
    const formError      = ref('')
    const formSuccess    = ref('')
    const leaves         = ref<any[]>([])
    const loadingHistory = ref(true)
    const suratDokterFile = ref<File | null>(null)
    const fileInput      = ref<HTMLInputElement | null>(null)

    const typeLabel  = (t: string) => ({ sakit: 'Sakit', cuti: 'Cuti Tahunan', keluarga: 'Keperluan Keluarga', mendadak: 'Izin Mendadak' }[t] ?? t)
    const typeIcon   = (t: string) => ({ sakit: '🤒', cuti: '🏖️', keluarga: '👨‍👩‍👧', mendadak: '⚡' }[t] ?? '📝')
    const formatDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

    const triggerFileInput = () => fileInput.value?.click()
    const onFileChange = (e: Event) => {
      const file = (e.target as HTMLInputElement).files?.[0]
      if (file) suratDokterFile.value = file
    }
    const onFileDrop = (e: DragEvent) => {
      const file = e.dataTransfer?.files?.[0]
      if (file) suratDokterFile.value = file
    }
    const clearFile = () => {
      suratDokterFile.value = null
      if (fileInput.value) fileInput.value.value = ''
    }

    const fetchLeaves = async () => {
      loadingHistory.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('leaves/my')
        leaves.value = data.data
      } catch (_) {}
      finally { loadingHistory.value = false }
    }

    const submitLeave = async () => {
      formError.value   = ''
      formSuccess.value = ''
      if (!form.value.date) { formError.value = 'Pilih tanggal izin.'; return }
      if (!form.value.type) { formError.value = 'Pilih jenis izin.'; return }

      submitting.value = true
      try {
        // Wajib pakai FormData agar file bisa ter-upload
        const fd = new FormData()
        fd.append('date', form.value.date)
        fd.append('type', form.value.type)
        fd.append('reason', form.value.reason ?? '')
        if (suratDokterFile.value) {
          fd.append('surat_dokter', suratDokterFile.value)
        }

        const token = localStorage.getItem('api_token') ?? ''
        await axios.post(`${import.meta.env.VITE_APP_API_URL}/leaves`, fd, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'multipart/form-data',
          }
        })

        formSuccess.value = 'Izin berhasil dicatat! 🎉'
        form.value = { date: todayRaw, type: '', reason: '' }
        clearFile()
        await fetchLeaves()
      } catch (err: any) {
        formError.value = err?.response?.data?.message ?? 'Gagal mengajukan izin.'
      } finally { submitting.value = false }
    }

    const deleteLeave = async (id: number) => {
      if (!confirm('Batalkan izin ini?')) return
      try {
        ApiService.setHeader()
        await ApiService.delete(`leaves/${id}`)
        leaves.value = leaves.value.filter(l => l.id !== id)
      } catch (err: any) {
        alert(err?.response?.data?.message ?? 'Gagal membatalkan izin.')
      }
    }

    onMounted(() => { fetchLeaves() })

    return {
      today, todayRaw, minDate, form, submitting, formError, formSuccess,
      leaves, loadingHistory, suratDokterFile, fileInput,
      typeLabel, typeIcon, formatDate,
      triggerFileInput, onFileChange, onFileDrop, clearFile,
      submitLeave, deleteLeave
    }
  }
})
</script>

<style scoped>
.lv-wrap { max-width: 520px; margin: 0 auto; padding: 24px 16px; }
.lv-header { margin-bottom: 24px; }
.lv-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.lv-sub { font-size: 13px; color: #55555e; margin: 0; }

.lv-card { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 24px; }
.lv-section-title { font-size: 15px; font-weight: 700; color: #f0f0f5; margin: 0 0 20px; }

.lv-form { display: flex; flex-direction: column; gap: 14px; }
.lv-field { display: flex; flex-direction: column; gap: 6px; }
.lv-field label { font-size: 12px; font-weight: 600; color: #aaaabc; text-transform: uppercase; letter-spacing: 0.5px; }
.lv-optional { font-weight: 400; text-transform: none; color: #55555e; }
.lv-input {
  background: #0d0f14;
  border: 1.5px solid rgba(255,255,255,0.08);
  border-radius: 10px;
  color: #f0f0f5;
  padding: 10px 14px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.15s;
  width: 100%;
  box-sizing: border-box;
}
.lv-input:focus { border-color: rgba(27,132,255,0.5); }
.lv-input option { background: #15171e; }
.lv-textarea { resize: vertical; min-height: 80px; font-family: inherit; }

/* File upload */
.lv-file-wrap {
  background: #0d0f14;
  border: 1.5px dashed rgba(255,255,255,0.15);
  border-radius: 10px;
  padding: 14px 16px;
  cursor: pointer;
  transition: border-color 0.15s;
  display: flex;
  align-items: center;
}
.lv-file-wrap:hover { border-color: rgba(27,132,255,0.4); }
.lv-file-placeholder { display: flex; align-items: center; gap: 10px; color: #55555e; font-size: 13px; }
.lv-file-selected { display: flex; align-items: center; gap: 10px; color: #f0f0f5; font-size: 13px; width: 100%; }
.lv-file-icon { font-size: 20px; }
.lv-file-clear {
  margin-left: auto; background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.2);
  color: #ff6b6b; border-radius: 6px; padding: 2px 8px; font-size: 12px; cursor: pointer;
}

.lv-btn-submit {
  width: 100%; background: #e8262a; color: #fff;
  border: none; border-radius: 12px; padding: 13px;
  font-size: 15px; font-weight: 700; cursor: pointer;
  transition: background 0.15s, transform 0.1s;
}
.lv-btn-submit:hover:not(:disabled) { background: #ff3a3d; transform: translateY(-1px); }
.lv-btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }

.lv-loading { display: flex; align-items: center; justify-content: center; gap: 8px; }
@keyframes spin { to { transform: rotate(360deg); } }
.lv-spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; display: inline-block; }

.lv-error   { padding: 10px 14px; background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.2); border-radius: 10px; color: #ff6b6b; font-size: 13px; }
.lv-success { padding: 10px 14px; background: rgba(23,198,83,0.1);  border: 1px solid rgba(23,198,83,0.2);  border-radius: 10px; color: #17c653; font-size: 13px; }

/* List */
.lv-empty { text-align: center; padding: 32px; color: #55555e; }
.lv-empty-icon { font-size: 40px; margin-bottom: 8px; }
.lv-empty p { font-size: 14px; margin: 0; }

.lv-list { display: flex; flex-direction: column; gap: 10px; }
.lv-item {
  display: flex; align-items: center; justify-content: space-between;
  background: #0d0f14; border: 1px solid rgba(255,255,255,0.06);
  border-radius: 12px; padding: 14px 16px;
}
.lv-item-left { display: flex; align-items: flex-start; gap: 12px; }
.lv-item-icon { font-size: 24px; margin-top: 2px; }
.lv-item-type { font-size: 14px; font-weight: 600; color: #f0f0f5; }
.lv-item-date { font-size: 12px; color: #55555e; margin-top: 2px; }
.lv-item-reason { font-size: 12px; color: #aaaabc; margin-top: 4px; font-style: italic; }
.lv-item-surat { display: inline-block; margin-top: 4px; font-size: 12px; color: #1b84ff; text-decoration: none; }
.lv-item-surat:hover { text-decoration: underline; }
.lv-item-nosurat { font-size: 11px; color: #55555e; margin-top: 4px; display: inline-block; }

.lv-btn-cancel {
  background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.2);
  color: #ff6b6b; border-radius: 8px; padding: 6px 10px;
  font-size: 12px; cursor: pointer; transition: all 0.15s; flex-shrink: 0;
}
.lv-btn-cancel:hover { background: rgba(255,107,107,0.2); }
</style>