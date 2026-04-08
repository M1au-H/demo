<template>
  <div class="fr-wrap">

    <!-- MODE ADMIN -->
    <div v-if="mode === 'admin'" class="fr-admin-panel">
      <button class="fr-back-btn" @click="mode = 'face'">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M6 2L1 7l5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Kembali
      </button>
      <div class="fr-admin-header">
        <div class="fr-admin-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <h2 class="fr-admin-title">Login Admin</h2>
        <p class="fr-admin-sub">Masukkan kredensial admin Anda</p>
      </div>
      <div class="fr-field">
        <label class="fr-label">Email</label>
        <div class="fr-inp-wrap">
          <span class="fr-inp-ico"><svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="14" height="10" rx="2"/><polyline points="1,3 8,9 15,3"/></svg></span>
          <input v-model="adminEmail" type="email" class="fr-input" placeholder="admin@email.com" autocomplete="email" />
        </div>
      </div>
      <div class="fr-field">
        <label class="fr-label">Password</label>
        <div class="fr-inp-wrap">
          <span class="fr-inp-ico"><svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="10" height="8" rx="1.5"/><path d="M5 7V5a3 3 0 016 0v2"/></svg></span>
          <input v-model="adminPassword" :type="showPwd ? 'text' : 'password'" class="fr-input fr-input-pwd" placeholder="Password" autocomplete="current-password" @keyup.enter="submitAdmin" />
          <button type="button" class="fr-eye-btn" @click="showPwd = !showPwd">
            <svg v-if="!showPwd" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>
      <div v-if="adminError" class="fr-error-box">{{ adminError }}</div>
      <button class="fr-submit-btn" @click="submitAdmin" :disabled="adminLoading">
        <span v-if="!adminLoading" class="fr-btn-inner">Masuk sebagai Admin <svg width="13" height="13" viewBox="0 0 14 14" fill="none"><path d="M1 7h12M8 2l5 5-5 5" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <span v-else class="fr-btn-inner"><span class="fr-spin"></span> Memproses...</span>
      </button>
    </div>

    <!-- MODE FACE -->
    <div v-else class="fr-face-panel">
      <div class="fr-face-header">
        <div class="fr-logo-mark"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e8262a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <h1 class="fr-title">Absensi Wajah</h1>
        <p class="fr-subtitle">Verifikasi wajah untuk absensi</p>
      </div>

      <!-- Loading -->
      <div v-if="modelStatus === 'loading'" class="fr-status-box fr-status-loading">
        <span class="fr-spin"></span>
        <span>{{ loadingMessage }}</span>
      </div>

      <!-- Error -->
      <div v-else-if="modelStatus === 'error'" class="fr-status-box fr-status-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ errorMessage }}
        <button class="fr-retry-btn" @click="retryInit">🔄 Coba Lagi</button>
      </div>

      <!-- Kamera -->
      <div class="fr-camera-section">
        <div class="fr-viewfinder" :class="faceStatus" :style="{ visibility: modelStatus === 'ready' ? 'visible' : 'hidden', height: modelStatus !== 'ready' ? '0' : undefined, overflow: modelStatus !== 'ready' ? 'hidden' : undefined }">
          <video ref="videoEl" autoplay playsinline muted class="fr-video" />
          <canvas ref="overlayCanvas" class="fr-overlay-canvas" />
          <div class="fr-corner fr-tl"></div><div class="fr-corner fr-tr"></div>
          <div class="fr-corner fr-bl"></div><div class="fr-corner fr-br"></div>
          <div v-if="faceStatus === 'detecting'" class="fr-scanline"></div>

          <!-- LIVENESS CHALLENGE OVERLAY -->
          <div v-if="livenessActive && !livenessCompleted" class="fr-liveness-overlay">
            <div class="fr-liveness-card" :class="{ shake: challengeFailed }">
              <div class="fr-liveness-step">
                Langkah {{ currentChallengeIndex + 1 }} / {{ challenges.length }}
              </div>
              <div class="fr-liveness-icon">{{ currentChallenge?.icon }}</div>
              <div class="fr-liveness-text">{{ currentChallenge?.instruction }}</div>

              <!-- Blink status -->
              <div v-if="currentChallenge?.type === 'blink'" class="fr-liveness-blink-status">
                <div v-if="!blinkBaselineReady" class="fr-blink-wait">
                  Mengukur mata... ({{ blinkBaselineFrames }}/30)
                </div>
                <div v-else-if="blinkEyeClosed" class="fr-blink-closed">Mata tertutup ✓</div>
                <div v-else class="fr-blink-open">Kedipkan mata sekarang</div>
              </div>

              <div class="fr-liveness-bar-wrap">
                <div class="fr-liveness-bar" :style="{ width: challengeTimer + '%' }" :class="challengeTimer < 30 ? 'danger' : ''"></div>
              </div>
              <div class="fr-liveness-hint">{{ challengeTimer < 30 ? '⚠️ Waktu hampir habis!' : 'Ikuti instruksi di atas' }}</div>
            </div>
          </div>

          <!-- Liveness berhasil -->
          <div v-if="livenessCompleted && faceStatus !== 'success'" class="fr-liveness-success-badge">
            ✅ Verifikasi liveness berhasil
          </div>

          <div class="fr-face-badge" :class="faceStatus">
            <span v-if="faceStatus === 'idle'">📷 Arahkan wajah ke kamera</span>
            <span v-else-if="faceStatus === 'liveness'">🔐 Selesaikan verifikasi liveness</span>
            <span v-else-if="faceStatus === 'detecting'">🔍 Mendeteksi wajah...</span>
            <span v-else-if="faceStatus === 'matching'">⚡ Mencocokkan identitas...</span>
            <span v-else-if="faceStatus === 'success'">✅ {{ recognizedName }}</span>
            <span v-else-if="faceStatus === 'unknown'">❓ Wajah tidak dikenali</span>
            <span v-else-if="faceStatus === 'no_face'">👤 Tidak ada wajah terdeteksi</span>
            <span v-else-if="faceStatus === 'liveness_fail'">❌ Verifikasi gagal, coba lagi</span>
          </div>
        </div>

        <!-- Progress liveness -->
        <div v-if="livenessActive && !livenessCompleted && modelStatus === 'ready'" class="fr-challenges-progress">
          <div v-for="(ch, i) in challenges" :key="i" class="fr-ch-dot" :class="{ done: i < currentChallengeIndex, active: i === currentChallengeIndex }">
            <span>{{ ch.icon }}</span>
          </div>
        </div>

        <!-- Result card -->
        <div v-if="recognizedUser && attendanceResult" class="fr-result-card" :class="attendanceResult.type">
          <div class="fr-result-avatar">
            <img v-if="recognizedUser.avatar" :src="recognizedUser.avatar" class="fr-result-img" />
            <div v-else class="fr-result-initials">{{ recognizedUser.name?.charAt(0)?.toUpperCase() }}</div>
          </div>
          <div class="fr-result-info">
            <div class="fr-result-name">{{ recognizedUser.name }}</div>
            <div class="fr-result-msg">{{ attendanceResult.message }}</div>
            <div class="fr-result-time">{{ currentTime }}</div>
          </div>
          <div class="fr-result-icon">
            <svg v-if="attendanceResult.type === 'check-in'" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#17c653" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            <svg v-else-if="attendanceResult.type === 'check-out'" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#1b84ff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <svg v-else width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffa500" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
        </div>

        <div v-if="faceError" class="fr-error-box">{{ faceError }}</div>
        <div v-if="faceProfiles.length === 0 && modelStatus === 'ready'" class="fr-error-box">⚠️ Belum ada wajah terdaftar. Hubungi admin.</div>
        <div v-if="!recognizedUser && !livenessActive && modelStatus === 'ready'" class="fr-tips">💡 Pastikan cahaya cukup dan wajah terlihat jelas</div>
      </div>

      <div class="fr-admin-link">
        <button @click="mode = 'admin'" class="fr-admin-toggle">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Login sebagai Admin
        </button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import * as faceapi from 'face-api.js'
import axios from 'axios'

const MATCH_THRESHOLD = 0.48
const MODEL_URL       = '/models'
const API_BASE = (import.meta.env.VITE_APP_API_URL as string || '/api').replace(/\/$/, '')
const publicHttp = axios.create({ baseURL: API_BASE })
function authHttp(token: string) {
  return axios.create({ baseURL: API_BASE, headers: { Authorization: `Bearer ${token}` } })
}

// ── LIVENESS CHALLENGES ────────────────────────────────────────────
interface Challenge {
  type: 'blink' | 'turn_left' | 'turn_right' | 'nod_up' | 'nod_down'
  icon: string
  instruction: string
}

const ALL_CHALLENGES: Challenge[] = [
  { type: 'blink',      icon: '👁️',  instruction: 'Kedipkan mata sekali' },
  { type: 'turn_left',  icon: '⬅️',  instruction: 'Palingkan kepala ke KIRI' },
  { type: 'turn_right', icon: '➡️',  instruction: 'Palingkan kepala ke KANAN' },
  { type: 'nod_up',     icon: '⬆️',  instruction: 'Tengadahkan kepala ke ATAS' },
  { type: 'nod_down',   icon: '⬇️',  instruction: 'Tundukkan kepala ke BAWAH' },
]

function pickChallenges(n = 3): Challenge[] {
  const shuffled = [...ALL_CHALLENGES].sort(() => Math.random() - 0.5)
  return shuffled.slice(0, n)
}

// ── EAR (Eye Aspect Ratio) ─────────────────────────────────────────
function calcEAR(eye: faceapi.Point[]): number {
  if (!eye || eye.length < 6) return 0.3
  const A = Math.hypot(eye[1].x - eye[5].x, eye[1].y - eye[5].y)
  const B = Math.hypot(eye[2].x - eye[4].x, eye[2].y - eye[4].y)
  const C = Math.hypot(eye[0].x - eye[3].x, eye[0].y - eye[3].y)
  return C < 0.001 ? 0.3 : (A + B) / (2.0 * C)
}

export default defineComponent({
  name: 'FaceLoginPage',
  setup() {
    const authStore = useAuthStore()
    const router    = useRouter()

    const mode             = ref<'face' | 'admin'>('face')
    const modelStatus      = ref<'loading' | 'ready' | 'error'>('loading')
    const loadingMessage   = ref('Memuat sistem pengenalan wajah...')
    const errorMessage     = ref('Gagal memuat. Refresh halaman.')
    const faceStatus       = ref<'idle' | 'liveness' | 'detecting' | 'matching' | 'success' | 'unknown' | 'no_face' | 'liveness_fail'>('idle')
    const faceError        = ref('')
    const recognizedUser   = ref<any>(null)
    const recognizedName   = ref('')
    const attendanceResult = ref<any>(null)
    const currentTime      = ref('')
    const videoEl          = ref<HTMLVideoElement | null>(null)
    const overlayCanvas    = ref<HTMLCanvasElement | null>(null)
    const adminEmail       = ref('')
    const adminPassword    = ref('')
    const adminError       = ref('')
    const adminLoading     = ref(false)
    const showPwd          = ref(false)

    // ── LIVENESS STATE ──────────────────────────────────────────────
    const livenessActive        = ref(false)
    const livenessCompleted     = ref(false)
    const challenges            = ref<Challenge[]>([])
    const currentChallengeIndex = ref(0)
    const challengeTimer        = ref(100)
    const challengeFailed       = ref(false)
    const currentChallenge      = computed(() => challenges.value[currentChallengeIndex.value] ?? null)

    // ── BLINK DISPLAY STATE ─────────────────────────────────────────
    const blinkBaselineReady  = ref(false)
    const blinkBaselineFrames = ref(0)
    const blinkEyeClosed      = ref(false)

    // ── Non-reactive blink variables ────────────────────────────────
    let earBuf: number[]  = []
    let earBase           = 0
    let earReady          = false
    let eyeIsOpen         = true
    let blinkCnt          = 0

    // Non-reactive movement variables
    let prevYaw   = 0
    let prevPitch = 0
    let baseSet   = false

    let challengeTimerInt: number | null = null
    let challengeSecondsLeft = 15

    let mediaStream   : MediaStream | null = null
    let faceProfiles  : any[]              = []
    let detectionLoop : number | null      = null
    let isProcessing                       = false
    let successLock                        = false
    let clockInterval : number | null      = null
    let pendingProfile: any                = null

    // ── INIT ─────────────────────────────────────────────────────────
    const init = async () => {
      modelStatus.value    = 'loading'
      loadingMessage.value = 'Memuat model AI (±5 detik pertama kali)...'
      faceError.value      = ''
      successLock          = false
      isProcessing         = false
      livenessCompleted.value = false
      livenessActive.value    = false

      try {
        loadingMessage.value = 'Memuat TinyFaceDetector...'
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL)

        loadingMessage.value = 'Memuat faceLandmark68Net...'
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL)

        loadingMessage.value = 'Memuat faceRecognitionNet...'
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)

        loadingMessage.value = 'Mengambil data wajah...'
        await loadFaceProfiles()
        loadingMessage.value = 'Memulai kamera...'

        mediaStream = await navigator.mediaDevices.getUserMedia({
          video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
        })

        modelStatus.value = 'ready'
        faceStatus.value  = 'idle'

        await nextTick()
        let waited = 0
        while (!videoEl.value && waited < 3000) {
          await new Promise(r => setTimeout(r, 50))
          waited += 50
        }
        if (!videoEl.value) throw new Error('Video element tidak ditemukan di DOM.')

        videoEl.value.srcObject = mediaStream
        await new Promise<void>((res) => {
          const v = videoEl.value!
          v.onloadedmetadata = () => res()
          setTimeout(() => res(), 5000)
        })
        await videoEl.value.play()

        faceStatus.value = 'detecting'
        startDetectionLoop()
      } catch (e: any) {
        mediaStream?.getTracks().forEach(t => t.stop())
        mediaStream = null
        modelStatus.value = 'error'
        if (e?.name === 'NotAllowedError') {
          errorMessage.value = 'Akses kamera ditolak. Izinkan akses kamera di browser lalu refresh.'
        } else if (e?.name === 'NotFoundError') {
          errorMessage.value = 'Kamera tidak ditemukan di perangkat ini.'
        } else {
          errorMessage.value = `Gagal memulai: ${e?.message ?? e}`
        }
      }
    }

    const retryInit = () => { stopCamera(); init() }

    const loadFaceProfiles = async () => {
      try {
        const res = await publicHttp.get('/face/profiles')
        faceProfiles = res.data?.data ?? []
      } catch (e) {
        faceProfiles = []
      }
    }

    const stopCamera = () => {
      if (detectionLoop) { clearInterval(detectionLoop); detectionLoop = null }
      if (clockInterval) { clearInterval(clockInterval); clockInterval = null }
      stopChallengeTimer()
      mediaStream?.getTracks().forEach(t => t.stop())
      mediaStream = null
    }

    // ── DETECTION LOOP ────────────────────────────────────────────────
    const startDetectionLoop = () => {
      if (detectionLoop) clearInterval(detectionLoop)
      detectionLoop = window.setInterval(async () => {
        if (isProcessing || successLock || !videoEl.value) return
        isProcessing = true
        try { await detectFrame() } finally { isProcessing = false }
      }, livenessActive.value ? 60 : 300)
    }

    const detectFrame = async () => {
      if (!videoEl.value || videoEl.value.readyState < 2) return
      const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })

      const detection = await faceapi
        .detectSingleFace(videoEl.value, opts)
        .withFaceLandmarks()
        .withFaceDescriptor()

      drawOverlay(detection)

      if (!detection) {
        if (faceStatus.value !== 'liveness' && faceStatus.value !== 'success') {
          faceStatus.value = 'no_face'
        }
        return
      }

      const positions = detection.landmarks.positions
      if (positions.length < 68) return

      // ── MODE: LIVENESS CHALLENGE ──
      if (livenessActive.value && !livenessCompleted.value) {
        faceStatus.value = 'liveness'
        processLivenessFrame(detection)
        return
      }

      // ── MODE: MATCHING ──
      if (!livenessActive.value && !livenessCompleted.value) {
        faceStatus.value = 'detecting'
        if (faceProfiles.length === 0) return

        let bestDist = Infinity
        let bestProfile: any = null
        for (const profile of faceProfiles) {
          const dist = faceapi.euclideanDistance(detection.descriptor, new Float32Array(profile.descriptor))
          if (dist < bestDist) { bestDist = dist; bestProfile = profile }
        }

        if (bestDist <= MATCH_THRESHOLD && bestProfile) {
          pendingProfile = bestProfile
          startLivenessChallenge()
        } else {
          faceStatus.value = 'unknown'
          setTimeout(() => { if (!successLock && !livenessActive.value) faceStatus.value = 'detecting' }, 2000)
        }
        return
      }

      // ── Liveness selesai → proses login ──
      if (livenessCompleted.value && !successLock) {
        faceStatus.value = 'matching'
        successLock = true
        if (pendingProfile) await processLogin(pendingProfile)
      }
    }

    // ── LIVENESS CHALLENGE ─────────────────────────────────────────
    const startLivenessChallenge = () => {
      challenges.value            = pickChallenges(3)
      currentChallengeIndex.value = 0
      challengeTimer.value        = 100
      challengeFailed.value       = false
      livenessActive.value        = true
      faceStatus.value            = 'liveness'
      resetAllLivenessState()
      startChallengeTimer()
      startDetectionLoop()
    }

    const resetAllLivenessState = () => {
      prevYaw = 0; prevPitch = 0; baseSet = false
      resetBlinkState()
    }

    const resetBlinkState = () => {
      earBuf    = []
      earBase   = 0
      earReady  = false
      eyeIsOpen = true
      blinkCnt  = 0
      blinkBaselineReady.value  = false
      blinkBaselineFrames.value = 0
      blinkEyeClosed.value      = false
    }

    const startChallengeTimer = () => {
      stopChallengeTimer()
      challengeSecondsLeft = 15
      challengeTimer.value = 100
      challengeTimerInt = window.setInterval(() => {
        challengeSecondsLeft -= 0.1
        challengeTimer.value = Math.max(0, (challengeSecondsLeft / 15) * 100)
        if (challengeSecondsLeft <= 0) onLivenessFail()
      }, 100)
    }

    const stopChallengeTimer = () => {
      if (challengeTimerInt) { clearInterval(challengeTimerInt); challengeTimerInt = null }
    }

    const onChallengeSuccess = () => {
      stopChallengeTimer()
      const nextIdx = currentChallengeIndex.value + 1
      if (nextIdx >= challenges.value.length) {
        livenessCompleted.value = true
        livenessActive.value    = false
        faceStatus.value        = 'detecting'
        startDetectionLoop()
      } else {
        currentChallengeIndex.value = nextIdx
        resetAllLivenessState()
        startChallengeTimer()
      }
    }

    const onLivenessFail = () => {
      stopChallengeTimer()
      challengeFailed.value   = true
      livenessActive.value    = false
      livenessCompleted.value = false
      pendingProfile          = null
      faceStatus.value        = 'liveness_fail'
      faceError.value         = '❌ Verifikasi liveness gagal. Pastikan Anda mengikuti instruksi.'
      setTimeout(() => {
        challengeFailed.value = false
        faceError.value       = ''
        faceStatus.value      = 'detecting'
        startDetectionLoop()
      }, 3000)
    }

    // ── PROSES SETIAP FRAME LIVENESS ─────────────────────────────────
    const processLivenessFrame = (detection: any) => {
      const positions = detection.landmarks.positions
      const ch = currentChallenge.value
      if (!ch || positions.length < 68) return

      if (ch.type === 'blink') {
        processBlink(positions)

      } else if (ch.type === 'turn_left' || ch.type === 'turn_right') {
        const noseX  = positions[30].x
        const leftX  = positions[36].x
        const rightX = positions[45].x
        const faceW  = Math.abs(rightX - leftX)
        if (faceW < 1) return
        const yaw = (noseX - (leftX + rightX) / 2) / faceW

        if (!baseSet) { prevYaw = yaw; baseSet = true; return }
        const delta = yaw - prevYaw

        if (ch.type === 'turn_left'  && delta >  0.10) { onChallengeSuccess(); return }
        if (ch.type === 'turn_right' && delta < -0.10) { onChallengeSuccess(); return }

      } else if (ch.type === 'nod_up' || ch.type === 'nod_down') {
        const noseY    = positions[30].y
        const eyeAvgY  = (positions[36].y + positions[45].y) / 2
        const chinY    = positions[8].y
        const faceH    = Math.abs(chinY - eyeAvgY)
        if (faceH < 1) return
        const pitch = (noseY - eyeAvgY) / faceH

        if (!baseSet) { prevPitch = pitch; baseSet = true; return }
        const delta = pitch - prevPitch

        if (ch.type === 'nod_up'   && delta < -0.06) { onChallengeSuccess(); return }
        if (ch.type === 'nod_down' && delta >  0.06) { onChallengeSuccess(); return }
      }
    }

    // ── BLINK DETECTION — Fixed ───────────────────────────────────────
    //
    // PERUBAHAN UTAMA:
    //
    // 1. Baseline 30 frame, hanya EAR > 0.22 (filter frame setengah merem)
    //    Clamp minimum baseline ke 0.25 agar threshold tidak terlalu ketat
    //    → masalah sebelumnya: baseline 0.278 (terlalu rendah) karena
    //      frame setengah merem ikut kalkulasi → threshold 0.167 terlalu ketat
    //
    // 2. Threshold CLOSE: 75% baseline (naik dari 60%)
    //    → contoh: baseline 0.30 → threshold 0.225 (jauh lebih toleran)
    //    → sebelumnya: 60% × 0.278 = 0.167 → EAR 0.272 tidak terdeteksi tutup
    //
    // 3. Threshold OPEN: 88% baseline (naik dari 72%)
    //
    // 4. Fallback absolut: EAR < 0.18 = paksa deteksi tutup
    //    → antisipasi pencahayaan gelap / baseline meleset
    // ─────────────────────────────────────────────────────────────────
    const processBlink = (positions: faceapi.Point[]) => {
      const leftEye  = positions.slice(36, 42)
      const rightEye = positions.slice(42, 48)

      if (leftEye.length < 6 || rightEye.length < 6) return

      const earL = calcEAR(leftEye)
      const earR = calcEAR(rightEye)
      const ear  = (earL + earR) / 2

      // ── Baseline: hanya frame dengan EAR > 0.22 (mata benar-benar terbuka) ──
      if (!earReady) {
        if (ear > 0.22 && ear < 0.45) {
          earBuf.push(ear)
          blinkBaselineFrames.value = earBuf.length
          if (earBuf.length >= 30) {
            earBase = earBuf.reduce((a, b) => a + b, 0) / earBuf.length
            // Clamp minimum ke 0.25 agar threshold tidak terlalu ketat
            if (earBase < 0.25) earBase = 0.25
            earReady = true
            blinkBaselineReady.value = true
          }
        }
        return
      }

      // ── Threshold adaptif — lebih toleran ────────────────────────
      const EAR_CLOSE = earBase * 0.75  // 75% baseline (naik dari 60%)
      const EAR_OPEN  = earBase * 0.88  // 88% baseline (naik dari 72%)

      // Fallback absolut: EAR di bawah 0.18 = pasti merem
      const isDefinitelyClosed = ear < 0.18
      const isBelowClose       = ear < EAR_CLOSE

      // ── State machine: open → closed → open = 1 kedipan ──────────
      if (eyeIsOpen && (isBelowClose || isDefinitelyClosed)) {
        eyeIsOpen            = false
        blinkEyeClosed.value = true

      } else if (!eyeIsOpen && ear > EAR_OPEN && !isDefinitelyClosed) {
        eyeIsOpen            = true
        blinkEyeClosed.value = false
        blinkCnt++
        onChallengeSuccess()
      }
    }

    // ── DRAW OVERLAY ──────────────────────────────────────────────────
    const drawOverlay = (detection: any) => {
      const canvas = overlayCanvas.value
      const video  = videoEl.value
      if (!canvas || !video) return
      canvas.width  = video.videoWidth
      canvas.height = video.videoHeight
      const ctx = canvas.getContext('2d')!
      ctx.clearRect(0, 0, canvas.width, canvas.height)
      if (!detection) return
      const box   = detection.detection.box
      const color = faceStatus.value === 'success'       ? '#17c653'
                  : faceStatus.value === 'unknown'        ? '#ff6b6b'
                  : faceStatus.value === 'liveness'       ? '#ffa500'
                  : faceStatus.value === 'liveness_fail'  ? '#ff6b6b'
                  : '#1b84ff'
      ctx.strokeStyle = color; ctx.lineWidth = 2.5; ctx.shadowColor = color; ctx.shadowBlur = 8
      ctx.strokeRect(box.x, box.y, box.width, box.height)
    }

    // ── PROSES LOGIN ──────────────────────────────────────────────────
    const processLogin = async (profile: any) => {
      try {
        const loginRes = await publicHttp.post('/face/login', { user_id: profile.user_id })
        const userData = loginRes.data
        authStore.setAuth(userData)
        const http = authHttp(userData.api_token)
        const todayRes = await http.get('/attendance/today')
        const today = todayRes.data?.data

        let msg = '', type = 'check-in'
        if (!today || !today.check_in_time) {
          const r = await http.post('/attendance/check-in', {}); msg = r.data?.message ?? 'Check-in berhasil'; type = 'check-in'
        } else if (!today.check_out_time) {
          const r = await http.post('/attendance/check-out', {}); msg = r.data?.message ?? 'Check-out berhasil'; type = 'check-out'
        } else { msg = 'Absensi hari ini sudah lengkap ✅'; type = 'done' }

        recognizedUser.value   = { name: profile.name, avatar: profile.avatar }
        recognizedName.value   = profile.name
        attendanceResult.value = { message: msg, type }
        faceStatus.value       = 'success'
        startClock()
        setTimeout(() => { router.push({ name: 'user-dashboard' }) }, 3000)
      } catch (e: any) {
        faceError.value  = e?.response?.data?.message ?? 'Gagal memproses. Coba lagi.'
        successLock      = false
        livenessCompleted.value = false
        pendingProfile   = null
        faceStatus.value = 'detecting'
        setTimeout(() => { faceError.value = '' }, 3000)
      }
    }

    const startClock = () => {
      const tick = () => { currentTime.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }
      tick(); clockInterval = window.setInterval(tick, 1000)
    }

    const submitAdmin = async () => {
      if (!adminEmail.value || !adminPassword.value) { adminError.value = 'Email dan password wajib diisi.'; return }
      adminError.value = ''; adminLoading.value = true
      try {
        await authStore.adminLogin({ email: adminEmail.value, password: adminPassword.value })
        const errs = Object.values(authStore.errors)
        if (errs.length > 0) { adminError.value = errs[0] as string; authStore.errors = {} }
        else { router.push({ name: 'dashboard' }) }
      } catch { adminError.value = 'Login gagal. Periksa email dan password.' }
      finally { adminLoading.value = false }
    }

    onMounted(() => { init() })
    onUnmounted(() => { stopCamera() })

    return {
      mode, modelStatus, loadingMessage, errorMessage,
      faceStatus, faceError,
      recognizedUser, recognizedName, attendanceResult, currentTime,
      videoEl, overlayCanvas, faceProfiles,
      livenessActive, livenessCompleted, challenges, currentChallengeIndex,
      currentChallenge, challengeTimer, challengeFailed,
      blinkBaselineReady, blinkBaselineFrames, blinkEyeClosed,
      adminEmail, adminPassword, adminError, adminLoading, showPwd,
      submitAdmin, retryInit,
    }
  },
})
</script>

<style scoped>
.fr-wrap { width: 100%; animation: fr-fadeup 0.4s ease both; }
@keyframes fr-fadeup { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
.fr-face-panel { display: flex; flex-direction: column; gap: 16px; }
.fr-face-header { text-align: center; margin-bottom: 4px; }
.fr-logo-mark { width: 52px; height: 52px; border-radius: 14px; background: rgba(232,38,42,0.1); border: 1px solid rgba(232,38,42,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
.fr-title { font-size: 24px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; letter-spacing: -0.02em; }
.fr-subtitle { font-size: 13px; color: #55555e; margin: 0; }
.fr-status-box { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 24px 16px; border-radius: 12px; font-size: 13px; font-weight: 500; text-align: center; }
.fr-status-loading { background: rgba(27,132,255,0.08); color: #5aabff; border: 1px solid rgba(27,132,255,0.15); }
.fr-status-error   { background: rgba(255,107,107,0.08); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.15); }
.fr-retry-btn { margin-top: 4px; padding: 7px 18px; background: rgba(255,107,107,0.12); border: 1px solid rgba(255,107,107,0.3); border-radius: 8px; color: #ff6b6b; font-size: 12px; cursor: pointer; }
.fr-camera-section { display: flex; flex-direction: column; gap: 12px; }
.fr-viewfinder { position: relative; border-radius: 16px; overflow: hidden; aspect-ratio: 4/3; background: #0a0c10; border: 2px solid rgba(255,255,255,0.07); transition: border-color 0.3s, box-shadow 0.3s; }
.fr-viewfinder.success        { border-color: #17c653; box-shadow: 0 0 24px rgba(23,198,83,0.2); }
.fr-viewfinder.unknown        { border-color: #ff6b6b; box-shadow: 0 0 24px rgba(255,107,107,0.15); }
.fr-viewfinder.matching       { border-color: #ffa500; box-shadow: 0 0 24px rgba(255,165,0,0.15); }
.fr-viewfinder.detecting      { border-color: rgba(27,132,255,0.4); }
.fr-viewfinder.liveness       { border-color: #ffa500; box-shadow: 0 0 28px rgba(255,165,0,0.25); }
.fr-viewfinder.liveness_fail  { border-color: #ff6b6b; box-shadow: 0 0 24px rgba(255,107,107,0.25); }
.fr-video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); display: block; }
.fr-overlay-canvas { position: absolute; inset: 0; width: 100%; height: 100%; transform: scaleX(-1); pointer-events: none; }
.fr-corner { position: absolute; width: 22px; height: 22px; border-color: #e8262a; border-style: solid; border-width: 0; }
.fr-tl { top: 10px;    left: 10px;  border-top-width: 2.5px; border-left-width: 2.5px;  border-top-left-radius: 4px; }
.fr-tr { top: 10px;    right: 10px; border-top-width: 2.5px; border-right-width: 2.5px; border-top-right-radius: 4px; }
.fr-bl { bottom: 10px; left: 10px;  border-bottom-width: 2.5px; border-left-width: 2.5px;  border-bottom-left-radius: 4px; }
.fr-br { bottom: 10px; right: 10px; border-bottom-width: 2.5px; border-right-width: 2.5px; border-bottom-right-radius: 4px; }
@keyframes fr-scan { 0% { top: 0%; opacity: 1; } 100% { top: 100%; opacity: 0; } }
.fr-scanline { position: absolute; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, #1b84ff, transparent); box-shadow: 0 0 8px #1b84ff; animation: fr-scan 2s linear infinite; pointer-events: none; }
.fr-face-badge { position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); background: rgba(10,12,16,0.85); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 6px 14px; font-size: 12px; font-weight: 600; color: #aaaabc; white-space: nowrap; max-width: 90%; transition: all 0.3s; z-index: 10; }
.fr-face-badge.success       { color: #17c653; border-color: rgba(23,198,83,0.3); }
.fr-face-badge.unknown       { color: #ff6b6b; border-color: rgba(255,107,107,0.3); }
.fr-face-badge.matching      { color: #ffa500; border-color: rgba(255,165,0,0.3); }
.fr-face-badge.liveness      { color: #ffa500; border-color: rgba(255,165,0,0.3); }
.fr-face-badge.liveness_fail { color: #ff6b6b; border-color: rgba(255,107,107,0.3); }
.fr-liveness-overlay { position: absolute; inset: 0; z-index: 20; background: rgba(0,0,0,0.55); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; padding: 16px; }
.fr-liveness-card { background: rgba(15,17,24,0.95); border: 1.5px solid rgba(255,165,0,0.4); border-radius: 16px; padding: 20px 22px; text-align: center; max-width: 260px; width: 100%; box-shadow: 0 0 32px rgba(255,165,0,0.15); animation: fr-popIn 0.25s ease; }
@keyframes fr-popIn { from { opacity:0; transform:scale(0.9); } to { opacity:1; transform:scale(1); } }
@keyframes shake { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-8px)} 40%{transform:translateX(8px)} 60%{transform:translateX(-6px)} 80%{transform:translateX(6px)} }
.fr-liveness-card.shake { animation: shake 0.5s ease; border-color: rgba(255,107,107,0.6); }
.fr-liveness-step { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #55555e; margin-bottom: 10px; }
.fr-liveness-icon { font-size: 44px; line-height: 1; margin-bottom: 10px; }
.fr-liveness-text { font-size: 15px; font-weight: 700; color: #f0f0f5; margin-bottom: 10px; line-height: 1.35; }
.fr-liveness-blink-status { margin-bottom: 10px; font-size: 12px; font-weight: 600; }
.fr-blink-wait   { color: #55555e; }
.fr-blink-closed { color: #ffa500; background: rgba(255,165,0,0.12); border: 1px solid rgba(255,165,0,0.3); border-radius: 8px; padding: 4px 10px; animation: blink-pulse 0.4s ease infinite alternate; }
.fr-blink-open   { color: #aaaabc; }
@keyframes blink-pulse { from { opacity: 0.7; } to { opacity: 1; } }
.fr-liveness-bar-wrap { height: 5px; background: rgba(255,255,255,0.08); border-radius: 4px; overflow: hidden; margin-bottom: 8px; }
.fr-liveness-bar { height: 100%; background: #ffa500; border-radius: 4px; transition: width 0.1s linear; }
.fr-liveness-bar.danger { background: #ff6b6b; }
.fr-liveness-hint { font-size: 11px; color: #55555e; }
.fr-challenges-progress { display: flex; justify-content: center; gap: 10px; }
.fr-ch-dot { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1.5px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-size: 16px; transition: all 0.3s; }
.fr-ch-dot.done   { background: rgba(23,198,83,0.15); border-color: rgba(23,198,83,0.4); }
.fr-ch-dot.active { background: rgba(255,165,0,0.15); border-color: rgba(255,165,0,0.5); box-shadow: 0 0 10px rgba(255,165,0,0.2); transform: scale(1.15); }
.fr-liveness-success-badge { position: absolute; top: 12px; left: 50%; transform: translateX(-50%); background: rgba(23,198,83,0.9); color: #fff; font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 20px; white-space: nowrap; z-index: 10; }
.fr-result-card { display: flex; align-items: center; gap: 14px; padding: 16px; border-radius: 14px; animation: fr-slideup 0.4s ease both; }
@keyframes fr-slideup { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.fr-result-card.check-in  { background: rgba(23,198,83,0.08);  border: 1px solid rgba(23,198,83,0.2); }
.fr-result-card.check-out { background: rgba(27,132,255,0.08); border: 1px solid rgba(27,132,255,0.2); }
.fr-result-card.done      { background: rgba(255,165,0,0.08);  border: 1px solid rgba(255,165,0,0.2); }
.fr-result-avatar { flex-shrink: 0; }
.fr-result-img      { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; }
.fr-result-initials { width: 44px; height: 44px; border-radius: 50%; background: #e8262a; color: #fff; font-size: 18px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.fr-result-info { flex: 1; min-width: 0; }
.fr-result-name { font-size: 15px; font-weight: 700; color: #f0f0f5; }
.fr-result-msg  { font-size: 12px; color: #72727a; margin-top: 2px; }
.fr-result-time { font-size: 11px; color: #3a3a48; margin-top: 4px; font-variant-numeric: tabular-nums; }
.fr-result-icon { flex-shrink: 0; }
.fr-tips { text-align: center; font-size: 11.5px; color: #3a3a48; }
.fr-admin-link { text-align: center; padding-top: 4px; }
.fr-admin-toggle { display: inline-flex; align-items: center; gap: 6px; background: none; border: none; font-size: 12px; color: #3a3a48; cursor: pointer; padding: 6px 12px; border-radius: 20px; transition: color 0.15s, background 0.15s; }
.fr-admin-toggle:hover { color: #72727a; background: rgba(255,255,255,0.04); }
.fr-error-box { padding: 12px 14px; border-radius: 10px; background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.18); color: #ff6b6b; font-size: 12.5px; }
.fr-admin-panel { display: flex; flex-direction: column; gap: 16px; }
.fr-back-btn { display: inline-flex; align-items: center; gap: 6px; background: none; border: none; color: #55555e; font-size: 12.5px; cursor: pointer; padding: 0; transition: color 0.15s; }
.fr-back-btn:hover { color: #aaaabc; }
.fr-admin-header { text-align: center; }
.fr-admin-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(232,38,42,0.1); border: 1px solid rgba(232,38,42,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; color: #e8262a; }
.fr-admin-title { font-size: 20px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.fr-admin-sub   { font-size: 12px; color: #55555e; margin: 0; }
.fr-field { display: flex; flex-direction: column; gap: 7px; }
.fr-label { font-size: 10.5px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; }
.fr-inp-wrap { position: relative; }
.fr-inp-ico { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #3a3a48; display: flex; align-items: center; pointer-events: none; }
.fr-input { width: 100%; background: #181b22; border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px; color: #e2e2e8; font-size: 13.5px; padding: 12px 14px 12px 40px; outline: none; box-sizing: border-box; transition: border-color 0.18s, box-shadow 0.18s; }
.fr-input::placeholder { color: #3a3a48; }
.fr-input:focus { border-color: #1b84ff; box-shadow: 0 0 0 3px rgba(27,132,255,0.12); }
.fr-input-pwd { padding-right: 42px; }
.fr-eye-btn { position: absolute; right: 11px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #3a3a48; cursor: pointer; display: flex; align-items: center; transition: color 0.15s; }
.fr-eye-btn:hover { color: #72727a; }
.fr-submit-btn { width: 100%; background: #e8262a; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; padding: 13px; cursor: pointer; margin-top: 4px; transition: background 0.18s, transform 0.12s; }
.fr-submit-btn:hover:not(:disabled) { background: #ff3a3d; transform: translateY(-1px); }
.fr-submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.fr-btn-inner { display: flex; align-items: center; justify-content: center; gap: 8px; }
@keyframes fr-spin { to { transform: rotate(360deg); } }
.fr-spin { display: inline-block; width: 15px; height: 15px; border: 2px solid rgba(255,255,255,0.25); border-top-color: #fff; border-radius: 50%; animation: fr-spin 0.65s linear infinite; }
</style>