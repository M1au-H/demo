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
            <th>Tanggal</th>
            <th>Nama</th>
            <th>Jam Masuk</th>
            <th>Foto Masuk</th>
            <th>Jam Pulang</th>
            <th>Foto Pulang</th>
            <th>Lokasi</th>
            <th>Status</th>
            <th>Pulang</th>
            <th>Terlambat</th>
            <th>Lembur</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="attendances.length === 0">
            <td colspan="11" class="ta-empty">Tidak ada data untuk periode ini</td>
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

            <!-- Kolom Lokasi -->
            <td>
              <div class="ta-loc-cell">
                <button
                  v-if="item.check_in_lat || item.check_out_lat"
                  @click="openMap(item)"
                  class="ta-btn-map"
                  title="Lihat lokasi masuk & pulang"
                >
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  Lihat Lokasi
                </button>
                <span v-else class="ta-muted">—</span>
              </div>
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

    <!-- Modal Map (tab Masuk / Pulang) -->
    <div v-if="mapModal.show" class="ta-modal-overlay" @click.self="closeMap">
      <div class="ta-modal ta-modal-map">
        <div class="ta-modal-header">
          <div class="ta-map-header-info">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#17c653" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span>Lokasi — {{ mapModal.userName }}</span>
          </div>
          <button @click="closeMap" class="ta-modal-close">✕</button>
        </div>

        <!-- Tab switcher -->
        <div class="ta-map-tabs">
          <button
            class="ta-map-tab"
            :class="{ active: mapModal.activeTab === 'in' }"
            :disabled="!mapModal.inLat"
            @click="switchTab('in')"
          >
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Masuk
            <span v-if="!mapModal.inLat" class="ta-tab-na">N/A</span>
          </button>
          <button
            class="ta-map-tab out"
            :class="{ active: mapModal.activeTab === 'out' }"
            :disabled="!mapModal.outLat"
            @click="switchTab('out')"
          >
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Pulang
            <span v-if="!mapModal.outLat" class="ta-tab-na">N/A</span>
          </button>
        </div>

        <!-- Info koordinat aktif -->
        <div class="ta-map-coords">
          <div class="ta-coord-item">
            <span class="ta-coord-label">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              {{ mapModal.activeTab === 'in' ? 'Lokasi Masuk' : 'Lokasi Pulang' }}
            </span>
            <span class="ta-coord-val">
              {{ activeLat?.toFixed(6) }}, {{ activeLng?.toFixed(6) }}
            </span>
          </div>
          <div class="ta-coord-item">
            <span class="ta-coord-label">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#e8262a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              Lokasi Toko
            </span>
            <span class="ta-coord-val">{{ STORE_LAT.toFixed(6) }}, {{ STORE_LNG.toFixed(6) }}</span>
          </div>
          <div class="ta-coord-item" v-if="activeDistance !== null">
            <span class="ta-coord-label">📏 Jarak</span>
            <span class="ta-coord-val" :class="activeDistance > STORE_RADIUS ? 'ta-dist-far' : 'ta-dist-ok'">
              {{ activeDistance }}m
              <span v-if="activeDistance > STORE_RADIUS"> ⚠ di luar radius</span>
              <span v-else> ✓ dalam radius</span>
            </span>
          </div>
        </div>

        <div class="ta-modal-body ta-map-body">
          <div id="ta-leaflet-map-monthly" class="ta-leaflet-container"></div>
        </div>

        <div class="ta-map-footer">
          <div class="ta-map-footer-coords" v-if="mapModal.inLat && mapModal.outLat">
            <a :href="`https://www.google.com/maps?q=${mapModal.inLat},${mapModal.inLng}`" target="_blank" class="ta-btn-gmaps">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              Maps Masuk
            </a>
            <a :href="`https://www.google.com/maps?q=${mapModal.outLat},${mapModal.outLng}`" target="_blank" class="ta-btn-gmaps out">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              Maps Pulang
            </a>
          </div>
          <a v-else :href="`https://www.google.com/maps?q=${activeLat},${activeLng}`" target="_blank" class="ta-btn-gmaps">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Buka di Google Maps
          </a>
        </div>
      </div>
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'

const API_BASE = (import.meta.env.VITE_APP_API_URL as string || '/api').replace(/\/$/, '')

const STORE_LAT    = Number(import.meta.env.VITE_STORE_LAT    ?? -7.2750211)
const STORE_LNG    = Number(import.meta.env.VITE_STORE_LNG    ?? 112.6518010)
const STORE_RADIUS = Number(import.meta.env.VITE_STORE_RADIUS ?? 100)

function getToken(): string {
  return localStorage.getItem('id_token')
    || localStorage.getItem('api_token')
    || localStorage.getItem('token')
    || sessionStorage.getItem('id_token')
    || ''
}

function haversine(lat1: number, lng1: number, lat2: number, lng2: number): number {
  const R  = 6371000
  const φ1 = lat1 * Math.PI / 180, φ2 = lat2 * Math.PI / 180
  const Δφ = (lat2 - lat1) * Math.PI / 180, Δλ = (lng2 - lng1) * Math.PI / 180
  const a  = Math.sin(Δφ/2)**2 + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ/2)**2
  return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)))
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

    const mapModal = ref<{
      show: boolean
      activeTab: 'in' | 'out'
      inLat: number | null
      inLng: number | null
      outLat: number | null
      outLng: number | null
      userName: string
    }>({
      show: false, activeTab: 'in',
      inLat: null, inLng: null,
      outLat: null, outLng: null,
      userName: ''
    })

    let leafletMap: any = null

    // Koordinat & jarak aktif sesuai tab
    const activeLat = computed(() =>
      mapModal.value.activeTab === 'in' ? mapModal.value.inLat : mapModal.value.outLat
    )
    const activeLng = computed(() =>
      mapModal.value.activeTab === 'in' ? mapModal.value.inLng : mapModal.value.outLng
    )
    const activeDistance = computed(() => {
      if (activeLat.value === null || activeLng.value === null) return null
      return haversine(activeLat.value, activeLng.value, STORE_LAT, STORE_LNG)
    })

    const months = [
      { val: 1, label: 'Januari' }, { val: 2, label: 'Februari' }, { val: 3, label: 'Maret' },
      { val: 4, label: 'April' },   { val: 5, label: 'Mei' },      { val: 6, label: 'Juni' },
      { val: 7, label: 'Juli' },    { val: 8, label: 'Agustus' },  { val: 9, label: 'September' },
      { val: 10, label: 'Oktober' },{ val: 11, label: 'November' },{ val: 12, label: 'Desember' },
    ]
    const years = [2024, 2025, 2026, 2027]

    const load = async () => {
      loading.value = true; error.value = ''
      const token   = getToken()
      if (!token) { error.value = 'Token tidak ditemukan. Silakan login ulang.'; loading.value = false; return }
      const url = `${API_BASE}/admin/attendance/monthly?month=${selectedMonth.value}&year=${selectedYear.value}`
      try {
        const { data } = await axios.get(url, { headers: { Authorization: `Bearer ${token}` } })
        attendances.value = data.data ?? []
      } catch (e: any) {
        const status = e?.response?.status
        if (status === 401) {
          try {
            const { data } = await axios.get(url, { headers: { Authorization: `Token ${token}` } })
            attendances.value = data.data ?? []
          } catch (e2: any) {
            error.value = `401 Unauthorized — cek token atau role admin.`
          }
        } else if (status === 403) {
          error.value = '403 Forbidden — akun ini bukan admin.'
        } else {
          error.value = `Gagal memuat: ${e?.response?.data?.message ?? e?.message ?? 'Network error'}`
        }
      } finally {
        loading.value = false
      }
    }

    // Buka modal gabungan — default ke tab 'in' jika ada, fallback ke 'out'
    const openMap = async (item: any) => {
      const inLat  = item.check_in_lat  ? parseFloat(item.check_in_lat)  : null
      const inLng  = item.check_in_lng  ? parseFloat(item.check_in_lng)  : null
      const outLat = item.check_out_lat ? parseFloat(item.check_out_lat) : null
      const outLng = item.check_out_lng ? parseFloat(item.check_out_lng) : null

      const defaultTab: 'in' | 'out' = inLat ? 'in' : 'out'

      mapModal.value = {
        show: true,
        activeTab: defaultTab,
        inLat, inLng, outLat, outLng,
        userName: item.user?.name ?? 'User',
      }

      await nextTick()
      const lat = defaultTab === 'in' ? (inLat ?? outLat!) : (outLat ?? inLat!)
      const lng = defaultTab === 'in' ? (inLng ?? outLng!) : (outLng ?? inLng!)
      await initLeafletMap(lat, lng, item.user?.name ?? 'User', defaultTab)
    }

    const switchTab = async (tab: 'in' | 'out') => {
      mapModal.value.activeTab = tab
      const lat = tab === 'in' ? mapModal.value.inLat! : mapModal.value.outLat!
      const lng = tab === 'in' ? mapModal.value.inLng! : mapModal.value.outLng!
      await nextTick()
      await initLeafletMap(lat, lng, mapModal.value.userName, tab)
    }

    const initLeafletMap = async (lat: number, lng: number, userName: string, type: 'in' | 'out') => {
      if (!document.getElementById('leaflet-css')) {
        const link = document.createElement('link')
        link.id = 'leaflet-css'; link.rel = 'stylesheet'
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css'
        document.head.appendChild(link)
      }
      if (!(window as any).L) {
        await new Promise<void>((resolve, reject) => {
          const script = document.createElement('script')
          script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js'
          script.onload = () => resolve(); script.onerror = () => reject()
          document.head.appendChild(script)
        })
      }

      const L = (window as any).L
      if (leafletMap) { leafletMap.remove(); leafletMap = null }

      const container = document.getElementById('ta-leaflet-map-monthly')
      if (!container) return

      leafletMap = L.map('ta-leaflet-map-monthly').setView([(lat + STORE_LAT) / 2, (lng + STORE_LNG) / 2], 16)
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(leafletMap)

      // Marker user aktif — hijau = masuk, orange = pulang
      const color  = type === 'in' ? '#17c653' : '#ffa500'
      const shadow = type === 'in' ? 'rgba(23,198,83,.6)' : 'rgba(255,165,0,.6)'
      const userIcon = L.divIcon({
        html: `<div style="background:${color};border:3px solid #fff;border-radius:50%;width:18px;height:18px;box-shadow:0 2px 8px ${shadow}"></div>`,
        className: '', iconSize: [18, 18], iconAnchor: [9, 9],
      })
      L.marker([lat, lng], { icon: userIcon }).addTo(leafletMap)
        .bindPopup(`<div style="font-size:13px;font-weight:600;color:#111">👤 ${userName}<br><span style="font-size:11px;color:#555;font-weight:400">${type === 'in' ? 'Lokasi Masuk' : 'Lokasi Pulang'}<br>${lat.toFixed(6)}, ${lng.toFixed(6)}</span></div>`, { maxWidth: 220 })
        .openPopup()

      // Plot marker lainnya jika keduanya ada
      if (mapModal.value.inLat && mapModal.value.outLat) {
        const otherLat   = type === 'in' ? mapModal.value.outLat! : mapModal.value.inLat!
        const otherLng   = type === 'in' ? mapModal.value.outLng! : mapModal.value.inLng!
        const otherColor  = type === 'in' ? '#ffa500' : '#17c653'
        const otherShadow = type === 'in' ? 'rgba(255,165,0,.6)' : 'rgba(23,198,83,.6)'
        const otherLabel  = type === 'in' ? 'Lokasi Pulang' : 'Lokasi Masuk'
        const otherIcon = L.divIcon({
          html: `<div style="background:${otherColor};border:3px solid #fff;border-radius:50%;width:14px;height:14px;opacity:0.6;box-shadow:0 2px 8px ${otherShadow}"></div>`,
          className: '', iconSize: [14, 14], iconAnchor: [7, 7],
        })
        L.marker([otherLat, otherLng], { icon: otherIcon }).addTo(leafletMap)
          .bindPopup(`<div style="font-size:13px;font-weight:600;color:#111">👤 ${userName}<br><span style="font-size:11px;color:#555;font-weight:400">${otherLabel}<br>${otherLat.toFixed(6)}, ${otherLng.toFixed(6)}</span></div>`, { maxWidth: 220 })

        // Garis masuk ↔ pulang
        L.polyline([[mapModal.value.inLat, mapModal.value.inLng!], [mapModal.value.outLat, mapModal.value.outLng!]], {
          color: '#aaaabc', weight: 1.5, dashArray: '4,4', opacity: 0.5,
        }).addTo(leafletMap)
      }

      // Marker toko (merah)
      const storeIcon = L.divIcon({
        html: `<div style="background:#e8262a;border:3px solid #fff;border-radius:50%;width:18px;height:18px;box-shadow:0 2px 8px rgba(232,38,42,.6)"></div>`,
        className: '', iconSize: [18, 18], iconAnchor: [9, 9],
      })
      L.marker([STORE_LAT, STORE_LNG], { icon: storeIcon }).addTo(leafletMap)
        .bindPopup(`<div style="font-size:13px;font-weight:600;color:#111">🏢 CV. MCFLYON TEKNOLOGI<br><span style="font-size:11px;color:#555;font-weight:400">Lokasi Toko<br>${STORE_LAT.toFixed(6)}, ${STORE_LNG.toFixed(6)}</span></div>`, { maxWidth: 220 })

      L.circle([STORE_LAT, STORE_LNG], {
        radius: STORE_RADIUS, color: '#e8262a', fillColor: '#e8262a',
        fillOpacity: 0.08, weight: 2, dashArray: '6,4',
      }).addTo(leafletMap)

      // Fit bounds semua marker
      const allPoints: [number, number][] = [[lat, lng], [STORE_LAT, STORE_LNG]]
      if (mapModal.value.inLat && mapModal.value.outLat) {
        allPoints.push([mapModal.value.inLat, mapModal.value.inLng!])
        allPoints.push([mapModal.value.outLat, mapModal.value.outLng!])
      }
      leafletMap.fitBounds(L.latLngBounds(allPoints).pad(0.3))
    }

    const closeMap = () => {
      if (leafletMap) { leafletMap.remove(); leafletMap = null }
      mapModal.value = { show: false, activeTab: 'in', inLat: null, inLng: null, outLat: null, outLng: null, userName: '' }
    }

    // Modal foto
    const viewPhoto = async (attendanceId: number, type: 'in' | 'out') => {
      photoModal.value = { show: true, loading: true, error: '', url: '', type }
      const token = getToken()
      try {
        let response = await fetch(`${API_BASE}/admin/attendance/photo/${attendanceId}/${type}`, { headers: { Authorization: `Bearer ${token}` } })
        if (response.status === 401) {
          response = await fetch(`${API_BASE}/admin/attendance/photo/${attendanceId}/${type}`, { headers: { Authorization: `Token ${token}` } })
        }
        if (!response.ok) throw new Error((await response.json().catch(() => ({}))).message ?? `HTTP ${response.status}`)
        const blob = await response.blob()
        photoModal.value.url = URL.createObjectURL(blob); photoModal.value.loading = false
      } catch (e: any) {
        photoModal.value.loading = false; photoModal.value.error = e.message ?? 'Gagal memuat foto'
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
      STORE_LAT, STORE_LNG, STORE_RADIUS,
      load, formatDate, formatTime, checkoutClass, checkoutLabel,
      totalHadir, totalTerlambat, totalLembur, totalCepat,
      photoModal, viewPhoto, closePhoto,
      mapModal, openMap, closeMap, switchTab,
      activeLat, activeLng, activeDistance,
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
.ta-table { width: 100%; border-collapse: collapse; min-width: 1000px; }
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

/* Lokasi */
.ta-loc-cell { display: flex; flex-direction: column; gap: 5px; }
.ta-btn-map { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 8px; font-size: 11.5px; font-weight: 600; cursor: pointer; border: 1px solid rgba(23,198,83,0.3); background: rgba(23,198,83,0.1); color: #17c653; transition: background 0.15s; white-space: nowrap; }
.ta-btn-map:hover { background: rgba(23,198,83,0.2); }

.badge-late   { background: rgba(255,107,107,0.15); color: #ff6b6b; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.badge-ontime { background: rgba(23,198,83,0.15);  color: #17c653; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.badge-blue   { background: rgba(27,132,255,0.15); color: #1b84ff; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.badge-orange { background: rgba(255,165,0,0.15);  color: #ffa500; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }

/* Modal */
.ta-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(4px); }
.ta-modal { background: #15171e; border: 1px solid rgba(255,255,255,0.12); border-radius: 18px; width: 90%; max-width: 520px; overflow: hidden; animation: modal-in 0.2s ease; }
.ta-modal-map { max-width: 660px; }
@keyframes modal-in { from { opacity:0; transform:scale(0.95) } to { opacity:1; transform:scale(1) } }
.ta-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 14px; font-weight: 600; color: #f0f0f5; }
.ta-map-header-info { display: flex; align-items: center; gap: 8px; }
.ta-modal-close { background: rgba(255,255,255,0.06); border: none; color: #aaaabc; font-size: 14px; cursor: pointer; padding: 6px 10px; border-radius: 8px; }
.ta-modal-body { padding: 24px; display: flex; justify-content: center; align-items: center; min-height: 220px; }
.ta-photo-img { width: 100%; border-radius: 12px; object-fit: contain; max-height: 60vh; }
.ta-photo-loading { color: #55555e; font-size: 14px; display: flex; align-items: center; gap: 10px; }
.ta-photo-error { color: #ff6b6b; font-size: 14px; text-align: center; }

/* Tab switcher */
.ta-map-tabs { display: flex; gap: 0; border-bottom: 1px solid rgba(255,255,255,0.08); }
.ta-map-tab { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 11px 16px; font-size: 13px; font-weight: 600; background: transparent; border: none; border-bottom: 2px solid transparent; color: #55555e; cursor: pointer; transition: all 0.15s; }
.ta-map-tab:hover:not(:disabled) { color: #aaaabc; background: rgba(255,255,255,0.03); }
.ta-map-tab.active { color: #17c653; border-bottom-color: #17c653; }
.ta-map-tab.out.active { color: #ffa500; border-bottom-color: #ffa500; }
.ta-map-tab:disabled { opacity: 0.35; cursor: not-allowed; }
.ta-tab-na { font-size: 10px; font-weight: 400; background: rgba(255,255,255,0.06); border-radius: 4px; padding: 1px 5px; color: #3a3a48; }

/* Koordinat */
.ta-map-coords { display: flex; flex-direction: column; border-bottom: 1px solid rgba(255,255,255,0.06); }
.ta-coord-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; border-bottom: 1px solid rgba(255,255,255,0.04); }
.ta-coord-item:last-child { border-bottom: none; }
.ta-coord-label { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #55555e; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.ta-coord-val { font-size: 12.5px; color: #aaaabc; font-family: monospace; }
.ta-dist-ok  { color: #17c653 !important; font-weight: 700; }
.ta-dist-far { color: #ff6b6b !important; font-weight: 700; }
.ta-map-body { padding: 0 !important; min-height: unset !important; }
.ta-leaflet-container { width: 100%; height: 340px; }
.ta-map-footer { padding: 14px 20px; border-top: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: flex-end; }
.ta-map-footer-coords { display: flex; gap: 10px; }
.ta-btn-gmaps { display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; background: rgba(27,132,255,0.12); border: 1px solid rgba(27,132,255,0.25); border-radius: 8px; color: #5aabff; font-size: 12.5px; font-weight: 600; text-decoration: none; transition: background 0.15s; }
.ta-btn-gmaps:hover { background: rgba(27,132,255,0.22); }
.ta-btn-gmaps.out { background: rgba(255,165,0,0.1); border-color: rgba(255,165,0,0.25); color: #ffa500; }
.ta-btn-gmaps.out:hover { background: rgba(255,165,0,0.2); }
</style>