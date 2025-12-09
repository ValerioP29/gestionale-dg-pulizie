import router from './router'
import { API_BASE_URL } from './endpoints'
import { clearToken, getToken } from './utils/storage'

async function request(path, options = {}) {
  const token = getToken()
  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: options.method || 'GET',
    headers,
    ...options,
  })

  if (response.status === 401) {
    clearToken()
    if (router.currentRoute.value.path !== '/login') {
      router.replace('/login')
    }
  }

  return response
}

export function apiGet(path) {
  return request(path, { method: 'GET' })
}

export function apiPost(path, body) {
  return request(path, { method: 'POST', body: JSON.stringify(body) })
}
