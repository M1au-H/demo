<template>
  <div class="lv-root">

    <!-- ── HEADER ── -->
    <div class="lv-header">
      <div class="lv-header-left">
        <div class="lv-header-eyebrow">HR Management</div>
        <h1 class="lv-header-title">Izin <span class="lv-title-accent">&</span> Cuti</h1>
        <p class="lv-header-sub">Kelola dan pantau pengajuan izin seluruh pegawai</p>
      </div>
      <div class="lv-header-right">
        <div v-if="countPending > 0" class="lv-pending-alert">
          <div class="lv-pending-pulse"></div>
          <span>{{ countPending }} izin menunggu persetujuan</span>
        </div>
      </div>
    </div>

    <!-- ── FILTER BAR ── -->
    <div class="lv-filterbar">

      <!-- Dropdown Bulan -->
      <div class="lv-dropdown-wrap" ref="monthDropdownRef">
        <button class="lv-dropdown-btn" @click="openMonthDropdown">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <span>{{ months.find(m => m.value === filter.month)?.label }}</span>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" :style="showMonthDropdown ? 'transform:rotate(180deg)' : ''" style="transition:transform 0.2s"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <Teleport to="body">
          <div v-if="showMonthDropdown" class="lv-dropdown-menu" :style="{ top: monthMenuPos.top, left: monthMenuPos.left }">
            <button
              v-for="m in months" :key="m.value"
              class="lv-dropdown-item"
              :class="{ active: filter.month === m.value }"
              @click="filter.month = m.value; showMonthDropdown = false; fetchLeaves()"
            >
              {{ m.label }}
              <svg v-if="filter.month === m.value" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
          </div>
        </Teleport>
      </div>

      <!-- Dropdown Tahun -->
      <div class="lv-dropdown-wrap" ref="yearDropdownRef">
        <button class="lv-dropdown-btn" @click="openYearDropdown">
          <span>{{ filter.year }}</span>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" :style="showYearDropdown ? 'transform:rotate(180deg)' : ''" style="transition:transform 0.2s"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <Teleport to="body">
          <div v-if="showYearDropdown" class="lv-dropdown-menu lv-dropdown-menu-sm" :style="{ top: yearMenuPos.top, left: yearMenuPos.left }">
            <button
              v-for="y in years" :key="y"
              class="lv-dropdown-item"
              :class="{ active: filter.year === y }"
              @click="filter.year = y; showYearDropdown = false; fetchLeaves()"
            >
              {{ y }}
              <svg v-if="filter.year === y" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
          </div>
        </Teleport>
      </div>

      <div class="lv-filter-divider"></div>
      <div class="lv-filter-tabs">
        <button v-for="tab in typeTabs" :key="tab.value"
          :class="['lv-tab', filter.type === tab.value && 'lv-tab-active']"
          @click="filter.type = tab.value; fetchLeaves()">
          {{ tab.label }}
        </button>
      </div>
      <div class="lv-filter-divider"></div>
      <div class="lv-filter-tabs">
        <button v-for="tab in statusTabs" :key="tab.value"
          :class="['lv-tab', filter.status === tab.value && `lv-tab-active lv-tab-status-${tab.value}`]"
          @click="filter.status = tab.value; fetchLeaves()">
          {{ tab.label }}
        </button>
      </div>
      <button @click="fetchLeaves" class="lv-refresh-btn" title="Refresh">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>
      </button>
    </div>

    <!-- ── STATS ── -->
    <div class="lv-stats">
      <div class="lv-stat" v-for="s in statCards" :key="s.key" :style="`--sc:${s.color}`">
        <div class="lv-stat-top">
          <div class="lv-stat-icon" v-html="s.icon"></div>
          <div class="lv-stat-val">{{ s.value }}</div>
        </div>
        <div class="lv-stat-label">{{ s.label }}</div>
        <div class="lv-stat-bar"><div class="lv-stat-bar-fill" :style="`width:${s.pct}%`"></div></div>
      </div>
    </div>

    <!-- ── KUOTA CUTI ── -->
    <div class="lv-card" v-if="kuotaPerUser.length">
      <div class="lv-card-head">
        <div class="lv-card-title">
          <span class="lv-dot" style="background:#7239ea"></span>
          Kuota Cuti Bulan Ini
          <span class="lv-info-hint">— Cuti otomatis disetujui sistem</span>
        </div>
        <div class="lv-badge" style="color:#7239ea;border-color:rgba(114,57,234,0.25);background:rgba(114,57,234,0.08)">
          Maks. 3× / bulan
        </div>
      </div>
      <div class="lv-kuota-grid">
        <div v-for="k in kuotaPerUser" :key="k.user_id" class="lv-kuota-card">
          <div class="lv-kuota-avatar" :style="`background:${avatarColor(k.name)}`">{{ k.name.charAt(0).toUpperCase() }}</div>
          <div class="lv-kuota-body">
            <div class="lv-kuota-name">{{ k.name }}</div>
            <div class="lv-kuota-track">
              <div class="lv-kuota-fill" :style="{
                width: Math.min(100, k.cuti_terpakai / k.kuota * 100) + '%',
                background: k.sisa_cuti === 0 ? '#f1416c' : k.sisa_cuti === 1 ? '#ffc700' : '#7239ea'
              }"></div>
            </div>
            <div class="lv-kuota-meta">
              <span :style="k.sisa_cuti === 0 ? 'color:#f1416c;font-weight:700' : 'color:var(--kt-text-muted)'">
                {{ k.cuti_terpakai }}/{{ k.kuota }}
                <span v-if="k.sisa_cuti === 0"> — Habis</span>
                <span v-else> — sisa {{ k.sisa_cuti }}×</span>
              </span>
              <span v-if="k.izin_pending > 0" class="lv-kuota-pending-tag">{{ k.izin_pending }} izin pending</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── TABEL ── -->
    <div class="lv-card" style="margin-top:14px">
      <div class="lv-card-head">
        <div class="lv-card-title">
          <span class="lv-dot"></span>
          Daftar Izin & Cuti
        </div>
        <div style="display:flex;align-items:center;gap:8px">
          <div class="lv-legend">
            <span class="lv-legend-item lv-legend-auto">Cuti/Sakit = otomatis</span>
            <span class="lv-legend-item lv-legend-manual">Izin = perlu approval</span>
          </div>
          <div class="lv-badge">{{ leaves.length }} entri</div>
        </div>
      </div>

      <div v-if="loading" class="lv-loading">
        <div class="lv-skel" v-for="n in 5" :key="n" :style="`animation-delay:${n*0.07}s`"></div>
      </div>

      <div v-else-if="leaves.length === 0" class="lv-empty">
        <div class="lv-empty-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <p>Tidak ada data untuk periode ini</p>
      </div>

      <div v-else class="lv-table-wrap">
        <table class="lv-table">
          <thead>
            <tr>
              <th class="lv-th-num">#</th>
              <th>Pegawai</th>
              <th>Tanggal</th>
              <th>Jenis</th>
              <th>Keterangan</th>
              <th>Dokumen</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(lv, i) in leaves" :key="lv.id"
              class="lv-row"
              :class="lv.status === 'pending' ? 'lv-row-pending' : ''">
              <td class="lv-td-num">{{ i + 1 }}</td>
              <td>
                <div class="lv-cell-user">
                  <div class="lv-cell-av" :style="`background:${avatarColor(lv.user?.name ?? '')}`">
                    <img v-if="lv.user?.avatar" :src="lv.user.avatar" />
                    <span v-else>{{ lv.user?.name?.charAt(0)?.toUpperCase() }}</span>
                  </div>
                  <div>
                    <div class="lv-cell-name">{{ lv.user?.name ?? '—' }}</div>
                    <div class="lv-cell-job">{{ lv.user?.job_title ?? '—' }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div class="lv-cell-date">{{ formatDateShort(lv.date) }}</div>
                <div class="lv-cell-day">{{ formatDay(lv.date) }}</div>
              </td>
              <td>
                <div style="display:flex;flex-direction:column;gap:4px">
                  <span :class="['lv-type-chip', `lv-type-${lv.type}`]">
                    <span v-html="typeIcon(lv.type)"></span>
                    {{ typeLabel(lv.type) }}
                  </span>
                  <span v-if="lv.cuti_type_label" class="lv-cuti-sub">{{ lv.cuti_type_label }}</span>
                  <span v-if="lv.type !== 'izin'" class="lv-auto-badge">otomatis</span>
                </div>
              </td>
              <td>
                <div v-if="lv.reason" class="lv-cell-reason">{{ lv.reason }}</div>
                <span v-else class="lv-cell-dash">—</span>
                <div v-if="lv.admin_note" class="lv-admin-note">
                  <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                  {{ lv.admin_note }}
                </div>
              </td>
              <td>
                <button v-if="lv.type === 'sakit' && lv.surat_dokter" class="lv-doc-btn" @click="openDoc(lv)">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  Lihat
                </button>
                <span v-else-if="lv.type === 'sakit'" class="lv-no-doc">Belum ada</span>
                <span v-else class="lv-cell-dash">—</span>
              </td>
              <td>
                <span :class="['lv-status-chip', `lv-status-${lv.status}`]">
                  <span class="lv-status-dot"></span>
                  {{ statusLabel(lv.status) }}
                </span>
              </td>
              <td>
                <div v-if="lv.type === 'izin' && lv.status === 'pending'" class="lv-actions">
                  <button class="lv-act-approve" @click="openApprove(lv)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Setuju
                  </button>
                  <button class="lv-act-reject" @click="openReject(lv)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Tolak
                  </button>
                </div>
                <div v-else-if="lv.type === 'izin' && lv.status !== 'pending'" class="lv-processed-info">
                  <span :class="['lv-processed-badge', `lv-processed-${lv.status}`]">
                    {{ lv.status === 'approved' ? '✓ Disetujui' : '✗ Ditolak' }}
                  </span>
                </div>
                <span v-else class="lv-auto-label">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── MODAL: Surat Dokter ── -->
    <Teleport to="body">
      <div v-if="docModal.show" class="lv-overlay" @click.self="closeDoc">
        <div class="lv-modal">
          <div class="lv-modal-head">
            <div class="lv-modal-user">
              <div class="lv-modal-av" :style="`background:${avatarColor(docModal.leave?.user?.name ?? '')}`">
                <img v-if="docModal.leave?.user?.avatar" :src="docModal.leave.user.avatar" />
                <span v-else>{{ docModal.leave?.user?.name?.charAt(0)?.toUpperCase() }}</span>
              </div>
              <div>
                <div class="lv-modal-name">{{ docModal.leave?.user?.name }}</div>
                <div class="lv-modal-sub">Surat Dokter &middot; {{ formatDateShort(docModal.leave?.date) }}</div>
              </div>
            </div>
            <button class="lv-modal-x" @click="closeDoc">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="lv-modal-body">
            <template v-if="docIsPdf">
              <div class="lv-pdf-block">
                <div class="lv-pdf-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#f1416c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                <div class="lv-pdf-name">{{ docFilename }}</div>
                <a :href="docUrl" target="_blank" class="lv-btn-outline">Buka PDF</a>
              </div>
            </template>
            <template v-else>
              <img v-if="!imgError" :src="docUrl" class="lv-doc-img" @error="imgError = true" />
              <div v-else class="lv-pdf-block">
                <div class="lv-pdf-name">Gagal memuat</div>
                <a :href="docUrl" target="_blank" class="lv-btn-outline">Buka Langsung</a>
              </div>
            </template>
          </div>
          <div class="lv-modal-foot">
            <a :href="docUrl" :download="docFilename" class="lv-btn-success">Download</a>
            <button class="lv-btn-ghost" @click="closeDoc">Tutup</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── MODAL: Approve / Reject IZIN ── -->
    <Teleport to="body">
      <div v-if="approvalModal.show" class="lv-overlay" @click.self="closeApproval">
        <div class="lv-modal lv-modal-sm">
          <div class="lv-modal-head">
            <div class="lv-modal-user">
              <div class="lv-approval-icon" :class="approvalModal.action === 'approved' ? 'lv-icon-ok' : 'lv-icon-no'">
                <svg v-if="approvalModal.action === 'approved'" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </div>
              <div>
                <div class="lv-modal-name">{{ approvalModal.action === 'approved' ? 'Setujui Izin' : 'Tolak Izin' }}</div>
                <div class="lv-modal-sub">{{ approvalModal.leave?.user?.name }} &middot; {{ formatDateShort(approvalModal.leave?.date) }}</div>
              </div>
            </div>
            <button class="lv-modal-x" @click="closeApproval">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="lv-modal-body" style="flex-direction:column;align-items:stretch;gap:16px;min-height:unset">
            <div class="lv-info-box">
              <div class="lv-info-row">
                <span class="lv-info-lbl">Jenis Izin</span>
                <span class="lv-info-val">{{ approvalModal.leave?.cuti_type_label ?? 'Izin Umum' }}</span>
              </div>
              <div class="lv-info-row">
                <span class="lv-info-lbl">Alasan</span>
                <span class="lv-info-val">{{ approvalModal.leave?.reason || '—' }}</span>
              </div>
            </div>
            <div>
              <label class="lv-field-lbl">Catatan Admin <span class="lv-field-opt">(opsional)</span></label>
              <textarea
                v-model="approvalModal.note"
                class="lv-textarea"
                :placeholder="approvalModal.action === 'approved' ? 'Misal: Disetujui, harap segera kembali.' : 'Misal: Alasan tidak mencukupi.'"
                rows="3"
              ></textarea>
            </div>
          </div>
          <div class="lv-modal-foot">
            <button class="lv-btn-ghost" @click="closeApproval">Batal</button>
            <button v-if="approvalModal.action === 'approved'" class="lv-btn-success" @click="submitApproval" :disabled="approvalModal.loading">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              {{ approvalModal.loading ? 'Menyimpan...' : 'Setujui' }}
            </button>
            <button v-else class="lv-btn-danger" @click="submitApproval" :disabled="approvalModal.loading">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              {{ approvalModal.loading ? 'Menyimpan...' : 'Tolak' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, onUnmounted } from 'vue'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'AdminLeaveList',
  setup() {
    const now    = new Date()
    const filter = ref({ month: now.getMonth() + 1, year: now.getFullYear(), type: '', status: '' })
    const leaves       = ref<any[]>([])
    const kuotaPerUser = ref<any[]>([])
    const loading      = ref(false)
    const imgError     = ref(false)

    // ── Dropdown state ─────────────────────────────────────────────────────
    const showMonthDropdown = ref(false)
    const showYearDropdown  = ref(false)
    const monthDropdownRef  = ref<HTMLElement | null>(null)
    const yearDropdownRef   = ref<HTMLElement | null>(null)
    const monthMenuPos      = ref({ top: '0px', left: '0px' })
    const yearMenuPos       = ref({ top: '0px', left: '0px' })

    const openMonthDropdown = () => {
      if (monthDropdownRef.value) {
        const rect = monthDropdownRef.value.getBoundingClientRect()
        monthMenuPos.value = {
          top:  (rect.bottom + 6) + 'px',
          left: rect.left + 'px',
        }
      }
      showMonthDropdown.value = !showMonthDropdown.value
      showYearDropdown.value  = false
    }

    const openYearDropdown = () => {
      if (yearDropdownRef.value) {
        const rect = yearDropdownRef.value.getBoundingClientRect()
        yearMenuPos.value = {
          top:  (rect.bottom + 6) + 'px',
          left: rect.left + 'px',
        }
      }
      showYearDropdown.value  = !showYearDropdown.value
      showMonthDropdown.value = false
    }

    const handleClickOutside = (e: MouseEvent) => {
      if (monthDropdownRef.value && !monthDropdownRef.value.contains(e.target as Node))
        showMonthDropdown.value = false
      if (yearDropdownRef.value && !yearDropdownRef.value.contains(e.target as Node))
        showYearDropdown.value = false
    }

    // ── Modals ──────────────────────────────────────────────────────────────
    const docModal      = ref<{ show: boolean; leave: any }>({ show: false, leave: null })
    const approvalModal = ref<{ show:boolean; leave:any; action:'approved'|'rejected'; note:string; loading:boolean }>
      ({ show: false, leave: null, action: 'approved', note: '', loading: false })

    const docUrl      = computed(() => docModal.value.leave?.surat_dokter ?? '')
    const docFilename = computed(() => docUrl.value.split('/').pop()?.split('?')[0] ?? 'dokumen')
    const docIsPdf    = computed(() => docFilename.value.toLowerCase().endsWith('.pdf'))

    const openDoc       = (lv: any) => { imgError.value = false; docModal.value = { show: true, leave: lv } }
    const closeDoc      = () => { docModal.value = { show: false, leave: null } }
    const openApprove   = (lv: any) => { approvalModal.value = { show: true, leave: lv, action: 'approved', note: '', loading: false } }
    const openReject    = (lv: any) => { approvalModal.value = { show: true, leave: lv, action: 'rejected', note: '', loading: false } }
    const closeApproval = () => { approvalModal.value = { show: false, leave: null, action: 'approved', note: '', loading: false } }

    const submitApproval = async () => {
      approvalModal.value.loading = true
      try {
        ApiService.setHeader()
        await ApiService.put(`admin/leaves/${approvalModal.value.leave.id}/status`, {
          status:     approvalModal.value.action,
          admin_note: approvalModal.value.note || null,
        })
        closeApproval()
        await fetchLeaves()
      } catch (e: any) {
        alert(e.response?.data?.message || 'Gagal memperbarui.')
        approvalModal.value.loading = false
      }
    }

    // ── Data ────────────────────────────────────────────────────────────────
    const months = [
      {value:1,label:'Januari'},{value:2,label:'Februari'},{value:3,label:'Maret'},
      {value:4,label:'April'},{value:5,label:'Mei'},{value:6,label:'Juni'},
      {value:7,label:'Juli'},{value:8,label:'Agustus'},{value:9,label:'September'},
      {value:10,label:'Oktober'},{value:11,label:'November'},{value:12,label:'Desember'},
    ]
    const years      = Array.from({ length: 5 }, (_, i) => now.getFullYear() - i)
    const typeTabs   = [
      { value:'', label:'Semua' },{ value:'sakit', label:'Sakit' },
      { value:'izin', label:'Izin' },{ value:'cuti', label:'Cuti' },
    ]
    const statusTabs = [
      { value:'', label:'Semua' },{ value:'pending', label:'Menunggu' },
      { value:'approved', label:'Disetujui' },{ value:'rejected', label:'Ditolak' },
    ]

    const typeLabel   = (t: string) => ({ sakit:'Sakit', izin:'Izin', cuti:'Cuti' }[t] ?? t)
    const statusLabel = (s: string) => ({ pending:'Menunggu', approved:'Disetujui', rejected:'Ditolak' }[s] ?? s)

    const typeIcon = (t: string) => ({
      sakit:`<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>`,
      izin: `<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`,
      cuti: `<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`,
    }[t] ?? '')

    const countType    = (t: string) => leaves.value.filter(l => l.type === t).length
    const countPending = computed(() => leaves.value.filter(l => l.type === 'izin' && l.status === 'pending').length)

    const statCards = computed(() => {
      const total = leaves.value.length || 1
      return [
        { key:'total',   label:'Total',         value:leaves.value.length, pct:100, color:'#1b84ff',
          icon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
        { key:'sakit',   label:'Sakit',         value:countType('sakit'), pct:Math.round(countType('sakit')/total*100), color:'#f1416c',
          icon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>` },
        { key:'izin',    label:'Izin',          value:countType('izin'),  pct:Math.round(countType('izin')/total*100),  color:'#ffc700',
          icon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>` },
        { key:'cuti',    label:'Cuti',          value:countType('cuti'),  pct:Math.round(countType('cuti')/total*100),  color:'#7239ea',
          icon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>` },
        { key:'pending', label:'Izin Menunggu', value:countPending.value, pct:Math.round(countPending.value/total*100), color:'#ffc700',
          icon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>` },
        { key:'surat',   label:'Ada Surat',     value:leaves.value.filter(l=>l.type==='sakit'&&l.surat_dokter).length,
          pct:Math.round(leaves.value.filter(l=>l.type==='sakit'&&l.surat_dokter).length/total*100), color:'#50cd89',
          icon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>` },
      ]
    })

    const AVATAR_PALETTE = ['#1b84ff','#7239ea','#f1416c','#ffc700','#50cd89','#00b2ff','#fd7e14']
    const avatarColor = (name: string) => AVATAR_PALETTE[(name.charCodeAt(0) || 0) % AVATAR_PALETTE.length]

    const formatDateShort = (d: string) => d ? new Date(d).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' }) : '—'
    const formatDay       = (d: string) => d ? new Date(d).toLocaleDateString('id-ID', { weekday:'long' }) : ''

    const fetchLeaves = async () => {
      loading.value = true
      try {
        ApiService.setHeader()
        let url = `admin/leaves?month=${filter.value.month}&year=${filter.value.year}`
        if (filter.value.type)   url += `&type=${filter.value.type}`
        if (filter.value.status) url += `&status=${filter.value.status}`
        const { data } = await ApiService.get(url)
        leaves.value       = data.data          ?? []
        kuotaPerUser.value = data.kuota_per_user ?? []
      } catch (_) {
        leaves.value = []; kuotaPerUser.value = []
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      fetchLeaves()
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      filter, leaves, kuotaPerUser, loading, imgError,
      months, years, typeTabs, statusTabs,
      typeLabel, statusLabel, typeIcon, countType, countPending, statCards, avatarColor,
      formatDateShort, formatDay, fetchLeaves,
      showMonthDropdown, showYearDropdown, monthDropdownRef, yearDropdownRef,
      monthMenuPos, yearMenuPos, openMonthDropdown, openYearDropdown,
      docModal, docUrl, docFilename, docIsPdf, openDoc, closeDoc,
      approvalModal, openApprove, openReject, closeApproval, submitApproval,
    }
  }
})
</script>

<style scoped>
.lv-root { padding: 24px 20px; }
.lv-header { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:26px; flex-wrap:wrap; gap:14px; }
.lv-header-eyebrow { font-size:11px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--kt-text-muted); margin-bottom:6px; }
.lv-header-title { font-size:26px; font-weight:800; color:var(--kt-text-dark); margin:0 0 5px; letter-spacing:-0.02em; line-height:1; }
.lv-title-accent { color:#1b84ff; }
.lv-header-sub { font-size:13px; color:var(--kt-text-muted); margin:0; }
.lv-pending-alert { display:flex; align-items:center; gap:9px; background:rgba(255,199,0,0.08); border:1px solid rgba(255,199,0,0.2); border-radius:100px; padding:8px 16px; font-size:12.5px; font-weight:600; color:#ffc700; }
.lv-pending-pulse { width:7px; height:7px; border-radius:50%; background:#ffc700; flex-shrink:0; animation:lv-pulse 1.6s ease infinite; }
@keyframes lv-pulse { 0%,100%{box-shadow:0 0 0 0 rgba(255,199,0,.5)} 50%{box-shadow:0 0 0 5px rgba(255,199,0,0)} }
.lv-filterbar { display:flex; align-items:center; gap:8px; flex-wrap:wrap; background:var(--kt-card-bg); border:1px solid var(--kt-border-color); border-radius:14px; padding:10px 16px; margin-bottom:18px; }
.lv-filter-divider { width:1px; height:18px; background:var(--kt-border-color); flex-shrink:0; }
.lv-filter-tabs { display:flex; gap:2px; }
.lv-tab { background:transparent; border:none; border-radius:8px; color:var(--kt-text-muted); font-size:12.5px; font-weight:600; padding:5px 12px; cursor:pointer; transition:all 0.14s; }
.lv-tab:hover { color:var(--kt-text-dark); background:var(--kt-gray-100); }
.lv-tab-active { color:var(--kt-text-dark) !important; background:var(--kt-gray-200) !important; }
.lv-tab-status-pending  { color:#ffc700 !important; }
.lv-tab-status-approved { color:#50cd89 !important; }
.lv-tab-status-rejected { color:#f1416c !important; }
.lv-refresh-btn { margin-left:auto; width:34px; height:34px; border-radius:9px; background:var(--kt-gray-100); border:1px solid var(--kt-border-color); color:var(--kt-text-muted); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.15s; flex-shrink:0; }
.lv-refresh-btn:hover { color:#1b84ff; border-color:rgba(27,132,255,0.35); }
.lv-dropdown-wrap { position:relative; }
.lv-dropdown-btn { display:flex; align-items:center; gap:7px; background:var(--kt-gray-100); border:1px solid var(--kt-border-color); border-radius:9px; color:var(--kt-text-dark); font-size:13px; font-weight:600; padding:7px 12px; cursor:pointer; transition:all 0.15s; white-space:nowrap; }
.lv-dropdown-btn:hover { border-color:rgba(27,132,255,0.4); color:#1b84ff; }
.lv-stats { display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:16px; }
.lv-stat { background:var(--kt-card-bg); border:1px solid var(--kt-border-color); border-radius:14px; padding:16px; position:relative; overflow:hidden; transition:transform 0.14s, border-color 0.14s; }
.lv-stat:hover { transform:translateY(-2px); border-color:color-mix(in srgb,var(--sc) 35%,transparent); }
.lv-stat::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:var(--sc); opacity:0.8; }
.lv-stat-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:10px; }
.lv-stat-icon { width:36px; height:36px; border-radius:10px; background:color-mix(in srgb,var(--sc) 14%,transparent); color:var(--sc); display:flex; align-items:center; justify-content:center; }
.lv-stat-val { font-size:26px; font-weight:800; color:var(--kt-text-dark); line-height:1; }
.lv-stat-label { font-size:11.5px; color:var(--kt-text-muted); font-weight:500; margin-bottom:10px; }
.lv-stat-bar { height:3px; background:var(--kt-gray-200); border-radius:2px; overflow:hidden; }
.lv-stat-bar-fill { height:100%; background:var(--sc); border-radius:2px; opacity:0.7; transition:width 0.5s ease; }
.lv-card { background:var(--kt-card-bg); border:1px solid var(--kt-border-color); border-radius:16px; padding:20px; }
.lv-card-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.lv-card-title { display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; color:var(--kt-text-dark); }
.lv-info-hint { font-size:11px; font-weight:400; color:var(--kt-text-muted); font-style:italic; }
.lv-dot { width:7px; height:7px; border-radius:50%; background:#1b84ff; flex-shrink:0; }
.lv-badge { font-size:12px; font-weight:600; border-radius:20px; padding:3px 12px; border:1px solid var(--kt-border-color); color:var(--kt-text-muted); background:var(--kt-gray-100); }
.lv-legend { display:flex; gap:8px; align-items:center; }
.lv-legend-item { font-size:10.5px; font-weight:600; border-radius:5px; padding:3px 8px; }
.lv-legend-auto   { background:rgba(80,205,137,0.1); color:#50cd89; border:1px solid rgba(80,205,137,0.2); }
.lv-legend-manual { background:rgba(255,199,0,0.1);  color:#ffc700; border:1px solid rgba(255,199,0,0.2); }
.lv-kuota-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:10px; }
.lv-kuota-card { display:flex; align-items:center; gap:10px; background:var(--kt-gray-100); border:1px solid var(--kt-border-color); border-radius:10px; padding:10px 12px; transition:border-color 0.14s; }
.lv-kuota-card:hover { border-color:rgba(114,57,234,0.3); }
.lv-kuota-avatar { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#fff; flex-shrink:0; }
.lv-kuota-body { flex:1; min-width:0; }
.lv-kuota-name { font-size:12px; font-weight:600; color:var(--kt-text-dark); margin-bottom:5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.lv-kuota-track { height:4px; background:var(--kt-gray-200); border-radius:2px; overflow:hidden; margin-bottom:4px; }
.lv-kuota-fill { height:100%; border-radius:2px; transition:width 0.5s ease; }
.lv-kuota-meta { display:flex; align-items:center; justify-content:space-between; font-size:10.5px; }
.lv-kuota-pending-tag { font-size:10px; font-weight:700; color:#ffc700; background:rgba(255,199,0,0.1); border-radius:4px; padding:1px 6px; }
.lv-table-wrap { overflow-x:auto; }
.lv-table { width:100%; border-collapse:collapse; }
.lv-table th { text-align:left; font-size:10.5px; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:var(--kt-text-muted); padding:0 14px 12px; border-bottom:1px solid var(--kt-border-color); white-space:nowrap; }
.lv-th-num { width:40px; }
.lv-table td { padding:13px 14px; border-bottom:1px solid var(--kt-border-color); vertical-align:middle; }
.lv-row { transition:background 0.1s; }
.lv-row:hover td { background:var(--kt-gray-100); }
.lv-row:last-child td { border-bottom:none; }
.lv-row-pending { background:rgba(255,199,0,0.025); }
.lv-row-pending:hover td { background:rgba(255,199,0,0.05) !important; }
.lv-td-num { color:var(--kt-text-muted); font-size:12px; }
.lv-cell-user { display:flex; align-items:center; gap:10px; }
.lv-cell-av { width:34px; height:34px; border-radius:8px; overflow:hidden; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#fff; flex-shrink:0; }
.lv-cell-av img { width:100%; height:100%; object-fit:cover; }
.lv-cell-name { font-size:13px; font-weight:600; color:var(--kt-text-dark); }
.lv-cell-job { font-size:11px; color:var(--kt-text-muted); margin-top:1px; }
.lv-cell-date { font-size:13px; font-weight:500; color:var(--kt-text-dark); white-space:nowrap; }
.lv-cell-day { font-size:11px; color:var(--kt-text-muted); margin-top:1px; }
.lv-type-chip { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:6px; font-size:11.5px; font-weight:600; width:fit-content; }
.lv-type-sakit { background:rgba(241,65,108,0.12); color:#f1416c; border:1px solid rgba(241,65,108,0.2); }
.lv-type-izin  { background:rgba(255,199,0,0.12);  color:#ffc700; border:1px solid rgba(255,199,0,0.2); }
.lv-type-cuti  { background:rgba(114,57,234,0.12); color:#9669f5; border:1px solid rgba(114,57,234,0.2); }
.lv-cuti-sub   { font-size:10.5px; color:var(--kt-text-muted); font-style:italic; padding-left:2px; }
.lv-auto-badge { font-size:9.5px; font-weight:700; color:#50cd89; background:rgba(80,205,137,0.1); border:1px solid rgba(80,205,137,0.2); border-radius:4px; padding:1px 6px; width:fit-content; }
.lv-cell-reason { font-size:12px; color:var(--kt-text-muted); font-style:italic; max-width:180px; }
.lv-cell-dash   { color:var(--kt-border-color); font-size:14px; }
.lv-admin-note  { display:flex; align-items:center; gap:4px; font-size:11px; color:#50cd89; margin-top:3px; font-style:italic; }
.lv-doc-btn { display:inline-flex; align-items:center; gap:5px; background:rgba(80,205,137,0.1); border:1px solid rgba(80,205,137,0.2); color:#50cd89; border-radius:7px; padding:5px 11px; font-size:11.5px; font-weight:600; cursor:pointer; white-space:nowrap; transition:all 0.14s; }
.lv-doc-btn:hover { background:rgba(80,205,137,0.2); }
.lv-no-doc { font-size:11.5px; color:var(--kt-text-muted); opacity:0.6; }
.lv-status-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:6px; font-size:11.5px; font-weight:600; white-space:nowrap; }
.lv-status-pending  { background:rgba(255,199,0,0.1);  color:#ffc700; border:1px solid rgba(255,199,0,0.2); }
.lv-status-approved { background:rgba(80,205,137,0.1); color:#50cd89; border:1px solid rgba(80,205,137,0.2); }
.lv-status-rejected { background:rgba(241,65,108,0.1); color:#f1416c; border:1px solid rgba(241,65,108,0.2); }
.lv-status-dot { width:5px; height:5px; border-radius:50%; background:currentColor; flex-shrink:0; }
.lv-actions { display:flex; gap:6px; }
.lv-act-approve, .lv-act-reject { display:inline-flex; align-items:center; gap:4px; border-radius:7px; padding:5px 10px; font-size:11.5px; font-weight:600; cursor:pointer; white-space:nowrap; transition:all 0.14s; }
.lv-act-approve { background:rgba(80,205,137,0.1); border:1px solid rgba(80,205,137,0.25); color:#50cd89; }
.lv-act-approve:hover { background:rgba(80,205,137,0.22); }
.lv-act-reject  { background:rgba(241,65,108,0.1);  border:1px solid rgba(241,65,108,0.25);  color:#f1416c; }
.lv-act-reject:hover  { background:rgba(241,65,108,0.22); }
.lv-processed-info  { display:flex; }
.lv-processed-badge { font-size:11px; font-weight:700; border-radius:6px; padding:4px 9px; }
.lv-processed-approved { background:rgba(80,205,137,0.1); color:#50cd89; border:1px solid rgba(80,205,137,0.2); }
.lv-processed-rejected { background:rgba(241,65,108,0.1); color:#f1416c; border:1px solid rgba(241,65,108,0.2); }
.lv-auto-label { color:var(--kt-border-color); font-size:14px; }
.lv-loading { display:flex; flex-direction:column; gap:8px; }
.lv-skel { height:52px; border-radius:10px; background:linear-gradient(90deg,var(--kt-gray-200) 25%,var(--kt-gray-300) 50%,var(--kt-gray-200) 75%); background-size:300%; animation:lv-shimmer 1.4s infinite; }
@keyframes lv-shimmer { from{background-position:300%} to{background-position:-300%} }
.lv-empty { text-align:center; padding:44px 20px; }
.lv-empty-icon { width:56px; height:56px; border-radius:14px; background:var(--kt-gray-200); display:flex; align-items:center; justify-content:center; color:var(--kt-text-muted); margin:0 auto 12px; }
.lv-empty p { font-size:13px; color:var(--kt-text-muted); margin:0; }
.lv-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.72); backdrop-filter:blur(8px); z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px; animation:lv-fade 0.2s ease; }
@keyframes lv-fade { from{opacity:0} to{opacity:1} }
.lv-modal { background:var(--kt-card-bg); border:1px solid var(--kt-border-color); border-radius:20px; width:100%; max-width:540px; overflow:hidden; box-shadow:0 32px 80px rgba(0,0,0,0.55); animation:lv-slideup 0.25s ease; }
.lv-modal-sm { max-width:420px; }
@keyframes lv-slideup { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:none} }
.lv-modal-head { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1px solid var(--kt-border-color); }
.lv-modal-user { display:flex; align-items:center; gap:12px; }
.lv-modal-av { width:40px; height:40px; border-radius:10px; overflow:hidden; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:700; color:#fff; flex-shrink:0; }
.lv-modal-av img { width:100%; height:100%; object-fit:cover; }
.lv-modal-name { font-size:14px; font-weight:700; color:var(--kt-text-dark); }
.lv-modal-sub  { font-size:11px; color:var(--kt-text-muted); margin-top:2px; }
.lv-modal-x { width:32px; height:32px; background:var(--kt-gray-100); border:1px solid var(--kt-border-color); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--kt-text-muted); cursor:pointer; transition:all 0.14s; }
.lv-modal-x:hover { background:rgba(241,65,108,0.12); color:#f1416c; border-color:rgba(241,65,108,0.3); }
.lv-modal-body { padding:22px; display:flex; align-items:center; justify-content:center; min-height:140px; }
.lv-modal-foot { display:flex; align-items:center; justify-content:flex-end; gap:10px; padding:14px 22px; border-top:1px solid var(--kt-border-color); }
.lv-approval-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.lv-icon-ok { background:rgba(80,205,137,0.12); color:#50cd89; }
.lv-icon-no { background:rgba(241,65,108,0.12); color:#f1416c; }
.lv-info-box { background:var(--kt-gray-100); border:1px solid var(--kt-border-color); border-radius:10px; padding:12px 14px; display:flex; flex-direction:column; gap:10px; }
.lv-info-row { display:flex; gap:12px; }
.lv-info-lbl { font-size:10.5px; font-weight:700; color:var(--kt-text-muted); text-transform:uppercase; letter-spacing:0.06em; min-width:80px; padding-top:1px; }
.lv-info-val { font-size:13px; color:var(--kt-text-dark); }
.lv-field-lbl { font-size:11px; font-weight:700; color:var(--kt-text-muted); text-transform:uppercase; letter-spacing:0.06em; display:block; margin-bottom:8px; }
.lv-field-opt { text-transform:none; letter-spacing:0; color:var(--kt-text-muted); font-weight:400; opacity:0.6; }
.lv-textarea { width:100%; box-sizing:border-box; background:var(--kt-gray-100); border:1.5px solid var(--kt-border-color); border-radius:10px; color:var(--kt-text-dark); font-size:13px; padding:10px 12px; outline:none; resize:vertical; transition:border-color 0.15s; }
.lv-textarea:focus { border-color:rgba(27,132,255,0.5); }
.lv-textarea::placeholder { color:var(--kt-text-muted); opacity:0.5; }
.lv-doc-img { width:100%; max-height:420px; object-fit:contain; border-radius:10px; border:1px solid var(--kt-border-color); }
.lv-pdf-block { display:flex; flex-direction:column; align-items:center; gap:14px; }
.lv-pdf-icon { width:60px; height:60px; background:var(--kt-gray-200); border-radius:14px; display:flex; align-items:center; justify-content:center; }
.lv-pdf-name { font-size:12px; color:var(--kt-text-muted); text-align:center; word-break:break-all; }
.lv-btn-success { display:inline-flex; align-items:center; gap:6px; background:#50cd89; border:none; color:#fff; border-radius:9px; padding:9px 18px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; transition:background 0.14s; }
.lv-btn-success:hover:not(:disabled) { background:#47be7d; }
.lv-btn-success:disabled { opacity:0.5; cursor:not-allowed; }
.lv-btn-danger { display:inline-flex; align-items:center; gap:6px; background:#f1416c; border:none; color:#fff; border-radius:9px; padding:9px 18px; font-size:13px; font-weight:600; cursor:pointer; transition:background 0.14s; }
.lv-btn-danger:hover:not(:disabled) { background:#d63059; }
.lv-btn-danger:disabled { opacity:0.5; cursor:not-allowed; }
.lv-btn-outline { display:inline-flex; align-items:center; background:transparent; border:1px solid var(--kt-border-color); color:var(--kt-text-muted); border-radius:9px; padding:8px 16px; font-size:13px; font-weight:500; cursor:pointer; text-decoration:none; transition:all 0.14s; }
.lv-btn-outline:hover { border-color:rgba(27,132,255,0.4); color:#1b84ff; }
.lv-btn-ghost { background:var(--kt-gray-200); border:1px solid var(--kt-border-color); color:var(--kt-text-muted); border-radius:9px; padding:9px 18px; font-size:13px; font-weight:500; cursor:pointer; transition:all 0.14s; }
.lv-btn-ghost:hover { color:var(--kt-text-dark); }
@media(max-width:1280px) { .lv-stats{grid-template-columns:repeat(3,1fr)} }
@media(max-width:768px) {
  .lv-stats{grid-template-columns:repeat(2,1fr)}
  .lv-kuota-grid{grid-template-columns:1fr}
  .lv-header{flex-direction:column;align-items:flex-start}
  .lv-overlay{align-items:flex-end;padding:0}
  .lv-modal{border-radius:20px 20px 0 0;max-width:100%}
}
</style>

<!-- Dropdown styles HARUS global karena pakai Teleport to="body" -->
<style>
.lv-dropdown-menu {
  position: fixed;
  background: var(--kt-card-bg, #1c1e2d);
  border: 1px solid var(--kt-border-color, rgba(255,255,255,0.1));
  border-radius: 12px;
  padding: 8px;
  z-index: 99999;
  box-shadow: 0 20px 60px rgba(0,0,0,0.55), 0 0 0 1px rgba(255,255,255,0.04);
  min-width: 220px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3px;
  animation: lv-dropdown-in 0.15s ease;
}
.lv-dropdown-menu-sm {
  grid-template-columns: 1fr;
  min-width: 110px;
}
.lv-dropdown-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  background: transparent;
  border: none;
  border-radius: 8px;
  color: var(--kt-text-muted, #9899ac);
  font-size: 13px;
  font-weight: 500;
  padding: 8px 12px;
  cursor: pointer;
  transition: all 0.12s;
  text-align: left;
  white-space: nowrap;
  font-family: inherit;
  width: 100%;
}
.lv-dropdown-item:hover {
  background: var(--kt-gray-100, rgba(255,255,255,0.06));
  color: var(--kt-text-dark, #f0f0f5);
}
.lv-dropdown-item.active {
  background: rgba(27,132,255,0.12);
  color: #1b84ff;
  font-weight: 700;
}
@keyframes lv-dropdown-in {
  from { opacity: 0; transform: translateY(-8px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
</style>