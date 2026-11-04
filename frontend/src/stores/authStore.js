import { defineStore } from 'pinia'
import * as authService from '@/services/authService'
import { connectEcho, disconnectEcho, echo } from '@/services/echoService'
import { getUnreadCount } from '@/services/chatService'
import { getNotifications } from '@/services/notificationService'

// Auto-connect on page refresh if token is present
const initialToken = localStorage.getItem('token')
if (initialToken) {
  connectEcho(initialToken)
}

// Module-level callback registry (non-reactive, avoids Pinia overhead)
// ChatContainer registers its handler here via onIncomingMessage()
const _messageCallbacks = []
const _notificationCallbacks = []

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: initialToken || null,
    isJustLoggedIn: false,
    isLoggingOut: false,
    unreadCount: 0,
    unreadNotificationsCount: 0,
    activeChatRecipientId: null,
    echoChannel: null,
    notificationChannel: null
  }),

  getters: {
    isWaliKelas: (state) => Boolean(state.user?.is_wali_kelas),
    userRole: (state) => state.user?.role,
    homeroomClassroom: (state) => state.user?.homeroom_classroom || null
  },

  actions: {
    /**
     * Inisialisasi: refresh profil user dari server agar role & data terbaru termuat.
     */
    async initUser() {
      if (!this.token) return
      try {
        const response = await authService.getUser()
        if (response.user) {
          this.user = response.user
        }
      } catch (error) {
        console.error('Gagal memuat ulang data user:', error)
      }
    },
    /**
     * Fetch total unread chats count from the server.
     */
    async fetchUnreadCount() {
      if (!this.token) return
      try {
        const count = await getUnreadCount()
        this.unreadCount = count
      } catch (error) {
        console.error('Gagal mengambil unread count:', error)
      }
    },

    /**
     * Register a callback to receive real-time incoming messages.
     * Returns an unsubscribe function — call it on component unmount.
     */
    onIncomingMessage(callback) {
      _messageCallbacks.push(callback)
      // Return unsubscribe function
      return () => {
        const idx = _messageCallbacks.indexOf(callback)
        if (idx > -1) _messageCallbacks.splice(idx, 1)
      }
    },

    /**
     * Setup a single, permanent global WebSocket listener for incoming messages.
     * This is the ONLY place where the chat channel is subscribed.
     * It handles the navbar badge AND relays events to registered callbacks.
     */
    setupGlobalChatListener() {
      const currentUserId = this.user?.id
      if (!currentUserId || this.echoChannel) return

      if (!echo) {
        console.warn('Echo belum terinisiasi untuk listener global.')
        return
      }

      this.echoChannel = echo.private(`chat.${currentUserId}`)
        .listen('MessageSent', (event) => {
          // 1. Increment navbar badge only if sender is NOT the active chat
          if (this.activeChatRecipientId !== event.sender_id) {
            this.unreadCount++
          }

          // 2. Relay event to all registered callbacks (e.g. ChatContainer)
          _messageCallbacks.forEach(cb => cb({ type: 'message', data: event }))
        })
        .listen('MessageRead', (event) => {
          // Relay MessageRead event to registered callbacks (to update checkmarks to read)
          _messageCallbacks.forEach(cb => cb({ type: 'read', data: event }))
        })
    },

    /**
     * Fetch total unread notifications count from the server.
     */
    async fetchUnreadNotificationsCount() {
      if (!this.token) return
      try {
        const data = await getNotifications()
        this.unreadNotificationsCount = data.unread_count
      } catch (error) {
        console.error('Gagal mengambil unread notifications count:', error)
      }
    },

    /**
     * Register a callback to receive real-time incoming notifications.
     */
    onIncomingNotification(callback) {
      _notificationCallbacks.push(callback)
      return () => {
        const idx = _notificationCallbacks.indexOf(callback)
        if (idx > -1) _notificationCallbacks.splice(idx, 1)
      }
    },

    /**
     * Setup a single global WebSocket listener for notifications.
     */
    setupGlobalNotificationListener() {
      const currentUserId = this.user?.id
      if (!currentUserId || this.notificationChannel) return

      if (!echo) {
        console.warn('Echo belum terinisiasi untuk listener notifikasi.')
        return
      }

      this.notificationChannel = echo.private(`notification.${currentUserId}`)
        .listen('NotificationSent', (event) => {
          this.unreadNotificationsCount++

          // Show popup toast
          import('vue-sonner').then(({ toast }) => {
            toast.info(event.title, {
              description: event.content,
              duration: 5000,
            })
          })

          // Relay to registered callbacks (like the Notifications list page)
          _notificationCallbacks.forEach(cb => cb(event))
        })
    },

    /**
     * Login melalui API backend Laravel Sanctum.
     * Mengirim email & password, menerima token + data user.
     */
    async login(email, password) {
      try {
        const data = await authService.login(email, password)

        // Simpan token dan user ke state & localStorage
        this.token = data.access_token
        this.user = data.user
        this.isJustLoggedIn = true

        localStorage.setItem('token', data.access_token)

        // Connect WebSocket Echo
        connectEcho(data.access_token)

        // Fetch unread counts & setup global listeners
        await this.fetchUnreadCount()
        await this.fetchUnreadNotificationsCount()
        this.setupGlobalChatListener()
        this.setupGlobalNotificationListener()

        return data.user
      } catch (error) {
        // Jika backend mengembalikan error (401, 403, 422), lempar pesan error
        if (error.response) {
          throw new Error(error.response.data.message || 'Login gagal.')
        }
        throw new Error('Tidak dapat terhubung ke server.')
      }
    },

    /**
     * Logout — hapus token di server dan bersihkan data lokal.
     */
    async logout() {
      try {
        if (this.token) {
          await authService.logout()
        }
      } catch (error) {
        // Abaikan error saat logout (misal token sudah expired)
      } finally {
        // Leave dynamic private channel
        if (this.echoChannel && echo) {
          const currentUserId = this.user?.id
          if (currentUserId) {
            echo.leaveChannel(`chat.${currentUserId}`)
          }
        }
        if (this.notificationChannel && echo) {
          const currentUserId = this.user?.id
          if (currentUserId) {
            echo.leaveChannel(`notification.${currentUserId}`)
          }
        }

        // Disconnect WebSocket Echo
        disconnectEcho()

        this.user = null
        this.token = null
        this.unreadCount = 0
        this.unreadNotificationsCount = 0
        this.activeChatRecipientId = null
        this.echoChannel = null
        this.notificationChannel = null
        // Clear all callbacks
        _messageCallbacks.length = 0
        _notificationCallbacks.length = 0
        localStorage.removeItem('token')
      }
    }
  }
})
