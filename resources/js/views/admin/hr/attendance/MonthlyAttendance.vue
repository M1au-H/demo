<template>
  <div class="ta-wrap">
    <div class="ta-header">
      <div>
        <h2 class="ta-title">Laporan Absensi Bulanan</h2>
        <p class="ta-sub">{{ attendances.length }} record ditemukan</p>
      </div>
      <div class="ta-filter">
        <select v-model="selectedMonth" @change="load" class="ta-select">
          <option v-for="m in months" :key="m.val" :value="m.val">{{ m.label }}</option>
        </select>
        <select v-model="selectedYear" @change="load" class="ta-select">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
        <button @click="load" class="ta-btn-refresh" :class="{ spinning: loading }">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Summary mini -->
    <div class="ta-summary-mini" v-if="!loading && !error && attendances.length > 0">
      <div class="ta-mini-stat"><span class="ta-mini-val">{{ totalHadir }}</span><span class="ta-mini-label">Total Hadir</span></div>
      <div class="ta-mini-stat red"><span class="ta-mini-val">{{ totalTerlambat }}</span><span class="ta-mini-label">Terlambat</span></div>
      <div class="ta-mini-stat blue"><span class="ta-mini-val">{{ totalLembur }}</span><span class="ta-mini-label">Lembur</span></div>
      <div class="ta-mini-stat orange"><span class="ta-mini-val">{{ totalCepat }}</span><span class="ta-mini-label">Pulang Cepat</span></div>
    </div>

    <div v-if="loading" class="ta-loading">
      <span class="ta-spinner"></span> Memuat data...
    </div>

    <div v-else-if="error" class="ta-error-box">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ error }}
      <button @click="load" class="ta-retry-btn">🔄 Coba Lagi</button>
    </div>

    <div v-else class="ta-table-wrap">
      <table class="ta-table">
        <thead>
          <tr>
            <th>Tanggal</th><th>Nama</th><th>Jam Masuk</th><th>Foto Masuk</th>
            <th>Jam Pulang</th><th>Foto Pulang</th><th>Status</th><th>Pulang</th>
            <th>Terlambat</th><th>Lembur</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="attendances.length === 0">
            <td colspan="10" class="ta-empty">Tidak ada data untuk periode ini</td>
          </tr>
          <tr v-for="item in attendances" :key="item.id">
            <td class="ta-date-col">{{ formatDate(item.date) }}</td>
            <td>
              <div class="ta-user">
                <img v-if="item.user?.avatar" :src="item.user.avatar" class="ta-avatar-img" />
                <div v-else class="ta-avatar">{{ item.user?.name?.charAt(0)?.toUpperCase() }}</div>
                <span>{{ item.user?.name }}</span>
              </div>
            </td>
            <td>{{ formatTime(item.check_in_time) }}</td>
            <td>
              <button v-if="item.check_in_photo" @click="viewPhoto(item.id, 'in')" class="ta-btn-photo">📷 Lihat</button>
              <span v-else class="ta-muted">-</span>
            </td>
            <td>{{ formatTime(item.check_out_time) }}</td>
            <td>
              <button v-if="item.check_out_photo" @click="viewPhoto(item.id, 'out')" class="ta-btn-photo">📷 Lihat</button>
              <span v-else class="ta-muted">-</span>
            </td>
            <td><span :class="item.status === 'late' ? 'badge-late' : 'badge-ontime'">{{ item.status === 'late' ? 'Terlambat' : 'Tepat Waktu' }}</span></td>
            <td>
              <span v-if="item.checkout_status" :class="checkoutClass(item.checkout_status)">{{ checkoutLabel(item.checkout_status) }}</span>
              <span v-else class="ta-muted">-</span>
            </td>
            <td class="ta-muted">{{ item.late_minutes > 0 ? item.late_minutes + ' mnt' : '-' }}</td>
            <td class="ta-muted">{{ item.overtime_minutes > 0 ? item.overtime_minutes + ' mnt' : '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Foto -->
    <div v-if="photoModal.show" class="ta-modal-overlay" @click.self="closePhoto">
      <div class="ta-modal">
        <div class="ta-modal-header">
          <span>{{ photoModal.type === 'in' ? '📷 Foto Absen Masuk' : '📷 Foto Absen Pulang' }}</span>
          <button @click="closePhoto" class="ta-modal-close">✕</button>
        </div>
        <div class="ta-modal-body">
          <div v-if="photoModal.loading" class="ta-photo-loading"><span class="ta-spinner"></span> Memuat foto...</div>
          <div v-else-if="photoModal.error" class="ta-photo-error">⚠️ {{ photoModal.error }}</div>
          <img v-else :src="photoModal.url" class="ta-photo-img" />
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue'
import axios from 'axios'

const API_BASE = (import.meta.env.VITE_APP_API_URL as string || '/api').replace(/\/$/, '')

function getToken(): string {
  return localStorage.getItem('id_token')
    || localStorage.getItem('api_token')
    || localStorage.getItem('token')
    || sessionStorage.getItem('id_token')
    || ''
}

export default defineComponent({
  name: 'MonthlyAttendance',
  setup() {
    const attendances   = ref<any[]>([])
    const loading       = ref(true)
    const error         = ref('')
    const selectedMonth = ref(new Date().getMonth() + 1)
    const selectedYear  = ref(new Date().getFullYear())
    const photoModal    = ref({ show: false, loading: false, error: '', url: '', type: 'in' })

    const months = [
      { val: 1, label: 'Januari' }, { val: 2, label: 'Februari' }, { val: 3, label: 'Maret' },
      { val: 4, label: 'April' },   { val: 5, label: 'Mei' },      { val: 6, label: 'Juni' },
      { val: 7, label: 'Juli' },    { val: 8, label: 'Agustus' },  { val: 9, label: 'September' },
      { val: 10, label: 'Oktober' },{ val: 11, label: 'November' },{ val: 12, label: 'Desember' },
    ]
    const years = [2024, 2025, 2026, 2027]

    const load = async () => {
      loading.value = true
      error.value   = ''
      const token   = getToken()

      if (!token) {
        error.value   = 'Token tidak ditemukan. Silakan login ulang.'
        loading.value = false
        return
      }

      const url = `${API_BASE}/admin/attendance/monthly?month=${selectedMonth.value}&year=${selectedYear.value}`

      try {
        const { data } = await axios.get(url, {
          headers: { Authorization: `Bearer ${token}` }
        })
        attendances.value = data.data ?? []
      } catch (e: any) {
        const status = e?.response?.status
        console.error('[MonthlyAttendance]', e?.response?.data ?? e)

        if (status === 401) {
          // Fallback ke Token prefix
          try {
            const { data } = await axios.get(url, {
              headers: { Authorization: `Token ${token}` }
            })
            attendances.value = data.data ?? []
          } catch (e2: any) {
            error.value = `401 Unauthorized — cek token atau role admin. (${e2?.response?.data?.message ?? ''})`
          }
        } else if (status === 403) {
          error.value = '403 Forbidden — akun ini bukan admin.'
        } else {
          error.value = `Gagal memuat: ${e?.response?.data?.message ?? e?.message ?? 'Network error'} (HTTP ${status ?? 'err'})`
        }
      } finally {
        loading.value = false
      }
    }

    const viewPhoto = async (attendanceId: number, type: 'in' | 'out') => {
      photoModal.value = { show: true, loading: true, error: '', url: '', type }
      const token = getToken()
      try {
        let response = await fetch(`${API_BASE}/admin/attendance/photo/${attendanceId}/${type}`, {
          headers: { Authorization: `Bearer ${token}` }
        })
        if (response.status === 401) {
          response = await fetch(`${API_BASE}/admin/attendance/photo/${attendanceId}/${type}`, {
            headers: { Authorization: `Token ${token}` }
          })
        }
        if (!response.ok) throw new Error((await response.json().catch(() => ({}))).message ?? `HTTP ${response.status}`)
        const blob = await response.blob()
        photoModal.value.url     = URL.createObjectURL(blob)
        photoModal.value.loading = false
      } catch (e: any) {
        photoModal.value.loading = false
        photoModal.value.error   = e.message ?? 'Gagal memuat foto'
      }
    }

    const closePhoto = () => {
      if (photoModal.value.url) URL.revokeObjectURL(photoModal.value.url)
      photoModal.value = { show: false, loading: false, error: '', url: '', type: 'in' }
    }

    const totalHadir     = computed(() => attendances.value.length)
    const totalTerlambat = computed(() => attendances.value.filter(a => a.status === 'late').length)
    const totalLembur    = computed(() => attendances.value.filter(a => a.checkout_status === 'overtime').length)
    const totalCepat     = computed(() => attendances.value.filter(a => a.checkout_status === 'early_leave').length)

    onMounted(load)

    const formatTime    = (t: string | null) => t ? t.substring(0, 5) : '-'
    const formatDate    = (d: string) => new Date(d).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' })
    const checkoutClass = (s: string) => s === 'overtime' ? 'badge-blue' : s === 'early_leave' ? 'badge-orange' : 'badge-ontime'
    const checkoutLabel = (s: string) => s === 'overtime' ? 'Lembur' : s === 'early_leave' ? 'Pulang Cepat' : 'Normal'

    return {
      attendances, loading, error, selectedMonth, selectedYear, months, years,
      load, formatDate, formatTime, checkoutClass, checkoutLabel,
      totalHadir, totalTerlambat, totalLembur, totalCepat,
      photoModal, viewPhoto, closePhoto,
    }
  }
})
</script>

<style scoped>
.ta-wrap { padding: 24px; }
.ta-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.ta-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.ta-sub   { font-size: 12px; color: #55555e; margin: 0; }
.ta-filter { display: flex; gap: 8px; align-items: center; }
.ta-select { background: #15171e; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 8px; color: #aaaabc; padding: 8px 12px; font-size: 13px; cursor: pointer; outline: none; }
.ta-btn-refresh { width: 36px; height: 36px; border-radius: 8px; border: 1.5px solid rgba(255,255,255,0.08); background: #15171e; color: #55555e; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.ta-btn-refresh.spinning svg { animation: spin 0.65s linear infinite; }
.ta-error-box { display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.2); border-radius: 10px; color: #ff6b6b; font-size: 13px; margin-bottom: 16px; flex-wrap: wrap; }
.ta-retry-btn { margin-left: auto; padding: 5px 14px; background: rgba(255,107,107,0.12); border: 1px solid rgba(255,107,107,0.3); border-radius: 8px; color: #ff6b6b; font-size: 12px; cursor: pointer; }
.ta-summary-mini { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.ta-mini-stat { display: flex; flex-direction: column; align-items: center; background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 12px 20px; min-width: 90px; }
.ta-mini-stat.red    { border-color: rgba(255,107,107,0.2); }
.ta-mini-stat.blue   { border-color: rgba(27,132,255,0.2); }
.ta-mini-stat.orange { border-color: rgba(255,165,0,0.2); }
.ta-mini-val   { font-size: 24px; font-weight: 800; color: #f0f0f5; line-height: 1; }
.ta-mini-stat.red    .ta-mini-val { color: #ff6b6b; }
.ta-mini-stat.blue   .ta-mini-val { color: #1b84ff; }
.ta-mini-stat.orange .ta-mini-val { color: #ffa500; }
.ta-mini-label { font-size: 11px; color: #55555e; margin-top: 4px; white-space: nowrap; }
.ta-loading { color: #55555e; display: flex; align-items: center; gap: 8px; padding: 32px 0; }
@keyframes spin { to { transform: rotate(360deg); } }
.ta-spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.1); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; display: inline-block; flex-shrink: 0; }
.ta-table-wrap { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; overflow: auto; }
.ta-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.ta-table th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; border-bottom: 1px solid rgba(255,255,255,0.06); white-space: nowrap; }
.ta-table td { padding: 12px 16px; font-size: 13px; color: #aaaabc; border-bottom: 1px solid rgba(255,255,255,0.04); }
.ta-table tr:last-child td { border-bottom: none; }
.ta-table tbody tr:hover td { background: rgba(255,255,255,0.02); }
.ta-empty { text-align: center; color: #3a3a48; padding: 40px !important; }
.ta-muted { color: #3a3a48 !important; }
.ta-date-col { white-space: nowrap; font-weight: 600; color: #f0f0f5 !important; }
.ta-user { display: flex; align-items: center; gap: 8px; }
.ta-avatar { width: 28px; height: 28px; border-radius: 50%; background: #e8262a; color: #fff; font-weight: 700; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ta-avatar-img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.ta-btn-photo { background: rgba(27,132,255,0.15); color: #1b84ff; border: 1px solid rgba(27,132,255,0.2); border-radius: 8px; padding: 4px 10px; font-size: 12px; cursor: pointer; white-space: nowrap; }
.ta-btn-photo:hover { background: rgba(27,132,255,0.28); }
.badge-late   { background: rgba(255,107,107,0.15); color: #ff6b6b; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.badge-ontime { background: rgba(23,198,83,0.15);  color: #17c653; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.badge-blue   { background: rgba(27,132,255,0.15); color: #1b84ff; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.badge-orange { background: rgba(255,165,0,0.15);  color: #ffa500; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.ta-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(4px); }
.ta-modal { background: #15171e; border: 1px solid rgba(255,255,255,0.12); border-radius: 18px; width: 90%; max-width: 520px; overflow: hidden; animation: modal-in 0.2s ease; }
@keyframes modal-in { from { opacity:0; transform:scale(0.95) } to { opacity:1; transform:scale(1) } }
.ta-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 14px; font-weight: 600; color: #f0f0f5; }
.ta-modal-close { background: rgba(255,255,255,0.06); border: none; color: #aaaabc; font-size: 14px; cursor: pointer; padding: 6px 10px; border-radius: 8px; }
.ta-modal-body { padding: 24px; display: flex; justify-content: center; align-items: center; min-height: 220px; }
.ta-photo-img { width: 100%; border-radius: 12px; object-fit: contain; max-height: 60vh; }
.ta-photo-loading { color: #55555e; font-size: 14px; display: flex; align-items: center; gap: 10px; }
.ta-photo-error { color: #ff6b6b; font-size: 14px; text-align: center; }
</style>