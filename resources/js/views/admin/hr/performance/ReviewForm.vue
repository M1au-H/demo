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
          <div class="rf-select-wrap">
            <select v-model="form.user_id" @change="onEmployeeChange" class="rf-select" :disabled="loadingToday">
              <option value="">-- Pilih pegawai --</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                {{ emp.name }} {{ emp.position ? `(${emp.position.name})` : '' }}
              </option>
            </select>
            <span v-if="loadingToday" class="rf-spin-inline"></span>
          </div>
        </div>

        <!-- Status absensi pegawai -->
        <div v-if="attendanceStatus && form.user_id" class="rf-attendance-status" :class="attendanceStatus.type">
          <div class="rf-attendance-icon">
            <svg v-if="attendanceStatus.type === 'present'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <div class="rf-attendance-text">
            <span class="rf-attendance-title">{{ attendanceStatus.title }}</span>
            <span class="rf-attendance-desc">{{ attendanceStatus.desc }}</span>
          </div>
        </div>

        <!-- Form aktif jika sudah absen atau belum pilih -->
        <template v-if="!form.user_id || attendanceStatus?.type === 'present'">
          <div class="rf-field">
            <label class="rf-label">Rating (1-5)</label>
            <div class="rf-stars">
              <button
                v-for="n in 5"
                :key="n"
                @click="setRating(n)"
                :class="['rf-star', n <= form.rating ? 'active' : '']"
                type="button"
                :disabled="!form.user_id"
              >★</button>
            </div>
            <p class="rf-rating-label">{{ ratingLabel }}</p>
          </div>

          <div class="rf-field">
            <label class="rf-label">Komentar (opsional)</label>
            <textarea
              v-model="form.comment"
              class="rf-textarea"
              rows="3"
              placeholder="Catatan performa hari ini..."
              :disabled="!form.user_id"
            ></textarea>
          </div>

          <div v-if="message" :class="['rf-msg', msgType]">{{ message }}</div>

          <button @click="submit" :disabled="loading || !form.user_id || !form.rating" class="rf-btn-submit">
            <span v-if="!loading">💾 Simpan Penilaian</span>
            <span v-else>Menyimpan...</span>
          </button>
        </template>

        <!-- Form disabled jika belum absen -->
        <template v-else-if="attendanceStatus?.type === 'absent'">
          <div class="rf-field rf-field-disabled">
            <label class="rf-label">Rating (1-5)</label>
            <div class="rf-stars">
              <button v-for="n in 5" :key="n" class="rf-star" type="button" disabled>★</button>
            </div>
            <p class="rf-rating-label">–</p>
          </div>
          <div class="rf-field rf-field-disabled">
            <label class="rf-label">Komentar (opsional)</label>
            <textarea class="rf-textarea" rows="3" placeholder="Catatan performa hari ini..." disabled></textarea>
          </div>
          <button class="rf-btn-submit" disabled>💾 Simpan Penilaian</button>
        </template>

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
    const employees      = ref<any[]>([])
    const reviews        = ref<any[]>([])
    const loading        = ref(false)
    const loadingHistory = ref(false)
    const loadingToday   = ref(false)
    const historyUserId  = ref('')
    const message        = ref('')
    const msgType        = ref('rf-msg-success')
    const todayAttendances = ref<any[]>([])
    const attendanceStatus = ref<{ type: 'present' | 'absent'; title: string; desc: string } | null>(null)

    const form = ref({ user_id: '', rating: 0, comment: '' })

    const ratingLabel = computed(() => {
      const labels = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik']
      return labels[form.value.rating] ?? ''
    })

    // ✅ Pindahkan logic ke method terpisah agar tidak error di template
    const setRating = (n: number) => {
      if (form.value.user_id) form.value.rating = n
    }

    const loadEmployees = async () => {
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('admin/employees')
        employees.value = data.data
      } catch (_) {}
    }

    const loadTodayAttendances = async () => {
      loadingToday.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('admin/attendance/today')
        todayAttendances.value = data.data ?? []
      } catch (_) {
        todayAttendances.value = []
      } finally {
        loadingToday.value = false
      }
    }

    const onEmployeeChange = () => {
      attendanceStatus.value = null
      message.value          = ''
      form.value.rating      = 0
      form.value.comment     = ''

      if (!form.value.user_id) return

      const userId = Number(form.value.user_id)
      const att = todayAttendances.value.find(
        (a: any) => Number(a.user_id) === userId || Number(a.user?.id) === userId
      )

      if (att && att.check_in_time) {
        const statusLabel = att.status === 'late'
          ? `Terlambat ${att.late_minutes} menit`
          : 'Tepat Waktu'
        attendanceStatus.value = {
          type:  'present',
          title: 'Sudah absen hari ini',
          desc:  `Jam masuk: ${att.check_in_time} · ${statusLabel}`,
        }
      } else {
        attendanceStatus.value = {
          type:  'absent',
          title: 'Pegawai belum absen hari ini',
          desc:  'Penilaian performa hanya bisa diberikan jika pegawai sudah melakukan absensi masuk.',
        }
      }
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
      if (!attendanceStatus.value || attendanceStatus.value.type === 'absent') {
        message.value = 'Tidak dapat menyimpan. Pegawai belum absen hari ini.'
        msgType.value  = 'rf-msg-error'
        return
      }
      loading.value = true; message.value = ''
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/performance/${form.value.user_id}`, {
          rating:  form.value.rating,
          comment: form.value.comment,
        })
        message.value = 'Penilaian berhasil disimpan!'
        msgType.value  = 'rf-msg-success'
        form.value = { user_id: '', rating: 0, comment: '' }
        attendanceStatus.value = null
      } catch (err: any) {
        message.value = err?.response?.data?.message ?? 'Gagal menyimpan'
        msgType.value  = 'rf-msg-error'
      } finally { loading.value = false }
    }

    const formatDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })

    onMounted(async () => {
      await Promise.all([loadEmployees(), loadTodayAttendances()])
    })

    return {
      employees, reviews, loading, loadingHistory, loadingToday,
      historyUserId, message, msgType, form, ratingLabel,
      attendanceStatus,
      setRating, onEmployeeChange, loadHistory, submit, formatDate,
    }
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
.rf-field-disabled { opacity: 0.35; pointer-events: none; }
.rf-label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; margin-bottom: 8px; }
.rf-select-wrap { position: relative; display: flex; align-items: center; gap: 8px; }
.rf-select { width: 100%; background: #0d0f14; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px; color: #e2e2e8; padding: 10px 14px; font-size: 13px; outline: none; cursor: pointer; }
.rf-select:focus { border-color: #1b84ff; }
.rf-select:disabled { opacity: 0.5; cursor: not-allowed; }
.rf-textarea { width: 100%; background: #0d0f14; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px; color: #e2e2e8; padding: 10px 14px; font-size: 13px; outline: none; resize: vertical; font-family: inherit; box-sizing: border-box; }
.rf-textarea:focus { border-color: #1b84ff; }
.rf-textarea:disabled { opacity: 0.4; cursor: not-allowed; }
.rf-stars { display: flex; gap: 6px; margin-bottom: 6px; }
.rf-star { background: none; border: none; font-size: 28px; color: #3a3a48; cursor: pointer; transition: color 0.1s; padding: 0; line-height: 1; }
.rf-star.active { color: #fbbf24; }
.rf-star:hover:not(:disabled) { color: #fbbf24; }
.rf-star:disabled { cursor: not-allowed; }
.rf-rating-label { font-size: 12px; color: #55555e; margin: 0; }
.rf-attendance-status { display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; }
.rf-attendance-status.present { background: rgba(23,198,83,0.08); border: 1px solid rgba(23,198,83,0.2); }
.rf-attendance-status.absent  { background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.2); }
.rf-attendance-icon { flex-shrink: 0; margin-top: 1px; }
.rf-attendance-status.present .rf-attendance-icon { color: #17c653; }
.rf-attendance-status.absent  .rf-attendance-icon { color: #ff6b6b; }
.rf-attendance-text { display: flex; flex-direction: column; gap: 2px; }
.rf-attendance-title { font-size: 13px; font-weight: 700; }
.rf-attendance-status.present .rf-attendance-title { color: #17c653; }
.rf-attendance-status.absent  .rf-attendance-title { color: #ff6b6b; }
.rf-attendance-desc { font-size: 12px; color: #72727a; line-height: 1.5; }
@keyframes rf-spin { to { transform: rotate(360deg); } }
.rf-spin-inline { display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.1); border-top-color: #aaaabc; border-radius: 50%; animation: rf-spin 0.6s linear infinite; flex-shrink: 0; }
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