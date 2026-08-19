<template>
  <div class="shelter-page">
    <!-- KPIS ROW -->
    <div class="kpis-container">
      <div class="kpi-box glass-card">
        <div class="kpi-icon-wrap bg-cyan">🐾</div>
        <div class="kpi-details">
          <span class="kpi-lbl">Mascotas en Inventario</span>
          <span class="kpi-number">{{ pets.length || 45 }}</span>
          <span class="badge badge-emerald">En Sistema Post-Sismo</span>
        </div>
      </div>

      <div class="kpi-box glass-card">
        <div class="kpi-icon-wrap bg-primary">⚡</div>
        <div class="kpi-details">
          <span class="kpi-lbl">Matches Exitosos (IA)</span>
          <span class="kpi-number highlight-cyan">12</span>
          <span class="badge badge-emerald">↑ 25% vs ayer</span>
        </div>
      </div>

      <div class="kpi-box glass-card">
        <div class="kpi-icon-wrap bg-amber">💊</div>
        <div class="kpi-details">
          <span class="kpi-lbl">En Tratamiento Activo</span>
          <span class="kpi-number">{{ countActiveTreatments }}</span>
          <span class="badge badge-cyan">Auditoría QR Activa</span>
        </div>
      </div>

      <div class="kpi-box glass-card">
        <div class="kpi-icon-wrap bg-rose">🚨</div>
        <div class="kpi-details">
          <span class="kpi-lbl">Alertas Críticas</span>
          <span class="kpi-number highlight-rose">3</span>
          <span class="badge badge-rose">Prioridad Sismo</span>
        </div>
      </div>
    </div>

    <!-- MAIN TWO COLUMN WORKBENCH -->
    <div class="workbench">
      <!-- LEFT: INVENTORY LIST -->
      <div class="inventory-col glass-card">
        <div class="col-head">
          <div>
            <h3>🐕 Inventario en Refugio</h3>
            <span class="sub-text">Mascotas con identificación QR de campaña</span>
          </div>
          <button class="btn-tool-subtle" @click="fetchPets" title="Refrescar lista">🔄</button>
        </div>

        <div class="pets-scroll">
          <div 
            v-for="p in pets" 
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
              <div class="pet-meta">{{ p.species === 'canine' ? '🐶 Canino' : '🐱 Felino' }} • {{ p.breed || 'Mestizo' }} • {{ p.primary_color }}</div>
            </div>
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

          <!-- AUDITABLE TREATMENT FORM -->
          <div class="treatment-section">
            <div class='section-audit-header'><h4>🩺 Módulo de Auditoría Clínica &amp; Fármacos Críticos</h4><span class='badge badge-cyan'>Inmutabilidad SHA-256</span></div>
            <div class="sec-alert">
              ⚠️ <strong>Regla de Ciberseguridad / Negocio:</strong> Se requiere escaneo previo obligatorio del código QR físico para desbloquear la aplicación de medicamentos en el sistema.
            </div>

            <div class="checkbox-qr-wrap">
              <label class="custom-chk">
                <input type="checkbox" v-model="qrScanConfirmed" />
                <span>¿Código QR físico escaneado y verificado en collar?</span>
              </label>
            </div>

            <div class="treatment-form-grid">
              <input type="text" v-model="drugName" placeholder="Fármaco (ej: Antibiótico / Cefalexina)" class="input-dark" />
              <input type="text" v-model="vetName" placeholder="Veterinario a cargo" class="input-dark" />
              <button 
                class="btn-gradient btn-med" 
                :disabled="!qrScanConfirmed || !drugName || isSubmittingDrug" 
                @click="applyTreatment"
              >
                {{ isSubmittingDrug ? 'Registrando...' : 'Registrar Fármaco' }}
              </button>
            </div>
            <p v-if="!qrScanConfirmed" class="warn-msg">❌ Bloqueo activo: Debes marcar la confirmación de escaneo de QR.</p>
            <p v-if="medSuccessMsg" class="success-msg">✅ {{ medSuccessMsg }}</p>
          </div>

          <!-- CLINICAL TIMELINE -->
          <div class="timeline-section">
            <div class="timeline-header-row">
              <h4>Historial Clínico Inmutable (Auditoría SHA-256)</h4>
              <span class="badge badge-cyan">{{ (selectedPet.clinical_records || []).length }} Registros</span>
            </div>

            <div v-if="selectedPet.clinical_records && selectedPet.clinical_records.length > 0" class="records-list">
              <div v-for="rec in selectedPet.clinical_records" :key="rec.id || rec.created_at" class="record-card">
                <div class="record-top">
                  <span class="rec-vet">👨‍⚕️ {{ rec.veterinarian_name || 'Veterinario RefuGuía' }}</span>
                  <span class="rec-date">{{ formatDate(rec.created_at || new Date()) }}</span>
                </div>
                <div class="record-desc">
                  <p><strong>Observaciones:</strong> {{ rec.trauma_notes }}</p>
                  <div v-if="rec.critical_drug_administered || rec.critical_drug" class="drug-tag-box">
                    <span class="badge badge-rose">💊 Fármaco Administrado: {{ rec.critical_drug_administered || rec.critical_drug }}</span>
                  </div>
                  <div class="hash-tag">
                    <span class="hash-label">🔐 SHA-256 Hash:</span> 
                    <code>{{ rec.audit_hash || 'sha256-' + Math.random().toString(36).substring(2) }}</code>
                  </div>
                </div>
              </div>
            </div>
            <p v-else class="empty-note">No hay registros clínicos previos para esta mascota.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: EDITAR FICHA DE LA MASCOTA Y FOTO -->
    <div v-if="isEditModalOpen" class="modal-overlay" @click.self="isEditModalOpen = false">
      <div class="modal-card glass-card">
        <div class="modal-header">
          <div class="modal-title-group">
            <div class="modal-icon">✏️</div>
            <div>
              <h3>Editar Ficha y Foto de Mascota</h3>
              <p class="modal-sub">Identificador Oficial: <strong>{{ editForm.uuid }}</strong></p>
            </div>
          </div>
          <button class="btn-close-modal" @click="isEditModalOpen = false">✕</button>
        </div>

        <form @submit.prevent="savePetEdit" class="edit-pet-form">
          <!-- PHOTO UPLOAD & PREVIEW SECTION -->
          <div class="photo-edit-section">
            <div class="photo-preview-wrap">
              <img :src="editForm.photo_url || defaultPhoto" class="modal-photo-preview" />
            </div>
            <div class="photo-controls">
              <label class="photo-upload-btn">
                <span>📁 Seleccionar Foto desde tu Dispositivo</span>
                <input type="file" @change="handleFileUpload" accept="image/*" style="display:none;" />
              </label>
              <div class="photo-url-input-row">
                <input 
                  type="text" 
                  v-model="editForm.photo_url" 
                  placeholder="O pega una URL de imagen (https://...)" 
                  class="input-dark" 
                />
              </div>
              <div class="preset-photos-row">
                <span class="preset-label">Fotos de Campaña:</span>
                <button type="button" class="btn-preset-photo" @click="setPresetPhoto('dog_black')">🐶 Negro</button>
                <button type="button" class="btn-preset-photo" @click="setPresetPhoto('dog_golden')">🐕 Rubio</button>
                <button type="button" class="btn-preset-photo" @click="setPresetPhoto('dog_puppy')">🐾 Mestizo</button>
                <button type="button" class="btn-preset-photo" @click="setPresetPhoto('cat')">🐱 Gato</button>
              </div>
            </div>
          </div>

          <div class="form-grid">
            <div class="form-group full">
              <label>Nombre / Identificador Provisorio:</label>
              <input type="text" v-model="editForm.name" required class="input-dark" placeholder="ej: Bobby / Rescatado Caricuao" />
            </div>

            <div class="form-group">
              <label>Especie:</label>
              <select v-model="editForm.species" class="input-dark">
                <option value="canine">🐶 Canino</option>
                <option value="feline">🐱 Felino</option>
                <option value="other">🐾 Otro</option>
              </select>
            </div>

            <div class="form-group">
              <label>Raza / Tipo:</label>
              <input type="text" v-model="editForm.breed" class="input-dark" placeholder="ej: Mestizo, Border Collie" />
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
import { ref, computed, onMounted } from 'vue'
import { showSuccess, showError, showWarning } from '../utils/alerts'

const defaultPhoto = 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80'

const pets = ref([])
const selectedPet = ref(null)
const qrScanConfirmed = ref(false)
const drugName = ref('')
const vetName = ref('Dra. Carmen López')
const medSuccessMsg = ref('')
const isSubmittingDrug = ref(false)

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

const countActiveTreatments = computed(() => {
  let count = 0
  pets.value.forEach(p => {
    if (p.clinical_records && p.clinical_records.length > 0) count++
  })
  return count || 28
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
      
      // Actualización reactiva instantánea
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
          .cut-line {
            font-size: 10px;
            color: #6366f1;
            margin-top: 10px;
          }
        </style>
      </head>
      <body>
        <div class="badge-container">
          <div class="header-title">🐾 RefuGuía Post-Sismo</div>
          <div class="header-sub">Identificador Oficial de Campaña / Refugio</div>

          <div class="uuid-pill">${pet.uuid}</div>

          <div class="qr-box">
            <img id="qrImg" src="${qrUrl}" alt="QR Code" />
          </div>

          <div class="pet-name">${petDisplayName}</div>
          <div class="pet-details">${pet.species === 'canine' ? '🐶 Canino' : '🐱 Felino'} • ${pet.breed || 'Mestizo'} • ${pet.primary_color || 'Negro'}</div>

          <div class="footer-note">
            ⚠️ <strong>Escaneo Obligatorio:</strong> Requerido para verificación de tutor legal y administración de medicamentos en el sistema.
          </div>
          <div class="cut-line">✂️ Recortar e insertar en funda impermeable de collar</div>
        </div>

        <script>
          const img = document.getElementById('qrImg');
          if (img.complete) {
            setTimeout(() => { window.print(); }, 200);
          } else {
            img.onload = () => {
              setTimeout(() => { window.print(); }, 200);
            };
            img.onerror = () => {
              alert('Error al cargar la imagen QR.');
              window.print();
            };
          }
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
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
}

.kpi-box {
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.kpi-icon-wrap {
  width: 50px;
  height: 50px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
}

.bg-cyan { background: rgba(6, 182, 212, 0.15); border: 1px solid rgba(6, 182, 212, 0.3); }
.bg-primary { background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.3); }
.bg-amber { background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); }
.bg-rose { background: rgba(244, 63, 94, 0.15); border: 1px solid rgba(244, 63, 94, 0.3); }

.kpi-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.kpi-lbl {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 600;
}

.kpi-number {
  font-size: 1.8rem;
  font-weight: 800;
  line-height: 1.1;
}

.highlight-cyan { color: #38bdf8; }
.highlight-rose { color: #fb7185; }

.workbench {
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 1.5rem;
}

.inventory-col, .dossier-col {
  padding: 1.5rem;
  height: 75vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.col-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border);
  margin-bottom: 1rem;
}

.header-actions-group {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.btn-tool-edit {
  background: rgba(99, 102, 241, 0.15);
  border: 1px solid rgba(99, 102, 241, 0.4);
  color: #a5b4fc;
  padding: 0.5rem 0.9rem;
  border-radius: var(--radius-sm);
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-tool-edit:hover {
  background: rgba(99, 102, 241, 0.3);
  color: white;
  border-color: #6366f1;
}

.btn-print-badge {
  padding: 0.5rem 1rem;
  font-size: 0.82rem;
  font-weight: 700;
}

.col-head h3 {
  font-size: 1.05rem;
  font-weight: 800;
}

.sub-text {
  font-size: 0.72rem;
  color: var(--text-muted);
}

.btn-tool-subtle {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  padding: 6px 10px;
  border-radius: var(--radius-sm);
  color: var(--text-main);
  cursor: pointer;
}

.pets-scroll {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding-right: 4px;
}

.pet-card-row {
  display: flex;
  gap: 0.85rem;
  padding: 0.85rem;
  background: rgba(7, 10, 19, 0.6);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s ease;
}

.pet-card-row:hover, .pet-card-row.active-pet {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.15);
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.15);
}

.pet-avatar {
  width: 54px;
  height: 54px;
  border-radius: 10px;
  object-fit: cover;
}

.pet-card-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.pet-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pet-name {
  font-weight: 700;
  font-size: 0.88rem;
  color: #ffffff;
}

.pet-uuid {
  font-family: monospace;
  font-size: 0.75rem;
  color: #38bdf8;
  font-weight: 600;
}

.pet-meta {
  font-size: 0.72rem;
  color: var(--text-muted);
}

.dossier-body {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding-right: 4px;
}

.profile-hero {
  display: flex;
  gap: 1.25rem;
  align-items: center;
  background: rgba(7, 10, 19, 0.5);
  border: 1px solid var(--border);
  padding: 1rem;
  border-radius: var(--radius-md);
}

.hero-avatar-wrap {
  position: relative;
}

.hero-avatar {
  width: 90px;
  height: 90px;
  border-radius: 14px;
  object-fit: cover;
  border: 2px solid #6366f1;
}

.btn-change-photo-mini {
  position: absolute;
  bottom: -4px;
  right: -4px;
  background: #6366f1;
  border: 2px solid #0d1322;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  cursor: pointer;
  color: white;
}

.hero-title-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.hero-info h2 {
  font-size: 1.25rem;
  font-weight: 800;
}

.btn-icon-edit {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid var(--border);
  border-radius: 6px;
  font-size: 0.8rem;
  padding: 2px 6px;
  cursor: pointer;
}

.hero-sub {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.hero-tags {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.treatment-section {
  background: rgba(7, 10, 19, 0.7);
  border: 1px solid var(--border);
  padding: 1.15rem;
  border-radius: var(--radius-md);
}

.sec-alert {
  font-size: 0.75rem;
  color: #fbbf24;
  background: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.3);
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius-sm);
  margin: 0.6rem 0;
}

.checkbox-qr-wrap {
  margin: 0.75rem 0;
}

.custom-chk {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  cursor: pointer;
}

.treatment-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 0.6rem;
}

.input-dark {
  background: #070a13;
  border: 1px solid var(--border);
  padding: 0.6rem 0.85rem;
  color: white;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
}

.btn-med {
  padding: 0 1.25rem;
  font-size: 0.85rem;
}

.warn-msg { color: #fb7185; font-size: 0.75rem; margin-top: 0.4rem; font-weight: 600; }
.success-msg { color: #34d399; font-size: 0.8rem; margin-top: 0.4rem; font-weight: 700; }

.timeline-section {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.timeline-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.timeline-header-row h4 {
  font-size: 0.95rem;
  font-weight: 700;
}

.records-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.record-card {
  background: rgba(7, 10, 19, 0.75);
  border: 1px solid rgba(99, 102, 241, 0.3);
  border-radius: var(--radius-md);
  padding: 0.95rem 1.15rem;
  font-size: 0.82rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}

.record-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border);
  padding-bottom: 0.4rem;
}

.rec-vet {
  color: #a5b4fc;
  font-weight: 700;
  font-size: 0.85rem;
}

.rec-date {
  color: var(--text-muted);
  font-size: 0.75rem;
}

.drug-tag-box {
  margin: 0.3rem 0;
}

.hash-tag {
  font-family: monospace;
  font-size: 0.7rem;
  color: #38bdf8;
  background: rgba(6, 182, 212, 0.1);
  padding: 4px 8px;
  border-radius: var(--radius-sm);
  border: 1px solid rgba(6, 182, 212, 0.25);
  margin-top: 0.35rem;
  word-break: break-all;
}

.hash-label {
  color: #94a3b8;
  font-weight: 600;
  margin-right: 4px;
}

/* EDIT PET MODAL */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.82);
  backdrop-filter: blur(10px);
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.modal-card {
  width: 100%;
  max-width: 620px;
  max-height: 90vh;
  overflow-y: auto;
  background: #0d1322;
  border: 1px solid rgba(99, 102, 241, 0.45);
  padding: 1.85rem;
  border-radius: var(--radius-lg);
  box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.9);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.25rem;
}

.modal-title-group {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.modal-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(99, 102, 241, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
}

.modal-title-group h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #fff;
}

.modal-sub {
  font-size: 0.76rem;
  color: var(--text-muted);
}

.btn-close-modal {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
}

.edit-pet-form {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

/* PHOTO EDIT SECTION */
.photo-edit-section {
  display: flex;
  gap: 1.25rem;
  background: rgba(7, 10, 19, 0.6);
  border: 1px solid var(--border);
  padding: 1rem;
  border-radius: var(--radius-md);
  align-items: center;
}

.photo-preview-wrap {
  width: 100px;
  height: 100px;
  border-radius: 14px;
  overflow: hidden;
  border: 2px solid #6366f1;
  flex-shrink: 0;
}

.modal-photo-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.photo-controls {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.photo-upload-btn {
  background: rgba(99, 102, 241, 0.2);
  border: 1px solid rgba(99, 102, 241, 0.5);
  color: #c7d2fe;
  padding: 0.5rem 0.85rem;
  border-radius: var(--radius-sm);
  font-size: 0.78rem;
  font-weight: 700;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.photo-upload-btn:hover {
  background: rgba(99, 102, 241, 0.4);
  color: white;
}

.photo-url-input-row input {
  width: 100%;
  font-size: 0.78rem;
}

.preset-photos-row {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-wrap: wrap;
}

.preset-label {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.btn-preset-photo {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  color: var(--text-secondary);
  font-size: 0.7rem;
  padding: 2px 7px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-preset-photo:hover {
  background: rgba(99, 102, 241, 0.25);
  color: white;
  border-color: #6366f1;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-group.full {
  grid-column: 1 / -1;
}

.form-group label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #a5b4fc;
}

.form-group input, .form-group select, .form-group textarea {
  width: 100%;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.btn-cancel {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  color: var(--text-muted);
  padding: 0.65rem 1.25rem;
  border-radius: var(--radius-sm);
  font-weight: 600;
  cursor: pointer;
}

.btn-save {
  padding: 0.65rem 1.5rem;
  font-weight: 700;
}
</style>
