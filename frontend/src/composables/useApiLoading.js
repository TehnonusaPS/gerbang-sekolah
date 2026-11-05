import { ref, computed, getCurrentInstance, onMounted } from 'vue'

const activeGetCount = ref(0)
const activeMutateCount = ref(0)
const isInitialPageLoading = ref(false)
const pageProgress = ref(0)

let totalPageRequests = 0
let completedPageRequests = 0
let settleTimer = null
let finishTimer = null
let maxSafetyTimer = null
let progressInterval = null

function startProgressTicker() {
  if (progressInterval) clearInterval(progressInterval)
  progressInterval = setInterval(() => {
    if (!isInitialPageLoading.value) {
      clearInterval(progressInterval)
      progressInterval = null
      return
    }

    const ratioTarget =
      totalPageRequests > 0
        ? Math.min(92, Math.round((completedPageRequests / totalPageRequests) * 85) + 15)
        : 85

    if (pageProgress.value < ratioTarget) {
      pageProgress.value = Math.min(92, pageProgress.value + 6)
    } else if (pageProgress.value < 90) {
      const step = Math.max(1, Math.round((90 - pageProgress.value) * 0.15))
      pageProgress.value = Math.min(90, pageProgress.value + step)
    }
  }, 40)
}

function stopProgressTicker() {
  if (progressInterval) {
    clearInterval(progressInterval)
    progressInterval = null
  }
}

function finishPageLoad() {
  stopProgressTicker()
  pageProgress.value = 100
  if (settleTimer) {
    clearTimeout(settleTimer)
    settleTimer = null
  }
  if (maxSafetyTimer) {
    clearTimeout(maxSafetyTimer)
    maxSafetyTimer = null
  }

  if (finishTimer) clearTimeout(finishTimer)
  finishTimer = setTimeout(() => {
    isInitialPageLoading.value = false
    finishTimer = null
  }, 100)
}

function scheduleSettleCheck(delayMs = 80) {
  if (settleTimer) clearTimeout(settleTimer)
  settleTimer = setTimeout(() => {
    // Selesai jika tidak ada GET request aktif ATAU seluruh request yang dicatat di awal halaman sudah beres
    const allTrackedDone = totalPageRequests > 0 && completedPageRequests >= totalPageRequests
    if (activeGetCount.value <= 0 || allTrackedDone) {
      finishPageLoad()
    } else {
      // Jika masih ada request yang berjalan, jadwalkan pengecekan ulang berkala sampai selesai/timeout
      scheduleSettleCheck(120)
    }
  }, delayMs)
}

/**
 * Dipanggil saat perpindahan halaman (route change) di SidebarLayout.
 * Menampilkan PageLoader dengan transisi progress nyata hingga data onMounted selesai dimuat.
 */
export function triggerPageLoad(minDurationMs = 120) {
  if (finishTimer) {
    clearTimeout(finishTimer)
    finishTimer = null
  }
  if (maxSafetyTimer) {
    clearTimeout(maxSafetyTimer)
  }

  totalPageRequests = activeGetCount.value
  completedPageRequests = 0
  pageProgress.value = 15
  isInitialPageLoading.value = true
  startProgressTicker()
  scheduleSettleCheck(minDurationMs)

  // Hard safety timeout: loader tidak boleh macet lebih dari 2.5 detik dalam kondisi apapun
  maxSafetyTimer = setTimeout(() => {
    if (isInitialPageLoading.value) {
      finishPageLoad()
    }
  }, 2500)
}

export function trackRequestStart(method = 'get') {
  const m = String(method || 'get').toLowerCase()
  if (m === 'get' || m === 'head' || m === 'options') {
    activeGetCount.value++
    if (isInitialPageLoading.value) {
      totalPageRequests++
      if (finishTimer) {
        clearTimeout(finishTimer)
        finishTimer = null
      }
      if (pageProgress.value >= 100) {
        pageProgress.value = 75
        startProgressTicker()
      }
    }
    scheduleSettleCheck(120)
  } else {
    activeMutateCount.value++
  }
}

export function trackRequestEnd(method = 'get') {
  const m = String(method || 'get').toLowerCase()
  if (m === 'get' || m === 'head' || m === 'options') {
    activeGetCount.value = Math.max(0, activeGetCount.value - 1)
    if (isInitialPageLoading.value) {
      completedPageRequests++
      const ratio =
        totalPageRequests > 0
          ? Math.min(95, Math.round((completedPageRequests / totalPageRequests) * 90) + 10)
          : 90
      if (ratio > pageProgress.value) {
        pageProgress.value = ratio
      }
    }
    scheduleSettleCheck(60)
  } else {
    activeMutateCount.value = Math.max(0, activeMutateCount.value - 1)
  }
}

export function useApiLoading() {
  const instance = getCurrentInstance()
  const isInitialMount = ref(Boolean(instance))

  if (instance) {
    onMounted(() => {
      setTimeout(() => {
        isInitialMount.value = false
      }, 80)
    })
  }

  const isFetching = computed(
    () => activeGetCount.value > 0 || isInitialPageLoading.value || isInitialMount.value
  )
  const isPageLoading = computed(() => isInitialPageLoading.value)
  const isMutating = computed(() => activeMutateCount.value > 0)
  const isAnyLoading = computed(
    () =>
      activeGetCount.value > 0 ||
      activeMutateCount.value > 0 ||
      isInitialPageLoading.value ||
      isInitialMount.value
  )

  return {
    activeGetCount,
    activeMutateCount,
    isInitialMount,
    isInitialPageLoading,
    pageProgress,
    isFetching,
    isPageLoading,
    isMutating,
    isAnyLoading,
    triggerPageLoad
  }
}
