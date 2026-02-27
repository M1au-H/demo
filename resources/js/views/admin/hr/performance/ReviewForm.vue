<template>
  <div class="rf-wrap">
    <div class="rf-header">
      <h2 class="rf-title">Penilaian Performa</h2>
      <p class="rf-sub">Beri nilai harian untuk pegawai</p>
    </div>

    <div class="rf-grid">
      <!-- Form penilaian -->
      <div class="rf-form-card">
        <h3 class="rf-card-title">Beri Nilai</h3>

        <!-- Pilih pegawai -->
        <div class="rf-field">
          <label class="rf-label">Pilih Pegawai</label>
          <select v-model="form.user_id" class="rf-select">
            <option value="">-- Pilih pegawai --</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
              {{ emp.name }} {{ emp.position ? `(${emp.position.name})` : '' }}
            </option>
          </select>
        </div>

        <!-- Rating bintang -->
        <div class="rf-field">
          <label class="rf-label">Rating (1-5)</label>
          <div class="rf-stars">
            <button
              v-for="n in 5" :key="n"
              @click="form.rating = n"
              :class="['rf-star', n <= form.rating ? 'active' : '']"
              type="button"
            >★</button>
          </div>
          <p class="rf-rating-label">{{ ratingLabel }}</p>
        </div>

        <!-- Komentar -->
        <div class="rf-field">
          <label class="rf-label">Komentar (opsional)</label>
          <textarea v-model="form.comment" class="rf-textarea" rows="3" placeholder="Catatan performa hari ini..."></textarea>
        </div>

        <div v-if="message" :class="['rf-msg', msgType]">{{ message }}</div>

        <button @click="submit" :disabled="loading || !form.user_id || !form.rating" class="rf-btn-submit">
          <span v-if="!loading">💾 Simpan Penilaian</span>
          <span v-else>Menyimpan...</span>
        </button>
      </div>

      <!-- Riwayat penilaian -->
      <div class="rf-history-card">
        <h3 class="rf-card-title">Riwayat Penilaian</h3>

        <select v-model="historyUserId" @change="loadHistory" class="rf-select" style="margin-bottom:16px">
          <option value="">-- Pilih pegawai --</option>
          <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
        </select>

        <div v-if="loadingHistory" class="rf-loading">Memuat...</div>
        <div v-else-if="reviews.length === 0 && historyUserId" class="rf-empty">Belum ada penilaian</div>

        <div v-for="r in reviews" :key="r.id" class="rf-review-item">
          <div class="rf-review-top">
            <span class="rf-review-date">{{ formatDate(r.review_date) }}</span>
            <span class="rf-review-stars">{{ '★'.repeat(r.rating) }}{{ '☆'.repeat(5 - r.rating) }}</span>
          </div>
          <p v-if="r.comment" class="rf-review-comment">{{ r.comment }}</p>
          <p class="rf-review-by">oleh {{ r.reviewer?.name }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'ReviewForm',
  setup() {
    const employees     = ref<any[]>([])
    const reviews       = ref<any[]>([])
    const loading       = ref(false)
    const loadingHistory = ref(false)
    const historyUserId = ref('')
    const message       = ref('')
    const msgType       = ref('rf-msg-success')

    const form = ref({ user_id: '', rating: 0, comment: '' })

    const ratingLabel = computed(() => {
      const labels = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik']
      return labels[form.value.rating] ?? ''
    })

    const loadEmployees = async () => {
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('admin/employees')
        employees.value = data.data
      } catch (_) {}
    }

    const loadHistory = async () => {
      if (!historyUserId.value) return
      loadingHistory.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get(`admin/performance/${historyUserId.value}`)
        reviews.value = data.data
      } catch (_) {} finally { loadingHistory.value = false }
    }

    const submit = async () => {
      loading.value = true; message.value = ''
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/performance/${form.value.user_id}`, {
          rating: form.value.rating,
          comment: form.value.comment,
        })
        message.value = 'Penilaian berhasil disimpan!'
        msgType.value  = 'rf-msg-success'
        form.value = { user_id: '', rating: 0, comment: '' }
      } catch (err: any) {
        message.value = err?.response?.data?.message ?? 'Gagal menyimpan'
        msgType.value  = 'rf-msg-error'
      } finally { loading.value = false }
    }

    const formatDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })

    onMounted(loadEmployees)

    return { employees, reviews, loading, loadingHistory, historyUserId, message, msgType, form, ratingLabel, loadHistory, submit, formatDate }
  }
})
</script>

<style scoped>
.rf-wrap { padding: 24px; }
.rf-header { margin-bottom: 24px; }
.rf-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.rf-sub { font-size: 13px; color: #55555e; margin: 0; }
.rf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.rf-form-card, .rf-history-card { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 24px; }
.rf-card-title { font-size: 15px; font-weight: 700; color: #f0f0f5; margin: 0 0 20px; }
.rf-field { margin-bottom: 16px; }
.rf-label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; margin-bottom: 8px; }
.rf-select { width: 100%; background: #0d0f14; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px; color: #e2e2e8; padding: 10px 14px; font-size: 13px; outline: none; cursor: pointer; }
.rf-select:focus { border-color: #1b84ff; }
.rf-textarea { width: 100%; background: #0d0f14; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px; color: #e2e2e8; padding: 10px 14px; font-size: 13px; outline: none; resize: vertical; font-family: inherit; }
.rf-textarea:focus { border-color: #1b84ff; }
.rf-stars { display: flex; gap: 6px; margin-bottom: 6px; }
.rf-star { background: none; border: none; font-size: 28px; color: #3a3a48; cursor: pointer; transition: color 0.1s; padding: 0; line-height: 1; }
.rf-star.active { color: #fbbf24; }
.rf-star:hover { color: #fbbf24; }
.rf-rating-label { font-size: 12px; color: #55555e; margin: 0; }
.rf-btn-submit { width: 100%; background: #e8262a; color: #fff; border: none; border-radius: 10px; padding: 13px; font-size: 14px; font-weight: 700; cursor: pointer; margin-top: 8px; transition: background 0.15s; }
.rf-btn-submit:hover:not(:disabled) { background: #ff3a3d; }
.rf-btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }
.rf-msg { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }
.rf-msg-success { background: rgba(23,198,83,0.1); color: #17c653; border: 1px solid rgba(23,198,83,0.2); }
.rf-msg-error { background: rgba(255,107,107,0.1); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.2); }
.rf-loading { color: #55555e; font-size: 13px; }
.rf-empty { color: #3a3a48; font-size: 13px; text-align: center; padding: 24px 0; }
.rf-review-item { padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
.rf-review-item:last-child { border-bottom: none; }
.rf-review-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.rf-review-date { font-size: 12px; color: #55555e; }
.rf-review-stars { color: #fbbf24; font-size: 14px; letter-spacing: 1px; }
.rf-review-comment { font-size: 13px; color: #aaaabc; margin: 4px 0; }
.rf-review-by { font-size: 11px; color: #3a3a48; margin: 0; }
@media (max-width: 768px) { .rf-grid { grid-template-columns: 1fr; } }
</style>