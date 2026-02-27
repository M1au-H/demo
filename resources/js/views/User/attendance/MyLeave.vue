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

export default defineComponent({
  name: 'MyLeave',
  setup() {
    const today    = new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
    const todayRaw = new Date().toISOString().split('T')[0]
    const minDate  = todayRaw

    const form = ref({ date: todayRaw, type: '', reason: '' })
    const submitting   = ref(false)
    const formError    = ref('')
    const formSuccess  = ref('')
    const leaves       = ref<any[]>([])
    const loadingHistory = ref(true)

    const typeLabel = (t: string) => ({ sakit: 'Sakit', cuti: 'Cuti Tahunan', keluarga: 'Keperluan Keluarga', mendadak: 'Izin Mendadak' }[t] ?? t)
    const typeIcon  = (t: string) => ({ sakit: '🤒', cuti: '🏖️', keluarga: '👨‍👩‍👧', mendadak: '⚡' }[t] ?? '📝')
    const formatDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

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
        ApiService.setHeader()
        await ApiService.post('leaves', form.value)
        formSuccess.value = 'Izin berhasil dicatat! 🎉'
        form.value = { date: todayRaw, type: '', reason: '' }
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

    return { today, todayRaw, minDate, form, submitting, formError, formSuccess, leaves, loadingHistory, typeLabel, typeIcon, formatDate, submitLeave, deleteLeave }
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

.lv-btn-cancel {
  background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.2);
  color: #ff6b6b; border-radius: 8px; padding: 6px 10px;
  font-size: 12px; cursor: pointer; transition: all 0.15s; flex-shrink: 0;
}
.lv-btn-cancel:hover { background: rgba(255,107,107,0.2); }
</style>