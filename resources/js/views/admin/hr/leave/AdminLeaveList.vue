<template>
  <div class="al-wrap">

    <!-- Header -->
    <div class="al-header">
      <h2 class="al-title">Data Izin & Cuti Pegawai</h2>
      <p class="al-sub">Pantau pengajuan izin seluruh pegawai</p>
    </div>

    <!-- Filter -->
    <div class="al-card">
      <div class="al-filters">
        <div class="al-filter-group">
          <label>Bulan</label>
          <select v-model="filter.month" class="al-input">
            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
          </select>
        </div>
        <div class="al-filter-group">
          <label>Tahun</label>
          <select v-model="filter.year" class="al-input">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <button @click="fetchLeaves" class="al-btn-filter">🔍 Tampilkan</button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="al-stats">
      <div class="al-stat-card">
        <div class="al-stat-icon">📋</div>
        <div class="al-stat-val">{{ leaves.length }}</div>
        <div class="al-stat-label">Total Izin</div>
      </div>
      <div class="al-stat-card">
        <div class="al-stat-icon">🤒</div>
        <div class="al-stat-val">{{ countType('sakit') }}</div>
        <div class="al-stat-label">Sakit</div>
      </div>
      <div class="al-stat-card">
        <div class="al-stat-icon">🏖️</div>
        <div class="al-stat-val">{{ countType('cuti') }}</div>
        <div class="al-stat-label">Cuti</div>
      </div>
      <div class="al-stat-card">
        <div class="al-stat-icon">⚡</div>
        <div class="al-stat-val">{{ countType('mendadak') + countType('keluarga') }}</div>
        <div class="al-stat-label">Izin Lainnya</div>
      </div>
    </div>

    <!-- Tabel -->
    <div class="al-card" style="margin-top: 20px;">
      <div class="al-table-header">
        <h3 class="al-section-title">Daftar Izin</h3>
        <span class="al-badge">{{ leaves.length }} data</span>
      </div>

      <div v-if="loading" class="al-empty">Memuat data...</div>

      <div v-else-if="leaves.length === 0" class="al-empty">
        <div class="al-empty-icon">📭</div>
        <p>Tidak ada data izin untuk periode ini</p>
      </div>

      <div v-else class="al-table-wrap">
        <table class="al-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Pegawai</th>
              <th>Tanggal</th>
              <th>Jenis Izin</th>
              <th>Keterangan</th>
              <th>Diajukan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(leave, i) in leaves" :key="leave.id">
              <td class="al-td-num">{{ i + 1 }}</td>
              <td>
                <div class="al-user">
                  <div class="al-avatar">
                 <img v-if="leave.user?.avatar" :src="`/storage/${leave.user.avatar}`" />
                    <span v-else>{{ leave.user?.name?.charAt(0) }}</span>
                  </div>
                  <div>
                    <div class="al-user-name">{{ leave.user?.name ?? '-' }}</div>
                    <div class="al-user-job">{{ leave.user?.job_title ?? '-' }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div class="al-date">{{ formatDate(leave.date) }}</div>
              </td>
              <td>
                <span :class="['al-type-badge', `al-type-${leave.type}`]">
                  {{ typeIcon(leave.type) }} {{ typeLabel(leave.type) }}
                </span>
              </td>
              <td>
                <span v-if="leave.reason" class="al-reason">{{ leave.reason }}</span>
                <span v-else class="al-no-reason">—</span>
              </td>
              <td class="al-td-created">{{ formatDateShort(leave.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'AdminLeaveList',
  setup() {
    const now = new Date()
    const filter = ref({ month: now.getMonth() + 1, year: now.getFullYear() })
    const leaves = ref<any[]>([])
    const loading = ref(false)

    const months = [
      { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' },
      { value: 3, label: 'Maret' },   { value: 4, label: 'April' },
      { value: 5, label: 'Mei' },     { value: 6, label: 'Juni' },
      { value: 7, label: 'Juli' },    { value: 8, label: 'Agustus' },
      { value: 9, label: 'September'},{ value: 10, label: 'Oktober' },
      { value: 11, label: 'November'},{ value: 12, label: 'Desember' },
    ]
    const years = Array.from({ length: 5 }, (_, i) => now.getFullYear() - i)

    const typeLabel = (t: string) => ({ sakit: 'Sakit', cuti: 'Cuti Tahunan', keluarga: 'Kep. Keluarga', mendadak: 'Izin Mendadak' }[t] ?? t)
    const typeIcon  = (t: string) => ({ sakit: '🤒', cuti: '🏖️', keluarga: '👨‍👩‍👧', mendadak: '⚡' }[t] ?? '📝')
    const countType = (t: string) => leaves.value.filter(l => l.type === t).length

    const formatDate      = (d: string) => new Date(d).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
    const formatDateShort = (d: string) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })

    // Generate full URL untuk avatar
    const avatarUrl = (path: string): string => {
      if (!path) return ''
      if (path.startsWith('http')) return path
      return `${import.meta.env.VITE_APP_API_URL ?? 'http://127.0.0.1:8000'}/storage/${path}`
    }

    const fetchLeaves = async () => {
      loading.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get(`admin/leaves?month=${filter.value.month}&year=${filter.value.year}`)
        leaves.value = data.data
      } catch (_) {
        leaves.value = []
      } finally {
        loading.value = false
      }
    }

    onMounted(() => fetchLeaves())

    return {
      filter, leaves, loading, months, years,
      typeLabel, typeIcon, countType,
      formatDate, formatDateShort,
      avatarUrl,
      fetchLeaves,
    }
  }
})
</script>

<style scoped>
.al-wrap { padding: 24px 16px; }
.al-header { margin-bottom: 24px; }
.al-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.al-sub { font-size: 13px; color: #55555e; margin: 0; }

.al-card { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 20px; }

/* Filter */
.al-filters { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.al-filter-group { display: flex; flex-direction: column; gap: 6px; }
.al-filter-group label { font-size: 11px; font-weight: 600; color: #aaaabc; text-transform: uppercase; letter-spacing: 0.5px; }
.al-input {
  background: #0d0f14; border: 1.5px solid rgba(255,255,255,0.08);
  border-radius: 10px; color: #f0f0f5; padding: 9px 14px;
  font-size: 14px; outline: none; min-width: 130px;
}
.al-input option { background: #15171e; }
.al-btn-filter {
  background: #1b84ff; color: #fff; border: none; border-radius: 10px;
  padding: 9px 20px; font-size: 14px; font-weight: 600; cursor: pointer;
  transition: background 0.15s;
}
.al-btn-filter:hover { background: #3395ff; }

/* Stats */
.al-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 16px; }
.al-stat-card {
  background: #15171e; border: 1px solid rgba(255,255,255,0.08);
  border-radius: 14px; padding: 16px; text-align: center;
}
.al-stat-icon { font-size: 24px; margin-bottom: 6px; }
.al-stat-val { font-size: 28px; font-weight: 800; color: #f0f0f5; }
.al-stat-label { font-size: 12px; color: #55555e; margin-top: 2px; }

/* Table */
.al-table-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.al-section-title { font-size: 15px; font-weight: 700; color: #f0f0f5; margin: 0; }
.al-badge { background: rgba(27,132,255,0.15); color: #1b84ff; border-radius: 20px; padding: 3px 10px; font-size: 12px; font-weight: 600; }

.al-table-wrap { overflow-x: auto; }
.al-table { width: 100%; border-collapse: collapse; }
.al-table th {
  text-align: left; font-size: 11px; font-weight: 600; color: #55555e;
  text-transform: uppercase; letter-spacing: 0.5px;
  padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.06);
}
.al-table td {
  padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.04);
  font-size: 13px; color: #d0d0e0; vertical-align: middle;
}
.al-table tr:last-child td { border-bottom: none; }
.al-table tr:hover td { background: rgba(255,255,255,0.02); }
.al-td-num { color: #55555e; width: 40px; }
.al-td-created { color: #55555e; white-space: nowrap; }

/* User cell */
.al-user { display: flex; align-items: center; gap: 10px; }
.al-avatar {
  width: 36px; height: 36px; border-radius: 50%; overflow: hidden;
  background: #1b84ff22; display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 700; color: #1b84ff; flex-shrink: 0;
}
.al-avatar img { width: 100%; height: 100%; object-fit: cover; }
.al-user-name { font-size: 13px; font-weight: 600; color: #f0f0f5; }
.al-user-job { font-size: 11px; color: #55555e; margin-top: 1px; }

/* Date */
.al-date { font-size: 13px; color: #d0d0e0; white-space: nowrap; }

/* Type badge */
.al-type-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap;
}
.al-type-sakit    { background: rgba(255,107,107,0.15); color: #ff6b6b; }
.al-type-cuti     { background: rgba(27,132,255,0.15);  color: #1b84ff; }
.al-type-keluarga { background: rgba(23,198,83,0.15);   color: #17c653; }
.al-type-mendadak { background: rgba(255,180,0,0.15);   color: #ffb400; }

.al-reason { font-style: italic; color: #aaaabc; }
.al-no-reason { color: #333340; }

/* Empty */
.al-empty { text-align: center; padding: 40px; color: #55555e; }
.al-empty-icon { font-size: 40px; margin-bottom: 8px; }
.al-empty p { font-size: 14px; margin: 0; }

@media (max-width: 768px) {
  .al-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>