<template>
  <div class="ud-root" ref="rootEl">
    <canvas ref="particleCanvas" class="ud-particle-canvas"></canvas>

    <div class="ud-layout">

      <!-- ══ SIDEBAR ══ -->
      <aside class="ud-sidebar ud-pop-item" style="--pop-delay:0s">

        <!-- Identity -->
        <div class="ud-identity">
          <div class="ud-avatar-wrap" @mouseenter="triggerAvatarPulse" ref="avatarWrap">
            <div class="ud-avatar-ring"></div>
            <img v-if="authStore.user.avatar" :src="avatarUrl" alt="avatar" class="ud-avatar-img" />
            <div v-else class="ud-avatar-initials">
              {{ authStore.user.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
            </div>
            <span class="ud-online-dot"></span>
          </div>
          <h2 class="ud-username">{{ authStore.user.name || 'User' }}</h2>
          <p class="ud-useremail">{{ authStore.user.email }}</p>
          <span class="ud-role-tag">
            <svg width="9" height="9" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0L6.12 3.38H9.51L6.82 5.47L7.94 8.85L5 6.76L2.06 8.85L3.18 5.47L0.49 3.38H3.88L5 0Z"/></svg>
            {{ authStore.user.role || 'User' }}
          </span>
        </div>

        <div class="ud-line"></div>

        <!-- Profile Completion Ring -->
        <div class="ud-completion-block">
          <div class="ud-completion-circle">
            <svg viewBox="0 0 80 80" width="80" height="80">
              <circle cx="40" cy="40" r="33" fill="none" stroke="var(--border)" stroke-width="5"/>
              <circle cx="40" cy="40" r="33" fill="none"
                :stroke="profileCompletion === 100 ? 'var(--green)' : 'var(--amber)'"
                stroke-width="5" stroke-linecap="round"
                stroke-dasharray="207.3"
                :stroke-dashoffset="207.3 - (207.3 * profileCompletion / 100)"
                class="ud-progress-arc"
                transform="rotate(-90 40 40)"/>
            </svg>
            <div class="ud-completion-inner">
              <span class="ud-completion-pct">{{ profileCompletion }}%</span>
              <span class="ud-completion-lbl">Profile</span>
            </div>
          </div>
          <div class="ud-completion-info">
            <p class="ud-ci-title">{{ profileCompletion === 100 ? 'Complete!' : 'Almost there' }}</p>
            <p class="ud-ci-sub">{{ filledCount }}/{{ totalFields }} fields filled</p>
            <div class="ud-mini-bar">
              <div class="ud-mini-fill" :style="{ width: profileCompletion + '%', background: profileCompletion === 100 ? 'var(--green)' : 'var(--amber)' }"></div>
            </div>
          </div>
        </div>

        <div class="ud-line"></div>

        <!-- Profile Status -->
        <div class="ud-block">
          <p class="ud-block-label">Profile Status</p>
          <div class="ud-checklist">
            <div v-for="f in fieldStatus" :key="f.key" class="ud-check-item">
              <span :class="['ud-check-icon', f.filled ? 'ud-check-ok' : 'ud-check-no']">
                <svg v-if="f.filled" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                <svg v-else width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </span>
              <span class="ud-check-label">{{ f.label }}</span>
            </div>
          </div>
        </div>

        <div class="ud-line"></div>

        <!-- Account Info -->
        <div class="ud-block">
          <p class="ud-block-label">Account Info</p>
          <div class="ud-kv-list">
            <div class="ud-kv"><span class="ud-kv-key">Joined</span><span class="ud-kv-val">{{ joinDate }}</span></div>
            <div class="ud-kv"><span class="ud-kv-key">Status</span><span class="ud-kv-val ud-kv-green">Active</span></div>
            <div class="ud-kv"><span class="ud-kv-key">Email</span><span class="ud-kv-val ud-kv-green">Verified</span></div>
            <div class="ud-kv"><span class="ud-kv-key">Security</span><span class="ud-kv-val">Standard</span></div>
            <div class="ud-kv"><span class="ud-kv-key">Access</span><span class="ud-kv-val ud-capitalize">{{ authStore.user.role || 'user' }}</span></div>
          </div>
        </div>

        <div class="ud-line"></div>

        <!-- Quick Actions -->
        <div class="ud-block">
          <p class="ud-block-label">Quick Actions</p>
          <router-link to="/user/profile" class="ud-action-item">
            <span class="ud-ai-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>
            <span class="ud-ai-label">Edit Profile</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </router-link>
          <router-link to="/user/settings?tab=password" class="ud-action-item">
            <span class="ud-ai-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
            <span class="ud-ai-label">Change Password</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </router-link>
          <router-link to="/user/settings?tab=notifications" class="ud-action-item">
            <span class="ud-ai-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
            <span class="ud-ai-label">Notifications</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </router-link>
          <router-link to="/user/settings" class="ud-action-item">
            <span class="ud-ai-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 0 0 2 12a10 10 0 0 0 17.07 7.07M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg></span>
            <span class="ud-ai-label">Settings</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </router-link>
        </div>

        <div class="ud-line"></div>

        <!-- Security Score -->
        <div class="ud-block">
          <p class="ud-block-label">Security Score</p>
          <div class="ud-score-wrap">
            <div class="ud-score-bar-track">
              <div class="ud-score-bar-fill" style="width: 45%"></div>
            </div>
            <div class="ud-score-labels">
              <span class="ud-score-val">45<span class="ud-score-max">/100</span></span>
              <span class="ud-score-grade ud-grade-warn">Fair</span>
            </div>
          </div>
          <div class="ud-score-items">
            <div class="ud-si"><span class="ud-si-dot ud-si-ok"></span><span class="ud-si-lbl">Email verified</span><span class="ud-si-pts ud-si-green">+20</span></div>
            <div class="ud-si"><span class="ud-si-dot ud-si-ok"></span><span class="ud-si-lbl">Password set</span><span class="ud-si-pts ud-si-green">+25</span></div>
            <div class="ud-si"><span class="ud-si-dot ud-si-no"></span><span class="ud-si-lbl">Two-factor auth</span><span class="ud-si-pts ud-si-muted">+30</span></div>
            <div class="ud-si"><span class="ud-si-dot ud-si-no"></span><span class="ud-si-lbl">Recovery email</span><span class="ud-si-pts ud-si-muted">+15</span></div>
            <div class="ud-si"><span class="ud-si-dot ud-si-no"></span><span class="ud-si-lbl">Profile complete</span><span class="ud-si-pts ud-si-muted">+10</span></div>
          </div>
        </div>

        <div class="ud-line"></div>

        <!-- Logout -->
        <div class="ud-block">
          <p class="ud-block-label">Session</p>
          <button class="ud-logout-btn" @click="onLogout">
            <span class="ud-logout-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
            <span class="ud-logout-text">
              <span class="ud-logout-title">Kembali ke Login</span>
              <span class="ud-logout-sub">Keluar dari sesi ini</span>
            </span>
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="ud-logout-arrow"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>

      </aside>

      <!-- ══ MAIN ══ -->
      <main class="ud-main">

        <!-- Welcome Banner -->
        <div class="ud-banner ud-pop-item" style="--pop-delay:0.05s">
          <div class="ud-banner-glow"></div>
          <div class="ud-banner-glow ud-banner-glow2"></div>
          <div class="ud-banner-content">
            <p class="ud-banner-greeting">Welcome back</p>
            <h1 class="ud-banner-name">{{ authStore.user.name || 'User' }}</h1>
            <p class="ud-banner-sub">Manage and monitor your account from this dashboard.</p>
          </div>
          <div class="ud-banner-badges">
            <span class="ud-bbadge ud-bbadge-green"><span class="ud-pulse-dot"></span>Active Account</span>
            <span class="ud-bbadge ud-bbadge-blue">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              Verified Email
            </span>
            <span class="ud-bbadge ud-bbadge-amber">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Secured
            </span>
            <router-link to="/user/profile" class="ud-banner-editbtn">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit Profile
            </router-link>
          </div>
        </div>

        <!-- Stats Row -->
        <div class="ud-stats-grid">
          <div class="ud-sc ud-sc-accent-green ud-pop-item" style="--pop-delay:0.10s">
            <div class="ud-sc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div class="ud-sc-info"><span class="ud-sc-val">Active</span><span class="ud-sc-lbl">Account Status</span></div>
          </div>
          <div class="ud-sc ud-sc-accent-blue ud-pop-item" style="--pop-delay:0.15s">
            <div class="ud-sc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/></svg></div>
            <div class="ud-sc-info"><span class="ud-sc-val">Verified</span><span class="ud-sc-lbl">Email Address</span></div>
          </div>
          <div class="ud-sc ud-pop-item" style="--pop-delay:0.20s">
            <div class="ud-sc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <div class="ud-sc-info"><span class="ud-sc-val">Standard</span><span class="ud-sc-lbl">Security Level</span></div>
          </div>
          <div class="ud-sc ud-sc-accent-amber ud-pop-item" style="--pop-delay:0.25s">
            <div class="ud-sc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2"/></svg></div>
            <div class="ud-sc-info"><span class="ud-sc-val ud-capitalize">{{ authStore.user.role || 'User' }}</span><span class="ud-sc-lbl">Role & Access</span></div>
          </div>
        </div>

        <!-- Feature Row -->
        <div class="ud-feature-row">
          <!-- Clock Card -->
          <div class="ud-card ud-clock-card ud-pop-item" style="--pop-delay:0.30s">
            <div class="ud-clock-bg"></div>
            <div class="ud-clock-inner">
              <div class="ud-clock-face" ref="clockFace">
                <svg viewBox="0 0 120 120" width="110" height="110" class="ud-clock-svg">
                  <circle cx="60" cy="60" r="54" fill="none" stroke="var(--border2)" stroke-width="1.5"/>
                  <circle cx="60" cy="60" r="48" fill="none" stroke="var(--border)" stroke-width="0.5"/>
                  <line v-for="n in 12" :key="'h'+n"
                    :x1="60 + 42*Math.sin((n/12)*2*Math.PI)" :y1="60 - 42*Math.cos((n/12)*2*Math.PI)"
                    :x2="60 + 48*Math.sin((n/12)*2*Math.PI)" :y2="60 - 48*Math.cos((n/12)*2*Math.PI)"
                    stroke="var(--border2)" stroke-width="2" stroke-linecap="round"/>
                  <line :x1="60" :y1="60" :x2="60 + 26*Math.sin(hourAngle)" :y2="60 - 26*Math.cos(hourAngle)" stroke="var(--head)" stroke-width="2.5" stroke-linecap="round"/>
                  <line :x1="60" :y1="60" :x2="60 + 38*Math.sin(minuteAngle)" :y2="60 - 38*Math.cos(minuteAngle)" stroke="var(--blue)" stroke-width="1.5" stroke-linecap="round"/>
                  <line :x1="60" :y1="60" :x2="60 + 40*Math.sin(secondAngle)" :y2="60 - 40*Math.cos(secondAngle)" stroke="var(--amber)" stroke-width="1" stroke-linecap="round"/>
                  <circle cx="60" cy="60" r="3.5" fill="var(--amber)"/>
                  <circle cx="60" cy="60" r="1.5" fill="var(--head)"/>
                </svg>
              </div>
              <div class="ud-clock-text">
                <span class="ud-clock-time">{{ timeStr }}</span>
                <span class="ud-clock-date">{{ dateStr }}</span>
                <span class="ud-clock-zone">{{ timezone }}</span>
              </div>
            </div>
          </div>

          <!-- Session Stats Card -->
          <div class="ud-card ud-session-card ud-pop-item" style="--pop-delay:0.35s">
            <div class="ud-card-head">
              <div class="ud-card-title-row">
                <span class="ud-card-icon ud-card-icon-amber">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </span>
                <div><h3 class="ud-card-title">Session Overview</h3><p class="ud-card-sub">Current session metrics</p></div>
              </div>
            </div>
            <div class="ud-line"></div>
            <div class="ud-session-grid">
              <div class="ud-sess-item">
                <span class="ud-sess-icon" style="color:var(--green); background:var(--green-bg)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                <div><span class="ud-sess-val">{{ sessionTime }}</span><span class="ud-sess-lbl">Session Time</span></div>
              </div>
              <div class="ud-sess-item">
                <span class="ud-sess-icon" style="color:var(--blue); background:var(--blue-bg)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                <div><span class="ud-sess-val">{{ pageViews }}</span><span class="ud-sess-lbl">Page Views</span></div>
              </div>
              <div class="ud-sess-item">
                <span class="ud-sess-icon" style="color:var(--amber); background:var(--amber-bg)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
                <div><span class="ud-sess-val">Online</span><span class="ud-sess-lbl">Status</span></div>
              </div>
              <div class="ud-sess-item">
                <span class="ud-sess-icon" style="color:var(--amber); background:var(--amber-bg)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></span>
                <div><span class="ud-sess-val">Web</span><span class="ud-sess-lbl">Device</span></div>
              </div>
            </div>
            <div class="ud-live-bar-wrap">
              <div class="ud-live-bar-label">
                <span>Live Activity</span>
                <span class="ud-live-dot"><span class="ud-pulse-dot"></span>Live</span>
              </div>
              <div class="ud-bars">
                <div v-for="(b, i) in activityBars" :key="i" class="ud-bar-col" :style="{ height: b + 'px', opacity: i === activityBars.length - 1 ? 1 : 0.3 + (i / activityBars.length) * 0.5 }"></div>
              </div>
            </div>
          </div>

          <!-- Quote Card -->
          <div class="ud-card ud-quote-card ud-pop-item" style="--pop-delay:0.40s">
            <div class="ud-quote-glow"></div>
            <div class="ud-quote-mark">"</div>
            <p class="ud-quote-text">{{ currentQuote.text }}</p>
            <div class="ud-quote-author"><div class="ud-qa-line"></div><span>{{ currentQuote.author }}</span></div>
            <div class="ud-quote-dots">
              <span v-for="(q, i) in quotes" :key="i" :class="['ud-qdot', i === quoteIndex ? 'ud-qdot-active' : '']" @click="quoteIndex = i"></span>
            </div>
          </div>
        </div>

        <!-- Mid Row -->
        <div class="ud-mid-row">
          <div class="ud-col-left">
            <!-- Profile Info Card -->
            <div class="ud-card ud-pop-item" style="--pop-delay:0.45s">
              <div class="ud-card-head">
                <div class="ud-card-title-row">
                  <span class="ud-card-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>
                  <div><h3 class="ud-card-title">Profile Information</h3><p class="ud-card-sub">Your current account data</p></div>
                </div>
                <router-link to="/user/profile" class="ud-ghost-btn">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </router-link>
              </div>
              <div class="ud-line"></div>
              <div class="ud-pf-grid">
                <div v-for="item in profileFields" :key="item.key" class="ud-pf-cell" @mouseenter="e => rippleEffect(e)">
                  <div class="ud-pf-top">
                    <span :class="['ud-pf-icon', item.iconColor]" v-html="item.iconSvg"></span>
                    <span class="ud-pf-label">{{ item.label }}</span>
                  </div>
                  <p v-if="item.value" class="ud-pf-val">{{ item.value }}</p>
                  <p v-else class="ud-pf-empty">Not provided</p>
                </div>
              </div>
            </div>

            <!-- Tips Card -->
            <div class="ud-card ud-tips-card ud-pop-item" style="--pop-delay:0.50s">
              <div class="ud-card-head">
                <div class="ud-card-title-row">
                  <span class="ud-card-icon ud-card-icon-amber"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
                  <div><h3 class="ud-card-title">Tips & Shortcuts</h3><p class="ud-card-sub">Get the most out of your account</p></div>
                </div>
              </div>
              <div class="ud-line ud-tips-line"></div>
              <div class="ud-tips-grid">
                <div class="ud-tip-item">
                  <span class="ud-tip-icon" style="color:var(--green);background:var(--green-bg);"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                  <div><p class="ud-tip-title">Enable 2FA</p><p class="ud-tip-desc">Secure your account with two-factor authentication for extra protection.</p></div>
                </div>
                <div class="ud-tip-item">
                  <span class="ud-tip-icon" style="color:var(--blue);background:var(--blue-bg);"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>
                  <div><p class="ud-tip-title">Complete Profile</p><p class="ud-tip-desc">Fill in all fields to unlock personalized features and better visibility.</p></div>
                </div>
                <div class="ud-tip-item">
                  <span class="ud-tip-icon" style="color:var(--amber);background:var(--amber-bg);"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
                  <div><p class="ud-tip-title">Stay Notified</p><p class="ud-tip-desc">Turn on notifications to never miss important account updates.</p></div>
                </div>
                <div class="ud-tip-item">
                  <span class="ud-tip-icon" style="color:var(--amber);background:var(--amber-bg);"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                  <div><p class="ud-tip-title">Strong Password</p><p class="ud-tip-desc">Change your password regularly and use a unique combination of characters.</p></div>
                </div>
              </div>
            </div>
          </div>

          <div class="ud-col-right">
            <!-- Security Card -->
            <div class="ud-card ud-pop-item" style="--pop-delay:0.55s">
              <div class="ud-card-head">
                <div class="ud-card-title-row">
                  <span class="ud-card-icon ud-card-icon-blue"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                  <div><h3 class="ud-card-title">Account Security</h3><p class="ud-card-sub">Protection status</p></div>
                </div>
              </div>
              <div class="ud-line"></div>
              <div class="ud-sec-list">
                <div class="ud-sec-row">
                  <div class="ud-sec-left">
                    <span class="ud-sec-check ud-sec-ok"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>
                    <div><p class="ud-sec-title">Email Verified</p><p class="ud-sec-desc">{{ authStore.user.email }}</p></div>
                  </div>
                </div>
                <div class="ud-sec-row">
                  <div class="ud-sec-left">
                    <span class="ud-sec-check ud-sec-ok"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>
                    <div><p class="ud-sec-title">Password Set</p><p class="ud-sec-desc">Last changed: —</p></div>
                  </div>
                </div>
                <div class="ud-sec-row">
                  <div class="ud-sec-left">
                    <span class="ud-sec-check ud-sec-warn"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
                    <div><p class="ud-sec-title">Two-Factor Auth</p><p class="ud-sec-desc">Not enabled</p></div>
                  </div>
                  <router-link to="/user/settings?tab=2fa" class="ud-sec-action">Enable</router-link>
                </div>
                <div class="ud-sec-row">
                  <div class="ud-sec-left">
                    <span class="ud-sec-check ud-sec-warn"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
                    <div><p class="ud-sec-title">Recovery Email</p><p class="ud-sec-desc">Not added yet</p></div>
                  </div>
                  <router-link to="/user/settings?tab=recovery" class="ud-sec-action">Add</router-link>
                </div>
              </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="ud-card ud-pop-item" style="--pop-delay:0.60s">
              <div class="ud-card-head">
                <div class="ud-card-title-row">
                  <span class="ud-card-icon ud-card-icon-green"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                  <div><h3 class="ud-card-title">Recent Activity</h3><p class="ud-card-sub">Account action history</p></div>
                </div>
              </div>
              <div class="ud-line"></div>
              <div class="ud-activity-list">
                <div class="ud-act-row">
                  <div class="ud-act-line-wrap"><span class="ud-act-dot ud-act-dot-green"></span><span class="ud-act-connector"></span></div>
                  <div class="ud-act-body"><p class="ud-act-title">Login successful</p><p class="ud-act-time">Today</p></div>
                </div>
                <div class="ud-act-row">
                  <div class="ud-act-line-wrap"><span class="ud-act-dot ud-act-dot-blue"></span><span class="ud-act-connector"></span></div>
                  <div class="ud-act-body"><p class="ud-act-title">Account created</p><p class="ud-act-time">{{ joinDate }}</p></div>
                </div>
                <div class="ud-act-row">
                  <div class="ud-act-line-wrap"><span :class="['ud-act-dot', isProfileUpdated ? 'ud-act-dot-green' : 'ud-act-dot-dim']"></span><span class="ud-act-connector"></span></div>
                  <div class="ud-act-body"><p :class="['ud-act-title', !isProfileUpdated ? 'ud-act-dim' : '']">Profile updated</p><p class="ud-act-time">{{ isProfileUpdated ? 'Completed' : 'No activity yet' }}</p></div>
                </div>
                <div class="ud-act-row">
                  <div class="ud-act-line-wrap"><span class="ud-act-dot ud-act-dot-dim"></span></div>
                  <div class="ud-act-body"><p class="ud-act-title ud-act-dim">Password changed</p><p class="ud-act-time">No activity yet</p></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Row -->
        <div class="ud-footer-row">
          <div v-if="profileCompletion < 100" class="ud-notice ud-notice-flex ud-pop-item" style="--pop-delay:0.65s">
            <div class="ud-notice-amber-bar"></div>
            <div class="ud-notice-body">
              <div class="ud-notice-left">
                <span class="ud-notice-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
                <div>
                  <p class="ud-notice-t">Profile Incomplete</p>
                  <p class="ud-notice-s">{{ totalFields - filledCount }} field{{ totalFields - filledCount !== 1 ? 's' : '' }} remaining — complete your profile to unlock all features.</p>
                </div>
              </div>
              <div class="ud-notice-right">
                <div class="ud-notice-progress"><div class="ud-notice-prog-fill" :style="{ width: profileCompletion + '%' }"></div></div>
                <span class="ud-notice-pct">{{ profileCompletion }}%</span>
                <router-link to="/user/profile" class="ud-notice-cta">Complete Profile<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></router-link>
              </div>
            </div>
          </div>

          <div class="ud-lastlogin-row">
            <div class="ud-ll-card ud-pop-item" style="--pop-delay:0.70s">
              <div class="ud-ll-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg></div>
              <div class="ud-ll-info"><span class="ud-ll-label">Last Login</span><span class="ud-ll-val">{{ lastLoginStr }}</span></div>
              <span class="ud-ll-badge"><span class="ud-pulse-dot" style="background:var(--green)"></span>Active now</span>
            </div>
            <div class="ud-ll-card ud-pop-item" style="--pop-delay:0.75s">
              <div class="ud-ll-icon" style="color:var(--blue); background:var(--blue-bg);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></div>
              <div class="ud-ll-info"><span class="ud-ll-label">Browser / OS</span><span class="ud-ll-val">{{ browserInfo }}</span></div>
            </div>
            <div class="ud-ll-card ud-pop-item" style="--pop-delay:0.80s">
              <div class="ud-ll-icon" style="color:var(--amber); background:var(--amber-bg);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
              <div class="ud-ll-info"><span class="ud-ll-label">Current Session</span><span class="ud-ll-val">{{ sessionTime }} elapsed</span></div>
            </div>
            <div class="ud-ll-card ud-pop-item" style="--pop-delay:0.85s">
              <div class="ud-ll-icon" style="color:var(--amber); background:var(--amber-bg);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
              <div class="ud-ll-info"><span class="ud-ll-label">Member Since</span><span class="ud-ll-val">{{ joinDate }}</span></div>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, computed, ref, onMounted, onUnmounted, nextTick } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";

const ICON_SVG: Record<string, string> = {
  name:      `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>`,
  email:     `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/></svg>`,
  phone:     `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.08 4.18 2 2 0 0 1 5.09 2h3a2 2 0 0 1 2 1.72c.13 1 .37 1.97.72 2.9a2 2 0 0 1-.45 2.11L9.09 9.91a16 16 0 0 0 6 6l1.18-1.18a2 2 0 0 1 2.11-.45c.93.35 1.9.59 2.9.72A2 2 0 0 1 22 16.92z"/></svg>`,
  job_title: `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2"/></svg>`,
  company:   `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="18" rx="1"/><path d="M9 3v18"/><path d="M9 8h6"/><path d="M9 12h6"/></svg>`,
  bio:       `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>`,
};
const ICON_COLOR: Record<string, string> = {
  name: "ic-default", email: "ic-blue", phone: "ic-green",
  job_title: "ic-default", company: "ic-default", bio: "ic-amber",
};
const QUOTES = [
  { text: "The secret of getting ahead is getting started.", author: "Mark Twain" },
  { text: "Quality is not an act, it is a habit.", author: "Aristotle" },
  { text: "Do what you can, with what you have, where you are.", author: "Theodore Roosevelt" },
  { text: "Small steps every day lead to big changes over time.", author: "Anonymous" },
];

export default defineComponent({
  name: "user-dashboard",
  setup() {
    const authStore = useAuthStore();
    const particleCanvas = ref<HTMLCanvasElement | null>(null);
    const avatarWrap = ref<HTMLElement | null>(null);
    const router = useRouter();

    // Clock
    const now = ref(new Date());
    const hourAngle   = computed(() => ((now.value.getHours() % 12) + now.value.getMinutes() / 60) / 12 * 2 * Math.PI);
    const minuteAngle = computed(() => (now.value.getMinutes() + now.value.getSeconds() / 60) / 60 * 2 * Math.PI);
    const secondAngle = computed(() => now.value.getSeconds() / 60 * 2 * Math.PI);
    const timeStr = computed(() => now.value.toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit", second: "2-digit" }));
    const dateStr = computed(() => now.value.toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short" }));
    const timezone = computed(() => Intl.DateTimeFormat().resolvedOptions().timeZone);

    const sessionStart = Date.now();
    const sessionTime = ref("00:00");
    const pageViews = ref(1);
    const activityBars = ref<number[]>(Array.from({ length: 20 }, () => Math.floor(Math.random() * 30) + 8));

    const quotes = QUOTES;
    const quoteIndex = ref(0);
    const currentQuote = computed(() => quotes[quoteIndex.value]);

    let clockTimer: ReturnType<typeof setInterval>;
    let barTimer: ReturnType<typeof setInterval>;
    let quoteTimer: ReturnType<typeof setInterval>;
    let animFrame: number;
    let resizeHandler: (() => void) | null = null;
    let popObserver: IntersectionObserver | null = null;

    // ✅ FIX: fungsi initPopAnimation terpisah agar bisa dipanggil ulang
    async function initPopAnimation() {
      // Reset semua elemen ke state awal (tersembunyi)
      document.querySelectorAll('.ud-pop-item').forEach(el => {
        el.classList.remove('ud-pop-visible');
      });

      // Tunggu DOM selesai render
      await nextTick();
      await new Promise(r => setTimeout(r, 30));

      // Cleanup observer lama
      if (popObserver) {
        popObserver.disconnect();
        popObserver = null;
      }

      popObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            (entry.target as HTMLElement).classList.add('ud-pop-visible');
            popObserver?.unobserve(entry.target);
          }
        });
      }, { threshold: 0.08, rootMargin: "0px 0px -20px 0px" });

      document.querySelectorAll('.ud-pop-item').forEach(el => popObserver!.observe(el));
    }

    onMounted(async () => {
      clockTimer = setInterval(() => {
        now.value = new Date();
        const elapsed = Math.floor((Date.now() - sessionStart) / 1000);
        const m = Math.floor(elapsed / 60).toString().padStart(2, "0");
        const s = (elapsed % 60).toString().padStart(2, "0");
        sessionTime.value = `${m}:${s}`;
      }, 1000);

      barTimer = setInterval(() => {
        activityBars.value = [...activityBars.value.slice(1), Math.floor(Math.random() * 32) + 8];
        pageViews.value += Math.random() < 0.15 ? 1 : 0;
      }, 900);

      quoteTimer = setInterval(() => {
        quoteIndex.value = (quoteIndex.value + 1) % quotes.length;
      }, 6000);

      // ✅ Inisialisasi pop animation
      await initPopAnimation();

      initParticles();
    });

    onUnmounted(() => {
      clearInterval(clockTimer);
      clearInterval(barTimer);
      clearInterval(quoteTimer);
      if (animFrame) cancelAnimationFrame(animFrame);
      if (resizeHandler) window.removeEventListener("resize", resizeHandler);
      // ✅ Cleanup observer saat component unmount
      if (popObserver) {
        popObserver.disconnect();
        popObserver = null;
      }
    });

    function initParticles() {
      const canvas = particleCanvas.value;
      if (!canvas) return;
      const ctx = canvas.getContext("2d");
      if (!ctx) return;

      function resize() { if (!canvas) return; canvas.width = canvas.offsetWidth; canvas.height = canvas.offsetHeight; }
      resize();
      resizeHandler = resize;
      window.addEventListener("resize", resizeHandler);

      const COLORS = ["rgba(91,156,246,", "rgba(240,167,50,", "rgba(62,207,114,"];
      const particles: { x: number; y: number; vx: number; vy: number; r: number; alpha: number; color: string }[] = [];
      for (let i = 0; i < 55; i++) {
        particles.push({ x: Math.random() * canvas.width, y: Math.random() * canvas.height, vx: (Math.random() - 0.5) * 0.3, vy: (Math.random() - 0.5) * 0.3, r: Math.random() * 1.6 + 0.4, alpha: Math.random() * 0.5 + 0.1, color: COLORS[Math.floor(Math.random() * COLORS.length)] });
      }

      function draw() {
        if (!canvas || !ctx) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (let i = 0; i < particles.length; i++) {
          const p = particles[i];
          p.x += p.vx; p.y += p.vy;
          if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
          if (p.y < 0 || p.y > canvas.height) p.vy *= -1;
          ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2); ctx.fillStyle = p.color + p.alpha + ")"; ctx.fill();
          for (let j = i + 1; j < particles.length; j++) {
            const q = particles[j]; const dx = p.x - q.x, dy = p.y - q.y; const dist = Math.sqrt(dx*dx + dy*dy);
            if (dist < 110) { ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y); ctx.strokeStyle = p.color + (0.06 * (1 - dist / 110)) + ")"; ctx.lineWidth = 0.5; ctx.stroke(); }
          }
        }
        animFrame = requestAnimationFrame(draw);
      }
      draw();
    }

    function rippleEffect(e: MouseEvent) {
      const el = e.currentTarget as HTMLElement;
      const old = el.querySelector(".ud-ripple-el"); if (old) old.remove();
      const span = document.createElement("span"); span.className = "ud-ripple-el";
      const rect = el.getBoundingClientRect(); const size = Math.max(rect.width, rect.height) * 2;
      span.style.cssText = `position:absolute;border-radius:50%;background:rgba(240,167,50,.08);transform:scale(0);animation:ud-ripple-anim .6s linear forwards;pointer-events:none;width:${size}px;height:${size}px;left:${e.clientX - rect.left - size / 2}px;top:${e.clientY - rect.top - size / 2}px;`;
      el.appendChild(span); setTimeout(() => span.remove(), 600);
    }

    function triggerAvatarPulse() {
      const el = avatarWrap.value; if (!el) return;
      el.classList.remove("ud-avatar-bounce"); void el.offsetWidth; el.classList.add("ud-avatar-bounce");
    }

    const avatarUrl = computed(() => authStore.user.avatar ? `${import.meta.env.VITE_APP_API_URL?.replace('/api', '')}/storage/${authStore.user.avatar}` : null);
    const fieldConfig = [
      { key: "name", label: "Full Name" }, { key: "email", label: "Email" },
      { key: "phone", label: "Phone Number" }, { key: "job_title", label: "Job Title" },
      { key: "company", label: "Company" }, { key: "bio", label: "Bio" },
    ];
    const profileFields = computed(() => fieldConfig.map(f => ({ ...f, value: (authStore.user as any)[f.key] || "", iconSvg: ICON_SVG[f.key], iconColor: ICON_COLOR[f.key] })));
    const fieldStatus = computed(() => fieldConfig.map(f => ({ key: f.key, label: f.label, filled: !!(authStore.user as any)[f.key] })));
    const filledCount = computed(() => fieldStatus.value.filter(f => f.filled).length);
    const totalFields = fieldConfig.length;
    const profileCompletion = computed(() => Math.round((filledCount.value / totalFields) * 100));
    const joinDate = computed(() => { const d = (authStore.user as any).created_at; if (!d) return "—"; return new Date(d).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" }); });
    const lastLoginStr = computed(() => now.value.toLocaleString("en-GB", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }));
    const browserInfo = computed(() => {
      const ua = navigator.userAgent;
      let browser = "Browser";
      if (ua.includes("Chrome") && !ua.includes("Edg")) browser = "Chrome"; else if (ua.includes("Firefox")) browser = "Firefox"; else if (ua.includes("Safari") && !ua.includes("Chrome")) browser = "Safari"; else if (ua.includes("Edg")) browser = "Edge";
      let os = "Unknown OS";
      if (ua.includes("Windows")) os = "Windows"; else if (ua.includes("Mac")) os = "macOS"; else if (ua.includes("Linux")) os = "Linux";
      return `${browser} · ${os}`;
    });
    const isProfileUpdated = computed(() => { const u = authStore.user as any; return !!(u.phone || u.bio || u.job_title || u.company || u.avatar); });

    async function onLogout() {
      try { await authStore.logout(); localStorage.removeItem("token"); await router.replace({ name: "sign-in" }); }
      catch (err) { window.location.href = "/sign-in"; }
    }

    return {
      authStore, avatarUrl, profileCompletion, profileFields, fieldStatus,
      filledCount, totalFields, joinDate, particleCanvas, avatarWrap,
      hourAngle, minuteAngle, secondAngle, timeStr, dateStr, timezone,
      sessionTime, pageViews, activityBars, quotes, quoteIndex, currentQuote,
      rippleEffect, triggerAvatarPulse, lastLoginStr, browserInfo, onLogout, isProfileUpdated,
    };
  },
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

:global(@keyframes ud-ripple-anim) { to { transform: scale(1); opacity: 0; } }

/* ════ POP-UP ANIMATION ════ */
:global(.ud-pop-item) {
  opacity: 0;
  transform: translateY(22px) scale(0.97);
  transition: opacity 0.45s cubic-bezier(0.16,1,0.3,1), transform 0.45s cubic-bezier(0.16,1,0.3,1);
  transition-delay: var(--pop-delay, 0s);
}
:global(.ud-pop-item.ud-pop-visible) {
  opacity: 1;
  transform: translateY(0) scale(1);
}

.ud-root {
  --bg: #0a0a0a; --s1: #111111; --s2: #161616; --s3: #1d1d1d;
  --border: #222222; --border2: #2e2e2e; --muted: #3a3a3a; --soft: #606060;
  --text: #999999; --head: #e0e0e0; --white: #f0f0f0;
  --green: #3ecf72; --green-bg: rgba(62,207,114,.1); --green-bd: rgba(62,207,114,.2);
  --blue: #5b9cf6; --blue-bg: rgba(91,156,246,.1); --blue-bd: rgba(91,156,246,.2);
  --amber: #f0a732; --amber-bg: rgba(240,167,50,.08); --amber-bd: rgba(240,167,50,.2);
  font-family: 'Inter', sans-serif; background: var(--bg); min-height: 100vh;
  padding: 1.5rem; color: var(--text); position: relative; overflow-x: hidden;
}

.ud-particle-canvas { position: fixed; inset: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; }
.ud-layout { display: flex; gap: 1.25rem; max-width: 1400px; margin: 0 auto; align-items: flex-start; position: relative; z-index: 1; }

.ud-sidebar { width: 265px; flex-shrink: 0; background: var(--s1); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem 1.25rem; display: flex; flex-direction: column; gap: .85rem; position: sticky; top: 1.5rem; }
.ud-identity { display: flex; flex-direction: column; align-items: center; text-align: center; gap: .45rem; }
.ud-avatar-wrap { position: relative; width: 74px; height: 74px; cursor: pointer; }
@keyframes avatarBounce { 0%{transform:scale(1)} 35%{transform:scale(1.12)} 65%{transform:scale(.96)} 100%{transform:scale(1)} }
.ud-avatar-bounce { animation: avatarBounce .4s ease; }
.ud-avatar-ring { position: absolute; inset: -4px; border-radius: 50%; border: 1.5px solid transparent; background: linear-gradient(135deg, var(--amber), var(--blue), var(--green)) border-box; -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0); -webkit-mask-composite: destination-out; mask-composite: exclude; animation: ringRotate 4s linear infinite; }
@keyframes ringRotate { to { filter: hue-rotate(360deg); } }
.ud-avatar-img, .ud-avatar-initials { width: 74px; height: 74px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: 700; background: var(--s3); color: var(--head); border: 1px solid var(--border2); }
.ud-online-dot { position: absolute; bottom: 3px; right: 3px; width: 11px; height: 11px; border-radius: 50%; background: var(--green); border: 2px solid var(--s1); }
.ud-username { font-size: .92rem; font-weight: 600; color: var(--head); margin: 0; }
.ud-useremail { font-size: .68rem; color: var(--soft); margin: 0; word-break: break-all; }
.ud-role-tag { display: inline-flex; align-items: center; gap: .3rem; font-size: .63rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; padding: .2rem .65rem; border-radius: 100px; background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-bd); }
.ud-completion-block { display: flex; align-items: center; gap: .85rem; }
.ud-completion-circle { position: relative; flex-shrink: 0; }
.ud-progress-arc { transition: stroke-dashoffset .8s ease; }
.ud-completion-inner { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.ud-completion-pct { font-size: .82rem; font-weight: 700; color: var(--head); line-height: 1; }
.ud-completion-lbl { font-size: .55rem; color: var(--soft); text-transform: uppercase; letter-spacing: .06em; }
.ud-completion-info { flex: 1; }
.ud-ci-title { font-size: .73rem; font-weight: 600; color: var(--head); margin: 0 0 .15rem; }
.ud-ci-sub { font-size: .63rem; color: var(--soft); margin: 0 0 .5rem; }
.ud-mini-bar { height: 3px; background: var(--border); border-radius: 2px; overflow: hidden; }
.ud-mini-fill { height: 100%; border-radius: 2px; transition: width .6s ease; }
.ud-line { height: 1px; background: var(--border); }
.ud-block { display: flex; flex-direction: column; gap: .5rem; }
.ud-block-label { font-size: .58rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin: 0; }
.ud-checklist { display: grid; grid-template-columns: 1fr 1fr; gap: .28rem .5rem; }
.ud-check-item { display: flex; align-items: center; gap: .38rem; font-size: .67rem; color: var(--soft); }
.ud-check-icon { width: 14px; height: 14px; border-radius: 3px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ud-check-ok { background: var(--green-bg); color: var(--green); }
.ud-check-no { background: var(--s3); color: var(--muted); }
.ud-check-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ud-kv-list { display: flex; flex-direction: column; gap: .32rem; }
.ud-kv { display: flex; justify-content: space-between; align-items: center; }
.ud-kv-key { font-size: .68rem; color: var(--soft); }
.ud-kv-val { font-size: .68rem; font-weight: 500; color: var(--text); font-family: 'JetBrains Mono', monospace; }
.ud-kv-green { color: var(--green); }
.ud-capitalize { text-transform: capitalize; }
.ud-action-item { display: flex; align-items: center; gap: .65rem; padding: .5rem .65rem; border-radius: 8px; border: 1px solid transparent; cursor: pointer; text-decoration: none; color: var(--text); font-size: .73rem; font-weight: 500; transition: background .15s, border-color .15s, color .15s; }
.ud-action-item:hover { background: var(--s3); border-color: var(--border); color: var(--head); }
.ud-ai-icon { width: 26px; height: 26px; border-radius: 6px; background: var(--s3); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ud-ai-label { flex: 1; }
.ud-score-wrap { display: flex; flex-direction: column; gap: .4rem; margin-bottom: .5rem; }
.ud-score-bar-track { height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; }
.ud-score-bar-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, var(--amber), #f5c542); transition: width .8s ease; }
.ud-score-labels { display: flex; justify-content: space-between; align-items: center; }
.ud-score-val { font-size: .78rem; font-weight: 700; color: var(--head); font-family: 'JetBrains Mono', monospace; }
.ud-score-max { font-size: .6rem; color: var(--soft); font-weight: 400; }
.ud-score-grade { font-size: .62rem; font-weight: 600; padding: .15rem .5rem; border-radius: 4px; }
.ud-grade-warn { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-bd); }
.ud-score-items { display: flex; flex-direction: column; gap: .3rem; }
.ud-si { display: flex; align-items: center; gap: .5rem; }
.ud-si-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.ud-si-ok { background: var(--green); }
.ud-si-no { background: var(--muted); }
.ud-si-lbl { font-size: .67rem; color: var(--soft); flex: 1; }
.ud-si-pts { font-size: .63rem; font-weight: 600; font-family: 'JetBrains Mono', monospace; }
.ud-si-green { color: var(--green); }
.ud-si-muted { color: var(--muted); }
.ud-logout-btn { display: flex; align-items: center; gap: .65rem; width: 100%; padding: .6rem .75rem; background: var(--s2); border: 1px solid var(--border2); border-radius: 9px; cursor: pointer; text-align: left; transition: background .2s, border-color .2s; }
.ud-logout-btn:hover { background: var(--s3); border-color: var(--soft); }
.ud-logout-icon { width: 28px; height: 28px; border-radius: 7px; flex-shrink: 0; background: var(--s3); border: 1px solid var(--border2); display: flex; align-items: center; justify-content: center; color: var(--soft); transition: color .2s, background .2s; }
.ud-logout-btn:hover .ud-logout-icon { color: var(--head); background: var(--muted); }
.ud-logout-text { display: flex; flex-direction: column; flex: 1; gap: .05rem; }
.ud-logout-title { font-size: .72rem; font-weight: 600; color: var(--head); }
.ud-logout-sub { font-size: .6rem; color: var(--soft); }
.ud-logout-arrow { color: var(--muted); flex-shrink: 0; }

.ud-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1.1rem; }
.ud-banner { background: var(--s1); border: 1px solid var(--border); border-radius: 16px; padding: 1.6rem 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; position: relative; overflow: hidden; }
.ud-banner-glow { position: absolute; top: -60px; right: -60px; width: 220px; height: 220px; border-radius: 50%; background: radial-gradient(circle, rgba(91,156,246,.07) 0%, transparent 70%); pointer-events: none; }
.ud-banner-glow2 { top: auto; bottom: -80px; right: 120px; width: 180px; height: 180px; background: radial-gradient(circle, rgba(240,167,50,.06) 0%, transparent 70%); }
.ud-banner-content { flex: 1; }
.ud-banner-greeting { font-size: .68rem; color: var(--soft); letter-spacing: .06em; text-transform: uppercase; margin: 0 0 .2rem; }
.ud-banner-name { font-size: 1.5rem; font-weight: 700; color: var(--white); margin: 0 0 .3rem; letter-spacing: -.02em; }
.ud-banner-sub { font-size: .72rem; color: var(--muted); margin: 0; }
.ud-banner-badges { display: flex; align-items: center; gap: .6rem; flex-shrink: 0; flex-wrap: wrap; justify-content: flex-end; }
.ud-bbadge { display: inline-flex; align-items: center; gap: .35rem; font-size: .68rem; font-weight: 600; padding: .3rem .75rem; border-radius: 100px; white-space: nowrap; }
.ud-bbadge-green { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-bd); }
.ud-bbadge-blue { background: var(--blue-bg); color: var(--blue); border: 1px solid var(--blue-bd); }
.ud-bbadge-amber { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-bd); }
.ud-pulse-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); animation: pulse 2s ease-in-out infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
.ud-banner-editbtn { display: inline-flex; align-items: center; gap: .4rem; font-size: .7rem; font-weight: 500; color: var(--text); background: var(--s3); border: 1px solid var(--border2); padding: .35rem .8rem; border-radius: 8px; text-decoration: none; transition: border-color .2s, color .15s; }
.ud-banner-editbtn:hover { border-color: var(--soft); color: var(--head); }
.ud-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .85rem; }
.ud-sc { background: var(--s1); border: 1px solid var(--border); border-radius: 12px; padding: 1rem 1.1rem; display: flex; align-items: center; gap: .8rem; transition: border-color .2s, transform .15s; }
.ud-sc:hover { border-color: var(--border2); transform: translateY(-2px); }
.ud-sc-icon { width: 34px; height: 34px; border-radius: 9px; background: var(--s3); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--soft); }
.ud-sc-accent-green { border-color: var(--green-bd); } .ud-sc-accent-green .ud-sc-icon { background: var(--green-bg); color: var(--green); border-color: var(--green-bd); } .ud-sc-accent-green .ud-sc-val { color: var(--green); }
.ud-sc-accent-blue { border-color: var(--blue-bd); } .ud-sc-accent-blue .ud-sc-icon { background: var(--blue-bg); color: var(--blue); border-color: var(--blue-bd); } .ud-sc-accent-blue .ud-sc-val { color: var(--blue); }
.ud-sc-accent-amber { border-color: var(--amber-bd); } .ud-sc-accent-amber .ud-sc-icon { background: var(--amber-bg); color: var(--amber); border-color: var(--amber-bd); } .ud-sc-accent-amber .ud-sc-val { color: var(--amber); }
.ud-sc-info { display: flex; flex-direction: column; gap: .1rem; }
.ud-sc-val { font-size: .82rem; font-weight: 600; color: var(--head); }
.ud-sc-lbl { font-size: .63rem; color: var(--soft); }
.ud-feature-row { display: grid; grid-template-columns: 200px 1fr 220px; gap: 1.1rem; }
@media (max-width: 1100px) { .ud-feature-row { grid-template-columns: 200px 1fr; } .ud-quote-card { display: none; } }
@media (max-width: 800px) { .ud-feature-row { grid-template-columns: 1fr; } }
.ud-clock-card { background: var(--s1); border: 1px solid var(--border); border-radius: 16px; padding: 1.25rem; display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden; }
.ud-clock-bg { position: absolute; inset: 0; background: radial-gradient(circle at 50% 50%, var(--amber-bg) 0%, transparent 70%); pointer-events: none; }
.ud-clock-inner { display: flex; flex-direction: column; align-items: center; gap: .75rem; position: relative; z-index: 1; }
.ud-clock-svg { filter: drop-shadow(0 0 8px rgba(240,167,50,.15)); }
.ud-clock-text { display: flex; flex-direction: column; align-items: center; gap: .1rem; }
.ud-clock-time { font-size: 1.1rem; font-weight: 700; color: var(--head); font-family: 'JetBrains Mono', monospace; letter-spacing: .06em; }
.ud-clock-date { font-size: .68rem; color: var(--text); }
.ud-clock-zone { font-size: .6rem; color: var(--muted); font-family: 'JetBrains Mono', monospace; }
.ud-session-card { background: var(--s1); border: 1px solid var(--border); border-radius: 16px; padding: 1.25rem; }
.ud-session-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; margin-bottom: 1rem; }
.ud-sess-item { display: flex; align-items: center; gap: .65rem; padding: .7rem .85rem; background: var(--s2); border: 1px solid var(--border); border-radius: 10px; transition: border-color .2s; }
.ud-sess-item:hover { border-color: var(--border2); }
.ud-sess-icon { width: 28px; height: 28px; border-radius: 7px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ud-sess-val { font-size: .8rem; font-weight: 600; color: var(--head); display: block; font-family: 'JetBrains Mono', monospace; }
.ud-sess-lbl { font-size: .6rem; color: var(--soft); display: block; }
.ud-live-bar-label { display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; font-size: .62rem; color: var(--soft); }
.ud-live-dot { display: flex; align-items: center; gap: .3rem; font-size: .62rem; color: var(--green); }
.ud-bars { display: flex; align-items: flex-end; gap: 2px; height: 42px; }
.ud-bar-col { flex: 1; background: var(--amber); border-radius: 2px 2px 0 0; min-height: 4px; transition: height .35s ease, opacity .35s ease; }
.ud-quote-card { background: var(--s1); border: 1px solid var(--amber-bd); border-radius: 16px; padding: 1.4rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; }
.ud-quote-glow { position: absolute; top: -30px; left: -30px; width: 130px; height: 130px; border-radius: 50%; background: radial-gradient(circle, rgba(240,167,50,.12) 0%, transparent 70%); pointer-events: none; }
.ud-quote-mark { font-size: 3.5rem; line-height: .8; color: var(--amber); font-family: Georgia, serif; opacity: .4; margin-bottom: .5rem; }
.ud-quote-text { font-size: .78rem; color: var(--head); line-height: 1.55; flex: 1; margin: 0 0 1rem; font-style: italic; }
.ud-quote-author { display: flex; align-items: center; gap: .6rem; font-size: .65rem; color: var(--amber); font-weight: 600; }
.ud-qa-line { width: 18px; height: 1px; background: var(--amber); }
.ud-quote-dots { display: flex; gap: .4rem; margin-top: .75rem; }
.ud-qdot { width: 5px; height: 5px; border-radius: 50%; background: var(--muted); cursor: pointer; transition: background .2s, transform .2s; }
.ud-qdot-active { background: var(--amber); transform: scale(1.3); }
.ud-mid-row { display: flex; gap: 1.1rem; align-items: stretch; }
.ud-col-left { display: flex; flex-direction: column; gap: 1.1rem; flex: 1; min-width: 0; }
.ud-col-right { display: flex; flex-direction: column; gap: 1.1rem; width: 295px; flex-shrink: 0; }
.ud-col-right .ud-card { flex: 1; }
.ud-tips-card { flex: 1; display: flex; flex-direction: column; }
.ud-tips-line { flex-shrink: 0; margin-bottom: .85rem; }
.ud-tips-grid { flex: 1; display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; gap: .75rem; }
.ud-tip-item { display: flex; align-items: flex-start; gap: .65rem; padding: .85rem .95rem; background: var(--s2); border: 1px solid var(--border); border-radius: 10px; transition: border-color .2s, background .15s; }
.ud-tip-item:hover { border-color: var(--border2); background: var(--s3); }
.ud-tip-icon { width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; margin-top: 1px; }
.ud-tip-title { font-size: .73rem; font-weight: 600; color: var(--head); margin: 0 0 .25rem; }
.ud-tip-desc { font-size: .62rem; color: var(--soft); margin: 0; line-height: 1.5; }
.ud-card { background: var(--s1); border: 1px solid var(--border); border-radius: 16px; padding: 1.25rem; }
.ud-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .85rem; }
.ud-card-title-row { display: flex; align-items: center; gap: .7rem; }
.ud-card-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--s3); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--soft); flex-shrink: 0; }
.ud-card-icon-blue { background: var(--blue-bg); border-color: var(--blue-bd); color: var(--blue); }
.ud-card-icon-green { background: var(--green-bg); border-color: var(--green-bd); color: var(--green); }
.ud-card-icon-amber { background: var(--amber-bg); border-color: var(--amber-bd); color: var(--amber); }
.ud-card-title { font-size: .85rem; font-weight: 600; color: var(--head); margin: 0; }
.ud-card-sub { font-size: .63rem; color: var(--soft); margin: .08rem 0 0; }
.ud-ghost-btn { display: inline-flex; align-items: center; gap: .35rem; font-size: .68rem; font-weight: 500; color: var(--text); background: var(--s3); border: 1px solid var(--border); padding: .32rem .7rem; border-radius: 7px; text-decoration: none; transition: border-color .2s, color .15s; white-space: nowrap; }
.ud-ghost-btn:hover { border-color: var(--border2); color: var(--head); }
.ud-pf-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: var(--border); border-radius: 10px; overflow: hidden; }
.ud-pf-cell { background: var(--s2); padding: .9rem 1rem; display: flex; flex-direction: column; gap: .4rem; transition: background .15s; position: relative; overflow: hidden; }
.ud-pf-cell:hover { background: var(--s3); }
.ud-pf-top { display: flex; align-items: center; gap: .5rem; }
.ud-pf-icon { display: flex; align-items: center; flex-shrink: 0; }
.ic-default { color: var(--muted); } .ic-blue { color: var(--blue); } .ic-green { color: var(--green); } .ic-amber { color: var(--amber); }
.ud-pf-label { font-size: .62rem; font-weight: 600; color: var(--soft); text-transform: uppercase; letter-spacing: .06em; }
.ud-pf-val { font-size: .82rem; font-weight: 500; color: var(--head); margin: 0; }
.ud-pf-empty { font-size: .72rem; color: var(--muted); margin: 0; font-style: italic; }
.ud-sec-list { display: flex; flex-direction: column; gap: .7rem; }
.ud-sec-row { display: flex; align-items: center; justify-content: space-between; }
.ud-sec-left { display: flex; align-items: center; gap: .65rem; }
.ud-sec-check { width: 22px; height: 22px; border-radius: 6px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.ud-sec-ok { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-bd); }
.ud-sec-warn { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-bd); }
.ud-sec-title { font-size: .73rem; font-weight: 500; color: var(--head); margin: 0; }
.ud-sec-desc { font-size: .63rem; color: var(--soft); margin: .08rem 0 0; }
.ud-sec-action { font-size: .63rem; font-weight: 600; color: var(--amber); background: var(--amber-bg); border: 1px solid var(--amber-bd); border-radius: 5px; padding: .2rem .55rem; cursor: pointer; white-space: nowrap; transition: background .2s; text-decoration: none; }
.ud-sec-action:hover { background: rgba(240,167,50,.16); }
.ud-activity-list { display: flex; flex-direction: column; }
.ud-act-row { display: flex; align-items: flex-start; gap: .75rem; padding: .5rem 0; border-bottom: 1px solid var(--border); }
.ud-act-row:last-child { border-bottom: none; }
.ud-act-line-wrap { display: flex; flex-direction: column; align-items: center; padding-top: 4px; flex-shrink: 0; }
.ud-act-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.ud-act-connector { width: 1px; flex: 1; background: var(--border); margin-top: 3px; min-height: 16px; }
.ud-act-dot-green { background: var(--green); box-shadow: 0 0 6px rgba(62,207,114,.4); }
.ud-act-dot-blue { background: var(--blue); box-shadow: 0 0 6px rgba(91,156,246,.3); }
.ud-act-dot-dim { background: var(--muted); }
.ud-act-body { display: flex; flex-direction: column; gap: .1rem; padding-bottom: .25rem; }
.ud-act-title { font-size: .73rem; font-weight: 500; color: var(--head); margin: 0; }
.ud-act-dim { color: var(--soft); }
.ud-act-time { font-size: .62rem; color: var(--muted); margin: 0; font-family: 'JetBrains Mono', monospace; }
.ud-footer-row { display: flex; flex-direction: column; gap: .85rem; width: 100%; }
.ud-notice { background: var(--s1); border: 1px solid var(--amber-bd); border-radius: 14px; overflow: hidden; position: relative; width: 100%; }
.ud-notice-flex { display: flex; flex-direction: column; }
.ud-notice-amber-bar { height: 3px; background: linear-gradient(90deg, var(--amber), transparent); }
.ud-notice-body { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.5rem; }
.ud-notice-left { display: flex; align-items: center; gap: .75rem; }
.ud-notice-icon { color: var(--amber); display: flex; align-items: center; flex-shrink: 0; }
.ud-notice-t { font-size: .8rem; font-weight: 600; color: var(--head); margin: 0; }
.ud-notice-s { font-size: .67rem; color: var(--soft); margin: .1rem 0 0; }
.ud-notice-right { display: flex; align-items: center; gap: .85rem; flex-shrink: 0; }
.ud-notice-progress { width: 120px; height: 4px; background: var(--border); border-radius: 2px; overflow: hidden; }
.ud-notice-prog-fill { height: 100%; background: var(--amber); border-radius: 2px; transition: width .6s ease; }
.ud-notice-pct { font-size: .7rem; font-weight: 700; color: var(--amber); font-family: 'JetBrains Mono', monospace; white-space: nowrap; }
.ud-notice-cta { display: inline-flex; align-items: center; gap: .35rem; font-size: .7rem; font-weight: 600; color: var(--bg); background: var(--amber); padding: .38rem .85rem; border-radius: 8px; text-decoration: none; transition: opacity .2s, transform .15s; white-space: nowrap; }
.ud-notice-cta:hover { opacity: .88; transform: translateY(-1px); }
.ud-lastlogin-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; width: 100%; }
.ud-ll-card { background: var(--s1); border: 1px solid var(--border); border-radius: 12px; padding: .9rem 1.1rem; display: flex; align-items: center; gap: .75rem; transition: border-color .2s, transform .15s; min-width: 0; }
.ud-ll-card:hover { border-color: var(--border2); transform: translateY(-1px); }
.ud-ll-icon { width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0; background: var(--green-bg); color: var(--green); display: flex; align-items: center; justify-content: center; border: 1px solid var(--green-bd); }
.ud-ll-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .1rem; }
.ud-ll-label { font-size: .6rem; color: var(--soft); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }
.ud-ll-val { font-size: .76rem; font-weight: 500; color: var(--head); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: 'JetBrains Mono', monospace; }
.ud-ll-badge { display: inline-flex; align-items: center; gap: .35rem; font-size: .62rem; font-weight: 600; color: var(--green); background: var(--green-bg); border: 1px solid var(--green-bd); padding: .2rem .55rem; border-radius: 100px; white-space: nowrap; flex-shrink: 0; }

@media (max-width: 900px) { .ud-stats-grid { grid-template-columns: repeat(2,1fr); } .ud-tips-grid { grid-template-columns: 1fr; } .ud-lastlogin-row { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 960px) { .ud-mid-row { flex-direction: column; } .ud-col-right, .ud-col-left { width: 100%; } }
@media (max-width: 768px) { .ud-layout { flex-direction: column; } .ud-sidebar { width: 100%; position: static; } }
@media (max-width: 520px) { .ud-lastlogin-row { grid-template-columns: 1fr; } }
</style>