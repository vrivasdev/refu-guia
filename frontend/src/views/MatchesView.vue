<template>
  <div class="matches-page">
    <div class="page-title-box">
      <h2>⚡ Matchmaker Central (Similitud Vectorial + Geo-Temporal)</h2>
      <p>Orquestación agéntica para el reencuentro de mascotas extraviadas con umbrales de decisión.</p>
    </div>

    <!-- MATCH CARD SIDE BY SIDE -->
    <div class="match-list">
      <div v-for="m in matches" :key="m.id" class="match-card glass-panel">
        <!-- HEADER METRICS -->
        <div class="match-card-top">
          <div class="match-percentage">
            <span class="score-num">{{ m.similarity_score }}%</span>
            <span class="score-label">Coincidencia de IA</span>
          </div>
          <div class="threshold-badge">
            <span v-if="m.similarity_score >= 80" class="badge badge-success">Alta Confianza (>80%) • Alerta Automática</span>
            <span v-else class="badge badge-warning">Revisión Humana Requerida (50-79%)</span>
          </div>
          <div class="match-status">
            <span class="badge badge-primary">{{ m.status }}</span>
          </div>
        </div>

        <!-- SIDE BY SIDE PET PROFILES -->
        <div class="comparison-grid">
          <!-- LOST PET -->
          <div class="pet-side lost">
            <div class="side-tag">🔍 Mascota Perdida (Reporte Familiar)</div>
            <img :src="m.lost_pet?.photo_url || 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=400'" class="comp-img" />
            <h4>{{ m.lost_pet?.name || 'Toby' }}</h4>
            <p class="pet-sub-info">{{ m.lost_pet?.species }} • {{ m.lost_pet?.breed }}</p>
            <p class="loc-text">📍 {{ m.lost_pet?.location_address }}</p>
          </div>

          <!-- AI COMPARISON CENTER CONNECTOR -->
          <div class="center-connector">
            <div class="metrics-breakdown">
              <div class="breakdown-item">
                <span>Visión / Fenotipo:</span>
                <strong>{{ m.visual_score }}%</strong>
              </div>
              <div class="breakdown-item">
                <span>Semántica NLP:</span>
                <strong>{{ m.nlp_semantic_score }}%</strong>
              </div>
              <div class="breakdown-item">
                <span>Distancia Geo:</span>
                <strong>{{ m.geo_distance_km }} km</strong>
              </div>
            </div>
            <div class="vs-circle">VS</div>
          </div>

          <!-- FOUND PET -->
          <div class="pet-side found">
            <div class="side-tag">🏥 Mascota en Refugio (ID: {{ m.found_pet?.uuid }})</div>
            <img :src="m.found_pet?.photo_url || 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=400'" class="comp-img" />
            <h4>{{ m.found_pet?.name || 'Rescatado en Refugio' }}</h4>
            <p class="pet-sub-info">{{ m.found_pet?.species }} • {{ m.found_pet?.breed }}</p>
            <p class="loc-text">📍 {{ m.found_pet?.location_address }}</p>
          </div>
        </div>

        <!-- HUMAN IN THE LOOP ACTIONS -->
        <div class="human-actions-bar">
          <p class="action-note"><strong>Validación Humana:</strong> El tutor o rescatista confirma la identidad real para actualizar el estado legal.</p>
          <div class="btn-group">
            <button class="btn-confirm" @click="confirmMatch(m.id)">
              ✅ Confirmar Reencuentro Familiar
            </button>
            <button class="btn-reject" @click="rejectMatch(m.id)">
              ❌ Descartar Match (Falso Positivo)
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const matches = ref([])

const fetchMatches = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/matches')
    const data = await res.json()
    if (data.success && data.data.length > 0) {
      matches.value = data.data
    }
  } catch (e) {
    // Fallback demo data
    matches.value = [
      {
        id: 1,
        similarity_score: 91.5,
        visual_score: 95.0,
        nlp_semantic_score: 90.0,
        geo_distance_km: 1.8,
        status: 'alert_sent',
        lost_pet: {
          name: 'Toby',
          species: 'Canino',
          breed: 'Border Collie Mestizo',
          location_address: 'Catia, Caracas',
          photo_url: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=400'
        },
        found_pet: {
          uuid: 'RG-2026-000512',
          name: 'Rescatado Toby (Provisorio)',
          species: 'Canino',
          breed: 'Border Collie Mestizo',
          location_address: 'Refugio Caricuao',
          photo_url: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=400'
        }
      }
    ]
  }
}

const confirmMatch = async (id) => {
  try {
    const res = await fetch(`http://localhost:8000/api/matches/${id}/confirm`, { method: 'POST' })
    const data = await res.json()
    alert(data.message || '¡Reencuentro confirmado!')
    fetchMatches()
  } catch (e) {
    alert('Reencuentro confirmado en modo demostración.')
  }
}

const rejectMatch = async (id) => {
  try {
    const res = await fetch(`http://localhost:8000/api/matches/${id}/reject`, { method: 'POST' })
    const data = await res.json()
    alert(data.message || 'Match descartado.')
    fetchMatches()
  } catch (e) {
    alert('Match descartado en modo demostración.')
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

.page-title-box h2 {
  font-size: 1.5rem;
  font-weight: 800;
}

.page-title-box p {
  font-size: 0.85rem;
  color: var(--text-muted);
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
  font-size: 2rem;
  font-weight: 800;
  color: #34d399;
}

.score-label {
  font-size: 0.8rem;
  color: var(--text-muted);
  text-transform: uppercase;
}

.comparison-grid {
  display: grid;
  grid-template-columns: 1fr 220px 1fr;
  gap: 1.5rem;
  align-items: center;
}

.pet-side {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1.25rem;
  text-align: center;
}

.side-tag {
  font-size: 0.75rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
  color: #818cf8;
}

.comp-img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  border-radius: 10px;
  margin-bottom: 0.75rem;
}

.pet-sub-info {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.loc-text {
  font-size: 0.75rem;
  color: #38bdf8;
  margin-top: 0.25rem;
}

.center-connector {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.metrics-breakdown {
  width: 100%;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 0.75rem;
  font-size: 0.75rem;
}

.breakdown-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.35rem;
}

.vs-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #4f46e5;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
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
  font-size: 0.8rem;
  color: var(--text-muted);
}

.btn-group {
  display: flex;
  gap: 0.75rem;
}

.btn-confirm {
  background: #10b981;
  color: white;
  padding: 0.6rem 1.25rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
}

.btn-reject {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.4);
  padding: 0.6rem 1.25rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
}
</style>
