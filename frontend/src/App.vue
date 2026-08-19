<template>
  <div class="app-wrapper" @click="closeUserMenuOnClickOutside">
    <!-- TOP NAVBAR -->
    <header class="navbar">
      <div class="nav-content">
        <!-- LOGO & BRAND -->
        <router-link to="/" class="brand-group">
          <div class="logo-box">
            <span class="logo-icon">🐾</span>
          </div>
          <div class="brand-text">
            <div class="brand-title">
              RefuGuía <span class="version-tag">SLM 2.5</span>
            </div>
            <div class="brand-sub">Sistema Agéntico Post-Sismo</div>
          </div>
        </router-link>

        <!-- DYNAMIC NAV MENU ACCORDING TO ROLE -->
        <nav class="nav-menu">
          <!-- SIEMPRE VISIBLE: CHAT CIUDADANO -->
          <router-link to="/" class="nav-pill" active-class="nav-pill-active">
            <span class="pill-icon">💬</span>
            <span class="pill-label">Chat Ciudadano</span>
          </router-link>

          <!-- REFUGIOS & QR: shelter_admin y rescuer -->
          <router-link 
            v-if="hasRole(['shelter_admin', 'rescuer'])" 
            to="/refugios" 
            class="nav-pill" 
            active-class="nav-pill-active"
          >
            <span class="pill-icon">🏥</span>
            <span class="pill-label">Refugios & QR</span>
          </router-link>

          <!-- MATCHMAKER HUB: shelter_admin, rescuer, citizen -->
          <router-link 
            v-if="hasRole(['shelter_admin', 'rescuer', 'citizen'])" 
            to="/matches" 
            class="nav-pill" 
            active-class="nav-pill-active"
          >
            <span class="pill-icon">⚡</span>
            <span class="pill-label">Matchmaker Hub</span>
          </router-link>

          <!-- ADOPCION: publico, adopter, shelter_admin -->
          <router-link to="/adopcion" class="nav-pill" active-class="nav-pill-active">
            <span class="pill-icon">❤️</span>
            <span class="pill-label">Adopción (15d)</span>
          </router-link>

          <!-- MCP & SKILLS: EXCLUSIVO COORDINADORA / SHELTER_ADMIN -->
          <router-link 
            v-if="hasRole('shelter_admin')" 
            to="/mcp" 
            class="nav-pill" 
            active-class="nav-pill-active"
          >
            <span class="pill-icon">🛠️</span>
            <span class="pill-label">MCP & Skills</span>
          </router-link>

          <!-- SLM LOCAL TERMINAL: EXCLUSIVO COORDINADORA / SHELTER_ADMIN -->
          <router-link 
            v-if="hasRole('shelter_admin')" 
            to="/terminal-slm" 
            class="nav-pill" 
            active-class="nav-pill-active"
          >
            <span class="pill-icon">💻</span>
            <span class="pill-label">SLM Local</span>
          </router-link>
        </nav>

        <!-- USER PROFILE & LOGIN / ACCOUNT MENU -->
        <div class="user-session-group">
          <!-- LOGGED IN USER CHIP WITH DROPDOWN TRIGGER -->
          <div v-if="currentUser" class="user-account-wrapper">
            <div 
              class="user-profile-chip" 
              @click.stop="toggleUserMenu"
              :class="{ 'chip-active': isUserMenuOpen }"
            >
              <div class="user-avatar-circle">
                {{ getUserInitials(currentUser.name) }}
              </div>
              <div class="user-info-text">
                <span class="user-name">{{ currentUser.name }}</span>
                <span :class="['badge', getRoleBadgeClass(currentUser.role)]">
                  {{ currentUser.role_label || currentUser.role }}
                </span>
              </div>
              <span class="dropdown-chevron">▼</span>
            </div>

            <!-- ELEGANT & CLEAN DROPDOWN (NO REDUNDANT DATA) -->
            <div v-if="isUserMenuOpen" class="user-dropdown-menu glass-card" @click.stop>
              <div class="dropdown-meta-box">
                <div class="user-email-text">{{ currentUser.email }}</div>
                <div class="status-indicator">
                  <span class="dot-online"></span> Sesión activa segura
                </div>
              </div>

              <div class="dropdown-divider"></div>

              <div class="dropdown-actions">
                <button class="btn-dropdown-action" @click="openLoginForSwitch">
                  <span class="action-icon">🔄</span>
                  <span>Cambiar de Usuario / Rol</span>
                </button>

                <button class="btn-dropdown-logout" @click="handleLogout">
                  <span class="action-icon">🚪</span>
                  <span>Cerrar Sesión</span>
                </button>
              </div>
            </div>
          </div>

          <!-- LOGIN BUTTON IF GUEST -->
          <button v-else class="btn-login-trigger" @click="showLoginModal = true">
            <span>🔑 Iniciar Sesión</span>
          </button>
        </div>
      </div>
    </header>

    <!-- ROLE RESTRICTION NOTICE BANNER IF GUEST -->
    <div v-if="!currentUser" class="role-hint-banner">
      <span>💡 <strong>Modo Visitante:</strong> Inicia sesión con el rol de <strong>Coordinadora de Refugio</strong> para habilitar las secciones técnicas avanzadas (<em>MCP & Skills</em> y <em>SLM Local</em>).</span>
      <button class="btn-banner-login" @click="showLoginModal = true">Cambiar de Rol</button>
    </div>

    <!-- MAIN VIEW -->
    <main class="page-container">
      <router-view />
    </main>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="footer-inner">
        <div>
          <strong>RefuGuía</strong> — Proyecto Final IA Aplicada a Organizaciones (UTN-FRBA & EPIData)
        </div>
        <div class="footer-badges">
          <span class="badge badge-primary">100% IA Local (SLM)</span>
          <span class="badge badge-cyan">Protocolo MCP</span>
          <span class="badge badge-emerald">Control de Acceso RBAC</span>
        </div>
      </div>
    </footer>

    <!-- LOGIN MODAL -->
    <LoginModal :isOpen="showLoginModal" @close="showLoginModal = false" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from './services/auth'
import LoginModal from './components/LoginModal.vue'

const router = useRouter()
const { currentUser, logout, hasRole } = useAuth()
const showLoginModal = ref(false)
const isUserMenuOpen = ref(false)

const toggleUserMenu = () => {
  isUserMenuOpen.value = !isUserMenuOpen.value
}

const closeUserMenuOnClickOutside = () => {
  isUserMenuOpen.value = false
}

const openLoginForSwitch = () => {
  isUserMenuOpen.value = false
  showLoginModal.value = true
}

const handleLogout = () => {
  isUserMenuOpen.value = false
  logout()
  router.push('/')
}

const getUserInitials = (name) => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
}

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'shelter_admin': return 'badge-rose'
    case 'rescuer': return 'badge-amber'
    case 'citizen': return 'badge-cyan'
    case 'adopter': return 'badge-emerald'
    default: return 'badge-primary'
  }
}
</script>

<style scoped>
.app-wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.navbar {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: rgba(7, 10, 19, 0.85);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
}

.nav-content {
  max-width: 1440px;
  margin: 0 auto;
  padding: 0.65rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
}

.brand-group {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.logo-box {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(6, 182, 212, 0.25));
  border: 1px solid rgba(99, 102, 241, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
}

.brand-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.version-tag {
  font-size: 0.65rem;
  padding: 1px 6px;
  background: rgba(6, 182, 212, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(6, 182, 212, 0.3);
  border-radius: var(--radius-full);
}

.brand-sub {
  font-size: 0.68rem;
  color: var(--text-muted);
}

.nav-menu {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(18, 28, 48, 0.5);
  padding: 4px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border);
}

.nav-pill {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.85rem;
  border-radius: 10px;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--text-secondary);
  white-space: nowrap;
}

.nav-pill:hover {
  color: var(--text-main);
  background: rgba(255, 255, 255, 0.05);
}

.nav-pill-active {
  color: #ffffff !important;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.4), rgba(79, 70, 229, 0.3)) !important;
  border: 1px solid rgba(99, 102, 241, 0.5);
}

.user-session-group {
  display: flex;
  align-items: center;
}

.user-account-wrapper {
  position: relative;
}

.btn-login-trigger {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(79, 70, 229, 0.25));
  border: 1px solid rgba(99, 102, 241, 0.4);
  color: #a5b4fc;
  padding: 0.5rem 1.1rem;
  border-radius: var(--radius-md);
  font-weight: 700;
  font-size: 0.82rem;
  cursor: pointer;
}

.btn-login-trigger:hover {
  background: rgba(99, 102, 241, 0.4);
  color: white;
  transform: translateY(-2px);
}

.user-profile-chip {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.45rem 0.95rem;
  background: rgba(18, 28, 48, 0.9);
  border: 1px solid rgba(99, 102, 241, 0.3);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}

.user-profile-chip:hover, .user-profile-chip.chip-active {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.18);
  box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
}

.user-avatar-circle {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #38bdf8);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 800;
}

.user-info-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name {
  font-size: 0.82rem;
  font-weight: 700;
}

.dropdown-chevron {
  font-size: 0.65rem;
  color: var(--text-muted);
  margin-left: 0.3rem;
  transition: transform 0.2s ease;
}

.chip-active .dropdown-chevron {
  transform: rotate(180deg);
}

/* CLEAN STREAMLINED DROPDOWN */
.user-dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 230px;
  background: #0f172a;
  border: 1px solid rgba(99, 102, 241, 0.4);
  border-radius: var(--radius-md);
  padding: 0.85rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.8);
  z-index: 1500;
  animation: fadeIn 0.15s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}

.dropdown-meta-box {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding: 0 4px 4px 4px;
}

.user-email-text {
  font-size: 0.74rem;
  color: #94a3b8;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.68rem;
  color: #34d399;
  font-weight: 600;
}

.dot-online {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 6px #10b981;
}

.dropdown-divider {
  height: 1px;
  background: var(--border);
  margin: 0.65rem 0;
}

.dropdown-actions {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.btn-dropdown-action {
  width: 100%;
  padding: 0.55rem 0.75rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-main);
  font-size: 0.78rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-dropdown-action:hover {
  background: rgba(99, 102, 241, 0.2);
  border-color: #6366f1;
}

.btn-dropdown-logout {
  width: 100%;
  padding: 0.55rem 0.75rem;
  background: rgba(244, 63, 94, 0.12);
  border: 1px solid rgba(244, 63, 94, 0.35);
  border-radius: var(--radius-sm);
  color: #fb7185;
  font-size: 0.8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-dropdown-logout:hover {
  background: rgba(244, 63, 94, 0.3);
  color: #ffffff;
  border-color: #f43f5e;
}

.action-icon {
  font-size: 0.95rem;
}

.role-hint-banner {
  background: rgba(99, 102, 241, 0.12);
  border-bottom: 1px solid rgba(99, 102, 241, 0.25);
  padding: 0.5rem 1.5rem;
  font-size: 0.78rem;
  color: #c7d2fe;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.btn-banner-login {
  background: #6366f1;
  color: white;
  padding: 3px 10px;
  border-radius: var(--radius-sm);
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
}

.page-container {
  flex: 1;
  max-width: 1440px;
  width: 100%;
  margin: 0 auto;
  padding: 1.75rem 1.5rem;
}

.footer {
  border-top: 1px solid var(--border);
  background: rgba(7, 10, 19, 0.95);
  padding: 1.25rem 1.5rem;
  font-size: 0.8rem;
  color: var(--text-muted);
}

.footer-inner {
  max-width: 1440px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.footer-badges {
  display: flex;
  gap: 0.5rem;
}
</style>
