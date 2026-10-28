import api from './api'

export async function getRuangans(params) {
  const response = await api.get('/ruangans', { params })
  return response.data
}

export async function createRuangan(data) {
  if (data instanceof FormData) {
    const response = await api.post('/ruangans', data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }
  const response = await api.post('/ruangans', data)
  return response.data
}

export async function getRuangan(id) {
  const response = await api.get(`/ruangans/${id}`)
  return response.data
}

export async function updateRuangan(id, data) {
  if (data instanceof FormData) {
    data.append('_method', 'PUT')
    const response = await api.post(`/ruangans/${id}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }
  const response = await api.put(`/ruangans/${id}`, data)
  return response.data
}

export async function deleteRuangan(id) {
  const response = await api.delete(`/ruangans/${id}`)
  return response.data
}
