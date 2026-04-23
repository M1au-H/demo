<template>
  <div class="pf-root" @click="closeDd">

    <!-- ── HEADER ── -->
    <div class="pf-header">
      <div class="pf-header-left">
        <div class="pf-header-eyebrow">HR Management</div>
        <h1 class="pf-header-title">Penilaian <span class="pf-title-accent">Performa</span></h1>
        <p class="pf-header-sub">Beri nilai harian untuk pegawai yang sudah absen</p>
      </div>
      <div class="pf-header-right">
        <div v-if="lastSaved" class="pf-saved-badge">
          <div class="pf-saved-pulse"></div>
          <span>Terakhir disimpan: {{ lastSaved }}</span>
        </div>
      </div>
    </div>

    <!-- ── STATS ── -->
    <div class="pf-stats">
      <div class="pf-stat" v-for="s in statCards" :key="s.key" :style="`--sc:${s.color}`">
        <div class="pf-stat-top">
          <div class="pf-stat-icon" v-html="s.icon"></div>
          <div class="pf-stat-val">{{ s.value }}</div>
        </div>
        <div class="pf-stat-label">{{ s.label }}</div>
        <div class="pf-stat-bar"><div class="pf-stat-bar-fill" :style="`width:${s.pct}%`"></div></div>
      </div>
    </div>

    <!-- ── MAIN GRID ── -->
    <div class="pf-grid">

      <!-- ── FORM PENILAIAN ── -->
      <div class="pf-card">
        <div class="pf-card-head">
          <div class="pf-card-title">
            <span class="pf-dot" style="background:#1b84ff"></span>
            Beri Nilai Hari Ini
          </div>
          <div class="pf-badge">{{ todayLabel }}</div>
        </div>

        <!-- Pilih Pegawai — custom dropdown -->
        <div class="pf-field">
          <label class="pf-label">Pilih Pegawai</label>
          <button
            ref="triggerForm"
            type="button"
            class="pf-dd-trigger"
            :class="{ 'pf-dd-trigger--open': openDd === 'form', 'pf-dd-trigger--disabled': loadingToday }"
            :disabled="loadingToday"
            @click.stop="toggleDd('form', $event)"
          >
            <span class="pf-dd-left">
              <span v-if="form.user_id" class="pf-dd-av" :style="`background:${avatarColor(getEmpName(form.user_id))}`">
                {{ getEmpName(form.user_id).charAt(0).toUpperCase() }}
              </span>
              <svg v-else class="pf-dd-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/></svg>
              <span :class="form.user_id ? 'pf-dd-val' : 'pf-dd-ph'">
                {{ form.user_id ? getEmpNameFull(form.user_id) : 'Pilih pegawai...' }}
              </span>
            </span>
            <span v-if="loadingToday" class="pf-spinner"></span>
            <svg v-else class="pf-dd-chev" :class="{ 'pf-dd-chev--up': openDd === 'form' }" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
        </div>

        <!-- Status Absensi -->
        <transition name="pf-slide">
          <div v-if="attendanceStatus && form.user_id" class="pf-status" :class="`pf-status-${attendanceStatus.type}`">
            <div class="pf-status-icon">
              <svg v-if="attendanceStatus.type === 'present'" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="pf-status-body">
              <span class="pf-status-title">{{ attendanceStatus.title }}</span>
              <span class="pf-status-desc">{{ attendanceStatus.desc }}</span>
            </div>
          </div>
        </transition>

        <!-- Form aktif -->
        <template v-if="!form.user_id || attendanceStatus?.type === 'present'">
          <div class="pf-field">
            <label class="pf-label">Rating Performa</label>
            <div class="pf-stars">
              <button
                v-for="n in 5" :key="n"
                @click="setRating(n)"
                :class="['pf-star', n <= (hoveredStar || form.rating) ? 'pf-star-on' : '']"
                @mouseenter="hoveredStar = form.user_id ? n : 0"
                @mouseleave="hoveredStar = 0"
                type="button"
                :disabled="!form.user_id"
              >
                <svg width="30" height="30" viewBox="0 0 24 24"
                  :fill="n <= (hoveredStar || form.rating) ? 'currentColor' : 'none'"
                  stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
              </button>
            </div>
            <div v-if="form.rating > 0" class="pf-rating-badge" :class="`pf-rbadge-${form.rating}`">
              <span class="pf-rbadge-num">{{ form.rating }}/5</span>
              <span class="pf-rbadge-sep">·</span>
              <span>{{ ratingLabel }}</span>
            </div>
            <div v-else-if="form.user_id" class="pf-rating-hint">Klik bintang untuk memberi nilai</div>
          </div>

          <div class="pf-field">
            <label class="pf-label">Catatan <span class="pf-label-opt">opsional</span></label>
            <textarea v-model="form.comment" class="pf-textarea" rows="3"
              placeholder="Tulis catatan performa hari ini..." :disabled="!form.user_id"></textarea>
          </div>

          <transition name="pf-fade">
            <div v-if="message" :class="['pf-msg', msgType]">
              <svg v-if="msgType === 'pf-msg-ok'" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              {{ message }}
            </div>
          </transition>

          <button @click="submit" :disabled="loading || !form.user_id || !form.rating" class="pf-btn">
            <svg v-if="!loading" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span v-if="loading" class="pf-spinner pf-spinner-w"></span>
            {{ loading ? 'Menyimpan...' : 'Simpan Penilaian' }}
          </button>
        </template>

        <!-- Locked state -->
        <template v-else-if="attendanceStatus?.type === 'absent'">
          <div class="pf-locked">
            <div class="pf-locked-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <p>Form terkunci</p>
            <span>Pegawai belum melakukan absensi masuk hari ini</span>
          </div>
        </template>
      </div>

      <!-- ── RIWAYAT ── -->
      <div class="pf-card">
        <div class="pf-card-head">
          <div class="pf-card-title">
            <span class="pf-dot" style="background:#fbbf24"></span>
            Riwayat Penilaian
          </div>
          <div class="pf-badge">{{ reviews.length }} entri</div>
        </div>

        <!-- Pilih Pegawai Riwayat — custom dropdown -->
        <div class="pf-field">
          <label class="pf-label">Pegawai</label>
          <button
            ref="triggerHistory"
            type="button"
            class="pf-dd-trigger"
            :class="{ 'pf-dd-trigger--open': openDd === 'history' }"
            @click.stop="toggleDd('history', $event)"
          >
            <span class="pf-dd-left">
              <span v-if="historyUserId" class="pf-dd-av" :style="`background:${avatarColor(getEmpName(historyUserId))}`">
                {{ getEmpName(historyUserId).charAt(0).toUpperCase() }}
              </span>
              <svg v-else class="pf-dd-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/></svg>
              <span :class="historyUserId ? 'pf-dd-val' : 'pf-dd-ph'">
                {{ historyUserId ? getEmpName(historyUserId) : 'Pilih pegawai...' }}
              </span>
            </span>
            <svg class="pf-dd-chev" :class="{ 'pf-dd-chev--up': openDd === 'history' }" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
        </div>

        <div v-if="loadingHistory" class="pf-skels">
          <div class="pf-skel" v-for="n in 4" :key="n" :style="`animation-delay:${n*0.07}s`"></div>
        </div>
        <div v-else-if="reviews.length === 0 && historyUserId" class="pf-empty">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <p>Belum ada penilaian</p>
        </div>
        <div v-else-if="!historyUserId" class="pf-empty">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/></svg>
          <p>Pilih pegawai untuk melihat riwayat</p>
        </div>
        <div v-else class="pf-review-list">
          <div v-for="(r, i) in reviews" :key="r.id" class="pf-review" :style="`animation-delay:${i*0.04}s`">
            <div class="pf-review-top">
              <div class="pf-review-date">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                {{ formatDate(r.review_date) }}
              </div>
              <div class="pf-review-stars-row">
                <svg v-for="n in 5" :key="n" width="12" height="12" viewBox="0 0 24 24"
                  :fill="n <= r.rating ? '#fbbf24' : 'none'"
                  :stroke="n <= r.rating ? '#fbbf24' : 'rgba(255,255,255,0.15)'"
                  stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span :class="['pf-review-score', `pf-rbadge-${r.rating}`]">{{ r.rating }}/5</span>
              </div>
            </div>
            <p v-if="r.comment" class="pf-review-comment">"{{ r.comment }}"</p>
            <div class="pf-review-by">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Dinilai oleh {{ r.reviewer?.name ?? 'Admin' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── STATUS ABSENSI PEGAWAI GRID ── -->
    <div class="pf-card pf-card-full" v-if="employees.length">
      <div class="pf-card-head">
        <div class="pf-card-title">
          <span class="pf-dot" style="background:#7239ea"></span>
          Status Absensi Hari Ini
          <span class="pf-info-hint">— Klik pegawai yang hadir untuk langsung menilai</span>
        </div>
        <div class="pf-badge">{{ todayAttendances.length }} hadir</div>
      </div>
      <div class="pf-emp-grid">
        <div
          v-for="emp in employees" :key="emp.id"
          class="pf-emp-card"
          :class="isPresent(emp.id) ? 'pf-emp-present' : 'pf-emp-absent'"
          @click="selectEmployee(emp)"
        >
          <div class="pf-emp-avatar" :style="`background:${avatarColor(emp.name)}`">
            {{ emp.name.charAt(0).toUpperCase() }}
          </div>
          <div class="pf-emp-body">
            <div class="pf-emp-name">{{ emp.name }}</div>
            <div class="pf-emp-status">
              <span class="pf-emp-dot" :class="isPresent(emp.id) ? 'pf-emp-dot-on' : 'pf-emp-dot-off'"></span>
              {{ isPresent(emp.id) ? getCheckIn(emp.id) : 'Belum absen' }}
            </div>
          </div>
          <div v-if="isPresent(emp.id)" class="pf-emp-action">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ DROPDOWN PANEL — Teleport ke body ══ -->
    <Teleport to="body">
      <div v-if="openDd" class="pf-dd-panel" :style="ddStyle" @click.stop>
        <!-- Search -->
        <div class="pf-dd-search-wrap">
          <svg class="pf-dd-search-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input ref="searchRef" v-model="searchQ" class="pf-dd-search" placeholder="Cari pegawai..." />
        </div>
        <!-- List -->
        <div class="pf-dd-list">
          <div v-if="ddFilteredEmps.length === 0" class="pf-dd-empty">Pegawai tidak ditemukan</div>
          <button
            v-for="emp in ddFilteredEmps" :key="emp.id"
            type="button"
            :class="['pf-dd-item', ddCurrentVal === emp.id && 'pf-dd-item--on']"
            @click="pickEmp(emp)"
          >
            <!-- Avatar + status hadir/tidak -->
            <span class="pf-dd-item-av" :style="`background:${avatarColor(emp.name)}`">
              {{ emp.name.charAt(0).toUpperCase() }}
            </span>
            <span class="pf-dd-item-body">
              <span class="pf-dd-item-name">{{ emp.name }}</span>
              <span v-if="openDd === 'form'" class="pf-dd-item-sub" :class="isPresent(emp.id) ? 'pf-dd-sub-ok' : 'pf-dd-sub-off'">
                <span class="pf-dd-sub-dot"></span>
                {{ isPresent(emp.id) ? getCheckIn(emp.id) : 'Belum absen' }}
              </span>
            </span>
            <svg v-if="ddCurrentVal === emp.id" class="pf-dd-item-check" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1b84ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </button>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, nextTick } from 'vue'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'ReviewForm',
  setup() {
    const employees        = ref<any[]>([])
    const reviews          = ref<any[]>([])
    const loading          = ref(false)
    const loadingHistory   = ref(false)
    const loadingToday     = ref(false)
    const historyUserId    = ref<any>('')
    const message          = ref('')
    const msgType          = ref('pf-msg-ok')
    const hoveredStar      = ref(0)
    const lastSaved        = ref('')
    const todayAttendances = ref<any[]>([])
    const attendanceStatus = ref<{ type: 'present' | 'absent'; title: string; desc: string } | null>(null)
    const form             = ref({ user_id: <any>'', rating: 0, comment: '' })

    // ── Dropdown state ──────────────────────────────────────────
    const openDd    = ref('')          // 'form' | 'history' | ''
    const searchQ   = ref('')
    const ddStyle   = ref<Record<string, string>>({})
    const searchRef = ref<HTMLInputElement | null>(null)

    const triggerForm    = ref<HTMLElement | null>(null)
    const triggerHistory = ref<HTMLElement | null>(null)

    // Nilai yang sedang aktif di dropdown terbuka
    const ddCurrentVal = computed(() => {
      if (openDd.value === 'form')    return form.value.user_id
      if (openDd.value === 'history') return historyUserId.value
      return ''
    })

    // Daftar pegawai yang sudah difilter search
    const ddFilteredEmps = computed(() =>
      employees.value.filter(e =>
        e.name.toLowerCase().includes(searchQ.value.toLowerCase())
      )
    )

    // Hitung posisi panel tepat di bawah (atau atas) trigger button
    const calcPos = (el: HTMLElement) => {
      const r       = el.getBoundingClientRect()
      const panelW  = Math.max(r.width, 280)
      const panelH  = 340
      const below   = window.innerHeight - r.bottom
      const top     = below > panelH
        ? r.bottom + window.scrollY + 6
        : r.top + window.scrollY - panelH - 6
      let left = r.left + window.scrollX
      if (left + panelW > window.innerWidth - 12) left = window.innerWidth - panelW - 12
      ddStyle.value = {
        position : 'absolute',
        top      : `${top}px`,
        left     : `${left}px`,
        width    : `${panelW}px`,
        zIndex   : '99999',
      }
    }

    const toggleDd = async (key: string, _e: MouseEvent) => {
      if (openDd.value === key) { openDd.value = ''; return }
      searchQ.value = ''
      openDd.value  = key
      const elMap: Record<string, typeof triggerForm> = {
        form    : triggerForm,
        history : triggerHistory,
      }
      const el = elMap[key]?.value
      if (el) calcPos(el)
      await nextTick()
      searchRef.value?.focus()
    }

    const closeDd = () => { openDd.value = '' }

    const pickEmp = (emp: any) => {
      if (openDd.value === 'form') {
        form.value.user_id = emp.id
        onEmployeeChange()
      } else if (openDd.value === 'history') {
        historyUserId.value = emp.id
        loadHistory()
      }
      closeDd()
    }

    // ── Helpers ──────────────────────────────────────────────────
    const getEmpName     = (id: any) => employees.value.find(e => e.id == id)?.name ?? ''
    const getEmpNameFull = (id: any) => {
      const emp = employees.value.find(e => e.id == id)
      return emp ? `${emp.name}${emp.position ? ` — ${emp.position.name}` : ''}` : ''
    }

    const todayLabel = computed(() =>
      new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
    )

    const ratingLabel = computed(() => {
      const labels = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik']
      return labels[form.value.rating] ?? ''
    })

    const statCards = computed(() => {
      const hadir  = todayAttendances.value.length
      const total  = employees.value.length || 1
      const absent = Math.max(0, total - hadir)
      return [
        { key:'total',  label:'Total Pegawai',  value: employees.value.length, pct: 100, color:'#1b84ff',
          icon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>` },
        { key:'hadir',  label:'Hadir Hari Ini', value: hadir,  pct: Math.round(hadir/total*100),  color:'#50cd89',
          icon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>` },
        { key:'absent', label:'Belum Absen',    value: absent, pct: Math.round(absent/total*100), color:'#f1416c',
          icon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>` },
        { key:'review', label:'Sudah Dinilai',  value: reviews.value.length, pct: reviews.value.length ? Math.round(reviews.value.length/total*100) : 0, color:'#fbbf24',
          icon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>` },
      ]
    })

    const isPresent  = (userId: number) =>
      todayAttendances.value.some((a: any) => Number(a.user_id) === userId || Number(a.user?.id) === userId)

    const getCheckIn = (userId: number) => {
      const att = todayAttendances.value.find((a: any) => Number(a.user_id) === userId || Number(a.user?.id) === userId)
      if (!att) return ''
      const time = att.check_in_time?.substring(0, 5) ?? ''
      return att.status === 'late' ? `${time} · Terlambat ${att.late_minutes}mnt` : `${time} · Tepat waktu`
    }

    const selectEmployee = (emp: any) => {
      if (!isPresent(emp.id)) return
      form.value.user_id = emp.id
      onEmployeeChange()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }

    const setRating = (n: number) => { if (form.value.user_id) form.value.rating = n }

    // ── API ──────────────────────────────────────────────────────
    const loadEmployees = async () => {
      try { ApiService.setHeader(); const { data } = await ApiService.get('admin/employees'); employees.value = data.data } catch (_) {}
    }

    const loadTodayAttendances = async () => {
      loadingToday.value = true
      try { ApiService.setHeader(); const { data } = await ApiService.get('admin/attendance/today'); todayAttendances.value = data.data ?? [] }
      catch (_) { todayAttendances.value = [] }
      finally { loadingToday.value = false }
    }

    const onEmployeeChange = () => {
      attendanceStatus.value = null; message.value = ''
      form.value.rating = 0; form.value.comment = ''
      if (!form.value.user_id) return
      const userId = Number(form.value.user_id)
      const att = todayAttendances.value.find(
        (a: any) => Number(a.user_id) === userId || Number(a.user?.id) === userId
      )
      if (att && att.check_in_time) {
        const statusLabel = att.status === 'late' ? `Terlambat ${att.late_minutes} menit` : 'Tepat Waktu'
        attendanceStatus.value = { type: 'present', title: 'Sudah absen hari ini', desc: `Jam masuk: ${att.check_in_time} · ${statusLabel}` }
      } else {
        attendanceStatus.value = { type: 'absent', title: 'Pegawai belum absen hari ini', desc: 'Penilaian performa hanya bisa diberikan jika pegawai sudah melakukan absensi masuk.' }
      }
    }

    const loadHistory = async () => {
      if (!historyUserId.value) return
      loadingHistory.value = true
      try { ApiService.setHeader(); const { data } = await ApiService.get(`admin/performance/${historyUserId.value}`); reviews.value = data.data }
      catch (_) {} finally { loadingHistory.value = false }
    }

    const submit = async () => {
      if (!attendanceStatus.value || attendanceStatus.value.type === 'absent') {
        message.value = 'Tidak dapat menyimpan. Pegawai belum absen hari ini.'; msgType.value = 'pf-msg-err'; return
      }
      loading.value = true; message.value = ''
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/performance/${form.value.user_id}`, { rating: form.value.rating, comment: form.value.comment })
        message.value = 'Penilaian berhasil disimpan!'; msgType.value = 'pf-msg-ok'
        lastSaved.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
        form.value = { user_id: '', rating: 0, comment: '' }; attendanceStatus.value = null
      } catch (err: any) {
        message.value = err?.response?.data?.message ?? 'Gagal menyimpan penilaian.'; msgType.value = 'pf-msg-err'
      } finally { loading.value = false }
    }

    const formatDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })

    const AVATAR_PALETTE = ['#1b84ff','#7239ea','#f1416c','#ffc700','#50cd89','#00b2ff','#fd7e14']
    const avatarColor = (name: string) => AVATAR_PALETTE[(name?.charCodeAt(0) || 0) % AVATAR_PALETTE.length]

    onMounted(async () => { await Promise.all([loadEmployees(), loadTodayAttendances()]) })

    return {
      employees, reviews, loading, loadingHistory, loadingToday, lastSaved,
      historyUserId, message, msgType, form, ratingLabel, todayLabel,
      attendanceStatus, hoveredStar, todayAttendances, statCards,
      openDd, searchQ, ddStyle, searchRef,
      triggerForm, triggerHistory,
      ddCurrentVal, ddFilteredEmps,
      toggleDd, closeDd, pickEmp, getEmpName, getEmpNameFull,
      isPresent, getCheckIn, selectEmployee, setRating,
      onEmployeeChange, loadHistory, submit, formatDate, avatarColor,
    }
  }
})
</script>

<style scoped>
.pf-root { padding: 24px 20px; }

/* ── HEADER ── */
.pf-header { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:26px; flex-wrap:wrap; gap:14px; }
.pf-header-eyebrow { font-size:11px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--kt-text-muted); margin-bottom:6px; }
.pf-header-title { font-size:26px; font-weight:800; color:var(--kt-text-dark); margin:0 0 5px; letter-spacing:-0.02em; line-height:1; }
.pf-title-accent { color:#fbbf24; }
.pf-header-sub { font-size:13px; color:var(--kt-text-muted); margin:0; }
.pf-saved-badge { display:flex; align-items:center; gap:9px; background:rgba(80,205,137,0.08); border:1px solid rgba(80,205,137,0.2); border-radius:100px; padding:8px 16px; font-size:12.5px; font-weight:600; color:#50cd89; }
.pf-saved-pulse { width:7px; height:7px; border-radius:50%; background:#50cd89; flex-shrink:0; animation:pf-pulse 1.6s ease infinite; }
@keyframes pf-pulse { 0%,100%{box-shadow:0 0 0 0 rgba(80,205,137,.5)} 50%{box-shadow:0 0 0 5px rgba(80,205,137,0)} }

/* ── STATS ── */
.pf-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.pf-stat { background:var(--kt-card-bg); border:1px solid var(--kt-border-color); border-radius:14px; padding:16px; position:relative; overflow:hidden; transition:transform 0.14s,border-color 0.14s; }
.pf-stat:hover { transform:translateY(-2px); border-color:color-mix(in srgb,var(--sc) 35%,transparent); }
.pf-stat::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:var(--sc); opacity:0.8; }
.pf-stat-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:10px; }
.pf-stat-icon { width:36px; height:36px; border-radius:10px; background:color-mix(in srgb,var(--sc) 14%,transparent); color:var(--sc); display:flex; align-items:center; justify-content:center; }
.pf-stat-val { font-size:26px; font-weight:800; color:var(--kt-text-dark); line-height:1; }
.pf-stat-label { font-size:11.5px; color:var(--kt-text-muted); font-weight:500; margin-bottom:10px; }
.pf-stat-bar { height:3px; background:var(--kt-gray-200); border-radius:2px; overflow:hidden; }
.pf-stat-bar-fill { height:100%; background:var(--sc); border-radius:2px; opacity:0.7; transition:width 0.5s ease; }

/* ── GRID ── */
.pf-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }

/* ── CARD ── */
.pf-card { background:var(--kt-card-bg); border:1px solid var(--kt-border-color); border-radius:16px; padding:20px; overflow:visible; }
.pf-card-full { }
.pf-card-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; padding-bottom:16px; border-bottom:1px solid var(--kt-border-color); }
.pf-card-title { display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; color:var(--kt-text-dark); }
.pf-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.pf-info-hint { font-size:11px; font-weight:400; color:var(--kt-text-muted); font-style:italic; }
.pf-badge { font-size:12px; font-weight:600; border-radius:20px; padding:3px 12px; border:1px solid var(--kt-border-color); color:var(--kt-text-muted); background:var(--kt-gray-100); }

/* ── FIELDS ── */
.pf-field { margin-bottom:16px; }
.pf-label { display:flex; align-items:center; gap:6px; font-size:10.5px; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:var(--kt-text-muted); margin-bottom:8px; }
.pf-label-opt { text-transform:none; letter-spacing:0; font-weight:400; opacity:0.55; font-size:10px; }

/* ── CUSTOM DROPDOWN TRIGGER ── */
.pf-dd-trigger {
  width:100%; display:flex; align-items:center; justify-content:space-between;
  background:var(--kt-gray-100); border:1.5px solid var(--kt-border-color);
  border-radius:10px; padding:9px 13px; min-height:42px;
  cursor:pointer; font-family:inherit;
  transition:border-color 0.15s, box-shadow 0.15s;
}
.pf-dd-trigger:hover:not(:disabled)  { border-color:rgba(27,132,255,0.35); }
.pf-dd-trigger--open                 { border-color:rgba(27,132,255,0.55) !important; box-shadow:0 0 0 3px rgba(27,132,255,0.1); }
.pf-dd-trigger--disabled, .pf-dd-trigger:disabled { opacity:0.5; cursor:not-allowed; }
.pf-dd-left  { display:flex; align-items:center; gap:9px; flex:1; min-width:0; }
.pf-dd-av    { width:24px; height:24px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff; flex-shrink:0; }
.pf-dd-ico   { color:var(--kt-text-muted); flex-shrink:0; }
.pf-dd-ph    { font-size:13px; color:var(--kt-text-muted); opacity:0.55; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.pf-dd-val   { font-size:13px; color:var(--kt-text-dark); font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.pf-dd-chev  { color:var(--kt-text-muted); flex-shrink:0; transition:transform 0.2s ease; }
.pf-dd-chev--up { transform:rotate(180deg); color:#1b84ff; }

/* ── TEXTAREA ── */
.pf-textarea { width:100%; background:var(--kt-gray-100); border:1.5px solid var(--kt-border-color); border-radius:10px; color:var(--kt-text-dark); padding:10px 14px; font-size:13px; outline:none; resize:vertical; font-family:inherit; box-sizing:border-box; transition:border-color 0.15s; min-height:80px; }
.pf-textarea:focus { border-color:rgba(27,132,255,0.5); }
.pf-textarea:disabled { opacity:0.4; cursor:not-allowed; }
.pf-textarea::placeholder { color:var(--kt-text-muted); opacity:0.5; }

/* ── STATUS ── */
.pf-status { display:flex; align-items:flex-start; gap:10px; padding:10px 13px; border-radius:10px; margin-bottom:16px; }
.pf-status-present { background:rgba(23,198,83,0.07); border:1px solid rgba(23,198,83,0.2); }
.pf-status-absent  { background:rgba(255,107,107,0.07); border:1px solid rgba(255,107,107,0.2); }
.pf-status-icon { flex-shrink:0; margin-top:1px; }
.pf-status-present .pf-status-icon { color:#17c653; }
.pf-status-absent  .pf-status-icon { color:#ff6b6b; }
.pf-status-body { display:flex; flex-direction:column; gap:2px; }
.pf-status-title { font-size:12.5px; font-weight:700; }
.pf-status-present .pf-status-title { color:#17c653; }
.pf-status-absent  .pf-status-title { color:#ff6b6b; }
.pf-status-desc { font-size:11.5px; color:var(--kt-text-muted); line-height:1.5; }

/* ── STARS ── */
.pf-stars { display:flex; gap:2px; margin-bottom:10px; }
.pf-star { background:none; border:none; padding:3px; cursor:pointer; color:var(--kt-border-color); transition:color 0.12s,transform 0.12s; border-radius:6px; line-height:0; }
.pf-star:hover:not(:disabled) { transform:scale(1.2); }
.pf-star:disabled { cursor:not-allowed; opacity:0.3; }
.pf-star-on { color:#fbbf24; }

/* ── RATING BADGE ── */
.pf-rating-badge { display:inline-flex; align-items:center; gap:7px; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
.pf-rbadge-1 { background:rgba(241,65,108,0.1);  color:#f1416c; border:1px solid rgba(241,65,108,0.2); }
.pf-rbadge-2 { background:rgba(255,107,107,0.1); color:#ff6b6b; border:1px solid rgba(255,107,107,0.2); }
.pf-rbadge-3 { background:rgba(255,199,0,0.1);   color:#ffc700; border:1px solid rgba(255,199,0,0.2); }
.pf-rbadge-4 { background:rgba(80,205,137,0.1);  color:#50cd89; border:1px solid rgba(80,205,137,0.2); }
.pf-rbadge-5 { background:rgba(27,132,255,0.1);  color:#1b84ff; border:1px solid rgba(27,132,255,0.2); }
.pf-rbadge-num { font-size:13px; font-weight:800; }
.pf-rbadge-sep { opacity:0.4; }
.pf-rating-hint { font-size:11.5px; color:var(--kt-text-muted); opacity:0.5; padding:2px 0; }

/* ── MSG ── */
.pf-msg { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:9px; font-size:12.5px; font-weight:500; margin-bottom:12px; }
.pf-msg-ok  { background:rgba(23,198,83,0.08);  color:#17c653; border:1px solid rgba(23,198,83,0.2); }
.pf-msg-err { background:rgba(255,107,107,0.08); color:#ff6b6b; border:1px solid rgba(255,107,107,0.2); }

/* ── BUTTON ── */
.pf-btn { width:100%; display:flex; align-items:center; justify-content:center; gap:8px; background:#1b84ff; color:#fff; border:none; border-radius:10px; padding:12px; font-size:13.5px; font-weight:700; cursor:pointer; margin-top:4px; transition:background 0.15s,transform 0.12s; }
.pf-btn:hover:not(:disabled) { background:#3d9eff; transform:translateY(-1px); }
.pf-btn:disabled { opacity:0.45; cursor:not-allowed; transform:none; }

/* ── LOCKED ── */
.pf-locked { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; padding:40px 20px; text-align:center; color:var(--kt-text-muted); border:1.5px dashed var(--kt-border-color); border-radius:12px; margin-top:4px; }
.pf-locked-icon { width:52px; height:52px; border-radius:14px; background:var(--kt-gray-200); display:flex; align-items:center; justify-content:center; }
.pf-locked p { font-size:13px; font-weight:600; margin:0; }
.pf-locked span { font-size:11.5px; opacity:0.6; line-height:1.5; }

/* ── SPINNER ── */
@keyframes pf-spin { to { transform:rotate(360deg); } }
.pf-spinner { display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,0.15); border-top-color:var(--kt-text-muted); border-radius:50%; animation:pf-spin 0.65s linear infinite; flex-shrink:0; }
.pf-spinner-w { border-top-color:#fff; }

/* ── REVIEW LIST ── */
.pf-review-list { display:flex; flex-direction:column; gap:8px; max-height:500px; overflow-y:auto; padding-right:2px; }
.pf-review-list::-webkit-scrollbar { width:3px; }
.pf-review-list::-webkit-scrollbar-thumb { background:var(--kt-border-color); border-radius:2px; }
.pf-review { background:var(--kt-gray-100); border:1px solid var(--kt-border-color); border-radius:10px; padding:12px 14px; transition:border-color 0.14s; animation:pf-up 0.2s ease both; }
.pf-review:hover { border-color:rgba(251,191,36,0.3); }
.pf-review-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:7px; }
.pf-review-date { display:flex; align-items:center; gap:5px; font-size:11.5px; color:var(--kt-text-muted); }
.pf-review-stars-row { display:flex; align-items:center; gap:2px; }
.pf-review-score { font-size:11px; font-weight:700; margin-left:6px; padding:2px 7px; border-radius:10px; }
.pf-review-comment { font-size:12.5px; color:var(--kt-text-dark); margin:0 0 7px; line-height:1.5; opacity:0.85; }
.pf-review-by { display:flex; align-items:center; gap:5px; font-size:11px; color:var(--kt-text-muted); opacity:0.6; }

/* ── SKELETON ── */
.pf-skels { display:flex; flex-direction:column; gap:8px; }
.pf-skel { height:68px; border-radius:10px; background:linear-gradient(90deg,var(--kt-gray-200) 25%,var(--kt-gray-300) 50%,var(--kt-gray-200) 75%); background-size:300%; animation:pf-shimmer 1.4s infinite; }
@keyframes pf-shimmer { from{background-position:300%} to{background-position:-300%} }

/* ── EMPTY ── */
.pf-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; padding:40px 20px; text-align:center; color:var(--kt-text-muted); }
.pf-empty p { font-size:13px; margin:0; opacity:0.6; }

/* ── EMP GRID ── */
.pf-emp-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:10px; }
.pf-emp-card { display:flex; align-items:center; gap:10px; background:var(--kt-gray-100); border:1px solid var(--kt-border-color); border-radius:10px; padding:10px 12px; transition:border-color 0.14s,transform 0.12s; }
.pf-emp-present { cursor:pointer; }
.pf-emp-present:hover { border-color:rgba(27,132,255,0.35); transform:translateY(-1px); }
.pf-emp-absent { opacity:0.4; }
.pf-emp-avatar { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#fff; flex-shrink:0; }
.pf-emp-body { flex:1; min-width:0; }
.pf-emp-name { font-size:12.5px; font-weight:600; color:var(--kt-text-dark); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pf-emp-status { display:flex; align-items:center; gap:5px; font-size:11px; color:var(--kt-text-muted); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pf-emp-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
.pf-emp-dot-on  { background:#17c653; box-shadow:0 0 5px rgba(23,198,83,0.5); }
.pf-emp-dot-off { background:rgba(255,255,255,0.2); }
.pf-emp-action { color:#1b84ff; opacity:0.6; flex-shrink:0; transition:opacity 0.14s; }
.pf-emp-present:hover .pf-emp-action { opacity:1; }

/* ── ANIMATION ── */
@keyframes pf-up { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }

/* ── TRANSITIONS ── */
.pf-slide-enter-active, .pf-slide-leave-active { transition:all 0.2s ease; }
.pf-slide-enter-from, .pf-slide-leave-to { opacity:0; transform:translateY(-6px); }
.pf-fade-enter-active, .pf-fade-leave-active { transition:opacity 0.2s ease; }
.pf-fade-enter-from, .pf-fade-leave-to { opacity:0; }

/* ── RESPONSIVE ── */
@media(max-width:1280px) { .pf-stats{grid-template-columns:repeat(2,1fr)} }
@media(max-width:768px) { .pf-grid{grid-template-columns:1fr} .pf-stats{grid-template-columns:repeat(2,1fr)} .pf-header{flex-direction:column;align-items:flex-start} }
</style>

<!-- Panel dropdown ke body — TIDAK scoped agar tidak ter-clip overflow -->
<style>
.pf-dd-panel {
  background: var(--kt-card-bg, #1e1e2d);
  border: 1.5px solid var(--kt-border-color, rgba(255,255,255,0.1));
  border-radius: 14px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.5), 0 4px 20px rgba(0,0,0,0.25);
  overflow: hidden;
  animation: pf-panel-in 0.14s cubic-bezier(0.16,1,0.3,1);
}
@keyframes pf-panel-in {
  from { opacity:0; transform:translateY(-8px) scale(0.97); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}

.pf-dd-search-wrap {
  position: relative;
  padding: 10px 10px 8px;
  border-bottom: 1px solid var(--kt-border-color, rgba(255,255,255,0.08));
}
.pf-dd-search-ico {
  position: absolute; left: 21px; top: 50%; transform: translateY(-22%);
  color: var(--kt-text-muted, #7e8299); pointer-events: none;
}
.pf-dd-search {
  width: 100%; box-sizing: border-box;
  background: var(--kt-gray-100, rgba(255,255,255,0.05));
  border: 1.5px solid var(--kt-border-color, rgba(255,255,255,0.1));
  border-radius: 8px; color: var(--kt-text-dark, #fff);
  padding: 8px 10px 8px 32px;
  font-size: 13px; font-family: inherit; outline: none;
  transition: border-color 0.14s;
}
.pf-dd-search:focus { border-color: rgba(27,132,255,0.5); }
.pf-dd-search::placeholder { color: var(--kt-text-muted, #7e8299); opacity: 0.5; }

.pf-dd-list {
  max-height: 260px; overflow-y: auto; padding: 6px;
  scrollbar-width: thin;
  scrollbar-color: var(--kt-border-color, rgba(255,255,255,0.1)) transparent;
}
.pf-dd-list::-webkit-scrollbar { width: 4px; }
.pf-dd-list::-webkit-scrollbar-thumb { background: var(--kt-border-color, rgba(255,255,255,0.1)); border-radius: 4px; }

.pf-dd-empty { padding: 14px; text-align: center; font-size: 12px; color: var(--kt-text-muted, #7e8299); }

.pf-dd-item {
  width: 100%; display: flex; align-items: center; gap: 10px;
  background: transparent; border: none; border-radius: 8px;
  padding: 8px 10px; cursor: pointer; font-family: inherit;
  transition: background 0.1s; text-align: left;
}
.pf-dd-item:hover { background: var(--kt-gray-100, rgba(255,255,255,0.05)); }
.pf-dd-item--on   { background: rgba(27,132,255,0.08); }

.pf-dd-item-av {
  width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: #fff;
}
.pf-dd-item-body {
  flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px;
}
.pf-dd-item-name {
  font-size: 13px; font-weight: 500;
  color: var(--kt-text-dark, #fff);
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.pf-dd-item--on .pf-dd-item-name { color: #1b84ff; font-weight: 600; }

/* Sub-info status absen di dropdown form penilaian */
.pf-dd-item-sub {
  display: flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 500;
}
.pf-dd-sub-ok  { color: #17c653; }
.pf-dd-sub-off { color: var(--kt-text-muted, #7e8299); opacity: 0.6; }
.pf-dd-sub-dot {
  width: 5px; height: 5px; border-radius: 50%;
  background: currentColor; flex-shrink: 0;
}

.pf-dd-item-check { flex-shrink: 0; }
</style>