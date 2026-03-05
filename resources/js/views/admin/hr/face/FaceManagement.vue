<template>
  <div class="fm-wrap">

    <div class="fm-header">
      <div>
        <h2 class="fm-title">Face Management</h2>
        <p class="fm-sub">Kelola data wajah pegawai untuk absensi otomatis</p>
      </div>
      <div class="fm-header-stats">
        <div class="fm-stat">
          <span class="fm-stat-num">{{ enrolled }}</span>
          <span class="fm-stat-label">Terdaftar</span>
        </div>
        <div class="fm-stat orange">
          <span class="fm-stat-num">{{ notEnrolled }}</span>
          <span class="fm-stat-label">Belum Daftar</span>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="fm-loading">
      <span class="fm-spinner"></span> Memuat data pegawai...
    </div>

    <!-- Empty state -->
    <div v-else-if="employees.length === 0" class="fm-empty">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#3a3a48" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      <p>Belum ada pegawai terdaftar.</p>
    </div>

    <div v-else class="fm-grid">
      <div
        v-for="emp in employees"
        :key="emp.id"
        class="fm-card"
        :class="{ 'has-face': emp.has_face }"
      >
        <!-- Avatar -->
        <div class="fm-avatar-wrap">
          <img v-if="emp.avatar" :src="emp.avatar" class="fm-avatar" />
          <div v-else class="fm-avatar-fallback">{{ emp.name?.charAt(0)?.toUpperCase() }}</div>
          <div class="fm-face-badge" :class="emp.has_face ? 'registered' : 'empty'">
            <svg v-if="emp.has_face" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            <svg v-else width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </div>
        </div>

        <!-- Info -->
        <div class="fm-card-info">
          <div class="fm-emp-name">{{ emp.name }}</div>
          <div class="fm-emp-pos">{{ emp.position ?? 'Tanpa jabatan' }}</div>
          <div v-if="emp.has_face && emp.enrolled_at" class="fm-enrolled-at">
            Didaftarkan: {{ emp.enrolled_at }}
          </div>
          <div v-else class="fm-not-enrolled">Wajah belum terdaftar</div>
        </div>

        <!-- Actions -->
        <div class="fm-card-actions">
          <!-- Sudah terdaftar: hanya tampilkan Update & Hapus -->
          <template v-if="emp.has_face">
            <button class="fm-btn-enroll" @click="openEnroll(emp)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
              Update Wajah
            </button>
            <button class="fm-btn-delete" @click="confirmDelete(emp)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6M14 11v6"/>
              </svg>
              Hapus
            </button>
          </template>

          <!-- Belum terdaftar: tampilkan tombol Daftarkan -->
          <template v-else>
            <button class="fm-btn-enroll fm-btn-register" @click="openEnroll(emp)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="16"/>
                <line x1="8" y1="12" x2="16" y2="12"/>
              </svg>
              Daftarkan Wajah
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- MODAL ENROLL -->
    <div v-if="enrollModal.show" class="fm-modal-overlay" @click.self="closeEnroll">
      <div class="fm-modal">
        <div class="fm-modal-header">
          <div class="fm-modal-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e8262a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <line x1="19" y1="8" x2="19" y2="14"/>
              <line x1="22" y1="11" x2="16" y2="11"/>
            </svg>
            {{ enrollModal.emp?.has_face ? 'Update Wajah' : 'Daftarkan Wajah' }}:
            <strong>{{ enrollModal.emp?.name }}</strong>
          </div>
          <button @click="closeEnroll" class="fm-modal-close">✕</button>
        </div>

        <div class="fm-modal-body">
          <div class="fm-enroll-tips">
            💡 Minta pegawai menghadap kamera dengan pencahayaan cukup
          </div>

          <!-- Camera -->
          <div class="fm-enroll-camera" :class="enrollStatus">
            <video ref="enrollVideo" autoplay playsinline muted class="fm-enroll-video" />
            <canvas ref="enrollCanvas" class="fm-enroll-canvas-overlay" />
            <div class="fm-corner fm-tl"></div>
            <div class="fm-corner fm-tr"></div>
            <div class="fm-corner fm-bl"></div>
            <div class="fm-corner fm-br"></div>
            <div class="fm-enroll-badge" :class="enrollStatus">
              <span v-if="enrollStatus === 'idle'">📷 Memulai kamera...</span>
              <span v-else-if="enrollStatus === 'detecting'">🔍 Mendeteksi wajah...</span>
              <span v-else-if="enrollStatus === 'ready'">✅ Wajah terdeteksi, siap diambil</span>
              <span v-else-if="enrollStatus === 'no_face'">👤 Tidak ada wajah - perbaiki posisi</span>
            </div>
          </div>

          <!-- Sampling progress -->
          <div class="fm-sample-progress">
            <div class="fm-sample-label">Sample terkumpul: {{ samples.length }} / {{ REQUIRED_SAMPLES }}</div>
            <div class="fm-sample-bar">
              <div class="fm-sample-fill" :style="{ width: (samples.length / REQUIRED_SAMPLES * 100) + '%' }"></div>
            </div>
          </div>

          <!-- Peringatan wajah duplikat -->
          <div v-if="duplicateWarning" class="fm-duplicate-box">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
              <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            ⚠️ Wajah ini terlalu mirip dengan <strong>{{ duplicateWarning }}</strong>. Tidak bisa mendaftarkan wajah yang sama untuk 2 orang.
          </div>

          <div v-if="enrollError" class="fm-error-box">{{ enrollError }}</div>

          <!-- Actions -->
          <div class="fm-modal-actions">
            <button
              class="fm-btn-capture"
              @click="captureSample"
              :disabled="enrollStatus !== 'ready' || samples.length >= REQUIRED_SAMPLES || !!duplicateWarning"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/>
              </svg>
              Ambil Sample ({{ samples.length }}/{{ REQUIRED_SAMPLES }})
            </button>
            <button
              class="fm-btn-save"
              @click="saveEnrollment"
              :disabled="samples.length < REQUIRED_SAMPLES || saving || !!duplicateWarning"
            >
              <span v-if="!saving" class="fm-btn-inner">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                  <polyline points="17 21 17 13 7 13 7 21"/>
                  <polyline points="7 3 7 8 15 8"/>
                </svg>
                Simpan Data Wajah
              </span>
              <span v-else class="fm-btn-inner"><span class="fm-spinner-sm"></span> Menyimpan...</span>
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onUnmounted } from 'vue'
import * as faceapi from 'face-api.js'
import axios from 'axios'
import JwtService from '@/core/services/JwtService'
import Swal from 'sweetalert2/dist/sweetalert2.js'

const REQUIRED_SAMPLES  = 5
const DUPLICATE_THRESHOLD = 0.45  // jarak euclidean — di bawah ini dianggap wajah sama
const MODEL_URL = '/models'
const API_BASE  = (import.meta.env.VITE_APP_API_URL as string || '/api').replace(/\/$/, '')

function authHttp() {
  return axios.create({
    baseURL: API_BASE,
    headers: {
      Authorization: `Bearer ${JwtService.getToken()}`,
      Accept: 'application/json',
    },
  })
}

const euclidean = (a: number[], b: number[]): number => {
  let sum = 0
  for (let i = 0; i < a.length; i++) sum += (a[i] - b[i]) ** 2
  return Math.sqrt(sum)
}

export default defineComponent({
  name: 'FaceManagement',
  setup() {
    const employees   = ref<any[]>([])
    const loading     = ref(true)
    const saving      = ref(false)

    const enrollModal    = ref({ show: false, emp: null as any })
    const enrollStatus   = ref<'idle' | 'detecting' | 'ready' | 'no_face'>('idle')
    const enrollError    = ref('')
    const duplicateWarning = ref('')  // nama user yang wajahnya mirip
    const samples        = ref<Float32Array[]>([])

    const enrollVideo  = ref<HTMLVideoElement | null>(null)
    const enrollCanvas = ref<HTMLCanvasElement | null>(null)

    let enrollStream  : MediaStream | null = null
    let detectInterval: number | null = null
    let modelsLoaded  = false

    // Semua face profiles yang sudah terdaftar (untuk cek duplikat)
    let allProfiles: { user_id: number; name: string; descriptor: number[] }[] = []

    const enrolled    = computed(() => employees.value.filter(e => e.has_face).length)
    const notEnrolled = computed(() => employees.value.filter(e => !e.has_face).length)

    // ─── LOAD DATA ───
    const loadEmployees = async () => {
      loading.value = true
      try {
        const { data } = await authHttp().get('/admin/face/status')
        employees.value = data.data ?? []
      } catch (e) {
        employees.value = []
      } finally {
        loading.value = false
      }
    }

    // Load semua descriptor untuk pengecekan duplikat
    const loadAllProfiles = async () => {
      try {
        const { data } = await axios.get(`${API_BASE}/face/profiles`)
        allProfiles = (data.data ?? []).map((p: any) => ({
          user_id:    p.user_id,
          name:       p.name,
          descriptor: p.descriptor,
        }))
      } catch (_) {
        allProfiles = []
      }
    }

    const ensureModels = async () => {
      if (modelsLoaded) return
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
      ])
      modelsLoaded = true
    }

    // ─── CEK DUPLIKAT ───
    // Cek apakah descriptor ini terlalu mirip dengan wajah yang sudah terdaftar
    // Kecualikan user yang sedang di-update (boleh re-enroll wajahnya sendiri)
    const checkDuplicate = (descriptor: Float32Array): string => {
      const currentUserId = enrollModal.value.emp?.id
      for (const profile of allProfiles) {
        // Skip user yang sedang di-update (dia boleh update wajahnya sendiri)
        if (profile.user_id === currentUserId) continue
        const dist = euclidean(Array.from(descriptor), profile.descriptor)
        if (dist < DUPLICATE_THRESHOLD) {
          return profile.name  // return nama yang sudah terdaftar
        }
      }
      return ''
    }

    // ─── OPEN ENROLL MODAL ───
    const openEnroll = async (emp: any) => {
      enrollModal.value    = { show: true, emp }
      enrollError.value    = ''
      duplicateWarning.value = ''
      samples.value        = []
      enrollStatus.value   = 'idle'

      await ensureModels()
      await loadAllProfiles()  // refresh profiles setiap buka modal
      await startEnrollCamera()
    }

    const startEnrollCamera = async () => {
      try {
        enrollStream = await navigator.mediaDevices.getUserMedia({
          video: { width: 480, height: 360, facingMode: 'user' }
        })

        // Poll sampai enrollVideo tersedia
        let waited = 0
        while (!enrollVideo.value && waited < 3000) {
          await new Promise(r => setTimeout(r, 50))
          waited += 50
        }

        if (enrollVideo.value) {
          enrollVideo.value.srcObject = enrollStream
          await new Promise<void>(res => {
            enrollVideo.value!.onloadedmetadata = () => res()
            setTimeout(res, 3000)
          })
          await enrollVideo.value.play()
        }
        enrollStatus.value = 'detecting'
        startEnrollDetect()
      } catch (e: any) {
        enrollError.value = 'Gagal mengakses kamera: ' + (e?.message ?? e)
      }
    }

    const startEnrollDetect = () => {
      detectInterval = window.setInterval(async () => {
        if (!enrollVideo.value || enrollVideo.value.readyState < 2) return
        const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })
        const det  = await faceapi.detectSingleFace(enrollVideo.value, opts).withFaceLandmarks(true)
        enrollStatus.value = det ? 'ready' : 'no_face'
        drawEnrollOverlay(det)
      }, 600)
    }

    const drawEnrollOverlay = (det: any) => {
      const canvas = enrollCanvas.value
      const video  = enrollVideo.value
      if (!canvas || !video) return
      canvas.width  = video.videoWidth
      canvas.height = video.videoHeight
      const ctx = canvas.getContext('2d')!
      ctx.clearRect(0, 0, canvas.width, canvas.height)
      if (!det) return
      const box = det.detection.box
      ctx.strokeStyle = enrollStatus.value === 'ready' ? '#17c653' : '#ffa500'
      ctx.lineWidth   = 2.5
      ctx.shadowColor = ctx.strokeStyle
      ctx.shadowBlur  = 8
      ctx.strokeRect(box.x, box.y, box.width, box.height)
    }

    // ─── CAPTURE SAMPLE ───
    const captureSample = async () => {
      if (!enrollVideo.value) return
      const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })
      const det  = await faceapi
        .detectSingleFace(enrollVideo.value, opts)
        .withFaceLandmarks(true)
        .withFaceDescriptor()

      if (!det) {
        enrollError.value = 'Tidak ada wajah terdeteksi. Coba lagi.'
        return
      }

      // ✅ CEK DUPLIKAT setiap kali ambil sample
      const dupName = checkDuplicate(det.descriptor)
      if (dupName) {
        duplicateWarning.value = dupName
        enrollError.value      = ''
        samples.value          = []  // reset samples
        return
      }

      duplicateWarning.value = ''
      enrollError.value      = ''
      samples.value = [...samples.value, det.descriptor]
    }

    // ─── SAVE ENROLLMENT ───
    const saveEnrollment = async () => {
      if (samples.value.length < REQUIRED_SAMPLES) return
      if (duplicateWarning.value) return
      saving.value      = true
      enrollError.value = ''

      // Rata-rata semua descriptor
      const avgDescriptor = new Float32Array(128)
      for (let i = 0; i < 128; i++) {
        let sum = 0
        for (const s of samples.value) sum += s[i]
        avgDescriptor[i] = sum / samples.value.length
      }

      // ✅ CEK DUPLIKAT SEKALI LAGI sebelum simpan (validasi akhir)
      const dupName = checkDuplicate(avgDescriptor)
      if (dupName) {
        duplicateWarning.value = dupName
        saving.value           = false
        return
      }

      try {
        await authHttp().post(`/admin/face/enroll/${enrollModal.value.emp.id}`, {
          descriptor: Array.from(avgDescriptor),
        })

        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: `Wajah ${enrollModal.value.emp.name} berhasil didaftarkan.`,
          timer: 2000,
          showConfirmButton: false,
          background: '#15171e',
          color: '#f0f0f5',
        })
        closeEnroll()
        await loadEmployees()
      } catch (e: any) {
        enrollError.value = e?.response?.data?.message ?? 'Gagal menyimpan data wajah.'
      } finally {
        saving.value = false
      }
    }

    // ─── DELETE ───
    const confirmDelete = async (emp: any) => {
      const result = await Swal.fire({
        icon: 'warning',
        title: 'Hapus Data Wajah?',
        text: `Data wajah ${emp.name} akan dihapus. Pegawai tidak bisa absen via face recognition.`,
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        background: '#15171e',
        color: '#f0f0f5',
        confirmButtonColor: '#e8262a',
      })
      if (!result.isConfirmed) return
      try {
        await authHttp().delete(`/admin/face/delete/${emp.id}`)
        await loadEmployees()
      } catch (e) {
        console.error('[FaceManagement] delete error:', e)
      }
    }

    // ─── CLOSE ───
    const closeEnroll = () => {
      if (detectInterval) { clearInterval(detectInterval); detectInterval = null }
      enrollStream?.getTracks().forEach(t => t.stop())
      enrollStream           = null
      enrollModal.value      = { show: false, emp: null }
      enrollStatus.value     = 'idle'
      samples.value          = []
      duplicateWarning.value = ''
      enrollError.value      = ''
    }

    onUnmounted(() => {
      if (detectInterval) clearInterval(detectInterval)
      enrollStream?.getTracks().forEach(t => t.stop())
    })

    loadEmployees()

    return {
      employees, loading, enrolled, notEnrolled,
      saving, enrollModal, enrollStatus, enrollError, duplicateWarning,
      samples, REQUIRED_SAMPLES,
      enrollVideo, enrollCanvas,
      openEnroll, closeEnroll,
      captureSample, saveEnrollment,
      confirmDelete,
    }
  }
})
</script>

<style scoped>
.fm-wrap { padding: 24px; }

.fm-header {
  display: flex; justify-content: space-between; align-items: flex-start;
  margin-bottom: 24px; flex-wrap: wrap; gap: 16px;
}
.fm-title { font-size: 22px; font-weight: 800; color: #f0f0f5; margin: 0 0 4px; }
.fm-sub   { font-size: 13px; color: #55555e; margin: 0; }

.fm-header-stats { display: flex; gap: 12px; }
.fm-stat { background: #15171e; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 12px 20px; text-align: center; }
.fm-stat.orange { border-color: rgba(255,165,0,0.2); }
.fm-stat-num   { display: block; font-size: 24px; font-weight: 800; color: #f0f0f5; }
.fm-stat.orange .fm-stat-num { color: #ffa500; }
.fm-stat-label { display: block; font-size: 11px; color: #55555e; }

.fm-loading { display: flex; align-items: center; gap: 10px; color: #55555e; padding: 48px 0; justify-content: center; }
.fm-empty { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 64px 0; color: #3a3a48; font-size: 14px; }

.fm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 14px;
}

.fm-card {
  background: #15171e; border: 1px solid rgba(255,255,255,0.06);
  border-radius: 14px; padding: 18px;
  display: flex; flex-direction: column; gap: 12px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.fm-card:hover { border-color: rgba(255,255,255,0.1); box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
.fm-card.has-face { border-color: rgba(23,198,83,0.15); }

.fm-avatar-wrap { position: relative; align-self: center; }
.fm-avatar { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; display: block; }
.fm-avatar-fallback { width: 64px; height: 64px; border-radius: 50%; background: #e8262a; color: #fff; font-size: 24px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.fm-face-badge {
  position: absolute; bottom: 0; right: 0;
  width: 20px; height: 20px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid #15171e;
}
.fm-face-badge.registered { background: #17c653; color: #fff; }
.fm-face-badge.empty      { background: #ff6b6b; color: #fff; }

.fm-card-info { text-align: center; }
.fm-emp-name { font-size: 14px; font-weight: 700; color: #f0f0f5; }
.fm-emp-pos  { font-size: 12px; color: #55555e; margin-top: 2px; }
.fm-enrolled-at  { font-size: 11px; color: #3a3a48; margin-top: 6px; }
.fm-not-enrolled { font-size: 11px; color: #ffa500; margin-top: 6px; font-weight: 600; }

.fm-card-actions { display: flex; gap: 8px; flex-direction: column; margin-top: auto; }

.fm-btn-enroll {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  background: rgba(27,132,255,0.12); color: #5aabff;
  border: 1px solid rgba(27,132,255,0.2); border-radius: 8px;
  padding: 9px 14px; font-size: 12.5px; font-weight: 600;
  cursor: pointer; transition: all 0.15s; width: 100%;
}
.fm-btn-enroll:hover { background: rgba(27,132,255,0.22); border-color: rgba(27,132,255,0.4); }

/* Tombol Daftarkan Wajah — warna berbeda agar mudah dibedakan */
.fm-btn-register {
  background: rgba(23,198,83,0.1); color: #17c653;
  border-color: rgba(23,198,83,0.2);
}
.fm-btn-register:hover { background: rgba(23,198,83,0.2); border-color: rgba(23,198,83,0.4); }

.fm-btn-delete {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  background: rgba(255,107,107,0.08); color: #ff6b6b;
  border: 1px solid rgba(255,107,107,0.15); border-radius: 8px;
  padding: 9px 14px; font-size: 12.5px; font-weight: 600;
  cursor: pointer; transition: all 0.15s; width: 100%;
}
.fm-btn-delete:hover { background: rgba(255,107,107,0.18); }

/* Modal */
.fm-modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.85);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; padding: 20px;
}
.fm-modal {
  background: #15171e; border: 1px solid rgba(255,255,255,0.08);
  border-radius: 18px; width: 100%; max-width: 500px; overflow: hidden;
  max-height: 90vh; overflow-y: auto;
}
.fm-modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 18px 22px; border-bottom: 1px solid rgba(255,255,255,0.06);
  position: sticky; top: 0; background: #15171e; z-index: 1;
}
.fm-modal-title { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; color: #f0f0f5; flex-wrap: wrap; }
.fm-modal-title strong { color: #e8262a; }
.fm-modal-close { background: none; border: none; color: #55555e; font-size: 18px; cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: color 0.15s; line-height: 1; }
.fm-modal-close:hover { color: #f0f0f5; background: rgba(255,255,255,0.06); }
.fm-modal-body { padding: 20px; display: flex; flex-direction: column; gap: 14px; }

.fm-enroll-tips { font-size: 12px; color: #55555e; text-align: center; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; }

.fm-enroll-camera {
  position: relative; border-radius: 14px; overflow: hidden;
  aspect-ratio: 4/3; background: #0a0c10;
  border: 2px solid rgba(255,255,255,0.07);
  transition: border-color 0.3s, box-shadow 0.3s;
}
.fm-enroll-camera.ready     { border-color: #17c653; box-shadow: 0 0 20px rgba(23,198,83,0.2); }
.fm-enroll-camera.no_face   { border-color: #ffa500; }
.fm-enroll-camera.detecting { border-color: rgba(27,132,255,0.4); }

.fm-enroll-video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); display: block; }
.fm-enroll-canvas-overlay { position: absolute; inset: 0; width: 100%; height: 100%; transform: scaleX(-1); pointer-events: none; }

.fm-corner { position: absolute; width: 20px; height: 20px; border-color: #e8262a; border-style: solid; border-width: 0; }
.fm-tl { top: 8px;    left: 8px;  border-top-width: 2px;    border-left-width: 2px;  border-top-left-radius: 4px; }
.fm-tr { top: 8px;    right: 8px; border-top-width: 2px;    border-right-width: 2px; border-top-right-radius: 4px; }
.fm-bl { bottom: 8px; left: 8px;  border-bottom-width: 2px; border-left-width: 2px;  border-bottom-left-radius: 4px; }
.fm-br { bottom: 8px; right: 8px; border-bottom-width: 2px; border-right-width: 2px; border-bottom-right-radius: 4px; }

.fm-enroll-badge {
  position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);
  background: rgba(10,12,16,0.85); backdrop-filter: blur(6px);
  border: 1px solid rgba(255,255,255,0.07); border-radius: 20px;
  padding: 5px 12px; font-size: 11.5px; font-weight: 600; color: #aaaabc; white-space: nowrap;
}
.fm-enroll-badge.ready   { color: #17c653; border-color: rgba(23,198,83,0.3); }
.fm-enroll-badge.no_face { color: #ffa500; border-color: rgba(255,165,0,0.3); }

.fm-sample-label { font-size: 12px; color: #55555e; margin-bottom: 6px; }
.fm-sample-bar { height: 6px; background: rgba(255,255,255,0.07); border-radius: 6px; overflow: hidden; }
.fm-sample-fill { height: 100%; background: linear-gradient(90deg, #e8262a, #ff6b6b); border-radius: 6px; transition: width 0.3s; }

/* Peringatan duplikat */
.fm-duplicate-box {
  display: flex; align-items: flex-start; gap: 8px;
  padding: 12px 14px; border-radius: 10px;
  background: rgba(255,165,0,0.08); border: 1px solid rgba(255,165,0,0.25);
  color: #ffa500; font-size: 12.5px; line-height: 1.5;
}
.fm-duplicate-box strong { color: #ffcc44; }

.fm-error-box { padding: 11px 14px; border-radius: 10px; background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.18); color: #ff6b6b; font-size: 12.5px; }

.fm-modal-actions { display: flex; gap: 10px; }

.fm-btn-capture {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;
  background: rgba(255,255,255,0.05); border: 1.5px solid rgba(255,255,255,0.1);
  color: #aaaabc; border-radius: 10px; padding: 11px;
  font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s;
}
.fm-btn-capture:hover:not(:disabled) { background: rgba(255,255,255,0.09); color: #f0f0f5; }
.fm-btn-capture:disabled { opacity: 0.35; cursor: not-allowed; }

.fm-btn-save {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;
  background: #e8262a; color: #fff; border: none; border-radius: 10px;
  padding: 11px; font-size: 13px; font-weight: 700; cursor: pointer; transition: background 0.15s;
}
.fm-btn-save:hover:not(:disabled) { background: #ff3a3d; }
.fm-btn-save:disabled { opacity: 0.4; cursor: not-allowed; }
.fm-btn-inner { display: flex; align-items: center; gap: 7px; }

@keyframes fm-spin { to { transform: rotate(360deg); } }
.fm-spinner    { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.1); border-top-color: #aaaabc; border-radius: 50%; animation: fm-spin 0.65s linear infinite; display: inline-block; }
.fm-spinner-sm { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.25); border-top-color: #fff; border-radius: 50%; animation: fm-spin 0.65s linear infinite; display: inline-block; }

@media (max-width: 640px) {
  .fm-grid { grid-template-columns: 1fr 1fr; }
  .fm-modal-actions { flex-direction: column; }
}
</style>