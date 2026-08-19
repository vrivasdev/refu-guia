<template>
  <div class="app-layout">
    <!-- TOP NAVIGATION BAR -->
    <header class="top-nav">
      <div class="nav-container">
        <div class="brand">
          <div class="brand-icon">🐾</div>
          <div class="brand-text">
            <span class="brand-name">RefuGuía</span>
            <span class="brand-sub">Sistema Agéntico Post-Sismo</span>
          </div>
        </div>

        <nav class="nav-links">
          <router-link to="/" class="nav-item" active-class="active">
            <span class="icon">💬</span> Chat Ciudadano
          </router-link>
          <router-link to="/refugios" class="nav-item" active-class="active">
            <span class="icon">🏥</span> Dashboard Refugios & QR
          </router-link>
          <router-link to="/matches" class="nav-item" active-class="active">
            <span class="icon">⚡</span> Matchmaker Hub
          </router-link>
          <router-link to="/adopcion" class="nav-item" active-class="active">
            <span class="icon">❤️</span> Adopción (15d Gracia)
          </router-link>
          <router-link to="/mcp" class="nav-item" active-class="active">
            <span class="icon">🛠️</span> MCP & Skills
          </router-link>
          <router-link to="/terminal-slm" class="nav-item" active-class="active">
            <span class="icon">💻</span> SLM Local (Ollama)
          </router-link>
        </nav>

        <div class="system-status">
          <div class="status-indicator online"></div>
          <div class="status-info">
            <span class="status-title">SLM Local Activo</span>
            <span class="status-detail">qwen2.5:1.5b • ChromaDB</span>
          </div>
        </div>
      </div>
    </header>

    <!-- MAIN VIEW CONTAINER -->
    <main class="main-content">
      <router-view />
    </main>

    <!-- FOOTER -->
    <footer class="app-footer">
      <div class="footer-container">
        <p>RefuGuía © 2026 — Proyecto Final IA Aplicada a Organizaciones (UTN-FRBA & EPIData)</p>
        <p class="footer-meta">Arquitectura 100% IA Local (SLM) • Protocolo MCP • Memoria Dual (MySQL + ChromaDB)</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.top-nav {
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0.75rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.brand-icon {
  font-size: 1.8rem;
  background: rgba(99, 102, 241, 0.2);
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  border: 1px solid rgba(99, 102, 241, 0.3);
}

.brand-name {
  font-size: 1.25rem;
  font-weight: 800;
  background: linear-gradient(135deg, #818cf8, #38bdf8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  display: block;
}

.brand-sub {
  font-size: 0.7rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.nav-links {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  overflow-x: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.85rem;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--text-muted);
  transition: all 0.2s ease;
  white-space: nowrap;
}

.nav-item:hover {
  color: var(--text-main);
  background: rgba(255, 255, 255, 0.05);
}

.nav-item.active {
  color: #fff;
  background: rgba(99, 102, 241, 0.25);
  border: 1px solid rgba(99, 102, 241, 0.4);
  font-weight: 600;
}

.system-status {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.35rem 0.75rem;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.2);
  border-radius: 10px;
}

.status-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--success);
  box-shadow: 0 0 10px var(--success);
  animation: pulse 2s infinite;
}

.status-title {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  color: #34d399;
}

.status-detail {
  display: block;
  font-size: 0.65rem;
  color: var(--text-muted);
}

@keyframes pulse {
  0% { transform: scale(0.95); opacity: 0.8; }
  50% { transform: scale(1.15); opacity: 1; }
  100% { transform: scale(0.95); opacity: 0.8; }
}

.main-content {
  flex: 1;
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
  padding: 1.5rem;
}

.app-footer {
  border-top: 1px solid var(--border);
  padding: 1.5rem;
  text-align: center;
  font-size: 0.8rem;
  color: var(--text-muted);
  background: rgba(15, 23, 42, 0.6);
}

.footer-meta {
  font-size: 0.7rem;
  margin-top: 0.25rem;
  opacity: 0.7;
}

@media (max-width: 900px) {
  .nav-container {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
