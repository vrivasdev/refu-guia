<template>
  <div class="app-wrapper">
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

        <!-- USER PROFILE & LOGIN BUTTON -->
        <div class="user-session-group">
          <!-- LOGGED IN USER PILL -->
          <div v-if="currentUser" class="user-profile-chip" @click="showLoginModal = true">
            <div class="user-avatar-circle">
              {{ getUserInitials(currentUser.name) }}
            </div>
            <div class="user-info-text">
              <span class="user-name">{{ currentUser.name }}</span>
              <span :class="['badge', getRoleBadgeClass(currentUser.role)]">{{ currentUser.role_label || currentUser.role }}</span>
            </div>
            <button class="btn-logout" @click.stop="handleLogout" title="Cerrar Sesión">✕</button>
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

const handleLogout = () => {
  logout()
  router.push('/')
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

.btn-login-trigger {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(79, 70, 229, 0.25));
  border: 1px solid rgba(99, 102, 241, 0.4);
  color: #a5b4fc;
  padding: 0.5rem 1.1rem;
  border-radius: var(--radius-md);
  font-weight: 700;
  font-size: 0.82rem;
}

.btn-login-trigger:hover {
  background: rgba(99, 102, 241, 0.4);
  color: white;
  transform: translateY(-2px);
}

.user-profile-chip {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.35rem 0.75rem;
  background: rgba(18, 28, 48, 0.8);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s ease;
}

.user-profile-chip:hover {
  border-color: #6366f1;
}

.user-avatar-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #38bdf8);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 800;
}

.user-info-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name {
  font-size: 0.78rem;
  font-weight: 700;
}

.btn-logout {
  background: rgba(255, 255, 255, 0.08);
  color: var(--text-muted);
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.65rem;
  margin-left: 0.3rem;
}

.btn-logout:hover {
  background: rgba(244, 63, 94, 0.3);
  color: #fb7185;
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
