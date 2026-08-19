<template>
  <div class="app-wrapper">
    <!-- TOP NAVBAR -->
    <header class="navbar">
      <div class="nav-content">
        <!-- LOGO & BRAND -->
        <router-link to="/" class="brand-group">
          <div class="logo-box">
            <span class="logo-icon">🐾</span>
          </div>
          <div class="brand-text">
            <div class="brand-title">
              RefuGuía <span class="version-tag">SLM 2.5</span>
            </div>
            <div class="brand-sub">Sistema Agéntico Post-Sismo</div>
          </div>
        </router-link>

        <!-- NAV LINKS -->
        <nav class="nav-menu">
          <router-link to="/" class="nav-pill" active-class="nav-pill-active">
            <span class="pill-icon">💬</span>
            <span class="pill-label">Chat Ciudadano</span>
          </router-link>
          <router-link to="/refugios" class="nav-pill" active-class="nav-pill-active">
            <span class="pill-icon">🏥</span>
            <span class="pill-label">Refugios & QR</span>
          </router-link>
          <router-link to="/matches" class="nav-pill" active-class="nav-pill-active">
            <span class="pill-icon">⚡</span>
            <span class="pill-label">Matchmaker Hub</span>
          </router-link>
          <router-link to="/adopcion" class="nav-pill" active-class="nav-pill-active">
            <span class="pill-icon">❤️</span>
            <span class="pill-label">Adopción (15d)</span>
          </router-link>
          <router-link to="/mcp" class="nav-pill" active-class="nav-pill-active">
            <span class="pill-icon">🛠️</span>
            <span class="pill-label">MCP & Skills</span>
          </router-link>
          <router-link to="/terminal-slm" class="nav-pill" active-class="nav-pill-active">
            <span class="pill-icon">💻</span>
            <span class="pill-label">SLM Local</span>
          </router-link>
        </nav>

        <!-- STATUS WIDGET -->
        <div class="system-status-chip">
          <div class="status-pulse-dot"></div>
          <div class="status-texts">
            <span class="status-mode">SLM Local Activo</span>
            <span class="status-engine">qwen2.5:1.5b • ChromaDB</span>
          </div>
        </div>
      </div>
    </header>

    <!-- CONTENT AREA -->
    <main class="page-container">
      <router-view />
    </main>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="footer-inner">
        <div>
          <strong>RefuGuía</strong> — Proyecto Final IA Aplicada a Organizaciones (UTN-FRBA & EPIData)
        </div>
        <div class="footer-badges">
          <span class="badge badge-primary">100% IA Local (SLM)</span>
          <span class="badge badge-cyan">Protocolo MCP</span>
          <span class="badge badge-emerald">MySQL + ChromaDB</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.app-wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.navbar {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: rgba(7, 10, 19, 0.85);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.nav-content {
  max-width: 1440px;
  margin: 0 auto;
  padding: 0.65rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
}

.brand-group {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  cursor: pointer;
}

.logo-box {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(6, 182, 212, 0.25));
  border: 1px solid rgba(99, 102, 241, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
}

.brand-title {
  font-size: 1.15rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  background: linear-gradient(120deg, #ffffff, #c7d2fe);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.version-tag {
  font-size: 0.65rem;
  font-weight: 700;
  padding: 1px 6px;
  background: rgba(6, 182, 212, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(6, 182, 212, 0.3);
  border-radius: var(--radius-full);
  -webkit-text-fill-color: initial;
}

.brand-sub {
  font-size: 0.68rem;
  color: var(--text-muted);
  font-weight: 500;
  letter-spacing: 0.02em;
}

.nav-menu {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(18, 28, 48, 0.5);
  padding: 4px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border);
}

.nav-pill {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.85rem;
  border-radius: 10px;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--text-secondary);
  transition: all 0.2s ease;
  white-space: nowrap;
}

.nav-pill:hover {
  color: var(--text-main);
  background: rgba(255, 255, 255, 0.05);
}

.nav-pill-active {
  color: #ffffff !important;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.4), rgba(79, 70, 229, 0.3)) !important;
  border: 1px solid rgba(99, 102, 241, 0.5);
  box-shadow: 0 2px 10px rgba(99, 102, 241, 0.25);
}

.system-status-chip {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.4rem 0.85rem;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.25);
  border-radius: var(--radius-md);
}

.status-pulse-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: var(--emerald);
  box-shadow: 0 0 10px var(--emerald);
  animation: pulse-ring 2s infinite;
}

@keyframes pulse-ring {
  0% { transform: scale(0.9); opacity: 0.7; }
  50% { transform: scale(1.2); opacity: 1; }
  100% { transform: scale(0.9); opacity: 0.7; }
}

.status-texts {
  display: flex;
  flex-direction: column;
}

.status-mode {
  font-size: 0.75rem;
  font-weight: 700;
  color: #34d399;
}

.status-engine {
  font-size: 0.65rem;
  color: var(--text-muted);
}

.page-container {
  flex: 1;
  max-width: 1440px;
  width: 100%;
  margin: 0 auto;
  padding: 1.75rem 1.5rem;
}

.footer {
  border-top: 1px solid var(--border);
  background: rgba(7, 10, 19, 0.95);
  padding: 1.25rem 1.5rem;
  font-size: 0.8rem;
  color: var(--text-muted);
}

.footer-inner {
  max-width: 1440px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.footer-badges {
  display: flex;
  gap: 0.5rem;
}

@media (max-width: 1080px) {
  .nav-menu {
    overflow-x: auto;
  }
}
</style>
