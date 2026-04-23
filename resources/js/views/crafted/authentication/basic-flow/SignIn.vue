<template>
  <div class="si-wrap">
    <h1 class="si-heading">Sign in to your<br /><span class="si-heading-red">account</span></h1>
    <p class="si-sub">New here? <router-link to="/sign-up" class="si-link">Create an Account</router-link></p>

    <!-- TAB SWITCHER -->
    <div class="si-tabs">
      <button :class="['si-tab', mode === 'face' && 'active']" @click="switchMode('face')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/></svg>
        Absensi Wajah
      </button>
      <button :class="['si-tab', mode === 'leave' && 'active']" @click="switchMode('leave')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
        Izin &amp; Cuti
      </button>
      <button :class="['si-tab', mode === 'admin' && 'active']" @click="switchMode('admin')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Login Admin
      </button>
    </div>

    <!-- FACE RECOGNITION -->
    <div v-if="mode === 'face'" class="si-face-section">
      <div v-if="faceModelLoading" class="si-model-loading">
        <div class="si-model-loading-cam"><div class="si-cam-placeholder"><span class="si-spin-big"></span></div></div>
        <div class="si-model-loading-text"><span class="si-spin"></span> Memuat AI model... {{ faceModelProgress }}</div>
      </div>

      <div v-else class="si-face-body">

        <!-- Overlay Zoom Fullscreen -->
        <teleport to="body">
          <div v-if="isZoomed" class="si-zoom-overlay" @click.self="zoomOut">
            <div class="si-zoom-container">
              <div class="si-cam-big si-cam-zoomed" :class="faceCamStatus">
                <video ref="faceVideoZoom" autoplay muted playsinline class="si-cam-video" />
                <canvas class="si-cam-canvas" />
                <div class="si-corner si-tl"></div><div class="si-corner si-tr"></div>
                <div class="si-corner si-bl"></div><div class="si-corner si-br"></div>
                <div class="si-cam-status-pill" :class="faceCamStatus">
                  <span class="si-cam-status-dot"></span>
                  <span v-if="faceCamStatus === 'detecting'">Mendeteksi...</span>
                  <span v-else-if="faceCamStatus === 'liveness'">Ikuti instruksi</span>
                  <span v-else-if="faceCamStatus === 'ready'">Mencocokkan wajah...</span>
                  <span v-else-if="faceCamStatus === 'no_face'">Arahkan wajah ke kamera</span>
                  <span v-else-if="faceCamStatus === 'processing'">Memproses...</span>
                  <span v-else-if="faceCamStatus === 'success'">Berhasil dikenali!</span>
                  <span v-else-if="faceCamStatus === 'failed'">Wajah tidak dikenali</span>
                  <span v-else-if="faceCamStatus === 'liveness_fail'">Gagal, coba lagi</span>
                </div>
                <button class="si-zoom-close-btn" @click="zoomOut" title="Tutup">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </teleport>

        <!-- GPS Status Bar -->
        <div v-if="gpsStatus !== 'idle'" class="si-gps-bar" :class="gpsStatus">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M2 12h3M19 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"/></svg>
          <span v-if="gpsStatus === 'getting'">Mengambil lokasi GPS...</span>
          <span v-else-if="gpsStatus === 'ok'">✓ Lokasi terverifikasi</span>
          <span v-else-if="gpsStatus === 'error'">{{ gpsMsg }}</span>
        </div>

        <!-- Kamera Normal -->
        <div class="si-cam-big" :class="faceCamStatus">
          <video ref="faceVideo" autoplay muted playsinline class="si-cam-video" />
          <canvas ref="faceCanvas" class="si-cam-canvas" />
          <div class="si-corner si-tl"></div><div class="si-corner si-tr"></div>
          <div class="si-corner si-bl"></div><div class="si-corner si-br"></div>

          <!-- Countdown Foto Overlay -->
          <div v-if="isCountingDown" class="si-countdown-overlay">
            <div v-if="photoCountdown > 0" class="si-countdown-number" :key="photoCountdown">
              {{ photoCountdown }}
            </div>
            <div v-else-if="isPhotoTaken" class="si-countdown-flash">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                <circle cx="12" cy="13" r="4"/>
              </svg>
              <span>Foto diambil!</span>
            </div>
            <div class="si-countdown-msg">{{ photoCountdownMsg }}</div>
          </div>

          <!-- Tombol Zoom -->
          <div class="si-zoom-btns">
            <button class="si-zoom-btn" @click="zoomIn" title="Perbesar">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 3 21 3 21 9"/>
                <polyline points="9 21 3 21 3 15"/>
                <line x1="21" y1="3" x2="14" y2="10"/>
                <line x1="3" y1="21" x2="10" y2="14"/>
              </svg>
            </button>
          </div>

          <div v-if="recognizedUser" class="si-recognized-overlay">
            <img v-if="recognizedUser.avatar" :src="recognizedUser.avatar" class="si-rec-avatar" />
            <div v-else class="si-rec-avatar-fallback">{{ recognizedUser.name && recognizedUser.name.charAt(0) }}</div>
            <div class="si-rec-check-badge">&#10003;</div>
            <div class="si-rec-name">Halo, {{ recognizedUser.name }}</div>
            <div class="si-rec-msg">{{ attendanceMsg }}</div>
          </div>
          <div class="si-cam-status-pill" :class="faceCamStatus">
            <span class="si-cam-status-dot"></span>
            <span v-if="faceCamStatus === 'detecting'">Mendeteksi...</span>
            <span v-else-if="faceCamStatus === 'liveness'">Ikuti instruksi</span>
            <span v-else-if="faceCamStatus === 'ready'">Mencocokkan wajah...</span>
            <span v-else-if="faceCamStatus === 'no_face'">Arahkan wajah ke kamera</span>
            <span v-else-if="faceCamStatus === 'processing'">Memproses...</span>
            <span v-else-if="faceCamStatus === 'success'">Berhasil dikenali!</span>
            <span v-else-if="faceCamStatus === 'failed'">Wajah tidak dikenali</span>
            <span v-else-if="faceCamStatus === 'liveness_fail'">Gagal, coba lagi</span>
          </div>
        </div>

        <!-- Liveness bar -->
        <div v-if="livenessActive && !livenessCompleted" class="si-liveness-bar-section" :class="{ shake: challengeFailed }">
          <div class="si-liveness-dots">
            <div v-for="(ch, i) in challenges" :key="i" class="si-lv-dot"
                :class="{ done: i < currentChallengeIndex, active: i === currentChallengeIndex }">
              <svg v-if="ch.type==='blink'" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg v-else-if="ch.type==='turn_left'" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
              <svg v-else-if="ch.type==='turn_right'" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
              <svg v-else-if="ch.type==='nod_up'" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
              <svg v-else viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
            </div>
          </div>
          <div class="si-lv-instruction">
            <div class="si-lv-icon-big">
              <svg v-if="currentChallenge && currentChallenge.type==='blink'" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg v-else-if="currentChallenge && currentChallenge.type==='turn_left'" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
              <svg v-else-if="currentChallenge && currentChallenge.type==='turn_right'" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
              <svg v-else-if="currentChallenge && currentChallenge.type==='nod_up'" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
              <svg v-else-if="currentChallenge" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
            </div>
            <div class="si-lv-text-wrap">
              <div class="si-lv-step">Langkah {{ currentChallengeIndex + 1 }} / {{ challenges.length }}</div>
              <div class="si-lv-action">{{ currentChallenge && currentChallenge.instruction }}</div>
              <div class="si-lv-hint-small">{{ currentChallenge && currentChallenge.hint }}</div>
            </div>
          </div>
          <div v-if="returnPhaseRef" class="si-lv-return-phase">Bagus! Sekarang <strong>kembali ke tengah</strong></div>

          <div v-if="currentChallenge && currentChallenge.type === 'blink'" class="si-lv-blink-state">
            <div v-if="!earBaselineReady" class="si-blink-waiting">
              Mengukur baseline mata... ({{ Math.min(earBaselineFramesDisplay, 8) }}/8)
            </div>
            <template v-else>
              <div v-if="blinkPhase === 'wait_close'" class="si-blink-waiting">👁 Tutup mata Anda sekarang</div>
              <div v-else-if="blinkPhase === 'wait_open'" class="si-blink-closed">✓ Mata tertutup — sekarang buka mata</div>
              <div v-else-if="blinkCountRef === 1 && currentChallenge.double" class="si-blink-one">Kedipan 1 ✓ — tutup mata sekali lagi</div>
            </template>
            <div class="si-ear-debug" v-if="earBaselineReady">
              EAR: <strong :style="{ color: earCurrentRef < earCloseThreshRef ? '#ff6b6b' : '#17c653' }">{{ earCurrentRef }}</strong>
              (L:<strong>{{ earLeftRef }}</strong> R:<strong>{{ earRightRef }}</strong>)
              &nbsp;|&nbsp; tutup &lt; <strong>{{ earCloseThreshRef }}</strong>
              &nbsp;|&nbsp; base: <strong>{{ earBaselineRef }}</strong>
              &nbsp;|&nbsp; fase: <strong style="color:#ffa500">{{ blinkPhase }}</strong>
            </div>
          </div>

          <div class="si-lv-timer-wrap">
            <div class="si-lv-timer-bar" :style="{ width: challengeTimerPct + '%' }"
                :class="challengeTimerPct < 40 ? 'danger' : challengeTimerPct < 70 ? 'warn' : ''"></div>
          </div>
          <div class="si-lv-timer-label" :class="challengeTimerPct < 40 ? 'urgent' : ''">
            <span v-if="challengeTimerPct < 40">Waktu hampir habis!</span>
            <span v-else>Lakukan gerakan sekarang</span>
          </div>
          <div class="si-lv-session-badge">Sesi unik &middot; tidak bisa diulang</div>
        </div>

        <div v-else-if="!recognizedUser" class="si-face-status-bar">
          <div v-if="faceMsg" :class="['si-face-msg', faceMsgType]">{{ faceMsg }}</div>
          <div v-else class="si-face-guide">
            <div class="si-face-guide-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/></svg>
            </div>
            <span>Posisikan wajah di tengah kamera</span>
          </div>
        </div>
      </div>
    </div>

    <!-- IZIN & CUTI -->
    <div v-if="mode === 'leave'" class="si-leave-section">
      <div v-if="!leaveUser">
        <div v-if="faceModelLoading" class="si-model-loading">
          <div class="si-model-loading-cam"><div class="si-cam-placeholder"><span class="si-spin-big"></span></div></div>
          <div class="si-model-loading-text"><span class="si-spin"></span> Memuat AI model... {{ faceModelProgress }}</div>
        </div>
        <div v-else>
          <div class="si-leave-scan-wrap">
            <div class="si-cam-big" :class="leaveCamStatus" style="aspect-ratio:4/3">
              <video ref="leaveVideo" autoplay muted playsinline class="si-cam-video" />
              <canvas ref="leaveCanvas" class="si-cam-canvas" />
              <div class="si-corner si-tl"></div><div class="si-corner si-tr"></div>
              <div class="si-corner si-bl"></div><div class="si-corner si-br"></div>
              <div class="si-cam-status-pill" :class="leaveCamStatus">
                <span class="si-cam-status-dot"></span>
                <span v-if="leaveCamStatus === 'detecting'">Mendeteksi wajah...</span>
                <span v-else-if="leaveCamStatus === 'ready'">Wajah terdeteksi!</span>
                <span v-else-if="leaveCamStatus === 'no_face'">Arahkan wajah ke kamera</span>
                <span v-else-if="leaveCamStatus === 'processing'">Memverifikasi identitas...</span>
                <span v-else-if="leaveCamStatus === 'failed'">Wajah tidak dikenali</span>
              </div>
            </div>
          </div>
          <div class="si-leave-scan-info">
            <div class="si-leave-scan-steps">
              <div class="si-leave-scan-step active"><div class="si-step-num">1</div><div>Scan wajah untuk verifikasi identitas</div></div>
              <div class="si-leave-scan-step"><div class="si-step-num">2</div><div>Isi form izin &amp; cuti</div></div>
            </div>
          </div>
          <div v-if="leaveVerifyMsg" :class="['si-face-msg', leaveVerifyMsgType]" style="margin-top:10px">{{ leaveVerifyMsg }}</div>
          <button class="si-face-btn" @click="verifyLeaveUser" :disabled="(leaveCamStatus !== 'ready' && leaveCamStatus !== 'no_face') || leaveVerifying">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
            {{ leaveVerifying ? 'Memverifikasi...' : 'Verifikasi Identitas' }}
          </button>
        </div>
      </div>
      <div v-else>
        <div class="si-leave-user-card">
          <div class="si-leave-user-check">&#10003;</div>
          <img v-if="leaveUser.avatar" :src="leaveUser.avatar" class="si-leave-user-avatar" />
          <div v-else class="si-leave-user-avatar-fb">{{ leaveUser.name && leaveUser.name.charAt(0) }}</div>
          <div class="si-leave-user-info">
            <div class="si-leave-user-name">{{ leaveUser.name }}</div>
            <div class="si-leave-user-pos">{{ leaveUser.position || 'Pegawai' }} &middot; Terverifikasi</div>
          </div>
          <button class="si-leave-change-btn" @click="resetLeaveUser">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>
          </button>
        </div>
        <div class="si-leave-scan-steps" style="margin-bottom:16px">
          <div class="si-leave-scan-step done"><div class="si-step-num done">&#10003;</div><div>Identitas terverifikasi</div></div>
          <div class="si-leave-scan-step active"><div class="si-step-num">2</div><div>Isi form izin &amp; cuti</div></div>
        </div>

        <div class="si-leave-form">
          <div class="si-leave-field">
            <label>Tanggal Izin</label>
            <input type="date" v-model="leaveForm.date" :min="todayRaw" class="si-leave-input" />
          </div>
          <div class="si-leave-field">
            <label>Jenis</label>
            <div class="si-leave-type-grid">
              <button
                v-for="opt in leaveTypes"
                :key="opt.value"
                class="si-leave-type-btn"
                :class="{ active: leaveForm.type === opt.value }"
                @click="leaveForm.type = opt.value; leaveForm.cutiType = ''"
              >
                <span class="si-ltype-icon" v-html="opt.svg"></span>
                <span>{{ opt.label }}</span>
              </button>
            </div>
          </div>

          <!-- Info kuota cuti — hanya muncul jika pilih Cuti -->
          <div v-if="leaveForm.type === 'cuti'" class="si-kuota-info" :class="sisaIzin === 0 ? 'danger' : sisaIzin === 1 ? 'warn' : 'ok'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span v-if="sisaIzin === 0">Kuota cuti bulan ini sudah habis! Gunakan <strong>Izin</strong> untuk keperluan mendesak.</span>
            <span v-else>Sisa kuota cuti bulan ini: <strong>{{ sisaIzin }}x</strong> dari 3x</span>
          </div>

          <!-- Sub-pilihan jenis izin khusus — hanya muncul jika pilih Izin -->
          <div v-if="leaveForm.type === 'izin'" class="si-leave-field">
            <label>Jenis Izin Khusus <span class="si-leave-req">*wajib dipilih</span></label>
            <div class="si-cuti-type-list">
              <button
                v-for="(label, val) in cutiTypes"
                :key="val"
                class="si-cuti-type-btn"
                :class="{ active: leaveForm.cutiType === val }"
                @click="leaveForm.cutiType = val"
              >
                {{ label }}
              </button>
            </div>
            <div class="si-cuti-info">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Izin khusus memerlukan persetujuan admin sebelum berlaku.
            </div>
          </div>

          <div class="si-leave-field">
            <label>Alasan <span class="si-leave-req">*wajib</span></label>
            <textarea v-model="leaveForm.reason" class="si-leave-input si-leave-ta" placeholder="Contoh: Demam tinggi sejak kemarin..." rows="3"></textarea>
          </div>

          <div v-if="leaveForm.type === 'sakit'" class="si-leave-field">
            <label>Surat Dokter <span class="si-leave-req">*wajib untuk sakit</span></label>
            <label class="si-upload-box" :class="{ 'has-file': leaveForm.suratDokter }">
              <input type="file" accept="image/*,application/pdf" class="si-upload-input" @change="(e: Event) => leaveForm.suratDokter = (e.target as HTMLInputElement).files?.[0] || null" />
              <div v-if="!leaveForm.suratDokter" class="si-upload-placeholder">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Upload foto / PDF surat dokter</span>
              </div>
              <div v-else class="si-upload-done">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#17c653" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span>{{ leaveForm.suratDokter.name }}</span>
                <button type="button" class="si-upload-clear" @click.stop="leaveForm.suratDokter = null">&times;</button>
              </div>
            </label>
          </div>

          <div v-if="leaveFormError" class="si-face-msg err">{{ leaveFormError }}</div>
          <div v-if="leaveFormSuccess" class="si-face-msg ok">{{ leaveFormSuccess }}</div>

          <button class="si-face-btn" @click="submitLeave" :disabled="leaveSubmitting">
            {{ leaveSubmitting ? 'Mengirim...' : leaveForm.type === 'izin' ? 'Ajukan Izin' : leaveForm.type === 'cuti' ? 'Ajukan Cuti' : 'Ajukan' }}
          </button>
        </div>

        <div class="si-leave-history">
          <div class="si-leave-history-title">Riwayat Izin & Cuti</div>
          <div v-if="leaveHistory.length === 0" class="si-leave-empty">Belum ada riwayat</div>
          <div v-else class="si-leave-list">
            <div v-for="lv in leaveHistory" :key="lv.id" class="si-leave-item">
              <span class="si-leave-item-icon" v-html="typeIcon(lv.type)"></span>
              <div class="si-leave-item-body">
                <div class="si-leave-item-type">{{ typeLabel(lv.type) }}</div>
                <div class="si-leave-item-date">{{ formatDate(lv.date) }}</div>
                <div v-if="lv.reason" class="si-leave-item-reason">{{ lv.reason }}</div>
                <!-- Status badge -->
                <div class="si-leave-item-status" :class="`si-lstatus-${lv.status}`">
                  {{ lv.status === 'pending' ? '⏳ Menunggu persetujuan' : lv.status === 'approved' ? '✓ Disetujui' : '✗ Ditolak' }}
                </div>
              </div>
              <button v-if="lv.date >= todayRaw && lv.status === 'pending'" class="si-leave-del" @click="deleteLeave(lv.id)">&times;</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ADMIN LOGIN -->
    <div v-if="mode === 'admin'">
      <VForm id="kt_login_signin_form" @submit="onSubmitLogin" :validation-schema="loginSchema" :initial-values="initialValues">
        <div class="si-field">
          <label class="si-label">Email address</label>
          <div class="si-inp-wrap">
            <span class="si-inp-ico"><svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="14" height="10" rx="2"/><polyline points="1,3 8,9 15,3"/></svg></span>
            <Field tabindex="1" class="si-input" placeholder="Enter your email" name="email" type="text" autocomplete="email" />
          </div>
          <div v-if="lastEmail" class="si-last-email" @click="useLastEmail"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 8A6 6 0 1 1 8 2"/><polyline points="14 2 14 8 8 8"/></svg> Use last: <strong>{{ lastEmail }}</strong></div>
          <div class="fv-plugins-message-container mt-1"><div class="si-error"><ErrorMessage name="email" /></div></div>
        </div>
        <div class="si-field">
          <div class="si-field-top">
            <label class="si-label" style="margin-bottom:0">Password</label>
            <router-link to="/password-reset" class="si-forgot">Forgot Password?</router-link>
          </div>
          <div class="si-inp-wrap" style="margin-top:7px">
            <span class="si-inp-ico"><svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="10" height="8" rx="1.5"/><path d="M5 7V5a3 3 0 016 0v2"/><circle cx="8" cy="11" r="1" fill="currentColor" stroke="none"/></svg></span>
            <Field tabindex="2" class="si-input si-input-pwd" placeholder="Min. 4 characters" name="password" :type="showPwd ? 'text' : 'password'" autocomplete="current-password" />
            <button type="button" class="si-eye-btn" @click="showPwd = !showPwd">
              <svg v-if="!showPwd" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <div class="fv-plugins-message-container mt-1"><div class="si-error"><ErrorMessage name="password" /></div></div>
        </div>
        <div class="si-remember">
          <input type="checkbox" class="si-chk" id="sf-rem" v-model="rememberMe" />
          <label class="si-chk-label" for="sf-rem">Keep me signed in for 30 days</label>
        </div>
        <button tabindex="3" type="submit" ref="submitButton" id="kt_sign_in_submit" class="si-submit-btn" :disabled="isLoading">
          <span v-if="!isLoading" class="si-btn-inner">Continue <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 7h12M8 2l5 5-5 5" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
          <span v-else class="si-btn-inner"><span class="si-spin"></span> Please wait...</span>
        </button>
      </VForm>
      <div class="si-divider"><span>OR</span></div>
      <div class="si-socials">
        <button class="si-social-btn" type="button"><svg viewBox="0 0 24 24" width="18" height="18" style="flex-shrink:0"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> Continue with Google</button>
        <button class="si-social-btn" type="button"><svg viewBox="0 0 24 24" width="18" height="18" fill="#1877F2" style="flex-shrink:0"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg> Continue with Facebook</button>
        <button class="si-social-btn" type="button"><svg viewBox="0 0 24 24" width="18" height="18" fill="#f0f0f5" style="flex-shrink:0"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg> Continue with Apple</button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { ErrorMessage, Field, Form as VForm } from 'vee-validate'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2/dist/sweetalert2.js'
import * as Yup from 'yup'
import * as tf from '@tensorflow/tfjs'
import * as faceapi from 'face-api.js'
import axios from 'axios'

const LAST_EMAIL_KEY = 'si_last_email'
const MODEL_URL      = '/models'
const API_BASE       = (import.meta.env.VITE_APP_API_URL || '/api').replace(/\/$/, '')
const THRESHOLD      = 0.45
const CHALLENGE_SECS = 15
const WARMUP_FRAMES   = 5
const BASELINE_FRAMES = 8

// ── Jenis izin khusus (hardcoded, sinkron dengan backend IZIN_TYPES) ──────────
const IZIN_TYPES: Record<string, string> = {
  'duka_cita':                 'Duka Cita (Keluarga Meninggal)',
  'menikah':                   'Pernikahan',
  'melahirkan':                'Melahirkan / Persalinan',
  'menemani_istri_melahirkan': 'Menemani Istri Melahirkan',
  'khitan':                    'Khitan Anak',
  'lainnya':                   'Keperluan Khusus Lainnya',
}

function calcEAR(eye: { x: number; y: number }[]): number {
  if (!eye || eye.length < 6) return 0.3
  const A = Math.hypot(eye[1].x - eye[5].x, eye[1].y - eye[5].y)
  const B = Math.hypot(eye[2].x - eye[4].x, eye[2].y - eye[4].y)
  const C = Math.hypot(eye[0].x - eye[3].x, eye[0].y - eye[3].y)
  return C < 0.001 ? 0.3 : (A + B) / (2.0 * C)
}

const euclidean = (a: number[], b: number[]): number => {
  let s = 0
  for (let i = 0; i < a.length; i++) s += (a[i] - b[i]) ** 2
  return Math.sqrt(s)
}

function capturePhoto(video: HTMLVideoElement): string | null {
  try {
    const c = document.createElement('canvas')
    c.width = video.videoWidth || 480
    c.height = video.videoHeight || 360
    const ctx = c.getContext('2d')
    if (!ctx) return null
    ctx.translate(c.width, 0)
    ctx.scale(-1, 1)
    ctx.drawImage(video, 0, 0, c.width, c.height)
    return c.toDataURL('image/jpeg', 0.8)
  } catch { return null }
}

const ALL_CHALLENGES = [
  { type: 'blink',      instruction: 'Kedipkan mata sekali',       hint: 'Tutup dan buka mata dengan natural' },
  { type: 'turn_left',  instruction: 'Palingkan kepala ke KIRI',   hint: 'Putar kepala ke kiri' },
  { type: 'turn_right', instruction: 'Palingkan kepala ke KANAN',  hint: 'Putar kepala ke kanan' },
  { type: 'nod_up',     instruction: 'Tengadahkan kepala ke ATAS', hint: 'Angkat dagu ke atas' },
  { type: 'nod_down',   instruction: 'Tundukkan kepala ke BAWAH',  hint: 'Turunkan dagu ke bawah' },
  { type: 'blink',      instruction: 'Kedipkan mata DUA kali',     hint: 'Dua kedipan berturut-turut', double: true },
]

function pickChallenges(n = 3): any[] {
  const shuffled = [...ALL_CHALLENGES].sort(() => Math.random() - 0.5)
  const result: any[] = [{ ...ALL_CHALLENGES[0] }]
  let last = 'blink'
  for (const ch of shuffled) {
    if (result.length >= n) break
    if (ch.type === last || (ch.type === 'blink' && result.some(r => r.type === 'blink'))) continue
    result.push({ ...ch })
    last = ch.type
  }
  return result
}

export default defineComponent({
  name: 'sign-in',
  components: { Field, VForm, ErrorMessage },
  setup() {
    const store  = useAuthStore()
    const router = useRouter()

    // ── Admin login ───────────────────────────────────────────────────────────
    const submitButton  = ref<HTMLButtonElement | null>(null)
    const isLoading     = ref(false)
    const showPwd       = ref(false)
    const rememberMe    = ref(true)
    const lastEmail     = ref(localStorage.getItem(LAST_EMAIL_KEY) || '')
    const initialValues = computed(() => ({ email: lastEmail.value || '', password: '' }))

    const useLastEmail = () => {
      const el = document.querySelector('#kt_login_signin_form input[name="email"]') as HTMLInputElement
      if (el) {
        el.value = lastEmail.value
        el.dispatchEvent(new Event('input', { bubbles: true }))
        el.focus()
      }
    }

    const loginSchema = Yup.object().shape({
      email:    Yup.string().email().required().label('Email'),
      password: Yup.string().min(4).required().label('Password'),
    })

    const onSubmitLogin = async (values: any) => {
      isLoading.value = true
      if (submitButton.value) {
        submitButton.value.disabled = true
        submitButton.value.setAttribute('data-kt-indicator', 'on')
      }
      await store.adminLogin(values)
      if (Object.values(store.errors).length > 0) {
        store.errors = {}
        await store.login(values)
      }
      const error = Object.values(store.errors)
      if (error.length === 0) {
        localStorage.setItem(LAST_EMAIL_KEY, values.email)
        lastEmail.value = values.email
        Swal.fire({
          text: 'You have successfully logged in!',
          icon: 'success',
          buttonsStyling: false,
          confirmButtonText: 'Ok, got it!',
          heightAuto: false,
          customClass: { confirmButton: 'btn fw-semibold btn-light-primary' }
        }).then(() => {
          store.isAdmin() ? router.push({ name: 'dashboard' }) : router.push({ name: 'user-dashboard' })
        })
      } else {
        Swal.fire({
          text: error[0] as string,
          icon: 'error',
          buttonsStyling: false,
          confirmButtonText: 'Try again!',
          heightAuto: false,
          customClass: { confirmButton: 'btn fw-semibold btn-light-danger' }
        }).then(() => { store.errors = {} })
      }
      isLoading.value = false
      submitButton.value?.removeAttribute('data-kt-indicator')
      if (submitButton.value) submitButton.value.disabled = false
    }

    // ── Mode ──────────────────────────────────────────────────────────────────
    const mode       = ref('face')
    const switchMode = (m: string) => { mode.value = m }

    // ── Models & profiles ─────────────────────────────────────────────────────
    const faceModelLoading  = ref(true)
    const faceModelProgress = ref('')
    let modelsLoaded = false
    let faceProfiles: any[] = []

    const initTFBackend = async (): Promise<void> => {
      try {
        faceModelProgress.value = 'inisialisasi backend AI...'
        await tf.setBackend('cpu')
        await tf.ready()
      } catch (e) {
        console.warn('[TF] Gagal set backend CPU:', e)
      }
    }

    const loadModels = async () => {
      if (modelsLoaded) { faceModelLoading.value = false; return }
      faceModelLoading.value = true
      try {
        await initTFBackend()
        const modelList: [string, any][] = [
          ['SSD detector',  faceapi.nets.ssdMobilenetv1],
          ['tiny detector', faceapi.nets.tinyFaceDetector],
          ['landmarks 68',  faceapi.nets.faceLandmark68Net],
          ['recognition',   faceapi.nets.faceRecognitionNet],
        ]
        for (const [lbl, net] of modelList) {
          try {
            faceModelProgress.value = lbl + '...'
            await net.loadFromUri(MODEL_URL)
          } catch (modelErr) {
            console.error(`[FaceAPI] Gagal load model "${lbl}":`, modelErr)
          }
        }
        modelsLoaded = true
        faceModelProgress.value = 'siap!'
      } catch (e) {
        console.error('[FaceAPI] Error load models:', e)
      } finally {
        faceModelLoading.value = false
      }
    }

    const loadProfiles = async () => {
      try {
        const { data } = await axios.get(`${API_BASE}/face/profiles`)
        faceProfiles = data.data || []
      } catch (_) {}
    }

    // ── Face state ────────────────────────────────────────────────────────────
    const faceVideo      = ref<HTMLVideoElement | null>(null)
    const faceCanvas     = ref<HTMLCanvasElement | null>(null)
    const faceCamStatus  = ref('detecting')
    const faceMsg        = ref('')
    const faceMsgType    = ref('info')
    const recognizedUser = ref<any>(null)
    const attendanceMsg  = ref('')

    const photoCountdown    = ref(0)
    const photoCountdownMsg = ref('')
    const isCountingDown    = ref(false)
    const isPhotoTaken      = ref(false)

    // ── Liveness state ────────────────────────────────────────────────────────
    const livenessActive        = ref(false)
    const livenessCompleted     = ref(false)
    const challenges            = ref<any[]>([])
    const currentChallengeIndex = ref(0)
    const challengeTimerPct     = ref(100)
    const challengeFailed       = ref(false)
    const currentChallenge      = computed(() => challenges.value[currentChallengeIndex.value] || null)
    const returnPhaseRef        = ref(false)
    const blinkCountRef         = ref(0)
    const eyeClosedRef          = ref(false)
    const earBaselineReady      = ref(false)
    const earBaselineFramesDisplay = ref(0)
    const earCurrentRef         = ref(0)
    const earBaselineRef        = ref(0)
    const earCloseThreshRef     = ref(0)
    const earLeftRef            = ref(0)
    const earRightRef           = ref(0)
    const blinkPhase            = ref<'idle'|'wait_close'|'wait_open'|'done'>('idle')

    let baseYaw = 0, basePitch = 0, baseSet = false
    let earBaselineArr: number[]  = []
    let earBaseline               = 0
    let earBaselineCollected      = false
    let eyeIsOpen                 = true
    let blinkCount                = 0
    let warmupFrameCount          = 0

    let challengeTimerInt: number | null = null
    let challengeSecsLeft = CHALLENGE_SECS
    let pendingProfile: any = null
    let faceStream: MediaStream | null = null
    let detectInterval: number | null = null
    let isDetecting = false
    let successLock = false

    const startLivenessChallenge = (profile: any) => {
      pendingProfile              = profile
      challenges.value            = pickChallenges(3)
      currentChallengeIndex.value = 0
      challengeTimerPct.value     = 100
      challengeFailed.value       = false
      livenessActive.value        = true
      livenessCompleted.value     = false
      faceCamStatus.value         = 'liveness'
      resetMovementState()
      startChallengeTimer()
      startDetectLoop()
    }

    const resetMovementState = () => {
      baseYaw = 0; basePitch = 0; baseSet = false
      returnPhaseRef.value = false
      resetBlinkState()
    }

    const resetBlinkState = () => {
      earBaselineArr                 = []
      earBaseline                    = 0
      earBaselineCollected           = false
      eyeIsOpen                      = true
      blinkCount                     = 0
      blinkCountRef.value            = 0
      eyeClosedRef.value             = false
      earBaselineReady.value         = false
      earBaselineFramesDisplay.value = 0
      earCurrentRef.value            = 0
      earBaselineRef.value           = 0
      earCloseThreshRef.value        = 0
      blinkPhase.value               = 'idle'
      warmupFrameCount               = 0
    }

    const startChallengeTimer = () => {
      stopChallengeTimer()
      challengeSecsLeft = CHALLENGE_SECS
      challengeTimerInt = window.setInterval(() => {
        challengeSecsLeft       -= 0.1
        challengeTimerPct.value  = Math.max(0, (challengeSecsLeft / CHALLENGE_SECS) * 100)
        if (challengeSecsLeft <= 0) onLivenessFail()
      }, 100)
    }

    const stopChallengeTimer = () => {
      if (challengeTimerInt !== null) { clearInterval(challengeTimerInt); challengeTimerInt = null }
    }

    const onChallengeSuccess = () => {
      stopChallengeTimer()
      const next = currentChallengeIndex.value + 1
      if (next >= challenges.value.length) {
        livenessCompleted.value = true
        livenessActive.value    = false
        successLock             = true
        faceCamStatus.value     = 'processing'
        processLogin(pendingProfile)
      } else {
        currentChallengeIndex.value = next
        resetMovementState()
        startChallengeTimer()
      }
    }

    const onLivenessFail = () => {
      stopChallengeTimer()
      challengeFailed.value   = true
      livenessActive.value    = false
      livenessCompleted.value = false
      pendingProfile          = null
      successLock             = false
      faceCamStatus.value     = 'liveness_fail'
      faceMsg.value           = 'Verifikasi liveness gagal. Ikuti instruksi dengan benar.'
      faceMsgType.value       = 'err'
      startDetectLoop()
      setTimeout(() => { challengeFailed.value = false; faceMsg.value = ''; faceCamStatus.value = 'detecting' }, 3000)
    }

    const processBlink = (pos: { x: number; y: number }[], needDouble: boolean) => {
      const leftEye  = pos.slice(36, 42)
      const rightEye = pos.slice(42, 48)
      if (leftEye.length < 6 || rightEye.length < 6) return
      const earL = calcEAR(leftEye)
      const earR = calcEAR(rightEye)
      const ear  = (earL + earR) / 2
      earLeftRef.value    = Math.round(earL * 1000) / 1000
      earRightRef.value   = Math.round(earR * 1000) / 1000
      earCurrentRef.value = Math.round(ear * 1000) / 1000
      if (!earBaselineCollected) {
        if (warmupFrameCount < WARMUP_FRAMES) { warmupFrameCount++; earBaselineFramesDisplay.value = 0; return }
        if (ear > 0.25 && ear < 0.55) {
          earBaselineArr.push(ear)
          earBaselineFramesDisplay.value = earBaselineArr.length
          if (earBaselineArr.length >= BASELINE_FRAMES) {
            earBaseline = Math.max(...earBaselineArr)
            if (earBaseline < 0.26) earBaseline = 0.26
            earBaselineCollected   = true
            earBaselineReady.value = true
            earBaselineRef.value   = Math.round(earBaseline * 1000) / 1000
            blinkPhase.value       = 'wait_close'
          }
        }
        return
      }
      const DROP_CLOSE = earBaseline * 0.85
      const DROP_OPEN  = earBaseline * 0.92
      earCloseThreshRef.value = Math.round(DROP_CLOSE * 1000) / 1000
      const isClosed = ear < DROP_CLOSE
      const isOpen   = ear > DROP_OPEN
      if (blinkPhase.value === 'wait_close') {
        eyeClosedRef.value = false
        if (isClosed) { blinkPhase.value = 'wait_open'; eyeClosedRef.value = true }
      } else if (blinkPhase.value === 'wait_open') {
        eyeClosedRef.value = true
        if (isOpen) {
          eyeClosedRef.value  = false
          blinkCount++
          blinkCountRef.value = blinkCount
          if (!needDouble || blinkCount >= 2) {
            blinkCount = 0; blinkCountRef.value = 0; blinkPhase.value = 'done'
            onChallengeSuccess()
          } else {
            blinkPhase.value = 'wait_close'
          }
        }
      }
    }

    const processLivenessFrame = (det: any) => {
      const pos = det.landmarks.positions as { x: number; y: number }[]
      const ch  = currentChallenge.value
      if (!ch || pos.length < 68) return
      if (ch.type === 'blink') {
        processBlink(pos, !!ch.double)
      } else if (ch.type === 'turn_left' || ch.type === 'turn_right') {
        const noseX  = pos[30].x; const leftX = pos[36].x; const rightX = pos[45].x
        const faceW  = Math.abs(rightX - leftX)
        if (faceW < 1) return
        const yaw = (noseX - (leftX + rightX) / 2) / faceW
        if (!baseSet) { baseYaw = yaw; baseSet = true; return }
        const delta = yaw - baseYaw
        if (ch.type === 'turn_left'  && delta >  0.10) { onChallengeSuccess(); return }
        if (ch.type === 'turn_right' && delta < -0.10) { onChallengeSuccess(); return }
      } else if (ch.type === 'nod_up' || ch.type === 'nod_down') {
        const noseY = pos[30].y; const eyeAvgY = (pos[36].y + pos[45].y) / 2; const chinY = pos[8].y
        const faceH = Math.abs(chinY - eyeAvgY)
        if (faceH < 1) return
        const pitch = (noseY - eyeAvgY) / faceH
        if (!baseSet) { basePitch = pitch; baseSet = true; return }
        const delta = pitch - basePitch
        if (ch.type === 'nod_up'   && delta < -0.06) { onChallengeSuccess(); return }
        if (ch.type === 'nod_down' && delta >  0.06) { onChallengeSuccess(); return }
      }
    }

    // ── Camera & Detection ────────────────────────────────────────────────────
    const SSD_OPTS  = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.2 })
    const TINY_OPTS = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.3 })

    const startFaceCamera = async () => {
      try {
        faceStream = await navigator.mediaDevices.getUserMedia({ video: { width: 480, height: 640, facingMode: 'user', aspectRatio: 0.75 } })
        let waited = 0
        while (!faceVideo.value && waited < 3000) { await new Promise(r => setTimeout(r, 50)); waited += 50 }
        if (!faceVideo.value) return
        faceVideo.value.srcObject = faceStream
        await new Promise<void>(res => { faceVideo.value!.onloadedmetadata = () => res(); setTimeout(res, 3000) })
        await faceVideo.value.play()
        faceCamStatus.value = 'detecting'
        startDetectLoop()
      } catch (e: any) {
        faceMsg.value = 'Gagal mengakses kamera: ' + (e.message || e)
        faceMsgType.value = 'err'
      }
    }

    const startDetectLoop = () => {
      if (detectInterval !== null) { clearInterval(detectInterval); detectInterval = null }
      const interval = livenessActive.value ? 60 : 300
      detectInterval = window.setInterval(async () => {
        if (!faceVideo.value || faceVideo.value.readyState < 2 || isDetecting || successLock) return
        isDetecting = true
        try { await detectFrame() } finally { isDetecting = false }
      }, interval)
    }

    const detectFrame = async () => {
      if (!faceVideo.value) return
      let det: any = null
      try {
        if (!livenessActive.value) {
          det = await faceapi.detectSingleFace(faceVideo.value, SSD_OPTS).withFaceLandmarks().withFaceDescriptor()
          if (!det) det = await faceapi.detectSingleFace(faceVideo.value, TINY_OPTS).withFaceLandmarks().withFaceDescriptor()
        } else {
          det = await faceapi.detectSingleFace(faceVideo.value, TINY_OPTS).withFaceLandmarks()
          if (!det) det = await faceapi.detectSingleFace(faceVideo.value, SSD_OPTS).withFaceLandmarks()
        }
      } catch { det = null }
      if (det?.detection?.box) {
        const minW = (faceVideo.value.videoWidth || 480) * 0.10
        if (det.detection.box.width < minW) det = null
      }
      drawBox(faceCanvas.value, faceVideo.value, det)
      if (!det) { if (!livenessActive.value && !successLock) faceCamStatus.value = 'no_face'; return }
      if (livenessActive.value && !livenessCompleted.value) {
        faceCamStatus.value = 'liveness'
        if (det.landmarks && det.landmarks.positions.length >= 68) processLivenessFrame(det)
        return
      }
      if (!livenessActive.value && !livenessCompleted.value && !successLock) {
        if (!faceProfiles.length) { faceCamStatus.value = 'no_face'; return }
        faceCamStatus.value = 'ready'
        const descriptor = Array.from(det.descriptor) as number[]
        let bestDist = Infinity, bestProfile: any = null
        for (const p of faceProfiles) {
          const d = euclidean(descriptor, p.descriptor)
          if (d < bestDist) { bestDist = d; bestProfile = p }
        }
        if (bestDist <= THRESHOLD && bestProfile) startLivenessChallenge(bestProfile)
      }
    }

    const drawBox = (canvas: HTMLCanvasElement | null, video: HTMLVideoElement | null, det: any) => {
      if (!canvas || !video) return
      canvas.width = video.videoWidth; canvas.height = video.videoHeight
      const ctx = canvas.getContext('2d')!
      ctx.clearRect(0, 0, canvas.width, canvas.height)
      if (!det) return
      const box   = det.detection.box
      const color = faceCamStatus.value === 'success' || faceCamStatus.value === 'ready' ? '#17c653'
                  : faceCamStatus.value === 'liveness'      ? '#ffa500'
                  : faceCamStatus.value === 'liveness_fail' ? '#ff6b6b' : '#1b84ff'
      ctx.strokeStyle = color; ctx.lineWidth = 2.5; ctx.shadowColor = color; ctx.shadowBlur = 8
      ctx.strokeRect(box.x, box.y, box.width, box.height)
    }

    // ── GPS ───────────────────────────────────────────────────────────────────
    const STORE_LAT    = -7.2750211
    const STORE_LNG    = 112.6518010
    const STORE_RADIUS = 100
    const gpsStatus  = ref<'idle'|'getting'|'ok'|'error'>('idle')
    const gpsMsg     = ref('')
    let   userLat    = 0
    let   userLng    = 0

    const haversine = (lat1: number, lng1: number, lat2: number, lng2: number): number => {
      const R = 6371000
      const φ1 = lat1 * Math.PI / 180; const φ2 = lat2 * Math.PI / 180
      const Δφ = (lat2 - lat1) * Math.PI / 180; const Δλ = (lng2 - lng1) * Math.PI / 180
      const a = Math.sin(Δφ/2)**2 + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ/2)**2
      return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
    }

    const getGPS = (): Promise<{ lat: number; lng: number }> =>
      new Promise((resolve, reject) => {
        if (!navigator.geolocation) { reject(new Error('GPS tidak didukung browser')); return }
        navigator.geolocation.getCurrentPosition(
          pos => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
          err => reject(new Error(
            err.code === 1 ? 'Izin GPS ditolak. Aktifkan GPS di browser.' :
            err.code === 2 ? 'Posisi tidak tersedia. Pastikan GPS aktif.' : 'GPS timeout. Coba lagi.'
          )),
          { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        )
      })

    const processLogin = async (profile: any) => {
      try {
        gpsStatus.value = 'getting'; faceMsg.value = '📍 Mengambil lokasi GPS...'; faceMsgType.value = 'info'
        let coords: { lat: number; lng: number }
        try {
          coords = await getGPS()
        } catch (gpsErr: any) {
          gpsStatus.value = 'error'; faceMsg.value = gpsErr.message; faceMsgType.value = 'err'
          successLock = false; livenessCompleted.value = false; livenessActive.value = false
          pendingProfile = null; faceCamStatus.value = 'detecting'; startDetectLoop()
          setTimeout(() => { faceMsg.value = ''; gpsStatus.value = 'idle' }, 4000)
          return
        }
        const jarak = haversine(coords.lat, coords.lng, STORE_LAT, STORE_LNG)
        if (jarak > STORE_RADIUS) {
          gpsStatus.value = 'error'
          faceMsg.value = `📍 Kamu berada ${Math.round(jarak)}m dari toko. Absensi hanya bisa dilakukan dalam radius ${STORE_RADIUS}m.`
          faceMsgType.value = 'err'
          successLock = false; livenessCompleted.value = false; livenessActive.value = false
          pendingProfile = null; faceCamStatus.value = 'detecting'; startDetectLoop()
          setTimeout(() => { faceMsg.value = ''; gpsStatus.value = 'idle' }, 5000)
          return
        }
        userLat = coords.lat; userLng = coords.lng; gpsStatus.value = 'ok'; faceMsg.value = ''
        const { data } = await axios.post(`${API_BASE}/face/login`, { user_id: profile.user_id })
        store.setAuth(data)
        faceCamStatus.value = 'processing'; isCountingDown.value = true
        photoCountdownMsg.value = 'Liveness berhasil! Bersiap untuk foto...'; photoCountdown.value = 0
        await new Promise(r => setTimeout(r, 1000))
        for (let i = 3; i >= 1; i--) {
          photoCountdown.value = i
          photoCountdownMsg.value = i === 3 ? 'Lihat ke kamera!' : i === 2 ? 'Jangan bergerak...' : 'Siap...'
          await new Promise(r => setTimeout(r, 1000))
        }
        photoCountdown.value = 0; photoCountdownMsg.value = 'Foto diambil!'; isPhotoTaken.value = true
        const photo = faceVideo.value ? capturePhoto(faceVideo.value) : null
        await new Promise(r => setTimeout(r, 600))
        isCountingDown.value = false; isPhotoTaken.value = false
        const http = axios.create({ baseURL: API_BASE, headers: { Authorization: `Bearer ${data.api_token}` } })
        const today = (await http.get('/attendance/today')).data?.data
        let msg = 'Absensi berhasil'
        if (!today?.check_in_time) {
          const fd = new FormData()
          if (photo) { const blob = await fetch(photo).then(r => r.blob()); fd.append('photo', blob, 'checkin.jpg') }
          fd.append('lat', String(userLat)); fd.append('lng', String(userLng))
          msg = ((await http.post('/attendance/check-in', fd)).data?.message) || 'Check-in berhasil'
        } else {
          msg = today.check_out_time ? 'Absensi hari ini sudah lengkap.' : 'Kamu sudah check-in hari ini. Check-out tersedia di dashboard.'
        }
        recognizedUser.value = { name: profile.name, avatar: profile.avatar }
        attendanceMsg.value  = msg; faceCamStatus.value = 'success'
        stopFaceCamera()
        setTimeout(() => { router.push({ name: 'user-dashboard' }) }, 2500)
      } catch (e: any) {
        isCountingDown.value = false; isPhotoTaken.value = false; photoCountdown.value = 0
        faceMsg.value = e.response?.data?.message || 'Gagal proses absensi.'; faceMsgType.value = 'err'
        successLock = false; livenessCompleted.value = false; livenessActive.value = false
        pendingProfile = null; faceCamStatus.value = 'detecting'; startDetectLoop()
        setTimeout(() => { faceMsg.value = '' }, 3000)
      }
    }

    const stopFaceCamera = () => {
      if (detectInterval !== null) { clearInterval(detectInterval); detectInterval = null }
      stopChallengeTimer()
      faceStream?.getTracks().forEach(t => t.stop())
      faceStream = null
    }

    // ── Zoom ──────────────────────────────────────────────────────────────────
    const zoomLevel = ref(1)
    const isZoomed  = ref(false)
    const zoomIn    = () => { isZoomed.value = true;  document.body.style.overflow = 'hidden' }
    const zoomOut   = () => { isZoomed.value = false; document.body.style.overflow = '' }
    const zoomReset = () => { isZoomed.value = false; document.body.style.overflow = '' }

    // ── Leave state ───────────────────────────────────────────────────────────
    const leaveVideo         = ref<HTMLVideoElement | null>(null)
    const leaveCanvas        = ref<HTMLCanvasElement | null>(null)
    const leaveCamStatus     = ref('detecting')
    const leaveVerifyMsg     = ref('')
    const leaveVerifyMsgType = ref('info')
    const leaveVerifying     = ref(false)
    const leaveUser          = ref<any>(null)

    const leaveForm = ref({
      date: new Date().toISOString().split('T')[0],
      type: '',
      cutiType: '',
      reason: '',
      suratDokter: null as File | null
    })

    const leaveSubmitting  = ref(false)
    const leaveFormError   = ref('')
    const leaveFormSuccess = ref('')
    const leaveHistory     = ref<any[]>([])
    const todayRaw         = new Date().toISOString().split('T')[0]

    const sisaIzin  = ref(3)
    // FIX: hardcode cutiTypes — tidak bergantung pada API agar selalu tampil
    const cutiTypes = ref<Record<string, string>>({ ...IZIN_TYPES })

    let leaveStream: MediaStream | null    = null
    let leaveDetectInterval: number | null = null
    let autoLeaveTimer: number | null      = null

    const startLeaveCamera = async () => {
      try {
        leaveStream = await navigator.mediaDevices.getUserMedia({ video: { width: 480, height: 360, facingMode: 'user' } })
        let waited = 0
        while (!leaveVideo.value && waited < 3000) { await new Promise(r => setTimeout(r, 50)); waited += 50 }
        if (!leaveVideo.value) return
        leaveVideo.value.srcObject = leaveStream
        await new Promise<void>(res => { leaveVideo.value!.onloadedmetadata = () => res(); setTimeout(res, 3000) })
        await leaveVideo.value.play()
        leaveCamStatus.value = 'detecting'
        startLeaveDetectLoop()
      } catch (e: any) {
        leaveVerifyMsg.value = 'Gagal mengakses kamera: ' + (e.message || e)
        leaveVerifyMsgType.value = 'err'
      }
    }

    const startLeaveDetectLoop = () => {
      if (leaveDetectInterval !== null) clearInterval(leaveDetectInterval)
      leaveDetectInterval = window.setInterval(async () => {
        if (!leaveVideo.value || leaveVideo.value.readyState < 2) return
        const det = await faceapi.detectSingleFace(leaveVideo.value, TINY_OPTS).withFaceLandmarks()
        if (leaveCamStatus.value === 'processing') return
        leaveCamStatus.value = det ? 'ready' : 'no_face'
        drawBox(leaveCanvas.value, leaveVideo.value, det || null)
        if (det && !autoLeaveTimer && !leaveVerifying.value) {
          autoLeaveTimer = window.setTimeout(() => {
            autoLeaveTimer = null
            if (leaveCamStatus.value === 'ready') verifyLeaveUser()
          }, 1500)
        }
        if (!det && autoLeaveTimer) { clearTimeout(autoLeaveTimer); autoLeaveTimer = null }
      }, 500)
    }

    const stopLeaveCamera = () => {
      if (leaveDetectInterval !== null) { clearInterval(leaveDetectInterval); leaveDetectInterval = null }
      if (autoLeaveTimer !== null)      { clearTimeout(autoLeaveTimer);       autoLeaveTimer      = null }
      leaveStream?.getTracks().forEach(t => t.stop())
      leaveStream = null
    }

    const verifyLeaveUser = async () => {
      if (!leaveVideo.value || leaveVerifying.value) return
      leaveVerifying.value = true; leaveCamStatus.value = 'processing'; leaveVerifyMsg.value = ''
      try {
        const det = await faceapi.detectSingleFace(leaveVideo.value, TINY_OPTS).withFaceLandmarks().withFaceDescriptor()
        if (!det) {
          leaveCamStatus.value = 'no_face'; leaveVerifyMsg.value = 'Wajah tidak terdeteksi.'
          leaveVerifyMsgType.value = 'err'; leaveVerifying.value = false; return
        }
        const descriptor = Array.from(det.descriptor) as number[]
        let bestMatch: any = null, bestDist = Infinity
        for (const p of faceProfiles) {
          const d = euclidean(descriptor, p.descriptor)
          if (d < bestDist) { bestDist = d; bestMatch = p }
        }
        if (!bestMatch || bestDist > THRESHOLD) {
          leaveCamStatus.value = 'failed'; leaveVerifyMsg.value = 'Wajah tidak dikenali. Coba lagi.'
          leaveVerifyMsgType.value = 'err'; leaveVerifying.value = false
          setTimeout(() => { leaveCamStatus.value = 'detecting' }, 2000); return
        }
        leaveUser.value = { user_id: bestMatch.user_id, name: bestMatch.name, avatar: bestMatch.avatar, position: bestMatch.position }
        stopLeaveCamera()
        fetchLeaveHistory()
      } catch (_) {
        leaveCamStatus.value = 'failed'; leaveVerifyMsg.value = 'Gagal verifikasi.'
        leaveVerifyMsgType.value = 'err'; leaveVerifying.value = false
        setTimeout(() => { leaveCamStatus.value = 'detecting' }, 2000)
      }
    }

    const fetchLeaveHistory = async () => {
      // Ambil riwayat izin
      try {
        const { data } = await axios.get(`${API_BASE}/face/leaves/${leaveUser.value.user_id}`)
        leaveHistory.value = data.data || []
      } catch (_) {}

      // FIX: cutiTypes hardcoded, tidak perlu fetch
      cutiTypes.value = { ...IZIN_TYPES }

      // Fetch sisa kuota cuti jika token tersedia
      try {
        const token = store.token
        if (token) {
          const { data } = await axios.get(`${API_BASE}/leaves/my`, {
            headers: { Authorization: `Bearer ${token}` }
          })
          sisaIzin.value = data.sisa_cuti ?? 3
        }
      } catch (_) {}
    }

    const submitLeave = async () => {
      leaveFormError.value   = ''
      leaveFormSuccess.value = ''
      if (!leaveForm.value.date)  { leaveFormError.value = 'Pilih tanggal izin.'; return }
      if (!leaveForm.value.type)  { leaveFormError.value = 'Pilih jenis (Sakit / Cuti / Izin).'; return }

      // Izin wajib pilih jenis izin khusus
      if (leaveForm.value.type === 'izin' && !leaveForm.value.cutiType) {
        leaveFormError.value = 'Pilih jenis izin khusus terlebih dahulu.'; return
      }
      if (!leaveForm.value.reason.trim()) { leaveFormError.value = 'Alasan wajib diisi.'; return }
      if (leaveForm.value.type === 'sakit' && !leaveForm.value.suratDokter) {
        leaveFormError.value = 'Surat dokter wajib diupload.'; return
      }

      leaveSubmitting.value = true
      try {
        const fd = new FormData()
        fd.append('user_id', leaveUser.value.user_id)
        fd.append('date',    leaveForm.value.date)
        fd.append('type',    leaveForm.value.type)
        fd.append('reason',  leaveForm.value.reason)

        // cuti_type hanya untuk izin (butuh approval admin)
        if (leaveForm.value.type === 'izin') fd.append('cuti_type', leaveForm.value.cutiType)

        if (leaveForm.value.suratDokter) fd.append('surat_dokter', leaveForm.value.suratDokter)

        const res = await axios.post(`${API_BASE}/face/leaves`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        leaveFormSuccess.value = res.data?.message || 'Berhasil!'
        leaveForm.value = { date: todayRaw, type: '', cutiType: '', reason: '', suratDokter: null }
        await fetchLeaveHistory()
      } catch (e: any) {
        console.error('submit error detail:', e.response?.data)
        leaveFormError.value = e.response?.data?.message || 'Gagal mengajukan.'
      } finally {
        leaveSubmitting.value = false
      }
    }

    const deleteLeave = async (id: number) => {
      if (!confirm('Batalkan izin ini?')) return
      try {
        await axios.delete(`${API_BASE}/face/leaves/${id}`, { data: { user_id: leaveUser.value.user_id } })
        leaveHistory.value = leaveHistory.value.filter((l: any) => l.id !== id)
      } catch (e: any) {
        alert(e.response?.data?.message || 'Gagal membatalkan.')
      }
    }

    const resetLeaveUser = () => {
      leaveUser.value = null; leaveHistory.value = []; leaveVerifyMsg.value = ''
      leaveFormError.value = ''; leaveFormSuccess.value = ''
      leaveCamStatus.value = 'detecting'; leaveVerifying.value = false
      startLeaveCamera()
    }

    const leaveTypes = [
      { value: 'sakit', svg: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>`, label: 'Sakit' },
      { value: 'cuti',  svg: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`, label: 'Cuti' },
      { value: 'izin',  svg: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`, label: 'Izin' },
    ]

    const typeLabel = (t: string): string => ({ sakit: 'Sakit', izin: 'Izin', cuti: 'Cuti' } as Record<string, string>)[t] || t
    const typeIcon  = (t: string): string => ({
      sakit: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>`,
      izin:  `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`,
      cuti:  `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`,
    } as Record<string, string>)[t] || `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>`

    const formatDate = (d: string): string =>
      new Date(d).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })

    // ── Init & lifecycle ──────────────────────────────────────────────────────
    const initFace = async () => {
      faceMsg.value = ''; recognizedUser.value = null; successLock = false
      livenessActive.value = false; livenessCompleted.value = false; pendingProfile = null
      isCountingDown.value = false; isPhotoTaken.value = false; photoCountdown.value = 0; photoCountdownMsg.value = ''
      resetBlinkState()
      await loadModels(); await loadProfiles()
      await new Promise(r => setTimeout(r, 100))
      await startFaceCamera()
    }

    const initLeave = async () => {
      leaveVerifyMsg.value = ''; leaveCamStatus.value = 'detecting'; leaveVerifying.value = false
      await loadModels(); await loadProfiles()
      await nextTick(); await new Promise(r => setTimeout(r, 150))
      if (!leaveUser.value) await startLeaveCamera()
    }

    onMounted(() => { initFace() })
    onUnmounted(() => { stopFaceCamera(); stopLeaveCamera(); document.body.style.overflow = '' })

    watch(mode, (val) => {
      if (val === 'face')  { stopLeaveCamera(); initFace() }
      if (val === 'leave') { stopFaceCamera();  initLeave() }
      if (val === 'admin') { stopFaceCamera();  stopLeaveCamera() }
    })

    watch(livenessActive, () => { if (detectInterval !== null) startDetectLoop() })

    return {
      submitButton, isLoading, showPwd, rememberMe, lastEmail, initialValues,
      useLastEmail, loginSchema, onSubmitLogin,
      mode, switchMode,
      faceVideo, faceCanvas, faceModelLoading, faceModelProgress,
      faceCamStatus, faceMsg, faceMsgType, recognizedUser, attendanceMsg,
      livenessActive, livenessCompleted, challenges, currentChallengeIndex,
      currentChallenge, challengeTimerPct, challengeFailed, returnPhaseRef,
      blinkCountRef, eyeClosedRef, earBaselineReady, earBaselineFramesDisplay,
      earCurrentRef, earBaselineRef, earCloseThreshRef, earLeftRef, earRightRef, blinkPhase,
      photoCountdown, photoCountdownMsg, isCountingDown, isPhotoTaken,
      leaveVideo, leaveCanvas, leaveCamStatus, leaveVerifyMsg, leaveVerifyMsgType,
      leaveVerifying, leaveUser, leaveForm, leaveSubmitting, leaveFormError,
      leaveFormSuccess, leaveHistory, todayRaw,
      verifyLeaveUser, submitLeave, deleteLeave, resetLeaveUser,
      typeLabel, typeIcon, formatDate, leaveTypes,
      sisaIzin, cutiTypes,
      zoomLevel, zoomIn, zoomOut, zoomReset, isZoomed,
      gpsStatus, gpsMsg,
    }
  },
})
</script>

<style scoped>
.si-wrap{width:100%;animation:si-fadeup .4s ease both}
@keyframes si-fadeup{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.si-heading{font-size:28px;font-weight:800;line-height:1.2;letter-spacing:-.025em;color:#f0f0f5;margin-bottom:10px}
.si-heading-red{color:#e8262a}
.si-sub{font-size:13px;color:#55555e;margin-bottom:20px}
.si-link{color:#1b84ff;font-weight:600;text-decoration:none}.si-link:hover{color:#5aabff}
.si-tabs{display:flex;gap:6px;margin-bottom:20px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:5px}
.si-tab{flex:1;display:flex;align-items:center;justify-content:center;gap:6px;background:transparent;border:none;color:#55555e;font-family:'Inter',sans-serif;font-size:11.5px;font-weight:600;padding:8px 6px;border-radius:8px;cursor:pointer;transition:all .18s}
.si-tab.active{background:#e8262a;color:#fff;box-shadow:0 4px 12px rgba(232,38,42,.3)}
.si-tab:not(.active):hover{background:rgba(255,255,255,.06);color:#aaaabc}
.si-model-loading{display:flex;flex-direction:column;gap:14px}
.si-model-loading-cam{width:100%;aspect-ratio:3/4;background:#0a0c10;border-radius:16px;overflow:hidden;border:2px solid rgba(255,255,255,.07);display:flex;align-items:center;justify-content:center}
.si-cam-placeholder{display:flex;align-items:center;justify-content:center}
.si-model-loading-text{display:flex;align-items:center;gap:10px;color:#55555e;justify-content:center;font-size:13px;padding-bottom:8px}
.si-cam-big{position:relative;width:100%;aspect-ratio:3/4;background:#060810;border-radius:16px;overflow:hidden;border:2px solid rgba(255,255,255,.07);transition:border-color .3s,box-shadow .3s}
.si-cam-big.ready{border-color:#17c653;box-shadow:0 0 24px rgba(23,198,83,.2)}
.si-cam-big.success{border-color:#17c653;box-shadow:0 0 36px rgba(23,198,83,.35)}
.si-cam-big.failed,.si-cam-big.liveness_fail{border-color:#ff6b6b;box-shadow:0 0 20px rgba(255,107,107,.2)}
.si-cam-big.no_face{border-color:rgba(255,165,0,.35)}
.si-cam-big.processing{border-color:#1b84ff;box-shadow:0 0 24px rgba(27,132,255,.2)}
.si-cam-big.liveness{border-color:#ffa500;box-shadow:0 0 28px rgba(255,165,0,.25)}
.si-cam-video{width:100%;height:100%;object-fit:cover;transform:scaleX(-1);display:block}
.si-cam-canvas{position:absolute;inset:0;width:100%;height:100%;transform:scaleX(-1);pointer-events:none}
.si-corner{position:absolute;width:22px;height:22px;border-color:#e8262a;border-style:solid;border-width:0;z-index:3}
.si-tl{top:10px;left:10px;border-top-width:2.5px;border-left-width:2.5px;border-top-left-radius:5px}
.si-tr{top:10px;right:10px;border-top-width:2.5px;border-right-width:2.5px;border-top-right-radius:5px}
.si-bl{bottom:10px;left:10px;border-bottom-width:2.5px;border-left-width:2.5px;border-bottom-left-radius:5px}
.si-br{bottom:10px;right:10px;border-bottom-width:2.5px;border-right-width:2.5px;border-bottom-right-radius:5px}
.si-cam-status-pill{position:absolute;top:12px;left:50%;transform:translateX(-50%);display:flex;align-items:center;gap:6px;background:rgba(8,10,16,.88);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:5px 14px;font-size:11.5px;font-weight:600;color:#aaaabc;white-space:nowrap;z-index:5}
.si-cam-status-dot{width:7px;height:7px;border-radius:50%;background:#3a3a48;flex-shrink:0}
.si-cam-status-pill.ready .si-cam-status-dot,.si-cam-status-pill.success .si-cam-status-dot{background:#17c653;box-shadow:0 0 6px #17c653;animation:blink-dot 1.2s ease-in-out infinite}
.si-cam-status-pill.liveness .si-cam-status-dot{background:#ffa500;box-shadow:0 0 6px #ffa500;animation:blink-dot .8s ease-in-out infinite}
.si-cam-status-pill.no_face .si-cam-status-dot{background:#ffa500}
.si-cam-status-pill.failed .si-cam-status-dot,.si-cam-status-pill.liveness_fail .si-cam-status-dot{background:#ff6b6b}
.si-cam-status-pill.processing .si-cam-status-dot{background:#1b84ff;animation:blink-dot .6s ease-in-out infinite}
.si-cam-status-pill.ready{color:#17c653;border-color:rgba(23,198,83,.25)}
.si-cam-status-pill.success{color:#17c653;border-color:rgba(23,198,83,.3)}
.si-cam-status-pill.no_face{color:#ffa500;border-color:rgba(255,165,0,.25)}
.si-cam-status-pill.failed,.si-cam-status-pill.liveness_fail{color:#ff6b6b;border-color:rgba(255,107,107,.25)}
.si-cam-status-pill.processing{color:#5aabff;border-color:rgba(27,132,255,.25)}
.si-cam-status-pill.liveness{color:#ffa500;border-color:rgba(255,165,0,.3)}
@keyframes blink-dot{0%,100%{opacity:1}50%{opacity:.35}}
.si-recognized-overlay{position:absolute;inset:0;background:rgba(6,8,16,.82);backdrop-filter:blur(6px);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;animation:si-fadeup .35s ease both;z-index:10}
.si-rec-avatar{width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #17c653;box-shadow:0 0 24px rgba(23,198,83,.4)}
.si-rec-avatar-fallback{width:80px;height:80px;border-radius:50%;background:#e8262a;color:#fff;font-size:32px;font-weight:700;display:flex;align-items:center;justify-content:center;border:3px solid #17c653}
.si-rec-check-badge{width:26px;height:26px;border-radius:50%;background:#17c653;color:#fff;font-size:13px;font-weight:900;display:flex;align-items:center;justify-content:center;margin-top:-22px;margin-left:56px}
.si-rec-name{font-size:18px;font-weight:800;color:#f0f0f5;margin-top:4px}
.si-rec-msg{font-size:13px;color:#17c653;font-weight:600;background:rgba(23,198,83,.12);padding:5px 16px;border-radius:20px;border:1px solid rgba(23,198,83,.25)}
.si-face-body{display:flex;flex-direction:column;gap:10px}
.si-liveness-bar-section{background:rgba(255,165,0,.06);border:1.5px solid rgba(255,165,0,.25);border-radius:14px;padding:14px 16px;animation:si-fadeup .25s ease}
.si-liveness-bar-section.shake{animation:shake .5s ease;border-color:rgba(255,107,107,.5);background:rgba(255,107,107,.06)}
@keyframes shake{0%,100%{transform:translateX(0)}20%{transform:translateX(-8px)}40%{transform:translateX(8px)}60%{transform:translateX(-5px)}80%{transform:translateX(5px)}}
.si-liveness-dots{display:flex;justify-content:center;gap:8px;margin-bottom:12px}
.si-lv-dot{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.04);border:1.5px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;transition:all .25s;color:#55555e}
.si-lv-dot.done{background:rgba(23,198,83,.15);border-color:rgba(23,198,83,.45);color:#17c653}
.si-lv-dot.active{background:rgba(255,165,0,.15);border-color:rgba(255,165,0,.55);box-shadow:0 0 12px rgba(255,165,0,.25);transform:scale(1.2);color:#ffa500}
.si-lv-instruction{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.si-lv-icon-big{display:flex;align-items:center;justify-content:center;width:48px;height:48px;background:rgba(27,132,255,.12);border-radius:12px;flex-shrink:0;color:#1b84ff}
.si-lv-text-wrap{flex:1}
.si-lv-step{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#55555e;margin-bottom:3px}
.si-lv-action{font-size:15px;font-weight:800;color:#f0f0f5;line-height:1.3}
.si-lv-hint-small{font-size:11px;color:#55555e;margin-top:3px}
.si-lv-return-phase{background:rgba(23,198,83,.1);border:1px solid rgba(23,198,83,.25);border-radius:8px;padding:7px 12px;font-size:12px;color:#17c653;text-align:center;margin:6px 0}
.si-lv-blink-state{text-align:center;font-size:12px;font-weight:700;margin:6px 0}
.si-blink-waiting{color:#55555e;padding:4px 0}
.si-blink-closed{color:#ffa500;background:rgba(255,165,0,.12);border:1px solid rgba(255,165,0,.3);border-radius:8px;padding:5px 12px;animation:blink-pulse .4s ease infinite alternate}
.si-blink-one{color:#17c653;background:rgba(23,198,83,.1);border:1px solid rgba(23,198,83,.3);border-radius:8px;padding:5px 12px}
@keyframes blink-pulse{from{opacity:.7}to{opacity:1}}
.si-ear-debug{margin-top:4px;font-size:10px;color:#3a3a48;background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.06);border-radius:6px;padding:3px 8px;display:inline-block}
.si-ear-debug strong{font-weight:700}
.si-lv-timer-wrap{height:5px;background:rgba(255,255,255,.07);border-radius:4px;overflow:hidden;margin-bottom:7px}
.si-lv-timer-bar{height:100%;background:linear-gradient(90deg,#ffa500,#ffcc44);border-radius:4px;transition:width .1s linear}
.si-lv-timer-bar.warn{background:linear-gradient(90deg,#1b84ff,#ffa500)}
.si-lv-timer-bar.danger{background:linear-gradient(90deg,#ffa500,#ff4444)}
.si-lv-timer-label{font-size:11px;color:#55555e;text-align:center;margin-top:4px}
.si-lv-timer-label.urgent{color:#ff9933;font-weight:600}
.si-lv-session-badge{display:flex;align-items:center;justify-content:center;margin-top:8px;font-size:10.5px;color:#3a3a45;font-weight:600}
.si-face-status-bar{padding:10px 0 2px}
.si-face-guide{display:flex;align-items:center;gap:8px;color:#3a3a48;font-size:12px;justify-content:center}
.si-face-guide-icon{color:#3a3a48;display:flex}
.si-leave-scan-wrap{margin-bottom:14px}
.si-leave-scan-info{margin-bottom:14px}
.si-leave-scan-steps{display:flex;gap:0;margin-bottom:8px}
.si-leave-scan-step{display:flex;align-items:center;gap:8px;font-size:12px;color:#3a3a48;flex:1;padding:8px 10px;background:rgba(255,255,255,.02);border-radius:8px}
.si-leave-scan-step.active{color:#f0f0f5;background:rgba(27,132,255,.08);border:1px solid rgba(27,132,255,.15)}
.si-leave-scan-step.done{color:#17c653;background:rgba(23,198,83,.06);border:1px solid rgba(23,198,83,.15)}
.si-step-num{width:22px;height:22px;border-radius:50%;background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
.si-step-num.done{background:rgba(23,198,83,.15);border-color:rgba(23,198,83,.4);color:#17c653}
.si-leave-scan-step.active .si-step-num{background:rgba(27,132,255,.2);border-color:rgba(27,132,255,.5);color:#5aabff}
.si-leave-user-card{display:flex;align-items:center;gap:12px;background:rgba(23,198,83,.06);border:1px solid rgba(23,198,83,.2);border-radius:12px;padding:12px 14px;margin-bottom:14px}
.si-leave-user-check{width:28px;height:28px;border-radius:50%;background:#17c653;color:#fff;font-size:14px;font-weight:900;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.si-leave-user-avatar{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #17c653;flex-shrink:0}
.si-leave-user-avatar-fb{width:38px;height:38px;border-radius:50%;background:#e8262a;color:#fff;font-size:16px;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid #17c653;flex-shrink:0}
.si-leave-user-info{flex:1;min-width:0}
.si-leave-user-name{font-size:14px;font-weight:700;color:#f0f0f5}
.si-leave-user-pos{font-size:11px;color:#17c653;margin-top:2px}
.si-leave-change-btn{display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#aaaabc;border-radius:8px;padding:7px;cursor:pointer}
.si-leave-change-btn:hover{background:rgba(255,255,255,.1);color:#f0f0f5}
.si-leave-type-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.si-leave-type-btn{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.03);border:1.5px solid rgba(255,255,255,.07);border-radius:10px;padding:10px 12px;font-size:13px;font-weight:600;color:#72727a;cursor:pointer;transition:all .15s;font-family:inherit}
.si-leave-type-btn:hover{background:rgba(255,255,255,.06);color:#aaaabc}
.si-leave-type-btn.active{background:rgba(232,38,42,.1);border-color:rgba(232,38,42,.4);color:#ff8080}
.si-ltype-icon{display:flex;align-items:center;color:inherit}
.si-ltype-icon svg{stroke:currentColor}
.si-face-msg{padding:10px 14px;border-radius:9px;font-size:12.5px;margin-bottom:10px}
.si-face-msg.ok{background:rgba(23,198,83,.08);color:#17c653;border:1px solid rgba(23,198,83,.2)}
.si-face-msg.err{background:rgba(255,107,107,.08);color:#ff6b6b;border:1px solid rgba(255,107,107,.2)}
.si-face-msg.info{background:rgba(27,132,255,.08);color:#5aabff;border:1px solid rgba(27,132,255,.2)}
.si-face-btn{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;background:#e8262a;color:#fff;border:none;border-radius:10px;font-family:'Inter',sans-serif;font-size:13.5px;font-weight:700;padding:13px;cursor:pointer;margin-bottom:10px;margin-top:4px;transition:background .18s,transform .12s}
.si-face-btn:hover:not(:disabled){background:#ff3a3d;transform:translateY(-1px)}
.si-face-btn:disabled{opacity:.45;cursor:not-allowed}
.si-upload-box{display:block;border:1.5px dashed rgba(255,255,255,.12);border-radius:10px;padding:14px 16px;cursor:pointer;transition:all .15s;background:rgba(255,255,255,.02)}
.si-upload-box:hover{border-color:rgba(27,132,255,.4)}
.si-upload-box.has-file{border-color:rgba(23,198,83,.4);background:rgba(23,198,83,.05);border-style:solid}
.si-upload-input{display:none}
.si-upload-placeholder{display:flex;flex-direction:column;align-items:center;gap:8px;color:#3a3a48;font-size:12px;text-align:center}
.si-upload-done{display:flex;align-items:center;gap:8px;font-size:12.5px;color:#17c653;font-weight:600}
.si-upload-done span{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.si-upload-clear{background:rgba(255,107,107,.1);border:1px solid rgba(255,107,107,.2);color:#ff6b6b;border-radius:5px;padding:2px 7px;font-size:11px;cursor:pointer;flex-shrink:0}
.si-leave-form{display:flex;flex-direction:column;gap:12px;margin-bottom:20px}
.si-leave-field{display:flex;flex-direction:column;gap:6px}
.si-leave-field label{font-size:11px;font-weight:700;color:#55555e;text-transform:uppercase;letter-spacing:.5px}
.si-leave-req{font-size:10px;font-weight:600;color:#e8262a;text-transform:none;letter-spacing:0;margin-left:6px}
.si-leave-input{background:#181b22;border:1.5px solid rgba(255,255,255,.08);border-radius:9px;color:#f0f0f5;padding:9px 12px;font-size:13px;outline:none;width:100%;box-sizing:border-box;transition:border-color .15s}
.si-leave-input:focus{border-color:rgba(27,132,255,.5)}
.si-leave-input option{background:#15171e}
.si-leave-ta{resize:vertical;min-height:64px;font-family:inherit}
.si-leave-history{margin-top:8px}
.si-leave-history-title{font-size:12px;font-weight:700;color:#55555e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px}
.si-leave-empty{text-align:center;padding:20px;color:#3a3a48;font-size:13px}
.si-leave-list{display:flex;flex-direction:column;gap:8px}
.si-leave-item{display:flex;align-items:flex-start;gap:10px;background:#0d0f14;border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:10px 12px}
.si-leave-item-icon{display:flex;align-items:center;color:#aaaabc;flex-shrink:0;margin-top:2px}
.si-leave-item-body{flex:1;min-width:0}
.si-leave-item-type{font-size:13px;font-weight:600;color:#f0f0f5}
.si-leave-item-date{font-size:11px;color:#55555e;margin-top:2px}
.si-leave-item-reason{font-size:11px;color:#aaaabc;margin-top:3px;font-style:italic}
.si-leave-item-status{font-size:10.5px;font-weight:600;margin-top:5px;padding:2px 8px;border-radius:5px;display:inline-block}
.si-lstatus-pending{color:#ffc700;background:rgba(255,199,0,.1);border:1px solid rgba(255,199,0,.2)}
.si-lstatus-approved{color:#17c653;background:rgba(23,198,83,.1);border:1px solid rgba(23,198,83,.2)}
.si-lstatus-rejected{color:#ff6b6b;background:rgba(255,107,107,.1);border:1px solid rgba(255,107,107,.2)}
.si-leave-del{background:rgba(255,107,107,.1);border:1px solid rgba(255,107,107,.2);color:#ff6b6b;border-radius:7px;padding:5px 8px;font-size:11px;cursor:pointer;flex-shrink:0}
.si-field{margin-bottom:16px}
.si-field-top{display:flex;justify-content:space-between;align-items:center}
.si-label{display:block;font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#55555e;margin-bottom:7px}
.si-forgot{font-size:11.5px;color:#1b84ff;font-weight:600;text-decoration:none}
.si-inp-wrap{position:relative}
.si-inp-ico{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#3a3a48;pointer-events:none;display:flex;align-items:center}
.si-input{width:100%;background:#181b22 !important;border:1.5px solid rgba(255,255,255,.08) !important;border-radius:10px !important;color:#e2e2e8 !important;font-family:'Inter',sans-serif !important;font-size:13.5px !important;padding:12px 14px 12px 42px !important;outline:none !important;transition:border-color .18s !important}
.si-input::placeholder{color:#3a3a48 !important}
.si-input:focus{border-color:#1b84ff !important;box-shadow:0 0 0 3px rgba(27,132,255,.12) !important}
.si-input-pwd{padding-right:44px !important}
.si-eye-btn{position:absolute;right:11px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#3a3a48;display:flex;align-items:center;padding:4px}
.si-last-email{display:inline-flex;align-items:center;gap:5px;margin-top:7px;font-size:11px;color:#3a3a48;cursor:pointer;padding:4px 10px 4px 8px;background:rgba(27,132,255,.06);border:1px solid rgba(27,132,255,.14);border-radius:20px}
.si-last-email:hover{background:rgba(27,132,255,.12);color:#7ac0ff}
.si-last-email strong{color:#5aabff;font-weight:600}
.si-error{font-size:11px;color:#ff6b6b}
.si-remember{display:flex;align-items:center;gap:9px;margin-bottom:20px}
.si-chk{width:17px;height:17px;border-radius:5px;background:#181b22;border:1.5px solid rgba(255,255,255,.10);appearance:none;-webkit-appearance:none;cursor:pointer;position:relative;transition:all .15s}
.si-chk:checked{background:#1b84ff;border-color:#1b84ff}
.si-chk:checked::after{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='10' height='8' viewBox='0 0 10 8' fill='none'%3E%3Cpath d='M1 4l2.5 2.5L9 1' stroke='white' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center no-repeat}
.si-chk-label{font-size:12.5px;color:#55555e;cursor:pointer;user-select:none}
.si-submit-btn{width:100%;background:#e8262a;color:#fff;border:none;border-radius:10px;font-family:'Inter',sans-serif;font-size:14px;font-weight:700;padding:13.5px;cursor:pointer;margin-bottom:20px;transition:background .18s,transform .12s}
.si-submit-btn:hover:not(:disabled){background:#ff3a3d;transform:translateY(-1px)}
.si-submit-btn:disabled{opacity:.55;cursor:not-allowed}
.si-btn-inner{display:flex;align-items:center;justify-content:center;gap:8px}
@keyframes si-spin{to{transform:rotate(360deg)}}
.si-spin{display:inline-block;width:15px;height:15px;border:2px solid rgba(255,255,255,.25);border-top-color:#fff;border-radius:50%;animation:si-spin .65s linear infinite}
.si-spin-big{display:inline-block;width:36px;height:36px;border:3px solid rgba(255,255,255,.08);border-top-color:rgba(232,38,42,.6);border-radius:50%;animation:si-spin .8s linear infinite}
.si-divider{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.si-divider::before,.si-divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.07)}
.si-divider span{font-size:10px;font-weight:700;letter-spacing:.1em;color:#3a3a48}
.si-socials{display:flex;flex-direction:column;gap:9px}
.si-social-btn{display:flex;align-items:center;gap:12px;background:#181b22;border:1.5px solid rgba(255,255,255,.07);border-radius:10px;color:#72727a;font-family:'Inter',sans-serif;font-size:13px;font-weight:500;padding:11px 16px;cursor:pointer;transition:all .15s;width:100%}
.si-social-btn:hover{background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.13);color:#e2e2e8}
.si-zoom-overlay{position:fixed;inset:0;background:rgba(0,0,0,.92);backdrop-filter:blur(8px);z-index:99999;display:flex;align-items:center;justify-content:center;animation:si-fadeup .2s ease}
.si-zoom-container{width:min(90vw,500px);display:flex;align-items:center;justify-content:center}
.si-cam-zoomed{width:100% !important;aspect-ratio:3/4 !important;border-radius:20px !important;border-width:2px !important}
.si-zoom-close-btn{position:absolute;top:12px;left:12px;width:36px;height:36px;border-radius:50%;background:rgba(8,10,16,.85);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);color:#aaaabc;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;transition:all .15s}
.si-zoom-close-btn:hover{background:rgba(255,107,107,.2);border-color:rgba(255,107,107,.4);color:#ff6b6b}
.si-zoom-btns{position:absolute;bottom:12px;right:12px;display:flex;flex-direction:column;gap:6px;z-index:10}
.si-zoom-btn{width:32px;height:32px;border-radius:8px;background:rgba(8,10,16,.85);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.12);color:#aaaabc;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s}
.si-zoom-btn:hover{background:rgba(27,132,255,.2);border-color:rgba(27,132,255,.4);color:#5aabff}
.si-countdown-overlay{position:absolute;inset:0;background:rgba(6,8,16,.75);backdrop-filter:blur(3px);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;z-index:20;animation:si-fadeup .3s ease}
.si-countdown-number{font-size:96px;font-weight:900;color:#fff;line-height:1;text-shadow:0 0 40px rgba(232,38,42,.8),0 0 80px rgba(232,38,42,.4);animation:countdown-pop .4s cubic-bezier(0.34,1.56,0.64,1)}
@keyframes countdown-pop{from{transform:scale(1.8);opacity:0}to{transform:scale(1);opacity:1}}
.si-countdown-flash{display:flex;flex-direction:column;align-items:center;gap:8px;color:#17c653;animation:flash-in .3s ease}
.si-countdown-flash svg{stroke:#17c653}
.si-countdown-flash span{font-size:18px;font-weight:700}
@keyframes flash-in{0%{opacity:0;transform:scale(.8)}50%{opacity:1;transform:scale(1.1)}100%{transform:scale(1)}}
.si-countdown-msg{font-size:14px;font-weight:600;color:#aaaabc;background:rgba(8,10,16,.8);padding:6px 16px;border-radius:20px;border:1px solid rgba(255,255,255,.1)}
.si-cuti-type-list{display:flex;flex-direction:column;gap:6px}
.si-cuti-type-btn{display:flex;align-items:center;background:rgba(255,255,255,.03);border:1.5px solid rgba(255,255,255,.07);border-radius:9px;padding:9px 12px;font-size:13px;font-weight:500;color:#72727a;cursor:pointer;transition:all .15s;font-family:inherit;text-align:left}
.si-cuti-type-btn:hover{background:rgba(255,255,255,.06);color:#aaaabc}
.si-cuti-type-btn.active{background:rgba(168,85,247,.1);border-color:rgba(168,85,247,.4);color:#c084fc}
.si-cuti-info{display:flex;align-items:flex-start;gap:6px;font-size:11px;color:#f59e0b;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:8px 10px;margin-top:2px}
.si-kuota-info{display:flex;align-items:flex-start;gap:7px;font-size:12px;border-radius:8px;padding:8px 10px}
.si-kuota-info.ok{color:#17c653;background:rgba(23,198,83,.08);border:1px solid rgba(23,198,83,.2)}
.si-kuota-info.warn{color:#ffa500;background:rgba(255,165,0,.08);border:1px solid rgba(255,165,0,.2)}
.si-kuota-info.danger{color:#ff6b6b;background:rgba(255,107,107,.08);border:1px solid rgba(255,107,107,.2)}
.si-gps-bar{display:flex;align-items:center;gap:8px;padding:9px 14px;border-radius:10px;font-size:12.5px;font-weight:600;margin-bottom:8px}
.si-gps-bar.getting{background:rgba(27,132,255,.08);border:1px solid rgba(27,132,255,.2);color:#5aabff}
.si-gps-bar.ok{background:rgba(23,198,83,.08);border:1px solid rgba(23,198,83,.2);color:#17c653}
.si-gps-bar.error{background:rgba(255,107,107,.08);border:1px solid rgba(255,107,107,.2);color:#ff6b6b}
</style>