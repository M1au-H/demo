<template>
  <div class="sf-wrap" @click="closeAllDropdowns">

    <!-- ── HEADER ── -->
    <div class="sf-header">
      <div class="sf-header-left">
        <div class="sf-eyebrow">HR Management</div>
        <h1 class="sf-title">Sanksi <span class="sf-title-accent">&</span> Tugas</h1>
        <p class="sf-sub">Kelola sanksi dan tugas tambahan untuk pegawai</p>
      </div>
      <div class="sf-header-right">
        <div v-if="unreadCount > 0" class="sf-unread-pill">
          <span class="sf-unread-dot"></span>
          {{ unreadCount }} perlu ditinjau
        </div>
      </div>
    </div>

    <!-- ── TABS ── -->
    <div class="sf-tabs">
      <button :class="['sf-tab', mainTab === 'form' && 'sf-tab--active']" @click="mainTab = 'form'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        Beri Sanksi / Tugas
      </button>
      <button :class="['sf-tab', mainTab === 'monitor' && 'sf-tab--active']" @click="mainTab = 'monitor'; loadMonitor()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Monitor Penyelesaian
        <span v-if="unreadCount > 0" class="sf-tab-badge">{{ unreadCount }}</span>
      </button>
    </div>

    <!-- ═══════════════ TAB: FORM ═══════════════ -->
    <div v-if="mainTab === 'form'" class="sf-form-grid">

      <!-- ── Kartu Sanksi ── -->
      <div class="sf-card sf-card--danger">
        <div class="sf-card-header">
          <div class="sf-card-icon sf-card-icon--danger">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          </div>
          <div>
            <div class="sf-card-title">Beri Sanksi</div>
            <div class="sf-card-desc">Peringatan, lembur wajib, atau catatan disiplin</div>
          </div>
        </div>
        <div class="sf-divider"></div>

        <div class="sf-field">
          <label class="sf-label">Pegawai</label>
          <button ref="triggerSanction" type="button"
            class="sf-dd-trigger" :class="{ 'sf-dd-trigger--open': openDropdown === 'sanction' }"
            @click.stop="openDd('sanction', $event)">
            <span class="sf-dd-left">
              <span v-if="sanctionForm.user_id" class="sf-dd-av" :style="`background:${avatarColor(getEmpName(sanctionForm.user_id))}`">
                {{ getEmpName(sanctionForm.user_id).charAt(0).toUpperCase() }}
              </span>
              <svg v-else class="sf-dd-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/></svg>
              <span :class="sanctionForm.user_id ? 'sf-dd-val' : 'sf-dd-ph'">
                {{ sanctionForm.user_id ? getEmpName(sanctionForm.user_id) : 'Pilih pegawai...' }}
              </span>
            </span>
            <svg class="sf-dd-chev" :class="{ 'sf-dd-chev--up': openDropdown === 'sanction' }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
        </div>

        <div class="sf-field">
          <label class="sf-label">Jenis Sanksi</label>
          <div class="sf-type-chips">
            <button v-for="opt in sanctionTypes" :key="opt.value"
              :class="['sf-chip', sanctionForm.type === opt.value && `sf-chip--active sf-chip--${opt.color}`]"
              @click="sanctionForm.type = opt.value">{{ opt.label }}</button>
          </div>
        </div>

        <div class="sf-field">
          <label class="sf-label">Alasan</label>
          <textarea v-model="sanctionForm.reason" class="sf-textarea" rows="4"
            placeholder="Jelaskan alasan pemberian sanksi secara detail..."></textarea>
        </div>

        <transition name="sf-msg">
          <div v-if="sanctionMsg" :class="['sf-alert', sanctionMsgType === 'sf-msg-success' ? 'sf-alert--success' : 'sf-alert--error']">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <template v-if="sanctionMsgType === 'sf-msg-success'"><polyline points="20 6 9 17 4 12"/></template>
              <template v-else><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></template>
            </svg>
            {{ sanctionMsg }}
          </div>
        </transition>

        <button @click="submitSanction"
          :disabled="sanctionLoading || !sanctionForm.user_id || !sanctionForm.type || !sanctionForm.reason"
          class="sf-btn sf-btn--danger">
          <span v-if="sanctionLoading" class="sf-spinner"></span>
          <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
          {{ sanctionLoading ? 'Menyimpan...' : 'Berikan Sanksi' }}
        </button>
      </div>

      <!-- ── Kartu Tugas ── -->
      <div class="sf-card sf-card--blue">
        <div class="sf-card-header">
          <div class="sf-card-icon sf-card-icon--blue">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          </div>
          <div>
            <div class="sf-card-title">Tugas Tambahan</div>
            <div class="sf-card-desc">Tetapkan pekerjaan tambahan dengan deadline</div>
          </div>
        </div>
        <div class="sf-divider"></div>

        <div class="sf-field">
          <label class="sf-label">Pegawai</label>
          <button ref="triggerTask" type="button"
            class="sf-dd-trigger" :class="{ 'sf-dd-trigger--open': openDropdown === 'task' }"
            @click.stop="openDd('task', $event)">
            <span class="sf-dd-left">
              <span v-if="taskForm.user_id" class="sf-dd-av" :style="`background:${avatarColor(getEmpName(taskForm.user_id))}`">
                {{ getEmpName(taskForm.user_id).charAt(0).toUpperCase() }}
              </span>
              <svg v-else class="sf-dd-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/></svg>
              <span :class="taskForm.user_id ? 'sf-dd-val' : 'sf-dd-ph'">
                {{ taskForm.user_id ? getEmpName(taskForm.user_id) : 'Pilih pegawai...' }}
              </span>
            </span>
            <svg class="sf-dd-chev" :class="{ 'sf-dd-chev--up': openDropdown === 'task' }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
        </div>

        <div class="sf-field">
          <label class="sf-label">Judul Tugas</label>
          <div class="sf-input-wrap">
            <svg class="sf-input-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            <input v-model="taskForm.title" class="sf-input" type="text" placeholder="Nama tugas yang harus diselesaikan..." />
          </div>
        </div>

        <div class="sf-field">
          <label class="sf-label">Deskripsi <span class="sf-label-opt">(opsional)</span></label>
          <textarea v-model="taskForm.description" class="sf-textarea" rows="3" placeholder="Detail dan instruksi tugas..."></textarea>
        </div>

        <div class="sf-field">
          <label class="sf-label">Deadline</label>
          <div class="sf-input-wrap">
            <svg class="sf-input-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <input v-model="taskForm.due_date" class="sf-input" type="date" />
          </div>
        </div>

        <transition name="sf-msg">
          <div v-if="taskMsg" :class="['sf-alert', taskMsgType === 'sf-msg-success' ? 'sf-alert--success' : 'sf-alert--error']">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <template v-if="taskMsgType === 'sf-msg-success'"><polyline points="20 6 9 17 4 12"/></template>
              <template v-else><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></template>
            </svg>
            {{ taskMsg }}
          </div>
        </transition>

        <button @click="submitTask"
          :disabled="taskLoading || !taskForm.user_id || !taskForm.title"
          class="sf-btn sf-btn--blue">
          <span v-if="taskLoading" class="sf-spinner"></span>
          <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/></svg>
          {{ taskLoading ? 'Menyimpan...' : 'Tetapkan Tugas' }}
        </button>
      </div>
    </div>

    <!-- ═══════════════ TAB: MONITOR ═══════════════ -->
    <div v-if="mainTab === 'monitor'">
      <div class="sf-monitor-bar">
        <div class="sf-subtabs">
          <button :class="['sf-subtab', monitorTab === 'sanctions' && 'sf-subtab--active']" @click="monitorTab = 'sanctions'">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
            Sanksi
            <span v-if="completedSanctions.filter(s => !s.admin_seen).length > 0" class="sf-tab-badge">{{ completedSanctions.filter(s => !s.admin_seen).length }}</span>
          </button>
          <button :class="['sf-subtab', monitorTab === 'tasks' && 'sf-subtab--active']" @click="monitorTab = 'tasks'">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Tugas
            <span v-if="completedTasks.filter(t => !t.admin_seen).length > 0" class="sf-tab-badge">{{ completedTasks.filter(t => !t.admin_seen).length }}</span>
          </button>
        </div>
        <button ref="triggerMonitor" type="button"
          class="sf-dd-trigger sf-dd-trigger--sm" :class="{ 'sf-dd-trigger--open': openDropdown === 'monitor' }"
          @click.stop="openDd('monitor', $event)">
          <span class="sf-dd-left">
            <span v-if="filterUserId" class="sf-dd-av sf-dd-av--sm" :style="`background:${avatarColor(getEmpName(filterUserId))}`">
              {{ getEmpName(filterUserId).charAt(0).toUpperCase() }}
            </span>
            <svg v-else class="sf-dd-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/></svg>
            <span :class="filterUserId ? 'sf-dd-val' : 'sf-dd-ph'">
              {{ filterUserId ? getEmpName(filterUserId) : 'Semua Pegawai' }}
            </span>
          </span>
          <svg class="sf-dd-chev" :class="{ 'sf-dd-chev--up': openDropdown === 'monitor' }" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
      </div>

      <div v-if="loadingMonitor" class="sf-loading">
        <span class="sf-spinner sf-spinner--lg"></span>
        <span>Memuat data...</span>
      </div>

      <div v-else-if="monitorTab === 'sanctions'">
        <div v-if="filteredSanctions.length === 0" class="sf-empty">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
          <p>Belum ada sanksi yang diselesaikan</p>
        </div>
        <div v-else class="sf-mon-list">
          <div v-for="s in filteredSanctions" :key="s.id" :class="['sf-mon-card', !s.admin_seen && 'sf-mon-card--new']">
            <div class="sf-mon-av" :style="`background:${avatarColor(s.user?.name)}`">{{ s.user?.name?.charAt(0)?.toUpperCase() }}</div>
            <div class="sf-mon-body">
              <div class="sf-mon-top">
                <div class="sf-mon-name-row">
                  <span class="sf-mon-name">{{ s.user?.name }}</span>
                  <span :class="['sf-badge', sanctionBadgeClass(s.type)]">{{ sanctionLabel(s.type) }}</span>
                  <span v-if="!s.admin_seen" class="sf-badge sf-badge--new">Baru</span>
                </div>
                <span class="sf-mon-date"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Selesai {{ formatDate(s.completed_at) }}</span>
              </div>
              <p class="sf-mon-reason">{{ s.reason }}</p>
              <p class="sf-mon-meta">Diberikan {{ formatDate(s.sanction_date) }} · Oleh {{ s.giver?.name }}</p>
              <div v-if="s.proof_photo_url" class="sf-mon-proof">
                <div class="sf-mon-proof-label"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>Foto Bukti</div>
                <img :src="s.proof_photo_url" class="sf-mon-img" @click="lightbox = s.proof_photo_url" />
              </div>
              <div v-else class="sf-mon-no-proof">Tidak ada foto bukti</div>
              <div class="sf-mon-actions">
                <button v-if="!s.admin_seen" class="sf-btn-seen" @click="markSeen('sanction', s.id)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Tandai Sudah Dilihat</button>
                <span v-else class="sf-seen-label"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Sudah ditinjau</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="monitorTab === 'tasks'">
        <div v-if="filteredTasks.length === 0" class="sf-empty">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <p>Belum ada tugas yang diselesaikan</p>
        </div>
        <div v-else class="sf-mon-list">
          <div v-for="t in filteredTasks" :key="t.id" :class="['sf-mon-card', !t.admin_seen && 'sf-mon-card--new']">
            <div class="sf-mon-av" :style="`background:${avatarColor(t.user?.name)}`">{{ t.user?.name?.charAt(0)?.toUpperCase() }}</div>
            <div class="sf-mon-body">
              <div class="sf-mon-top">
                <div class="sf-mon-name-row">
                  <span class="sf-mon-name">{{ t.user?.name }}</span>
                  <span class="sf-badge sf-badge--done">✓ Selesai</span>
                  <span v-if="!t.admin_seen" class="sf-badge sf-badge--new">Baru</span>
                </div>
                <span class="sf-mon-date"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Selesai {{ formatDate(t.completed_at) }}</span>
              </div>
              <p class="sf-mon-task-title">{{ t.title }}</p>
              <p v-if="t.description" class="sf-mon-reason">{{ t.description }}</p>
              <p class="sf-mon-meta">Deadline {{ t.due_date ? formatDate(t.due_date) : '—' }} · Dari {{ t.assigner?.name }}</p>
              <div v-if="t.proof_photo_url" class="sf-mon-proof">
                <div class="sf-mon-proof-label"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>Foto Bukti</div>
                <img :src="t.proof_photo_url" class="sf-mon-img" @click="lightbox = t.proof_photo_url" />
              </div>
              <div v-else class="sf-mon-no-proof">Tidak ada foto bukti</div>
              <div class="sf-mon-actions">
                <button v-if="!t.admin_seen" class="sf-btn-seen" @click="markSeen('task', t.id)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Tandai Sudah Dilihat</button>
                <span v-else class="sf-seen-label"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Sudah ditinjau</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ DROPDOWN PANELS — Teleport ke body, tidak ter-clip overflow ══ -->
    <Teleport to="body">
      <template v-if="openDropdown">
        <div class="sf-dd-panel" :style="ddStyle" @click.stop>
          <div class="sf-dd-search-wrap">
            <svg class="sf-dd-search-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input ref="searchInputRef" v-model="searchQ" class="sf-dd-search" placeholder="Cari pegawai..." />
          </div>
          <div class="sf-dd-list">
            <!-- Opsi "Semua" hanya untuk monitor -->
            <template v-if="openDropdown === 'monitor'">
              <button type="button"
                :class="['sf-dd-item', filterUserId === '' && 'sf-dd-item--on']"
                @click="filterUserId = ''; closeAllDropdowns()">
                <span class="sf-dd-item-av" style="background:#3f4254">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="17" cy="8" r="3"/><circle cx="7" cy="8" r="3"/><path d="M1 20v-1a7 7 0 0 1 7-7"/><path d="M23 20v-1a7 7 0 0 0-7-7h-1"/></svg>
                </span>
                <span class="sf-dd-item-name">Semua Pegawai</span>
                <svg v-if="filterUserId === ''" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1b84ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
              <div class="sf-dd-sep"></div>
            </template>

            <div v-if="filteredEmps.length === 0" class="sf-dd-empty">Pegawai tidak ditemukan</div>
            <button v-for="emp in filteredEmps" :key="emp.id" type="button"
              :class="['sf-dd-item', currentValue === emp.id && 'sf-dd-item--on']"
              @click="pickEmp(emp)">
              <span class="sf-dd-item-av" :style="`background:${avatarColor(emp.name)}`">{{ emp.name.charAt(0).toUpperCase() }}</span>
              <span class="sf-dd-item-name">{{ emp.name }}</span>
              <svg v-if="currentValue === emp.id" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1b84ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
          </div>
        </div>
      </template>
    </Teleport>

    <!-- ── Lightbox ── -->
    <Teleport to="body">
      <div v-if="lightbox" class="sf-lightbox" @click="lightbox = null">
        <img :src="lightbox" class="sf-lightbox-img" />
        <p class="sf-lightbox-hint">Klik untuk menutup</p>
      </div>
    </Teleport>

  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, nextTick } from 'vue'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'SanctionForm',
  setup() {
    const employees    = ref<any[]>([])
    const mainTab      = ref('form')
    const monitorTab   = ref('sanctions')
    const filterUserId = ref<any>('')
    const lightbox     = ref<string | null>(null)

    const sanctionForm    = ref({ user_id: <any>'', type: '', reason: '' })
    const sanctionLoading = ref(false)
    const sanctionMsg     = ref('')
    const sanctionMsgType = ref('sf-msg-success')

    const taskForm    = ref({ user_id: <any>'', title: '', description: '', due_date: '' })
    const taskLoading = ref(false)
    const taskMsg     = ref('')
    const taskMsgType = ref('sf-msg-success')

    const completedSanctions = ref<any[]>([])
    const completedTasks     = ref<any[]>([])
    const loadingMonitor     = ref(false)

    // ── Dropdown state ──────────────────────────────────────────────
    const openDropdown   = ref('')
    const searchQ        = ref('')
    const ddStyle        = ref<Record<string, string>>({})
    const searchInputRef = ref<HTMLInputElement | null>(null)

    const triggerSanction = ref<HTMLElement | null>(null)
    const triggerTask     = ref<HTMLElement | null>(null)
    const triggerMonitor  = ref<HTMLElement | null>(null)

    const sanctionTypes = [
      { value: 'warning',            label: 'Peringatan',       color: 'warn' },
      { value: 'mandatory_overtime', label: 'Lembur Wajib',     color: 'ot'   },
      { value: 'disciplinary_note',  label: 'Catatan Disiplin', color: 'disc' },
    ]

    // Nilai yang sedang dipilih di dropdown yang sedang aktif
    const currentValue = computed(() => {
      if (openDropdown.value === 'sanction') return sanctionForm.value.user_id
      if (openDropdown.value === 'task')     return taskForm.value.user_id
      if (openDropdown.value === 'monitor')  return filterUserId.value
      return ''
    })

    // List pegawai yang sudah difilter search
    const filteredEmps = computed(() =>
      employees.value.filter(e =>
        e.name.toLowerCase().includes(searchQ.value.toLowerCase())
      )
    )

    const getEmpName = (id: any): string =>
      employees.value.find(e => e.id == id)?.name ?? ''

    // Hitung posisi panel tepat di bawah (atau di atas) trigger button
    const calcPos = (el: HTMLElement) => {
      const r           = el.getBoundingClientRect()
      const panelW      = Math.max(r.width, 260)
      const panelH      = 320
      const spaceBelow  = window.innerHeight - r.bottom
      const top         = spaceBelow > panelH
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

    const triggerMap: Record<string, any> = {}

    const openDd = async (key: string, _e: MouseEvent) => {
      if (openDropdown.value === key) { openDropdown.value = ''; return }
      searchQ.value      = ''
      openDropdown.value = key
      const elMap: Record<string, typeof triggerSanction> = {
        sanction : triggerSanction,
        task     : triggerTask,
        monitor  : triggerMonitor,
      }
      const el = elMap[key]?.value
      if (el) calcPos(el)
      await nextTick()
      searchInputRef.value?.focus()
    }

    const closeAllDropdowns = () => { openDropdown.value = '' }

    const pickEmp = (emp: any) => {
      if (openDropdown.value === 'sanction') sanctionForm.value.user_id = emp.id
      else if (openDropdown.value === 'task') taskForm.value.user_id    = emp.id
      else if (openDropdown.value === 'monitor') filterUserId.value     = emp.id
      closeAllDropdowns()
    }

    // ── Computed ──────────────────────────────────────────────────
    const unreadCount = computed(() =>
      completedSanctions.value.filter(s => !s.admin_seen).length +
      completedTasks.value.filter(t => !t.admin_seen).length
    )
    const filteredSanctions = computed(() =>
      filterUserId.value
        ? completedSanctions.value.filter(s => s.user_id == filterUserId.value)
        : completedSanctions.value
    )
    const filteredTasks = computed(() =>
      filterUserId.value
        ? completedTasks.value.filter(t => t.user_id == filterUserId.value)
        : completedTasks.value
    )

    // ── API ──────────────────────────────────────────────────────
    const loadEmployees = async () => {
      try { ApiService.setHeader(); const { data } = await ApiService.get('admin/employees'); employees.value = data.data } catch (_) {}
    }
    const loadMonitor = async () => {
      loadingMonitor.value = true
      try {
        ApiService.setHeader()
        const [sRes, tRes] = await Promise.all([ApiService.get('admin/sanctions/completed'), ApiService.get('admin/tasks/completed')])
        completedSanctions.value = sRes.data.data; completedTasks.value = tRes.data.data
      } catch (_) {} finally { loadingMonitor.value = false }
    }
    const submitSanction = async () => {
      sanctionLoading.value = true; sanctionMsg.value = ''
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/sanction/${sanctionForm.value.user_id}`, { type: sanctionForm.value.type, reason: sanctionForm.value.reason })
        sanctionMsg.value = 'Sanksi berhasil diberikan!'; sanctionMsgType.value = 'sf-msg-success'
        sanctionForm.value = { user_id: '', type: '', reason: '' }
      } catch (err: any) { sanctionMsg.value = err?.response?.data?.message ?? 'Gagal menyimpan'; sanctionMsgType.value = 'sf-msg-error' }
      finally { sanctionLoading.value = false }
    }
    const submitTask = async () => {
      taskLoading.value = true; taskMsg.value = ''
      try {
        ApiService.setHeader()
        await ApiService.post(`admin/task/${taskForm.value.user_id}`, { title: taskForm.value.title, description: taskForm.value.description, due_date: taskForm.value.due_date || null })
        taskMsg.value = 'Tugas berhasil ditetapkan!'; taskMsgType.value = 'sf-msg-success'
        taskForm.value = { user_id: '', title: '', description: '', due_date: '' }
      } catch (err: any) { taskMsg.value = err?.response?.data?.message ?? 'Gagal menyimpan'; taskMsgType.value = 'sf-msg-error' }
      finally { taskLoading.value = false }
    }
    const markSeen = async (type: 'sanction' | 'task', id: number) => {
      try {
        ApiService.setHeader(); await ApiService.post(`admin/${type}s/${id}/seen`, {})
        if (type === 'sanction') { const i = completedSanctions.value.findIndex(s => s.id === id); if (i !== -1) completedSanctions.value[i].admin_seen = true }
        else { const i = completedTasks.value.findIndex(t => t.id === id); if (i !== -1) completedTasks.value[i].admin_seen = true }
      } catch (_) {}
    }

    const formatDate = (d: string) => d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'
    const sanctionBadgeClass = (t: string) => t === 'warning' ? 'sf-badge--warn' : t === 'mandatory_overtime' ? 'sf-badge--ot' : 'sf-badge--disc'
    const sanctionLabel = (t: string) => t === 'warning' ? 'Peringatan' : t === 'mandatory_overtime' ? 'Lembur Wajib' : 'Catatan Disiplin'
    const AVATAR_COLORS = ['#f1416c','#1b84ff','#ffc700','#50cd89','#7239ea','#00b2ff','#fd7e14']
    const avatarColor = (name: string) => AVATAR_COLORS[(name?.charCodeAt(0) ?? 0) % AVATAR_COLORS.length]

    onMounted(loadEmployees)

    return {
      employees, mainTab, monitorTab, filterUserId, lightbox,
      openDropdown, searchQ, ddStyle, searchInputRef,
      triggerSanction, triggerTask, triggerMonitor,
      openDd, closeAllDropdowns, pickEmp, getEmpName,
      filteredEmps, currentValue,
      sanctionForm, sanctionLoading, sanctionMsg, sanctionMsgType, sanctionTypes,
      taskForm, taskLoading, taskMsg, taskMsgType,
      completedSanctions, completedTasks, loadingMonitor,
      unreadCount, filteredSanctions, filteredTasks,
      submitSanction, submitTask, loadMonitor, markSeen,
      formatDate, sanctionBadgeClass, sanctionLabel, avatarColor,
    }
  }
})
</script>

<style scoped>
.sf-wrap { padding: 28px 24px; }
.sf-header { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:28px; flex-wrap:wrap; gap:12px; }
.sf-eyebrow { font-size:11px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--kt-text-muted); margin-bottom:6px; }
.sf-title { font-size:28px; font-weight:800; color:var(--kt-text-dark); margin:0 0 5px; letter-spacing:-0.02em; line-height:1; }
.sf-title-accent { color:#1b84ff; }
.sf-sub { font-size:13px; color:var(--kt-text-muted); margin:0; }
.sf-unread-pill { display:flex; align-items:center; gap:8px; background:rgba(255,199,0,0.08); border:1px solid rgba(255,199,0,0.2); border-radius:100px; padding:8px 16px; font-size:12.5px; font-weight:600; color:#ffc700; }
.sf-unread-dot { width:7px; height:7px; border-radius:50%; background:#ffc700; animation:sf-pulse 1.6s ease infinite; }
@keyframes sf-pulse { 0%,100%{box-shadow:0 0 0 0 rgba(255,199,0,.5)} 50%{box-shadow:0 0 0 5px rgba(255,199,0,0)} }
.sf-tabs { display:flex; gap:6px; margin-bottom:24px; background:var(--kt-card-bg); border:1px solid var(--kt-border-color); border-radius:14px; padding:6px; width:fit-content; }
.sf-tab { display:flex; align-items:center; gap:7px; background:transparent; border:none; border-radius:10px; color:var(--kt-text-muted); font-size:13px; font-weight:600; padding:9px 18px; cursor:pointer; transition:all 0.15s; }
.sf-tab:hover { color:var(--kt-text-dark); background:var(--kt-gray-100); }
.sf-tab--active { background:var(--kt-gray-200); color:var(--kt-text-dark); }
.sf-tab-badge { background:#e8262a; color:#fff; border-radius:20px; padding:1px 7px; font-size:10px; font-weight:800; line-height:1.6; }
.sf-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
/* overflow:visible penting agar trigger button tidak ter-clip */
.sf-card { background:var(--kt-card-bg); border:1px solid var(--kt-border-color); border-radius:18px; padding:24px; position:relative; overflow:visible; }
.sf-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius:18px 18px 0 0; pointer-events:none; }
.sf-card--danger::before { background:linear-gradient(90deg,#f1416c,#ff8596); }
.sf-card--blue::before   { background:linear-gradient(90deg,#1b84ff,#5aabff); }
.sf-card-header { display:flex; align-items:flex-start; gap:14px; margin-bottom:20px; }
.sf-card-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.sf-card-icon--danger { background:rgba(241,65,108,0.12); color:#f1416c; }
.sf-card-icon--blue   { background:rgba(27,132,255,0.12);  color:#1b84ff; }
.sf-card-title { font-size:15px; font-weight:700; color:var(--kt-text-dark); margin-bottom:3px; }
.sf-card-desc  { font-size:12px; color:var(--kt-text-muted); }
.sf-divider { height:1px; background:var(--kt-border-color); margin:0 0 20px; }
.sf-field { margin-bottom:16px; }
.sf-label { display:block; font-size:11px; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:var(--kt-text-muted); margin-bottom:8px; }
.sf-label-opt { text-transform:none; letter-spacing:0; font-weight:400; opacity:0.6; margin-left:4px; }

/* ── Trigger button ── */
.sf-dd-trigger {
  width:100%; display:flex; align-items:center; justify-content:space-between;
  background:var(--kt-gray-100); border:1.5px solid var(--kt-border-color);
  border-radius:10px; padding:9px 13px; min-height:42px;
  cursor:pointer; font-family:inherit; transition:border-color 0.15s, box-shadow 0.15s;
}
.sf-dd-trigger:hover   { border-color:rgba(27,132,255,0.35); }
.sf-dd-trigger--open   { border-color:rgba(27,132,255,0.55)!important; box-shadow:0 0 0 3px rgba(27,132,255,0.1); }
.sf-dd-trigger--sm     { min-height:36px; padding:6px 12px; width:auto; min-width:180px; max-width:240px; }
.sf-dd-left            { display:flex; align-items:center; gap:9px; flex:1; min-width:0; }
.sf-dd-av              { width:24px; height:24px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff; flex-shrink:0; }
.sf-dd-av--sm          { width:20px; height:20px; border-radius:5px; font-size:10px; }
.sf-dd-ico             { color:var(--kt-text-muted); flex-shrink:0; }
.sf-dd-ph              { font-size:13px; color:var(--kt-text-muted); opacity:0.55; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sf-dd-val             { font-size:13px; color:var(--kt-text-dark); font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sf-dd-chev            { color:var(--kt-text-muted); flex-shrink:0; transition:transform 0.2s ease; }
.sf-dd-chev--up        { transform:rotate(180deg); color:#1b84ff; }

.sf-input-wrap { position:relative; }
.sf-input-ico  { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--kt-text-muted); pointer-events:none; }
.sf-input { width:100%; box-sizing:border-box; background:var(--kt-gray-100); border:1.5px solid var(--kt-border-color); border-radius:10px; color:var(--kt-text-dark); padding:10px 14px 10px 38px; font-size:13px; outline:none; transition:border-color 0.15s; }
.sf-input:focus { border-color:rgba(27,132,255,0.5); box-shadow:0 0 0 3px rgba(27,132,255,0.08); }
.sf-input::placeholder { color:var(--kt-text-muted); opacity:0.5; }
.sf-textarea { width:100%; box-sizing:border-box; background:var(--kt-gray-100); border:1.5px solid var(--kt-border-color); border-radius:10px; color:var(--kt-text-dark); padding:10px 14px; font-size:13px; font-family:inherit; outline:none; resize:vertical; transition:border-color 0.15s; }
.sf-textarea:focus { border-color:rgba(27,132,255,0.5); box-shadow:0 0 0 3px rgba(27,132,255,0.08); }
.sf-textarea::placeholder { color:var(--kt-text-muted); opacity:0.5; }
.sf-type-chips { display:flex; flex-wrap:wrap; gap:7px; }
.sf-chip { display:inline-flex; align-items:center; background:var(--kt-gray-100); border:1.5px solid var(--kt-border-color); border-radius:8px; color:var(--kt-text-muted); font-size:12px; font-weight:600; padding:7px 13px; cursor:pointer; transition:all 0.14s; font-family:inherit; }
.sf-chip:hover { color:var(--kt-text-dark); }
.sf-chip--warn.sf-chip--active { background:rgba(241,65,108,0.12); border-color:rgba(241,65,108,0.4); color:#f1416c; }
.sf-chip--ot.sf-chip--active   { background:rgba(27,132,255,0.12);  border-color:rgba(27,132,255,0.4);  color:#1b84ff; }
.sf-chip--disc.sf-chip--active { background:rgba(114,57,234,0.12); border-color:rgba(114,57,234,0.4); color:#7239ea; }
.sf-alert { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:10px; font-size:12.5px; font-weight:500; margin-bottom:14px; }
.sf-alert--success { background:rgba(80,205,137,0.08); color:#50cd89; border:1px solid rgba(80,205,137,0.2); }
.sf-alert--error   { background:rgba(241,65,108,0.08); color:#f1416c; border:1px solid rgba(241,65,108,0.2); }
.sf-msg-enter-active, .sf-msg-leave-active { transition:all 0.2s ease; }
.sf-msg-enter-from, .sf-msg-leave-to { opacity:0; transform:translateY(-6px); }
.sf-btn { width:100%; display:flex; align-items:center; justify-content:center; gap:8px; border:none; border-radius:11px; padding:13px; font-size:13.5px; font-weight:700; cursor:pointer; font-family:inherit; transition:all 0.15s; }
.sf-btn:disabled { opacity:0.4; cursor:not-allowed; }
.sf-btn--danger { background:linear-gradient(135deg,#e8262a,#c41f22); color:#fff; }
.sf-btn--danger:hover:not(:disabled) { background:linear-gradient(135deg,#ff3a3d,#d42326); transform:translateY(-1px); box-shadow:0 6px 20px rgba(232,38,42,0.3); }
.sf-btn--blue { background:linear-gradient(135deg,#1b84ff,#0a6fe8); color:#fff; }
.sf-btn--blue:hover:not(:disabled) { background:linear-gradient(135deg,#3a9dff,#1b84ff); transform:translateY(-1px); box-shadow:0 6px 20px rgba(27,132,255,0.3); }
.sf-spinner { display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,0.3); border-top-color:#fff; border-radius:50%; animation:sf-spin 0.65s linear infinite; }
.sf-spinner--lg { width:20px; height:20px; border-color:var(--kt-border-color); border-top-color:#1b84ff; }
@keyframes sf-spin { to { transform:rotate(360deg); } }
.sf-monitor-bar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.sf-subtabs { display:flex; gap:4px; background:var(--kt-card-bg); border:1px solid var(--kt-border-color); border-radius:12px; padding:5px; }
.sf-subtab { display:flex; align-items:center; gap:6px; background:transparent; border:none; border-radius:8px; color:var(--kt-text-muted); font-size:12.5px; font-weight:600; padding:7px 14px; cursor:pointer; transition:all 0.14s; }
.sf-subtab:hover { color:var(--kt-text-dark); background:var(--kt-gray-100); }
.sf-subtab--active { background:var(--kt-gray-200); color:var(--kt-text-dark); }
.sf-loading { display:flex; align-items:center; justify-content:center; gap:10px; padding:60px 0; color:var(--kt-text-muted); font-size:13px; }
.sf-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:64px 0; color:var(--kt-text-muted); gap:12px; }
.sf-empty p { font-size:13.5px; margin:0; }
.sf-mon-list { display:flex; flex-direction:column; gap:12px; }
.sf-mon-card { display:flex; gap:16px; background:var(--kt-card-bg); border:1px solid var(--kt-border-color); border-radius:16px; padding:18px 20px; transition:border-color 0.15s; }
.sf-mon-card--new { border-color:rgba(27,132,255,0.3); background:color-mix(in srgb,var(--kt-card-bg) 97%,#1b84ff); }
.sf-mon-av { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:800; color:#fff; flex-shrink:0; }
.sf-mon-body { flex:1; min-width:0; }
.sf-mon-top { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; margin-bottom:8px; flex-wrap:wrap; }
.sf-mon-name-row { display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
.sf-mon-name { font-size:14px; font-weight:700; color:var(--kt-text-dark); }
.sf-mon-date { display:flex; align-items:center; gap:4px; font-size:11.5px; color:var(--kt-text-muted); white-space:nowrap; }
.sf-mon-task-title { font-size:14px; font-weight:600; color:var(--kt-text-dark); margin:0 0 5px; }
.sf-mon-reason { font-size:13px; color:var(--kt-text-muted); margin:0 0 5px; }
.sf-mon-meta { font-size:11.5px; color:var(--kt-text-muted); opacity:0.6; margin:0 0 12px; }
.sf-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:6px; font-size:11px; font-weight:700; white-space:nowrap; }
.sf-badge--done { background:rgba(80,205,137,0.12); color:#50cd89; border:1px solid rgba(80,205,137,0.2); }
.sf-badge--new  { background:rgba(27,132,255,0.12);  color:#1b84ff; border:1px solid rgba(27,132,255,0.2); }
.sf-badge--warn { background:rgba(241,65,108,0.12); color:#f1416c; border:1px solid rgba(241,65,108,0.2); }
.sf-badge--ot   { background:rgba(27,132,255,0.12);  color:#1b84ff; border:1px solid rgba(27,132,255,0.2); }
.sf-badge--disc { background:rgba(114,57,234,0.12); color:#7239ea; border:1px solid rgba(114,57,234,0.2); }
.sf-mon-proof { margin-bottom:12px; }
.sf-mon-proof-label { display:flex; align-items:center; gap:5px; font-size:11px; font-weight:700; color:var(--kt-text-muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px; }
.sf-mon-img { width:100%; max-width:260px; border-radius:10px; cursor:pointer; border:1px solid var(--kt-border-color); display:block; transition:opacity 0.15s,transform 0.15s; }
.sf-mon-img:hover { opacity:0.85; transform:scale(1.01); }
.sf-mon-no-proof { font-size:12px; color:var(--kt-text-muted); opacity:0.5; font-style:italic; margin-bottom:12px; }
.sf-mon-actions { display:flex; align-items:center; gap:10px; }
.sf-btn-seen { display:inline-flex; align-items:center; gap:6px; background:rgba(27,132,255,0.08); border:1px solid rgba(27,132,255,0.2); color:#1b84ff; border-radius:8px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer; font-family:inherit; transition:all 0.14s; }
.sf-btn-seen:hover { background:rgba(27,132,255,0.16); }
.sf-seen-label { display:inline-flex; align-items:center; gap:5px; font-size:12px; color:var(--kt-text-muted); opacity:0.5; }
.sf-lightbox { position:fixed; inset:0; background:rgba(0,0,0,0.88); backdrop-filter:blur(8px); display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:9999; cursor:pointer; animation:sf-fade 0.2s ease; }
@keyframes sf-fade { from{opacity:0} to{opacity:1} }
.sf-lightbox-img { max-width:90vw; max-height:85vh; border-radius:14px; object-fit:contain; }
.sf-lightbox-hint { color:rgba(255,255,255,0.4); font-size:12px; margin-top:14px; }
@media (max-width:900px) { .sf-form-grid { grid-template-columns:1fr; } }
@media (max-width:600px) { .sf-wrap { padding:16px; } .sf-mon-card { flex-direction:column; } .sf-monitor-bar { flex-direction:column; align-items:flex-start; } }
</style>

<!-- Panel dropdown di-render ke body — TIDAK scoped agar bisa keluar dari stacking context -->
<style>
.sf-dd-panel {
  background: var(--kt-card-bg, #1e1e2d);
  border: 1.5px solid var(--kt-border-color, rgba(255,255,255,0.1));
  border-radius: 14px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.5), 0 4px 20px rgba(0,0,0,0.25);
  overflow: hidden;
  animation: sf-panel-in 0.14s cubic-bezier(0.16,1,0.3,1);
}
@keyframes sf-panel-in {
  from { opacity:0; transform:translateY(-8px) scale(0.97); }
  to   { opacity:1; transform:translateY(0)    scale(1); }
}
.sf-dd-search-wrap {
  position: relative;
  padding: 10px 10px 8px;
  border-bottom: 1px solid var(--kt-border-color, rgba(255,255,255,0.08));
}
.sf-dd-search-ico {
  position: absolute; left: 21px; top: 50%; transform: translateY(-22%);
  color: var(--kt-text-muted, #7e8299); pointer-events: none;
}
.sf-dd-search {
  width: 100%; box-sizing: border-box;
  background: var(--kt-gray-100, rgba(255,255,255,0.05));
  border: 1.5px solid var(--kt-border-color, rgba(255,255,255,0.1));
  border-radius: 8px; color: var(--kt-text-dark, #ffffff);
  padding: 8px 10px 8px 32px;
  font-size: 13px; font-family: inherit; outline: none;
  transition: border-color 0.14s;
}
.sf-dd-search:focus { border-color: rgba(27,132,255,0.5); }
.sf-dd-search::placeholder { color: var(--kt-text-muted, #7e8299); opacity: 0.5; }
.sf-dd-list {
  max-height: 240px; overflow-y: auto; padding: 6px;
  scrollbar-width: thin; scrollbar-color: var(--kt-border-color, rgba(255,255,255,0.1)) transparent;
}
.sf-dd-list::-webkit-scrollbar { width: 4px; }
.sf-dd-list::-webkit-scrollbar-thumb { background: var(--kt-border-color, rgba(255,255,255,0.1)); border-radius: 4px; }
.sf-dd-empty { padding: 14px; text-align: center; font-size: 12px; color: var(--kt-text-muted, #7e8299); }
.sf-dd-sep { height: 1px; background: var(--kt-border-color, rgba(255,255,255,0.08)); margin: 4px 6px; }
.sf-dd-item {
  width: 100%; display: flex; align-items: center; gap: 10px;
  background: transparent; border: none; border-radius: 8px;
  padding: 8px 10px; cursor: pointer; font-family: inherit;
  transition: background 0.1s; text-align: left;
}
.sf-dd-item:hover  { background: var(--kt-gray-100, rgba(255,255,255,0.05)); }
.sf-dd-item--on    { background: rgba(27,132,255,0.1); }
.sf-dd-item-av {
  width: 28px; height: 28px; border-radius: 7px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: #fff;
}
.sf-dd-item-name {
  font-size: 13px; font-weight: 500; flex: 1; min-width: 0;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  color: var(--kt-text-dark, #ffffff);
}
.sf-dd-item--on .sf-dd-item-name { color: #1b84ff; font-weight: 600; }
</style>