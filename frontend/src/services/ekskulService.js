import api from './api'

export async function fetchAllEkskul(params) {
  const response = await api.get('/management/extracurriculars', { params })
  return response.data
}

export async function createEkskul(data) {
  const response = await api.post('/management/extracurriculars', data)
  return response.data
}

export async function getEkskul(id) {
  const response = await api.get(`/management/extracurriculars/${id}`)
  return response.data
}

export async function updateEkskul(id, data) {
  const response = await api.put(`/management/extracurriculars/${id}`, data)
  return response.data
}

export async function deleteEkskul(id) {
  const response = await api.delete(`/management/extracurriculars/${id}`)
  return response.data
}

export async function toggleEkskulStatus(id, newStatus) {
  const response = await api.put(`/management/extracurriculars/${id}`, { status: newStatus })
  return response.data
}
