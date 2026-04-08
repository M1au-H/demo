<template>
  <div class="al-wrap">

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
        <button @click="fetchLeaves" class="al-btn-filter">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Tampilkan
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="al-stats">
      <div class="al-stat-card" style="--accent:#1b84ff">
        <div class="al-stat-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1b84ff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="al-stat-val">{{ leaves.length }}</div>
        <div class="al-stat-label">Total Izin</div>
      </div>
      <div class="al-stat-card" style="--accent:#ff6b6b">
        <div class="al-stat-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ff6b6b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div class="al-stat-val">{{ countType('sakit') }}</div>
        <div class="al-stat-label">Sakit</div>
      </div>
      <div class="al-stat-card" style="--accent:#ffa500">
        <div class="al-stat-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffa500" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="al-stat-val">{{ countType('izin') }}</div>
        <div class="al-stat-label">Izin</div>
      </div>
      <div class="al-stat-card" style="--accent:#17c653">
        <div class="al-stat-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#17c653" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="al-stat-val">{{ sakitDenganSurat }}</div>
        <div class="al-stat-label">Ada Surat Dokter</div>
      </div>
    </div>

    <!-- Kuota Izin Per Karyawan -->
    <div class="al-card" style="margin-top:16px" v-if="kuotaPerUser.length > 0">
      <div class="al-table-header">
        <h3 class="al-section-title">
          <span class="al-section-dot" style="background:#ffa500"></span>
          Kuota Izin Bulan Ini
        </h3>
        <span class="al-badge" style="background:rgba(255,165,0,0.15);color:#ffa500">
          Maks. 3x/bulan
        </span>
      </div>
      <div class="al-kuota-grid">
        <div v-for="k in kuotaPerUser" :key="k.user_id" class="al-kuota-item">
          <div class="al-kuota-avatar">{{ k.name.charAt(0) }}</div>
          <div class="al-kuota-info">
            <div class="al-kuota-name">{{ k.name }}</div>
            <div class="al-kuota-bar-wrap">
              <div class="al-kuota-bar">
                <div
                  class="al-kuota-fill"
                  :style="{
                    width: (k.izin_terpakai / k.kuota * 100) + '%',
                    background: k.sisa_izin === 0 ? '#ff6b6b' : k.sisa_izin === 1 ? '#ffa500' : '#17c653'
                  }"
                ></div>
              </div>
              <span class="al-kuota-text" :class="k.sisa_izin === 0 ? 'al-kuota-habis' : ''">
                {{ k.izin_terpakai }}/{{ k.kuota }}
                <span v-if="k.sisa_izin === 0" class="al-kuota-badge-habis">Habis</span>
                <span v-else class="al-kuota-sisa">sisa {{ k.sisa_izin }}x</span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabel Izin -->
    <div class="al-card" style="margin-top:16px">
      <div class="al-table-header">
        <h3 class="al-section-title">
          <span class="al-section-dot"></span>
          Daftar Izin
        </h3>
        <span class="al-badge">{{ leaves.length }} data</span>
      </div>

      <div v-if="loading" class="al-empty">
        <div class="al-loading-rows">
          <div class="al-skel-row" v-for="n in 5" :key="n"></div>
        </div>
      </div>

      <div v-else-if="leaves.length === 0" class="al-empty">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:#3a3a48;margin-bottom:10px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <p>Tidak ada data izin untuk periode ini</p>
      </div>

      <div v-else class="al-table-wrap">
        <table class="al-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Pegawai</th>
              <th>Tanggal</th>
              <th>Jenis</th>
              <th>Keterangan</th>
              <th>Surat Dokter</th>
              <th>Diajukan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(leave, i) in leaves" :key="leave.id">
              <td class="al-td-num">{{ i + 1 }}</td>
              <td>
                <div class="al-user">
                  <div class="al-avatar">
                    <img v-if="leave.user?.avatar" :src="leave.user.avatar" />
                    <span v-else>{{ leave.user?.name?.charAt(0) }}</span>
                  </div>
                  <div>
                    <div class="al-user-name">{{ leave.user?.name ?? '-' }}</div>
                    <div class="al-user-job">{{ leave.user?.job_title ?? '-' }}</div>
                  </div>
                </div>
              </td>
              <td><div class="al-date">{{ formatDate(leave.date) }}</div></td>
              <td>
                <span :class="['al-type-badge', `al-type-${leave.type}`]">
                  <span v-html="typeIconSvg(leave.type)"></span>
                  {{ typeLabel(leave.type) }}
                </span>
              </td>
              <td>
                <span v-if="leave.reason" class="al-reason">{{ leave.reason }}</span>
                <span v-else class="al-no-reason">—</span>
              </td>
              <td>
                <template v-if="leave.type === 'sakit'">
                  <button v-if="leave.surat_dokter" class="al-doc-btn" @click="openDoc(leave)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Lihat
                  </button>
                  <span v-else class="al-no-doc">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    Belum ada
                  </span>
                </template>
                <span v-else class="al-no-reason">—</span>
              </td>
              <td class="al-td-created">{{ formatDateShort(leave.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Surat Dokter -->
    <div v-if="docModal.show" class="al-modal-backdrop" @click.self="closeDoc">
      <div class="al-modal">
        <div class="al-modal-head">
          <div class="al-modal-title-wrap">
            <div class="al-modal-avatar">
              <img v-if="docModal.leave?.user?.avatar" :src="docModal.leave.user.avatar" />
              <span v-else>{{ docModal.leave?.user?.name?.charAt(0) }}</span>
            </div>
            <div>
              <div class="al-modal-name">{{ docModal.leave?.user?.name }}</div>
              <div class="al-modal-meta">Surat Dokter · {{ formatDate(docModal.leave?.date) }}</div>
            </div>
          </div>
          <button class="al-modal-close" @click="closeDoc">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="al-modal-body">
          <template v-if="docIsPdf">
            <div class="al-pdf-wrap">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ff6b6b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <p class="al-pdf-name">{{ docFilename }}</p>
              <a :href="docUrl" target="_blank" class="al-doc-open-btn">Buka di Tab Baru</a>
            </div>
          </template>
          <template v-else>
            <div class="al-img-wrap">
              <img v-if="!imgError" :src="docUrl" class="al-doc-img" @error="imgError = true" />
              <div v-if="imgError" class="al-img-error">
                <p>Gagal memuat gambar</p>
                <a :href="docUrl" target="_blank" class="al-doc-open-btn">Buka Langsung</a>
              </div>
            </div>
          </template>
        </div>
        <div class="al-modal-foot">
          <a :href="docUrl" :download="docFilename" class="al-doc-download-btn">Download</a>
          <button class="al-modal-close-btn" @click="closeDoc">Tutup</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'AdminLeaveList',
  setup() {
    const now    = new Date()
    const filter = ref({ month: now.getMonth() + 1, year: now.getFullYear() })
    const leaves       = ref<any[]>([])
    const kuotaPerUser = ref<any[]>([])
    const loading      = ref(false)
    const docModal     = ref<{ show: boolean; leave: any }>({ show: false, leave: null })
    const imgError     = ref(false)

    const docUrl      = computed(() => docModal.value.leave?.surat_dokter ?? '')
    const docFilename = computed(() => docUrl.value.split('/').pop()?.split('?')[0] ?? 'surat_dokter')
    const docIsPdf    = computed(() => docFilename.value.toLowerCase().endsWith('.pdf'))

    const openDoc  = (leave: any) => { imgError.value = false; docModal.value = { show: true, leave } }
    const closeDoc = () => { docModal.value = { show: false, leave: null } }

    const months = [
      { value: 1, label: 'Januari' },   { value: 2,  label: 'Februari'  },
      { value: 3, label: 'Maret' },     { value: 4,  label: 'April'     },
      { value: 5, label: 'Mei' },       { value: 6,  label: 'Juni'      },
      { value: 7, label: 'Juli' },      { value: 8,  label: 'Agustus'   },
      { value: 9, label: 'September' }, { value: 10, label: 'Oktober'   },
      { value: 11, label: 'November' }, { value: 12, label: 'Desember'  },
    ]
    const years = Array.from({ length: 5 }, (_, i) => now.getFullYear() - i)

    // Hanya 2 tipe sekarang
    const typeLabel = (t: string) => ({ sakit: 'Sakit', izin: 'Izin' }[t] ?? t)

    const typeIconSvg = (t: string) => ({
      sakit: `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>`,
      izin:  `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`,
    }[t] ?? `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/></svg>`)

    const countType        = (t: string) => leaves.value.filter(l => l.type === t).length
    const sakitDenganSurat = computed(() => leaves.value.filter(l => l.type === 'sakit' && l.surat_dokter).length)

    const formatDate      = (d: string) => d ? new Date(d).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-'
    const formatDateShort = (d: string) => d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'

    const fetchLeaves = async () => {
      loading.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get(`admin/leaves?month=${filter.value.month}&year=${filter.value.year}`)
        leaves.value       = data.data          ?? []
        kuotaPerUser.value = data.kuota_per_user ?? []
      } catch (_) {
        leaves.value       = []
        kuotaPerUser.value = []
      } finally {
        loading.value = false
      }
    }

    onMounted(() => fetchLeaves())

    return {
      filter, leaves, kuotaPerUser, loading, months, years,
      typeLabel, typeIconSvg, countType, sakitDenganSurat,
      formatDate, formatDateShort, fetchLeaves,
      docModal, docUrl, docFilename, docIsPdf, imgError,
      openDoc, closeDoc,
    }
  }
})
</script>

<style scoped>
.al-wrap { padding: 24px 16px; }
.al-header { margin-bottom: 24px; }
.al-title { font-size: 22px; font-weight: 800; color: var(--kt-text-dark); margin: 0 0 4px; }
.al-sub { font-size: 13px; color: var(--kt-text-muted); margin: 0; }
.al-card { background: var(--kt-card-bg); border: 1px solid var(--kt-border-color); border-radius: 16px; padding: 20px; }
.al-filters { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.al-filter-group { display: flex; flex-direction: column; gap: 6px; }
.al-filter-group label { font-size: 11px; font-weight: 600; color: var(--kt-text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.al-input { background: var(--kt-gray-100); border: 1.5px solid var(--kt-border-color); border-radius: 10px; color: var(--kt-text-dark); padding: 9px 14px; font-size: 14px; outline: none; min-width: 130px; }
.al-btn-filter { display: flex; align-items: center; gap: 7px; background: #1b84ff; color: #fff; border: none; border-radius: 10px; padding: 9px 20px; font-size: 14px; font-weight: 600; cursor: pointer; }
.al-btn-filter:hover { background: #3395ff; }
.al-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-top: 16px; }
.al-stat-card { background: var(--kt-card-bg); border: 1px solid var(--kt-border-color); border-radius: 16px; padding: 18px 16px; display: flex; flex-direction: column; align-items: center; gap: 6px; position: relative; overflow: hidden; transition: transform 0.15s, box-shadow 0.15s; }
.al-stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.18); }
.al-stat-card::after { content:''; position:absolute; top:-20px; right:-20px; width:70px; height:70px; border-radius:50%; background:var(--accent); opacity:0.1; pointer-events:none; }
.al-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; background:color-mix(in srgb,var(--accent) 15%,transparent); }
.al-stat-val { font-size:30px; font-weight:800; color:var(--kt-text-dark); line-height:1; }
.al-stat-label { font-size:12px; color:var(--kt-text-muted); font-weight:500; }

/* Kuota Grid */
.al-kuota-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin-top: 4px; }
.al-kuota-item { display: flex; align-items: center; gap: 10px; background: var(--kt-gray-100); border-radius: 12px; padding: 10px 12px; }
.al-kuota-avatar { width: 32px; height: 32px; border-radius: 50%; background: rgba(27,132,255,0.15); color: #1b84ff; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.al-kuota-info { flex: 1; min-width: 0; }
.al-kuota-name { font-size: 12px; font-weight: 600; color: var(--kt-text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
.al-kuota-bar-wrap { display: flex; align-items: center; gap: 8px; }
.al-kuota-bar { flex: 1; height: 5px; background: var(--kt-border-color); border-radius: 3px; overflow: hidden; }
.al-kuota-fill { height: 100%; border-radius: 3px; transition: width 0.4s ease; }
.al-kuota-text { font-size: 11px; color: var(--kt-text-muted); white-space: nowrap; display: flex; align-items: center; gap: 4px; }
.al-kuota-habis { color: #ff6b6b !important; font-weight: 600; }
.al-kuota-badge-habis { background: rgba(255,107,107,0.15); color: #ff6b6b; border-radius: 4px; padding: 1px 5px; font-size: 10px; font-weight: 700; }
.al-kuota-sisa { color: var(--kt-text-muted); }

.al-table-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; }
.al-section-title { display:flex; align-items:center; gap:8px; font-size:15px; font-weight:700; color:var(--kt-text-dark); margin:0; }
.al-section-dot { width:8px; height:8px; border-radius:50%; background:#ffa500; flex-shrink:0; }
.al-badge { background:rgba(27,132,255,0.15); color:#1b84ff; border-radius:20px; padding:3px 10px; font-size:12px; font-weight:600; }
.al-table-wrap { overflow-x:auto; }
.al-table { width:100%; border-collapse:collapse; }
.al-table th { text-align:left; font-size:11px; font-weight:600; color:var(--kt-text-muted); text-transform:uppercase; letter-spacing:0.5px; padding:10px 12px; border-bottom:1px solid var(--kt-border-color); white-space:nowrap; }
.al-table td { padding:12px; border-bottom:1px solid var(--kt-border-color); font-size:13px; color:var(--kt-text-dark); vertical-align:middle; }
.al-table tr:last-child td { border-bottom:none; }
.al-table tr:hover td { background:var(--kt-gray-100); }
.al-td-num { color:var(--kt-text-muted); width:40px; }
.al-td-created { color:var(--kt-text-muted); white-space:nowrap; }
.al-user { display:flex; align-items:center; gap:10px; }
.al-avatar { width:36px; height:36px; border-radius:50%; overflow:hidden; background:rgba(27,132,255,0.15); display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; color:#1b84ff; flex-shrink:0; }
.al-avatar img { width:100%; height:100%; object-fit:cover; }
.al-user-name { font-size:13px; font-weight:600; color:var(--kt-text-dark); }
.al-user-job { font-size:11px; color:var(--kt-text-muted); margin-top:1px; }
.al-date { font-size:13px; white-space:nowrap; }
.al-type-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap; }
.al-type-sakit { background:rgba(255,107,107,0.15); color:#ff6b6b; }
.al-type-izin  { background:rgba(255,165,0,0.15);   color:#ffa500; }
.al-reason { font-style:italic; color:var(--kt-text-muted); font-size:12px; }
.al-no-reason { color:var(--kt-border-color); }
.al-doc-btn { display:inline-flex; align-items:center; gap:5px; background:rgba(23,198,83,0.12); border:1px solid rgba(23,198,83,0.25); color:#17c653; border-radius:8px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.15s; white-space:nowrap; }
.al-doc-btn:hover { background:rgba(23,198,83,0.22); }
.al-no-doc { display:inline-flex; align-items:center; gap:4px; color:var(--kt-text-muted); font-size:12px; opacity:0.5; }
.al-loading-rows { display:flex; flex-direction:column; gap:10px; padding:8px 0; }
.al-skel-row { height:52px; border-radius:12px; background:linear-gradient(90deg,var(--kt-gray-200) 25%,var(--kt-gray-300) 50%,var(--kt-gray-200) 75%); background-size:200%; animation:shimmer 1.2s infinite; }
@keyframes shimmer { from{background-position:200%} to{background-position:-200%} }
.al-empty { text-align:center; padding:40px; color:var(--kt-text-muted); }
.al-empty p { font-size:14px; margin:6px 0 0; }
.al-modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(6px); z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px; animation:fade-in 0.2s ease; }
@keyframes fade-in { from{opacity:0} to{opacity:1} }
.al-modal { background:var(--kt-card-bg); border:1px solid var(--kt-border-color); border-radius:20px; width:100%; max-width:520px; overflow:hidden; box-shadow:0 24px 64px rgba(0,0,0,0.5); animation:slide-up 0.25s ease; }
@keyframes slide-up { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:none} }
.al-modal-head { display:flex; align-items:center; justify-content:space-between; padding:18px 20px; border-bottom:1px solid var(--kt-border-color); }
.al-modal-title-wrap { display:flex; align-items:center; gap:12px; }
.al-modal-avatar { width:40px; height:40px; border-radius:50%; overflow:hidden; background:rgba(27,132,255,0.15); display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:700; color:#1b84ff; flex-shrink:0; }
.al-modal-avatar img { width:100%; height:100%; object-fit:cover; }
.al-modal-name { font-size:14px; font-weight:700; color:var(--kt-text-dark); }
.al-modal-meta { font-size:11px; color:var(--kt-text-muted); margin-top:2px; }
.al-modal-close { width:34px; height:34px; background:var(--kt-gray-100); border:1px solid var(--kt-border-color); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--kt-text-muted); cursor:pointer; }
.al-modal-close:hover { background:rgba(255,107,107,0.12); color:#ff6b6b; }
.al-modal-body { padding:20px; min-height:200px; display:flex; align-items:center; justify-content:center; }
.al-img-wrap { width:100%; }
.al-doc-img { width:100%; max-height:420px; object-fit:contain; border-radius:12px; border:1px solid var(--kt-border-color); background:var(--kt-gray-100); }
.al-img-error { display:flex; flex-direction:column; align-items:center; gap:10px; color:var(--kt-text-muted); font-size:13px; }
.al-pdf-wrap { display:flex; flex-direction:column; align-items:center; gap:14px; padding:20px; }
.al-pdf-name { font-size:13px; color:var(--kt-text-muted); text-align:center; word-break:break-all; margin:0; }
.al-doc-open-btn { display:inline-flex; align-items:center; gap:6px; background:rgba(27,132,255,0.12); border:1px solid rgba(27,132,255,0.25); color:#1b84ff; border-radius:10px; padding:8px 16px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; }
.al-doc-open-btn:hover { background:rgba(27,132,255,0.22); }
.al-modal-foot { display:flex; align-items:center; justify-content:flex-end; gap:10px; padding:14px 20px; border-top:1px solid var(--kt-border-color); }
.al-doc-download-btn { display:inline-flex; align-items:center; gap:6px; background:rgba(23,198,83,0.12); border:1px solid rgba(23,198,83,0.25); color:#17c653; border-radius:10px; padding:8px 16px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; }
.al-doc-download-btn:hover { background:rgba(23,198,83,0.22); }
.al-modal-close-btn { background:var(--kt-gray-200); border:1px solid var(--kt-border-color); color:var(--kt-text-muted); border-radius:10px; padding:8px 16px; font-size:13px; font-weight:600; cursor:pointer; }
.al-modal-close-btn:hover { color:var(--kt-text-dark); }
@media(max-width:768px) { .al-stats{grid-template-columns:repeat(2,1fr)} .al-kuota-grid{grid-template-columns:1fr} .al-modal{max-width:100%;border-radius:20px 20px 0 0} .al-modal-backdrop{align-items:flex-end;padding:0} }
</style>