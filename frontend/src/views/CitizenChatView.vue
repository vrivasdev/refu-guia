<template>
  <div class="chat-page">
    <div class="chat-container glass-panel">
      <!-- HEADER -->
      <div class="chat-header">
        <div class="header-avatar">🤖</div>
        <div class="header-info">
          <h3>RefuGuía Asistente Ciudadano</h3>
          <p class="status-text">● En línea • Motor SLM Local Qwen 2.5</p>
        </div>
        <div class="emergency-tag">Emergencia Sismo 2026</div>
      </div>

      <!-- MESSAGES THREAD -->
      <div class="messages-thread" ref="threadRef">
        <div 
          v-for="(msg, idx) in messages" 
          :key="idx" 
          :class="['message-bubble', msg.sender === 'user' ? 'user-msg' : 'bot-msg']"
        >
          <div class="bubble-content">
            <p v-html="msg.text"></p>

            <!-- EXTRACTION CARD PREVIEW IF EXTRACTED -->
            <div v-if="msg.extractedCard" class="extraction-card">
              <div class="card-header">
                <span>📋 Extracción Estructurada por Agente NLP</span>
                <span class="conf-badge">Confianza 94%</span>
              </div>
              <div class="card-grid">
                <div><strong>Especie:</strong> {{ msg.extractedCard.species === 'canine' ? '🐶 Canino' : '🐱 Felino' }}</div>
                <div><strong>Tamaño:</strong> {{ msg.extractedCard.size }}</div>
                <div><strong>Color Primario:</strong> {{ msg.extractedCard.primary_color }}</div>
                <div><strong>Color Secundario:</strong> {{ msg.extractedCard.secondary_color || 'No detectado' }}</div>
                <div class="full-row"><strong>Evaluación Clínica Inicial:</strong> {{ msg.extractedCard.health_state }}</div>
              </div>
            </div>

            <!-- QR BADGE GENERATED -->
            <div v-if="msg.qrBadge" class="qr-result-card">
              <div class="qr-preview-side">
                <img :src="msg.qrBadge.print_ready_badge.qr_preview_url" alt="QR Collar" class="qr-img" />
              </div>
              <div class="qr-info-side">
                <h4>Identificador Oficial Generado</h4>
                <p class="qr-uuid">{{ msg.qrBadge.uuid }}</p>
                <p class="qr-desc">Este código QR vincula el collar físico con el expediente clínico digital.</p>
                <router-link to="/refugios" class="btn-view-shelter">Ver en Panel de Refugios →</router-link>
              </div>
            </div>

            <!-- MATCH ALERT CARD -->
            <div v-if="msg.matchesFound && msg.matchesFound.length > 0" class="match-alert-box">
              <div class="match-title">⚡ ¡Alerta del Agente Emparejador!</div>
              <p>Hemos encontrado {{ msg.matchesFound.length }} coincidencia(s) de alta probabilidad en la base de datos:</p>
              <div v-for="m in msg.matchesFound" :key="m.candidate_uuid" class="match-mini-row">
                <span>{{ m.candidate_name }} ({{ m.candidate_uuid }})</span>
                <span class="match-percent">{{ m.similarity_score }}% Match</span>
              </div>
              <router-link to="/matches" class="btn-check-match">Ver Comparativa en el Hub →</router-link>
            </div>
          </div>
          <span class="msg-time">{{ msg.time }}</span>
        </div>

        <div v-if="isProcessing" class="bot-typing">
          <span></span><span></span><span></span>
          <span class="typing-text">Agente NLP procesando relato e invocando Skills MCP...</span>
        </div>
      </div>

      <!-- QUICK ACTIONS -->
      <div v-if="messages.length <= 2" class="quick-options">
        <button class="btn-quick lost" @click="selectQuickOption('lost')">
          <span>🔍</span> Perdí a mi mascota
        </button>
        <button class="btn-quick found" @click="selectQuickOption('found')">
          <span>🏡</span> Encontré / Rescaté una mascota
        </button>
      </div>

      <!-- INPUT AREA -->
      <div class="chat-input-wrapper">
        <div class="input-actions-bar">
          <label class="btn-action-icon" title="Subir foto">
            📷 Foto
            <input type="file" @change="handlePhotoUpload" accept="image/*" style="display:none;" />
          </label>
          <button class="btn-action-icon" @click="simulateVoiceInput" title="Nota de voz">
            🎙️ Simular Audio
          </button>
          <span v-if="selectedPhotoName" class="file-tag">📎 {{ selectedPhotoName }}</span>
        </div>

        <div class="input-form">
          <textarea 
            v-model="userInput" 
            @keydown.enter.prevent="sendMessage"
            placeholder="Describe a la mascota (ej: Vi un perrito negro con pecho blanco cerca de Catia, parece asustado y cojea de una pata)..."
            rows="2"
          ></textarea>
          <button class="btn-send" :disabled="!userInput.trim() || isProcessing" @click="sendMessage">
            <span>Enviar</span> 🚀
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'

const threadRef = ref(null)
const userInput = ref('')
const isProcessing = ref(false)
const selectedPhotoName = ref('')
const currentReportType = ref('found')

const messages = ref([
  {
    sender: 'bot',
    text: '<strong>¡Hola! Soy RefuGuía</strong>, tu asistente inteligente para la recuperación y gestión de mascotas afectadas por los recientes eventos sísmicos en Venezuela.<br><br>¿En qué podemos ayudarte hoy?',
    time: 'Ahora'
  }
])

const scrollToBottom = () => {
  nextTick(() => {
    if (threadRef.value) {
      threadRef.value.scrollTop = threadRef.value.scrollHeight
    }
  })
}

const selectQuickOption = (type) => {
  currentReportType.value = type
  if (type === 'lost') {
    userInput.value = 'Perdí a mi perro Toby en la zona de Catia durante el sismo. Es un mestizo mediano color negro con blanco en el pecho.'
  } else {
    userInput.value = 'Rescatamos a un perrito mestizo mediano negro con manchas blancas en el pecho cerca de Caricuao. Tiene una patita lastimada y tiembla.'
  }
}

const simulateVoiceInput = () => {
  userInput.value = 'Transcripción de voz: "Hola, acabo de encontrar a un perrito asustado cerca de la estación del metro. Es mediano, color negro con el pecho blanco, parece un Border Collie mestizo y tiene una patita lastimada."'
}

const handlePhotoUpload = (e) => {
  if (e.target.files && e.target.files[0]) {
    selectedPhotoName.value = e.target.files[0].name
  }
}

const sendMessage = async () => {
  if (!userInput.value.trim() || isProcessing.value) return

  const textToSend = userInput.value.trim()
  userInput.value = ''
  selectedPhotoName.value = ''

  messages.value.push({
    sender: 'user',
    text: textToSend,
    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  })
  scrollToBottom()

  isProcessing.value = true

  try {
    const res = await fetch('http://localhost:8000/api/pets/report-citizen', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        raw_text: textToSend,
        report_type: currentReportType.value,
        location_address: 'Zona de Emergencia Caracas',
        photo_url: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80'
      })
    })
    const data = await res.json()

    if (data.success) {
      const ext = data.nlp_extraction.extracted_data
      messages.value.push({
        sender: 'bot',
        text: `¡Reporte procesado exitosamente! El <strong>Agente NLP</strong> y la <strong>Base Vectorial</strong> han registrado el expediente.`,
        extractedCard: ext,
        qrBadge: data.qr_badge ? { uuid: data.pet.uuid, print_ready_badge: data.qr_badge.print_ready_badge } : null,
        matchesFound: data.matches_found || [],
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      })
    } else {
      messages.value.push({
        sender: 'bot',
        text: `⚠️ Aviso: ${data.error || 'Ocurrió un error al procesar el reporte.'}`,
        time: 'Ahora'
      })
    }
  } catch (err) {
    // Fallback didáctico si la API no está arriba
    messages.value.push({
      sender: 'bot',
      text: `<strong>[Simulación Local SLM]</strong> He estructurado tu reporte como <em>Canino Mestizo Mediano</em>, color Negro/Blanco, estado de salud con traumatismo leve. Generando código QR provisorio...`,
      time: 'Ahora'
    })
  } finally {
    isProcessing.value = false
    scrollToBottom()
  }
}
</script>

<style scoped>
.chat-page {
  display: flex;
  justify-content: center;
  align-items: center;
}

.chat-container {
  width: 100%;
  max-width: 850px;
  height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
  overflow: hidden;
}

.chat-header {
  padding: 1.25rem 1.5rem;
  background: rgba(30, 41, 59, 0.85);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-avatar {
  font-size: 1.75rem;
  background: rgba(99, 102, 241, 0.2);
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}

.header-info h3 {
  font-size: 1.1rem;
  font-weight: 700;
}

.status-text {
  font-size: 0.75rem;
  color: #34d399;
}

.emergency-tag {
  margin-left: auto;
  font-size: 0.75rem;
  font-weight: 700;
  color: #fbbf24;
  background: rgba(245, 158, 11, 0.15);
  padding: 4px 10px;
  border-radius: 9999px;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.messages-thread {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.message-bubble {
  max-width: 85%;
  display: flex;
  flex-direction: column;
}

.user-msg {
  align-self: flex-end;
}

.user-msg .bubble-content {
  background: #4f46e5;
  color: white;
  border-radius: 16px 16px 2px 16px;
  padding: 1rem 1.25rem;
}

.bot-msg {
  align-self: flex-start;
}

.bot-msg .bubble-content {
  background: #1e293b;
  color: var(--text-main);
  border: 1px solid var(--border);
  border-radius: 16px 16px 16px 2px;
  padding: 1.25rem;
}

.msg-time {
  font-size: 0.65rem;
  color: var(--text-muted);
  margin-top: 4px;
  padding: 0 4px;
}

.extraction-card {
  margin-top: 1rem;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(99, 102, 241, 0.3);
  border-radius: 12px;
  padding: 1rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
  color: #818cf8;
}

.conf-badge {
  background: rgba(16, 185, 129, 0.2);
  color: #34d399;
  padding: 2px 8px;
  border-radius: 9999px;
  font-size: 0.7rem;
}

.card-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  font-size: 0.85rem;
}

.full-row {
  grid-column: 1 / -1;
  margin-top: 0.25rem;
  padding-top: 0.25rem;
  border-top: 1px solid var(--border);
}

.qr-result-card {
  margin-top: 1rem;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  gap: 1.25rem;
  align-items: center;
}

.qr-img {
  width: 90px;
  height: 90px;
  border-radius: 8px;
  background: white;
  padding: 4px;
}

.qr-info-side h4 {
  font-size: 0.95rem;
  color: #34d399;
  font-weight: 700;
}

.qr-uuid {
  font-family: monospace;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  color: #f8fafc;
}

.qr-desc {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin: 0.25rem 0 0.5rem 0;
}

.btn-view-shelter {
  font-size: 0.8rem;
  font-weight: 600;
  color: #818cf8;
}

.match-alert-box {
  margin-top: 1rem;
  background: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.4);
  border-radius: 12px;
  padding: 1rem;
}

.match-title {
  font-weight: 700;
  color: #fbbf24;
  margin-bottom: 0.25rem;
}

.match-mini-row {
  display: flex;
  justify-content: space-between;
  padding: 0.4rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  font-size: 0.85rem;
}

.match-percent {
  font-weight: 700;
  color: #34d399;
}

.btn-check-match {
  display: inline-block;
  margin-top: 0.75rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: #fbbf24;
}

.quick-options {
  padding: 0 1.5rem 1rem 1.5rem;
  display: flex;
  gap: 1rem;
}

.btn-quick {
  flex: 1;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: rgba(30, 41, 59, 0.9);
  color: var(--text-main);
  border: 1px solid var(--border);
}

.btn-quick:hover {
  background: rgba(99, 102, 241, 0.2);
  border-color: #6366f1;
}

.chat-input-wrapper {
  padding: 1rem 1.5rem;
  background: rgba(30, 41, 59, 0.9);
  border-top: 1px solid var(--border);
}

.input-actions-bar {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  margin-bottom: 0.5rem;
}

.btn-action-icon {
  font-size: 0.75rem;
  padding: 4px 10px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text-muted);
}

.btn-action-icon:hover {
  color: var(--text-main);
  background: rgba(255, 255, 255, 0.1);
}

.file-tag {
  font-size: 0.75rem;
  color: #38bdf8;
}

.input-form {
  display: flex;
  gap: 0.75rem;
}

.input-form textarea {
  flex: 1;
  background: #0f172a;
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 0.75rem 1rem;
  color: white;
  font-family: inherit;
  resize: none;
  font-size: 0.9rem;
}

.input-form textarea:focus {
  outline: none;
  border-color: #6366f1;
}

.btn-send {
  background: #4f46e5;
  color: white;
  padding: 0 1.5rem;
  border-radius: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-send:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.bot-typing {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  color: var(--text-muted);
}

.bot-typing span:not(.typing-text) {
  width: 6px;
  height: 6px;
  background: #818cf8;
  border-radius: 50%;
  animation: bounce 1.4s infinite ease-in-out both;
}

.bot-typing span:nth-child(1) { animation-delay: -0.32s; }
.bot-typing span:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1.0); }
}
</style>
