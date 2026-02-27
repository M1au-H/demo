<template>
  <div class="ta-wrap">
    <div class="ta-header">
      <h2 class="ta-title">Absensi Hari Ini</h2>
      <p class="ta-sub">{{ today }}</p>
    </div>

    <!-- Summary cards -->
    <div class="ta-summary">
      <div class="ta-card-stat">
        <div class="ta-stat-num">{{ summary.total_hadir }}</div>
        <div class="ta-stat-label">Total Hadir</div>
      </div>
      <div class="ta-card-stat red">
        <div class="ta-stat-num">{{ summary.total_terlambat }}</div>
        <div class="ta-stat-label">Terlambat</div>
      </div>
      <div class="ta-card-stat blue">
        <div class="ta-stat-num">{{ summary.total_lembur }}</div>
        <div class="ta-stat-label">Lembur</div>
      </div>
      <div class="ta-card-stat orange">
        <div class="ta-stat-num">{{ summary.total_pulang_cepat }}</div>
        <div class="ta-stat-label">Pulang Cepat</div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="ta-loading">
      <span class="ta-spinner"></span> Memuat data...
    </div>

    <!-- Table -->
    <div v-else class="ta-table-wrap">
      <table class="ta-table">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Jabatan</th>
            <th>Jam Masuk</th>
            <th>Foto Masuk</th>
            <th>Jam Pulang</th>
            <th>Foto Pulang</th>
            <th>Status</th>
            <th>Pulang</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="attendances.length === 0">
            <td colspan="8" class="ta-empty">Belum ada yang absen hari ini</td>
          </tr>
          <tr v-for="item in attendances" :key="item.id">
            <td>
              <div class="ta-user">
                <img
                  v-if="item.user?.avatar"
                  :src="`/storage/${item.user.avatar}`"
                  class="ta-avatar-img"
                />
                <div v-else class="ta-avatar">
                  {{ item.user?.name?.charAt(0)?.toUpperCase() }}
                </div>
                <span>{{ item.user?.name }}</span>
              </div>
            </td>
            <td class="ta-muted">{{ item.user?.position?.name ?? '-' }}</td>
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
            <td>
              <span :class="statusClass(item.status)">
                {{ statusLabel(item.status, item.late_minutes) }}
              </span>
            </td>
            <td>
              <span v-if="item.checkout_status" :class="checkoutClass(item.checkout_status)">
                {{ checkoutLabel(item.checkout_status, item.overtime_minutes) }}
              </span>
              <span v-else class="ta-muted">-</span>
            </td>
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
  name: 'TodayAttendance',
  setup() {
    const attendances = ref<any[]>([])
    const loading = ref(true)
    const summary = ref({ total_hadir: 0, total_terlambat: 0, total_lembur: 0, total_pulang_cepat: 0 })

    const photoModal = ref({
      show: false, loading: false, error: '', url: '', type: 'in',
    })

    const today = new Date().toLocaleDateString('id-ID', {
      weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    })

    const load = async () => {
      loading.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('admin/attendance/today')
        attendances.value = data.data
        summary.value = data.summary
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

    const statusClass   = (s: string) => s === 'late' ? 'badge-late' : 'badge-ontime'
    const statusLabel   = (s: string, m: number) => s === 'late' ? `Terlambat ${m} mnt` : 'Tepat Waktu'
    const checkoutClass = (s: string) => s === 'overtime' ? 'badge-blue' : s === 'early_leave' ? 'badge-orange' : 'badge-ontime'
    const checkoutLabel = (s: string, m: number) => s === 'overtime' ? `Lembur ${m} mnt` : s === 'early_leave' ? 'Pulang Cepat' : 'Normal'

    return {
      attendances, loading, summary, today,
      statusClass, statusLabel, checkoutClass, checkoutLabel,
      photoModal, viewPhoto, closePhoto,
    }
  }
})
</script>

<style scoped>
.ta-wrap { padding: 24px; }
.ta-header { margin-bottom: 24px; }
.ta-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.ta-sub { font-size: 13px; color: #55555e; margin: 0; }

.ta-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
.ta-card-stat { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; text-align: center; }
.ta-card-stat.red    { border-color: rgba(255,107,107,0.2); }
.ta-card-stat.blue   { border-color: rgba(27,132,255,0.2); }
.ta-card-stat.orange { border-color: rgba(255,165,0,0.2); }
.ta-stat-num   { font-size: 32px; font-weight: 800; color: #f0f0f5; }
.ta-card-stat.red    .ta-stat-num { color: #ff6b6b; }
.ta-card-stat.blue   .ta-stat-num { color: #1b84ff; }
.ta-card-stat.orange .ta-stat-num { color: #ffa500; }
.ta-stat-label { font-size: 12px; color: #55555e; margin-top: 4px; }

.ta-loading { color: #55555e; display: flex; align-items: center; gap: 8px; padding: 32px 0; }
@keyframes spin { to { transform: rotate(360deg); } }
.ta-spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.1); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; display: inline-block; }

.ta-table-wrap { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; overflow: auto; }
.ta-table { width: 100%; border-collapse: collapse; min-width: 800px; }
.ta-table th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; border-bottom: 1px solid rgba(255,255,255,0.06); }
.ta-table td { padding: 14px 16px; font-size: 13px; color: #aaaabc; border-bottom: 1px solid rgba(255,255,255,0.04); }
.ta-table tr:last-child td { border-bottom: none; }
.ta-empty { text-align: center; color: #3a3a48; padding: 32px !important; }
.ta-muted { color: #3a3a48 !important; }

.ta-user { display: flex; align-items: center; gap: 10px; }
.ta-avatar { width: 32px; height: 32px; border-radius: 50%; background: #e8262a; color: #fff; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ta-avatar-img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }

/* Tombol foto */
.ta-btn-photo {
  background: rgba(27,132,255,0.15); color: #1b84ff;
  border: 1px solid rgba(27,132,255,0.2); border-radius: 8px;
  padding: 4px 10px; font-size: 12px; cursor: pointer;
  transition: background 0.15s; white-space: nowrap;
}
.ta-btn-photo:hover { background: rgba(27,132,255,0.25); }

/* Badge */
.badge-late   { background: rgba(255,107,107,0.15); color: #ff6b6b; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-ontime { background: rgba(23,198,83,0.15);  color: #17c653; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-blue   { background: rgba(27,132,255,0.15); color: #1b84ff; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-orange { background: rgba(255,165,0,0.15);  color: #ffa500; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }

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

@media (max-width: 768px) { .ta-summary { grid-template-columns: repeat(2, 1fr); } }
</style>