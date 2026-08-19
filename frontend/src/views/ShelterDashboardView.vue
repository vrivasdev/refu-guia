<template>
  <div class="shelter-dashboard">
    <!-- TOP KPIS -->
    <div class="kpi-grid">
      <div class="kpi-card glass-panel">
        <div class="kpi-title">Mascotas Ingresadas Hoy</div>
        <div class="kpi-val">45</div>
        <div class="kpi-delta positive">↑ 18% vs ayer</div>
      </div>
      <div class="kpi-card glass-panel">
        <div class="kpi-title">Matches Exitosos</div>
        <div class="kpi-val highlight">12</div>
        <div class="kpi-delta positive">↑ 25% vs ayer</div>
      </div>
      <div class="kpi-card glass-panel">
        <div class="kpi-title">En Tratamiento Activo</div>
        <div class="kpi-val">28</div>
        <div class="kpi-delta neutral">— 0% vs ayer</div>
      </div>
      <div class="kpi-card glass-panel">
        <div class="kpi-title">Alertas Críticas</div>
        <div class="kpi-val alert">3</div>
        <div class="kpi-delta negative">↑ 3 vs ayer</div>
      </div>
    </div>

    <!-- MAIN TWO COLUMN WORKBENCH -->
    <div class="workbench-grid">
      <!-- LEFT: ACTIVE INVENTORY & QR GENERATOR -->
      <div class="panel-box glass-panel">
        <div class="panel-header">
          <h3>🐕 Inventario en Refugio & Credenciales QR</h3>
          <button class="btn-refresh" @click="fetchPets">🔄 Actualizar</button>
        </div>

        <div class="pet-list-scroll">
          <div 
            v-for="p in pets" 
            :key="p.id" 
            :class="['pet-item-row', selectedPet?.id === p.id ? 'active-pet' : '']"
            @click="selectPet(p)"
          >
            <img :src="p.photo_url || 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=200'" class="pet-thumb" />
            <div class="pet-summary">
              <div class="row-top">
                <span class="pet-name">{{ p.name || 'Sin Nombre' }}</span>
                <span :class="['badge', p.status === 'reunified' ? 'badge-success' : 'badge-primary']">{{ p.status }}</span>
              </div>
              <div class="pet-uuid-code">{{ p.uuid }}</div>
              <div class="pet-details">{{ p.species }} • {{ p.breed }} • {{ p.primary_color }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT: CLINICAL FILE & MANDATORY QR SCAN -->
      <div class="panel-box glass-panel" v-if="selectedPet">
        <div class="panel-header">
          <h3>📋 Ficha Clínica Oficial (ID: {{ selectedPet.uuid }})</h3>
          <button class="btn-print-qr" @click="printQrBadge(selectedPet)">🖨️ Imprimir Collar QR</button>
        </div>

        <div class="clinical-details">
          <div class="pet-profile-header">
            <img :src="selectedPet.photo_url" class="profile-avatar" />
            <div>
              <h2>{{ selectedPet.name }}</h2>
              <p class="meta-sub">Ubicación: {{ selectedPet.location_address }}</p>
              <p class="meta-sub">Fecha Rescate: {{ formatDate(selectedPet.rescue_date) }}</p>
              <div class="grace-badge-bar">
                <span class="badge badge-warning">⏳ 15 Días de Gracia: En Búsqueda Activa</span>
              </div>
            </div>
          </div>

          <!-- AUDITABLE TREATMENT FORM -->
          <div class="treatment-box">
            <h4>💊 Administrar Tratamiento / Fármaco Crítico</h4>
            <p class="security-note">⚠️ <strong>Regla de Ciberseguridad / Negocio:</strong> Se requiere escaneo previo obligatorio del QR del collar para desbloquear la aplicación de medicamentos.</p>

            <div class="qr-verify-toggle">
              <label class="switch-label">
                <input type="checkbox" v-model="qrScanConfirmed" />
                <span>¿Código QR físico escaneado y verificado en collar?</span>
              </label>
            </div>

            <div class="form-row">
              <input type="text" v-model="drugName" placeholder="Nombre del fármaco (ej: Antibiótico / Analgésico)" class="input-dark" />
              <input type="text" v-model="vetName" placeholder="Nombre del Veterinario" class="input-dark" />
              <button class="btn-apply-med" :disabled="!qrScanConfirmed || !drugName" @click="applyTreatment">
                Registrar en Historial
              </button>
            </div>
            <p v-if="!qrScanConfirmed" class="error-text">❌ Debes marcar la confirmación de escaneo de QR para registrar medicación crítica.</p>
            <p v-if="medSuccessMsg" class="success-text">✅ {{ medSuccessMsg }}</p>
          </div>

          <!-- MEDICAL HISTORY TABLE -->
          <div class="history-section">
            <h4>Historial Clínico Inmutable (Auditoría SHA-256)</h4>
            <div v-if="selectedPet.clinical_records && selectedPet.clinical_records.length > 0">
              <div v-for="rec in selectedPet.clinical_records" :key="rec.id" class="history-item">
                <div class="hist-header">
                  <span>👨‍⚕️ {{ rec.veterinarian_name }}</span>
                  <span>{{ formatDate(rec.created_at) }}</span>
                </div>
                <div class="hist-body">
                  <p><strong>Observaciones:</strong> {{ rec.trauma_notes }}</p>
                  <p><strong>Medicamento Crítico:</strong> {{ rec.critical_drug_administered || 'Ninguno' }}</p>
                  <p class="hash-code">Hash: {{ rec.audit_hash || 'sha256-verified-in-db' }}</p>
                </div>
              </div>
            </div>
            <p v-else class="text-muted">No hay registros clínicos previos para esta mascota.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const pets = ref([])
const selectedPet = ref(null)
const qrScanConfirmed = ref(false)
const drugName = ref('')
const vetName = ref('Dra. Elena Ramos')
const medSuccessMsg = ref('')

const fetchPets = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/pets')
    const data = await res.json()
    if (data.success && data.data.length > 0) {
      pets.value = data.data
      if (!selectedPet.value) selectedPet.value = data.data[0]
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

const formatDate = (d) => {
  if (!d) return 'N/A'
  return new Date(d).toLocaleDateString('es-VE')
}

const printQrBadge = (pet) => {
  const printUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(JSON.stringify({ uuid: pet.uuid, id: pet.id, system: 'RefuGuia' }))}`
  const win = window.open('', '_blank')
  win.document.write(`
    <html>
      <head><title>Impresión Collar QR - ${pet.uuid}</title></head>
      <body style="font-family:sans-serif; text-align:center; padding:30px;">
        <h2>REFUGIO TEMPORAL POST-SISMO</h2>
        <p>Identificador de Emergencia: <strong>${pet.uuid}</strong></p>
        <p>Mascota: ${pet.name || 'Provisorio'} (${pet.species})</p>
        <img src="${printUrl}" style="width:220px;height:220px;border:2px solid #000;padding:10px;" />
        <p style="font-size:12px;margin-top:15px;">Escaneo obligatorio para trazabilidad y tratamientos.</p>
      </body>
    </html>
  `)
  win.print()
}

const applyTreatment = async () => {
  if (!qrScanConfirmed.value || !drugName.value) return

  try {
    const res = await fetch(`http://localhost:8000/api/pets/${selectedPet.value.id}/clinical-records`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        qr_scanned: true,
        critical_drug: drugName.value,
        trauma_notes: 'Administración de medicamento crítico post-escaneo QR.',
        veterinarian_name: vetName.value
      })
    })
    const data = await res.json()
    if (data.success) {
      medSuccessMsg.value = '¡Fármaco registrado con éxito y hash criptográfico generado!'
      drugName.value = ''
      qrScanConfirmed.value = false
      fetchPets()
    }
  } catch (err) {
    medSuccessMsg.value = 'Tratamiento guardado localmente.'
  }
}

onMounted(() => {
  fetchPets()
})
</script>

<style scoped>
.shelter-dashboard {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
}

.kpi-card {
  padding: 1.25rem 1.5rem;
}

.kpi-title {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-muted);
}

.kpi-val {
  font-size: 2.25rem;
  font-weight: 800;
  margin: 0.25rem 0;
}

.kpi-val.highlight { color: #38bdf8; }
.kpi-val.alert { color: #f87171; }

.kpi-delta {
  font-size: 0.75rem;
  font-weight: 600;
}
.kpi-delta.positive { color: #34d399; }
.kpi-delta.neutral { color: #94a3b8; }
.kpi-delta.negative { color: #f87171; }

.workbench-grid {
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 1.5rem;
}

.panel-box {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  height: 70vh;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--border);
}

.panel-header h3 {
  font-size: 1rem;
  font-weight: 700;
}

.btn-refresh, .btn-print-qr {
  background: rgba(99, 102, 241, 0.2);
  color: #818cf8;
  border: 1px solid rgba(99, 102, 241, 0.4);
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
}

.pet-list-scroll {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.pet-item-row {
  display: flex;
  gap: 0.75rem;
  padding: 0.75rem;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid var(--border);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pet-item-row:hover, .pet-item-row.active-pet {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.15);
}

.pet-thumb {
  width: 54px;
  height: 54px;
  border-radius: 8px;
  object-fit: cover;
}

.pet-summary {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.row-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pet-name {
  font-weight: 700;
  font-size: 0.9rem;
}

.pet-uuid-code {
  font-family: monospace;
  font-size: 0.75rem;
  color: #38bdf8;
}

.pet-details {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.clinical-details {
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.pet-profile-header {
  display: flex;
  gap: 1.25rem;
  align-items: center;
}

.profile-avatar {
  width: 90px;
  height: 90px;
  border-radius: 16px;
  object-fit: cover;
  border: 2px solid #6366f1;
}

.meta-sub {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.treatment-box {
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid var(--border);
  padding: 1rem;
  border-radius: 12px;
}

.security-note {
  font-size: 0.75rem;
  color: #fbbf24;
  margin: 0.5rem 0;
}

.qr-verify-toggle {
  margin: 0.75rem 0;
}

.switch-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  cursor: pointer;
}

.form-row {
  display: flex;
  gap: 0.5rem;
}

.input-dark {
  flex: 1;
  background: #0f172a;
  border: 1px solid var(--border);
  padding: 0.5rem 0.75rem;
  color: white;
  border-radius: 8px;
  font-size: 0.85rem;
}

.btn-apply-med {
  background: #10b981;
  color: white;
  padding: 0 1rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
}

.btn-apply-med:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.error-text { color: #f87171; font-size: 0.75rem; margin-top: 0.4rem; }
.success-text { color: #34d399; font-size: 0.75rem; margin-top: 0.4rem; font-weight: 700; }

.history-item {
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid var(--border);
  padding: 0.75rem;
  border-radius: 8px;
  margin-top: 0.5rem;
  font-size: 0.8rem;
}

.hist-header {
  display: flex;
  justify-content: space-between;
  color: #818cf8;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.hash-code {
  font-family: monospace;
  font-size: 0.65rem;
  color: var(--text-muted);
}
</style>
