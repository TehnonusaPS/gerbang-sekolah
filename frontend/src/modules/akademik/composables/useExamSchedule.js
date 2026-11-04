import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { fetchAllAcademicYears } from '@/services/academicYearService'
import { fetchEvents } from '@/services/academicCalendarService'
import { fetchExamSchedules, bulkStoreExamSchedules, fetchMyExamSchedule, publishExamSchedules, unpublishExamSchedules } from '@/services/examScheduleService'
import { toast } from 'vue-sonner'

export function useExamSchedule() {
  const auth = useAuthStore()

  const userRole = computed(() => auth.user?.role || 'siswa')
  const canEditExams = computed(() => ['admin_sekolah', 'superadmin'].includes(userRole.value))
  const canViewExams = computed(() => ['kepala_sekolah'].includes(userRole.value))
  const canManageExams = computed(() => canEditExams.value || canViewExams.value)
  const isTeacherOrStaff = computed(() => userRole.value === 'guru')
  const isStudentOrParent = computed(() => ['siswa', 'orang_tua'].includes(userRole.value))

  const loading = ref(false)
  const loadingEvents = ref(false)
  const saving = ref(false)
  const showConfirmSaveModal = ref(false)

  // Academic Years
  const academicYears = ref([])
  const selectedAcademicYearId = ref(null)
  const selectedAcademicYear = computed(() => academicYears.value.find(y => y.id === selectedAcademicYearId.value))

  // Events & selection
  const examEvents = ref([])
  const selectedEventId = ref(null)
  const selectedEvent = computed(() => examEvents.value.find(e => e.id === selectedEventId.value))

  // Selected Date Tab for matrix editing
  const selectedDateTab = ref('all') // 'all' or 'YYYY-MM-DD'

  // School metadata loaded from API
  const availableGrades = ref([10, 11, 12]) // default fallback
  const availableSubjects = ref([])

  // Set of dates marked as No Exam Day (Hari Bebas Ujian)
  const noExamDays = ref(new Set())

  const toggleNoExamDay = (dateStr) => {
    const newSet = new Set(noExamDays.value)
    if (newSet.has(dateStr)) {
      newSet.delete(dateStr)
    } else {
      newSet.add(dateStr)
    }
    noExamDays.value = newSet
  }

  // Subject options formatted for FormSelect
  const subjectOptions = computed(() => [
    { value: 'no_exam', label: '--- Tidak Ada Jadwal ---' },
    ...availableSubjects.value.map(s => ({
      value: String(s.id),
      label: `${s.name} (${s.code})`
    }))
  ])

  // Dynamic subject options per grade with duplicate prevention
  const getSubjectOptionsForGrade = (session, grade) => {
    const usedSubjectIds = new Set()
    
    editorSessions.value.forEach(s => {
      if (s !== session) {
        if (s.subjects && s.subjects[grade] && s.subjects[grade] !== 'no_exam') {
          usedSubjectIds.add(String(s.subjects[grade]))
        }
      }
    })

    return [
      { value: 'no_exam', label: '--- Tidak Ada Jadwal ---' },
      ...availableSubjects.value.map(s => {
        const valStr = String(s.id)
        const isUsed = usedSubjectIds.has(valStr)
        return {
          value: valStr,
          label: isUsed ? `${s.name} (${s.code}) - (Sudah Dipilih)` : `${s.name} (${s.code})`,
          disabled: isUsed
        }
      })
    ]
  }

  // Admin Editor Matrix State: Array of sessions
  const editorSessions = ref([])

  // Student / Public Schedule State
  const myScheduleData = ref([])
  const selectedGradeFilter = ref(null)

  // Standard default session times
  const defaultSessionTimes = [
    { session_number: 1, start_time: '07:30', end_time: '09:00' },
    { session_number: 2, start_time: '09:30', end_time: '11:00' },
    { session_number: 3, start_time: '11:30', end_time: '13:00' }
  ]

  // Generate dates between start_date and end_date using local year/month/day
  const eventDates = computed(() => {
    if (!selectedEvent.value) return []
    const startDateStr = (selectedEvent.value.start_date || selectedEvent.value.startDate || '').split('T')[0].split(' ')[0]
    const endDateStr = (selectedEvent.value.end_date || selectedEvent.value.endDate || '').split('T')[0].split(' ')[0]
    if (!startDateStr || !endDateStr) return []

    const dates = []
    const [sy, sm, sd] = startDateStr.split('-').map(Number)
    const [ey, em, ed] = endDateStr.split('-').map(Number)

    if (!sy || !sm || !sd || !ey || !em || !ed) return []

    const start = new Date(sy, sm - 1, sd)
    const end = new Date(ey, em - 1, ed)

    let current = new Date(start)
    while (current <= end) {
      if (current.getDay() !== 0) {
        const y = current.getFullYear()
        const m = String(current.getMonth() + 1).padStart(2, '0')
        const day = String(current.getDate()).padStart(2, '0')
        dates.push(`${y}-${m}-${day}`)
      }
      current.setDate(current.getDate() + 1)
    }
    return dates
  })

  // Filtered dates based on selected date tab
  const visibleDates = computed(() => {
    if (selectedDateTab.value === 'all') return eventDates.value
    return eventDates.value.filter(d => d === selectedDateTab.value)
  })

  const formatDateIndo = (dateStr, short = false) => {
    if (!dateStr) return ''
    const cleanStr = String(dateStr).split('T')[0].split(' ')[0]
    const parts = cleanStr.split('-').map(Number)
    if (parts.length < 3) return dateStr
    const [y, m, d] = parts
    const date = new Date(y, m - 1, d)
    return new Intl.DateTimeFormat('id-ID', short ? {
      weekday: 'short',
      day: 'numeric',
      month: 'short'
    } : {
      weekday: 'long',
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    }).format(date)
  }

  // Date status helper: 'free', 'complete', or 'empty'
  const getDateStatus = (dateStr) => {
    if (noExamDays.value.has(dateStr)) return 'free'

    const sessionsForDate = editorSessions.value.filter(s => s.exam_date === dateStr)
    if (sessionsForDate.length === 0) return 'empty'

    // Check if every session has all grades selected (either subject or no_exam)
    let isAllConfigured = true
    sessionsForDate.forEach(s => {
      if (!s.subjects) {
        isAllConfigured = false
        return
      }
      availableGrades.value.forEach(g => {
        const val = s.subjects[g]
        if (val === undefined || val === null || val === '') {
          isAllConfigured = false
        }
      })
    })

    return isAllConfigured ? 'complete' : 'empty'
  }

  // Dates that have no subjects assigned at all
  const emptyDates = computed(() => {
    return eventDates.value.filter(d => getDateStatus(d) === 'empty')
  })

  // Total stats
  const totalAssignedSubjects = computed(() => {
    let count = 0
    editorSessions.value.forEach(s => {
      if (s.subjects) {
        Object.values(s.subjects).forEach(subId => {
          if (subId) count++
        })
      }
    })
    return count
  })

  // Exam type check helper
  const examTypes = ['uts', 'uas', 'us', 'anbk', 'ujian', 'remedi', 'kegiatan']
  const isExamEvent = (e) => {
    const typeLower = (e.type || '').toLowerCase()
    if (examTypes.includes(typeLower)) return true
    const titleLower = (e.title || '').toLowerCase()
    return titleLower.includes('uts') || titleLower.includes('uas') || titleLower.includes('ujian') || titleLower.includes('pts') || titleLower.includes('pas') || titleLower.includes('sas') || titleLower.includes('sat')
  }

  // Load Academic Years
  const loadAcademicYears = async () => {
    try {
      const res = await fetchAllAcademicYears()
      let list = []
      if (Array.isArray(res)) {
        list = res
      } else if (res && res.data) {
        list = res.data
      }

      // Filter only approved academic years (fallback to all list if none marked approved)
      let approved = list.filter(y => y.calendar_status === 'approved')
      if (approved.length === 0) {
        approved = list
      }

      // Deduplicate by year name (e.g. '2025/2026')
      const uniqueYearMap = new Map()
      approved.forEach(y => {
        if (!uniqueYearMap.has(y.name)) {
          uniqueYearMap.set(y.name, y)
        } else {
          if (!uniqueYearMap.get(y.name).is_active && y.is_active) {
            uniqueYearMap.set(y.name, y)
          }
        }
      })

      const uniqueYears = Array.from(uniqueYearMap.values())
      academicYears.value = uniqueYears
      // Default to null so user explicitly selects Academic Year
      selectedAcademicYearId.value = null
    } catch (err) {
      console.error('Gagal memuat tahun ajaran:', err)
    }
  }

  // Load exam events from calendar for selected academic year
  const loadExamEvents = async () => {
    if (!selectedAcademicYearId.value) return
    loadingEvents.value = true
    examEvents.value = []
    selectedEventId.value = null
    editorSessions.value = []

    try {
      const res = await fetchEvents(selectedAcademicYearId.value)
      if (res && res.data) {
        const normalized = res.data.map(e => ({
          ...e,
          start_date: e.start_date || e.startDate,
          end_date: e.end_date || e.endDate
        }))

        let filtered = normalized.filter(isExamEvent)
        if (filtered.length === 0) {
          filtered = normalized
        }

        examEvents.value = filtered
        // Default to null so user explicitly selects Exam Event (UTS / UAS)
        selectedEventId.value = null
      }
    } catch (err) {
      console.error('Gagal memuat event ujian:', err)
      toast.error('Gagal memuat event kalender akademik.')
    } finally {
      loadingEvents.value = false
    }
  }

  const rawSessionsFromApi = ref([])
  const isPublishing = ref(false)

  const isPublished = computed(() => {
    if (!rawSessionsFromApi.value || rawSessionsFromApi.value.length === 0) return false
    return rawSessionsFromApi.value.some(s => s.status === 'published')
  })

  const handlePublishExamSchedule = async () => {
    if (!selectedEventId.value) return
    isPublishing.value = true
    try {
      const res = await publishExamSchedules(selectedEventId.value)
      toast.success('Berhasil Dipublikasikan', { description: res.message })
      await loadEventSessions()
    } catch (err) {
      toast.error('Gagal mempublikasikan jadwal ujian')
    } finally {
      isPublishing.value = false
    }
  }

  const handleUnpublishExamSchedule = async () => {
    if (!selectedEventId.value) return
    isPublishing.value = true
    try {
      const res = await unpublishExamSchedules(selectedEventId.value)
      toast.success('Ditarik ke Draft', { description: res.message })
      await loadEventSessions()
    } catch (err) {
      toast.error('Gagal menarik jadwal ujian ke draft')
    } finally {
      isPublishing.value = false
    }
  }

  // Load detail sessions for selected event (Admin / Manager Mode)
  const loadEventSessions = async () => {
    if (!selectedEventId.value) return
    loading.value = true
    try {
      const res = await fetchExamSchedules(selectedEventId.value)
      if (res && res.status === 'success') {
        rawSessionsFromApi.value = res.sessions || []

        if (res.grades && res.grades.length > 0) {
          availableGrades.value = res.grades
        }
        if (res.subjects) {
          availableSubjects.value = res.subjects
        }

        if (res.sessions && res.sessions.length > 0) {
          editorSessions.value = res.sessions.map(s => {
            const subjectsMap = {}
            const list = s.session_subjects || s.sessionSubjects || []
            list.forEach(ss => {
              const subjId = ss.subject_id || ss.subjectId
              if (subjId) {
                subjectsMap[ss.grade] = String(subjId)
              }
            })
            const cleanDate = String(s.exam_date || s.examDate || '').split('T')[0].split(' ')[0]
            return {
              id: s.id,
              exam_date: cleanDate,
              session_number: s.session_number,
              start_time: (s.start_time || '').substring(0, 5),
              end_time: (s.end_time || '').substring(0, 5),
              notes: s.notes || '',
              subjects: subjectsMap
            }
          })
        } else {
          autoPopulateSessions()
        }
      }
    } catch (err) {
      console.error('Gagal memuat sesi ujian:', err)
      toast.error('Gagal memuat detail sesi ujian.')
    } finally {
      loading.value = false
    }
  }

  // Auto populate 2 sessions for each date in event
  const autoPopulateSessions = () => {
    const newSessions = []
    eventDates.value.forEach(date => {
      defaultSessionTimes.slice(0, 2).forEach(t => {
        newSessions.push({
          exam_date: date,
          session_number: t.session_number,
          start_time: t.start_time,
          end_time: t.end_time,
          notes: '',
          subjects: {}
        })
      })
    })
    editorSessions.value = newSessions
  }

  // Add a session for a specific date
  const addSessionForDate = (dateStr) => {
    const existingForDate = editorSessions.value.filter(s => s.exam_date === dateStr)
    const nextNum = existingForDate.length + 1
    const timeTemplate = defaultSessionTimes[nextNum - 1] || { start_time: '13:30', end_time: '15:00' }

    editorSessions.value.push({
      exam_date: dateStr,
      session_number: nextNum,
      start_time: timeTemplate.start_time,
      end_time: timeTemplate.end_time,
      notes: '',
      subjects: {}
    })
  }

  // Remove a session
  const removeSession = (index) => {
    editorSessions.value.splice(index, 1)
  }

  // Open save confirmation modal
  const confirmSave = () => {
    if (!selectedEventId.value) return
    showConfirmSaveModal.value = true
  }

  // Execute Save All sessions (Bulk store)
  const executeSaveAllSessions = async () => {
    if (!selectedEventId.value) return
    saving.value = true
    try {
      const payloadSessions = editorSessions.value
        .filter(s => !noExamDays.value.has(s.exam_date))
        .map(s => {
          const subjectsArray = []
          Object.keys(s.subjects).forEach(grade => {
            const val = s.subjects[grade]
            if (val && val !== 'no_exam') {
              subjectsArray.push({
                grade: parseInt(grade),
                subject_id: parseInt(val)
              })
            }
          })

          return {
            exam_date: s.exam_date,
            session_number: s.session_number,
            start_time: s.start_time,
            end_time: s.end_time,
            notes: s.notes,
            subjects: subjectsArray
          }
        })

      const res = await bulkStoreExamSchedules(selectedEventId.value, payloadSessions)
      if (res && res.status === 'success') {
        toast.success('Jadwal Ujian Detail Berhasil Disimpan!')
        showConfirmSaveModal.value = false
        await loadEventSessions()
      }
    } catch (err) {
      console.error('Gagal menyimpan jadwal:', err)
      toast.error('Gagal menyimpan jadwal ujian.')
    } finally {
      saving.value = false
    }
  }

  // Load schedule for student / read-only view
  const loadMySchedule = async () => {
    loading.value = true
    try {
      const params = {}
      if (selectedGradeFilter.value) {
        params.grade = selectedGradeFilter.value
      }
      const res = await fetchMyExamSchedule(params)
      if (res && res.status === 'success') {
        myScheduleData.value = res.data
      }
    } catch (err) {
      console.error('Gagal memuat jadwal siswa:', err)
    } finally {
      loading.value = false
    }
  }

  const printSchedule = () => {
    window.print()
  }

  // Watchers
  watch(selectedAcademicYearId, (newVal) => {
    if (newVal) {
      loadExamEvents()
    }
  })

  watch(selectedEventId, (newVal) => {
    if (newVal && canManageExams.value) {
      selectedDateTab.value = 'all'
      loadEventSessions()
    }
  })

  watch(selectedGradeFilter, () => {
    if (!canManageExams.value) {
      loadMySchedule()
    }
  })

  onMounted(async () => {
    if (canManageExams.value) {
      await loadAcademicYears()
    } else {
      await loadMySchedule()
    }
  })

  return {
    userRole,
    canEditExams,
    canViewExams,
    canManageExams,
    isTeacherOrStaff,
    isStudentOrParent,
    loading,
    loadingEvents,
    saving,
    showConfirmSaveModal,
    academicYears,
    selectedAcademicYearId,
    selectedAcademicYear,
    examEvents,
    selectedEventId,
    selectedEvent,
    selectedDateTab,
    availableGrades,
    availableSubjects,
    subjectOptions,
    getSubjectOptionsForGrade,
    noExamDays,
    toggleNoExamDay,
    editorSessions,
    myScheduleData,
    selectedGradeFilter,
    eventDates,
    visibleDates,
    emptyDates,
    totalAssignedSubjects,
    formatDateIndo,
    getDateStatus,
    addSessionForDate,
    removeSession,
    confirmSave,
    executeSaveAllSessions,
    printSchedule,
    isPublished,
    isPublishing,
    handlePublishExamSchedule,
    handleUnpublishExamSchedule
  }
}
