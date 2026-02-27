<template>
  <div class="ci-wrap">

    <!-- Header -->
    <div class="ci-header">
      <h2 class="ci-title">Absen Masuk</h2>
      <p class="ci-sub">{{ today }}</p>
    </div>

    <!-- Status card jika sudah absen -->
    <div v-if="alreadyCheckedIn" class="ci-done-card">
      <div class="ci-done-icon">
        <svg class="ci-check-svg" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle class="ci-check-circle" cx="40" cy="40" r="36" stroke="#17c653" stroke-width="4" fill="rgba(23,198,83,0.08)"/>
          <polyline class="ci-check-mark" points="24,42 35,53 56,30" stroke="#17c653" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
      </div>
      <h3>Kamu sudah absen masuk hari ini</h3>
      <p>Jam masuk: <strong>{{ todayAttendance?.check_in_time }}</strong></p>
      <p>Status:
        <span :class="todayAttendance?.status === 'late' ? 'badge-late' : 'badge-ontime'">
          {{ todayAttendance?.status === 'late' ? `Terlambat ${todayAttendance?.late_minutes} menit` : 'Tepat Waktu' }}
        </span>
      </p>
      <div v-if="!alreadyCheckedOut" class="ci-goto-checkout">
        <router-link to="/user/attendance/check-out" class="ci-btn-red">
          Lanjut Absen Pulang →
        </router-link>
      </div>
      <div v-else class="ci-done-both">
        <p>Jam pulang: <strong>{{ todayAttendance?.check_out_time }}</strong></p>
        <p>Kamu sudah selesai absen hari ini 🎉</p>
      </div>
    </div>

    <!-- Form absen -->
    <div v-else class="ci-body">

      <!-- Kamera -->
      <div class="ci-camera-section">
        <div class="ci-camera-box" :class="{ 'has-preview': capturedImage }">
          <video v-show="streaming && !capturedImage" ref="videoEl" autoplay playsinline class="ci-video" />
          <img  v-if="capturedImage" :src="capturedImage" class="ci-preview" />
          <div v-if="!streaming && !capturedImage" class="ci-placeholder">
            <div class="ci-placeholder-icon">📷</div>
            <p>Klik "Buka Kamera" untuk mulai</p>
          </div>
        </div>

        <canvas ref="canvasEl" style="display:none" />

        <!-- Tombol kamera -->
        <div class="ci-camera-actions">
          <button v-if="!streaming && !capturedImage" @click="startCamera" class="ci-btn-secondary">
            📷 Buka Kamera
          </button>
          <button v-if="streaming && !capturedImage" @click="capturePhoto" class="ci-btn-primary">
            📸 Ambil Foto
          </button>
          <button v-if="capturedImage" @click="retake" class="ci-btn-secondary">
            🔄 Ulangi Foto
          </button>
        </div>
      </div>

      <!-- Tombol submit -->
      <button
        v-if="capturedImage"
        @click="submitCheckIn"
        :disabled="loading"
        class="ci-btn-submit"
      >
        <span v-if="!loading">✓ Absen Masuk Sekarang</span>
        <span v-else class="ci-loading">
          <span class="ci-spinner"></span> Menyimpan...
        </span>
      </button>

      <!-- Error message -->
      <div v-if="errorMsg" class="ci-error">{{ errorMsg }}</div>

    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, onUnmounted } from 'vue'
import ApiService from '@/core/services/ApiService'
import { useAuthStore } from '@/stores/auth'

export default defineComponent({
  name: 'CheckIn',
  setup() {
    const authStore    = useAuthStore()
    const videoEl      = ref<HTMLVideoElement | null>(null)
    const canvasEl     = ref<HTMLCanvasElement | null>(null)
    const capturedImage = ref<string | null>(null)
    const streaming    = ref(false)
    const loading      = ref(false)
    const errorMsg     = ref('')
    const alreadyCheckedIn  = ref(false)
    const alreadyCheckedOut = ref(false)
    const todayAttendance   = ref<any>(null)

    let mediaStream: MediaStream | null = null

    // Format tanggal hari ini
    const today = new Date().toLocaleDateString('id-ID', {
      weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    })

    // Cek status absen hari ini
    const checkTodayStatus = async () => {
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('attendance/today')
        if (data.data) {
          todayAttendance.value   = data.data
          alreadyCheckedIn.value  = !!data.data.check_in_time
          alreadyCheckedOut.value = !!data.data.check_out_time
        }
      } catch (_) {}
    }

    onMounted(() => { checkTodayStatus() })

    // Buka kamera
    const startCamera = async () => {
      errorMsg.value = ''
      try {
        mediaStream = await navigator.mediaDevices.getUserMedia({
          video: { width: 640, height: 480, facingMode: 'user' }
        })
        if (videoEl.value) {
          videoEl.value.srcObject = mediaStream
          streaming.value = true
        }
      } catch {
        errorMsg.value = 'Kamera tidak bisa diakses. Pastikan izin kamera sudah diaktifkan di browser.'
      }
    }

    // Capture foto
    const capturePhoto = () => {
      if (!videoEl.value || !canvasEl.value) return
      const canvas = canvasEl.value
      canvas.width  = videoEl.value.videoWidth
      canvas.height = videoEl.value.videoHeight
      canvas.getContext('2d')!.drawImage(videoEl.value, 0, 0)
      capturedImage.value = canvas.toDataURL('image/jpeg', 0.85)
      mediaStream?.getTracks().forEach(t => t.stop())
      streaming.value = false
    }

    // Ulangi foto
    const retake = () => {
      capturedImage.value = null
      startCamera()
    }

    // Convert base64 → File
    const dataURLtoFile = (dataUrl: string, filename: string): File => {
      const [header, data] = dataUrl.split(',')
      const mime   = header.match(/:(.*?);/)![1]
      const binary = atob(data)
      const arr    = new Uint8Array(binary.length)
      for (let i = 0; i < binary.length; i++) arr[i] = binary.charCodeAt(i)
      return new File([arr], filename, { type: mime })
    }

    // Submit check-in
    const submitCheckIn = async () => {
      if (!capturedImage.value) return
      errorMsg.value = ''

      const file = dataURLtoFile(capturedImage.value, 'check_in.jpg')
      if (file.size > 2 * 1024 * 1024) {
        errorMsg.value = 'Foto terlalu besar. Maksimal 2MB.'
        return
      }

      const formData = new FormData()
      formData.append('photo', file)

      loading.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.post('attendance/check-in', formData)
        todayAttendance.value  = data.data
        alreadyCheckedIn.value = true
      } catch (err: any) {
        errorMsg.value = err?.response?.data?.message ?? 'Gagal absen. Coba lagi.'
      } finally {
        loading.value = false
      }
    }

    onUnmounted(() => {
      mediaStream?.getTracks().forEach(t => t.stop())
    })

    return {
      videoEl, canvasEl, capturedImage,
      streaming, loading, errorMsg, today,
      alreadyCheckedIn, alreadyCheckedOut, todayAttendance,
      startCamera, capturePhoto, retake, submitCheckIn,
    }
  }
})
</script>

<style scoped>
.ci-wrap { max-width: 520px; margin: 0 auto; padding: 24px 16px; }

.ci-header { margin-bottom: 24px; }
.ci-title  { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.ci-sub    { font-size: 13px; color: #55555e; margin: 0; }

/* Done card */
.ci-done-card {
  background: #15171e;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 16px;
  padding: 32px;
  text-align: center;
}

/* SVG Check Icon */
.ci-done-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}
.ci-check-svg {
  width: 80px;
  height: 80px;
  filter: drop-shadow(0 0 16px rgba(23, 198, 83, 0.35));
}
.ci-check-circle {
  stroke-dasharray: 226;
  stroke-dashoffset: 226;
  animation: draw-circle 0.5s ease forwards;
}
.ci-check-mark {
  stroke-dasharray: 50;
  stroke-dashoffset: 50;
  animation: draw-check 0.4s ease 0.4s forwards;
}
@keyframes draw-circle {
  to { stroke-dashoffset: 0; }
}
@keyframes draw-check {
  to { stroke-dashoffset: 0; }
}

.ci-done-card h3 { color: #f0f0f5; font-size: 16px; margin-bottom: 12px; }
.ci-done-card p  { color: #55555e; font-size: 14px; margin: 6px 0; }
.ci-done-card strong { color: #f0f0f5; }
.ci-goto-checkout { margin-top: 20px; }
.ci-done-both { margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.06); }

.badge-late   { background: rgba(255,107,107,0.15); color: #ff6b6b; padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.badge-ontime { background: rgba(23,198,83,0.15);  color: #17c653; padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

/* Camera */
.ci-camera-section { margin-bottom: 16px; }
.ci-camera-box {
  background: #0d0f14;
  border: 1.5px solid rgba(255,255,255,0.08);
  border-radius: 14px;
  overflow: hidden;
  aspect-ratio: 4/3;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
}
.ci-video   { width: 100%; height: 100%; object-fit: cover; display: block; }
.ci-preview { width: 100%; height: 100%; object-fit: cover; display: block; }
.ci-placeholder { text-align: center; color: #3a3a48; }
.ci-placeholder-icon { font-size: 48px; margin-bottom: 8px; }
.ci-placeholder p { font-size: 13px; }

.ci-camera-actions { display: flex; gap: 10px; flex-wrap: wrap; }

/* Buttons */
.ci-btn-red {
  display: inline-block;
  background: #e8262a; color: #fff;
  border: none; border-radius: 10px;
  padding: 11px 22px; font-size: 14px; font-weight: 700;
  cursor: pointer; text-decoration: none;
  transition: background 0.15s;
}
.ci-btn-red:hover { background: #ff3a3d; }

.ci-btn-primary {
  background: #1b84ff; color: #fff;
  border: none; border-radius: 10px;
  padding: 11px 22px; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: background 0.15s;
}
.ci-btn-primary:hover { background: #3a9dff; }

.ci-btn-secondary {
  background: #181b22;
  border: 1.5px solid rgba(255,255,255,0.10);
  color: #aaaabc; border-radius: 10px;
  padding: 11px 22px; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: all 0.15s;
}
.ci-btn-secondary:hover { border-color: rgba(255,255,255,0.2); color: #e2e2e8; }

.ci-btn-submit {
  width: 100%; background: #e8262a;
  color: #fff; border: none; border-radius: 12px;
  padding: 14px; font-size: 15px; font-weight: 700;
  cursor: pointer; margin-top: 8px;
  transition: background 0.15s, transform 0.1s;
}
.ci-btn-submit:hover:not(:disabled) { background: #ff3a3d; transform: translateY(-1px); }
.ci-btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }

.ci-loading { display: flex; align-items: center; justify-content: center; gap: 8px; }
@keyframes spin { to { transform: rotate(360deg); } }
.ci-spinner {
  width: 16px; height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff; border-radius: 50%;
  animation: spin 0.6s linear infinite;
  display: inline-block;
}

.ci-error {
  margin-top: 12px; padding: 12px 16px;
  background: rgba(255,107,107,0.1);
  border: 1px solid rgba(255,107,107,0.2);
  border-radius: 10px; color: #ff6b6b;
  font-size: 13px;
}
</style>