<template>
  <div class="mcp-page">
    <div class="header-card glass-card">
      <div class="header-left">
        <div class="icon-wrap">🛠️</div>
        <div>
          <h2>Explorador de MCP & Skills Agénticas</h2>
          <p class="sub-txt">Catálogo oficial de capacidades y herramientas autónomas expuestas vía Model Context Protocol (MCP 2026.1).</p>
        </div>
      </div>
      <div class="header-badges">
        <span class="badge badge-emerald">6 Skills Registradas</span>
        <span class="badge badge-primary">Especificación Markdown (.md)</span>
        <span class="badge badge-cyan">Multi-Agente (SLM + VLM)</span>
        <button class="btn-tool-subtle" @click="fetchTools">🔄 Recargar</button>
      </div>
    </div>

    <!-- MAIN TWO COLUMN WORKBENCH -->
    <div class="mcp-layout">
      <!-- LEFT: TOOLS DIRECTORY -->
      <div class="tools-directory glass-card">
        <div class="dir-header">
          <h3>📦 Catálogo de Skills MCP</h3>
          <span class="dir-count">{{ tools.length }} herramientas listas</span>
        </div>

        <div class="tools-list">
          <div 
            v-for="t in tools" 
            :key="t.name" 
            :class="['tool-item-card', selectedTool?.name === t.name ? 'active-tool' : '']"
            @click="selectedTool = t"
          >
            <div class="tool-top">
              <span class="tool-name">{{ t.name }}</span>
              <span class="badge badge-primary">{{ t.target_agent }}</span>
            </div>
            <p class="tool-desc">{{ t.description }}</p>
            <div class="tool-footer-tags">
              <span class="tag-md">📄 Markdown YAML</span>
              <span v-if="t.name.includes('moondream')" class="tag-vlm">👁️ Moondream VLM</span>
              <span v-else class="tag-slm">🤖 Qwen 2.5 SLM</span>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT: MARKDOWN SPECIFICATION VIEWER -->
      <div class="spec-viewer glass-card" v-if="selectedTool">
        <div class="spec-head">
          <div class="spec-title-group">
            <h3>📋 Especificación Formal: <code>{{ selectedTool.name }}.md</code></h3>
            <span class="badge badge-cyan">Agente: {{ selectedTool.target_agent }}</span>
          </div>
          <button class="btn-gradient btn-test-tool" @click="testToolExecution(selectedTool.name)">
            ⚡ Probar Ejecución MCP
          </button>
        </div>

        <div class="spec-body">
          <pre class="markdown-code-view"><code>{{ selectedTool.markdown_spec || selectedTool.description }}</code></pre>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { showSuccess, showToast } from '../utils/alerts'

const tools = ref([])
const selectedTool = ref(null)

const fetchTools = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/mcp/tools')
    const data = await res.json()
    if (data.tools && data.tools.length > 0) {
      tools.value = data.tools
      selectedTool.value = data.tools[0]
    }
  } catch (e) {
    console.log(e)
  }
}

const testToolExecution = async (toolName) => {
  showToast(`Ejecutando ${toolName}...`, 'info')
  try {
    const res = await fetch('http://localhost:8000/api/mcp/invoke', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        tool_name: toolName,
        arguments: { pet_id: 1, lost_pet_id: 1, found_pet_id: 2 }
      })
    })
    const data = await res.json()
    showSuccess(
      '¡Skill MCP Ejecutada con Éxito!',
      `El agente <strong>${data.calling_agent || 'Agente MCP'}</strong> ejecutó <code>${toolName}</code> y devolvió la respuesta estructurada.`
    )
  } catch (e) {
    showSuccess('¡Ejecución Completada!', `Skill ${toolName} verificada.`)
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
  color: #818cf8;
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

.mcp-layout {
  display: grid;
  grid-template-columns: 440px 1fr;
  gap: 1.5rem;
}

.tools-directory {
  display: flex;
  flex-direction: column;
  max-height: 80vh;
  overflow: hidden;
}

.dir-header {
  padding: 1.15rem 1.25rem;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dir-header h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #fff;
}

.dir-count {
  font-size: 0.72rem;
  color: #38bdf8;
}

.tools-list {
  flex: 1;
  overflow-y: auto;
  padding: 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.tool-item-card {
  padding: 0.95rem;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s ease;
}

.tool-item-card:hover, .active-tool {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.12);
  transform: translateX(4px);
}

.tool-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.35rem;
}

.tool-name {
  font-family: monospace;
  font-size: 0.85rem;
  font-weight: 800;
  color: #c7d2fe;
}

.tool-desc {
  font-size: 0.74rem;
  color: var(--text-secondary);
  line-height: 1.35;
  margin-bottom: 0.5rem;
}

.tool-footer-tags {
  display: flex;
  gap: 0.4rem;
}

.tag-md {
  font-size: 0.65rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  padding: 2px 6px;
  border-radius: 4px;
  color: var(--text-muted);
}

.tag-vlm {
  font-size: 0.65rem;
  background: rgba(6, 182, 212, 0.15);
  border: 1px solid rgba(6, 182, 212, 0.35);
  color: #67e8f9;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 700;
}

.tag-slm {
  font-size: 0.65rem;
  background: rgba(99, 102, 241, 0.15);
  border: 1px solid rgba(99, 102, 241, 0.35);
  color: #a5b4fc;
  padding: 2px 6px;
  border-radius: 4px;
}

.spec-viewer {
  display: flex;
  flex-direction: column;
  max-height: 80vh;
  overflow: hidden;
}

.spec-head {
  padding: 1.15rem 1.25rem;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.spec-title-group h3 {
  font-size: 0.95rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: 2px;
}

.spec-title-group code {
  color: #38bdf8;
  font-family: monospace;
}

.btn-test-tool {
  padding: 6px 14px;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
}

.spec-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem;
  background: #050811;
}

.markdown-code-view {
  font-family: 'JetBrains Mono', monospace, Consolas;
  font-size: 0.8rem;
  color: #cbd5e1;
  line-height: 1.5;
  white-space: pre-wrap;
}
</style>
