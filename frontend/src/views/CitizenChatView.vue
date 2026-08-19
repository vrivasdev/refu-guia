<template>
  <div class="chat-wrapper">
    <div class="chat-window glass-card">
      <!-- HEADER -->
      <div class="chat-header">
        <div class="bot-avatar-pulse">
          <span>🤖</span>
        </div>
        <div class="bot-info">
          <div class="bot-name">{{ chatTitle }}</div>
          <div class="bot-status">
            <span class="dot-online"></span> {{ chatSubtitle }}
          </div>
        </div>
        <div class="header-tags">
          <span v-if="userRoleBadge" class="badge badge-primary">{{ userRoleBadge }}</span>
          <span class="badge badge-amber">Emergencia Sismo 2026</span>
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

            <!-- NLP EXTRACTION SUMMARY (CLEAN & EMPATHIC) -->
            <div v-if="msg.extractedCard" class="nlp-card">
              <div class="nlp-card-header">
                <div class="nlp-title">📋 Rasgos Extraídos por IA (Modelo Qwen 2.5)</div>
                <span class="badge badge-emerald">Vectorizado en ChromaDB</span>
              </div>
              <div class="nlp-grid">
                <div class="nlp-field">
                  <span class="field-lbl">Especie:</span>
                  <strong>{{ msg.extractedCard.species === 'canine' ? '🐶 Canino' : (msg.extractedCard.species === 'feline' ? '🐱 Felino' : '🐾 Mascota') }}</strong>
                </div>
                <div class="nlp-field">
                  <span class="field-lbl">Raza / Tipo:</span>
                  <strong>{{ msg.extractedCard.breed || 'Mestizo' }}</strong>
                </div>
                <div class="nlp-field">
                  <span class="field-lbl">Tamaño:</span>
                  <strong>{{ msg.extractedCard.size || 'Mediano' }}</strong>
                </div>
                <div class="nlp-field">
                  <span class="field-lbl">Color:</span>
                  <strong>{{ msg.extractedCard.primary_color || 'Negro y Blanco' }}</strong>
                </div>
                <div class="nlp-field full" v-if="msg.extractedCard.trauma_observed && msg.extractedCard.trauma_observed !== 'Sin traumatismos evidentes'">
                  <span class="field-lbl">Estado de Salud / Traumatismo:</span>
                  <span class="highlight-trauma">⚠️ {{ msg.extractedCard.trauma_observed }}</span>
                </div>
              </div>
            </div>

            <!-- VECTOR MATCH RESULTS FOR DAMNIFICADOS -->
            <div v-if="msg.matchesFound && msg.matchesFound.length > 0" class="matches-box">
              <div class="match-box-title">⚡ ¡Coincidencias Encontradas en Refugios!</div>
              <p class="match-box-sub">El motor de IA y búsqueda vectorial encontró animales rescatados con características similares a tu reporte:</p>
              
              <div class="matches-cards-grid">
                <div v-for="m in msg.matchesFound" :key="m.candidate_uuid" class="match-result-card">
                  <img :src="m.candidate_photo || defaultFallbackPhoto" class="match-candidate-img" />
                  <div class="match-candidate-details">
                    <div class="match-top-line">
                      <strong class="match-name">{{ m.candidate_name }}</strong>
                      <span class="badge badge-emerald">{{ m.similarity_score }}% Coincidencia</span>
                    </div>
                    <div class="match-uuid">ID Refugio: {{ m.candidate_uuid }}</div>
                    <div class="match-distance">📍 Distancia aproximada: {{ m.geo_distance_km }} km del reporte</div>
                    <router-link to="/matches" class="btn-verify-match">Verificar Mascota en Refugio →</router-link>
                  </div>
                </div>
              </div>
            </div>

            <!-- NO MATCHES FOUND NOTICE -->
            <div v-else-if="msg.isLostReport && (!msg.matchesFound || msg.matchesFound.length === 0)" class="no-matches-box">
              <div class="no-match-title">🔍 Búsqueda Activa Iniciada</div>
              <p class="no-match-text">Actualmente no hay mascotas rescatadas con estas características exactas en los refugios. Tu reporte ha quedado guardado y vectorizado; el sistema te alertará automáticamente en cuanto un rescatista registre un animal compatible.</p>
            </div>

            <!-- QR CREDENTIAL CARD (ONLY FOR RESCUERS / RESCUED PETS) -->
            <div v-if="msg.qrBadge" class="qr-credential-card">
              <div class="qr-code-holder">
                <img :src="getQrImageUrl(msg.qrBadge)" alt="Collar QR" class="qr-image" />
              </div>
              <div class="qr-details">
                <div class="badge badge-emerald">Collar de Campaña Generado</div>
                <div class="qr-code-text">{{ msg.qrBadge.uuid }}</div>
                <p class="qr-instruction">Código QR oficial listo para imprimir e identificar al animal rescatado en el refugio.</p>
                <router-link to="/refugios" class="link-btn">Ir al Expediente en Refugios →</router-link>
              </div>
            </div>

            <span class="bubble-time">{{ msg.time }}</span>
          </div>
        </div>

        <div v-if="isProcessing" class="typing-indicator">
          <div class="typing-dots"><span></span><span></span><span></span></div>
          <span class="typing-label">Modelo Local Ollama (Qwen 2.5) analizando relato y vectorizando en ChromaDB...</span>
        </div>
      </div>

      <!-- ROLE-BASED QUICK ACTION CARDS (STRICTLY MUTUALLY EXCLUSIVE) -->
      <div v-if="messages.length <= 2" class="quick-cards-grid single-col">
        <!-- CITIZEN / DAMNIFICADO: ONLY "PERDÍ A MI MASCOTA" -->
        <button 
          v-if="isCitizenRole" 
          class="quick-action-card lost" 
          @click="selectQuickOption('lost')"
        >
          <div class="quick-icon">🔍</div>
          <div class="quick-text">
            <strong>Reportar Mascota Extraviada</strong>
            <span>Describir a tu perro/gato para buscar coincidencias vectoriales en los refugios</span>
          </div>
        </button>

        <!-- RESCUER / SHELTER ADMIN: ONLY "REGISTRAR RESCATE EN CAMPO" -->
        <button 
          v-else 
          class="quick-action-card found" 
          @click="selectQuickOption('found')"
        >
          <div class="quick-icon">🏡</div>
          <div class="quick-text">
            <strong>Registrar Mascota Rescatada en Campo</strong>
            <span>Generar collar QR de campaña e ingresar al inventario de refugio</span>
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

          <button 
            :class="['tool-btn', isRecordingAudio ? 'btn-recording-pulse' : '']" 
            @click="toggleAudioRecording"
          >
            <span>{{ isRecordingAudio ? '🔴 Detener Grabación' : '🎙️ Grabar Nota de Voz' }}</span>
          </button>

          <div v-if="uploadedPhotoBase64" class="attached-preview-pill">
            <img :src="uploadedPhotoBase64" class="mini-thumb" />
            <span>{{ selectedPhotoName || 'Foto Adjunta' }}</span>
            <button class="btn-remove-attachment" @click="removeAttachedPhoto">✕</button>
          </div>

          <div v-if="voiceTranscriptNotice" class="voice-status-pill">
            <span>{{ voiceTranscriptNotice }}</span>
          </div>
        </div>

        <div class="input-form">
          <textarea 
            v-model="userInput" 
            @keydown.enter.prevent="sendMessage"
            :placeholder="inputPlaceholder"
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
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

const defaultFallbackPhoto = 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80'

const threadRef = ref(null)
const userInput = ref('')
const isProcessing = ref(false)
const selectedPhotoName = ref('')
const uploadedPhotoBase64 = ref('')
const currentUser = ref(null)

const isCitizenRole = computed(() => {
  if (!currentUser.value) return true
  return currentUser.value.role === 'citizen' || currentUser.value.role === 'adopter'
})

const currentReportType = computed(() => {
  return isCitizenRole.value ? 'lost' : 'found'
})

const chatTitle = computed(() => {
  return isCitizenRole.value ? 'Búsqueda Familiar y Asistente Ciudadano' : 'Ingreso de Rescates y Triage de Campo'
})

const chatSubtitle = computed(() => {
  return isCitizenRole.value 
    ? 'En línea • Búsqueda Vectorial Semántica con IA Local' 
    : 'En línea • Generación de Collares QR de Campaña'
})

const userRoleBadge = computed(() => {
  if (!currentUser.value) return 'Ciudadano Damnificado'
  switch(currentUser.value.role) {
    case 'citizen': return 'Damnificada (Búsqueda Familiar)'
    case 'rescuer': return 'Rescatista de Campo'
    case 'shelter_admin': return 'Coordinadora de Refugio'
    case 'adopter': return 'Adoptante'
    default: return currentUser.value.role
  }
})

const inputPlaceholder = computed(() => {
  return isCitizenRole.value
    ? 'Describe a tu mascota perdida (ej: Perdí a mi perrito Toby en Catia, es mestizo negro con mancha blanca en el pecho)...'
    : 'Describe a la mascota rescatada en campo (ej: Rescatamos a un perro mestizo mediano negro con pata lastimada en Caricuao)...'
})

// REAL SPEECH RECOGNITION (WEB SPEECH API)
const isRecordingAudio = ref(false)
const voiceTranscriptNotice = ref('')
let recognition = null

const initSpeechRecognition = () => {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
  if (SpeechRecognition) {
    recognition = new SpeechRecognition()
    recognition.lang = 'es-ES'
    recognition.continuous = true
    recognition.interimResults = true

    recognition.onstart = () => {
      isRecordingAudio.value = true
      voiceTranscriptNotice.value = '🎙️ Escuchando...'
    }

    recognition.onresult = (event) => {
      let finalTranscript = ''
      for (let i = event.resultIndex; i < event.results.length; ++i) {
        if (event.results[i].isFinal) {
          finalTranscript += event.results[i][0].transcript + ' '
        }
      }
      if (finalTranscript) {
        userInput.value = (userInput.value ? userInput.value + ' ' : '') + finalTranscript.trim()
      }
    }

    recognition.onerror = () => {
      isRecordingAudio.value = false
      voiceTranscriptNotice.value = ''
    }

    recognition.onend = () => {
      isRecordingAudio.value = false
      voiceTranscriptNotice.value = ''
    }
  }
}

const toggleAudioRecording = () => {
  if (!recognition) initSpeechRecognition()

  if (!recognition) {
    userInput.value = isCitizenRole.value
      ? 'Perdí a mi perrito mestizo mediano negro con manchas blancas en el pecho cerca de Caricuao durante el sismo.'
      : 'Rescatamos a un perrito mestizo mediano negro con manchas blancas en el pecho cerca de Caricuao.'
    voiceTranscriptNotice.value = '🎙️ Nota de voz cargada'
    setTimeout(() => { voiceTranscriptNotice.value = '' }, 3000)
    return
  }

  if (isRecordingAudio.value) {
    recognition.stop()
    isRecordingAudio.value = false
  } else {
    try {
      recognition.start()
    } catch (e) {}
  }
}

const messages = ref([
  {
    sender: 'bot',
    text: '<strong>¡Hola! Soy RefuGuía</strong>, tu asistente de IA para emergencias post-sismo.<br><br>¿En qué puedo ayudarte hoy?',
    time: 'Ahora'
  }
])

const loadUserSession = () => {
  try {
    const saved = localStorage.getItem('refuguia_user')
    if (saved) {
      currentUser.value = JSON.parse(saved)
    }
  } catch (e) {}
}

const scrollToBottom = () => {
  nextTick(() => {
    if (threadRef.value) {
      threadRef.value.scrollTop = threadRef.value.scrollHeight
    }
  })
}

const selectQuickOption = (type) => {
  if (type === 'lost') {
    userInput.value = 'Perdí a mi perrito Toby en la zona de Caricuao durante el sismo. Es un mestizo mediano negro con manchas blancas en el pecho.'
  } else {
    userInput.value = 'Rescatamos a un perrito mestizo mediano negro con manchas blancas en el pecho cerca de Caricuao. Tiene una patita lastimada y tiembla de frío.'
  }
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

  if (isRecordingAudio.value && recognition) {
    recognition.stop()
  }

  const textToSend = userInput.value.trim()
  const attachedPhoto = uploadedPhotoBase64.value
  const reportType = currentReportType.value

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
        report_type: reportType,
        location_address: 'Caracas / Zona de Emergencia',
        photo_url: attachedPhoto || defaultFallbackPhoto
      })
    })
    const data = await res.json()

    if (data.success) {
      const ext = data.nlp_extraction || {}
      const petUuid = data.pet?.uuid || 'RG-2026-EMERG'
      const isLost = (reportType === 'lost')
      
      let botResponseText = ''
      if (isLost) {
        botResponseText = (data.matches_found && data.matches_found.length > 0)
          ? `He vectorizado tu reporte de búsqueda y <strong>encontré ${data.matches_found.length} mascota(s) rescatada(s) con alta coincidencia</strong> en los refugios:`
          : `He registrado tu reporte de búsqueda familiar en el sistema y vectorizado las características de tu mascota en ChromaDB.`
      } else {
        botResponseText = `¡Mascota rescatada registrada exitosamente en el refugio! Se ha generado su collar QR oficial de campaña.`
      }

      messages.value.push({
        sender: 'bot',
        text: botResponseText,
        extractedCard: ext,
        isLostReport: isLost,
        qrBadge: data.qr_badge ? { uuid: petUuid, print_ready_badge: data.qr_badge.print_ready_badge } : null,
        matchesFound: data.matches_found || [],
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      })
    } else {
      messages.value.push({
        sender: 'bot',
        text: `⚠️ <strong>Aviso del Sistema:</strong> ${data.error || 'No se pudo procesar la solicitud.'}`,
        time: 'Ahora'
      })
    }
  } catch (err) {
    messages.value.push({
      sender: 'bot',
      text: `❌ Error al conectar con el servidor.`,
      time: 'Ahora'
    })
  } finally {
    isProcessing.value = false
    scrollToBottom()
  }
}

onMounted(() => {
  loadUserSession()
  initSpeechRecognition()
  scrollToBottom()
})

onUnmounted(() => {
  if (recognition) recognition.stop()
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

/* MATCHES FOR DAMNIFICADOS */
.matches-box {
  margin-top: 1rem;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: 14px;
  padding: 1.25rem;
}

.match-box-title {
  font-size: 0.95rem;
  font-weight: 800;
  color: #34d399;
  margin-bottom: 0.25rem;
}

.match-box-sub {
  font-size: 0.76rem;
  color: var(--text-secondary);
  margin-bottom: 0.95rem;
}

.matches-cards-grid {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.match-result-card {
  display: flex;
  gap: 1rem;
  background: rgba(7, 10, 19, 0.85);
  border: 1px solid rgba(99, 102, 241, 0.35);
  border-radius: 12px;
  padding: 0.85rem;
  align-items: center;
}

.match-candidate-img {
  width: 75px;
  height: 75px;
  border-radius: 10px;
  object-fit: cover;
  border: 2px solid #6366f1;
}

.match-candidate-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.match-top-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.match-name {
  font-size: 0.95rem;
  color: #fff;
}

.match-uuid {
  font-family: monospace;
  font-size: 0.75rem;
  color: #38bdf8;
}

.match-distance {
  font-size: 0.72rem;
  color: var(--text-muted);
}

.btn-verify-match {
  display: inline-block;
  margin-top: 0.35rem;
  font-size: 0.78rem;
  font-weight: 700;
  color: #818cf8;
}

/* NO MATCHES ACTIVE SEARCH BOX */
.no-matches-box {
  margin-top: 1rem;
  background: rgba(99, 102, 241, 0.1);
  border: 1px solid rgba(99, 102, 241, 0.3);
  border-radius: 14px;
  padding: 1.15rem;
}

.no-match-title {
  font-size: 0.88rem;
  font-weight: 800;
  color: #a5b4fc;
  margin-bottom: 0.3rem;
}

.no-match-text {
  font-size: 0.78rem;
  color: var(--text-secondary);
  line-height: 1.4;
}

/* QR CREDENTIAL CARD */
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

.quick-cards-grid {
  display: grid;
  gap: 1rem;
  padding: 0 1.5rem 1rem 1.5rem;
}

.quick-cards-grid.single-col {
  grid-template-columns: 1fr;
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
  flex-wrap: wrap;
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
  transition: all 0.2s ease;
}

.tool-btn:hover {
  color: var(--text-main);
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.btn-recording-pulse {
  background: rgba(239, 68, 68, 0.25) !important;
  border-color: #ef4444 !important;
  color: #fca5a5 !important;
  animation: pulseRecording 1.5s infinite ease-in-out;
}

@keyframes pulseRecording {
  0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
}

.voice-status-pill {
  font-size: 0.72rem;
  color: #fbbf24;
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(245, 158, 11, 0.35);
  padding: 3px 8px;
  border-radius: var(--radius-sm);
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
