<template>
  <div class="co-wrap">
    <div class="co-header">
      <h2 class="co-title">Absen Pulang</h2>
      <p class="co-sub">{{ today }}</p>
    </div>

    <!-- Belum check-in -->
    <div v-if="pageState === 'no_checkin'" class="co-done-card">
      <div class="co-done-icon">
        <svg viewBox="0 0 80 80" fill="none" width="80" height="80">
          <circle cx="40" cy="40" r="36" stroke="#ffa500" stroke-width="4" fill="rgba(255,165,0,0.08)"/>
          <text x="40" y="54" text-anchor="middle" font-size="32" fill="#ffa500" font-weight="bold">!</text>
        </svg>
      </div>
      <h3>Kamu belum absen masuk hari ini</h3>
      <p>Absensi masuk dilakukan otomatis saat login menggunakan face recognition</p>
    </div>

    <!-- Sudah check-out -->
    <div v-else-if="pageState === 'done'" class="co-done-card co-done-success">
      <div class="co-done-icon">
        <svg viewBox="0 0 80 80" fill="none" width="80" height="80">
          <circle cx="40" cy="40" r="36" stroke="#17c653" stroke-width="4" fill="rgba(23,198,83,0.08)" class="co-circle-anim"/>
          <polyline points="24,42 35,53 56,30" stroke="#17c653" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" fill="none" class="co-check-anim"/>
        </svg>
      </div>
      <h3>Absen Pulang Berhasil!</h3>
      <p>Jam pulang: <strong>{{ checkoutTime }}</strong></p>
      <p v-if="checkoutMsg" class="co-status-msg">{{ checkoutMsg }}</p>
      <p class="co-logout-hint">Logging out dalam <strong>{{ countdown }}</strong> detik...</p>
    </div>

    <!-- Form face recognition checkout -->
    <div v-else-if="pageState === 'ready'" class="co-face-section">

      <!-- Loading model -->
      <div v-if="modelLoading" class="co-loading-box">
        <span class="co-spin"></span>
        <span>Memuat sistem pengenalan wajah...</span>
      </div>

      <div v-else>
        <!-- Info check-in -->
        <div class="co-info-bar">
          <span>Jam masuk: <strong>{{ checkinTime }}</strong></span>
          <span :class="checkinStatus === 'late' ? 'co-badge-late' : 'co-badge-ontime'">
            {{ checkinStatus === 'late' ? `Terlambat ${lateMinutes} mnt` : 'Tepat Waktu' }}
          </span>
        </div>

        <!-- Camera -->
        <div class="co-cam-wrap" :class="camStatus">
          <video ref="videoEl" autoplay muted playsinline class="co-cam-video" />
          <canvas ref="canvasEl" class="co-cam-canvas" />
          <div class="co-corner co-tl"></div>
          <div class="co-corner co-tr"></div>
          <div class="co-corner co-bl"></div>
          <div class="co-corner co-br"></div>
          <div class="co-cam-badge" :class="camStatus">
            <span v-if="camStatus === 'detecting'">🔍 Mendeteksi wajah...</span>
            <span v-else-if="camStatus === 'ready'">✅ Wajah terdeteksi - siap absen</span>
            <span v-else-if="camStatus === 'no_face'">👤 Arahkan wajah ke kamera</span>
            <span v-else-if="camStatus === 'processing'">⏳ Memproses absensi...</span>
            <span v-else-if="camStatus === 'success'">🎉 Wajah dikenali!</span>
            <span v-else-if="camStatus === 'failed'">❌ Wajah tidak dikenali</span>
          </div>
        </div>

        <div v-if="errorMsg" class="co-error">{{ errorMsg }}</div>

        <button
          class="co-btn-submit"
          @click="doCheckout"
          :disabled="(camStatus !== 'ready' && camStatus !== 'no_face') || processing"
        >
          <span v-if="!processing">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Absen Pulang via Wajah
          </span>
          <span v-else class="co-loading-inner"><span class="co-spin-sm"></span> Memproses...</span>
        </button>

        <p class="co-hint">Atau posisikan wajah — sistem akan otomatis mendeteksi</p>
      </div>
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import ApiService from '@/core/services/ApiService'
import * as faceapi from 'face-api.js'
import axios from 'axios'

const MODEL_URL  = '/models'
const API_BASE   = (import.meta.env.VITE_APP_API_URL as string || '/api').replace(/\/$/, '')
const THRESHOLD  = 0.45

export default defineComponent({
  name: 'CheckOut',
  setup() {
    const authStore = useAuthStore()
    const router    = useRouter()

    const today = new Date().toLocaleDateString('id-ID', {
      weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    })

    // Page states: loading | no_checkin | ready | done
    const pageState    = ref<'loading' | 'no_checkin' | 'ready' | 'done'>('loading')
    const checkinTime  = ref('')
    const checkinStatus = ref('')
    const lateMinutes  = ref(0)
    const checkoutTime = ref('')
    const checkoutMsg  = ref('')
    const countdown    = ref(5)
    const errorMsg     = ref('')
    const processing   = ref(false)
    const modelLoading = ref(true)

    // Camera
    const videoEl  = ref<HTMLVideoElement | null>(null)
    const canvasEl = ref<HTMLCanvasElement | null>(null)
    const camStatus = ref<'detecting' | 'ready' | 'no_face' | 'processing' | 'success' | 'failed'>('detecting')

    let mediaStream    : MediaStream | null = null
    let detectInterval : number | null      = null
    let autoTimer      : number | null      = null
    let modelsLoaded   = false
    let faceProfiles   : { user_id: number; name: string; descriptor: number[] }[] = []

    // Cek status absensi hari ini
    const checkTodayStatus = async () => {
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('attendance/today')
        const att = data.data

        if (!att || !att.check_in_time) {
          pageState.value = 'no_checkin'
          return
        }
        if (att.check_out_time) {
          // Sudah check-out
          checkoutTime.value = att.check_out_time
          pageState.value    = 'done'
          startCountdown()
          return
        }

        // Belum check-out, siap untuk face recognition
        checkinTime.value   = att.check_in_time
        checkinStatus.value = att.status
        lateMinutes.value   = att.late_minutes ?? 0
        pageState.value     = 'ready'

        await initFace()
      } catch (_) {
        pageState.value = 'no_checkin'
      }
    }

    // Init face recognition
    const initFace = async () => {
      modelLoading.value = true
      try {
        if (!modelsLoaded) {
          await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
          ])
          modelsLoaded = true
        }

        // Load face profiles
        const { data } = await axios.get(`${API_BASE}/face/profiles`)
        faceProfiles = data.data ?? []

        modelLoading.value = false

        await startCamera()
      } catch (e) {
        modelLoading.value = false
        errorMsg.value = 'Gagal memuat sistem pengenalan wajah.'
      }
    }

    const startCamera = async () => {
      try {
        mediaStream = await navigator.mediaDevices.getUserMedia({
          video: { width: 480, height: 360, facingMode: 'user' }
        })

        // Poll sampai videoEl tersedia
        let waited = 0
        while (!videoEl.value && waited < 3000) {
          await new Promise(r => setTimeout(r, 50))
          waited += 50
        }
        if (!videoEl.value) { errorMsg.value = 'Kamera tidak bisa dimuat.'; return }

        videoEl.value.srcObject = mediaStream
        await new Promise<void>(res => {
          videoEl.value!.onloadedmetadata = () => res()
          setTimeout(res, 3000)
        })
        await videoEl.value.play()
        camStatus.value = 'detecting'
        startDetectLoop()
      } catch (e: any) {
        errorMsg.value = 'Gagal mengakses kamera: ' + (e?.message ?? '')
      }
    }

    const startDetectLoop = () => {
      if (detectInterval) clearInterval(detectInterval)
      detectInterval = window.setInterval(async () => {
        if (!videoEl.value || videoEl.value.readyState < 2) return
        if (camStatus.value === 'processing' || camStatus.value === 'success') return

        const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })
        const det  = await faceapi.detectSingleFace(videoEl.value, opts).withFaceLandmarks(true)

        camStatus.value = det ? 'ready' : 'no_face'

        // Draw bounding box
        const canvas = canvasEl.value
        const video  = videoEl.value
        if (canvas && video) {
          canvas.width  = video.videoWidth
          canvas.height = video.videoHeight
          const ctx = canvas.getContext('2d')!
          ctx.clearRect(0, 0, canvas.width, canvas.height)
          if (det) {
            const box = det.detection.box
            ctx.strokeStyle = '#17c653'; ctx.lineWidth = 2.5
            ctx.shadowColor = '#17c653'; ctx.shadowBlur = 8
            ctx.strokeRect(box.x, box.y, box.width, box.height)
          }
        }

        // Auto checkout setelah wajah stabil 1.5 detik
        if (det && !autoTimer && !processing.value) {
          autoTimer = window.setTimeout(() => {
            autoTimer = null
            if (camStatus.value === 'ready') doCheckout()
          }, 1500)
        }
        if (!det && autoTimer) { clearTimeout(autoTimer); autoTimer = null }
      }, 500)
    }

    const euclidean = (a: number[], b: number[]): number => {
      let sum = 0
      for (let i = 0; i < a.length; i++) sum += (a[i] - b[i]) ** 2
      return Math.sqrt(sum)
    }

    // Lakukan checkout via face recognition
    const doCheckout = async () => {
      if (!videoEl.value || processing.value) return
      processing.value = true
      camStatus.value  = 'processing'
      errorMsg.value   = ''

      try {
        const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })
        const det  = await faceapi
          .detectSingleFace(videoEl.value, opts)
          .withFaceLandmarks(true)
          .withFaceDescriptor()

        if (!det) {
          camStatus.value  = 'no_face'
          errorMsg.value   = 'Wajah tidak terdeteksi. Coba lagi.'
          processing.value = false
          return
        }

        const descriptor = Array.from(det.descriptor)

        // Cari wajah paling mirip
        let bestMatch: typeof faceProfiles[0] | null = null
        let bestDist = Infinity
        for (const profile of faceProfiles) {
          const dist = euclidean(descriptor, profile.descriptor)
          if (dist < bestDist) { bestDist = dist; bestMatch = profile }
        }

        // Validasi: pastikan wajah yang dikenali adalah user yang sedang login
        if (!bestMatch || bestDist > THRESHOLD) {
          camStatus.value  = 'failed'
          errorMsg.value   = 'Wajah tidak dikenali. Pastikan pencahayaan cukup.'
          processing.value = false
          setTimeout(() => { camStatus.value = 'detecting'; errorMsg.value = '' }, 2500)
          return
        }

        if (bestMatch.user_id !== authStore.user.id) {
          camStatus.value  = 'failed'
          errorMsg.value   = 'Wajah tidak cocok dengan akun yang sedang login.'
          processing.value = false
          setTimeout(() => { camStatus.value = 'detecting'; errorMsg.value = '' }, 2500)
          return
        }

        // Wajah valid — lakukan check-out
        camStatus.value = 'success'
        ApiService.setHeader()
        const { data } = await ApiService.post('attendance/check-out', {})

        checkoutTime.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
        checkoutMsg.value  = data.message ?? 'Check-out berhasil'

        // Stop kamera
        stopCamera()

        // Tampilkan halaman sukses lalu logout
        pageState.value = 'done'
        startCountdown()

      } catch (e: any) {
        camStatus.value  = 'failed'
        errorMsg.value   = e?.response?.data?.message ?? 'Gagal absen pulang. Coba lagi.'
        processing.value = false
        setTimeout(() => { camStatus.value = 'detecting' }, 2000)
      }
    }

    const stopCamera = () => {
      if (detectInterval) { clearInterval(detectInterval); detectInterval = null }
      if (autoTimer)      { clearTimeout(autoTimer);       autoTimer      = null }
      mediaStream?.getTracks().forEach(t => t.stop())
      mediaStream = null
    }

    // Countdown lalu logout
    const startCountdown = () => {
      countdown.value = 5
      const timer = setInterval(() => {
        countdown.value--
        if (countdown.value <= 0) {
          clearInterval(timer)
          authStore.logout()
          router.push({ name: 'sign-in' })
        }
      }, 1000)
    }

    onMounted(() => { checkTodayStatus() })
    onUnmounted(() => { stopCamera() })

    return {
      today, pageState, checkinTime, checkinStatus, lateMinutes,
      checkoutTime, checkoutMsg, countdown,
      modelLoading, camStatus, errorMsg, processing,
      videoEl, canvasEl, doCheckout,
    }
  }
})
</script>

<style scoped>
.co-wrap { max-width: 520px; margin: 0 auto; padding: 24px 16px; }
.co-header { margin-bottom: 24px; }
.co-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.co-sub { font-size: 13px; color: #55555e; margin: 0; }

/* Done card */
.co-done-card { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 32px; text-align: center; }
.co-done-success { border-color: rgba(23,198,83,0.2); }
.co-done-card h3 { color: #f0f0f5; font-size: 16px; margin: 0 0 12px; }
.co-done-card p { color: #55555e; font-size: 14px; margin: 6px 0; }
.co-done-card strong { color: #f0f0f5; }
.co-done-icon { display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
.co-status-msg { color: #17c653 !important; font-weight: 600; }
.co-logout-hint { color: #3a3a48 !important; font-size: 12px !important; margin-top: 12px !important; }

@keyframes draw-circle { to { stroke-dashoffset: 0; } }
@keyframes draw-check  { to { stroke-dashoffset: 0; } }
.co-circle-anim { stroke-dasharray: 226; stroke-dashoffset: 226; animation: draw-circle 0.5s ease forwards; }
.co-check-anim  { stroke-dasharray: 50;  stroke-dashoffset: 50;  animation: draw-check  0.4s ease 0.4s forwards; }

/* Loading */
.co-loading-box { display: flex; align-items: center; gap: 10px; color: #55555e; padding: 32px 0; justify-content: center; font-size: 13px; }

/* Info bar */
.co-info-bar { display: flex; justify-content: space-between; align-items: center; background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px; color: #aaaabc; }
.co-info-bar strong { color: #f0f0f5; }
.co-badge-late   { background: rgba(255,107,107,0.15); color: #ff6b6b; padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.co-badge-ontime { background: rgba(23,198,83,0.15);  color: #17c653; padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

/* Camera */
.co-cam-wrap { position: relative; width: 100%; aspect-ratio: 4/3; background: #0a0c10; border-radius: 14px; overflow: hidden; border: 2px solid rgba(255,255,255,0.07); margin-bottom: 14px; transition: border-color 0.3s, box-shadow 0.3s; }
.co-cam-wrap.ready     { border-color: #17c653; box-shadow: 0 0 20px rgba(23,198,83,0.18); }
.co-cam-wrap.success   { border-color: #17c653; box-shadow: 0 0 30px rgba(23,198,83,0.3); }
.co-cam-wrap.failed    { border-color: #ff6b6b; box-shadow: 0 0 20px rgba(255,107,107,0.18); }
.co-cam-wrap.no_face   { border-color: #ffa500; }
.co-cam-wrap.processing { border-color: #1b84ff; box-shadow: 0 0 20px rgba(27,132,255,0.2); }
.co-cam-video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); display: block; }
.co-cam-canvas { position: absolute; inset: 0; width: 100%; height: 100%; transform: scaleX(-1); pointer-events: none; }

.co-corner { position: absolute; width: 20px; height: 20px; border-color: #e8262a; border-style: solid; border-width: 0; }
.co-tl { top: 8px;    left: 8px;  border-top-width: 2px;    border-left-width: 2px;  border-top-left-radius: 4px; }
.co-tr { top: 8px;    right: 8px; border-top-width: 2px;    border-right-width: 2px; border-top-right-radius: 4px; }
.co-bl { bottom: 8px; left: 8px;  border-bottom-width: 2px; border-left-width: 2px;  border-bottom-left-radius: 4px; }
.co-br { bottom: 8px; right: 8px; border-bottom-width: 2px; border-right-width: 2px; border-bottom-right-radius: 4px; }

.co-cam-badge { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); background: rgba(10,12,16,0.85); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,0.07); border-radius: 20px; padding: 5px 14px; font-size: 12px; font-weight: 600; color: #aaaabc; white-space: nowrap; transition: all 0.2s; }
.co-cam-badge.ready     { color: #17c653; border-color: rgba(23,198,83,0.3); }
.co-cam-badge.success   { color: #17c653; border-color: rgba(23,198,83,0.4); }
.co-cam-badge.no_face   { color: #ffa500; border-color: rgba(255,165,0,0.3); }
.co-cam-badge.failed    { color: #ff6b6b; border-color: rgba(255,107,107,0.3); }
.co-cam-badge.processing { color: #1b84ff; border-color: rgba(27,132,255,0.3); }

.co-error { padding: 11px 14px; border-radius: 10px; background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.18); color: #ff6b6b; font-size: 12.5px; margin-bottom: 14px; }

.co-btn-submit { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; background: #e8262a; color: #fff; border: none; border-radius: 12px; padding: 14px; font-size: 14px; font-weight: 700; cursor: pointer; margin-bottom: 10px; transition: background 0.15s, transform 0.1s; }
.co-btn-submit:hover:not(:disabled) { background: #ff3a3d; transform: translateY(-1px); }
.co-btn-submit:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }

.co-loading-inner { display: flex; align-items: center; gap: 8px; }
.co-hint { text-align: center; font-size: 11.5px; color: #3a3a48; margin: 0; }

@keyframes co-spin { to { transform: rotate(360deg); } }
.co-spin    { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.15); border-top-color: #aaaabc; border-radius: 50%; animation: co-spin 0.65s linear infinite; }
.co-spin-sm { display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.25); border-top-color: #fff; border-radius: 50%; animation: co-spin 0.65s linear infinite; }
</style>