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

export async function getEkskulStudents(id) {
  const response = await api.get(`/management/extracurriculars/${id}/students`)
  return response.data
}

export async function addEkskulStudents(id, studentIds) {
  const response = await api.post(`/management/extracurriculars/${id}/students`, {
    student_ids: studentIds,
  })
  return response.data
}

export async function removeEkskulStudent(id, studentId) {
  const response = await api.post(`/management/extracurriculars/${id}/remove-student`, {
    student_id: studentId,
  })
  return response.data
}

export async function getAvailableEkskulStudents(id, params) {
  const response = await api.get(`/management/extracurriculars/${id}/available-students`, { params })
  return response.data
}

