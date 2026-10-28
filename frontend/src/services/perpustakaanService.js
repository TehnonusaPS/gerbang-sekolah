import api from './api'

export async function getPerpustakaans(params) {
  const response = await api.get('/perpustakaans', { params })
  return response.data
}

export async function createPerpustakaan(data) {
  if (data instanceof FormData) {
    const response = await api.post('/perpustakaans', data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }
  const response = await api.post('/perpustakaans', data)
  return response.data
}

export async function getPerpustakaan(id) {
  const response = await api.get(`/perpustakaans/${id}`)
  return response.data
}

export async function updatePerpustakaan(id, data) {
  if (data instanceof FormData) {
    data.append('_method', 'PUT')
    const response = await api.post(`/perpustakaans/${id}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }
  const response = await api.put(`/perpustakaans/${id}`, data)
  return response.data
}

export async function deletePerpustakaan(id) {
  const response = await api.delete(`/perpustakaans/${id}`)
  return response.data
}
