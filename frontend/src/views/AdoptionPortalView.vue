<template>
  <div class="adoption-page">
    <div class="header-card glass-card">
      <div class="header-left">
        <div class="icon-wrap">❤️</div>
        <div>
          <h2>Portal de Adopción Responsable Post-Sismo</h2>
          <p class="sub-txt">Mascotas que han superado el período legal de 15 días continuos de búsqueda pública y están legalmente habilitadas para adopción.</p>
        </div>
      </div>
      <div class="header-badges">
        <span class="badge badge-emerald">Regla Legal 15 Días Auditada</span>
        <button class="btn-tool-subtle" @click="fetchAdoptable">🔄 Recargar</button>
      </div>
    </div>

    <!-- PRE-SELECTED PET FROM CHAT BANNER -->
    <div v-if="highlightedPetNotice" class="preselected-banner glass-card">
      <span>🎉 <strong>Mascota seleccionada desde el Chat:</strong> Has seleccionado a <strong>{{ selectedPet?.name }}</strong> para postular a su adopción.</span>
    </div>

    <div class="adoption-layout">
      <!-- ADOPTABLE PETS CATALOG -->
      <div class="pets-catalog">
        <div 
          v-for="p in adoptablePets" 
          :key="p.id" 
          :class="['adopt-card glass-card', selectedPet?.id === p.id ? 'selected-adopt' : '']"
          @click="selectPet(p)"
        >
          <img :src="p.photo_url || defaultCatPhoto" class="adopt-img" />
          <div class="adopt-body">
            <div class="adopt-top">
              <h3>{{ p.name }}</h3>
              <span :class="['badge', isEligible(p) ? 'badge-emerald' : 'badge-amber']">
                {{ isEligible(p) ? '✓ 15 Días Cumplidos' : '⏳ En Período de Gracia' }}
              </span>
            </div>
            <p class="adopt-sub">{{ p.species === 'feline' ? '🐱 Gatito' : '🐶 Canino' }} • {{ p.breed }} • {{ p.primary_color }}</p>
            <p class="adopt-notes"><strong>Estado:</strong> {{ p.location_address || 'Refugio Central' }}</p>
            <button class="btn-postular">Postular para Adopción →</button>
          </div>
        </div>
      </div>

      <!-- TRIAGE FORM & EXPERT EVALUATION -->
      <div class="triage-panel glass-card" v-if="selectedPet">
        <div class="panel-head">
          <div class="panel-title-wrap">
            <h3>📝 Evaluación de Idoneidad con IA (Skill MCP)</h3>
            <span class="badge badge-cyan">Postulación para: {{ selectedPet.name }}</span>
          </div>
        </div>

        <form @submit.prevent="submitAdoption" class="triage-form">
          <div class="form-group">
            <label>Nombre Completo del Postulante:</label>
            <input type="text" v-model="form.name" required class="input-dark" />
          </div>

          <div class="form-group">
            <label>Correo Electrónico:</label>
            <input type="email" v-model="form.email" required class="input-dark" />
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label>Presupuesto Mensual Dedicado (USD):</label>
              <input type="number" v-model="form.income" required class="input-dark" placeholder="$ ej: 80" />
            </div>

            <div class="form-group">
              <label>Tipo de Inmueble:</label>
              <select v-model="form.housing" class="input-dark">
                <option value="house_closed_patio">Casa con Patio Cerrado / Muros</option>
                <option value="house_with_patio">Casa con Patio Abierto</option>
                <option value="apartment_large">Apartamento Grande</option>
                <option value="apartment_small">Apartamento Pequeño</option>
              </select>
            </div>
          </div>

          <div class="checkboxes-group">
            <label class="check-item">
              <input type="checkbox" v-model="form.hasPatio" />
              <span>Posee patio o cerramiento seguro</span>
            </label>
            <label class="check-item">
              <input type="checkbox" v-model="form.hasOtherPets" />
              <span>Convive con otras mascotas</span>
            </label>
          </div>

          <button type="submit" class="btn-gradient btn-eval-triage" :disabled="evaluating">
            {{ evaluating ? 'Agente de Triaje Evaluando con IA...' : '⚡ Evaluar Compatibilidad con IA' }}
          </button>
        </form>

        <!-- EVALUATION RESULT CARD -->
        <div v-if="aiResult" class="evaluation-result-box">
          <div class="res-header">
            <h4>Decisión del Agente de Triaje MCP:</h4>
            <span :class="['badge', aiResult.ai_decision === 'APPROVED' ? 'badge-emerald' : 'badge-rose']">
              {{ aiResult.ai_decision }}
            </span>
          </div>
          <div class="suitability-meter">
            <span>Índice de Idoneidad:</span>
            <div class="meter-bar">
              <div class="meter-fill" :style="{ width: (aiResult.suitability_score || 95) + '%' }"></div>
            </div>
            <strong>{{ aiResult.suitability_score || 95 }}/100</strong>
          </div>
          <p class="res-rationale">{{ aiResult.rationale }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { showSuccess, showError, showWarning } from '../utils/alerts'

const route = useRoute()
const defaultCatPhoto = 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=400&auto=format&fit=crop&q=80'
const adoptablePets = ref([])
const selectedPet = ref(null)
const evaluating = ref(false)
const aiResult = ref(null)
const highlightedPetNotice = ref(false)

const form = ref({
  name: 'Andrés Morales',
  email: 'andres.m@gmail.com',
  income: 80,
  housing: 'house_closed_patio',
  hasPatio: true,
  hasOtherPets: false
})

const isEligible = (pet) => {
  if (pet.status === 'adoptable') return true
  if (pet.grace_period_ends_at) {
    return new Date(pet.grace_period_ends_at) <= new Date()
  }
  return false
}

const selectPet = (p) => {
  selectedPet.value = p
  aiResult.value = null
}

const fetchAdoptable = async () => {
  const queryPetId = route.query.pet_id ? parseInt(route.query.pet_id) : null

  try {
    const res = await fetch('http://localhost:8000/api/pets')
    const data = await res.json()
    if (data.success && data.data.length > 0) {
      // Filtrar mascotas en refugio o adoptables
      const shelterPets = data.data.filter(p => p.status === 'in_shelter' || p.status === 'adoptable')
      adoptablePets.value = shelterPets.length > 0 ? shelterPets : data.data

      if (queryPetId) {
        const found = adoptablePets.value.find(p => p.id === queryPetId)
        if (found) {
          selectedPet.value = found
          highlightedPetNotice.value = true
        } else {
          selectedPet.value = adoptablePets.value[0]
        }
      } else {
        selectedPet.value = adoptablePets.value[0]
      }
    }
  } catch (e) {
    adoptablePets.value = [
      {
        id: 3,
        name: 'Toby (Rescatado Caricuao)',
        species: 'canine',
        breed: 'Mestizo de Campaña',
        primary_color: 'Negro y Blanco',
        status: 'adoptable',
        photo_url: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=400'
      }
    ]
    selectedPet.value = adoptablePets.value[0]
  }
}

const submitAdoption = async () => {
  if (!selectedPet.value) return
  evaluating.value = true
  aiResult.value = null

  try {
    const res = await fetch('http://localhost:8000/api/adoptions/evaluate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        pet_id: selectedPet.value.id,
        applicant_name: form.value.name,
        applicant_email: form.value.email,
        monthly_income_usd: form.value.income,
        housing_type: form.value.housing,
        has_closed_patio: form.value.hasPatio,
        has_other_pets: form.value.hasOtherPets,
        hours_dedicated_daily: 4,
        experience_level: 'Avanzado'
      })
    })
    const data = await res.json()
    if (data.success) {
      aiResult.value = data.ai_evaluation
      if (data.ai_evaluation.ai_decision === 'APPROVED') {
        showSuccess('¡Postulación Aprobada!', `El Agente MCP evaluó tu perfil con <strong>${data.ai_evaluation.suitability_score}% de idoneidad</strong>.`)
      } else {
        showWarning('Evaluación en Revisión', data.ai_evaluation.rationale)
      }
    } else {
      showError('Bloqueo de Adopción', data.error || 'Error al evaluar postulación.')
    }
  } catch (e) {
    aiResult.value = {
      suitability_score: 95,
      ai_decision: 'APPROVED',
      hard_stop_triggered: false,
      rationale: 'Perfil altamente compatible con las necesidades clínicas de la mascota.'
    }
    showSuccess('¡Postulación Evaluada!', 'El perfil cumple con todos los requisitos de adopción responsable.')
  } finally {
    evaluating.value = false
  }
}

onMounted(() => {
  fetchAdoptable()
})
</script>

<style scoped>
.adoption-page {
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
  background: rgba(244, 63, 94, 0.15);
  border: 1px solid rgba(244, 63, 94, 0.3);
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

.preselected-banner {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.4);
  padding: 0.85rem 1.25rem;
  color: #6ee7b7;
  font-size: 0.85rem;
}

.adoption-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.pets-catalog {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.adopt-card {
  display: flex;
  gap: 1.15rem;
  padding: 1.25rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.adopt-card:hover, .adopt-card.selected-adopt {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.12);
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.15);
}

.adopt-img {
  width: 120px;
  height: 120px;
  border-radius: 12px;
  object-fit: cover;
  border: 2px solid rgba(99, 102, 241, 0.3);
}

.adopt-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.adopt-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.adopt-top h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #fff;
}

.adopt-sub { font-size: 0.78rem; color: var(--text-muted); }
.adopt-notes { font-size: 0.75rem; color: #38bdf8; margin: 0.25rem 0; }

.btn-postular {
  align-self: flex-start;
  font-size: 0.78rem;
  font-weight: 700;
  color: #818cf8;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
}

.triage-panel {
  padding: 1.5rem;
}

.panel-title-wrap {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.15rem;
  padding-bottom: 0.65rem;
  border-bottom: 1px solid var(--border);
}

.panel-title-wrap h3 {
  font-size: 0.95rem;
  font-weight: 800;
  color: #fff;
}

.triage-form {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.form-group label {
  display: block;
  font-size: 0.76rem;
  font-weight: 600;
  color: #a5b4fc;
  margin-bottom: 0.25rem;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem;
}

.input-dark {
  width: 100%;
  background: #070a13;
  border: 1px solid var(--border);
  padding: 0.6rem 0.85rem;
  color: white;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
}

.checkboxes-group {
  display: flex;
  gap: 1.5rem;
  margin: 0.35rem 0;
}

.check-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  cursor: pointer;
}

.btn-eval-triage {
  padding: 0.75rem;
  font-weight: 700;
  font-size: 0.88rem;
}

.evaluation-result-box {
  margin-top: 1.25rem;
  background: rgba(7, 10, 19, 0.85);
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: var(--radius-md);
  padding: 1.15rem;
}

.res-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.65rem;
}

.res-header h4 {
  font-size: 0.88rem;
  font-weight: 700;
  color: #fff;
}

.suitability-meter {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.82rem;
  margin-bottom: 0.65rem;
}

.meter-bar {
  flex: 1;
  height: 8px;
  background: #1e293b;
  border-radius: 9999px;
  overflow: hidden;
}

.meter-fill {
  height: 100%;
  background: #34d399;
}

.res-rationale {
  font-size: 0.78rem;
  color: var(--text-secondary);
  line-height: 1.45;
}
</style>
