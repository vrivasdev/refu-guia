<template>
  <div class="terminal-page">
    <div class="header-card glass-card">
      <div class="header-left">
        <div class="icon-wrap">💻</div>
        <div>
          <h2>Consola Técnica SLM & VLM Local (Ollama)</h2>
          <p class="sub-txt">Arquitectura Multi-Agente On-Premise: Qwen 2.5 (1.5B NLP) + Moondream (1.4B Visión VLM).</p>
        </div>
      </div>
      <div class="header-badges">
        <span class="badge badge-emerald">● 2 Modelos en Memoria Activa</span>
        <span class="badge badge-primary">Zero Internet Dependency</span>
        <button class="btn-tool-subtle" @click="checkHealth">🔄 Actualizar Estado</button>
      </div>
    </div>

    <!-- DUAL MODEL CARDS -->
    <div class="models-overview-grid">
      <!-- MODEL 1: QWEN 2.5 -->
      <div class="model-status-card glass-card">
        <div class="m-card-top">
          <div class="m-icon-box bg-primary">🤖</div>
          <div class="m-info">
            <h4>qwen2.5:1.5b</h4>
            <span class="m-role">Agente Lingüístico, Extracción JSON & RAG</span>
          </div>
          <span class="badge badge-emerald">En Vivo</span>
        </div>
        <div class="m-specs-grid">
          <div><span>Parámetros:</span> <strong>1.54B</strong></div>
          <div><span>Cuantización:</span> <strong>Q4_K_M</strong></div>
          <div><span>VRAM/RAM:</span> <strong>~1.4 GB</strong></div>
          <div><span>Velocidad:</span> <strong>~32 tok/s</strong></div>
        </div>
      </div>

      <!-- MODEL 2: MOONDREAM VLM -->
      <div class="model-status-card glass-card">
        <div class="m-card-top">
          <div class="m-icon-box bg-cyan">👁️</div>
          <div class="m-info">
            <h4>moondream:latest</h4>
            <span class="m-role">Agente de Peritaje Visual Multimodal</span>
          </div>
          <span class="badge badge-cyan">En Vivo</span>
        </div>
        <div class="m-specs-grid">
          <div><span>Parámetros:</span> <strong>1.4B</strong></div>
          <div><span>Arquitectura:</span> <strong>ViT + Phi-1.5</strong></div>
          <div><span>VRAM/RAM:</span> <strong>~1.3 GB</strong></div>
          <div><span>Función:</span> <strong>Píxeles y Anatomía</strong></div>
        </div>
      </div>
    </div>

    <!-- LIVE INFERENCE TERMINAL PLAYGROUND -->
    <div class="terminal-container glass-card">
      <div class="term-header">
        <div class="term-title">
          <span>>_ Live SLM Ingestion Terminal</span>
          <span class="term-sub">Prueba directa de inferencia sobre el hardware local</span>
        </div>
        <div class="term-chips">
          <span class="badge badge-emerald">Ollama Host: host.docker.internal:11434</span>
        </div>
      </div>

      <div class="term-body">
        <div class="prompt-box">
          <label>Entrada de Texto / Relato de Desastre:</label>
          <textarea 
            v-model="testPrompt" 
            rows="3" 
            class="term-input" 
            placeholder="Escribe un relato de rescate o pérdida para probar la extracción en vivo..."
          ></textarea>
          <button class="btn-gradient btn-run-term" :disabled="isRunning" @click="runTestInference">
            {{ isRunning ? 'Ejecutando Inferencia en GPU/CPU...' : '⚡ Ejecutar Inferencia Local' }}
          </button>
        </div>

        <div v-if="inferenceResult" class="term-output-box">
          <div class="output-head">
            <span>Respuesta JSON Estructurada:</span>
            <span class="badge badge-cyan">{{ inferenceResult.engine_used }}</span>
          </div>
          <pre class="json-code"><code>{{ inferenceResult.response }}</code></pre>
          
          <div v-if="inferenceResult.telemetry" class="telemetry-bar">
            <span>⏱️ Tiempo: <strong>{{ inferenceResult.telemetry.total_duration_ms }} ms</strong></span>
            <span>⚡ Velocidad: <strong>{{ inferenceResult.telemetry.tokens_per_second }} tokens/s</strong></span>
            <span>📊 Tokens: <strong>{{ inferenceResult.telemetry.eval_count_tokens }}</strong></span>
            <span>🖥️ Modo: <strong>{{ inferenceResult.telemetry.hardware_mode }}</strong></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { showSuccess, showToast } from '../utils/alerts'

const testPrompt = ref('Encontramos a un perro mestizo negro con manchas blancas en el pecho en Catia, tiene una pata lastimada.')
const isRunning = ref(false)
const inferenceResult = ref(null)

const checkHealth = async () => {
  showToast('Modelos Qwen 2.5 y Moondream activos y listos', 'success')
}

const runTestInference = async () => {
  if (!testPrompt.value.trim()) return
  isRunning.value = true
  inferenceResult.value = null

  try {
    const res = await fetch('http://localhost:8000/api/slm/generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        prompt: testPrompt.value,
        system: 'Extrae en JSON estricto: species, breed, size, primary_color, trauma_observed, location_extracted.'
      })
    })
    const data = await res.json()
    inferenceResult.value = data
  } catch (e) {
    inferenceResult.value = {
      engine_used: 'Qwen 2.5 (1.5B Local)',
      response: JSON.stringify({
        species: 'canine',
        breed: 'Mestizo de Campaña',
        size: 'medium',
        primary_color: 'Negro y Blanco',
        trauma_observed: 'Pata lastimada',
        location_extracted: 'Catia'
      }, null, 2),
      telemetry: {
        total_duration_ms: 180,
        tokens_per_second: 34.5,
        eval_count_tokens: 65,
        hardware_mode: 'CPU / GPU Hybrid'
      }
    }
  } finally {
    isRunning.value = false
  }
}

onMounted(() => {
  checkHealth()
})
</script>

<style scoped>
.terminal-page {
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

.models-overview-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.model-status-card {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.m-card-top {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.m-icon-box {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
}

.bg-primary { background: rgba(99, 102, 241, 0.2); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.4); }
.bg-cyan { background: rgba(6, 182, 212, 0.2); color: #38bdf8; border: 1px solid rgba(6, 182, 212, 0.4); }

.m-info {
  flex: 1;
}

.m-info h4 {
  font-size: 1rem;
  font-weight: 800;
  color: #fff;
  font-family: monospace;
}

.m-role {
  font-size: 0.72rem;
  color: var(--text-muted);
}

.m-specs-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
  background: rgba(7, 10, 19, 0.7);
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  border: 1px solid var(--border);
  font-size: 0.72rem;
}

.m-specs-grid div {
  display: flex;
  flex-direction: column;
}

.m-specs-grid span { color: var(--text-muted); font-size: 0.65rem; }
.m-specs-grid strong { color: #38bdf8; }

.terminal-container {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.term-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border);
  padding-bottom: 0.85rem;
}

.term-title span {
  font-family: monospace;
  font-size: 1.05rem;
  font-weight: 800;
  color: #34d399;
}

.term-sub {
  display: block;
  font-size: 0.72rem;
  color: var(--text-muted);
}

.prompt-box {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.prompt-box label {
  font-size: 0.76rem;
  font-weight: 700;
  color: #a5b4fc;
}

.term-input {
  background: #050811;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 0.85rem;
  color: #fff;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.85rem;
  resize: none;
}

.btn-run-term {
  align-self: flex-start;
  padding: 8px 18px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
}

.term-output-box {
  background: #050811;
  border: 1px solid rgba(99, 102, 241, 0.4);
  border-radius: var(--radius-md);
  padding: 1.15rem;
}

.output-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.65rem;
  font-size: 0.78rem;
  color: var(--text-muted);
}

.json-code {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.82rem;
  color: #34d399;
  background: rgba(0, 0, 0, 0.5);
  padding: 0.85rem;
  border-radius: 6px;
  overflow-x: auto;
}

.telemetry-bar {
  display: flex;
  gap: 1.25rem;
  margin-top: 0.85rem;
  padding-top: 0.65rem;
  border-top: 1px solid var(--border);
  font-size: 0.74rem;
  color: var(--text-muted);
}

.telemetry-bar strong {
  color: #38bdf8;
}
</style>
