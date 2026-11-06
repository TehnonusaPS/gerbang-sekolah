<script setup>
import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { Check, CheckCheck, Search, ArrowLeft, User, Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import { useAuthStore } from '@/stores/authStore'
import { getContacts, getMessages, sendMessage, markAsRead } from '@/services/chatService'

function getPhotoUrl(photo) {
  if (!photo) return null
  if (photo.startsWith('http://') || photo.startsWith('https://')) return photo
  return `http://127.0.0.1:8000/${photo.startsWith('/') ? photo.slice(1) : photo}`
}

const props = defineProps({
  role: {
    type: String,
    required: true
  }
})

const auth = useAuthStore()

const currentRole = computed(() => {
  const r = props.role || 'guru'
  if (r === 'wali_kelas') return 'guru'
  if (r === 'orang_tua') return 'siswa'
  return r
})

const isStudent = computed(() => currentRole.value === 'siswa')

const filters = [
  { label: 'Semua', value: 'all' },
  { label: 'Orang Tua', value: 'orang_tua' },
  { label: 'Siswa', value: 'siswa' }
]

const activeFilter = ref('all')
const searchQuery = ref('')
const newMessage = ref('')
const activeConversationId = ref(null)
const conversations = ref([])
const activeConversationMessages = ref([])

const isLoadingContacts = ref(false)
const isLoadingMessages = ref(false)
const isLoadingOlder = ref(false)
const hasMoreMessages = ref(false)
const nextCursor = ref(null)

// Mobile responsiveness detail panel status
const isChatDetailOpen = ref(false)

// Ref to the scrollable messages container
const messagesContainer = ref(null)

// Scroll to bottom of messages list
function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

const filterOptions = computed(() => (isStudent.value ? [] : filters))

const filteredConversations = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  return conversations.value.filter(convo => {
    const matchesRole =
      isStudent.value || activeFilter.value === 'all' || convo.role === activeFilter.value
    const matchesQuery =
      !query ||
      convo.name.toLowerCase().includes(query) ||
      convo.preview.toLowerCase().includes(query) ||
      convo.roleLabel.toLowerCase().includes(query)

    return matchesRole && matchesQuery
  })
})

const activeConversation = computed(() => {
  return (
    conversations.value.find(convo => convo.id === activeConversationId.value) ||
    null
  )
})

const panelTitle = computed(() => (isStudent.value ? 'Komunikasi dengan Guru' : 'Komunikasi'))
const panelDescription = computed(() =>
  isStudent.value
    ? 'Lihat dan balas pesan dari guru secara rapi dan cepat.'
    : 'Kelola percakapan dengan guru dan siswa dari satu tempat.'
)

function formatTime(isoString) {
  if (!isoString) return ''
  try {
    const date = new Date(isoString)
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })
  } catch (e) {
    return ''
  }
}

// Fetch contacts
async function loadContacts(selectFirst = true) {
  isLoadingContacts.value = true
  try {
    const list = await getContacts()
    conversations.value = list.map(c => ({
      ...c,
      time: formatTime(c.last_message_at) || c.time
    }))
    
    if (selectFirst && conversations.value.length && !activeConversationId.value) {
      activeConversationId.value = conversations.value[0].id
    }
  } catch (error) {
    console.error('Gagal mengambil kontak:', error)
  } finally {
    isLoadingContacts.value = false
  }
}

// Fetch thread (Initial load: latest 20 messages)
async function loadMessages(recipientId) {
  if (!recipientId) return
  isLoadingMessages.value = true
  hasMoreMessages.value = false
  nextCursor.value = null
  try {
    const data = await getMessages(recipientId, { limit: 20 })
    const msgs = data.messages || []
    activeConversationMessages.value = msgs.map(m => ({
      ...m,
      time: formatTime(m.created_at) || m.time
    }))
    hasMoreMessages.value = Boolean(data.has_more)
    nextCursor.value = data.next_cursor || null
    
    // Clear unread count locally for this contact
    const convo = conversations.value.find(c => c.id === recipientId)
    if (convo) convo.unread = 0

    // Sync navbar unread count badge from server (authoritative)
    auth.fetchUnreadCount()

    // Auto-scroll to latest message
    scrollToBottom()
  } catch (error) {
    console.error('Gagal mengambil pesan:', error)
  } finally {
    isLoadingMessages.value = false
  }
}

// Load older messages on scroll-up (Cursor pagination)
async function loadOlderMessages() {
  if (!activeConversationId.value || isLoadingOlder.value || !hasMoreMessages.value || !nextCursor.value) return

  const el = messagesContainer.value
  if (!el) return

  isLoadingOlder.value = true
  const prevScrollHeight = el.scrollHeight
  const prevScrollTop = el.scrollTop

  try {
    const data = await getMessages(activeConversationId.value, {
      limit: 20,
      before_id: nextCursor.value
    })

    const olderMsgs = (data.messages || []).map(m => ({
      ...m,
      time: formatTime(m.created_at) || m.time
    }))

    // Prepend older messages to top of active list
    activeConversationMessages.value = [...olderMsgs, ...activeConversationMessages.value]
    hasMoreMessages.value = Boolean(data.has_more)
    nextCursor.value = data.next_cursor || null

    // Restore scroll position (Anti-Jumping)
    await nextTick()
    el.scrollTop = el.scrollHeight - prevScrollHeight + prevScrollTop
  } catch (error) {
    console.error('Gagal memuat pesan terdahulu:', error)
  } finally {
    isLoadingOlder.value = false
  }
}

function handleScroll() {
  if (!messagesContainer.value || isLoadingOlder.value || !hasMoreMessages.value) return
  if (messagesContainer.value.scrollTop <= 60) {
    loadOlderMessages()
  }
}

function selectConversation(convo) {
  activeConversationId.value = convo.id
  isChatDetailOpen.value = true
}

// Keep authStore in sync with which conversation is active
watch(activeConversationId, (newId) => {
  auth.activeChatRecipientId = newId
  if (newId) {
    loadMessages(newId)
  } else {
    activeConversationMessages.value = []
  }
}, { immediate: true })

/**
 * Handle incoming WebSocket message relayed by authStore.
 * authStore is the SINGLE WebSocket subscriber — ChatContainer registers
 * this handler via auth.onIncomingMessage() and unsubscribes on unmount.
 */
function handleIncomingMessage(wrapper) {
  const { type, data: event } = wrapper

  if (type === 'read') {
    // If the active conversation is with the reader, mark our sent messages to them as read (status = 'read')
    if (activeConversationId.value === event.reader_id) {
      activeConversationMessages.value.forEach(msg => {
        if (msg.from === 'me') {
          msg.status = 'read'
        }
      })
    }
    return
  }

  if (type === 'delivered') {
    // If the active conversation is with this recipient, mark our sent messages as delivered (status = 'delivered')
    if (activeConversationId.value === event.reader_id) {
      activeConversationMessages.value.forEach(msg => {
        if (msg.from === 'me' && msg.status === 'sent') {
          msg.status = 'delivered'
        }
      })
    }
    return
  }

  // Handle incoming 'message'
  const localTime = formatTime(event.created_at) || event.time
  const incomingMsg = {
    id: event.id,
    from: 'other',
    text: event.message,
    time: localTime,
    status: 'read',
    created_at: event.created_at
  }

  // If active conversation is currently showing this sender's thread
  if (activeConversationId.value === event.sender_id) {
    activeConversationMessages.value.push(incomingMsg)

    // Update contact preview
    const convo = conversations.value.find(c => c.id === event.sender_id)
    if (convo) {
      convo.preview = event.message
      convo.time = localTime
    }

    // Mark as read in DB (endpoint also broadcasts MessageRead → updates sender's checkmarks)
    markAsRead(event.id)

    // Sync navbar badge (decrement since we just read it)
    auth.fetchUnreadCount()

    // Auto-scroll to show the new incoming message
    scrollToBottom()
  } else {
    // Find sender in contacts list, increment unread and update preview
    const convo = conversations.value.find(c => c.id === event.sender_id)
    if (convo) {
      convo.unread++
      convo.preview = event.message
      convo.time = localTime

      // Re-sort contacts list since we received a new message (moves to top)
      conversations.value.sort((a, b) => {
        if (a.id === event.sender_id) return -1
        if (b.id === event.sender_id) return 1
        return 0
      })
    } else {
      // If not in contacts, reload contacts list without resetting selection
      loadContacts(false)
    }
  }
}

let unsubscribeMessage = null

onMounted(() => {
  loadContacts()
  // Register callback to receive real-time messages from authStore's single global listener
  unsubscribeMessage = auth.onIncomingMessage(handleIncomingMessage)
})

onUnmounted(() => {
  // Unsubscribe callback (does NOT touch the WebSocket channel)
  if (unsubscribeMessage) unsubscribeMessage()
  // Clear active conversation so global listener resumes badge increment for this sender
  auth.activeChatRecipientId = null
})

async function send() {
  if (!newMessage.value.trim() || !activeConversationId.value) return

  const text = newMessage.value.trim()
  const recipientId = activeConversationId.value

  // Optimistic UI update
  const tempId = Date.now()
  const tempMsg = {
    id: tempId,
    from: 'me',
    text: text,
    time: formatTime(new Date().toISOString()),
    status: 'sent',
    created_at: new Date().toISOString()
  }
  activeConversationMessages.value.push(tempMsg)

  // Auto-scroll to show the newly sent message immediately
  scrollToBottom()

  // Update contact preview and move to top
  const convo = conversations.value.find(c => c.id === recipientId)
  if (convo) {
    convo.preview = text
    convo.time = tempMsg.time
  }

  newMessage.value = ''

  try {
    const savedMsg = await sendMessage(recipientId, text)
    // Replace optimistic message with backend saved message
    const idx = activeConversationMessages.value.findIndex(m => m.id === tempId)
    if (idx !== -1) {
      activeConversationMessages.value[idx] = {
        ...savedMsg,
        time: formatTime(savedMsg.created_at) || savedMsg.time
      }
    }
  } catch (error) {
    console.error('Gagal mengirim pesan:', error)
    const idx = activeConversationMessages.value.findIndex(m => m.id === tempId)
    if (idx !== -1) {
      activeConversationMessages.value[idx].status = 'failed'
    }
  }
}
</script>

<template>
  <div class="flex flex-col gap-4 lg:h-[calc(100vh-11rem)] lg:flex-row">
    <!-- ASIDE: CONVERSATIONS LIST -->
    <aside 
      class="w-full overflow-hidden rounded-xl border border-border bg-card shadow-sm lg:w-92 lg:h-full flex flex-col"
      :class="[isChatDetailOpen ? 'hidden lg:flex' : 'flex flex-col']"
    >
      <div class="border-b border-border p-4 sm:p-5 shrink-0">
        <div>
          <h2 class="text-xl font-semibold tracking-tight text-foreground">{{ panelTitle }}</h2>
          <p class="text-sm text-muted-foreground">{{ panelDescription }}</p>
        </div>

        <div class="mt-4 relative">
          <Search
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
          />
          <Input v-model="searchQuery" placeholder="Cari percakapan ...." class="h-10 pl-9" />
        </div>

        <div v-if="filterOptions.length" class="mt-4 flex flex-wrap gap-2">
          <Button
            v-for="filter in filterOptions"
            :key="filter.value"
            type="button"
            size="sm"
            :variant="activeFilter === filter.value ? 'default' : 'outline'"
            class="rounded-full"
            @click="activeFilter = filter.value"
          >
            {{ filter.label }}
          </Button>
        </div>
      </div>

      <!-- LIST CONTAINER -->
      <div class="overflow-y-auto p-2 max-h-96 sm:max-h-112 lg:max-h-none lg:flex-1">
        <template v-if="isLoadingContacts">
          <div class="flex h-20 items-center justify-center text-sm text-muted-foreground">
            Memuat daftar kontak...
          </div>
        </template>
        
        <template v-else>
          <button
            v-for="convo in filteredConversations"
            :key="convo.id"
            type="button"
            class="flex w-full items-center gap-3 rounded-lg border border-transparent p-3 text-left transition-colors hover:border-border hover:bg-accent/60"
            :class="activeConversationId === convo.id ? 'border-border bg-accent/50' : ''"
            @click="selectConversation(convo)"
          >
            <Avatar class="h-11 w-11 shrink-0 ring-1 ring-border">
              <AvatarImage v-if="convo.photo" :src="getPhotoUrl(convo.photo)" :alt="convo.name" />
              <AvatarFallback class="bg-primary/10 text-primary">
                <User class="h-5 w-5 text-muted-foreground" />
              </AvatarFallback>
            </Avatar>

            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-2">
                <div class="truncate font-semibold text-foreground text-sm sm:text-base">{{ convo.name }}</div>
                <div class="flex items-center gap-1">
                  <div class="text-[10px] text-muted-foreground font-medium font-mono">{{ convo.time }}</div>
                  <span
                    v-if="convo.unread"
                    class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[9px] font-bold text-white"
                  >
                    {{ convo.unread }}
                  </span>
                </div>
              </div>

              <div class="mt-1.5 flex items-center gap-2 text-xs text-muted-foreground">
                <span class="rounded-md border border-border px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider bg-muted/40 text-foreground/85">
                  {{ convo.roleLabel }}
                </span>
                <span v-if="convo.preview" class="text-[11px] font-medium truncate flex-1 block max-w-[140px]">{{ convo.preview }}</span>
              </div>
            </div>
          </button>

          <div
            v-if="!filteredConversations.length"
            class="flex h-40 items-center justify-center rounded-lg border border-dashed border-border text-sm text-muted-foreground"
          >
            Tidak ada percakapan yang cocok.
          </div>
        </template>
      </div>
    </aside>

    <!-- SECTION: CHAT WINDOW -->
    <section
      class="flex min-h-112 flex-1 flex-col overflow-hidden rounded-xl border border-border bg-card shadow-sm lg:h-full"
      :class="[!isChatDetailOpen ? 'hidden lg:flex' : 'flex']"
    >
      <header class="flex items-center gap-3 border-b border-border p-4 sm:p-5">
        <!-- Back button on mobile -->
        <Button
          variant="ghost"
          size="icon"
          class="lg:hidden rounded-full mr-1 -ml-1 shrink-0"
          @click="isChatDetailOpen = false"
        >
          <ArrowLeft class="h-5 w-5" />
        </Button>
        
        <Avatar class="h-11 w-11 ring-1 ring-border">
          <AvatarImage v-if="activeConversation?.photo" :src="getPhotoUrl(activeConversation.photo)" :alt="activeConversation.name" />
          <AvatarFallback class="bg-primary/10 text-primary">
            <User class="h-5 w-5 text-muted-foreground" />
          </AvatarFallback>
        </Avatar>
        <div class="min-w-0 flex-1 text-left">
          <div class="truncate text-base font-semibold text-foreground">
            {{ activeConversation?.name || 'Pilih percakapan' }}
          </div>
          <div v-if="activeConversation" class="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
            <span class="rounded-md border border-border px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider bg-muted/40 text-foreground/85">
              {{ activeConversation.roleLabel }}
            </span>
            <span class="text-[11px] font-medium">{{ activeConversation.email }}</span>
          </div>
        </div>
      </header>

      <!-- MESSAGE BUBBLES LIST -->
      <div ref="messagesContainer" class="flex-1 overflow-auto p-4 sm:p-6" @scroll.passive="handleScroll">
        <template v-if="isLoadingMessages">
          <div class="flex h-full items-center justify-center text-sm text-muted-foreground">
            Memuat obrolan...
          </div>
        </template>

        <template v-else-if="activeConversationId">
          <div class="space-y-5">
            <!-- Indikator Loading Pesan Terdahulu -->
            <div
              v-if="isLoadingOlder"
              class="flex items-center justify-center gap-2 py-2 text-xs text-muted-foreground animate-pulse"
            >
              <Loader2 class="h-3.5 w-3.5 animate-spin text-primary" />
              <span>Memuat pesan terdahulu...</span>
            </div>

            <!-- Penanda Awal Percakapan (Ketika seluruh riwayat sudah termuat) -->
            <div
              v-else-if="!hasMoreMessages && activeConversationMessages.length > 0"
              class="flex items-center justify-center my-2"
            >
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-medium text-muted-foreground bg-muted/50 border border-border/50">
                Awal percakapan
              </span>
            </div>
            <div
              v-for="message in activeConversationMessages"
              :key="message.id"
              class="flex flex-col gap-1 w-full"
              :class="message.from === 'me' ? 'items-end' : 'items-start'"
            >
              <div class="flex items-start gap-3 max-w-[85%] sm:max-w-[75%]" :class="message.from === 'me' ? 'justify-end' : ''">
                <!-- Avatar for other sender -->
                <Avatar
                  v-if="message.from !== 'me'"
                  class="h-9 w-9 shrink-0 ring-1 ring-border self-start"
                >
                  <AvatarImage v-if="activeConversation?.photo" :src="getPhotoUrl(activeConversation.photo)" :alt="activeConversation.name" />
                  <AvatarFallback class="bg-primary/10 text-primary">
                    <User class="h-4 w-4 text-muted-foreground" />
                  </AvatarFallback>
                </Avatar>

                <div class="flex flex-col gap-1 w-full">
                  <!-- Message Bubble Box -->
                  <div
                    class="rounded-xl border px-4 py-3 text-sm leading-6 shadow-xs text-left"
                    :class="
                      message.from === 'me'
                        ? 'border-primary/30 bg-primary/10 text-foreground'
                        : 'border-border bg-muted/40 text-foreground'
                    "
                  >
                    <p class="whitespace-pre-line">{{ message.text }}</p>
                  </div>

                  <!-- Time & Status below message box -->
                  <div
                    class="flex items-center gap-1.5 text-[10px] font-medium text-muted-foreground px-1 font-mono"
                    :class="message.from === 'me' ? 'justify-end' : 'justify-start'"
                  >
                    <span>{{ message.time }}</span>
                    <template v-if="message.from === 'me'">
                      <!-- Message Read indicator (Ceklis 2 Biru) -->
                      <CheckCheck
                        v-if="message.status === 'read'"
                        class="h-3.5 w-3.5 text-sky-500"
                      />
                      <!-- Message Delivered indicator (Ceklis 2 Abu-abu saat Penerima Online) -->
                      <CheckCheck
                        v-else-if="message.status === 'delivered'"
                        class="h-3.5 w-3.5 text-muted-foreground/80"
                      />
                      <!-- Message Sent indicator (Ceklis 1 Abu-abu saat Penerima Offline) -->
                      <Check
                        v-else-if="message.status === 'sent'"
                        class="h-3.5 w-3.5 text-muted-foreground/80"
                      />
                      <!-- Message Failed indicator -->
                      <span v-else-if="message.status === 'failed'" class="text-[9px] text-red-500 font-sans">
                        Gagal mengirim
                      </span>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <div
          v-else
          class="flex h-full items-center justify-center rounded-lg border border-dashed border-border text-sm text-muted-foreground"
        >
          Pilih percakapan untuk melihat isi chat.
        </div>
      </div>

      <!-- FOOTER: SEND INPUT -->
      <footer class="border-t border-border bg-background p-4 sm:p-5">
        <div class="flex gap-3">
          <Input 
            v-model="newMessage" 
            placeholder="Tulis pesan..." 
            class="h-11 flex-1" 
            :disabled="!activeConversationId"
            @keyup.enter="send" 
          />
          <Button class="h-11 shrink-0 px-6" :disabled="!activeConversationId" @click="send">Kirim</Button>
        </div>
      </footer>
    </section>
  </div>
</template>
