<template>
  <div class="mcp-page">
    <!-- TOP HEADER -->
    <div class="header-box glass-card">
      <div class="header-left">
        <div class="mcp-badge-icon">🛠️</div>
        <div>
          <h2>Servidor MCP — Protocolo Model Context Protocol</h2>
          <p class="sub-txt">Catálogo declarativo de SKILLS sincronizado con los archivos <code>backend/storage/app/skills/*.md</code></p>
        </div>
      </div>
      <div class="header-badges">
        <span class="badge badge-primary">Protocolo MCP 2026.1</span>
        <span class="badge badge-emerald">5 Skills Sincronizadas (.md)</span>
        <button class="btn-tool-subtle" @click="fetchTools">🔄 Recargar</button>
      </div>
    </div>

    <!-- MAIN TWO COLUMN WORKBENCH -->
    <div class="workbench">
      <!-- LEFT: SKILLS CATALOG FROM .MD -->
      <div class="catalog-col glass-card">
        <div class="col-head">
          <div>
            <h3>📦 Catálogo de Skills (.md)</h3>
            <span class="sub-text">Herramientas atómicas del agente</span>
          </div>
        </div>

        <div class="skills-scroll">
          <div 
            v-for="tool in tools" 
            :key="tool.name" 
            :class="['skill-card', selectedTool?.name === tool.name ? 'active-skill' : '']"
            @click="selectTool(tool)"
          >
            <div class="skill-top">
              <div class="skill-name">{{ tool.name }}</div>
              <span class="badge badge-cyan">v{{ tool.version }}</span>
            </div>
            <div class="skill-cat">📂 {{ tool.category }}</div>
            <div class="skill-desc">{{ tool.description }}</div>
            <div class="skill-file-source">📄 {{ tool.definition_source }}</div>
          </div>
        </div>
      </div>

      <!-- RIGHT: MARKDOWN SPECIFICATION & LIVE MCP SANDBOX -->
      <div class="details-col glass-card" v-if="selectedTool">
        <div class="col-head">
          <div class="selected-head-group">
            <h3>📖 Especificación: {{ selectedTool.name }}</h3>
            <span class="badge badge-amber">{{ selectedTool.category }}</span>
          </div>
          <div class="meta-pills">
            <span class="meta-pill">👤 {{ selectedTool.author }}</span>
            <span class="meta-pill">⏱️ {{ selectedTool.timeout_ms }}ms</span>
          </div>
        </div>

        <div class="details-scroll">
          <!-- TABS: MARKDOWN VIEW vs LIVE RUNNER -->
          <div class="view-mode-tabs">
            <button 
              :class="['tab-btn', activeTab === 'doc' ? 'tab-active' : '']" 
              @click="activeTab = 'doc'"
            >
              📄 Documentación Markdown (.md)
            </button>
            <button 
              :class="['tab-btn', activeTab === 'runner' ? 'tab-active' : '']" 
              @click="activeTab = 'runner'"
            >
              ⚡ Ejecutar Skill (Sandbox MCP)
            </button>
          </div>

          <!-- TAB 1: MARKDOWN SPEC RENDERER -->
          <div v-if="activeTab === 'doc'" class="markdown-preview-box">
            <div class="raw-code-box">
              <div class="raw-code-header">
                <span>Ruta del Archivo: <strong>{{ selectedTool.definition_source }}</strong></span>
                <span class="badge badge-primary">Markdown + YAML Frontmatter</span>
              </div>
              <pre class="md-content-pre"><code>{{ selectedTool.raw_markdown || selectedTool.markdown_body }}</code></pre>
            </div>
          </div>

          <!-- TAB 2: LIVE EXECUTION SANDBOX -->
          <div v-if="activeTab === 'runner'" class="runner-box">
            <div class="runner-card">
              <h4>Esquema de Parámetros Requeridos (JSON Schema):</h4>
              <pre class="schema-pre"><code>{{ JSON.stringify(selectedTool.parameters, null, 2) }}</code></pre>

              <h4 style="margin-top: 1rem;">Argumentos de Prueba para la Skill:</h4>
              <textarea 
                v-model="argumentsInput" 
                rows="4" 
                class="input-dark-area"
                placeholder='{"pet_id": 1}'
              ></textarea>

              <button class="btn-gradient btn-run" :disabled="loading" @click="runTool">
                {{ loading ? 'Ejecutando en Servidor MCP...' : '⚡ Ejecutar Skill Directamente' }}
              </button>
            </div>

            <!-- RESULT CONSOLE -->
            <div v-if="executionResult" class="result-console">
              <div class="console-head">
                <span>📡 Respuesta del Servidor MCP</span>
                <span :class="['badge', executionResult.success ? 'badge-emerald' : 'badge-rose']">
                  {{ executionResult.success ? 'HTTP 200 OK' : 'ERROR' }}
                </span>
              </div>
              <pre class="result-pre"><code>{{ JSON.stringify(executionResult, null, 2) }}</code></pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const tools = ref([])
const selectedTool = ref(null)
const activeTab = ref('doc')
const argumentsInput = ref('{\n  "pet_id": 1\n}')
const executionResult = ref(null)
const loading = ref(false)

const fetchTools = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/mcp/tools')
    const data = await res.json()
    if (data.tools && data.tools.length > 0) {
      tools.value = data.tools
      if (!selectedTool.value) {
        selectTool(data.tools[0])
      } else {
        const current = data.tools.find(t => t.name === selectedTool.value.name)
        if (current) selectedTool.value = current
      }
    }
  } catch (e) {
    console.error('Error fetching MCP tools:', e)
  }
}

const selectTool = (tool) => {
  selectedTool.value = tool
  executionResult.value = null
  
  if (tool.name === 'skill_verificar_periodo_gracia' || tool.name === 'skill_generar_identidad_qr') {
    argumentsInput.value = '{\n  "pet_id": 1\n}'
  } else if (tool.name === 'skill_extraer_entidades_nlp') {
    argumentsInput.value = '{\n  "raw_text": "Perro mestizo negro de tamaño mediano encontrado en Catia con pata lastimada"\n}'
  } else if (tool.name === 'skill_calcular_similitud_vectorial') {
    argumentsInput.value = '{\n  "lost_pet_id": 1,\n  "found_pet_id": 2\n}'
  } else if (tool.name === 'skill_evaluar_compatibilidad_adopcion') {
    argumentsInput.value = '{\n  "pet_id": 2,\n  "monthly_income_usd": 400,\n  "housing_type": "house_with_patio",\n  "hours_dedicated_daily": 4\n}'
  } else {
    argumentsInput.value = '{}'
  }
}

const runTool = async () => {
  if (!selectedTool.value) return
  loading.value = true
  executionResult.value = null

  try {
    let parsedArgs = {}
    try {
      parsedArgs = JSON.parse(argumentsInput.value)
    } catch (err) {
      executionResult.value = { success: false, error: 'JSON de argumentos inválido: ' + err.message }
      loading.value = false
      return
    }

    const res = await fetch('http://localhost:8000/api/mcp/invoke', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        tool_name: selectedTool.value.name,
        arguments: parsedArgs
      })
    })

    const data = await res.json()
    executionResult.value = data
  } catch (e) {
    executionResult.value = { success: false, error: e.message }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchTools()
})
</script>

<style scoped>
.mcp-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.header-box {
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

.mcp-badge-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(99, 102, 241, 0.2);
  border: 1px solid rgba(99, 102, 241, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
}

.header-left h2 {
  font-size: 1.2rem;
  font-weight: 800;
  color: #fff;
}

.sub-txt {
  font-size: 0.78rem;
  color: var(--text-muted);
}

.sub-txt code {
  color: #38bdf8;
  background: rgba(6, 182, 212, 0.1);
  padding: 2px 6px;
  border-radius: 4px;
}

.header-badges {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.btn-tool-subtle {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  color: var(--text-main);
  cursor: pointer;
}

.workbench {
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 1.5rem;
}

.catalog-col, .details-col {
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

.selected-head-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.selected-head-group h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #fff;
}

.meta-pills {
  display: flex;
  gap: 0.5rem;
}

.meta-pill {
  font-size: 0.72rem;
  background: rgba(255, 255, 255, 0.05);
  padding: 3px 8px;
  border-radius: var(--radius-sm);
  color: #94a3b8;
}

.skills-scroll {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding-right: 4px;
}

.skill-card {
  padding: 0.95rem;
  background: rgba(7, 10, 19, 0.6);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.skill-card:hover, .skill-card.active-skill {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.15);
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.15);
}

.skill-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.skill-name {
  font-family: monospace;
  font-weight: 700;
  font-size: 0.82rem;
  color: #38bdf8;
}

.skill-cat {
  font-size: 0.72rem;
  color: #fbbf24;
  font-weight: 600;
}

.skill-desc {
  font-size: 0.75rem;
  color: var(--text-secondary);
  line-height: 1.35;
}

.skill-file-source {
  font-family: monospace;
  font-size: 0.68rem;
  color: #64748b;
  margin-top: 0.2rem;
}

.details-scroll {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding-right: 4px;
}

.view-mode-tabs {
  display: flex;
  gap: 0.5rem;
  border-bottom: 1px solid var(--border);
  padding-bottom: 0.5rem;
}

.tab-btn {
  background: transparent;
  border: 1px solid transparent;
  padding: 0.45rem 0.95rem;
  border-radius: var(--radius-sm);
  color: var(--text-muted);
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tab-btn:hover {
  color: white;
}

.tab-active {
  background: rgba(99, 102, 241, 0.25);
  border-color: #6366f1;
  color: #ffffff !important;
}

.raw-code-box {
  background: #060911;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.raw-code-header {
  background: #0c111e;
  padding: 0.6rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.76rem;
  color: var(--text-secondary);
  border-bottom: 1px solid var(--border);
}

.md-content-pre {
  margin: 0;
  padding: 1.25rem;
  color: #e2e8f0;
  font-family: monospace;
  font-size: 0.82rem;
  line-height: 1.6;
  white-space: pre-wrap;
  word-break: break-word;
}

.runner-card {
  background: rgba(7, 10, 19, 0.6);
  border: 1px solid var(--border);
  padding: 1.15rem;
  border-radius: var(--radius-md);
}

.runner-card h4 {
  font-size: 0.85rem;
  font-weight: 700;
  color: #a5b4fc;
  margin-bottom: 0.4rem;
}

.schema-pre {
  background: #060911;
  border: 1px solid var(--border);
  padding: 0.75rem;
  border-radius: var(--radius-sm);
  font-family: monospace;
  font-size: 0.76rem;
  color: #38bdf8;
  overflow-x: auto;
}

.input-dark-area {
  width: 100%;
  background: #060911;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 0.75rem;
  color: #34d399;
  font-family: monospace;
  font-size: 0.82rem;
  resize: vertical;
}

.btn-run {
  width: 100%;
  padding: 0.75rem;
  margin-top: 0.85rem;
  font-size: 0.88rem;
  font-weight: 700;
}

.result-console {
  background: #060911;
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: var(--radius-md);
  overflow: hidden;
  margin-top: 1rem;
}

.console-head {
  background: rgba(16, 185, 129, 0.15);
  padding: 0.6rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
  font-weight: 700;
  color: #34d399;
}

.result-pre {
  margin: 0;
  padding: 1rem;
  font-family: monospace;
  font-size: 0.78rem;
  color: #e2e8f0;
  overflow-x: auto;
}
</style>
