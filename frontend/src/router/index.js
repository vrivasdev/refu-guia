import { createRouter, createWebHistory } from 'vue-router'
import CitizenChatView from '../views/CitizenChatView.vue'
import ShelterDashboardView from '../views/ShelterDashboardView.vue'
import MatchesView from '../views/MatchesView.vue'
import AdoptionPortalView from '../views/AdoptionPortalView.vue'
import McpExplorerView from '../views/McpExplorerView.vue'
import LocalSlmTerminalView from '../views/LocalSlmTerminalView.vue'

const routes = [
  { path: '/', name: 'ChatCiudadano', component: CitizenChatView },
  { path: '/refugios', name: 'Refugios', component: ShelterDashboardView },
  { path: '/matches', name: 'Matches', component: MatchesView },
  { path: '/adopcion', name: 'Adopcion', component: AdoptionPortalView },
  { path: '/mcp', name: 'McpExplorer', component: McpExplorerView },
  { path: '/terminal-slm', name: 'LocalSlmTerminal', component: LocalSlmTerminalView },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
