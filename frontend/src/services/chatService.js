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
  const response = await api.get('/chat/unread-count', { __skipGlobalLoading: true })
  return response.data.unread_count
}

/**
 * Fetch chat message thread history with a specific recipient.
 */
export async function getMessages(recipientId) {
  const response = await api.get(`/chat/messages/${recipientId}`)
  return response.data.messages
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
