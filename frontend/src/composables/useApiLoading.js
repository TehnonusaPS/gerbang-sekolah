import { ref, computed, getCurrentInstance, onMounted } from 'vue'

const activeGetCount = ref(0)
const activeMutateCount = ref(0)
const isInitialPageLoading = ref(false)
const pageProgress = ref(0)

let totalPageRequests = 0
let completedPageRequests = 0
let settleTimer = null
let finishTimer = null
let progressInterval = null

function startProgressTicker() {
  if (progressInterval) clearInterval(progressInterval)
  progressInterval = setInterval(() => {
    if (!isInitialPageLoading.value) {
      clearInterval(progressInterval)
      progressInterval = null
      return
    }

    // Hitung target minimum berdasarkan rasio request yang sudah selesai
    const ratioTarget =
      totalPageRequests > 0
        ? Math.min(90, Math.round((completedPageRequests / totalPageRequests) * 85) + 15)
        : 35

    if (pageProgress.value < ratioTarget) {
      pageProgress.value = Math.min(92, pageProgress.value + 8)
    } else if (pageProgress.value < 90) {
      // Naik perlahan mendekati 90% selama masih menunggu response server
      const step = Math.max(1, Math.round((90 - pageProgress.value) * 0.15))
      pageProgress.value = Math.min(90, pageProgress.value + step)
    }
  }, 50)
}

function stopProgressTicker() {
  if (progressInterval) {
    clearInterval(progressInterval)
    progressInterval = null
  }
}

function scheduleSettleCheck(delayMs = 100) {
  if (settleTimer) clearTimeout(settleTimer)
  settleTimer = setTimeout(() => {
    if (activeGetCount.value === 0) {
      stopProgressTicker()
      pageProgress.value = 100
      if (finishTimer) clearTimeout(finishTimer)
      finishTimer = setTimeout(() => {
        if (activeGetCount.value === 0) {
          isInitialPageLoading.value = false
        }
      }, 140)
    }
  }, delayMs)
}

/**
 * Dipanggil saat perpindahan halaman (route change) di SidebarLayout.
 * Menahan tampilan halaman sampai seluruh request GET awal di onMounted selesai
 * dengan progress bar yang bergerak nyata dari 0% hingga 100%.
 */
export function triggerPageLoad(minDurationMs = 120) {
  if (finishTimer) {
    clearTimeout(finishTimer)
    finishTimer = null
  }
  totalPageRequests = activeGetCount.value
  completedPageRequests = 0
  pageProgress.value = 12
  isInitialPageLoading.value = true
  startProgressTicker()
  scheduleSettleCheck(minDurationMs)
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
    if (settleTimer) {
      clearTimeout(settleTimer)
      settleTimer = null
    }
  } else {
    activeMutateCount.value++
  }
}

export function trackRequestEnd(method = 'get') {
  const m = String(method || 'get').toLowerCase()
  setTimeout(() => {
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
        if (activeGetCount.value === 0) {
          scheduleSettleCheck(80)
        }
      }
    } else {
      activeMutateCount.value = Math.max(0, activeMutateCount.value - 1)
    }
  }, 25)
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
