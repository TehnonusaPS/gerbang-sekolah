import api from './api'

/**
 * Fetch list of contacts filtered by school/foundation isolation.
 */
export async function getContacts() {
  const response = await api.get('/chat/contacts')
  return response.data.contacts
}

/**
 * Fetch total unread chats count.
 */
export async function getUnreadCount() {
  const response = await api.get('/chat/unread-count')
  return response.data.unread_count
}

/**
 * Fetch chat message thread history with a specific recipient.
 */
export async function getMessages(recipientId, params = {}) {
  const response = await api.get(`/chat/messages/${recipientId}`, { params })
  return response.data
}

/**
 * Send a new text message.
 */
export async function sendMessage(recipientId, message) {
  const response = await api.post('/chat/messages', {
    recipient_id: recipientId,
    message: message
  })
  return response.data.data
}

/**
 * Mark a message as read.
 */
export async function markAsRead(messageId) {
  const response = await api.post(`/chat/messages/${messageId}/read`)
  return response.data
}

/**
 * Mark messages from a sender as delivered to the logged-in recipient.
 */
export async function markAsDelivered(senderId) {
  const response = await api.post('/chat/delivered', { sender_id: senderId })
  return response.data
}
