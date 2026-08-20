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

        <!-- DYNAMIC NAV MENU STRICTLY FILTERED BY ROLE -->
        <nav class="nav-menu">
          <!-- CHAT CIUDADANO: Visible para todos -->
          <router-link to="/" class="nav-pill" active-class="nav-pill-active">
            <span class="pill-icon">💬</span>
            <span class="pill-label">Chat Ciudadano</span>
          </router-link>

          <!-- REFUGIOS & QR: Solo Coordinadora y Rescatista -->
          <router-link 
            v-if="hasRole(['shelter_admin', 'rescuer'])" 
            to="/refugios" 
            class="nav-pill" 
            active-class="nav-pill-active"
          >
            <span class="pill-icon">🏥</span>
            <span class="pill-label">Refugios & QR</span>
          </router-link>

          <!-- CENTRO DE REENCUENTRO (Cotejo Vectorial): Coordinadora, Rescatista, Damnificada -->
          <router-link 
            v-if="hasRole(['shelter_admin', 'rescuer', 'citizen'])" 
            to="/matches" 
            class="nav-pill" 
            active-class="nav-pill-active"
          >
            <span class="pill-icon">⚡</span>
            <span class="pill-label">Centro de Reencuentro</span>
          </router-link>

          <!-- PORTAL DE ADOPCIÓN: Coordinadora, Damnificada, Adoptante (Oculto para Rescatista) -->
          <router-link 
            v-if="hasRole(['shelter_admin', 'citizen', 'adopter'])" 
            to="/adopcion" 
            class="nav-pill" 
            active-class="nav-pill-active"
          >
            <span class="pill-icon">❤️</span>
            <span class="pill-label">Adopción (15d)</span>
          </router-link>

          <!-- MCP & SKILLS: EXCLUSIVO COORDINADORA -->
          <router-link 
            v-if="hasRole('shelter_admin')" 
            to="/mcp-explorer" 
            class="nav-pill" 
            active-class="nav-pill-active"
          >
            <span class="pill-icon">🛠️</span>
            <span class="pill-label">MCP & Skills</span>
          </router-link>

          <!-- SLM LOCAL: EXCLUSIVO COORDINADORA -->
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
          <!-- LOGGED IN USER TRIGGER -->
          <div v-if="currentUser" class="user-account-wrapper">
            <button 
              class="user-profile-trigger" 
              @click.stop="toggleUserMenu"
              :class="{ 'trigger-active': isUserMenuOpen }"
              aria-label="Menú de usuario"
            >
              <div class="avatar-container" :class="getAvatarGradientClass(currentUser.role)">
                <span class="avatar-initials">{{ getUserInitials(currentUser.name) }}</span>
                <span class="live-dot"></span>
              </div>
              <div class="user-meta-brief">
                <span class="user-display-name">{{ currentUser.name }}</span>
                <span class="role-micro-badge" :class="getRoleBadgeClass(currentUser.role)">
                  {{ getRoleShortLabel(currentUser.role) }}
                </span>
              </div>
              <div class="chevron-box">
                <svg class="chevron-svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </div>
            </button>

            <!-- SENIOR UX/UI DROPDOWN POPOVER WITH INSTANT PERSONA SWITCHER -->
            <transition name="dropdown-anim">
              <div v-if="isUserMenuOpen" class="user-dropdown-popover glass-card" @click.stop>
                <!-- ACTIVE USER HEADER CARD -->
                <div class="popover-user-card">
                  <div class="popover-avatar-lg" :class="getAvatarGradientClass(currentUser.role)">
                    <span>{{ getUserInitials(currentUser.name) }}</span>
                  </div>
                  <div class="popover-user-details">
                    <div class="popover-name">{{ currentUser.name }}</div>
                    <div class="popover-email">{{ currentUser.email }}</div>
                    <div class="popover-role-tag" :class="getRoleBadgeClass(currentUser.role)">
                      ● {{ getRoleFullTitle(currentUser.role) }}
                    </div>
                  </div>
                </div>

                <div class="popover-divider"></div>

                <!-- INSTANT PERSONA / ROLE SWITCHER LIST -->
                <div class="switcher-section">
                  <div class="switcher-title">
                    <span>CAMBIAR DE ROL / PERSONA</span>
                    <span class="badge-mini">1-Click</span>
                  </div>

                  <div class="persona-options-list">
                    <button 
                      v-for="persona in demoPersonas" 
                      :key="persona.role"
                      :class="['persona-row-btn', currentUser.role === persona.role ? 'persona-active' : '']"
                      @click="switchPersona(persona)"
                    >
                      <div class="persona-left">
                        <span class="persona-icon-circle" :class="persona.avatarClass">
                          {{ persona.icon }}
                        </span>
                        <div class="persona-info">
                          <span class="persona-name">{{ persona.name }}</span>
                          <span class="persona-role-sub">{{ persona.title }}</span>
                        </div>
                      </div>
                      <span v-if="currentUser.role === persona.role" class="active-check-icon">✓</span>
                    </button>
                  </div>
                </div>

                <div class="popover-divider"></div>

                <!-- ACTIONS -->
                <div class="popover-footer">
                  <button class="btn-popover-logout" @click="handleLogout">
                    <svg class="logout-icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Cerrar Sesión</span>
                  </button>
                </div>
              </div>
            </transition>
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
      <span>💡 <strong>Modo Visitante:</strong> Inicia sesión con el rol de <strong>Coordinadora de Refugio</strong> para habilitar las herramientas avanzadas.</span>
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
          <strong>RefuGuía</strong> — Sistema de Respuesta Humanitaria y Animal Post-Sismo (UTN-FRBA & EPIData)
        </div>
        <div class="footer-badges">
          <span class="badge badge-primary">100% IA Local (SLM)</span>
          <span class="badge badge-cyan">Protocolo MCP</span>
          <span class="badge badge-emerald">Control de Acceso RBAC</span>
        </div>
      </div>
    </footer>

    <!-- LOGIN MODAL (FALLBACK) -->
    <LoginModal :isOpen="showLoginModal" @close="showLoginModal = false" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from './services/auth'
import LoginModal from './components/LoginModal.vue'
import { showToast } from './utils/alerts'

const router = useRouter()
const { currentUser, login, setDirectUser, logout, hasRole } = useAuth()
const showLoginModal = ref(false)
const isUserMenuOpen = ref(false)

const demoPersonas = [
  {
    name: 'Dra. Carmen López',
    role: 'shelter_admin',
    title: 'Coordinadora de Refugio',
    email: 'carmen.refugio@refuguia.org',
    password: 'Password123!',
    icon: '🏥',
    avatarClass: 'avatar-purple'
  },
  {
    name: 'Carlos Mendoza',
    role: 'rescuer',
    title: 'Rescatista de Campo',
    email: 'carlos.rescate@refuguia.org',
    password: 'Password123!',
    icon: '👷',
    avatarClass: 'avatar-amber'
  },
  {
    name: 'María Fernández',
    role: 'citizen',
    title: 'Ciudadana Damnificada',
    email: 'maria.f@gmail.com',
    password: 'Password123!',
    icon: '👩',
    avatarClass: 'avatar-cyan'
  },
  {
    name: 'Andrés Morales',
    role: 'adopter',
    title: 'Adoptante Post-Sismo',
    email: 'andres.m@gmail.com',
    password: 'Password123!',
    icon: '❤️',
    avatarClass: 'avatar-emerald'
  }
]

const toggleUserMenu = () => {
  isUserMenuOpen.value = !isUserMenuOpen.value
}

const closeUserMenuOnClickOutside = () => {
  isUserMenuOpen.value = false
}

const switchPersona = async (persona) => {
  if (currentUser.value?.role === persona.role) {
    isUserMenuOpen.value = false
    return
  }

  isUserMenuOpen.value = false

  const res = await login(persona.email, persona.password)
  
  if (res.success) {
    showToast(`Sesión cambiada a: ${persona.name}`, 'success')
  } else {
    setDirectUser({
      name: persona.name,
      email: persona.email,
      role: persona.role,
      role_label: persona.title
    })
    showToast(`Sesión cambiada a: ${persona.name}`, 'success')
  }

  // Redirección inteligente según permisos del nuevo rol
  const currentPath = router.currentRoute.value.path
  if (persona.role === 'citizen') {
    if (currentPath === '/refugios' || currentPath === '/terminal-slm' || currentPath === '/mcp-explorer') {
      router.push('/')
    }
  } else if (persona.role === 'rescuer') {
    if (currentPath === '/adopcion' || currentPath === '/terminal-slm' || currentPath === '/mcp-explorer') {
      router.push('/refugios')
    }
  } else if (persona.role === 'adopter') {
    if (currentPath === '/refugios' || currentPath === '/matches' || currentPath === '/terminal-slm' || currentPath === '/mcp-explorer') {
      router.push('/adopcion')
    }
  }
}

const handleLogout = () => {
  isUserMenuOpen.value = false
  logout()
  router.push('/')
  showToast('Has cerrado sesión', 'info')
}

const getUserInitials = (name) => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
}

const getAvatarGradientClass = (role) => {
  switch (role) {
    case 'shelter_admin': return 'avatar-purple'
    case 'rescuer': return 'avatar-amber'
    case 'citizen': return 'avatar-cyan'
    case 'adopter': return 'avatar-emerald'
    default: return 'avatar-purple'
  }
}

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'shelter_admin': return 'badge-role-purple'
    case 'rescuer': return 'badge-role-amber'
    case 'citizen': return 'badge-role-cyan'
    case 'adopter': return 'badge-role-emerald'
    default: return 'badge-role-purple'
  }
}

const getRoleShortLabel = (role) => {
  switch (role) {
    case 'shelter_admin': return 'Coordinadora'
    case 'rescuer': return 'Rescatista'
    case 'citizen': return 'Damnificada'
    case 'adopter': return 'Adoptante'
    default: return role
  }
}

const getRoleFullTitle = (role) => {
  switch (role) {
    case 'shelter_admin': return 'Coordinadora de Refugio'
    case 'rescuer': return 'Rescatista de Campo'
    case 'citizen': return 'Ciudadana Damnificada'
    case 'adopter': return 'Adoptante Post-Sismo'
    default: return role
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
  background: rgba(7, 10, 19, 0.88);
  backdrop-filter: blur(24px);
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
  text-decoration: none;
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
  color: var(--text-main);
  letter-spacing: -0.02em;
}

.version-tag {
  font-size: 0.65rem;
  padding: 2px 6px;
  border-radius: 6px;
  background: rgba(6, 182, 212, 0.18);
  color: #38bdf8;
  border: 1px solid rgba(6, 182, 212, 0.35);
  font-weight: 700;
  vertical-align: middle;
}

.brand-sub {
  font-size: 0.68rem;
  color: var(--text-muted);
}

.nav-menu {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(14, 22, 38, 0.7);
  padding: 4px;
  border-radius: 14px;
  border: 1px solid var(--border);
}

.nav-pill {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.5rem 0.95rem;
  border-radius: 10px;
  color: var(--text-secondary);
  font-size: 0.82rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-pill:hover {
  color: var(--text-main);
  background: rgba(255, 255, 255, 0.05);
}

.nav-pill-active {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(79, 70, 229, 0.25)) !important;
  color: #ffffff !important;
  border: 1px solid rgba(99, 102, 241, 0.45);
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
}

/* USER ACCOUNT TRIGGER BUTTON */
.user-account-wrapper {
  position: relative;
}

.user-profile-trigger {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 5px 12px 5px 6px;
  background: rgba(14, 22, 38, 0.75);
  border: 1px solid rgba(99, 102, 241, 0.3);
  border-radius: 30px;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.user-profile-trigger:hover, .trigger-active {
  background: rgba(26, 38, 64, 0.95);
  border-color: #6366f1;
  box-shadow: 0 4px 20px rgba(99, 102, 241, 0.25);
}

.avatar-container {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.avatar-initials {
  font-size: 0.8rem;
  font-weight: 800;
  color: #ffffff;
}

.live-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #10b981;
  border: 2px solid #070a13;
  box-shadow: 0 0 6px #10b981;
}

.avatar-purple {
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
}

.avatar-amber {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}

.avatar-cyan {
  background: linear-gradient(135deg, #06b6d4, #0284c7);
}

.avatar-emerald {
  background: linear-gradient(135deg, #10b981, #059669);
}

.user-meta-brief {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
}

.user-display-name {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--text-main);
  line-height: 1.1;
}

.role-micro-badge {
  font-size: 0.65rem;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.badge-role-purple {
  background: rgba(139, 92, 246, 0.2);
  color: #c4b5fd;
  border: 1px solid rgba(139, 92, 246, 0.35);
}

.badge-role-amber {
  background: rgba(245, 158, 11, 0.2);
  color: #fcd34d;
  border: 1px solid rgba(245, 158, 11, 0.35);
}

.badge-role-cyan {
  background: rgba(6, 182, 212, 0.2);
  color: #67e8f9;
  border: 1px solid rgba(6, 182, 212, 0.35);
}

.badge-role-emerald {
  background: rgba(16, 185, 129, 0.2);
  color: #6ee7b7;
  border: 1px solid rgba(16, 185, 129, 0.35);
}

.chevron-box {
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  transition: transform 0.25s ease;
}

.trigger-active .chevron-box {
  transform: rotate(180deg);
}

.chevron-svg {
  width: 14px;
  height: 14px;
}

/* POPOVER DROPDOWN */
.user-dropdown-popover {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 320px;
  background: rgba(14, 22, 38, 0.98);
  backdrop-filter: blur(28px);
  border: 1px solid rgba(99, 102, 241, 0.35);
  border-radius: 18px;
  padding: 1.15rem;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 30px rgba(99, 102, 241, 0.15);
  z-index: 1500;
}

.dropdown-anim-enter-active, .dropdown-anim-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.dropdown-anim-enter-from, .dropdown-anim-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.96);
}

.popover-user-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding-bottom: 0.85rem;
}

.popover-avatar-lg {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 800;
  color: white;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
}

.popover-user-details {
  flex: 1;
  overflow: hidden;
}

.popover-name {
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--text-main);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.popover-email {
  font-size: 0.72rem;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}

.popover-role-tag {
  display: inline-block;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 8px;
}

.popover-divider {
  height: 1px;
  background: var(--border);
  margin: 0.75rem 0;
}

.switcher-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.switcher-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.68rem;
  font-weight: 800;
  color: #a5b4fc;
  letter-spacing: 0.05em;
}

.badge-mini {
  font-size: 0.6rem;
  background: rgba(99, 102, 241, 0.2);
  color: #c7d2fe;
  padding: 1px 5px;
  border-radius: 4px;
}

.persona-options-list {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.persona-row-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0.65rem;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  width: 100%;
  text-align: left;
}

.persona-row-btn:hover {
  background: rgba(99, 102, 241, 0.15);
  border-color: rgba(99, 102, 241, 0.3);
}

.persona-active {
  background: rgba(99, 102, 241, 0.22) !important;
  border-color: #6366f1 !important;
}

.persona-left {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.persona-icon-circle {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
}

.persona-info {
  display: flex;
  flex-direction: column;
}

.persona-name {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--text-main);
}

.persona-role-sub {
  font-size: 0.68rem;
  color: var(--text-muted);
}

.active-check-icon {
  font-size: 0.85rem;
  font-weight: 800;
  color: #34d399;
}

.popover-footer {
  padding-top: 0.2rem;
}

.btn-popover-logout {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.55rem 0.85rem;
  border-radius: 10px;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-popover-logout:hover {
  background: rgba(239, 68, 68, 0.25);
  border-color: #ef4444;
  color: #ffffff;
  box-shadow: 0 4px 15px rgba(239, 68, 68, 0.25);
}

.logout-icon-svg {
  width: 15px;
  height: 15px;
}

/* GUEST LOGIN BUTTON */
.btn-login-trigger {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  color: white;
  font-size: 0.8rem;
  font-weight: 700;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
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
  border: none;
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
