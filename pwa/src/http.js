import { API_BASE_URL } from './endpoints'
import { clearToken, getToken } from '../utils/storage'

async function buildRequest(path, options = {}) {
  const token = getToken()
  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  const { body, ...rest } = options
  const requestInit = {
    method: options.method || 'GET',
    headers,
    ...rest,
  }

  if (body !== undefined) {
    requestInit.body = typeof body === 'string' ? body : JSON.stringify(body)
  }

  return fetch(`${API_BASE_URL}${path}`, requestInit)
}

export async function http(path, options) {
  const response = await buildRequest(path, options)

  if (response.status === 401) {
    clearToken()
  }

  return response
}