<template>
  <div class="auth-root">

    <!-- LEFT PANEL -->
    <div class="auth-left">
      <div class="auth-grain" aria-hidden="true"></div>
      <div class="auth-glow-tl" aria-hidden="true"></div>

      <!-- Logo -->
      <div class="auth-logo-wrap">
        <router-link to="/">
          <img
            :src="getAssetPath('media/logos/default-dark.svg')"
            alt="Metronic"
            class="auth-logo-img"
          />
        </router-link>
      </div>

      <!-- Form slot -->
      <div class="auth-form-slot">
        <router-view></router-view>
      </div>

      <!-- Footer -->
      <div class="auth-footer">
        <a href="#" class="auth-footer-link">Terms</a>
        <span class="auth-footer-dot"></span>
        <a href="#" class="auth-footer-link">Plans</a>
        <span class="auth-footer-dot"></span>
        <a href="#" class="auth-footer-link">Contact Us</a>
      </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="auth-right">
      <div class="auth-right-glow-r" aria-hidden="true"></div>
      <div class="auth-right-glow-b" aria-hidden="true"></div>

      <div class="auth-right-inner">

        <!-- Big logo -->
        <router-link to="/" class="auth-right-logo">
          <img
            :src="getAssetPath('media/logos/default-dark.svg')"
            alt="Metronic"
            class="auth-right-logo-img"
          />
        </router-link>

        <!-- Mockup cards -->
        <div class="auth-mockup">

          <!-- Stats card -->
          <div class="amc amc-stats">
            <div class="amc-head">
              Stats by Department
              <span class="amc-badge">PDF Report</span>
            </div>
            <div class="amc-nums">
              <div>
                <strong>$8,035</strong>
                <small>Actual for April</small>
              </div>
              <div>
                <strong>$4,684</strong>
                <small>SAP</small>
                <em>+4.5%</em>
              </div>
            </div>
            <div class="amc-donut-row">
              <div class="amc-donut"></div>
            </div>
          </div>

          <!-- Earnings card -->
          <div class="amc amc-earn">
            <div class="amc-earn-top">
              <div>
                <strong>$69,700</strong>
                <small>Projects Earnings in April</small>
              </div>
              <em>+2.2%</em>
            </div>
            <div class="amc-bars">
              <div style="height:40%;background:#1e2d42"></div>
              <div style="height:65%;background:#1b84ff"></div>
              <div style="height:35%;background:#1e2d42"></div>
              <div style="height:80%;background:#1b84ff"></div>
              <div style="height:50%;background:#1e2d42"></div>
              <div style="height:95%;background:#1b84ff"></div>
              <div style="height:60%;background:#1e2d42"></div>
              <div style="height:75%;background:#1b84ff"></div>
            </div>
            <div class="amc-legend">
              <div><i style="background:#1b84ff"></i>Lead CRM $7,660</div>
              <div><i style="background:#7239ea"></i>Mrvy App $2,820</div>
              <div><i style="background:#17c653"></i>Others $45,257</div>
            </div>
          </div>

          <!-- Twitter card -->
          <div class="amc amc-tw">
            <div class="amc-tw-ico">
              <svg width="11" height="9" viewBox="0 0 24 20" fill="white">
                <path d="M24 2.557a9.83 9.83 0 01-2.828.775 4.932 4.932 0 002.165-2.724 9.864 9.864 0 01-3.127 1.195 4.916 4.916 0 00-8.384 4.482A13.978 13.978 0 011.64 1.149a4.916 4.916 0 001.523 6.556 4.903 4.903 0 01-2.229-.616v.061a4.916 4.916 0 003.946 4.814 4.936 4.936 0 01-2.224.084 4.918 4.918 0 004.597 3.415A9.868 9.868 0 010 17.54a13.941 13.941 0 007.548 2.212c9.057 0 14.01-7.503 14.01-14.01 0-.213-.005-.426-.015-.637A10.012 10.012 0 0024 2.557z"/>
              </svg>
            </div>
            <strong>570k</strong>
            <small>Followers</small>
            <em>+3%</em>
          </div>

        </div>

        <!-- Tagline -->
        <h2 class="auth-tagline">
          Fast, Efficient<br />and <span>Productive</span>
        </h2>
        <p class="auth-tagline-sub">
          In this kind of post, the blogger introduces a person they've interviewed
          and provides some background information about the interviewee and their work.
        </p>

      </div>
    </div>

  </div>
</template>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent, onMounted } from "vue";
import LayoutService from "@/core/services/LayoutService";
import { useBodyStore } from "@/stores/body";

export default defineComponent({
  name: "auth-layout",
  components: {},
  setup() {
    const store = useBodyStore();
    onMounted(() => {
      LayoutService.emptyElementClassesAndAttributes(document.body);
      store.addBodyClassname("app-blank");
      store.addBodyClassname("bg-body");
    });
    return { getAssetPath };
  },
});
</script>

<style scoped>
.auth-root {
  display: flex;
  min-height: 100vh;
  width: 100%;
  background: #0b0c10;
  font-family: 'Inter', sans-serif;
}

/* ── LEFT PANEL ── */
.auth-left {
  width: 500px;
  height: 100vh;          /* fix tinggi = viewport */
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  padding: 40px 52px 36px;
  background: #111318;
  border-right: 1px solid rgba(255,255,255,0.06);
  position: relative;
  overflow-y: auto;       /* ← bisa scroll */
  overflow-x: hidden;
  /* custom scrollbar */
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,0.08) transparent;
}
.auth-left::-webkit-scrollbar {
  width: 4px;
}
.auth-left::-webkit-scrollbar-track {
  background: transparent;
}
.auth-left::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.08);
  border-radius: 4px;
}
.auth-left::-webkit-scrollbar-thumb:hover {
  background: rgba(255,255,255,0.14);
}

.auth-grain {
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 0;
}
.auth-glow-tl {
  position: absolute;
  top: -160px;
  left: -160px;
  width: 420px;
  height: 420px;
  background: radial-gradient(circle, rgba(232,38,42,0.10) 0%, transparent 65%);
  pointer-events: none;
  z-index: 0;
}
.auth-left > *:not(.auth-grain):not(.auth-glow-tl) {
  position: relative;
  z-index: 1;
}
.auth-logo-wrap { margin-bottom: 36px; flex-shrink: 0; }
.auth-logo-img  { height: 38px; width: auto; display: block; }

/* form slot: tidak center paksa, biarkan konten natural */
.auth-form-slot {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-start; /* ← was: center, sekarang natural dari atas */
  padding-bottom: 16px;
}

.auth-footer {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 18px;
  padding-top: 24px;
  flex-shrink: 0;
}
.auth-footer-link {
  font-size: 11px;
  color: #3a3a48;
  text-decoration: none;
  transition: color 0.15s;
}
.auth-footer-link:hover { color: #72727a; }
.auth-footer-dot {
  width: 3px;
  height: 3px;
  border-radius: 50%;
  background: #3a3a48;
  display: inline-block;
}

/* ── RIGHT PANEL ── */
.auth-right {
  flex: 1;
  background: #0b0c10;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 40px;
  position: relative;
  overflow: hidden;
}
.auth-right-glow-r {
  position: absolute;
  top: 5%;
  left: 50%;
  transform: translateX(-50%);
  width: 600px;
  height: 400px;
  background: radial-gradient(ellipse, rgba(232,38,42,0.07) 0%, transparent 65%);
  pointer-events: none;
}
.auth-right-glow-b {
  position: absolute;
  bottom: 6%;
  right: 4%;
  width: 320px;
  height: 320px;
  background: radial-gradient(ellipse, rgba(27,132,255,0.07) 0%, transparent 70%);
  pointer-events: none;
}
.auth-right-inner {
  position: relative;
  z-index: 1;
  text-align: center;
  max-width: 520px;
  width: 100%;
}
.auth-right-logo { display: block; margin-bottom: 48px; }
.auth-right-logo-img { height: 58px; width: auto; display: inline-block; }

/* ── Mockup ── */
.auth-mockup {
  position: relative;
  width: 440px;
  height: 270px;
  margin: 0 auto 44px;
}
.amc {
  position: absolute;
  background: #15171e;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 14px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.65), 0 1px 0 rgba(255,255,255,0.04) inset;
  overflow: hidden;
}

@keyframes amc-float-a { 0%,100%{ transform:translateY(0) } 50%{ transform:translateY(-9px) } }
@keyframes amc-float-b { 0%,100%{ transform:translateY(0) } 50%{ transform:translateY(-7px) } }
@keyframes amc-float-c { 0%,100%{ transform:translateY(0) } 50%{ transform:translateY(-11px) } }

.amc-stats {
  width: 215px; left: 0; top: 0;
  padding: 14px; z-index: 1;
  animation: amc-float-a 6s ease-in-out infinite;
}
.amc-head {
  font-size: 8px; font-weight: 700; letter-spacing: 0.1em;
  text-transform: uppercase; color: #555;
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 11px;
}
.amc-badge {
  background: rgba(255,255,255,0.06);
  padding: 2px 6px; border-radius: 4px;
  font-size: 7px; color: #555;
}
.amc-nums { display: flex; gap: 14px; margin-bottom: 11px; }
.amc-nums > div strong { font-size: 15px; font-weight: 800; color: #f0f0f5; display: block; letter-spacing: -0.02em; }
.amc-nums > div small  { font-size: 7px; color: #555; display: block; }
.amc-nums > div em     { font-style: normal; font-size: 7px; font-weight: 600; padding: 1px 5px; border-radius: 3px; background: rgba(23,198,83,0.12); color: #17c653; display: inline-block; margin-top: 2px; }
.amc-donut-row { display: flex; justify-content: center; }
.amc-donut {
  width: 78px; height: 39px;
  border-radius: 78px 78px 0 0;
  background: conic-gradient(from 180deg, #7239ea 0deg 88deg, #1b84ff 88deg 160deg, #17c653 160deg 180deg);
  position: relative;
}
.amc-donut::after {
  content: ''; position: absolute;
  bottom: 0; left: 50%; transform: translateX(-50%);
  width: 48px; height: 24px;
  background: #15171e; border-radius: 48px 48px 0 0;
}

.amc-earn {
  width: 195px; bottom: 0; left: 22px;
  padding: 12px; z-index: 2;
  animation: amc-float-b 6s ease-in-out infinite;
  animation-delay: -2s;
}
.amc-earn-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 7px; }
.amc-earn-top strong { font-size: 14px; font-weight: 800; color: #f0f0f5; display: block; letter-spacing: -0.02em; }
.amc-earn-top small  { font-size: 7px; color: #555; display: block; }
.amc-earn-top em     { font-style: normal; font-size: 7px; font-weight: 600; padding: 1px 5px; border-radius: 3px; background: rgba(23,198,83,0.12); color: #17c653; white-space: nowrap; }
.amc-bars { display: flex; align-items: flex-end; gap: 2px; height: 32px; margin-bottom: 7px; }
.amc-bars div { flex: 1; border-radius: 2px 2px 0 0; }
.amc-legend { display: flex; flex-direction: column; gap: 2px; font-size: 7px; color: #555; }
.amc-legend div { display: flex; align-items: center; gap: 4px; }
.amc-legend i { width: 5px; height: 5px; border-radius: 1px; display: inline-block; font-style: normal; }

.amc-tw {
  width: 132px; top: 10px; right: 0;
  padding: 13px; z-index: 3;
  display: flex; flex-direction: column;
  animation: amc-float-c 6s ease-in-out infinite;
  animation-delay: -4s;
}
.amc-tw-ico {
  width: 22px; height: 22px; border-radius: 8px;
  background: #1a8cd8;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 9px;
}
.amc-tw strong { font-size: 21px; font-weight: 800; color: #f0f0f5; display: block; letter-spacing: -0.04em; }
.amc-tw small  { font-size: 8px; color: #555; display: block; margin-bottom: 6px; }
.amc-tw em     { font-style: normal; font-size: 7px; font-weight: 600; padding: 1px 5px; border-radius: 3px; background: rgba(23,198,83,0.12); color: #17c653; display: inline-block; }

.auth-tagline {
  font-size: 26px; font-weight: 800;
  letter-spacing: -0.03em; color: #f0f0f5;
  line-height: 1.25; margin-bottom: 12px;
}
.auth-tagline span { color: #e8262a; }
.auth-tagline-sub {
  font-size: 13px; color: #55555e;
  line-height: 1.75; font-weight: 400;
}

/* ── RESPONSIVE ── */
@media (max-width: 1024px) {
  .auth-right { display: none; }
  .auth-left  { width: 100%; border-right: none; height: 100vh; }
}
@media (max-width: 600px) {
  .auth-left { padding: 32px 28px 28px; }
}
</style>