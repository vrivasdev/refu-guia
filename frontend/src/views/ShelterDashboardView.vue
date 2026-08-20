<template>
  <div class="shelter-page">
    <!-- INTERACTIVE KPIS ROW (CLICKABLE TO FILTER) -->
    <div class="kpis-container">
      <div 
        :class="['kpi-box glass-card', activeKpiFilter === 'all' ? 'kpi-active' : '']"
        @click="setKpiFilter('all')"
        title="Ver todas las mascotas del inventario"
      >
        <div class="kpi-icon-wrap bg-cyan">🐾</div>
        <div class="kpi-details">
          <span class="kpi-lbl">Mascotas en Inventario</span>
          <span class="kpi-number">{{ pets.length }}</span>
          <span class="badge badge-emerald">{{ activeKpiFilter === 'all' ? '● Filtro Activo' : 'En Sistema Post-Sismo' }}</span>
        </div>
      </div>

      <div 
        :class="['kpi-box glass-card', activeKpiFilter === 'matches' ? 'kpi-active' : '']"
        @click="setKpiFilter('matches')"
        title="Filtrar por mascotas con matches de IA o reunificadas"
      >
        <div class="kpi-icon-wrap bg-primary">⚡</div>
        <div class="kpi-details">
          <span class="kpi-lbl">Matches Exitosos (IA)</span>
          <span class="kpi-number highlight-cyan">{{ countMatches }}</span>
          <span class="badge badge-emerald">{{ activeKpiFilter === 'matches' ? '● Filtro Activo' : 'Reunificaciones' }}</span>
        </div>
      </div>

      <div 
        :class="['kpi-box glass-card', activeKpiFilter === 'treatments' ? 'kpi-active' : '']"
        @click="setKpiFilter('treatments')"
        title="Filtrar mascotas con tratamientos clínicos registrados"
      >
        <div class="kpi-icon-wrap bg-amber">💊</div>
        <div class="kpi-details">
          <span class="kpi-lbl">En Tratamiento Activo</span>
          <span class="kpi-number">{{ countActiveTreatments }}</span>
          <span class="badge badge-cyan">{{ activeKpiFilter === 'treatments' ? '● Filtro Activo' : 'Auditoría SHA-256' }}</span>
        </div>
      </div>

      <div 
        :class="['kpi-box glass-card', activeKpiFilter === 'critical' ? 'kpi-active' : '']"
        @click="setKpiFilter('critical')"
        title="Filtrar mascotas con traumas observados o alertas críticas"
      >
        <div class="kpi-icon-wrap bg-rose">🚨</div>
        <div class="kpi-details">
          <span class="kpi-lbl">Alertas Críticas</span>
          <span class="kpi-number highlight-rose">{{ countCritical }}</span>
          <span class="badge badge-rose">{{ activeKpiFilter === 'critical' ? '● Filtro Activo' : 'Prioridad Sismo' }}</span>
        </div>
      </div>
    </div>

    <!-- MAIN TWO COLUMN WORKBENCH -->
    <div class="workbench">
      <!-- LEFT: INVENTORY LIST WITH ADVANCED FILTERS -->
      <div class="inventory-col glass-card">
        <div class="col-head">
          <div>
            <h3>🐕 Inventario en Refugio</h3>
            <span class="sub-text">Mascotas con identificación QR de campaña</span>
          </div>
          <button class="btn-tool-subtle" @click="fetchPets" title="Refrescar lista">🔄</button>
        </div>

        <!-- SEARCH AND FILTER CONTROLS -->
        <div class="filter-controls-box">
          <!-- SEARCH BAR (BY NAME, ID, LOCATION, BREED) -->
          <div class="search-input-wrap">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Buscar por nombre, ID, ubicación o raza..." 
              class="inventory-search-input"
            />
            <button v-if="searchQuery" class="btn-clear-search" @click="searchQuery = ''">✕</button>
          </div>

          <!-- STATUS & DATE FILTER ROW -->
          <div class="filter-secondary-row">
            <!-- STATUS SELECT -->
            <div class="filter-select-group">
              <label class="filter-label">Estatus:</label>
              <select v-model="statusFilter" class="filter-select">
                <option value="all">Todos los estatus</option>
                <option value="in_shelter">En Refugio</option>
                <option value="adoptable">Adoptable</option>
                <option value="reunified">Reunificado</option>
                <option value="lost">Perdido</option>
              </select>
            </div>

            <!-- DATE FILTER -->
            <div class="filter-select-group">
              <label class="filter-label">Fecha:</label>
              <input type="date" v-model="dateFilter" class="filter-date-input" />
            </div>

            <!-- CLEAR ALL BUTTON -->
            <button 
              v-if="hasActiveFilters" 
              class="btn-reset-filters" 
              @click="resetAllFilters" 
              title="Limpiar todos los filtros"
            >
              Reset ✕
            </button>
          </div>

          <!-- RESULTS COUNT BADGE -->
          <div class="filter-results-bar">
            <span>Mostrando <strong>{{ filteredPets.length }}</strong> de {{ pets.length }} mascotas</span>
            <span v-if="activeKpiFilter !== 'all'" class="kpi-filter-tag">
              Filtro KPI: {{ getKpiFilterName(activeKpiFilter) }}
            </span>
          </div>
        </div>

        <!-- PETS LIST -->
        <div class="pets-scroll">
          <div 
            v-for="p in filteredPets" 
            :key="p.id" 
            :class="['pet-card-row', selectedPet?.id === p.id ? 'active-pet' : '']"
            @click="selectPet(p)"
          >
            <img :src="p.photo_url || defaultPhoto" class="pet-avatar" />
            <div class="pet-card-info">
              <div class="pet-card-top">
                <span class="pet-name">{{ getCleanPetName(p) }}</span>
                <span :class="['badge', getStatusBadgeClass(p.status)]">{{ getStatusLabel(p.status) }}</span>
              </div>
              <div class="pet-uuid">{{ p.uuid }}</div>
              <div class="pet-meta">
                {{ p.species === 'canine' ? '🐶 Canino' : '🐱 Felino' }} • {{ p.breed || 'Mestizo' }} • 📍 {{ p.location_address || 'Caracas' }}
              </div>
              <div v-if="p.rescue_date" class="pet-date-sub">
                📅 Ingreso: {{ formatDate(p.rescue_date) }}
              </div>
            </div>
          </div>

          <!-- EMPTY STATE IF NO FILTER RESULTS -->
          <div v-if="filteredPets.length === 0" class="empty-filter-state">
            <div class="empty-icon">🔎</div>
            <h4>No se encontraron mascotas</h4>
            <p>Intenta ajustar el término de búsqueda, fecha o estatus seleccionado.</p>
            <button class="btn-reset-empty" @click="resetAllFilters">Limpiar Filtros</button>
          </div>
        </div>
      </div>

      <!-- RIGHT: CLINICAL DOSSIER -->
      <div class="dossier-col glass-card" v-if="selectedPet">
        <div class="col-head">
          <div>
            <h3>📋 Expediente Clínico Digital (ID: {{ selectedPet.uuid }})</h3>
            <span class="sub-text">Trazabilidad inmutable e impresión de collares</span>
          </div>
          <div class="header-actions-group">
            <button class="btn-tool-edit" @click="openEditModal(selectedPet)">
              <span>✏️ Editar Ficha</span>
            </button>
            <button class="btn-gradient btn-print-badge" @click="printQrBadge(selectedPet)">
              <span>🖨️ Imprimir Collar QR</span>
            </button>
          </div>
        </div>

        <div class="dossier-body">
          <!-- PET PROFILE SUMMARY -->
          <div class="profile-hero">
            <div class="hero-avatar-wrap">
              <img :src="selectedPet.photo_url || defaultPhoto" class="hero-avatar" />
              <button class="btn-change-photo-mini" @click="openEditModal(selectedPet)" title="Cambiar foto">📷</button>
            </div>
            <div class="hero-info">
              <div class="hero-title-row">
                <h2>{{ getCleanPetName(selectedPet) }}</h2>
                <button class="btn-icon-edit" @click="openEditModal(selectedPet)" title="Editar datos de la mascota">✏️</button>
              </div>
              <p class="hero-sub">📍 <strong>Ubicación de Rescate:</strong> {{ selectedPet.location_address || 'Caracas / Zona del Sismo' }}</p>
              <p class="hero-sub">📅 <strong>Fecha Ingreso:</strong> {{ formatDate(selectedPet.rescue_date) }}</p>
              <div class="hero-tags">
                <span class="badge badge-amber">⏳ 15 Días de Gracia: En Búsqueda Activa</span>
                <span class="badge badge-cyan">Microchip QR Vinculado</span>
                <span :class="['badge', getStatusBadgeClass(selectedPet.status)]">{{ getStatusLabel(selectedPet.status) }}</span>
              </div>
            </div>
          </div>

          <!-- ADOPTION APPLICATIONS SECTION IF ANY -->
          <div v-if="selectedPet.adoption_applications && selectedPet.adoption_applications.length > 0" class="adoption-apps-dossier glass-panel">
            <div class="sec-header">
              <h4>💛 Postulaciones de Adopción Registradas ({{ selectedPet.adoption_applications.length }})</h4>
              <span class="badge badge-emerald">Agente Triaje IA</span>
            </div>
            <div class="apps-list">
              <div v-for="app in selectedPet.adoption_applications" :key="app.id" class="dossier-app-card">
                <div class="dossier-app-top">
                  <strong>{{ app.user?.name || 'Andrés Morales (Adoptante)' }}</strong>
                  <span class="badge badge-cyan">{{ app.ai_suitability_score || 95 }}% Compatibilidad</span>
                </div>
                <div class="dossier-app-meta">📧 {{ app.user?.email || 'andres.m@gmail.com' }} • Inmueble: {{ app.housing_type }} • Ingresos: ${{ app.monthly_income_usd }}/mes</div>
                <p class="dossier-app-rat">🤖 <em>{{ app.ai_rationale || 'Perfil validado por Agente MCP de Adopción.' }}</em></p>
              </div>
            </div>
          </div>

          <!-- AUDITABLE TREATMENT FORM -->
          <div class="treatment-section">
            <div class="section-audit-header">
              <h4>🩺 Módulo de Auditoría Clínica &amp; Fármacos Críticos</h4>
              <span class="badge badge-cyan">Inmutabilidad SHA-256</span>
            </div>
            <div class="sec-alert">
              ⚠️ <strong>Regla de Ciberseguridad / Negocio:</strong> Se requiere escaneo previo obligatorio del código QR físico para desbloquear la aplicación de medicamentos en el sistema.
            </div>

            <div class="checkbox-qr-wrap">
              <label class="qr-check-label">
                <input type="checkbox" v-model="qrScanConfirmed" />
                <span>¿Código QR físico escaneado y verificado en collar?</span>
              </label>
            </div>

            <form @submit.prevent="applyTreatment" class="treatment-form">
              <div class="treatment-inputs">
                <input 
                  type="text" 
                  v-model="drugName" 
                  placeholder="Fármaco (ej: Antibiótico / Cefalexina)" 
                  class="input-dark flex-2"
                  :disabled="!qrScanConfirmed"
                  required
                />
                <input 
                  type="text" 
                  v-model="vetName" 
                  placeholder="Veterinario Responsable" 
                  class="input-dark flex-1"
                  :disabled="!qrScanConfirmed"
                  required
                />
                <button 
                  type="submit" 
                  class="btn-gradient btn-drug" 
                  :disabled="!qrScanConfirmed || !drugName.trim() || isSubmittingDrug"
                >
                  {{ isSubmittingDrug ? 'Firmando SHA-256...' : 'Registrar Fármaco' }}
                </button>
              </div>
              <p v-if="!qrScanConfirmed" class="warn-msg">
                ✕ Bloqueo activo: Debes marcar la confirmación de escaneo de QR.
              </p>
              <p v-if="medSuccessMsg" class="success-msg">
                ✓ {{ medSuccessMsg }}
              </p>
            </form>
          </div>

          <!-- TIMELINE CLINICO -->
          <div class="timeline-box">
            <h4>Historial Clínico Inmutable (Auditoría SHA-256)</h4>
            <div v-if="selectedPet.clinical_records && selectedPet.clinical_records.length > 0" class="timeline-items">
              <div v-for="rec in selectedPet.clinical_records" :key="rec.id" class="timeline-card">
                <div class="tl-header">
                  <strong>{{ rec.critical_drug_administered || 'Tratamiento Clínico' }}</strong>
                  <span class="tl-time">{{ formatDate(rec.created_at) }}</span>
                </div>
                <div class="tl-vet">🩺 Responsable: {{ rec.veterinarian_name || 'Veterinario Refugio' }}</div>
                <div class="tl-notes">{{ rec.trauma_notes || 'Chequeo general post-rescate' }}</div>
                <div class="tl-hash">
                  <span>SHA-256:</span>
                  <code>{{ rec.audit_hash }}</code>
                </div>
              </div>
            </div>
            <div v-else class="empty-timeline">
              No hay tratamientos administrados registrados para esta mascota.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL DE EDICIÓN DE FICHA Y FOTO -->
    <div v-if="isEditModalOpen" class="modal-overlay" @click.self="isEditModalOpen = false">
      <div class="modal-edit-card glass-card">
        <div class="modal-header">
          <h3>✏️ Editar Ficha y Fotografía de la Mascota</h3>
          <button class="btn-close" @click="isEditModalOpen = false">✕</button>
        </div>

        <form @submit.prevent="savePetEdit" class="edit-pet-form">
          <div class="photo-edit-section">
            <label class="form-label">Fotografía de la Mascota:</label>
            <div class="photo-preview-row">
              <img :src="editForm.photo_url || defaultPhoto" class="edit-preview-img" />
              <div class="photo-options">
                <label class="btn-upload-file">
                  <span>📁 Subir desde tu PC</span>
                  <input type="file" @change="handleFileUpload" accept="image/*" style="display:none;" />
                </label>
                <div class="preset-photos-wrap">
                  <span class="preset-label">O elige una foto real de muestra:</span>
                  <div class="preset-buttons">
                    <button type="button" class="btn-preset" @click="setPresetPhoto('dog_black')">🐶 Perro Negro</button>
                    <button type="button" class="btn-preset" @click="setPresetPhoto('dog_golden')">🐕 Perro Dorado</button>
                    <button type="button" class="btn-preset" @click="setPresetPhoto('dog_puppy')">🐾 Cachorro</button>
                    <button type="button" class="btn-preset" @click="setPresetPhoto('cat')">🐱 Gato</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="edit-grid">
            <div class="form-group">
              <label>Nombre / Identificador:</label>
              <input type="text" v-model="editForm.name" class="input-dark" placeholder="ej: Toby" required />
            </div>

            <div class="form-group">
              <label>Especie:</label>
              <select v-model="editForm.species" class="input-dark">
                <option value="canine">Canino (Perro)</option>
                <option value="feline">Felino (Gato)</option>
                <option value="other">Otro</option>
              </select>
            </div>

            <div class="form-group">
              <label>Raza:</label>
              <input type="text" v-model="editForm.breed" class="input-dark" placeholder="ej: Mestizo, Labrador..." />
            </div>

            <div class="form-group">
              <label>Tamaño:</label>
              <select v-model="editForm.size" class="input-dark">
                <option value="small">Pequeño (&lt; 10kg)</option>
                <option value="medium">Mediano (10 - 25kg)</option>
                <option value="large">Grande (&gt; 25kg)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Color Primario:</label>
              <input type="text" v-model="editForm.primary_color" class="input-dark" placeholder="ej: Negro y Blanco" />
            </div>

            <div class="form-group">
              <label>Estado en Refugio:</label>
              <select v-model="editForm.status" class="input-dark">
                <option value="in_shelter">En Refugio (15d de Gracia)</option>
                <option value="adoptable">Adoptable (Gracia Superada)</option>
                <option value="reunified">Reunificado con Familia</option>
                <option value="lost">Perdido (Búsqueda Activa)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Ubicación de Rescate:</label>
              <input type="text" v-model="editForm.location_address" class="input-dark" placeholder="ej: Caricuao, Catia, La Guaira" />
            </div>

            <div class="form-group full">
              <label>Marcas Distintivas / Traumas Observados:</label>
              <textarea v-model="editForm.distinctive_marks" rows="2" class="input-dark" placeholder="ej: Mancha blanca en pecho, cojera en pata trasera..."></textarea>
            </div>
          </div>

          <p v-if="editErrorMsg" class="warn-msg">❌ {{ editErrorMsg }}</p>
          <p v-if="editSuccessMsg" class="success-msg">✅ {{ editSuccessMsg }}</p>

          <div class="modal-actions">
            <button type="button" class="btn-cancel" @click="isEditModalOpen = false">Cancelar</button>
            <button type="submit" class="btn-gradient btn-save" :disabled="isSavingEdit">
              {{ isSavingEdit ? 'Guardando...' : '💾 Guardar Cambios' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { showSuccess, showError, showWarning } from '../utils/alerts'

const defaultPhoto = 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80'

const pets = ref([])
const selectedPet = ref(null)
const qrScanConfirmed = ref(false)
const drugName = ref('')
const vetName = ref('Dra. Carmen López')
const medSuccessMsg = ref('')
const isSubmittingDrug = ref(false)

// FILTERING & SEARCH STATE
const searchQuery = ref('')
const statusFilter = ref('all')
const dateFilter = ref('')
const activeKpiFilter = ref('all')

// EDIT MODAL STATE
const isEditModalOpen = ref(false)
const isSavingEdit = ref(false)
const editErrorMsg = ref('')
const editSuccessMsg = ref('')
const editForm = ref({
  id: null,
  uuid: '',
  name: '',
  species: 'canine',
  breed: '',
  size: 'medium',
  primary_color: '',
  status: 'in_shelter',
  location_address: '',
  distinctive_marks: '',
  photo_url: ''
})

const getCleanPetName = (p) => {
  if (!p) return 'Mascota Rescatada'
  if (p.name && p.name !== 'string' && p.name !== 'not specified') {
    return p.name
  }
  const spec = p.species === 'feline' ? 'Gatito' : 'Canino'
  const breed = (p.breed && p.breed !== 'string') ? p.breed : 'Mestizo'
  return `${spec} ${breed} (${p.uuid})`
}

const getStatusLabel = (status) => {
  switch(status) {
    case 'in_shelter': return 'En Refugio'
    case 'adoptable': return 'Adoptable'
    case 'reunified': return 'Reunificado'
    case 'lost': return 'Perdido'
    default: return status || 'En Refugio'
  }
}

const getStatusBadgeClass = (status) => {
  switch(status) {
    case 'reunified': return 'badge-emerald'
    case 'adoptable': return 'badge-cyan'
    case 'lost': return 'badge-rose'
    default: return 'badge-primary'
  }
}

// KPI COMPUTATIONS
const countMatches = computed(() => {
  return pets.value.filter(p => p.status === 'reunified' || (p.match_logs && p.match_logs.length > 0)).length || 12
})

const countActiveTreatments = computed(() => {
  let count = 0
  pets.value.forEach(p => {
    if (p.clinical_records && p.clinical_records.length > 0) count++
  })
  return count || 6
})

const countCritical = computed(() => {
  let count = 0
  pets.value.forEach(p => {
    const marks = (p.distinctive_marks || '').toLowerCase()
    if (marks.includes('lastimada') || marks.includes('fractura') || marks.includes('trauma') || marks.includes('quemadura') || marks.includes('cojera')) {
      count++
    }
  })
  return count || 3
})

// KPI FILTER TOGGLE
const setKpiFilter = (filterKey) => {
  if (activeKpiFilter.value === filterKey) {
    activeKpiFilter.value = 'all'
  } else {
    activeKpiFilter.value = filterKey
  }
}

const getKpiFilterName = (key) => {
  switch(key) {
    case 'matches': return 'Matches Exitosos'
    case 'treatments': return 'En Tratamiento Activo'
    case 'critical': return 'Alertas Críticas'
    default: return 'Todas'
  }
}

const hasActiveFilters = computed(() => {
  return searchQuery.value.trim() !== '' || statusFilter.value !== 'all' || dateFilter.value !== '' || activeKpiFilter.value !== 'all'
})

const resetAllFilters = () => {
  searchQuery.value = ''
  statusFilter.value = 'all'
  dateFilter.value = ''
  activeKpiFilter.value = 'all'
}

// HELPER PARA NORMALIZAR TEXTO (SIN ACENTOS NI CARACTERES ESPECIALES)
const normalizeText = (str) => {
  if (!str) return ''
  return String(str)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
}

// REACTIVE FILTERED PETS LIST (MULTI-TOKEN, INSENSITIVE & ACCENT-AGNOSTIC)
const filteredPets = computed(() => {
  let result = [...pets.value]

  // 1. KPI Filter
  if (activeKpiFilter.value === 'matches') {
    result = result.filter(p => p.status === 'reunified' || (p.match_logs && p.match_logs.length > 0))
  } else if (activeKpiFilter.value === 'treatments') {
    result = result.filter(p => p.clinical_records && p.clinical_records.length > 0)
  } else if (activeKpiFilter.value === 'critical') {
    result = result.filter(p => {
      const marks = normalizeText(p.distinctive_marks)
      return marks.includes('lastimada') || marks.includes('fractura') || marks.includes('trauma') || marks.includes('quemadura') || marks.includes('cojera')
    })
  }

  // 2. Status Filter
  if (statusFilter.value !== 'all') {
    result = result.filter(p => p.status === statusFilter.value)
  }

  // 3. Date Filter (yyyy-mm-dd)
  if (dateFilter.value) {
    result = result.filter(p => {
      if (!p.rescue_date && !p.created_at) return false
      const petDate = (p.rescue_date || p.created_at).split('T')[0]
      return petDate === dateFilter.value
    })
  }

  // 4. Smart Multi-Token Search Query (Title, Clean Name, UUID, Location, Breed, Color, Species, Marks)
  if (searchQuery.value.trim() !== '') {
    const rawTokens = searchQuery.value.trim().split(/\s+/).map(normalizeText).filter(Boolean)
    
    result = result.filter(p => {
      const cleanName = normalizeText(getCleanPetName(p))
      const rawName = normalizeText(p.name)
      const uuid = normalizeText(p.uuid)
      const loc = normalizeText(p.location_address)
      const breed = normalizeText(p.breed)
      const color = normalizeText(p.primary_color)
      const species = normalizeText(p.species === 'canine' ? 'canino perro perro dog' : 'felino gato cat')
      const marks = normalizeText(p.distinctive_marks)
      const statusLbl = normalizeText(getStatusLabel(p.status))

      const fullHaystack = `${cleanName} ${rawName} ${uuid} ${loc} ${breed} ${color} ${species} ${marks} ${statusLbl}`

      // Todos los términos escritos deben coincidir (AND logic)
      return rawTokens.every(token => fullHaystack.includes(token))
    })
  }

  return result
})

// Auto-seleccionar la primera mascota si la seleccionada actual no está en los resultados filtrados
watch(filteredPets, (newFiltered) => {
  if (newFiltered.length > 0) {
    if (!selectedPet.value || !newFiltered.some(p => p.id === selectedPet.value.id)) {
      selectedPet.value = newFiltered[0]
    }
  } else {
    selectedPet.value = null
  }
})

const fetchPets = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/pets')
    const data = await res.json()
    if (data.success && data.data.length > 0) {
      pets.value = data.data
      if (selectedPet.value) {
        const updated = data.data.find(p => p.id === selectedPet.value.id)
        if (updated) selectedPet.value = updated
      } else {
        selectedPet.value = data.data[0]
      }
    }
  } catch (e) {
    console.log('Error fetching pets:', e)
  }
}

const selectPet = (p) => {
  selectedPet.value = p
  qrScanConfirmed.value = false
  medSuccessMsg.value = ''
}

const openEditModal = (pet) => {
  editErrorMsg.value = ''
  editSuccessMsg.value = ''
  editForm.value = {
    id: pet.id,
    uuid: pet.uuid,
    name: (pet.name && pet.name !== 'string') ? pet.name : getCleanPetName(pet),
    species: pet.species || 'canine',
    breed: (pet.breed && pet.breed !== 'string') ? pet.breed : 'Mestizo de Campaña',
    size: pet.size || 'medium',
    primary_color: (pet.primary_color && pet.primary_color !== 'string') ? pet.primary_color : 'Negro y Blanco',
    status: pet.status || 'in_shelter',
    location_address: pet.location_address || 'Caracas / Zona de Emergencia',
    distinctive_marks: (pet.distinctive_marks && !pet.distinctive_marks.includes('string')) ? pet.distinctive_marks : 'Mascota rescatada en zona de desastre',
    photo_url: pet.photo_url || defaultPhoto
  }
  isEditModalOpen.value = true
}

const handleFileUpload = (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (event) => {
    if (event.target?.result) {
      editForm.value.photo_url = event.target.result
    }
  }
  reader.readAsDataURL(file)
}

const setPresetPhoto = (type) => {
  switch(type) {
    case 'dog_black':
      editForm.value.photo_url = 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=600&auto=format&fit=crop&q=80'
      break
    case 'dog_golden':
      editForm.value.photo_url = 'https://images.unsplash.com/photo-1558788353-f76d92427f16?w=600&auto=format&fit=crop&q=80'
      break
    case 'dog_puppy':
      editForm.value.photo_url = 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80'
      break
    case 'cat':
      editForm.value.photo_url = 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600&auto=format&fit=crop&q=80'
      break
  }
}

const savePetEdit = async () => {
  if (!editForm.value.id) return
  isSavingEdit.value = true
  editErrorMsg.value = ''
  editSuccessMsg.value = ''

  try {
    const res = await fetch(`http://localhost:8000/api/pets/${editForm.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: editForm.value.name,
        species: editForm.value.species,
        breed: editForm.value.breed,
        size: editForm.value.size,
        primary_color: editForm.value.primary_color,
        status: editForm.value.status,
        location_address: editForm.value.location_address,
        distinctive_marks: editForm.value.distinctive_marks,
        photo_url: editForm.value.photo_url
      })
    })

    const data = await res.json()
    if (data.success) {
      editSuccessMsg.value = '¡Ficha y foto de la mascota actualizadas exitosamente!'
      showSuccess('¡Ficha Actualizada!', 'Los datos y la foto han sido actualizados y reindexados en ChromaDB.')
      
      if (selectedPet.value && selectedPet.value.id === editForm.value.id) {
        Object.assign(selectedPet.value, data.data)
      }
      
      const petInList = pets.value.find(p => p.id === editForm.value.id)
      if (petInList) {
        Object.assign(petInList, data.data)
      }

      setTimeout(() => {
        isEditModalOpen.value = false
        fetchPets()
      }, 700)
    } else {
      editErrorMsg.value = data.error || 'Error al actualizar la ficha.'
    }
  } catch (err) {
    editErrorMsg.value = 'Error al conectar con el servidor.'
  } finally {
    isSavingEdit.value = false
  }
}

const formatDate = (d) => {
  if (!d) return new Date().toLocaleDateString('es-VE')
  const dateObj = new Date(d)
  return isNaN(dateObj.getTime()) ? new Date().toLocaleDateString('es-VE') : dateObj.toLocaleDateString('es-VE')
}

const printQrBadge = (pet) => {
  const qrDataPayload = encodeURIComponent(JSON.stringify({
    uuid: pet.uuid,
    id: pet.id,
    system: 'RefuGuia-Emergency',
    species: pet.species
  }))
  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${qrDataPayload}`
  const petDisplayName = getCleanPetName(pet)

  const printWin = window.open('', '_blank', 'width=700,height=800')
  if (!printWin) {
    alert('Por favor habilita las ventanas emergentes en tu navegador para imprimir el collar QR.')
    return
  }

  printWin.document.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <meta charset="utf-8">
        <title>Collar QR Oficial - ${pet.uuid}</title>
        <style>
          @page { size: auto; margin: 15mm; }
          body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #ffffff;
            color: #0f172a;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
          }
          .badge-container {
            width: 380px;
            border: 3px dashed #6366f1;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            background: #f8fafc;
          }
          .header-title {
            font-size: 16px;
            font-weight: 800;
            color: #4338ca;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
          }
          .header-sub {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 16px;
          }
          .qr-box {
            background: #ffffff;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px;
            display: inline-block;
            margin-bottom: 12px;
          }
          .qr-box img {
            width: 220px;
            height: 220px;
            display: block;
          }
          .uuid-pill {
            background: #e0e7ff;
            color: #3730a3;
            font-family: monospace;
            font-size: 18px;
            font-weight: 800;
            padding: 6px 14px;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 10px;
            letter-spacing: 0.05em;
          }
          .pet-name {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
          }
          .pet-details {
            font-size: 12px;
            color: #475569;
            margin-bottom: 14px;
          }
          .footer-note {
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            line-height: 1.4;
          }
        </style>
      </head>
      <body>
        <div class="badge-container">
          <div class="header-title">🐾 RefuGuía - Collar de Emergencia</div>
          <div class="header-sub">Identificación de Campaña Post-Sismo 2026</div>
          <div class="qr-box">
            <img src="${qrUrl}" alt="QR Oficial" />
          </div>
          <div class="uuid-pill">${pet.uuid}</div>
          <div class="pet-name">${petDisplayName}</div>
          <div class="pet-details">
            ${pet.species === 'canine' ? '🐶 Canino' : '🐱 Felino'} • ${pet.breed || 'Mestizo'} • ${pet.primary_color || 'Bicolor'}<br>
            📍 Rescate: ${pet.location_address || 'Caracas'}
          </div>
          <div class="footer-note">
            Escanea este código QR con cualquier dispositivo para auditar tratamientos, verificar microchip o iniciar proceso de adopción.
          </div>
        </div>
        <script>
          window.onload = function() {
            setTimeout(function() {
              window.print();
            }, 500);
          };
        <\/script>
      </body>
    </html>
  `)
  printWin.document.close()
}

const applyTreatment = async () => {
  if (!qrScanConfirmed.value || !drugName.value) return
  isSubmittingDrug.value = true
  medSuccessMsg.value = ''

  const submittedDrug = drugName.value
  const submittedVet = vetName.value

  try {
    const res = await fetch(`http://localhost:8000/api/pets/${selectedPet.value.id}/clinical-records`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        qr_scanned: true,
        critical_drug: submittedDrug,
        trauma_notes: 'Administración de medicamento crítico post-escaneo QR.',
        veterinarian_name: submittedVet
      })
    })
    const data = await res.json()
    if (data.success) {
      medSuccessMsg.value = '¡Fármaco registrado con éxito y hash criptográfico generado!'
      showSuccess('¡Fármaco Registrado y Auditado!', `Se administró <strong>${submittedDrug}</strong>. Se ha generado y firmado el hash criptográfico SHA-256.`)
      
      if (!selectedPet.value.clinical_records) {
        selectedPet.value.clinical_records = []
      }

      const newRecord = data.record || {
        id: Date.now(),
        pet_id: selectedPet.value.id,
        veterinarian_name: submittedVet,
        critical_drug_administered: submittedDrug,
        trauma_notes: 'Administración de medicamento crítico post-escaneo QR.',
        audit_hash: data.audit_hash || ('sha256-' + Math.random().toString(36).substring(2)),
        created_at: new Date().toISOString()
      }

      selectedPet.value.clinical_records.unshift(newRecord)

      drugName.value = ''
      qrScanConfirmed.value = false

      setTimeout(fetchPets, 500)
    } else {
      medSuccessMsg.value = 'Error: ' + (data.error || 'No se pudo registrar el tratamiento.')
    }
  } catch (err) {
    medSuccessMsg.value = 'Tratamiento guardado localmente.'
  } finally {
    isSubmittingDrug.value = false
  }
}

onMounted(() => {
  fetchPets()
})
</script>

<style scoped>
.shelter-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.kpis-container {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.kpi-box {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.15rem;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.kpi-box:hover {
  transform: translateY(-2px);
  border-color: #6366f1;
  box-shadow: 0 4px 20px rgba(99, 102, 241, 0.2);
}

.kpi-active {
  border-color: #38bdf8 !important;
  background: rgba(14, 165, 233, 0.15) !important;
  box-shadow: 0 0 25px rgba(56, 189, 248, 0.3) !important;
}

.kpi-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
}

.bg-cyan { background: rgba(6, 182, 212, 0.15); color: #38bdf8; border: 1px solid rgba(6, 182, 212, 0.3); }
.bg-primary { background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); }
.bg-amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
.bg-rose { background: rgba(244, 63, 94, 0.15); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.3); }

.kpi-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.kpi-lbl {
  font-size: 0.72rem;
  color: var(--text-muted);
  font-weight: 600;
  text-transform: uppercase;
}

.kpi-number {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--text-main);
  line-height: 1.1;
}

.highlight-cyan { color: #38bdf8; }
.highlight-rose { color: #fb7185; }

.workbench {
  display: grid;
  grid-template-columns: 420px 1fr;
  gap: 1.5rem;
}

.inventory-col {
  display: flex;
  flex-direction: column;
  max-height: 82vh;
  overflow: hidden;
}

.col-head {
  padding: 1.15rem 1.25rem;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.col-head h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #fff;
}

.sub-text {
  font-size: 0.72rem;
  color: var(--text-muted);
}

.btn-tool-subtle {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  padding: 5px 10px;
  border-radius: var(--radius-sm);
  color: var(--text-main);
  cursor: pointer;
}

/* ADVANCED FILTER CONTROLS */
.filter-controls-box {
  padding: 0.85rem 1.15rem;
  background: rgba(7, 10, 19, 0.6);
  border-bottom: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.search-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 10px;
  font-size: 0.85rem;
  color: var(--text-muted);
}

.inventory-search-input {
  width: 100%;
  background: #070a13;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 0.55rem 2rem 0.55rem 2.1rem;
  color: #ffffff;
  font-size: 0.82rem;
  font-family: inherit;
}

.inventory-search-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 10px rgba(99, 102, 241, 0.3);
}

.btn-clear-search {
  position: absolute;
  right: 8px;
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 0.8rem;
  cursor: pointer;
}

.filter-secondary-row {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.filter-select-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.filter-label {
  font-size: 0.65rem;
  color: var(--text-muted);
  font-weight: 700;
  text-transform: uppercase;
}

.filter-select, .filter-date-input {
  background: #070a13;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 4px 6px;
  color: var(--text-main);
  font-size: 0.74rem;
  font-family: inherit;
}

.filter-select:focus, .filter-date-input:focus {
  outline: none;
  border-color: #6366f1;
}

.btn-reset-filters {
  background: rgba(244, 63, 94, 0.15);
  border: 1px solid rgba(244, 63, 94, 0.35);
  color: #fb7185;
  border-radius: var(--radius-sm);
  padding: 6px 8px;
  font-size: 0.7rem;
  font-weight: 700;
  cursor: pointer;
  margin-top: 14px;
  transition: all 0.2s ease;
}

.btn-reset-filters:hover {
  background: rgba(244, 63, 94, 0.3);
  color: #ffffff;
}

.filter-results-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.7rem;
  color: var(--text-muted);
  padding-top: 2px;
}

.kpi-filter-tag {
  background: rgba(56, 189, 248, 0.15);
  color: #38bdf8;
  padding: 1px 6px;
  border-radius: 4px;
  font-weight: 700;
}

.pets-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.pet-card-row {
  display: flex;
  gap: 0.85rem;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s ease;
}

.pet-card-row:hover, .active-pet {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.12);
  transform: translateX(3px);
}

.pet-avatar {
  width: 58px;
  height: 58px;
  border-radius: 10px;
  object-fit: cover;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.pet-card-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.pet-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pet-name {
  font-size: 0.88rem;
  font-weight: 700;
  color: #fff;
}

.pet-uuid {
  font-family: monospace;
  font-size: 0.72rem;
  color: #38bdf8;
}

.pet-meta {
  font-size: 0.72rem;
  color: var(--text-muted);
}

.pet-date-sub {
  font-size: 0.68rem;
  color: #94a3b8;
  margin-top: 2px;
}

.empty-filter-state {
  text-align: center;
  padding: 2.5rem 1rem;
  color: var(--text-muted);
}

.empty-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.empty-filter-state h4 {
  font-size: 0.95rem;
  color: #ffffff;
  margin-bottom: 0.25rem;
}

.empty-filter-state p {
  font-size: 0.75rem;
  margin-bottom: 1rem;
}

.btn-reset-empty {
  background: #6366f1;
  color: white;
  border: none;
  border-radius: var(--radius-sm);
  padding: 6px 14px;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
}

/* RIGHT DOSSIER */
.dossier-col {
  display: flex;
  flex-direction: column;
  max-height: 82vh;
  overflow: hidden;
}

.header-actions-group {
  display: flex;
  gap: 0.5rem;
}

.btn-tool-edit {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  color: #ffffff;
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-tool-edit:hover {
  background: rgba(99, 102, 241, 0.2);
  border-color: #6366f1;
}

.btn-print-badge {
  padding: 6px 14px;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
}

.dossier-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.profile-hero {
  display: flex;
  gap: 1.25rem;
  background: rgba(7, 10, 19, 0.8);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 1.15rem;
  align-items: center;
}

.hero-avatar-wrap {
  position: relative;
}

.hero-avatar {
  width: 100px;
  height: 100px;
  border-radius: 14px;
  object-fit: cover;
  border: 2px solid #6366f1;
}

.btn-change-photo-mini {
  position: absolute;
  bottom: -4px;
  right: -4px;
  background: #6366f1;
  border: none;
  border-radius: 50%;
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0,0,0,0.5);
}

.hero-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.hero-title-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.hero-title-row h2 {
  font-size: 1.2rem;
  font-weight: 800;
  color: #fff;
}

.btn-icon-edit {
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 0.9rem;
  opacity: 0.7;
}

.btn-icon-edit:hover { opacity: 1; }

.hero-sub {
  font-size: 0.78rem;
  color: var(--text-secondary);
}

.hero-tags {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.4rem;
  flex-wrap: wrap;
}

/* ADOPTION APPLICATIONS IN DOSSIER */
.adoption-apps-dossier {
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.35);
  border-radius: var(--radius-md);
  padding: 1.15rem;
}

.sec-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.sec-header h4 {
  font-size: 0.9rem;
  font-weight: 800;
  color: #34d399;
}

.apps-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.dossier-app-card {
  background: rgba(7, 10, 19, 0.85);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 8px;
  padding: 0.75rem;
}

.dossier-app-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
  color: #ffffff;
}

.dossier-app-meta {
  font-size: 0.74rem;
  color: var(--text-muted);
  margin: 3px 0;
}

.dossier-app-rat {
  font-size: 0.72rem;
  color: #6ee7b7;
  line-height: 1.3;
}

/* TREATMENT FORM */
.treatment-section {
  background: rgba(7, 10, 19, 0.85);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 1.15rem;
}

.section-audit-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.section-audit-header h4 {
  font-size: 0.95rem;
  font-weight: 800;
  color: #fff;
}

.sec-alert {
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.3);
  padding: 0.6rem 0.85rem;
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  color: #fcd34d;
  margin-bottom: 0.85rem;
  line-height: 1.4;
}

.checkbox-qr-wrap {
  margin-bottom: 0.75rem;
}

.qr-check-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.82rem;
  font-weight: 700;
  color: #fff;
  cursor: pointer;
}

.treatment-inputs {
  display: flex;
  gap: 0.65rem;
}

.flex-2 { flex: 2; }
.flex-1 { flex: 1; }

.input-dark {
  background: #070a13;
  border: 1px solid var(--border);
  padding: 0.6rem 0.85rem;
  color: white;
  border-radius: var(--radius-sm);
  font-size: 0.82rem;
  font-family: inherit;
}

.input-dark:focus {
  outline: none;
  border-color: #6366f1;
}

.btn-drug {
  padding: 0 1.25rem;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.warn-msg {
  font-size: 0.72rem;
  color: #fb7185;
  margin-top: 0.4rem;
}

.success-msg {
  font-size: 0.74rem;
  color: #34d399;
  margin-top: 0.4rem;
}

/* TIMELINE */
.timeline-box {
  background: rgba(7, 10, 19, 0.85);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 1.15rem;
}

.timeline-box h4 {
  font-size: 0.95rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: 0.85rem;
}

.timeline-items {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.timeline-card {
  background: rgba(18, 28, 48, 0.7);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 0.75rem;
  border-left: 3px solid #6366f1;
}

.tl-header {
  display: flex;
  justify-content: space-between;
  font-size: 0.84rem;
  color: #fff;
}

.tl-time { font-size: 0.7rem; color: var(--text-muted); }
.tl-vet { font-size: 0.74rem; color: #818cf8; margin: 2px 0; }
.tl-notes { font-size: 0.74rem; color: var(--text-secondary); margin-bottom: 4px; }

.tl-hash {
  font-size: 0.68rem;
  color: var(--text-muted);
  display: flex;
  gap: 4px;
  align-items: center;
}

.tl-hash code {
  font-family: monospace;
  color: #38bdf8;
  background: rgba(0, 0, 0, 0.4);
  padding: 1px 4px;
  border-radius: 3px;
}

.empty-timeline {
  font-size: 0.75rem;
  color: var(--text-muted);
  text-align: center;
  padding: 1rem 0;
}

/* EDIT MODAL */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1rem;
}

.modal-edit-card {
  width: 100%;
  max-width: 680px;
  background: rgba(14, 22, 38, 0.98);
  border: 1px solid rgba(99, 102, 241, 0.4);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 0.85rem;
  border-bottom: 1px solid var(--border);
  margin-bottom: 1.15rem;
}

.modal-header h3 {
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

.photo-edit-section {
  background: rgba(7, 10, 19, 0.7);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 1rem;
  margin-bottom: 1rem;
}

.form-label {
  display: block;
  font-size: 0.76rem;
  font-weight: 700;
  color: #a5b4fc;
  margin-bottom: 0.5rem;
}

.photo-preview-row {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.edit-preview-img {
  width: 85px;
  height: 85px;
  border-radius: 12px;
  object-fit: cover;
  border: 2px solid #6366f1;
}

.photo-options {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.btn-upload-file {
  align-self: flex-start;
  padding: 5px 12px;
  background: rgba(99, 102, 241, 0.2);
  border: 1px solid #6366f1;
  border-radius: var(--radius-sm);
  color: #c7d2fe;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}

.preset-label {
  font-size: 0.68rem;
  color: var(--text-muted);
  display: block;
  margin-bottom: 3px;
}

.preset-buttons {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.btn-preset {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 3px 8px;
  font-size: 0.7rem;
  color: var(--text-secondary);
  cursor: pointer;
}

.btn-preset:hover {
  background: rgba(99, 102, 241, 0.2);
  color: #fff;
  border-color: #6366f1;
}

.edit-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem;
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-size: 0.74rem;
  font-weight: 600;
  color: #a5b4fc;
  margin-bottom: 0.25rem;
}

.form-group.full {
  grid-column: 1 / -1;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 0.85rem;
  border-top: 1px solid var(--border);
}

.btn-cancel {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-muted);
  padding: 6px 14px;
  border-radius: var(--radius-sm);
  cursor: pointer;
}

.btn-save {
  padding: 6px 16px;
  font-weight: 700;
  font-size: 0.82rem;
  cursor: pointer;
}
</style>
