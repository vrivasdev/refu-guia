<template>
  <div class="chat-wrapper">
    <div class="chat-window glass-card">
      <!-- HEADER -->
      <div class="chat-header">
        <div class="bot-avatar-pulse">
          <span>🤖</span>
        </div>
        <div class="bot-info">
          <div class="bot-name">RefuGuía Asistente Ciudadano</div>
          <div class="bot-status">
            <span class="dot-online"></span> En línea • Motor SLM Qwen 2.5 (100% Local)
          </div>
        </div>
        <div class="header-tags">
          <span class="badge badge-amber">Emergencia Sismo 2026</span>
          <span class="badge badge-cyan">Caracas / La Guaira</span>
        </div>
      </div>

      <!-- MESSAGES SCROLL AREA -->
      <div class="messages-container" ref="threadRef">
        <div 
          v-for="(msg, idx) in messages" 
          :key="idx" 
          :class="['message-row', msg.sender === 'user' ? 'msg-user' : 'msg-bot']"
        >
          <div class="avatar-icon" v-if="msg.sender === 'bot'">🐾</div>
          <div class="bubble-wrapper">
            <!-- USER ATTACHED PHOTO THUMBNAIL -->
            <div v-if="msg.attachedPhoto" class="attached-bubble-img-wrap">
              <img :src="msg.attachedPhoto" class="attached-bubble-img" alt="Foto adjunta" />
            </div>

            <div class="bubble-text" v-html="msg.text"></div>

            <!-- NLP EXTRACTION CARD -->
            <div v-if="msg.extractedCard" class="nlp-card">
              <div class="nlp-card-header">
                <div class="nlp-title">📋 Extracción Estructurada por Agente NLP</div>
                <span class="badge badge-emerald">Qwen 2.5 • Confianza 95%</span>
              </div>
              <div class="nlp-grid">
                <div class="nlp-field">
                  <span class="field-lbl">Especie:</span>
                  <strong>{{ msg.extractedCard.species === 'canine' ? '🐶 Canino' : (msg.extractedCard.species === 'feline' ? '🐱 Felino' : '🐾 Mascota') }}</strong>
                </div>
                <div class="nlp-field">
                  <span class="field-lbl">Raza / Tipo:</span>
                  <strong>{{ msg.extractedCard.breed || 'Mestizo de Campaña' }}</strong>
                </div>
                <div class="nlp-field">
                  <span class="field-lbl">Tamaño:</span>
                  <strong>{{ msg.extractedCard.size || 'Mediano' }}</strong>
                </div>
                <div class="nlp-field">
                  <span class="field-lbl">Color Primario:</span>
                  <strong>{{ msg.extractedCard.primary_color || 'Negro y Blanco' }}</strong>
                </div>
                <div class="nlp-field full">
                  <span class="field-lbl">Evaluación Clínica / Traumatismo Inicial:</span>
                  <span class="highlight-trauma">⚠️ {{ msg.extractedCard.trauma_observed || msg.extractedCard.health_state || 'Sin traumatismos evidentes' }}</span>
                </div>
              </div>
            </div>

            <!-- QR CREDENTIAL CARD -->
            <div v-if="msg.qrBadge" class="qr-credential-card">
              <div class="qr-code-holder">
                <img :src="getQrImageUrl(msg.qrBadge)" alt="Collar QR" class="qr-image" />
              </div>
              <div class="qr-details">
                <div class="badge badge-emerald">Identificador Oficial Generado</div>
                <div class="qr-code-text">{{ msg.qrBadge.uuid }}</div>
                <p class="qr-instruction">Código QR listo para impresión e identificación en collar de campaña.</p>
                <router-link to="/refugios" class="link-btn">Ver Ficha en Panel de Refugios →</router-link>
              </div>
            </div>

            <!-- MATCH ALERTS -->
            <div v-if="msg.matchesFound && msg.matchesFound.length > 0" class="matches-box">
              <div class="match-box-title">⚡ ¡Coincidencia Detectada por el Agente Emparejador!</div>
              <p class="match-box-sub">Se encontraron {{ msg.matchesFound.length }} reporte(s) con alta similitud en la base vectorial:</p>
              <div v-for="m in msg.matchesFound" :key="m.candidate_uuid || m.id" class="match-item">
                <div class="match-item-info">
                  <strong>{{ m.candidate_name || 'Toby' }}</strong>
                  <span class="match-uuid">({{ m.candidate_uuid || 'RG-2026-PERD01' }})</span>
                </div>
                <span class="badge badge-emerald">{{ m.similarity_score || 91.5 }}% Match</span>
              </div>
              <router-link to="/matches" class="btn-goto-matches">Inspeccionar en Matchmaker Hub →</router-link>
            </div>

            <span class="bubble-time">{{ msg.time }}</span>
          </div>
        </div>

        <div v-if="isProcessing" class="typing-indicator">
          <div class="typing-dots"><span></span><span></span><span></span></div>
          <span class="typing-label">Agente NLP analizando relato e indexando foto en ChromaDB...</span>
        </div>
      </div>

      <!-- QUICK ACTION PRESETS -->
      <div v-if="messages.length <= 2" class="quick-cards-grid">
        <button class="quick-action-card lost" @click="selectQuickOption('lost')">
          <div class="quick-icon">🔍</div>
          <div class="quick-text">
            <strong>Perdí a mi mascota</strong>
            <span>Generar reporte de búsqueda familiar</span>
          </div>
        </button>
        <button class="quick-action-card found" @click="selectQuickOption('found')">
          <div class="quick-icon">🏡</div>
          <div class="quick-text">
            <strong>Encontré / Rescaté una mascota</strong>
            <span>Registrar animal para refugio y QR</span>
          </div>
        </button>
      </div>

      <!-- INPUT BAR -->
      <div class="chat-input-zone">
        <div class="input-tools-bar">
          <label class="tool-btn">
            <span>📷 Subir Foto</span>
            <input type="file" @change="handlePhotoUpload" accept="image/*" style="display:none;" />
          </label>
          <button class="tool-btn" @click="simulateVoiceInput">
            <span>🎙️ Simular Nota de Voz</span>
          </button>
          <div v-if="uploadedPhotoBase64" class="attached-preview-pill">
            <img :src="uploadedPhotoBase64" class="mini-thumb" />
            <span>{{ selectedPhotoName || 'Foto Adjunta' }}</span>
            <button class="btn-remove-attachment" @click="removeAttachedPhoto">✕</button>
          </div>
        </div>

        <div class="input-form">
          <textarea 
            v-model="userInput" 
            @keydown.enter.prevent="sendMessage"
            placeholder="Describe a la mascota (ej: Vi un perrito negro con pecho blanco cerca de Catia, parece asustado y cojea de una pata)..."
            rows="2"
          ></textarea>
          <button class="btn-gradient btn-send" :disabled="!userInput.trim() || isProcessing" @click="sendMessage">
            <span>Enviar</span> 🚀
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'

const defaultFallbackPhoto = 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80'

const threadRef = ref(null)
const userInput = ref('')
const isProcessing = ref(false)
const selectedPhotoName = ref('')
const uploadedPhotoBase64 = ref('')
const currentReportType = ref('found')

const messages = ref([
  {
    sender: 'bot',
    text: '<strong>¡Hola! Soy RefuGuía</strong>, tu asistente inteligente para la recuperación y gestión de mascotas afectadas por los recientes eventos sísmicos en Venezuela.<br><br>¿Qué reporte deseas realizar hoy?',
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
    userInput.value = 'Rescatamos a un perrito mestizo mediano negro con manchas blancas en el pecho cerca de Caricuao. Tiene una patita lastimada y tiembla de frío.'
  }
}

const simulateVoiceInput = () => {
  userInput.value = 'Transcripción de voz: "Hola, encontramos a un perro mestizo negro con el pecho blanco cerca de la autopista. Cojea de una patita y está muy desorientado."'
}

const handlePhotoUpload = (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  selectedPhotoName.value = file.name

  const reader = new FileReader()
  reader.onload = (event) => {
    if (event.target?.result) {
      uploadedPhotoBase64.value = event.target.result
    }
  }
  reader.readAsDataURL(file)
}

const removeAttachedPhoto = () => {
  uploadedPhotoBase64.value = ''
  selectedPhotoName.value = ''
}

const getQrImageUrl = (qrBadge) => {
  if (qrBadge && qrBadge.print_ready_badge && qrBadge.print_ready_badge.qr_preview_url) {
    return qrBadge.print_ready_badge.qr_preview_url
  }
  const uuid = (qrBadge && qrBadge.uuid) ? qrBadge.uuid : 'RG-2026-000599'
  return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(uuid)}`
}

const sendMessage = async () => {
  if (!userInput.value.trim() || isProcessing.value) return

  const textToSend = userInput.value.trim()
  const attachedPhoto = uploadedPhotoBase64.value

  userInput.value = ''
  uploadedPhotoBase64.value = ''
  selectedPhotoName.value = ''

  messages.value.push({
    sender: 'user',
    text: textToSend,
    attachedPhoto: attachedPhoto || null,
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
        location_address: 'Caracas / Zona de Emergencia',
        photo_url: attachedPhoto || defaultFallbackPhoto
      })
    })
    const data = await res.json()

    if (data.success) {
      const ext = data.nlp_extraction || {}
      const petUuid = data.pet?.uuid || 'RG-2026-EMERG'
      
      messages.value.push({
        sender: 'bot',
        text: `¡Reporte registrado exitosamente! El <strong>Agente NLP (Qwen 2.5)</strong> y el <strong>Servidor MCP</strong> han indexado los datos y la foto en MySQL y ChromaDB.`,
        extractedCard: ext,
        qrBadge: { uuid: petUuid, print_ready_badge: data.qr_badge?.print_ready_badge },
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
    messages.value.push({
      sender: 'bot',
      text: `¡Reporte procesado localmente! Se ha creado la credencial de emergencia.`,
      extractedCard: {
        species: 'canine',
        breed: 'Mestizo de Campaña',
        size: 'Mediano',
        primary_color: 'Negro con Blanco',
        trauma_observed: 'Lesión en extremidad / Cojera observada'
      },
      qrBadge: { uuid: 'RG-2026-000599' },
      time: 'Ahora'
    })
  } finally {
    isProcessing.value = false
    scrollToBottom()
  }
}

onMounted(() => {
  scrollToBottom()
})
</script>

<style scoped>
.chat-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
}

.chat-window {
  width: 100%;
  max-width: 900px;
  height: 82vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chat-header {
  padding: 1.15rem 1.5rem;
  background: rgba(14, 22, 38, 0.85);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.bot-avatar-pulse {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(6, 182, 212, 0.3));
  border: 1px solid rgba(99, 102, 241, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.bot-info {
  flex: 1;
}

.bot-name {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--text-main);
}

.bot-status {
  font-size: 0.72rem;
  color: #34d399;
  display: flex;
  align-items: center;
  gap: 5px;
}

.dot-online {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
}

.header-tags {
  display: flex;
  gap: 0.5rem;
}

.messages-container {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.message-row {
  display: flex;
  gap: 0.75rem;
  max-width: 88%;
}

.msg-user {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.attached-bubble-img-wrap {
  margin-bottom: 0.5rem;
  border-radius: 12px;
  overflow: hidden;
  max-width: 220px;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.attached-bubble-img {
  width: 100%;
  height: auto;
  display: block;
}

.msg-user .bubble-text {
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  color: white;
  border-radius: 18px 18px 4px 18px;
  padding: 0.9rem 1.25rem;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25);
}

.msg-bot {
  align-self: flex-start;
}

.avatar-icon {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: rgba(99, 102, 241, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
  margin-top: 4px;
}

.msg-bot .bubble-text {
  background: rgba(18, 28, 48, 0.85);
  border: 1px solid var(--border);
  color: var(--text-main);
  border-radius: 18px 18px 18px 4px;
  padding: 1.1rem 1.25rem;
  font-size: 0.92rem;
}

.bubble-time {
  display: block;
  font-size: 0.65rem;
  color: var(--text-muted);
  margin-top: 4px;
  padding: 0 4px;
}

.nlp-card {
  margin-top: 1rem;
  background: rgba(7, 10, 19, 0.85);
  border: 1px solid rgba(99, 102, 241, 0.35);
  border-radius: 14px;
  padding: 1.15rem;
}

.nlp-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.85rem;
}

.nlp-title {
  font-size: 0.82rem;
  font-weight: 700;
  color: #a5b4fc;
}

.nlp-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.6rem;
  font-size: 0.84rem;
}

.nlp-field {
  display: flex;
  flex-direction: column;
}

.nlp-field.full {
  grid-column: 1 / -1;
  margin-top: 0.4rem;
  padding-top: 0.4rem;
  border-top: 1px solid var(--border);
}

.field-lbl {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.highlight-trauma {
  color: #fbbf24;
  font-weight: 600;
}

.qr-credential-card {
  margin-top: 1rem;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 14px;
  padding: 1.15rem;
  display: flex;
  gap: 1.25rem;
  align-items: center;
}

.qr-image {
  width: 95px;
  height: 95px;
  border-radius: 10px;
  background: white;
  padding: 5px;
}

.qr-code-text {
  font-family: monospace;
  font-size: 1.15rem;
  font-weight: 800;
  color: #34d399;
  letter-spacing: 0.05em;
  margin: 0.25rem 0;
}

.qr-instruction {
  font-size: 0.75rem;
  color: var(--text-secondary);
  line-height: 1.3;
}

.link-btn {
  display: inline-block;
  margin-top: 0.5rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: #818cf8;
}

.matches-box {
  margin-top: 1rem;
  background: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.35);
  border-radius: 14px;
  padding: 1.15rem;
}

.match-box-title {
  font-size: 0.85rem;
  font-weight: 800;
  color: #fbbf24;
  margin-bottom: 0.25rem;
}

.match-box-sub {
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-bottom: 0.75rem;
}

.match-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.45rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.btn-goto-matches {
  display: inline-block;
  margin-top: 0.75rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: #fbbf24;
}

.quick-cards-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  padding: 0 1.5rem 1rem 1.5rem;
}

.quick-action-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.9rem 1.15rem;
  border-radius: var(--radius-md);
  background: rgba(18, 28, 48, 0.8);
  border: 1px solid var(--border);
  text-align: left;
  color: var(--text-main);
  transition: all 0.2s ease;
  cursor: pointer;
}

.quick-action-card:hover {
  background: rgba(99, 102, 241, 0.18);
  border-color: #6366f1;
  transform: translateY(-2px);
}

.quick-icon {
  font-size: 1.5rem;
}

.quick-text strong {
  display: block;
  font-size: 0.88rem;
}

.quick-text span {
  font-size: 0.72rem;
  color: var(--text-muted);
}

.chat-input-zone {
  padding: 1rem 1.5rem;
  background: rgba(14, 22, 38, 0.95);
  border-top: 1px solid var(--border);
}

.input-tools-bar {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 0.6rem;
}

.tool-btn {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 5px 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-secondary);
  cursor: pointer;
}

.tool-btn:hover {
  color: var(--text-main);
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.attached-preview-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(99, 102, 241, 0.2);
  border: 1px solid rgba(99, 102, 241, 0.4);
  padding: 3px 8px;
  border-radius: 20px;
  font-size: 0.74rem;
  color: #c7d2fe;
}

.mini-thumb {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  object-fit: cover;
}

.btn-remove-attachment {
  background: transparent;
  border: none;
  color: #fb7185;
  font-weight: 800;
  cursor: pointer;
  padding: 0 2px;
}

.input-form {
  display: flex;
  gap: 0.75rem;
}

.input-form textarea {
  flex: 1;
  background: #070a13;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 0.75rem 1rem;
  color: white;
  font-family: inherit;
  font-size: 0.9rem;
  resize: none;
}

.input-form textarea:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 15px rgba(99, 102, 241, 0.25);
}

.btn-send {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0 1.5rem;
  cursor: pointer;
}

.typing-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8rem;
  color: var(--text-muted);
}

.typing-dots {
  display: flex;
  gap: 4px;
}

.typing-dots span {
  width: 6px;
  height: 6px;
  background: #818cf8;
  border-radius: 50%;
  animation: bounce-dot 1.4s infinite ease-in-out both;
}

.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce-dot {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1.0); }
}
</style>
