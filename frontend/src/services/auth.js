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
    logout,
    isAuthenticated,
    hasRole
  }
}
