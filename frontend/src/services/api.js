import axios from 'axios'
import { trackRequestStart, trackRequestEnd } from '@/composables/useApiLoading'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json'
  }
})


// Bungkus method Axios agar trackRequestStart langsung berjalan secara sinkron
// tepat saat api.get / api.post dipanggil di onMounted (tanpa menunggu microtask interceptor Axios)
for (const method of ['get', 'delete', 'head', 'options']) {
  const original = api[method].bind(api)
  api[method] = function (url, config = {}) {
    const nextConfig = { ...config }
    if (!nextConfig.__skipGlobalLoading && !nextConfig.__trackedMethod) {
      nextConfig.__trackedMethod = method
      trackRequestStart(method)
    }
    return original(url, nextConfig)
  }
}

for (const method of ['post', 'put', 'patch']) {
  const original = api[method].bind(api)
  api[method] = function (url, data, config = {}) {
    const nextConfig = { ...config }
    if (!nextConfig.__skipGlobalLoading && !nextConfig.__trackedMethod) {
      nextConfig.__trackedMethod = method
      trackRequestStart(method)
    }
    return original(url, data, nextConfig)
  }
}

// Request interceptor — sisipkan Bearer token secara otomatis jika ada
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  
  // Allow browser to auto-detect boundary for FormData requests by deleting the default Content-Type
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type']
  }

  if (!config.__skipGlobalLoading && !config.__trackedMethod) {
    config.__trackedMethod = config.method || 'get'
    trackRequestStart(config.__trackedMethod)
  }
  
  return config
})

// Response interceptor — tangani 401 Unauthorized secaraß global
api.interceptors.response.use(
  response => {
    if (response?.config?.__trackedMethod) {
      trackRequestEnd(response.config.__trackedMethod)
    }
    return response
  },
  error => {
    if (error?.config?.__trackedMethod) {
      trackRequestEnd(error.config.__trackedMethod)
    }
    if (error.response && error.response.status === 401) {
      // Token expired atau tidak valid, bersihkan data auth
      localStorage.removeItem('token')
      localStorage.removeItem('user')

      // Redirect ke login jika belum di halaman login
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default api
