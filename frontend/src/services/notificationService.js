import api from './api'

/**
 * Fetch all notifications for the authenticated user.
 */
export async function getNotifications() {
  const response = await api.get('/notifications', { __skipGlobalLoading: true })
  return response.data.data
}

/**
 * Mark a single notification as read.
 */
export async function markAsRead(id) {
  const response = await api.post(`/notifications/${id}/read`)
  return response.data.data
}

/**
 * Mark all notifications as read.
 */
export async function markAllAsRead() {
  const response = await api.post('/notifications/read-all')
  return response.data.data
}
