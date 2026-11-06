import api from './api'

/**
 * Fetch real-time dashboard data for Admin Yayasan from backend.
 */
export async function getYayasanDashboard() {
  const response = await api.get('/yayasan/dashboard')
  return response.data.data
}

