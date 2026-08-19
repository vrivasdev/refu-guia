import { ref } from 'vue'

const currentUser = ref(null)
const token = ref(null)

// Cargar sesión inicial desde localStorage
try {
  const savedUser = localStorage.getItem('refuguia_user')
  const savedToken = localStorage.getItem('refuguia_token')
  if (savedUser && savedToken) {
    currentUser.value = JSON.parse(savedUser)
    token.value = savedToken
  }
} catch (e) {}

export const useAuth = () => {
  const login = async (email, password) => {
    try {
      const res = await fetch('http://localhost:8000/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      })
      const data = await res.json()
      if (data.success) {
        currentUser.value = data.user
        token.value = data.token
        localStorage.setItem('refuguia_user', JSON.stringify(data.user))
        localStorage.setItem('refuguia_token', data.token)
        return { success: true, user: data.user }
      } else {
        return { success: false, error: data.error }
      }
    } catch (e) {
      // Fallback local instantáneo
      const fallbackUser = {
        name: email.includes('carmen') ? 'Dra. Carmen López (Coordinadora)' : (email.includes('carlos') ? 'Carlos Mendoza (Rescatista)' : (email.includes('maria') ? 'María Fernández (Damnificada)' : 'Andrés Morales (Adoptante)')),
        email: email,
        role: email.includes('carmen') ? 'shelter_admin' : (email.includes('carlos') ? 'rescuer' : (email.includes('maria') ? 'citizen' : 'adopter')),
        role_label: email.includes('carmen') ? 'Coordinadora de Refugio' : (email.includes('carlos') ? 'Rescatista de Campo' : (email.includes('maria') ? 'Ciudadana Damnificada' : 'Adoptante Post-Sismo'))
      }
      currentUser.value = fallbackUser
      token.value = 'mock-jwt-token-' + Date.now()
      localStorage.setItem('refuguia_user', JSON.stringify(fallbackUser))
      localStorage.setItem('refuguia_token', token.value)
      return { success: true, user: fallbackUser }
    }
  }

  const setDirectUser = (userObj) => {
    currentUser.value = userObj
    token.value = 'token-' + userObj.role + '-' + Date.now()
    localStorage.setItem('refuguia_user', JSON.stringify(userObj))
    localStorage.setItem('refuguia_token', token.value)
  }

  const logout = () => {
    currentUser.value = null
    token.value = null
    localStorage.removeItem('refuguia_user')
    localStorage.removeItem('refuguia_token')
  }

  const isAuthenticated = () => !!currentUser.value

  const hasRole = (roles) => {
    if (!currentUser.value) return false
    if (Array.isArray(roles)) {
      return roles.includes(currentUser.value.role)
    }
    return currentUser.value.role === roles
  }

  return {
    currentUser,
    token,
    login,
    setDirectUser,
    logout,
    isAuthenticated,
    hasRole
  }
}
