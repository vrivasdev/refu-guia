import { createRouter, createWebHistory } from 'vue-router'
import CitizenChatView from '../views/CitizenChatView.vue'
import ShelterDashboardView from '../views/ShelterDashboardView.vue'
import MatchesView from '../views/MatchesView.vue'
import AdoptionPortalView from '../views/AdoptionPortalView.vue'
import McpExplorerView from '../views/McpExplorerView.vue'
import TerminalSlmView from '../views/TerminalSlmView.vue'
import { useAuth } from '../services/auth'
import { showWarning } from '../utils/alerts'

const routes = [
  {
    path: '/',
    name: 'citizen-chat',
    component: CitizenChatView,
    meta: { title: 'Chat Ciudadano - RefuGuía' }
  },
  {
    path: '/refugios',
    name: 'shelter-dashboard',
    component: ShelterDashboardView,
    meta: { 
      title: 'Refugios & QR - RefuGuía',
      requiredRoles: ['shelter_admin', 'rescuer']
    }
  },
  {
    path: '/matches',
    name: 'matches',
    component: MatchesView,
    meta: { 
      title: 'Centro de Reencuentro - RefuGuía',
      requiredRoles: ['shelter_admin', 'rescuer', 'citizen']
    }
  },
  {
    path: '/adopcion',
    name: 'adoption-portal',
    component: AdoptionPortalView,
    meta: { 
      title: 'Portal de Adopción - RefuGuía',
      requiredRoles: ['shelter_admin', 'citizen', 'adopter']
    }
  },
  {
    path: '/mcp-explorer',
    name: 'mcp-explorer',
    component: McpExplorerView,
    meta: { 
      title: 'MCP & Skills - RefuGuía',
      requiredRoles: ['shelter_admin']
    }
  },
  {
    path: '/terminal-slm',
    name: 'terminal-slm',
    component: TerminalSlmView,
    meta: { 
      title: 'Terminal SLM Local - RefuGuía',
      requiredRoles: ['shelter_admin']
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const { currentUser, hasRole } = useAuth()
  
  if (to.meta.requiredRoles) {
    if (!currentUser.value) {
      showWarning(
        'Acceso Restringido',
        'Debes iniciar sesión con las credenciales correspondientes para acceder a esta área.'
      )
      return next('/')
    }

    if (!hasRole(to.meta.requiredRoles)) {
      showWarning(
        'Permisos Insuficientes',
        `Tu rol actual (<em>${currentUser.value.role}</em>) no tiene acceso a <strong>${to.meta.title.split(' - ')[0]}</strong>.`
      )
      return next('/')
    }
  }

  next()
})

export default router
