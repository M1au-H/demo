<template>
  <div class="fe-wrap">
    <div class="fe-header">
      <div>
        <h2 class="fe-title">Face Management</h2>
        <p class="fe-sub">Daftarkan dan kelola wajah pegawai</p>
      </div>
    </div>

    <!-- Tabel pegawai -->
    <div class="fe-table-wrap">
      <div v-if="loading" class="fe-loading">Memuat data pegawai...</div>
      <table v-else class="fe-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Pegawai</th>
            <th>Jabatan</th>
            <th>Status Wajah</th>
            <th>Terdaftar</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(emp, i) in employees" :key="emp.id">
            <td class="fe-num">{{ i + 1 }}</td>
            <td>
              <div class="fe-user">
                <div class="fe-avatar">
                  <img v-if="emp.avatar" :src="`/storage/${emp.avatar}`" />
                  <span v-else>{{ emp.name?.charAt(0) }}</span>
                </div>
                <div>
                  <div class="fe-name">{{ emp.name }}</div>
                  <div class="fe-email">{{ emp.email }}</div>
                </div>
              </div>
            </td>
            <td class="fe-pos">{{ emp.position ?? '-' }}</td>
            <td>
              <span :class="['fe-status', emp.has_face ? 'fe-status-ok' : 'fe-status-none']">
                {{ emp.has_face ? '✅ Terdaftar' : '❌ Belum' }}
              </span>
            </td>
            <td class="fe-date">{{ emp.registered_at ? formatDate(emp.registered_at) : '-' }}</td>
            <td>
              <div class="fe-actions">
                <button @click="openEnroll(emp)" class="fe-btn-enroll">
                  {{ emp.has_face ? '🔄 Update' : '📷 Daftarkan' }}
                </button>
                <button v-if="emp.has_face" @click="deleteFace(emp)" class="fe-btn-delete">🗑️ Hapus</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Enroll Wajah -->
    <div v-if="showEnroll" class="fe-modal-overlay" @click.self="closeEnroll">
      <div class="fe-modal">
        <div class="fe-modal-header">
          <div>
            <h3 class="fe-modal-title">📷 Daftarkan Wajah</h3>
            <p class="fe-modal-sub">{{ selectedEmp?.name }}</p>
          </div>
          <button @click="closeEnroll" class="fe-btn-x">✕</button>
        </div>

        <!-- Status model loading -->
        <div v-if="modelLoading" class="fe-model-status">
          <div class="fe-spinner"></div>
          <p>Memuat model AI... {{ modelProgress }}</p>
        </div>

        <div v-else>
          <!-- Video kamera -->
          <div class="fe-cam-wrap">
            <video ref="videoRef" class="fe-video" autoplay muted playsinline></video>
            <canvas ref="canvasRef" class="fe-canvas"></canvas>
            <div v-if="faceDetected" class="fe-face-indicator">✅ Wajah terdeteksi</div>
            <div v-else class="fe-face-indicator fe-no-face">❌ Arahkan wajah ke kamera</div>
          </div>

          <div v-if="enrollMsg" :class="['fe-msg', enrollSuccess ? 'fe-msg-ok' : 'fe-msg-err']">
            {{ enrollMsg }}
          </div>

          <div class="fe-modal-actions">
            <button @click="closeEnroll" class="fe-btn-cancel">Batal</button>
            <button @click="captureAndEnroll" :disabled="!faceDetected || enrollLoading" class="fe-btn-save">
              {{ enrollLoading ? 'Menyimpan...' : '✅ Simpan Wajah' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, onUnmounted, watch } from 'vue'
import * as faceapi from 'face-api.js'
import ApiService from '@/core/services/ApiService'

export default defineComponent({
  name: 'FaceEnrollment',
  setup() {
    const employees   = ref<any[]>([])
    const loading     = ref(true)
    const showEnroll  = ref(false)
    const selectedEmp = ref<any>(null)
    const modelLoading = ref(true)
    const modelProgress = ref('0%')
    const enrollLoading = ref(false)
    const enrollMsg   = ref('')
    const enrollSuccess = ref(false)
    const faceDetected = ref(false)

    const videoRef  = ref<HTMLVideoElement | null>(null)
    const canvasRef = ref<HTMLCanvasElement | null>(null)
    let stream: MediaStream | null = null
    let detectionInterval: any = null
    let modelsLoaded = false

    const formatDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })

    const loadEmployees = async () => {
      loading.value = true
      try {
        ApiService.setHeader()
        const { data } = await ApiService.get('admin/face-profiles')
        employees.value = data.data
      } catch (_) {} finally { loading.value = false }
    }

    const loadModels = async () => {
      if (modelsLoaded) return
      modelLoading.value = true
      try {
        const MODEL_URL = '/models'
        modelProgress.value = 'Loading detector...'
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL)
        modelProgress.value = 'Loading landmarks...'
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL)
        modelProgress.value = 'Loading recognition...'
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        modelsLoaded = true
      } catch (e) {
        console.error('Model load error:', e)
      } finally {
        modelLoading.value = false
      }
    }

    const startCamera = async () => {
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } })
        if (videoRef.value) {
          videoRef.value.srcObject = stream
          await videoRef.value.play()
          startDetection()
        }
      } catch (e) {
        console.error('Camera error:', e)
      }
    }

    const startDetection = () => {
      detectionInterval = setInterval(async () => {
        if (!videoRef.value || !canvasRef.value) return
        const detection = await faceapi
          .detectSingleFace(videoRef.value, new faceapi.TinyFaceDetectorOptions())
          .withFaceLandmarks()
        
        const ctx = canvasRef.value.getContext('2d')
        if (ctx) ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height)
        
        if (detection) {
          faceDetected.value = true
          faceapi.matchDimensions(canvasRef.value, videoRef.value)
          const resized = faceapi.resizeResults(detection, { width: videoRef.value.videoWidth, height: videoRef.value.videoHeight })
          faceapi.draw.drawFaceLandmarks(canvasRef.value, resized)
        } else {
          faceDetected.value = false
        }
      }, 300)
    }

    const stopCamera = () => {
      clearInterval(detectionInterval)
      if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null }
    }

    const openEnroll = async (emp: any) => {
      selectedEmp.value = emp
      enrollMsg.value = ''
      showEnroll.value = true
      await loadModels()
      await startCamera()
    }

    const closeEnroll = () => {
      stopCamera()
      showEnroll.value = false
      selectedEmp.value = null
      faceDetected.value = false
    }

    const captureAndEnroll = async () => {
      if (!videoRef.value || !selectedEmp.value) return
      enrollLoading.value = true
      enrollMsg.value = ''
      try {
        const detection = await faceapi
          .detectSingleFace(videoRef.value, new faceapi.TinyFaceDetectorOptions())
          .withFaceLandmarks()
          .withFaceDescriptor()

        if (!detection) {
          enrollMsg.value = 'Wajah tidak terdeteksi. Coba lagi.'
          enrollSuccess.value = false
          return
        }

        const descriptor = Array.from(detection.descriptor)

        ApiService.setHeader()
        await ApiService.post('face/enroll', {
          user_id: selectedEmp.value.id,
          descriptor,
        })

        enrollMsg.value = `Wajah ${selectedEmp.value.name} berhasil didaftarkan!`
        enrollSuccess.value = true

        // Update status di tabel
        const emp = employees.value.find(e => e.id === selectedEmp.value.id)
        if (emp) { emp.has_face = true; emp.registered_at = new Date().toISOString() }

        setTimeout(() => closeEnroll(), 1500)
      } catch (err: any) {
        enrollMsg.value = err?.response?.data?.message ?? 'Gagal menyimpan wajah.'
        enrollSuccess.value = false
      } finally {
        enrollLoading.value = false
      }
    }

    const deleteFace = async (emp: any) => {
      if (!confirm(`Hapus data wajah ${emp.name}?`)) return
      try {
        ApiService.setHeader()
        await ApiService.delete(`face/delete/${emp.id}`)
        emp.has_face = false
        emp.registered_at = null
      } catch (err: any) {
        alert(err?.response?.data?.message ?? 'Gagal menghapus.')
      }
    }

    onMounted(loadEmployees)
    onUnmounted(stopCamera)

    return {
      employees, loading, showEnroll, selectedEmp, modelLoading, modelProgress,
      enrollLoading, enrollMsg, enrollSuccess, faceDetected,
      videoRef, canvasRef,
      formatDate, openEnroll, closeEnroll, captureAndEnroll, deleteFace,
    }
  }
})
</script>

<style scoped>
.fe-wrap { padding: 24px; }
.fe-header { margin-bottom: 24px; }
.fe-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.fe-sub { font-size: 13px; color: #55555e; margin: 0; }

.fe-table-wrap { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; overflow: hidden; }
.fe-table { width: 100%; border-collapse: collapse; }
.fe-table th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #55555e; border-bottom: 1px solid rgba(255,255,255,0.06); }
.fe-table td { padding: 14px 16px; font-size: 13px; color: #aaaabc; border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle; }
.fe-table tr:last-child td { border-bottom: none; }
.fe-num { color: #3a3a48; width: 40px; }
.fe-loading { padding: 32px; text-align: center; color: #55555e; }

.fe-user { display: flex; align-items: center; gap: 10px; }
.fe-avatar { width: 36px; height: 36px; border-radius: 50%; background: #e8262a; color: #fff; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
.fe-avatar img { width: 100%; height: 100%; object-fit: cover; }
.fe-name { font-size: 13px; font-weight: 600; color: #f0f0f5; }
.fe-email { font-size: 11px; color: #55555e; }
.fe-pos { color: #aaaabc; }
.fe-date { color: #55555e; font-size: 12px; }

.fe-status { padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.fe-status-ok { background: rgba(23,198,83,0.1); color: #17c653; }
.fe-status-none { background: rgba(255,107,107,0.1); color: #ff6b6b; }

.fe-actions { display: flex; gap: 6px; }
.fe-btn-enroll { background: rgba(27,132,255,0.1); color: #1b84ff; border: 1px solid rgba(27,132,255,0.2); border-radius: 6px; padding: 5px 12px; font-size: 12px; cursor: pointer; }
.fe-btn-delete { background: rgba(255,107,107,0.1); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.2); border-radius: 6px; padding: 5px 12px; font-size: 12px; cursor: pointer; }

/* Modal */
.fe-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 999; padding: 16px; }
.fe-modal { background: #15171e; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 28px; width: 100%; max-width: 520px; }
.fe-modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
.fe-modal-title { font-size: 17px; font-weight: 800; color: #f0f0f5; margin: 0; }
.fe-modal-sub { font-size: 13px; color: #1b84ff; margin: 4px 0 0; }
.fe-btn-x { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #aaa; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; }

.fe-model-status { text-align: center; padding: 40px 0; color: #aaaabc; }
.fe-spinner { width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.1); border-top-color: #1b84ff; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
@keyframes spin { to { transform: rotate(360deg); } }

.fe-cam-wrap { position: relative; width: 100%; aspect-ratio: 4/3; background: #000; border-radius: 12px; overflow: hidden; margin-bottom: 16px; }
.fe-video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
.fe-canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; transform: scaleX(-1); }
.fe-face-indicator { position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); background: rgba(23,198,83,0.9); color: #fff; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
.fe-no-face { background: rgba(255,107,107,0.9); }

.fe-msg { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }
.fe-msg-ok { background: rgba(23,198,83,0.1); color: #17c653; border: 1px solid rgba(23,198,83,0.2); }
.fe-msg-err { background: rgba(255,107,107,0.1); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.2); }

.fe-modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
.fe-btn-cancel { background: #181b22; border: 1.5px solid rgba(255,255,255,0.1); color: #aaaabc; border-radius: 10px; padding: 10px 20px; font-size: 13px; cursor: pointer; }
.fe-btn-save { background: #1b84ff; color: #fff; border: none; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 700; cursor: pointer; }
.fe-btn-save:disabled { opacity: 0.4; cursor: not-allowed; }
</style>