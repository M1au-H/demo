<template>
  <div class="ta-wrap">
    <div class="ta-header">
      <h2 class="ta-title">Laporan Absensi Bulanan</h2>
      <div class="ta-filter">
        <select v-model="selectedMonth" @change="load" class="ta-select">
          <option v-for="m in months" :key="m.val" :value="m.val">{{ m.label }}</option>
        </select>
        <select v-model="selectedYear" @change="load" class="ta-select">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="ta-loading">
      <span class="ta-spinner"></span> Memuat data...
    </div>

    <div v-else class="ta-table-wrap">
      <table class="ta-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Nama</th>
            <th>Jam Masuk</th>
            <th>Foto Masuk</th>
            <th>Jam Pulang</th>
            <th>Foto Pulang</th>
            <th>Status</th>
            <th>Pulang</th>
            <th>Terlambat</th>
            <th>Lembur</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="attendances.length === 0">
            <td colspan="10" class="ta-empty">Tidak ada data untuk periode ini</td>
          </tr>
          <tr v-for="item in attendances" :key="item.id">
            <td>{{ formatDate(item.date) }}</td>
            <td>
              <div class="ta-user">
                <img v-if="item.user?.avatar" :src="`/storage/${item.user.avatar}`" class="ta-avatar-img" />
                <div v-else class="ta-avatar">{{ item.user?.name?.charAt(0) }}</div>
                <span>{{ item.user?.name }}</span>
              </div>
            </td>
            <td>{{ item.check_in_time ?? '-' }}</td>
            <td>
              <button v-if="item.check_in_photo" @click="viewPhoto(item.id, 'in')" class="ta-btn-photo">
                📷 Lihat
              </button>
              <span v-else class="ta-muted">-</span>
            </td>
            <td>{{ item.check_out_time ?? '-' }}</td>
            <td>
              <button v-if="item.check_out_photo" @click="viewPhoto(item.id, 'out')" class="ta-btn-photo">
                📷 Lihat
              </button>
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
          <div v-if="photoModal.loading" class="ta-photo-loading">Memuat foto...</div>
          <div v-else-if="photoModal.error" class="ta-photo-error">{{ photoModal.error }}</div>
          <img v-else :src="photoModal.url" class="ta-photo-img" />
        </div>
      </div>
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue'
import ApiService from '@/core/services/ApiService'
import JwtService from '@/core/services/JwtService'

export default defineComponent({
  name: 'MonthlyAttendance',
  setup() {
    const attendances   = ref<any[]>([])
    const loading       = ref(true)
    const selectedMonth = ref(new Date().getMonth() + 1)
    const selectedYear  = ref(new Date().getFullYear())

    const photoModal = ref({
      show: false, loading: false, error: '', url: '', type: 'in',
    })

    const months = [
      { val: 1, label: 'Januari' }, { val: 2, label: 'Februari' },
      { val: 3, label: 'Maret' },   { val: 4, label: 'April' },
      { val: 5, label: 'Mei' },     { val: 6, label: 'Juni' },
      { val: 7, label: 'Juli' },    { val: 8, label: 'Agustus' },
      { val: 9, label: 'September' },{ val: 10, label: 'Oktober' },
      { val: 11, label: 'November' },{ val: 12, label: 'Desember' },
    ]
    const years = [2024, 2025, 2026, 2027]

    const load = async () => {
      loading.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get(`admin/attendance/monthly?month=${selectedMonth.value}&year=${selectedYear.value}`)
        attendances.value = data.data
      } catch (_) {} finally { loading.value = false }
    }

    const viewPhoto = async (attendanceId: number, type: 'in' | 'out') => {
      photoModal.value = { show: true, loading: true, error: '', url: '', type }
      try {
        const token = JwtService.getToken()
        const response = await fetch(
          `http://127.0.0.1:8000/api/attendance/photo/${attendanceId}/${type}`,
          { headers: { Authorization: `Bearer ${token}` } }
        )
        if (!response.ok) throw new Error('Foto tidak ditemukan')
        const blob = await response.blob()
        photoModal.value.url = URL.createObjectURL(blob)
        photoModal.value.loading = false
      } catch (e: any) {
        photoModal.value.loading = false
        photoModal.value.error = e.message ?? 'Gagal memuat foto'
      }
    }

    const closePhoto = () => {
      if (photoModal.value.url) URL.revokeObjectURL(photoModal.value.url)
      photoModal.value = { show: false, loading: false, error: '', url: '', type: 'in' }
    }

    onMounted(load)

    const formatDate    = (d: string) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
    const checkoutClass = (s: string) => s === 'overtime' ? 'badge-blue' : s === 'early_leave' ? 'badge-orange' : 'badge-ontime'
    const checkoutLabel = (s: string) => s === 'overtime' ? 'Lembur' : s === 'early_leave' ? 'Cepat' : 'Normal'

    return {
      attendances, loading, selectedMonth, selectedYear, months, years,
      load, formatDate, checkoutClass, checkoutLabel,
      photoModal, viewPhoto, closePhoto,
    }
  }
})
</script>

<style scoped>
.ta-wrap { padding: 24px; }
.ta-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.ta-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0; }
.ta-filter { display: flex; gap: 8px; }
.ta-select { background: #15171e; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 8px; color: #aaaabc; padding: 8px 12px; font-size: 13px; cursor: pointer; outline: none; }
.ta-select:focus { border-color: #1b84ff; }
.ta-loading { color: #55555e; display: flex; align-items: center; gap: 8px; padding: 32px 0; }
@keyframes spin { to { transform: rotate(360deg); } }
.ta-spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.1); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; display: inline-block; }
.ta-table-wrap { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; overflow: auto; }
.ta-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.ta-table th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; border-bottom: 1px solid rgba(255,255,255,0.06); }
.ta-table td { padding: 12px 16px; font-size: 13px; color: #aaaabc; border-bottom: 1px solid rgba(255,255,255,0.04); }
.ta-table tr:last-child td { border-bottom: none; }
.ta-empty { text-align: center; color: #3a3a48; padding: 32px !important; }
.ta-muted { color: #3a3a48 !important; }
.ta-user { display: flex; align-items: center; gap: 8px; }
.ta-avatar { width: 28px; height: 28px; border-radius: 50%; background: #e8262a; color: #fff; font-weight: 700; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ta-avatar-img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }

/* Tombol foto */
.ta-btn-photo {
  background: rgba(27,132,255,0.15); color: #1b84ff;
  border: 1px solid rgba(27,132,255,0.2); border-radius: 8px;
  padding: 4px 10px; font-size: 12px; cursor: pointer;
  transition: background 0.15s; white-space: nowrap;
}
.ta-btn-photo:hover { background: rgba(27,132,255,0.25); }

/* Badge */
.badge-late   { background: rgba(255,107,107,0.15); color: #ff6b6b; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-ontime { background: rgba(23,198,83,0.15);  color: #17c653; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-blue   { background: rgba(27,132,255,0.15); color: #1b84ff; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-orange { background: rgba(255,165,0,0.15);  color: #ffa500; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }

/* Modal */
.ta-modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.8);
  display: flex; align-items: center; justify-content: center; z-index: 9999;
}
.ta-modal {
  background: #15171e; border: 1px solid rgba(255,255,255,0.1);
  border-radius: 16px; width: 90%; max-width: 480px; overflow: hidden;
}
.ta-modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.08);
  font-size: 14px; font-weight: 600; color: #f0f0f5;
}
.ta-modal-close {
  background: none; border: none; color: #55555e;
  font-size: 16px; cursor: pointer; padding: 4px 8px;
  border-radius: 6px; transition: color 0.15s;
}
.ta-modal-close:hover { color: #f0f0f5; }
.ta-modal-body {
  padding: 20px; display: flex; justify-content: center;
  align-items: center; min-height: 200px;
}
.ta-photo-img { width: 100%; border-radius: 10px; object-fit: contain; max-height: 60vh; }
.ta-photo-loading { color: #55555e; font-size: 14px; }
.ta-photo-error { color: #ff6b6b; font-size: 14px; }
</style>