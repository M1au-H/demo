<template>
  <div class="ah-wrap">
    <div class="ah-header">
      <h2 class="ah-title">Riwayat Absensi</h2>
      <p class="ah-sub">Histori kehadiran kamu</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="ah-loading">
      <span class="ah-spinner"></span> Memuat data...
    </div>

    <!-- Empty -->
    <div v-else-if="!loading && attendances.length === 0" class="ah-empty">
      <div style="font-size:48px;margin-bottom:12px">📋</div>
      <p>Belum ada riwayat absensi</p>
    </div>

    <!-- List -->
    <div v-else class="ah-list">
      <div v-for="item in attendances" :key="item.id" class="ah-card">
        <div class="ah-card-left">
          <div class="ah-date">{{ formatDate(item.date) }}</div>
          <div class="ah-times">
            <span>Masuk: <strong>{{ item.check_in_time ?? '-' }}</strong></span>
            <span>Pulang: <strong>{{ item.check_out_time ?? '-' }}</strong></span>
          </div>
        </div>
        <div class="ah-card-right">
          <span :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
          <span v-if="item.checkout_status" :class="checkoutClass(item.checkout_status)" style="margin-top:4px">
            {{ checkoutLabel(item.checkout_status, item.overtime_minutes) }}
          </span>
          <span v-if="item.is_edited" class="badge-edited">Diedit Admin</span>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="lastPage > 1" class="ah-pagination">
      <button @click="loadPage(currentPage - 1)" :disabled="currentPage === 1" class="ah-page-btn">← Prev</button>
      <span class="ah-page-info">{{ currentPage }} / {{ lastPage }}</span>
      <button @click="loadPage(currentPage + 1)" :disabled="currentPage === lastPage" class="ah-page-btn">Next →</button>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'MyHistory',
  setup() {
    const attendances  = ref<any[]>([])
    const loading      = ref(true)
    const currentPage  = ref(1)
    const lastPage     = ref(1)

    const loadPage = async (page: number) => {
      loading.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get(`attendance/my-history?page=${page}`)
        attendances.value = data.data
        currentPage.value = data.current_page
        lastPage.value    = data.last_page
      } catch (_) {} finally { loading.value = false }
    }

    onMounted(() => loadPage(1))

    const formatDate = (d: string) => new Date(d).toLocaleDateString('id-ID', {
      weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'
    })

    const statusClass = (s: string) => s === 'late' ? 'badge-late' : s === 'absent' ? 'badge-absent' : 'badge-ontime'
    const statusLabel = (s: string) => s === 'late' ? 'Terlambat' : s === 'absent' ? 'Tidak Hadir' : 'Tepat Waktu'
    const checkoutClass = (s: string) => s === 'overtime' ? 'badge-overtime' : s === 'early_leave' ? 'badge-early' : 'badge-ontime'
    const checkoutLabel = (s: string, m: number) => s === 'overtime' ? `Lembur ${m} mnt` : s === 'early_leave' ? 'Pulang Cepat' : 'Normal'

    return { attendances, loading, currentPage, lastPage, loadPage, formatDate, statusClass, statusLabel, checkoutClass, checkoutLabel }
  }
})
</script>

<style scoped>
.ah-wrap { max-width: 640px; margin: 0 auto; padding: 24px 16px; }
.ah-header { margin-bottom: 24px; }
.ah-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.ah-sub { font-size: 13px; color: #55555e; margin: 0; }
.ah-loading { color: #55555e; font-size: 14px; display: flex; align-items: center; gap: 8px; padding: 32px 0; }
.ah-empty { text-align: center; color: #55555e; padding: 48px 0; }
.ah-list { display: flex; flex-direction: column; gap: 10px; }
.ah-card { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; }
.ah-date { font-size: 13px; font-weight: 700; color: #f0f0f5; margin-bottom: 6px; }
.ah-times { display: flex; gap: 16px; font-size: 12px; color: #55555e; }
.ah-times strong { color: #aaaabc; }
.ah-card-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.badge-late     { background: rgba(255,107,107,0.15); color: #ff6b6b; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-ontime   { background: rgba(23,198,83,0.15);  color: #17c653; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-absent   { background: rgba(255,107,107,0.15); color: #ff6b6b; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-overtime { background: rgba(27,132,255,0.15); color: #1b84ff; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-early    { background: rgba(255,165,0,0.15);  color: #ffa500; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-edited   { background: rgba(114,57,234,0.15); color: #7239ea; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.ah-pagination { display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 24px; }
.ah-page-btn { background: #181b22; border: 1.5px solid rgba(255,255,255,0.10); color: #aaaabc; border-radius: 8px; padding: 8px 16px; cursor: pointer; font-size: 13px; }
.ah-page-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.ah-page-info { color: #55555e; font-size: 13px; }
@keyframes spin { to { transform: rotate(360deg); } }
.ah-spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.1); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; display: inline-block; }
</style>