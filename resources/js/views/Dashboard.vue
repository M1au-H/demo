<template>
  <div class="hr-dash">

    <!-- ══ HEADER ══ -->
    <div class="hr-dash-header">
      <div>
        <h1 class="hr-dash-title">Dashboard HR</h1>
        <p class="hr-dash-sub">{{ todayLabel }} · Data real-time sistem absensi</p>
      </div>
      <div class="hr-dash-header-right">
        <div class="hr-dash-clock">
          <span class="hr-clock-time">{{ liveTime }}</span>
          <span class="hr-clock-dot">●</span>
          <span class="hr-clock-date">{{ todayShort }}</span>
        </div>
        <button class="hr-dash-refresh" @click="loadAll" :class="{ spinning: refreshing }">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 .49-4"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- ══ STAT CARDS ══ -->
    <div class="hr-stat-grid">
      <div
        class="hr-stat-card"
        v-for="(s, i) in stats"
        :key="i"
        :style="{ '--accent': s.color }"
        @click="s.route && $router.push(s.route)"
        :class="{ clickable: s.route }"
      >
        <div class="hr-stat-icon" v-html="s.icon"></div>
        <div class="hr-stat-body">
          <div class="hr-stat-val">
            <span v-if="loading" class="hr-skel-val"></span>
            <span v-else class="hr-count-anim">{{ s.value }}</span>
          </div>
          <div class="hr-stat-label">{{ s.label }}</div>
          <div class="hr-stat-bar-wrap" v-if="s.pct !== undefined">
            <div class="hr-stat-bar">
              <div class="hr-stat-bar-fill" :style="{ width: s.pct + '%', background: s.color }"></div>
            </div>
            <span class="hr-stat-pct">{{ s.pct }}%</span>
          </div>
        </div>
        <div class="hr-stat-trend" :class="s.trendType" v-if="s.trend">{{ s.trend }}</div>
        <div class="hr-stat-glow"></div>
      </div>
    </div>

    <!-- ══ MAIN GRID ══ -->
    <div class="hr-main-grid">

      <!-- Absensi Hari Ini -->
      <div class="hr-card hr-card-wide">
        <div class="hr-card-head">
          <div class="hr-card-title">
            <span class="hr-card-dot pulse" style="background:#17c653"></span>
            Absensi Hari Ini
            <span class="hr-badge-count">{{ todayAttendance.length }}</span>
          </div>
          <div class="hr-card-head-right">
            <div class="hr-filter-tabs">
              <button
                v-for="f in filters"
                :key="f.key"
                class="hr-filter-tab"
                :class="{ active: activeFilter === f.key }"
                @click="activeFilter = f.key"
              >{{ f.label }}</button>
            </div>
            <router-link to="/hr/attendance/today" class="hr-card-link">Lihat Semua →</router-link>
          </div>
        </div>

        <div v-if="loading" class="hr-loading-rows">
          <div class="hr-skel-row" v-for="n in 5" :key="n"></div>
        </div>
        <div v-else-if="filteredAttendance.length === 0" class="hr-empty">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:#3a3a48;margin-bottom:8px">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span>Belum ada data absensi</span>
        </div>
        <div v-else class="hr-attendance-list">
          <div
            class="hr-att-item"
            v-for="att in filteredAttendance.slice(0, 8)"
            :key="att.id"
          >
            <div class="hr-att-rank">{{ filteredAttendance.indexOf(att) + 1 }}</div>
            <div class="hr-att-avatar">
              <img v-if="att.user?.avatar || att.avatar" :src="att.user?.avatar || att.avatar" />
              <span v-else>{{ (att.user_name || att.user?.name)?.charAt(0) }}</span>
            </div>
            <div class="hr-att-info">
              <div class="hr-att-name">{{ att.user_name || att.user?.name }}</div>
              <div class="hr-att-pos">{{ att.user?.position?.name ?? att.position ?? 'Pegawai' }}</div>
            </div>
            <div class="hr-att-times">
              <span class="hr-att-chip in" v-if="att.check_in_time">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                {{ formatTime(att.check_in_time) }}
              </span>
              <span class="hr-att-chip out" v-if="att.check_out_time">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                {{ formatTime(att.check_out_time) }}
              </span>
              <span class="hr-att-chip pending" v-if="att.check_in_time && !att.check_out_time">Belum pulang</span>
            </div>
            <div class="hr-att-status-pill" :class="getAttStatus(att)">
              {{ getAttLabel(att) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Donut Chart: Kehadiran -->
      <div class="hr-card hr-card-donut">
        <div class="hr-card-head">
          <div class="hr-card-title">
            <span class="hr-card-dot" style="background:#1b84ff"></span>
            Kehadiran Hari Ini
          </div>
        </div>
        <div class="hr-donut-wrap">
          <svg viewBox="0 0 120 120" class="hr-donut-svg">
            <circle cx="60" cy="60" r="48" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="14"/>
            <circle cx="60" cy="60" r="48" fill="none"
              :stroke="'#17c653'"
              stroke-width="14"
              stroke-linecap="round"
              :stroke-dasharray="`${donutHadir} ${301.6 - donutHadir}`"
              stroke-dashoffset="75.4"
              style="transition:stroke-dasharray 1s ease"
            />
            <circle cx="60" cy="60" r="48" fill="none"
              :stroke="'#ffa500'"
              stroke-width="14"
              stroke-linecap="round"
              :stroke-dasharray="`${donutLate} ${301.6 - donutLate}`"
              :stroke-dashoffset="75.4 - donutHadir"
              style="transition:stroke-dasharray 1s ease"
            />
            <text x="60" y="55" text-anchor="middle" fill="#f0f0f5" font-size="20" font-weight="800">{{ presentToday }}</text>
            <text x="60" y="70" text-anchor="middle" fill="#55555e" font-size="8">dari {{ totalEmployees }}</text>
          </svg>
          <div class="hr-donut-legend">
            <div class="hr-legend-item">
              <span class="hr-legend-dot" style="background:#17c653"></span>
              <span>Tepat Waktu</span>
              <strong>{{ onTimeToday }}</strong>
            </div>
            <div class="hr-legend-item">
              <span class="hr-legend-dot" style="background:#ffa500"></span>
              <span>Terlambat</span>
              <strong>{{ lateToday }}</strong>
            </div>
            <div class="hr-legend-item">
              <span class="hr-legend-dot" style="background:#e8262a"></span>
              <span>Tidak Hadir</span>
              <strong>{{ absentToday }}</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Kehadiran Bulan Ini -->
      <div class="hr-card">
        <div class="hr-card-head">
          <div class="hr-card-title">
            <span class="hr-card-dot" style="background:#a855f7"></span>
            Top Kehadiran Bulan Ini
          </div>
          <router-link to="/hr/attendance/monthly" class="hr-card-link">Laporan →</router-link>
        </div>
        <div v-if="loading" class="hr-loading-rows">
          <div class="hr-skel-row" v-for="n in 5" :key="n"></div>
        </div>
        <div v-else-if="monthlyTop.length === 0" class="hr-empty">Belum ada data</div>
        <div v-else class="hr-top-list">
          <div class="hr-top-item" v-for="(item, i) in monthlyTop" :key="i">
            <div class="hr-top-rank" :class="['gold','silver','bronze'][i] ?? 'other'">{{ i + 1 }}</div>
            <div class="hr-top-info">
              <div class="hr-top-name">{{ item.user_name }}</div>
              <div class="hr-top-bar-wrap">
                <div class="hr-top-bar">
                  <div class="hr-top-bar-fill" :style="{ width: barWidth(item.total_days) + '%', background: barColors[i % barColors.length] }"></div>
                </div>
                <span class="hr-top-days">{{ item.total_days }}h</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Izin & Cuti -->
      <div class="hr-card">
        <div class="hr-card-head">
          <div class="hr-card-title">
            <span class="hr-card-dot" style="background:#ffa500"></span>
            Izin & Cuti Terbaru
          </div>
          <router-link to="/hr/leaves" class="hr-card-link">Lihat →</router-link>
        </div>
        <div v-if="loading" class="hr-loading-rows">
          <div class="hr-skel-row" v-for="n in 4" :key="n"></div>
        </div>
        <div v-else-if="leaves.length === 0" class="hr-empty">Tidak ada pengajuan izin</div>
        <div v-else class="hr-leave-list">
          <div class="hr-leave-item" v-for="lv in leaves.slice(0, 5)" :key="lv.id">
            <div class="hr-leave-icon-wrap" :style="{ background: leaveIconBg(lv.type) }">
              <span v-html="leaveIconSvg(lv.type)"></span>
            </div>
            <div class="hr-leave-body">
              <div class="hr-leave-name">{{ lv.user_name ?? lv.user?.name ?? '—' }}</div>
              <div class="hr-leave-meta">{{ typeLabel(lv.type) }} · {{ formatDate(lv.date) }}</div>
            </div>
            <span class="hr-leave-badge" :class="lv.status ?? 'pending'">{{ lv.status ?? 'Pending' }}</span>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="hr-card hr-card-links">
        <div class="hr-card-head">
          <div class="hr-card-title">
            <span class="hr-card-dot" style="background:#17c653"></span>
            Akses Cepat
          </div>
        </div>
        <div class="hr-quick-grid">
          <router-link v-for="link in quickLinks" :key="link.to" :to="link.to" class="hr-quick-item">
            <div class="hr-quick-icon" :style="{ background: link.bg }" v-html="link.icon"></div>
            <span class="hr-quick-label">{{ link.label }}</span>
          </router-link>
        </div>
      </div>

      <!-- Error alert -->
      <div v-if="loadError" class="hr-card hr-error-card">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ff6b6b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ loadError }}</span>
        <button @click="loadAll" class="hr-error-retry">Coba Lagi</button>
      </div>

    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const API_BASE = (import.meta.env.VITE_APP_API_URL as string || '/api').replace(/\/$/, '')

export default defineComponent({
  name: 'hr-dashboard',
  setup() {
    const authStore  = useAuthStore()
    const loading    = ref(true)
    const refreshing = ref(false)
    const loadError  = ref('')

    const todayAttendance = ref<any[]>([])
    const leaves          = ref<any[]>([])
    const monthlyData     = ref<any[]>([])
    const activeFilter    = ref('all')
    const liveTime        = ref('')

    const filters = [
      { key: 'all',     label: 'Semua' },
      { key: 'present', label: 'Hadir' },
      { key: 'late',    label: 'Terlambat' },
      { key: 'absent',  label: 'Absen' },
    ]

    const now = new Date()
    const todayLabel = computed(() => now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }))
    const todayShort = computed(() => now.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }))
    const barColors  = ['#1b84ff', '#17c653', '#ffa500', '#e8262a', '#a855f7']

    // ── TOKEN — ambil dari authStore, fallback ke localStorage
    const getToken = () => {
      return authStore.user?.api_token
        || (authStore as any).token
        || localStorage.getItem('api_token')
        || localStorage.getItem('id_token')
        || ''
    }

    // ── HTTP client dengan token yang benar
    const http = () => {
      const token = getToken()
      return axios.create({
        baseURL: API_BASE,
        headers: { Authorization: `Bearer ${token}` },
      })
    }

    // ── COMPUTED ──
    const presentToday   = computed(() => todayAttendance.value.filter(a => a.check_in_time).length)
    const lateToday      = computed(() => todayAttendance.value.filter(a => a.status === 'late').length)
    const onTimeToday    = computed(() => todayAttendance.value.filter(a => a.check_in_time && a.status !== 'late').length)
    const absentToday    = computed(() => todayAttendance.value.filter(a => !a.check_in_time).length)
    const totalEmployees = computed(() => todayAttendance.value.length)

    const donutHadir = computed(() => totalEmployees.value ? Math.round((presentToday.value / totalEmployees.value) * 301.6) : 0)
    const donutLate  = computed(() => totalEmployees.value ? Math.round((lateToday.value  / totalEmployees.value) * 301.6) : 0)

    const filteredAttendance = computed(() => {
      if (activeFilter.value === 'all')     return todayAttendance.value
      if (activeFilter.value === 'present') return todayAttendance.value.filter(a => a.check_in_time && a.status !== 'late')
      if (activeFilter.value === 'late')    return todayAttendance.value.filter(a => a.status === 'late')
      if (activeFilter.value === 'absent')  return todayAttendance.value.filter(a => !a.check_in_time)
      return todayAttendance.value
    })

    const monthlyTop = computed(() => {
      const map: Record<number, { user_name: string; total_days: number }> = {}
      for (const row of monthlyData.value) {
        const uid  = row.user?.id ?? row.user_id
        const name = row.user_name || row.user?.name || '—'
        if (!map[uid]) map[uid] = { user_name: name, total_days: 0 }
        map[uid].total_days++
      }
      return Object.values(map).sort((a, b) => b.total_days - a.total_days).slice(0, 5)
    })

    const maxDays  = computed(() => Math.max(...monthlyTop.value.map(m => m.total_days), 1))
    const barWidth = (d: number) => Math.round((d / maxDays.value) * 100)

    const stats = computed(() => [
      {
        label: 'Total Pegawai', value: totalEmployees.value,
        icon: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
        color: '#1b84ff', route: null, trend: null, trendType: '', pct: undefined,
      },
      {
        label: 'Hadir Hari Ini', value: presentToday.value,
        icon: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
        color: '#17c653', route: '/hr/attendance/today',
        trend: totalEmployees.value ? `+${presentToday.value}` : null, trendType: 'good',
        pct: totalEmployees.value ? Math.round(presentToday.value / totalEmployees.value * 100) : 0,
      },
      {
        label: 'Terlambat', value: lateToday.value,
        icon: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
        color: '#ffa500', route: null,
        trend: lateToday.value > 0 ? `${lateToday.value} orang` : null, trendType: 'warn',
        pct: totalEmployees.value ? Math.round(lateToday.value / totalEmployees.value * 100) : 0,
      },
      {
        label: 'Tidak Hadir', value: absentToday.value,
        icon: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
        color: '#e8262a', route: null,
        trend: absentToday.value > 0 ? `${absentToday.value} orang` : null, trendType: 'bad',
        pct: totalEmployees.value ? Math.round(absentToday.value / totalEmployees.value * 100) : 0,
      },
    ])

    const quickLinks = [
      { to: '/hr/attendance/today',   label: 'Absensi',  bg: 'rgba(23,198,83,0.15)',  icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#17c653" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' },
      { to: '/hr/attendance/monthly', label: 'Laporan',  bg: 'rgba(27,132,255,0.15)', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1b84ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>' },
      { to: '/hr/leaves',             label: 'Izin',     bg: 'rgba(255,165,0,0.15)',  icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffa500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>' },
      { to: '/hr/performance',        label: 'Performa', bg: 'rgba(168,85,247,0.15)', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>' },
      { to: '/hr/sanctions',          label: 'Sanksi',   bg: 'rgba(232,38,42,0.15)',  icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e8262a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>' },
      { to: '/hr/positions',          label: 'Jabatan',  bg: 'rgba(27,132,255,0.10)', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1b84ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>' },
      { to: '/hr/face-management',    label: 'Face',     bg: 'rgba(23,198,83,0.10)',  icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#17c653" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/></svg>' },
      { to: '/hr/attendance/today',   label: 'Live',     bg: 'rgba(232,38,42,0.10)',  icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e8262a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' },
    ]

    // ── HELPERS ──
    const formatTime = (t: string) => {
      if (!t) return '—'
      return new Date(t).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
    }
    const formatDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })

    const getAttStatus = (att: any) => {
      if (!att.check_in_time) return 'absent'
      if (att.status === 'late') return 'late'
      if (!att.check_out_time) return 'present'
      return 'done'
    }
    const getAttLabel = (att: any) => {
      if (!att.check_in_time) return 'Absen'
      if (att.status === 'late') return 'Terlambat'
      if (!att.check_out_time) return 'Hadir'
      return 'Selesai'
    }

    const typeLabel    = (t: string) => ({ sakit: 'Sakit', cuti: 'Cuti Tahunan' }[t] ?? t)
    const leaveIconBg  = (t: string) => ({ sakit: 'rgba(232,38,42,0.12)', cuti: 'rgba(27,132,255,0.12)' }[t] ?? 'rgba(255,255,255,0.06)')
    const leaveIconSvg = (t: string) => ({
      sakit: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e8262a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>`,
      cuti:  `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1b84ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`,
    }[t] ?? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>`)

    // ── LOAD DATA ──
    const loadAll = async () => {
      refreshing.value = true
      loadError.value  = ''
      try {
        const y = now.getFullYear()
        const m = String(now.getMonth() + 1).padStart(2, '0')

        const [attRes, leavesRes, monthlyRes] = await Promise.allSettled([
          http().get('/admin/attendance/today'),
          http().get('/admin/leaves'),
          http().get(`/admin/attendance/monthly?year=${y}&month=${m}`),
        ])

        if (attRes.status     === 'fulfilled') todayAttendance.value = attRes.value.data?.data     ?? []
        if (leavesRes.status  === 'fulfilled') leaves.value          = leavesRes.value.data?.data  ?? []
        if (monthlyRes.status === 'fulfilled') monthlyData.value     = monthlyRes.value.data?.data ?? []

        // Debug: log token jika data kosong
        if (todayAttendance.value.length === 0) {
          const token = getToken()
          console.warn('[Dashboard] Data kosong. Token:', token ? token.slice(0, 10) + '...' : 'TIDAK ADA TOKEN')
          if (!token) loadError.value = 'Token tidak ditemukan. Silakan login ulang.'
        }

      } catch (e: any) {
        const status = e?.response?.status
        if (status === 401) {
          loadError.value = '401 Unauthorized — Token tidak valid atau sudah kedaluwarsa. Silakan login ulang.'
        } else {
          loadError.value = `Gagal memuat data (${status ?? 'network error'})`
        }
        console.error('[Dashboard] Load error:', e?.response?.data ?? e)
      } finally {
        loading.value    = false
        refreshing.value = false
      }
    }

    // ── LIVE CLOCK ──
    let clockInterval: number | null = null
    const tickClock = () => {
      liveTime.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    }

    onMounted(() => {
      loadAll()
      tickClock()
      clockInterval = window.setInterval(tickClock, 1000)
    })

    onUnmounted(() => {
      if (clockInterval) clearInterval(clockInterval)
    })

    return {
      loading, refreshing, loadError, liveTime,
      todayLabel, todayShort,
      todayAttendance, filteredAttendance, leaves, monthlyTop,
      activeFilter, filters,
      barColors, barWidth,
      stats, quickLinks,
      presentToday, lateToday, onTimeToday, absentToday, totalEmployees,
      donutHadir, donutLate,
      formatTime, formatDate,
      getAttStatus, getAttLabel,
      typeLabel, leaveIconBg, leaveIconSvg,
      loadAll,
    }
  }
})
</script>

<style scoped>
.hr-dash { padding: 4px 0 48px; animation: fade-in 0.35s ease both; }
@keyframes fade-in { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

.hr-dash-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; flex-wrap: wrap; gap: 14px; }
.hr-dash-title  { font-size: 26px; font-weight: 800; color: var(--kt-text-dark); letter-spacing: -0.03em; margin: 0 0 4px; }
.hr-dash-sub    { font-size: 13px; color: var(--kt-text-muted); margin: 0; }
.hr-dash-header-right { display: flex; align-items: center; gap: 10px; }

.hr-dash-clock { display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: var(--kt-card-bg); border: 1px solid var(--kt-border-color); border-radius: 12px; }
.hr-clock-time { font-size: 14px; font-weight: 800; color: var(--kt-text-dark); font-variant-numeric: tabular-nums; letter-spacing: 0.03em; }
.hr-clock-dot  { font-size: 6px; color: #17c653; animation: blink 1s step-start infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
.hr-clock-date { font-size: 12px; color: var(--kt-text-muted); font-weight: 500; }

.hr-dash-refresh { width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--kt-border-color); background: var(--kt-card-bg); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--kt-text-muted); transition: all 0.2s; }
.hr-dash-refresh:hover { color: #1b84ff; border-color: rgba(27,132,255,0.3); }
.hr-dash-refresh.spinning svg { animation: spin 0.65s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.hr-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 22px; }
@media (max-width: 1100px) { .hr-stat-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px)  { .hr-stat-grid { grid-template-columns: 1fr; } }

.hr-stat-card { position: relative; overflow: hidden; background: var(--kt-card-bg); border: 1px solid var(--kt-border-color); border-radius: 18px; padding: 20px 20px 16px; display: flex; flex-direction: column; gap: 12px; transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s; }
.hr-stat-card.clickable { cursor: pointer; }
.hr-stat-card.clickable:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.3); border-color: var(--accent); }
.hr-stat-glow { position: absolute; top: -30px; right: -30px; width: 100px; height: 100px; border-radius: 50%; background: var(--accent); opacity: 0.1; pointer-events: none; transition: opacity 0.2s; }
.hr-stat-card:hover .hr-stat-glow { opacity: 0.18; }
.hr-stat-icon { width: 44px; height: 44px; border-radius: 12px; background: color-mix(in srgb, var(--accent) 15%, transparent); color: var(--accent); display: flex; align-items: center; justify-content: center; }
.hr-stat-body { flex: 1; }
.hr-stat-val   { font-size: 32px; font-weight: 800; color: var(--kt-text-dark); line-height: 1; margin-bottom: 4px; letter-spacing: -0.03em; }
.hr-stat-label { font-size: 12px; color: var(--kt-text-muted); font-weight: 500; margin-bottom: 10px; }
.hr-stat-bar-wrap { display: flex; align-items: center; gap: 8px; }
.hr-stat-bar { flex: 1; height: 4px; background: rgba(255,255,255,0.07); border-radius: 4px; overflow: hidden; }
.hr-stat-bar-fill { height: 100%; border-radius: 4px; transition: width 1s cubic-bezier(.4,0,.2,1); }
.hr-stat-pct { font-size: 11px; font-weight: 700; color: var(--kt-text-muted); white-space: nowrap; }
.hr-stat-trend { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; align-self: flex-start; }
.hr-stat-trend.good { background: rgba(23,198,83,0.15);  color: #17c653; }
.hr-stat-trend.bad  { background: rgba(232,38,42,0.15);  color: #e8262a; }
.hr-stat-trend.warn { background: rgba(255,165,0,0.15);  color: #ffa500; }
.hr-skel-val { display: inline-block; width: 56px; height: 32px; background: linear-gradient(90deg, var(--kt-gray-200) 25%, var(--kt-gray-300) 50%, var(--kt-gray-200) 75%); background-size: 200%; animation: shimmer 1.2s infinite; border-radius: 8px; }
@keyframes shimmer { from{background-position:200%} to{background-position:-200%} }

.hr-main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.hr-card-wide  { grid-column: span 2; }
@media (max-width: 960px) { .hr-main-grid { grid-template-columns: 1fr; } .hr-card-wide { grid-column: span 1; } }

.hr-card { background: var(--kt-card-bg); border: 1px solid var(--kt-border-color); border-radius: 18px; padding: 22px; transition: box-shadow 0.2s; }
.hr-card:hover { box-shadow: 0 4px 24px rgba(0,0,0,0.2); }
.hr-card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px; }
.hr-card-title { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; color: var(--kt-text-dark); }
.hr-card-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.hr-card-dot.pulse { animation: pulse-dot 2s ease-in-out infinite; }
@keyframes pulse-dot { 0%,100%{box-shadow:0 0 0 0 currentColor} 50%{box-shadow:0 0 0 5px transparent} }
.hr-badge-count { background: rgba(255,255,255,0.08); color: var(--kt-text-muted); font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
.hr-card-head-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.hr-card-link { font-size: 12px; font-weight: 600; color: #1b84ff; text-decoration: none; transition: color 0.15s; }
.hr-card-link:hover { color: #5aabff; }

.hr-filter-tabs { display: flex; gap: 4px; background: rgba(255,255,255,0.04); border-radius: 10px; padding: 3px; }
.hr-filter-tab { padding: 5px 12px; border-radius: 7px; border: none; font-size: 11.5px; font-weight: 600; cursor: pointer; color: var(--kt-text-muted); background: none; transition: all 0.15s; }
.hr-filter-tab.active { background: var(--kt-card-bg); color: var(--kt-text-dark); box-shadow: 0 1px 6px rgba(0,0,0,0.3); }

.hr-attendance-list { display: flex; flex-direction: column; gap: 8px; }
.hr-att-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: var(--kt-gray-100); border-radius: 12px; transition: background 0.15s; }
.hr-att-item:hover { background: var(--kt-gray-200); }
.hr-att-rank { width: 20px; font-size: 11px; font-weight: 700; color: var(--kt-text-muted); text-align: center; flex-shrink: 0; }
.hr-att-avatar { width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; background: #e8262a; color: #fff; font-size: 14px; font-weight: 700; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.hr-att-avatar img { width: 100%; height: 100%; object-fit: cover; }
.hr-att-info { flex: 1; min-width: 0; }
.hr-att-name { font-size: 13px; font-weight: 600; color: var(--kt-text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.hr-att-pos  { font-size: 11px; color: var(--kt-text-muted); }
.hr-att-times { display: flex; gap: 6px; flex-wrap: wrap; }
.hr-att-chip { display: flex; align-items: center; gap: 3px; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px; }
.hr-att-chip.in      { background: rgba(23,198,83,0.12);  color: #17c653; }
.hr-att-chip.out     { background: rgba(232,38,42,0.12);  color: #e8262a; }
.hr-att-chip.pending { background: rgba(255,165,0,0.12);  color: #ffa500; }
.hr-att-status-pill { font-size: 10.5px; font-weight: 700; padding: 4px 10px; border-radius: 20px; white-space: nowrap; }
.hr-att-status-pill.present { background: rgba(23,198,83,0.15);  color: #17c653; }
.hr-att-status-pill.late    { background: rgba(255,165,0,0.15);  color: #ffa500; }
.hr-att-status-pill.done    { background: rgba(27,132,255,0.15); color: #1b84ff; }
.hr-att-status-pill.absent  { background: rgba(232,38,42,0.15);  color: #e8262a; }

.hr-card-donut { display: flex; flex-direction: column; }
.hr-donut-wrap { display: flex; align-items: center; gap: 24px; flex: 1; }
.hr-donut-svg  { width: 140px; height: 140px; flex-shrink: 0; transform: rotate(-90deg); }
.hr-donut-svg text { transform: rotate(90deg); transform-origin: 60px 60px; }
.hr-donut-legend { flex: 1; display: flex; flex-direction: column; gap: 12px; }
.hr-legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--kt-text-muted); }
.hr-legend-item strong { margin-left: auto; font-size: 15px; font-weight: 700; color: var(--kt-text-dark); }
.hr-legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

.hr-top-list { display: flex; flex-direction: column; gap: 12px; }
.hr-top-item { display: flex; align-items: center; gap: 12px; }
.hr-top-rank { width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; }
.hr-top-rank.gold   { background: rgba(255,215,0,0.2);   color: #ffd700; }
.hr-top-rank.silver { background: rgba(192,192,192,0.2); color: #c0c0c0; }
.hr-top-rank.bronze { background: rgba(205,127,50,0.2);  color: #cd7f32; }
.hr-top-rank.other  { background: rgba(255,255,255,0.06); color: var(--kt-text-muted); }
.hr-top-info { flex: 1; min-width: 0; }
.hr-top-name { font-size: 13px; font-weight: 600; color: var(--kt-text-dark); margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.hr-top-bar-wrap { display: flex; align-items: center; gap: 8px; }
.hr-top-bar { flex: 1; height: 5px; background: rgba(255,255,255,0.07); border-radius: 5px; overflow: hidden; }
.hr-top-bar-fill { height: 100%; border-radius: 5px; transition: width 0.8s cubic-bezier(.4,0,.2,1); }
.hr-top-days { font-size: 11px; font-weight: 700; color: var(--kt-text-muted); white-space: nowrap; }

.hr-leave-list { display: flex; flex-direction: column; gap: 10px; }
.hr-leave-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: var(--kt-gray-100); border-radius: 12px; transition: background 0.15s; }
.hr-leave-item:hover { background: var(--kt-gray-200); }
.hr-leave-icon-wrap { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.hr-leave-body { flex: 1; min-width: 0; }
.hr-leave-name { font-size: 13px; font-weight: 600; color: var(--kt-text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.hr-leave-meta { font-size: 11px; color: var(--kt-text-muted); margin-top: 2px; }
.hr-leave-badge { font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 20px; text-transform: capitalize; white-space: nowrap; flex-shrink: 0; }
.hr-leave-badge.pending  { background: rgba(255,165,0,0.15);  color: #ffa500; }
.hr-leave-badge.approved { background: rgba(23,198,83,0.15);  color: #17c653; }
.hr-leave-badge.rejected { background: rgba(232,38,42,0.15);  color: #e8262a; }

.hr-quick-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
@media (max-width: 700px) { .hr-quick-grid { grid-template-columns: repeat(3, 1fr); } }
.hr-quick-item { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 14px 8px; border-radius: 14px; background: var(--kt-gray-100); text-decoration: none; border: 1px solid transparent; transition: all 0.15s; }
.hr-quick-item:hover { background: var(--kt-gray-200); border-color: var(--kt-border-color); transform: translateY(-2px); }
.hr-quick-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.hr-quick-label { font-size: 11.5px; font-weight: 600; color: var(--kt-text-dark); text-align: center; }

.hr-error-card { grid-column: span 2; display: flex; align-items: center; gap: 10px; background: rgba(255,107,107,0.06); border-color: rgba(255,107,107,0.2); color: #ff6b6b; font-size: 13px; }
.hr-error-retry { margin-left: auto; background: rgba(255,107,107,0.15); border: 1px solid rgba(255,107,107,0.3); color: #ff6b6b; border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; }
.hr-error-retry:hover { background: rgba(255,107,107,0.25); }

.hr-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 32px; color: var(--kt-text-muted); font-size: 13px; }
.hr-loading-rows { display: flex; flex-direction: column; gap: 10px; }
.hr-skel-row { height: 50px; border-radius: 12px; background: linear-gradient(90deg, var(--kt-gray-200) 25%, var(--kt-gray-300) 50%, var(--kt-gray-200) 75%); background-size: 200%; animation: shimmer 1.2s infinite; }
</style>