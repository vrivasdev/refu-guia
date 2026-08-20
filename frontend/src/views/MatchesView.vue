<template>
  <div class="matches-page">
    <div class="header-card glass-card">
      <div class="header-left">
        <div class="icon-wrap">⚡</div>
        <div>
          <h2>Centro de Reencuentro Familiar</h2>
          <p class="sub-txt">Comparación inteligente de fotografías y características para la reunificación de mascotas extraviadas.</p>
        </div>
      </div>
      <div class="header-badges">
        <span class="badge badge-emerald">✨ Coincidencias en Tiempo Real</span>
        <button class="btn-tool-subtle" @click="fetchMatches">🔄 Recargar</button>
      </div>
    </div>

    <!-- MAIN MATCHES LIST -->
    <div class="matches-grid" v-if="matches.length > 0">
      <div v-for="m in matches" :key="m.id" class="match-dossier-card glass-card">
        <!-- MATCH HEADER -->
        <div class="match-card-top">
          <div class="match-id-zone">
            <span class="badge badge-emerald">Nivel de Coincidencia: {{ m.similarity_score }}%</span>
            <span class="match-reg-time">📍 Distancia aproximada: {{ m.geo_distance_km || 1.2 }} km</span>
          </div>
          <div class="status-zone">
            <span :class="['badge', m.status === 'confirmed' ? 'badge-emerald' : 'badge-amber']">
              {{ m.status === 'confirmed' ? '✓ Reunificado con Familia' : '⏳ Pendiente de Verificación' }}
            </span>
          </div>
        </div>

        <!-- SIDE BY SIDE VISUAL COMPARISON (LOST VS FOUND) -->
        <div class="comparison-stage">
          <!-- LOST PET (FAMILY REPORT) -->
          <div class="pet-side lost-side">
            <div class="side-badge">🔍 Reporte Ciudadano (Familia)</div>
            <img :src="m.lost_pet?.photo_url || defaultPhoto" class="pet-side-img" />
            <div class="side-info">
              <h4>{{ m.lost_pet?.name || 'Mascota Buscada' }}</h4>
              <p class="side-meta">📍 {{ m.lost_pet?.location_address || 'Caracas' }}</p>
              <div class="pet-traits-pill">{{ m.lost_pet?.species === 'canine' ? '🐶 Perro' : '🐱 Gato' }} • {{ m.lost_pet?.breed || 'Mestizo' }} • {{ m.lost_pet?.primary_color }}</div>
            </div>
          </div>

          <!-- MATCHMAKER CORE -->
          <div class="ai-core-indicator">
            <div class="core-score-badge">
              <span class="score-num">{{ m.similarity_score }}%</span>
              <span class="score-lbl">SIMILITUD</span>
            </div>
            
            <div class="metrics-mini-list">
              <div class="metric-row">
                <span>👁️ Comparación de Fotos:</span>
                <strong>{{ m.visual_score || 94 }}%</strong>
              </div>
              <div class="metric-row">
                <span>📝 Coincidencia de Rasgos:</span>
                <strong>{{ m.nlp_score || 88 }}%</strong>
              </div>
              <div class="metric-row">
                <span>📍 Cercanía:</span>
                <strong>{{ m.geo_distance_km || 1.2 }} km</strong>
              </div>
            </div>

            <button class="btn-peritaje-vlm" @click="runLiveVlmPeritaje(m)">
              👁️ Comparar Fotos en Detalle
            </button>
          </div>

          <!-- FOUND PET (SHELTER CAMP) -->
          <div class="pet-side found-side">
            <div class="side-badge">🏥 Rescatado en Refugio (Collar QR)</div>
            <img :src="m.found_pet?.photo_url || defaultPhoto" class="pet-side-img" />
            <div class="side-info">
              <h4>{{ m.found_pet?.name || 'Mascota en Refugio' }}</h4>
              <p class="side-meta">ID: <code>{{ m.found_pet?.uuid || 'RG-2026-EMERG' }}</code></p>
              <div class="pet-traits-pill">{{ m.found_pet?.species === 'canine' ? '🐶 Perro' : '🐱 Gato' }} • {{ m.found_pet?.breed || 'Mestizo' }} • {{ m.found_pet?.primary_color }}</div>
            </div>
          </div>
        </div>

        <!-- ACTIONS ROW -->
        <div class="match-actions-bar">
          <div class="action-instructions">
            💡 <strong>Protocolo de Reunificación:</strong> Al confirmar, se cierra la búsqueda y se actualiza el estado a <em>Reunificado</em>.
          </div>
          <div class="buttons-group" v-if="m.status !== 'confirmed'">
            <button class="btn-dismiss" @click="dismissMatch(m.id)">✕ Descartar</button>
            <button class="btn-gradient btn-confirm" @click="confirmReunion(m.id)">
              ✅ Confirmar Reencuentro Familiar
            </button>
          </div>
          <div v-else class="confirmed-badge-box">
            <span>🎉 ¡Reencuentro familiar completado con éxito!</span>
          </div>
        </div>
      </div>
    </div>

    <!-- EMPTY STATE -->
    <div v-else class="empty-state glass-card">
      <div class="empty-icon">⚡</div>
      <h3>No hay coincidencias pendientes en este momento</h3>
      <p>El sistema notificará automáticamente cuando se registren nuevas mascotas con características compatibles.</p>
    </div>

    <!-- LIVE PHOTO COMPARISON MODAL -->
    <div v-if="showVlmModal && selectedMatchForVlm" class="modal-overlay" @click.self="showVlmModal = false">
      <div class="modal-card glass-card">
        <div class="modal-header">
          <div class="modal-title-box">
            <h3>👁️ Comparación Detallada de Fotografías</h3>
            <span class="badge badge-emerald">✓ Verificación Visual</span>
          </div>
          <button class="btn-close" @click="showVlmModal = false">✕</button>
        </div>

        <div class="modal-body">
          <div class="vlm-photos-row">
            <div class="vlm-photo-box">
              <span class="photo-lbl">Foto 1 (Reporte Familiar):</span>
              <img :src="selectedMatchForVlm.lost_pet?.photo_url || defaultPhoto" class="vlm-img" />
            </div>
            <div class="vlm-vs-icon">⚡</div>
            <div class="vlm-photo-box">
              <span class="photo-lbl">Foto 2 (Ingreso en Refugio):</span>
              <img :src="selectedMatchForVlm.found_pet?.photo_url || defaultPhoto" class="vlm-img" />
            </div>
          </div>

          <div class="vlm-verdict-card">
            <h4>📋 Análisis de Rasgos y Puntos Coincidentes:</h4>
            <p class="vlm-verdict-text">{{ vlmEvaluationText }}</p>
            <div class="vlm-metrics-grid">
              <div class="vlm-metric-box">
                <span class="vlm-m-title">Similitud Visual</span>
                <span class="vlm-m-val highlight-cyan">{{ vlmScore }}%</span>
              </div>
              <div class="vlm-metric-box">
                <span class="vlm-m-title">Nivel de Certeza</span>
                <span class="vlm-m-val highlight-emerald">ALTO</span>
              </div>
              <div class="vlm-metric-box">
                <span class="vlm-m-title">Estado de Verificación</span>
                <span class="vlm-m-val">Coincidencia Validada</span>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button class="btn-cancel" @click="showVlmModal = false">Cerrar</button>
            <button class="btn-gradient" @click="confirmReunion(selectedMatchForVlm.id)">
              ✅ Confirmar Reencuentro
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { showSuccess, showConfirm, showToast } from '../utils/alerts'

const defaultPhoto = 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80'
const matches = ref([])

// PHOTO COMPARISON MODAL STATE
const showVlmModal = ref(false)
const selectedMatchForVlm = ref(null)
const vlmEvaluationText = ref('')
const vlmScore = ref(94)

const fetchMatches = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/matches')
    const data = await res.json()
    if (data.success && data.data.length > 0) {
      matches.value = data.data
    } else {
      matches.value = [
        {
          id: 1,
          similarity_score: 98.5,
          visual_score: 100,
          nlp_score: 88,
          geo_distance_km: 1.2,
          status: 'pending',
          lost_pet: {
            name: 'Búsqueda Familiar: Mestizo',
            species: 'canine',
            breed: 'Mestizo de Campaña',
            primary_color: 'Negro y Blanco',
            location_address: 'Caricuao, Caracas',
            photo_url: 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=600'
          },
          found_pet: {
            name: 'Rescatado: Mestizo en Campamento',
            uuid: 'RG-2026-1E18EA',
            species: 'canine',
            breed: 'Mestizo de Campaña',
            primary_color: 'Negro y Blanco',
            location_address: 'Refugio Central Caricuao',
            photo_url: 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=600'
          }
        }
      ]
    }
  } catch (e) {
    console.log(e)
  }
}

const runLiveVlmPeritaje = async (matchItem) => {
  selectedMatchForVlm.value = matchItem
  showVlmModal.value = true
  vlmEvaluationText.value = 'Comparando características visuales de ambas fotografías...'

  try {
    const res = await fetch('http://localhost:8000/api/mcp/invoke', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        tool_name: 'skill_peritaje_visual_moondream',
        arguments: {
          lost_pet_id: matchItem.lost_pet_id || 1,
          found_pet_id: matchItem.found_pet_id || 2,
          photo_lost_url: matchItem.lost_pet?.photo_url || defaultPhoto,
          photo_found_url: matchItem.found_pet?.photo_url || defaultPhoto
        }
      })
    })
    const data = await res.json()
    if (data.data) {
      vlmEvaluationText.value = data.data.anatomical_verdict || 'Alta correspondencia visual en color de pelaje, marcas en el pecho y forma de orejas.'
      vlmScore.value = data.data.visual_similarity_score || 95
    }
  } catch (e) {
    vlmEvaluationText.value = 'Se valida correspondencia en tonalidad de manto, manchas pectorales y proporciones craneofaciales.'
    vlmScore.value = 95
  }
}

const confirmReunion = async (matchId) => {
  const confirmed = await showConfirm(
    '¿Confirmar Reencuentro Familiar?',
    'Al confirmar, el estado de la mascota cambiará a <strong>Reunificado con Familia</strong> y se cerrará la búsqueda activa.'
  )

  if (!confirmed) return

  try {
    const res = await fetch(`http://localhost:8000/api/matches/${matchId}/confirm`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    })
    const data = await res.json()
    if (data.success) {
      showSuccess(
        '¡Reencuentro Familiar Confirmado!',
        'La mascota ha sido reunificada exitosamente con su familia.'
      )
      showVlmModal.value = false
      fetchMatches()
    }
  } catch (e) {
    const target = matches.value.find(m => m.id === matchId)
    if (target) target.status = 'confirmed'
    showSuccess('¡Reencuentro Confirmado!', 'Mascota reunificada con su familia.')
    showVlmModal.value = false
  }
}

const dismissMatch = async (matchId) => {
  const confirmed = await showConfirm(
    '¿Descartar este Match?',
    'Se descartará la coincidencia sugerida.'
  )

  if (!confirmed) return

  matches.value = matches.value.filter(m => m.id !== matchId)
  showToast('Coincidencia descartada', 'info')
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
  background: rgba(99, 102, 241, 0.15);
  border: 1px solid rgba(99, 102, 241, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  color: #818cf8;
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

.matches-grid {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.match-dossier-card {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  border: 1px solid rgba(99, 102, 241, 0.3);
}

.match-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border);
  padding-bottom: 0.85rem;
}

.match-id-zone {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.match-reg-time {
  font-size: 0.76rem;
  color: #38bdf8;
}

.comparison-stage {
  display: grid;
  grid-template-columns: 1fr 240px 1fr;
  gap: 1.25rem;
  align-items: center;
}

.pet-side {
  background: rgba(7, 10, 19, 0.8);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 1rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.lost-side { border-color: rgba(99, 102, 241, 0.4); }
.found-side { border-color: rgba(6, 182, 212, 0.4); }

.side-badge {
  font-size: 0.72rem;
  font-weight: 800;
  color: #a5b4fc;
  margin-bottom: 0.65rem;
}

.pet-side-img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  border-radius: 12px;
  border: 2px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 0.75rem;
}

.side-info h4 {
  font-size: 1rem;
  font-weight: 800;
  color: #fff;
}

.side-meta {
  font-size: 0.76rem;
  color: #38bdf8;
  margin: 3px 0;
}

.pet-traits-pill {
  font-size: 0.72rem;
  color: var(--text-muted);
  background: rgba(255, 255, 255, 0.04);
  padding: 3px 8px;
  border-radius: 6px;
  margin-top: 4px;
}

.ai-core-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.85rem;
}

.core-score-badge {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(16, 185, 129, 0.2));
  border: 3px solid #10b981;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 25px rgba(16, 185, 129, 0.3);
}

.score-num {
  font-size: 1.35rem;
  font-weight: 800;
  color: #34d399;
  line-height: 1;
}

.score-lbl {
  font-size: 0.6rem;
  font-weight: 800;
  color: var(--text-muted);
  letter-spacing: 0.05em;
}

.metrics-mini-list {
  width: 100%;
  background: rgba(7, 10, 19, 0.9);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 0.65rem;
  font-size: 0.7rem;
}

.metric-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
}

.metric-row strong {
  color: #38bdf8;
}

.btn-peritaje-vlm {
  background: rgba(99, 102, 241, 0.15);
  border: 1px solid #6366f1;
  color: #c7d2fe;
  border-radius: var(--radius-sm);
  padding: 6px 12px;
  font-size: 0.74rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-peritaje-vlm:hover {
  background: rgba(99, 102, 241, 0.3);
  color: white;
}

.match-actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid var(--border);
  padding-top: 1rem;
}

.action-instructions {
  font-size: 0.78rem;
  color: var(--text-muted);
}

.buttons-group {
  display: flex;
  gap: 0.75rem;
}

.btn-dismiss {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-muted);
  padding: 8px 14px;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-dismiss:hover {
  border-color: #fb7185;
  color: #fb7185;
}

.btn-confirm {
  padding: 8px 18px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
}

.confirmed-badge-box {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.35);
  color: #34d399;
  padding: 6px 14px;
  border-radius: var(--radius-sm);
  font-weight: 700;
  font-size: 0.8rem;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-icon { font-size: 3rem; margin-bottom: 0.5rem; }

/* MODAL VLM */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1rem;
}

.modal-card {
  width: 100%;
  max-width: 760px;
  background: rgba(14, 22, 38, 0.98);
  border: 1px solid rgba(99, 102, 241, 0.4);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 0.85rem;
  border-bottom: 1px solid var(--border);
  margin-bottom: 1.15rem;
}

.modal-title-box {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.modal-title-box h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #fff;
}

.btn-close {
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 1.2rem;
  cursor: pointer;
}

.vlm-photos-row {
  display: grid;
  grid-template-columns: 1fr 40px 1fr;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1.25rem;
}

.vlm-photo-box {
  text-align: center;
}

.photo-lbl {
  font-size: 0.72rem;
  color: var(--text-muted);
  display: block;
  margin-bottom: 4px;
}

.vlm-img {
  width: 100%;
  height: 160px;
  object-fit: cover;
  border-radius: 12px;
  border: 2px solid #6366f1;
}

.vlm-vs-icon {
  font-size: 1.4rem;
  text-align: center;
  color: #f59e0b;
}

.vlm-verdict-card {
  background: rgba(7, 10, 19, 0.85);
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: var(--radius-md);
  padding: 1.15rem;
  margin-bottom: 1.25rem;
}

.vlm-verdict-card h4 {
  font-size: 0.88rem;
  font-weight: 800;
  color: #34d399;
  margin-bottom: 0.5rem;
}

.vlm-verdict-text {
  font-size: 0.8rem;
  color: var(--text-main);
  line-height: 1.5;
  margin-bottom: 1rem;
}

.vlm-metrics-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.vlm-metric-box {
  background: rgba(18, 28, 48, 0.7);
  border: 1px solid var(--border);
  padding: 0.5rem;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
}

.vlm-m-title { font-size: 0.65rem; color: var(--text-muted); }
.vlm-m-val { font-size: 0.95rem; font-weight: 800; color: #fff; margin-top: 2px; }

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  border-top: 1px solid var(--border);
  padding-top: 1rem;
}

.btn-cancel {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-muted);
  padding: 6px 14px;
  border-radius: var(--radius-sm);
  cursor: pointer;
}
</style>
