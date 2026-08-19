<template>
  <div v-if="isOpen" class="modal-overlay">
    <div class="modal-card glass-card">
      <div class="modal-header">
        <div class="modal-title-group">
          <div class="modal-icon">🔐</div>
          <div>
            <h3>Iniciar Sesión en RefuGuía</h3>
            <p class="modal-sub">Acceso con Control de Roles (RBAC)</p>
          </div>
        </div>
        <!-- ONLY BUTTON THAT CLOSES THE MODAL -->
        <button class="btn-close-modal" @click="close" title="Cerrar ventana">✕</button>
      </div>

      <!-- SUCCESS NOTICE IF LOGGED IN -->
      <div v-if="successMsg" class="success-banner-box">
        <div class="success-banner-title">🎉 ¡Autenticación Exitosa!</div>
        <p class="success-banner-desc">{{ successMsg }}</p>
        <p class="success-banner-action">Haz clic en la <strong>X</strong> de la esquina superior derecha para continuar a la plataforma.</p>
      </div>

      <!-- DEMO 1-CLICK QUICK LOGINS -->
      <div class="demo-logins-box">
        <div class="demo-title">⚡ Acceso Rápido de Prueba (1 Clic por Rol):</div>
        <div class="demo-grid">
          <button class="btn-demo-role admin" @click="quickLogin('carmen.refugio@refuguia.org')">
            <span class="role-icon">🏥</span>
            <div class="role-desc">
              <strong>Admin de Refugio</strong>
              <span>Dra. Carmen López</span>
            </div>
          </button>

          <button class="btn-demo-role rescuer" @click="quickLogin('carlos.rescate@refuguia.org')">
            <span class="role-icon">🚒</span>
            <div class="role-desc">
              <strong>Rescatista de Campo</strong>
              <span>Carlos Mendoza</span>
            </div>
          </button>

          <button class="btn-demo-role citizen" @click="quickLogin('maria.f@gmail.com')">
            <span class="role-icon">🙋‍♀️</span>
            <div class="role-desc">
              <strong>Ciudadana Damnificada</strong>
              <span>María Fernández</span>
            </div>
          </button>

          <button class="btn-demo-role adopter" @click="quickLogin('andres.m@gmail.com')">
            <span class="role-icon">❤️</span>
            <div class="role-desc">
              <strong>Adoptante</strong>
              <span>Andrés Morales</span>
            </div>
          </button>
        </div>
      </div>

      <div class="divider"><span>O ingresa manualmente</span></div>

      <!-- LOGIN FORM -->
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label>Correo Electrónico:</label>
          <input type="email" v-model="email" required class="input-dark" placeholder="tu.correo@ejemplo.com" />
        </div>

        <div class="form-group">
          <label>Contraseña:</label>
          <input type="password" v-model="password" required class="input-dark" placeholder="••••••••" />
        </div>

        <p v-if="errorMsg" class="err-text">❌ {{ errorMsg }}</p>

        <button type="submit" class="btn-gradient btn-submit" :disabled="loading">
          {{ loading ? 'Autenticando...' : 'Iniciar Sesión' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuth } from '../services/auth'

const props = defineProps({
  isOpen: Boolean
})
const emit = defineEmits(['close'])

const { login } = useAuth()

const email = ref('carmen.refugio@refuguia.org')
const password = ref('Password123!')
const loading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

const close = () => {
  errorMsg.value = ''
  successMsg.value = ''
  emit('close')
}

const quickLogin = (presetEmail) => {
  email.value = presetEmail
  password.value = 'Password123!'
  handleLogin()
}

const handleLogin = async () => {
  loading.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    const res = await login(email.value, password.value)
    if (res.success) {
      successMsg.value = `Sesión iniciada como ${res.user.name} (${res.user.role_label}).`
    } else {
      errorMsg.value = res.error || 'Credenciales inválidas.'
    }
  } catch (e) {
    errorMsg.value = 'Error al conectar con el servidor de autenticación.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.82);
  backdrop-filter: blur(10px);
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.modal-card {
  width: 100%;
  max-width: 530px;
  background: #0d1322;
  border: 1px solid rgba(99, 102, 241, 0.45);
  padding: 1.85rem;
  border-radius: var(--radius-lg);
  box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.9);
  position: relative;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.25rem;
}

.modal-title-group {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.modal-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: rgba(99, 102, 241, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.modal-title-group h3 {
  font-size: 1.2rem;
  font-weight: 800;
  color: #fff;
}

.modal-sub {
  font-size: 0.76rem;
  color: var(--text-muted);
}

.btn-close-modal {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-close-modal:hover {
  background: rgba(244, 63, 94, 0.4);
  border-color: #fb7185;
  transform: scale(1.1);
}

.success-banner-box {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: var(--radius-md);
  padding: 1rem;
  margin-bottom: 1.25rem;
  text-align: center;
}

.success-banner-title {
  color: #34d399;
  font-weight: 800;
  font-size: 0.95rem;
  margin-bottom: 0.25rem;
}

.success-banner-desc {
  color: #e2e8f0;
  font-size: 0.82rem;
  margin-bottom: 0.4rem;
}

.success-banner-action {
  font-size: 0.76rem;
  color: #a7f3d0;
}

.demo-logins-box {
  background: rgba(7, 10, 19, 0.7);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 1.1rem;
  margin-bottom: 1.25rem;
}

.demo-title {
  font-size: 0.76rem;
  font-weight: 700;
  color: #a5b4fc;
  margin-bottom: 0.75rem;
}

.demo-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.65rem;
}

.btn-demo-role {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 0.85rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-main);
  text-align: left;
  transition: all 0.2s ease;
  cursor: pointer;
}

.btn-demo-role:hover {
  background: rgba(99, 102, 241, 0.2);
  border-color: #6366f1;
  transform: translateY(-2px);
}

.role-icon {
  font-size: 1.35rem;
}

.role-desc strong {
  display: block;
  font-size: 0.78rem;
}

.role-desc span {
  font-size: 0.68rem;
  color: var(--text-muted);
}

.divider {
  text-align: center;
  position: relative;
  margin: 1.25rem 0;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--border);
}

.divider span {
  position: relative;
  background: #0d1322;
  padding: 0 10px;
  font-size: 0.72rem;
  color: var(--text-muted);
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.form-group label {
  display: block;
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-bottom: 0.3rem;
}

.btn-submit {
  width: 100%;
  padding: 0.75rem;
  margin-top: 0.5rem;
  font-size: 0.9rem;
}

.err-text { color: #fb7185; font-size: 0.8rem; }
</style>
