import { createRouter, createWebHistory } from 'vue-router'
import CitizenChatView from '../views/CitizenChatView.vue'
import ShelterDashboardView from '../views/ShelterDashboardView.vue'
import MatchesView from '../views/MatchesView.vue'
import AdoptionPortalView from '../views/AdoptionPortalView.vue'
import McpExplorerView from '../views/McpExplorerView.vue'
import LocalSlmTerminalView from '../views/LocalSlmTerminalView.vue'
import { showWarning } from '../utils/alerts'

const routes = [
  {
    path: '/',
    name: 'chat',
    component: CitizenChatView,
    meta: { title: 'Chat Ciudadano', roles: ['citizen', 'rescuer', 'shelter_admin', 'adopter'] }
  },
  {
    path: '/refugios',
    name: 'shelters',
    component: ShelterDashboardView,
    meta: { title: 'Inventario de Refugios', roles: ['shelter_admin', 'rescuer'] }
  },
  {
    path: '/matches',
    name: 'matches',
    component: MatchesView,
    meta: { title: 'Matchmaker Hub', roles: ['shelter_admin', 'rescuer', 'citizen'] }
  },
  {
    path: '/adopcion',
    name: 'adoption',
    component: AdoptionPortalView,
    meta: { title: 'Portal de Adopción', roles: ['citizen', 'adopter', 'shelter_admin'] }
  },
  {
    path: '/mcp-explorer',
    name: 'mcp-explorer',
    component: McpExplorerView,
    meta: { title: 'Explorador MCP', roles: ['shelter_admin'] }
  },
  {
    path: '/terminal-slm',
    name: 'terminal-slm',
    component: LocalSlmTerminalView,
    meta: { title: 'Diagnóstico SLM Local', roles: ['shelter_admin'] }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const saved = localStorage.getItem('refuguia_user')
  let user = null
  try {
    user = saved ? JSON.parse(saved) : null
  } catch (e) {}

  if (to.meta && to.meta.roles) {
    if (!user) {
      showWarning(
        'Acceso Restringido',
        `Debes iniciar sesión con un rol autorizado (<em>${to.meta.roles.join(', ')}</em>) para acceder a <strong>${to.meta.title}</strong>.`
      )
      return next('/')
    }
    if (!to.meta.roles.includes(user.role)) {
      showWarning(
        'Permisos Insuficientes',
        `Tu rol actual (<em>${user.role}</em>) no tiene acceso a <strong>${to.meta.title}</strong>. Sección reservada para: <em>${to.meta.roles.join(', ')}</em>.`
      )
      return next(from.path || '/')
    }
  }

  next()
})

export default router
