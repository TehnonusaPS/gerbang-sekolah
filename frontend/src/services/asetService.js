import api from './api'

export async function getAsets(params) {
  const response = await api.get('/asets', { params })
  return response.data
}

export async function createAset(data) {
  if (data instanceof FormData) {
    const response = await api.post('/asets', data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }
  const response = await api.post('/asets', data)
  return response.data
}

export async function getAset(id) {
  const response = await api.get(`/asets/${id}`)
  return response.data
}

export async function updateAset(id, data) {
  if (data instanceof FormData) {
    data.append('_method', 'PUT')
    const response = await api.post(`/asets/${id}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }
  const response = await api.put(`/asets/${id}`, data)
  return response.data
}

export async function deleteAset(id) {
  const response = await api.delete(`/asets/${id}`)
  return response.data
}
