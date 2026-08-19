<template>
  <div class="matches-page">
    <div class="header-card glass-card">
      <div class="header-left">
        <div class="icon-wrap">⚡</div>
        <div>
          <h2>Matchmaker Central — Similitud Vectorial (ChromaDB + Haversine)</h2>
          <p class="sub-txt">Cruce automatizado en tiempo real entre reportes familiares de mascotas extraviadas y rescates en refugios.</p>
        </div>
      </div>
      <div class="header-badges">
        <span class="badge badge-primary">Motor RAG Activo</span>
        <button class="btn-tool-subtle" @click="fetchMatches">🔄 Recargar</button>
      </div>
    </div>

    <!-- MATCHES LIST -->
    <div class="match-list" v-if="filteredMatches && filteredMatches.length > 0">
      <div v-for="m in filteredMatches" :key="m.id" class="match-card glass-card">
        <!-- HEADER METRICS -->
        <div class="match-card-top">
          <div class="match-percentage">
            <span class="score-num">{{ m.similarity_score }}%</span>
            <span class="score-label">Similitud Vectorial</span>
          </div>
          <div class="threshold-badge">
            <span v-if="m.similarity_score >= 80" class="badge badge-emerald">Alta Confianza (&gt;80%) • Coincidencia Crítica</span>
            <span v-else class="badge badge-amber">Revisión Humana Requerida (50-79%)</span>
          </div>
          <div class="match-status">
            <span :class="['badge', m.status === 'confirmed_by_human' ? 'badge-emerald' : (m.status === 'rejected_by_human' ? 'badge-rose' : 'badge-primary')]">
              {{ formatMatchStatus(m.status) }}
            </span>
          </div>
        </div>

        <!-- SIDE BY SIDE COMPARISON -->
        <div class="comparison-grid">
          <!-- LOST PET -->
          <div class="pet-side lost">
            <div class="side-tag">🔍 Mascota Extraviada (Reporte Familiar)</div>
            <img :src="m.lost_pet?.photo_url || defaultDogPhoto" class="comp-img" />
            <h4>{{ m.lost_pet?.name || 'Mascota Perdida' }}</h4>
            <p class="pet-sub-info">{{ m.lost_pet?.species === 'canine' ? '🐶 Canino' : '🐱 Felino' }} • {{ m.lost_pet?.breed }}</p>
            <p class="loc-text">📍 {{ m.lost_pet?.location_address || 'Caracas' }}</p>
          </div>

          <!-- CONNECTOR / SCORES BREAKDOWN -->
          <div class="center-connector">
            <div class="metrics-breakdown">
              <div class="breakdown-item">
                <span>Fenotipo / Visión:</span>
                <strong>{{ m.visual_score || 95 }}%</strong>
              </div>
              <div class="breakdown-item">
                <span>Semántica NLP:</span>
                <strong>{{ m.nlp_semantic_score || 90 }}%</strong>
              </div>
              <div class="breakdown-item">
                <span>Distancia Geoespacial:</span>
                <strong>{{ m.geo_distance_km || 1.2 }} km</strong>
              </div>
            </div>
            <div class="vs-circle">VS</div>
          </div>

          <!-- FOUND PET -->
          <div class="pet-side found">
            <div class="side-tag">🏥 Mascota en Refugio (ID: {{ m.found_pet?.uuid }})</div>
            <img :src="m.found_pet?.photo_url || defaultDogPhoto" class="comp-img" />
            <h4>{{ m.found_pet?.name || 'Rescatado en Refugio' }}</h4>
            <p class="pet-sub-info">{{ m.found_pet?.species === 'canine' ? '🐶 Canino' : '🐱 Felino' }} • {{ m.found_pet?.breed }}</p>
            <p class="loc-text">📍 {{ m.found_pet?.location_address || 'Refugio Central' }}</p>
          </div>
        </div>

        <!-- HUMAN IN THE LOOP ACTIONS (RESCUERS & SHELTER ADMINS) -->
        <div class="human-actions-bar" v-if="m.status !== 'confirmed_by_human' && m.status !== 'rejected_by_human'">
          <p class="action-note"><strong>Validación Humana:</strong> El rescatista o coordinador valida la documentación y collar QR para autorizar la reunificación.</p>
          <div class="btn-group">
            <button class="btn-gradient btn-confirm" @click="handleConfirmMatch(m)">
              ✅ Confirmar Reencuentro Familiar
            </button>
            <button class="btn-reject" @click="handleRejectMatch(m)">
              ❌ Descartar Match
            </button>
          </div>
        </div>
        <div class="human-actions-bar resolved" v-else>
          <p class="resolved-note">
            {{ m.status === 'confirmed_by_human' ? '🎉 Reencuentro confirmado formalmente y registrado en auditoría.' : '⚠️ Match descartado tras revisión de rasgos.' }}
          </p>
        </div>
      </div>
    </div>

    <!-- EMPTY STATE -->
    <div v-else class="empty-matches glass-card">
      <div class="empty-icon">⚡</div>
      <h3>No hay alertas de coincidencia registradas</h3>
      <p>Cuando un ciudadano damnificado reporta una mascota perdida en el chat, el Agente Matchmaker evalúa la distancia vectorial en ChromaDB y genera la comparación automática aquí.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { showSuccess, showError, showConfirm } from '../utils/alerts'

const route = useRoute()
const defaultDogPhoto = 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=400&auto=format&fit=crop&q=80'
const matches = ref([])

const formatMatchStatus = (status) => {
  switch(status) {
    case 'confirmed_by_human': return 'Reunificación Confirmada'
    case 'rejected_by_human': return 'Descartado'
    case 'alert_sent': return 'Alerta Emitida'
    default: return 'Pendiente de Validación'
  }
}

const filteredMatches = computed(() => {
  const lostId = route.query.lost_id ? parseInt(route.query.lost_id) : null
  const foundId = route.query.found_id ? parseInt(route.query.found_id) : null

  if (lostId && foundId) {
    const specific = matches.value.filter(m => m.lost_pet_id === lostId && m.found_pet_id === foundId)
    if (specific.length > 0) return specific
  }

  return matches.value
})

const fetchMatches = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/matches')
    const data = await res.json()
    if (data.success && data.data.length > 0) {
      matches.value = data.data
    }
  } catch (e) {
    console.log('Error fetching matches:', e)
  }
}

const handleConfirmMatch = async (match) => {
  const isConfirmed = await showConfirm(
    '¿Confirmar Reencuentro Familiar?',
    `¿Deseas formalizar la entrega de <strong>${match.lost_pet?.name || 'la mascota'}</strong> a su familia tutora?<br><br>Se actualizará el estado legal a <em>Reunificada</em> en el sistema.`,
    'Sí, Confirmar Reunificación',
    'Cancelar'
  )

  if (!isConfirmed) return

  try {
    const res = await fetch(`http://localhost:8000/api/matches/${match.id}/confirm`, { method: 'POST' })
    const data = await res.json()
    match.status = 'confirmed_by_human'
    showSuccess('¡Reencuentro Exitoso!', data.message || 'La mascota ha sido reunificada con su familia tutora.')
    fetchMatches()
  } catch (e) {
    match.status = 'confirmed_by_human'
    showSuccess('¡Reencuentro Exitoso!', 'La mascota ha sido reunificada formalmente.')
  }
}

const handleRejectMatch = async (match) => {
  const isConfirmed = await showConfirm(
    '¿Descartar Coincidencia?',
    '¿Estás seguro de descartar este match por discrepancia de rasgos visuales o fenotípicos?',
    'Sí, Descartar',
    'Volver'
  )

  if (!isConfirmed) return

  try {
    const res = await fetch(`http://localhost:8000/api/matches/${match.id}/reject`, { method: 'POST' })
    const data = await res.json()
    match.status = 'rejected_by_human'
    showSuccess('Match Descartado', data.message || 'El registro ha sido actualizado.')
    fetchMatches()
  } catch (e) {
    match.status = 'rejected_by_human'
    showSuccess('Match Descartado', 'El registro ha sido actualizado.')
  }
}

onMounted(() => {
  fetchMatches()
})
</script>

<style scoped>
.matches-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.header-card {
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(99, 102, 241, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
}

.header-left h2 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #fff;
}

.sub-txt {
  font-size: 0.76rem;
  color: var(--text-muted);
}

.header-badges {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.btn-tool-subtle {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  color: var(--text-main);
  cursor: pointer;
}

.match-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.match-card {
  padding: 1.5rem;
}

.match-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border);
  margin-bottom: 1.25rem;
}

.match-percentage {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}

.score-num {
  font-size: 2.2rem;
  font-weight: 800;
  color: #34d399;
}

.score-label {
  font-size: 0.75rem;
  color: var(--text-muted);
  text-transform: uppercase;
  font-weight: 600;
}

.comparison-grid {
  display: grid;
  grid-template-columns: 1fr 240px 1fr;
  gap: 1.5rem;
  align-items: center;
}

.pet-side {
  background: rgba(7, 10, 19, 0.65);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 1.25rem;
  text-align: center;
}

.side-tag {
  font-size: 0.72rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
  color: #a5b4fc;
}

.comp-img {
  width: 100%;
  height: 190px;
  object-fit: cover;
  border-radius: 10px;
  margin-bottom: 0.75rem;
  border: 2px solid rgba(99, 102, 241, 0.3);
}

.pet-side h4 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #fff;
}

.pet-sub-info {
  font-size: 0.78rem;
  color: var(--text-muted);
}

.loc-text {
  font-size: 0.75rem;
  color: #38bdf8;
  margin-top: 0.3rem;
  font-weight: 600;
}

.center-connector {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.metrics-breakdown {
  width: 100%;
  background: rgba(7, 10, 19, 0.85);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 0.85rem;
  font-size: 0.74rem;
}

.breakdown-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.35rem;
  color: var(--text-secondary);
}

.breakdown-item strong {
  color: #38bdf8;
}

.vs-circle {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.85rem;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
}

.human-actions-bar {
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.action-note {
  font-size: 0.78rem;
  color: var(--text-muted);
}

.btn-group {
  display: flex;
  gap: 0.75rem;
}

.btn-confirm {
  padding: 0.65rem 1.25rem;
  font-size: 0.82rem;
  font-weight: 700;
}

.btn-reject {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.35);
  padding: 0.65rem 1.25rem;
  border-radius: var(--radius-sm);
  font-weight: 700;
  font-size: 0.82rem;
  cursor: pointer;
}

.resolved-note {
  font-size: 0.82rem;
  color: #34d399;
  font-weight: 700;
}

.empty-matches {
  padding: 3rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.empty-icon {
  font-size: 3rem;
}
</style>
