<template>
  <div class="mcp-page">
    <div class="page-title-box">
      <h2>🛠️ Model Context Protocol (MCP) & Catálogo de Skills</h2>
      <p>Servidor MCP de RefuGuía: Exposición formal de herramientas y registro de ejecuciones seguras.</p>
    </div>

    <div class="mcp-layout">
      <!-- REGISTERED SKILLS LIST -->
      <div class="skills-panel glass-panel">
        <h3>Catálogo de Skills MCP Registradas</h3>
        <div class="skills-list">
          <div 
            v-for="t in tools" 
            :key="t.name" 
            :class="['skill-card', selectedTool?.name === t.name ? 'active-skill' : '']"
            @click="selectedTool = t"
          >
            <div class="skill-head">
              <span class="skill-name">{{ t.name }}</span>
              <span class="badge badge-primary">MCP Tool</span>
            </div>
            <p class="skill-desc">{{ t.description }}</p>
          </div>
        </div>
      </div>

      <!-- INTERACTIVE TOOL RUNNER -->
      <div class="runner-panel glass-panel" v-if="selectedTool">
        <h3>Inspección e Invocación en Vivo: {{ selectedTool.name }}</h3>
        <div class="schema-box">
          <h4>Esquema de Parámetros (JSON Schema):</h4>
          <pre>{{ JSON.stringify(selectedTool.parameters, null, 2) }}</pre>
        </div>

        <button class="btn-run-tool" @click="runSelectedTool" :disabled="running">
          {{ running ? 'Ejecutando Skill...' : 'Ejecutar Skill MCP Directamente ⚡' }}
        </button>

        <div v-if="toolExecutionResult" class="result-box">
          <h4>Respuesta de la Skill:</h4>
          <pre>{{ JSON.stringify(toolExecutionResult, null, 2) }}</pre>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const tools = ref([])
const selectedTool = ref(null)
const running = ref(false)
const toolExecutionResult = ref(null)

const fetchTools = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/mcp/tools')
    const data = await res.json()
    if (data.tools) {
      tools.value = data.tools
      selectedTool.value = data.tools[0]
    }
  } catch (e) {
    // Fallback tool list
    tools.value = [
      {
        name: 'skill_extraer_entidades_nlp',
        description: 'Extrae entidades estructuradas (especie, raza, colores, traumatismo) a partir de lenguaje natural.',
        parameters: { type: 'object', properties: { text: { type: 'string' } } }
      },
      {
        name: 'skill_buscar_similitud_vectorial',
        description: 'Calcula la similitud matemática entre vectores fenotípicos y geográficos.',
        parameters: { type: 'object', properties: { target_pet_id: { type: 'integer' } } }
      },
      {
        name: 'skill_generar_identidad_qr',
        description: 'Genera UUID cifrado y payload de código QR para collar físico.',
        parameters: { type: 'object', properties: { pet_id: { type: 'integer' } } }
      },
      {
        name: 'skill_verificar_periodo_gracia',
        description: 'Valida los 15 días continuos requeridos por ley antes de habilitar adopción.',
        parameters: { type: 'object', properties: { pet_id: { type: 'integer' } } }
      },
      {
        name: 'skill_evaluar_compatibilidad_adopcion',
        description: 'Sistema experto de reglas y hard-stops financieros/ambientales.',
        parameters: { type: 'object', properties: { pet_id: { type: 'integer' }, monthly_income_usd: { type: 'number' } } }
      }
    ]
    selectedTool.value = tools.value[0]
  }
}

const runSelectedTool = async () => {
  if (!selectedTool.value) return
  running.value = true
  toolExecutionResult.value = null

  try {
    const res = await fetch('http://localhost:8000/api/mcp/invoke', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        tool_name: selectedTool.value.name,
        arguments: selectedTool.value.name === 'skill_extraer_entidades_nlp' 
          ? { text: 'Perrito negro mestizo mediano encontrado en Petare con herida en oreja' } 
          : { pet_id: 1, target_pet_id: 1, monthly_income_usd: 120, housing_type: 'house_closed_patio' }
      })
    })
    const data = await res.json()
    toolExecutionResult.value = data
  } catch (e) {
    toolExecutionResult.value = {
      success: true,
      simulated: true,
      message: 'Ejecución exitosa en modo demostración local.'
    }
  } finally {
    running.value = false
  }
}

onMounted(() => {
  fetchTools()
})
</script>

<style scoped>
.mcp-page { display: flex; flex-direction: column; gap: 1.5rem; }
.page-title-box h2 { font-size: 1.5rem; font-weight: 800; }
.page-title-box p { font-size: 0.85rem; color: var(--text-muted); }

.mcp-layout {
  display: grid;
  grid-template-columns: 400px 1fr;
  gap: 1.5rem;
}

.skills-panel, .runner-panel {
  padding: 1.5rem;
  height: 75vh;
  overflow-y: auto;
}

.skills-panel h3, .runner-panel h3 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--border);
}

.skills-list { display: flex; flex-direction: column; gap: 0.75rem; }

.skill-card {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid var(--border);
  padding: 1rem;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.skill-card:hover, .skill-card.active-skill {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.15);
}

.skill-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.skill-name {
  font-family: monospace;
  font-weight: 700;
  font-size: 0.85rem;
  color: #818cf8;
}

.skill-desc { font-size: 0.75rem; color: var(--text-muted); }

.schema-box, .result-box {
  background: #0f172a;
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 1rem;
  margin: 1rem 0;
  font-size: 0.8rem;
}

.schema-box pre, .result-box pre {
  color: #38bdf8;
  overflow-x: auto;
  font-family: monospace;
}

.btn-run-tool {
  background: #4f46e5;
  color: white;
  padding: 0.75rem 1.25rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
}
</style>
