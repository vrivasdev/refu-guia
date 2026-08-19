import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../services/auth'
import CitizenChatView from '../views/CitizenChatView.vue'
import ShelterDashboardView from '../views/ShelterDashboardView.vue'
import MatchesView from '../views/MatchesView.vue'
import AdoptionPortalView from '../views/AdoptionPortalView.vue'
import McpExplorerView from '../views/McpExplorerView.vue'
import LocalSlmTerminalView from '../views/LocalSlmTerminalView.vue'

const routes = [
  { 
    path: '/', 
    name: 'ChatCiudadano', 
    component: CitizenChatView,
    meta: { public: true, title: 'Chat Ciudadano' }
  },
  { 
    path: '/refugios', 
    name: 'Refugios', 
    component: ShelterDashboardView,
    meta: { roles: ['shelter_admin', 'rescuer'], title: 'Refugios & QR' }
  },
  { 
    path: '/matches', 
    name: 'Matches', 
    component: MatchesView,
    meta: { roles: ['shelter_admin', 'rescuer', 'citizen'], title: 'Matchmaker Hub' }
  },
  { 
    path: '/adopcion', 
    name: 'Adopcion', 
    component: AdoptionPortalView,
    meta: { public: true, title: 'Adopción Responsable' }
  },
  { 
    path: '/mcp', 
    name: 'McpExplorer', 
    component: McpExplorerView,
    meta: { roles: ['shelter_admin'], title: 'MCP & Skills' }
  },
  { 
    path: '/terminal-slm', 
    name: 'LocalSlmTerminal', 
    component: LocalSlmTerminalView,
    meta: { roles: ['shelter_admin'], title: 'SLM Local (Ollama)' }
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Guardia de navegación por roles
router.beforeEach((to, from, next) => {
  const { hasRole, isAuthenticated } = useAuth()

  if (to.meta.public) {
    next()
    return
  }

  if (to.meta.roles) {
    if (!isAuthenticated()) {
      alert(`⚠️ Acceso Restringido: Debes iniciar sesión con un rol autorizado (${to.meta.roles.join(', ')}) para acceder a "${to.meta.title}".`)
      next({ path: '/' })
      return
    }

    if (!hasRole(to.meta.roles)) {
      alert(`⛔ Permisos Insuficientes: Tu rol actual no tiene acceso a "${to.meta.title}". Esta sección está reservada para: ${to.meta.roles.join(', ')}.`)
      next({ path: '/' })
      return
    }
  }

  next()
})

export default router
