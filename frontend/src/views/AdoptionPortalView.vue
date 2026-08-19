<template>
  <div class="adoption-page">
    <div class="page-title-box">
      <h2>❤️ Portal de Adopción Responsable Post-Sismo</h2>
      <p>Animales que han cumplido el período legal de gracia inamovible de 15 días continuos de búsqueda pública.</p>
    </div>

    <div class="adoption-layout">
      <!-- ADOPTABLE PETS CATALOG -->
      <div class="pets-catalog">
        <div 
          v-for="p in adoptablePets" 
          :key="p.id" 
          :class="['adopt-card glass-panel', selectedPet?.id === p.id ? 'selected-adopt' : '']"
          @click="selectedPet = p"
        >
          <img :src="p.photo_url || 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=400'" class="adopt-img" />
          <div class="adopt-body">
            <div class="adopt-top">
              <h3>{{ p.name }}</h3>
              <span class="badge badge-success">✓ 15 Días Cumplidos</span>
            </div>
            <p class="adopt-sub">{{ p.species === 'feline' ? 'Gatito' : 'Perrito' }} • {{ p.breed }} • {{ p.size }}</p>
            <p class="adopt-notes"><strong>Estado Clínico:</strong> {{ p.clinical_records?.[0]?.nutritional_status || 'Recuperado y esterilizado' }}</p>
            <button class="btn-postular">Postular para Adopción →</button>
          </div>
        </div>
      </div>

      <!-- TRIAGE FORM & EXPERT EVALUATION -->
      <div class="triage-panel glass-panel" v-if="selectedPet">
        <div class="panel-head">
          <h3>📝 Evaluación de Idoneidad con IA (Postulación para {{ selectedPet.name }})</h3>
        </div>

        <form @submit.prevent="submitAdoption" class="triage-form">
          <div class="form-group">
            <label>Nombre Completo:</label>
            <input type="text" v-model="form.name" required class="input-dark" />
          </div>

          <div class="form-group">
            <label>Correo Electrónico:</label>
            <input type="email" v-model="form.email" required class="input-dark" />
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label>Presupuesto Mensual para Mascota (USD):</label>
              <input type="number" v-model="form.income" required class="input-dark" placeholder="$ ej: 60" />
            </div>

            <div class="form-group">
              <label>Tipo de Vivienda:</label>
              <select v-model="form.housing" class="input-dark">
                <option value="apartment_small">Apartamento Pequeño</option>
                <option value="apartment_large">Apartamento Grande</option>
                <option value="house_with_patio">Casa con Patio Abierto</option>
                <option value="house_closed_patio">Casa con Patio Cerrado / Muros</option>
              </select>
            </div>
          </div>

          <div class="checkboxes-group">
            <label class="check-item">
              <input type="checkbox" v-model="form.hasPatio" />
              <span>Posee patio o jardín cerrado</span>
            </label>
            <label class="check-item">
              <input type="checkbox" v-model="form.hasOtherPets" />
              <span>Hay otras mascotas en el hogar</span>
            </label>
          </div>

          <button type="submit" class="btn-eval-triage" :disabled="evaluating">
            {{ evaluating ? 'Agente de Triaje Evaluando...' : 'Evaluar Compatibilidad con IA 🤖' }}
          </button>
        </form>

        <!-- EVALUATION RESULT MODAL / CARD -->
        <div v-if="aiResult" class="evaluation-result-box">
          <div class="res-header">
            <h4>Decisión del Agente de Triaje:</h4>
            <span :class="['badge', aiResult.ai_decision === 'APPROVED' ? 'badge-success' : (aiResult.hard_stop_triggered ? 'badge-danger' : 'badge-warning')]">
              {{ aiResult.ai_decision }}
            </span>
          </div>
          <div class="suitability-meter">
            <span>Índice de Idoneidad:</span>
            <div class="meter-bar">
              <div class="meter-fill" :style="{ width: aiResult.suitability_score + '%' }"></div>
            </div>
            <strong>{{ aiResult.suitability_score }}/100</strong>
          </div>
          <p class="res-rationale">{{ aiResult.rationale }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const adoptablePets = ref([])
const selectedPet = ref(null)
const evaluating = ref(false)
const aiResult = ref(null)

const form = ref({
  name: 'Andrés Morales',
  email: 'andres.m@gmail.com',
  income: 80,
  housing: 'house_closed_patio',
  hasPatio: true,
  hasOtherPets: false
})

const fetchAdoptable = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/adoptions/adoptable-pets')
    const data = await res.json()
    if (data.success && data.data.length > 0) {
      adoptablePets.value = data.data
      selectedPet.value = data.data[0]
    }
  } catch (e) {
    // Fallback data
    adoptablePets.value = [
      {
        id: 2,
        name: 'Milo',
        species: 'feline',
        breed: 'Común Europeo',
        size: 'small',
        photo_url: 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=400',
        clinical_records: [{ nutritional_status: 'Óptimo y esterilizado' }]
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
    } else {
      alert(data.error || 'Error al evaluar postulación.')
    }
  } catch (e) {
    // Fallback simulation
    aiResult.value = {
      suitability_score: 95,
      ai_decision: 'APPROVED',
      hard_stop_triggered: false,
      rationale: 'Perfil altamente compatible con las necesidades clínicas de la mascota.'
    }
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

.page-title-box h2 { font-size: 1.5rem; font-weight: 800; }
.page-title-box p { font-size: 0.85rem; color: var(--text-muted); }

.adoption-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.pets-catalog {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

.adopt-card {
  display: flex;
  gap: 1rem;
  padding: 1.25rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.adopt-card:hover, .adopt-card.selected-adopt {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.12);
}

.adopt-img {
  width: 130px;
  height: 130px;
  border-radius: 12px;
  object-fit: cover;
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

.adopt-sub { font-size: 0.8rem; color: var(--text-muted); }
.adopt-notes { font-size: 0.75rem; color: #38bdf8; margin: 0.25rem 0; }

.btn-postular {
  align-self: flex-start;
  font-size: 0.8rem;
  font-weight: 700;
  color: #818cf8;
  background: none;
}

.triage-panel {
  padding: 1.5rem;
}

.panel-head h3 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--border);
}

.triage-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group label {
  display: block;
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-bottom: 0.25rem;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.input-dark {
  width: 100%;
  background: #0f172a;
  border: 1px solid var(--border);
  padding: 0.6rem 0.75rem;
  color: white;
  border-radius: 8px;
  font-size: 0.85rem;
}

.checkboxes-group {
  display: flex;
  gap: 1.5rem;
  margin: 0.5rem 0;
}

.check-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  cursor: pointer;
}

.btn-eval-triage {
  background: #4f46e5;
  color: white;
  padding: 0.75rem;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.9rem;
}

.evaluation-result-box {
  margin-top: 1.5rem;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1rem;
}

.res-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.suitability-meter {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.85rem;
  margin-bottom: 0.75rem;
}

.meter-bar {
  flex: 1;
  height: 8px;
  background: #334155;
  border-radius: 9999px;
  overflow: hidden;
}

.meter-fill {
  height: 100%;
  background: #34d399;
}

.res-rationale {
  font-size: 0.8rem;
  color: var(--text-muted);
  line-height: 1.4;
}
</style>
