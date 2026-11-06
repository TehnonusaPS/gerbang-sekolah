import api from './api'

/**
 * Fetch real-time dashboard data for Superadmin from backend.
 * @param {Object} params - Query params (e.g., { year: 2026 })
 */
export async function getSuperadminDashboard(params = {}) {
  const response = await api.get('/superadmin/dashboard', { params })
  return response.data.data
}

/**
 * Fetch growth chart data only for a specific year (lightweight query).
 * @param {number|string} year - Target year (e.g., 2026, 2025)
 */
export async function getSuperadminGrowthChart(year) {
  const response = await api.get('/superadmin/dashboard', {
    params: { year, chart_only: true }
  })
  return response.data.data
}

