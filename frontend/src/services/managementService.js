import api from './api'

// ─────────────────────────────────────────────────────────────────────────
//  Yayasan (Foundations) API
// ─────────────────────────────────────────────────────────────────────────

export async function getFoundations(params) {
  const response = await api.get('/management/foundations', { params })
  return response.data
}

export async function createFoundation(data) {
  const response = await api.post('/management/foundations', data)
  return response.data
}

export async function getFoundation(id) {
  const response = await api.get(`/management/foundations/${id}`)
  return response.data
}

export async function updateFoundation(id, data) {
  if (data instanceof FormData) {
    data.append('_method', 'PUT')
    const response = await api.post(`/management/foundations/${id}`, data)
    return response.data
  }
  const response = await api.put(`/management/foundations/${id}`, data)
  return response.data
}

export async function deleteFoundation(id) {
  const response = await api.delete(`/management/foundations/${id}`)
  return response.data
}

// ─────────────────────────────────────────────────────────────────────────
//  Sekolah (Schools) API
// ─────────────────────────────────────────────────────────────────────────

export async function getSchools(params) {
  const response = await api.get('/management/schools', { params })
  return response.data
}

export async function createSchool(data) {
  const response = await api.post('/management/schools', data)
  return response.data
}

export async function getSchool(id) {
  const response = await api.get(`/management/schools/${id}`)
  return response.data
}

export async function updateSchool(id, data) {
  if (data instanceof FormData) {
    data.append('_method', 'PUT')
    const response = await api.post(`/management/schools/${id}`, data)
    return response.data
  }
  const response = await api.put(`/management/schools/${id}`, data)
  return response.data
}

export async function deleteSchool(id) {
  const response = await api.delete(`/management/schools/${id}`)
  return response.data
}

// ─────────────────────────────────────────────────────────────────────────
//  Pengguna (Users) API
// ─────────────────────────────────────────────────────────────────────────

export async function getUsers(params) {
  const response = await api.get('/management/users', { params })
  return response.data
}

export async function createUser(data) {
  const response = await api.post('/management/users', data)
  return response.data
}

export async function getUser(id) {
  const response = await api.get(`/management/users/${id}`)
  return response.data
}

export async function updateUser(id, data) {
  if (data instanceof FormData) {
    data.append('_method', 'PUT')
    const response = await api.post(`/management/users/${id}`, data)
    return response.data
  }
  const response = await api.put(`/management/users/${id}`, data)
  return response.data
}

export async function deleteUser(id) {
  const response = await api.delete(`/management/users/${id}`)
  return response.data
}

export async function getRoles() {
  const response = await api.get('/management/roles')
  return response.data
}

export async function getClassrooms(params) {
  const response = await api.get('/management/classrooms', { params })
  return response.data
}

export async function getClassroom(id) {
  const response = await api.get(`/management/classrooms/${id}`)
  return response.data
}

export async function createClassroom(data) {
  const response = await api.post('/management/classrooms', data)
  return response.data
}

export async function updateClassroom(id, data) {
  const response = await api.put(`/management/classrooms/${id}`, data)
  return response.data
}

export async function deleteClassroom(id) {
  const response = await api.delete(`/management/classrooms/${id}`)
  return response.data
}

export async function assignClassroomStudents(classroomId, studentIds) {
  const response = await api.post(`/management/classrooms/${classroomId}/assign-students`, {
    student_ids: studentIds
  })
  return response.data
}

// ─────────────────────────────────────────────────────────────────────────
//  Guru & Staff (Teachers & Staff) API
// ─────────────────────────────────────────────────────────────────────────

export async function getTeachers(params) {
  const response = await api.get('/management/teachers', { params })
  return response.data
}

export async function createTeacher(data) {
  const response = await api.post('/management/teachers', data)
  return response.data
}

export async function getTeacher(id) {
  const response = await api.get(`/management/teachers/${id}`)
  return response.data
}

export async function updateTeacher(id, data) {
  if (data instanceof FormData) {
    data.append('_method', 'PUT')
    const response = await api.post(`/management/teachers/${id}`, data)
    return response.data
  }
  const response = await api.put(`/management/teachers/${id}`, data)
  return response.data
}

export async function deleteTeacher(id) {
  const response = await api.delete(`/management/teachers/${id}`)
  return response.data
}

