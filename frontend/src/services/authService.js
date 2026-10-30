import api from './api'

/**
 * Login — kirim email & password ke backend, terima token & data user.
 */
export async function login(email, password) {
  const response = await api.post('/login', { email, password })
  return response.data
}

/**
 * Logout — hapus token aktif di server.
 */
export async function logout() {
  const response = await api.post('/logout')
  return response.data
}

/**
 * Get User — ambil profil user yang sedang login.
 */
export async function getUser() {
  const response = await api.get('/user')
  return response.data
}

/**
 * Update Profile — perbarui profil & foto user yang sedang login.
 */
export async function updateProfile(formData) {
  const response = await api.post('/user/profile', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  })
  return response.data
}

/**
 * Update Password — ubah password user yang sedang login.
 */
export async function updatePassword(data) {
  const response = await api.put('/user/password', data)
  return response.data
}
