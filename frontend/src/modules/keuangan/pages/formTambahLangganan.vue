<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { toast } from 'vue-sonner'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { getLocalTimeZone, today, CalendarDate } from '@internationalized/date'
import { formatDate } from '@/utils/formatDate'
import { glassFade } from '@/config/motion'
import {
  Field,
  FieldContent,
  FieldDescription,
  FieldLabel,
} from '@/components/ui/field'
import {
  InputGroup,
  InputGroupText,
  InputGroupInput,
} from '@/components/ui/input-group'
import {
  Building2,
  CheckCircle2,
  Sparkles,
  ShieldCheck,
  Zap,
  Info,
  Mail,
  Phone,
  HelpCircle,
  Key,
  Layers,
  Calendar
} from 'lucide-vue-next'
import * as financeService from '@/services/superAdminFinanceService'

const router = useRouter()
const route = useRoute()

const isEditMode = computed(() => !!route.query.id)

const activeDate = ref(today(getLocalTimeZone()))
const dueDate = ref(today(getLocalTimeZone()).add({ years: 1 }))
const plans = ref([])
const foundations = ref([])
const selectedPlan = ref(null)
const selectedFoundation = ref(null)

const form = ref({
  foundation_id: '',
  subscription_plan_id: '',
  status: 'aktif',
  catatan: '',

  // Preview properties
  namaInstitusi: '',
  tenantId: '',
  paket: '',
  nilaiKontrak: 0,
  emailAdmin: '',
  telepon: ''
})

const calculateDueDate = () => {
  if (isEditMode.value) return // Don't auto-calculate if editing, keep the one from database
  if (!selectedPlan.value) {
    dueDate.value = activeDate.value.add({ years: 1 })
    return
  }
  const cycle = selectedPlan.value.billing_cycle
  if (cycle === 'monthly') {
    dueDate.value = activeDate.value.add({ months: 1 })
  } else if (cycle === 'yearly') {
    dueDate.value = activeDate.value.add({ years: 1 })
  } else if (cycle === 'lifetime') {
    dueDate.value = activeDate.value.add({ years: 100 })
  } else {
    dueDate.value = activeDate.value.add({ years: 1 })
  }
}

watch([selectedPlan, activeDate], () => {
  calculateDueDate()
})

// Binds for editable HTML date pickers in edit mode
const startsAtStr = computed({
  get() {
    return activeDate.value.toString()
  },
  set(val) {
    const parts = val.split('-')
    if (parts.length === 3) {
      activeDate.value = new CalendarDate(parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2]))
    }
  }
})

const endsAtStr = computed({
  get() {
    return dueDate.value.toString()
  },
  set(val) {
    const parts = val.split('-')
    if (parts.length === 3) {
      dueDate.value = new CalendarDate(parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2]))
    }
  }
})

// Load options from backend
onMounted(async () => {
  try {
    const plansRes = await financeService.getPlans()
    if (plansRes.status === 'success') {
      plans.value = plansRes.data
    }

    const foundationsRes = await financeService.getFoundations()
    if (foundationsRes.status === 'success') {
      foundations.value = foundationsRes.data
    }

    if (isEditMode.value) {
      const subRes = await financeService.getSubscription(route.query.id)
      if (subRes.status === 'success' && subRes.data) {
        const sub = subRes.data
        form.value.foundation_id = sub.foundation_id
        form.value.subscription_plan_id = sub.subscription_plan_id
        form.value.status = sub.status // 'active', 'expired', 'trial', 'cancelled'
        form.value.catatan = sub.notes || ''

        if (sub.starts_at) {
          const parts = sub.starts_at.split('-')
          if (parts.length === 3) {
            activeDate.value = new CalendarDate(parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2]))
          }
        }
        if (sub.ends_at) {
          const parts = sub.ends_at.split('-')
          if (parts.length === 3) {
            dueDate.value = new CalendarDate(parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2]))
          }
        }
      }
    }
  } catch (error) {
    toast.error('Gagal mengambil data dari server.')
  }
})

// Watch foundation to populate preview
watch(() => form.value.foundation_id, (newId) => {
  const found = foundations.value.find(f => f.id === newId)
  if (found) {
    selectedFoundation.value = found
    form.value.namaInstitusi = found.name
    form.value.tenantId = found.code || `F-${found.id}`
    form.value.emailAdmin = found.email || `${found.name.toLowerCase().replace(/[^a-z0-9]/g, '')}@nusantara.sch.id`
    form.value.telepon = found.phone || '-'
  }
})

// Watch plan to populate preview
watch(() => form.value.subscription_plan_id, (newId) => {
  const plan = plans.value.find(p => p.id === newId)
  if (plan) {
    selectedPlan.value = plan
    form.value.paket = plan.name
    form.value.nilaiKontrak = parseFloat(plan.price)
  }
})

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val)
}

const packageBadgeStyle = (pkg) => {
  if (!pkg) return 'bg-slate-500/25 text-slate-300 text-[10px]'
  const name = pkg.toLowerCase()
  if (name.includes('enterprise')) {
    return 'bg-violet-500/20 text-violet-300 border border-violet-500/30 text-[10px] px-2.5 py-0.5 font-semibold tracking-wider'
  } else if (name.includes('premium') || name.includes('professional')) {
    return 'bg-blue-500/20 text-blue-300 border border-blue-500/30 text-[10px] px-2.5 py-0.5 font-semibold tracking-wider'
  } else if (name.includes('basic')) {
    return 'bg-slate-500/20 text-slate-300 border border-slate-500/30 text-[10px] px-2.5 py-0.5 font-semibold tracking-wider'
  } else {
    return 'bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] px-2.5 py-0.5 font-semibold tracking-wider'
  }
}

const statusBadgeStyle = (status) => {
  if (!status) return 'bg-slate-500/10 text-slate-400 border border-slate-500/20 text-[10px] px-2 py-0.5'
  switch (status.toLowerCase()) {
    case 'aktif':
    case 'active':
      return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] px-2 py-0.5'
    case 'trialing':
    case 'trial':
      return 'bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[10px] px-2 py-0.5'
    case 'overdue':
    case 'expired':
      return 'bg-red-500/10 text-red-400 border border-red-500/20 text-[10px] px-2 py-0.5'
    case 'cancelled':
      return 'bg-orange-500/10 text-orange-400 border border-orange-500/20 text-[10px] px-2 py-0.5'
    default:
      return 'bg-slate-500/10 text-slate-400 border border-slate-500/20 text-[10px] px-2 py-0.5'
  }
}

const packageFeatures = computed(() => {
  if (!selectedPlan.value) return null
  const plan = selectedPlan.value
  const name = plan.name.toLowerCase()

  let icon = ShieldCheck
  let color = 'text-slate-500 bg-slate-500/10 border-slate-500/20'

  if (name.includes('enterprise')) {
    icon = Zap
    color = 'text-violet-500 bg-violet-500/10 border-violet-500/20'
  } else if (name.includes('premium') || name.includes('professional')) {
    icon = Sparkles
    color = 'text-blue-500 bg-blue-500/10 border-blue-500/20'
  }

  return {
    title: plan.name,
    desc: `Fitur untuk paket ${plan.name}.`,
    benefits: plan.features || [
      `Maksimal ${plan.max_schools} unit sekolah`,
      `Maksimal ${plan.max_students} siswa`,
      `Siklus Tagihan: ${plan.billing_cycle}`
    ],
    icon,
    color
  }
})

const isSubmitting = ref(false)

const handleSubmit = async () => {
  if (!form.value.foundation_id) {
    toast.error('Mohon pilih institusi / yayasan!')
    return
  }
  if (!form.value.subscription_plan_id) {
    toast.error('Mohon pilih paket langganan!')
    return
  }

  isSubmitting.value = true

  try {
    if (isEditMode.value) {
      await financeService.updateSubscription(route.query.id, {
        subscription_plan_id: form.value.subscription_plan_id,
        status: form.value.status,
        starts_at: activeDate.value.toString(),
        ends_at: dueDate.value.toString(),
        notes: form.value.catatan
      })
      toast.success('Detail Langganan Berhasil Diperbarui!')
      router.push('/keuangan/subscription')
    } else {
      // 1. Create manual pending invoice/payment
      const invRes = await financeService.createInvoice({
        foundation_id: form.value.foundation_id,
        subscription_plan_id: form.value.subscription_plan_id,
        notes: form.value.catatan || 'Manual subscription activation invoice'
      })

      if (invRes.status === 'success' && invRes.data) {
        const paymentId = invRes.data.id

        // 2. If status set to 'aktif', immediately verify payment as 'paid' to activate subscription
        if (form.value.status === 'aktif') {
          await financeService.verifyPayment(paymentId, {
            status: 'paid',
            notes: 'Auto-approved and activated by Administrator.'
          })
          toast.success('Pendaftaran Langganan Baru Berhasil & Diaktifkan!')
        } else {
          toast.success('Invoice Tagihan Langganan Berhasil Dibuat (Pending)!')
        }

        router.push('/keuangan/subscription')
      } else {
        throw new Error(invRes.message || 'Gagal membuat tagihan.')
      }
    }
  } catch (error) {
    toast.error('Gagal menyimpan langganan', {
      description: error.message || 'Terjadi kesalahan saat memproses API.'
    })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="h-full space-y-6 text-foreground pb-10 max-w-6xl mx-auto w-full text-left"
  >
    <PageHeader
      :title="isEditMode ? 'Edit Detail Langganan' : 'Tambah Langganan Baru'"
      description="Lengkapi formulir di bawah ini untuk mendaftarkan institusi/tenant baru ke dalam lisensi Gerbang Sekolah melalui backend."
      back
      @back="router.push('/keuangan/subscription')"
    />

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-6 items-start">
      <!-- Left Column: Form -->
      <Card class="shadow-sm border-border bg-card/60 backdrop-blur-md overflow-hidden rounded-xl">
        <div class="px-6 py-5 border-b border-border/80 flex justify-between items-center bg-muted/20">
          <div class="flex items-center gap-2.5">
            <div class="p-1.5 bg-primary/10 rounded-lg text-primary">
              <Layers class="w-4.5 h-4.5" />
            </div>
            <div>
              <h2 class="font-bold text-sm tracking-wide text-foreground uppercase">FORMULIR AKTIVASI</h2>
              <p class="text-[10px] text-muted-foreground mt-0.5">Menggunakan komponen standar proyek</p>
            </div>
          </div>
          <Badge variant="outline" class="font-mono text-xs px-2.5 py-0.5 border-primary/20 bg-primary/5 text-primary" v-if="form.tenantId">
            {{ form.tenantId }}
          </Badge>
        </div>

        <form @submit.prevent="handleSubmit" class="p-6 sm:p-8 space-y-6">
          <!-- Nama & ID Tenant -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nama Institusi -->
            <Field>
              <FieldLabel>Nama Institusi / Sekolah <span class="text-destructive">*</span></FieldLabel>
              <FieldContent>
                <Select v-model="form.foundation_id" required :disabled="isEditMode">
                  <SelectTrigger class="h-11 bg-background/50 border-border">
                    <SelectValue placeholder="Pilih Institusi / Yayasan" />
                  </SelectTrigger>
                  <SelectContent class="bg-card border-border">
                    <SelectItem v-for="f in foundations" :key="f.id" :value="f.id">
                      {{ f.name }} ({{ f.code }})
                    </SelectItem>
                  </SelectContent>
                </Select>
              </FieldContent>
            </Field>

            <!-- ID Tenant -->
            <Field>
              <FieldLabel>ID Tenant (Otomatis)</FieldLabel>
              <FieldContent>
                <InputGroup>
                  <InputGroupText>
                    <Key class="w-4 h-4 text-muted-foreground" />
                  </InputGroupText>
                  <InputGroupInput
                    v-model="form.tenantId"
                    placeholder="Terisi otomatis..."
                    disabled
                    class="bg-muted/60 text-muted-foreground font-mono cursor-not-allowed"
                  />
                </InputGroup>
              </FieldContent>
            </Field>
          </div>

          <!-- Paket & Status -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Paket Langganan -->
            <Field>
              <FieldLabel>Paket Langganan <span class="text-destructive">*</span></FieldLabel>
              <FieldContent>
                <Select v-model="form.subscription_plan_id" required>
                  <SelectTrigger class="h-11 bg-background/50 border-border">
                    <SelectValue placeholder="Pilih Paket Langganan" />
                  </SelectTrigger>
                  <SelectContent class="bg-card border-border">
                    <SelectItem v-for="p in plans" :key="p.id" :value="p.id">
                      {{ p.name }} ({{ formatCurrency(p.price) }} / {{ p.billing_cycle }})
                    </SelectItem>
                  </SelectContent>
                </Select>
              </FieldContent>
            </Field>
            
            <!-- Status Awal / Status Langganan -->
            <Field>
              <FieldLabel>Status {{ isEditMode ? 'Langganan' : 'Awal' }}</FieldLabel>
              <FieldContent>
                <Select v-model="form.status">
                  <SelectTrigger class="h-11 bg-background/50 border-border">
                    <SelectValue :placeholder="isEditMode ? 'Pilih Status' : 'Pilih Status Awal'" />
                  </SelectTrigger>
                  <SelectContent class="bg-card border-border">
                    <template v-if="isEditMode">
                      <SelectItem value="active">Aktif (Active)</SelectItem>
                      <SelectItem value="trial">Uji Coba (Trial)</SelectItem>
                      <SelectItem value="expired">Kedaluwarsa (Expired)</SelectItem>
                      <SelectItem value="cancelled">Dibatalkan (Cancelled)</SelectItem>
                    </template>
                    <template v-else>
                      <SelectItem value="aktif">Aktif Langsung (Paid)</SelectItem>
                      <SelectItem value="pending">Menunggu Pembayaran (Pending)</SelectItem>
                    </template>
                  </SelectContent>
                </Select>
              </FieldContent>
            </Field>
          </div>

          <!-- Nilai Kontrak -->
          <Field>
            <FieldLabel>Nilai Kontrak Pertahun (Rp)</FieldLabel>
            <FieldContent>
              <InputGroup>
                <InputGroupText class="font-bold text-xs">Rp</InputGroupText>
                <InputGroupInput
                  :model-value="form.nilaiKontrak"
                  type="text"
                  placeholder="0"
                  disabled
                  class="bg-muted/60 text-muted-foreground cursor-not-allowed"
                />
              </InputGroup>
            </FieldContent>
            <FieldDescription class="flex items-center gap-1">
              <HelpCircle class="w-3 h-3 shrink-0" />
              Sesuai dengan tarif dasar paket yang dipilih.
            </FieldDescription>
          </Field>

          <!-- Tanggal Aktivasi & Jatuh Tempo -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Field>
              <FieldLabel>Tanggal Aktivasi</FieldLabel>
              <FieldContent>
                <InputGroup>
                  <InputGroupText>
                    <Calendar class="w-4 h-4 text-muted-foreground" />
                  </InputGroupText>
                  <input
                    v-if="isEditMode"
                    v-model="startsAtStr"
                    type="date"
                    class="flex h-11 w-full rounded-lg border border-border bg-background/50 px-3 py-2 text-sm focus-visible:outline-none focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                  />
                  <InputGroupInput
                    v-else
                    :model-value="formatDate(activeDate)"
                    disabled
                    class="bg-muted/60 text-muted-foreground cursor-not-allowed"
                  />
                </InputGroup>
              </FieldContent>
            </Field>
            
            <Field>
              <FieldLabel>Tanggal Jatuh Tempo</FieldLabel>
              <FieldContent>
                <InputGroup>
                  <InputGroupText>
                    <Calendar class="w-4 h-4 text-muted-foreground" />
                  </InputGroupText>
                  <input
                    v-if="isEditMode"
                    v-model="endsAtStr"
                    type="date"
                    class="flex h-11 w-full rounded-lg border border-border bg-background/50 px-3 py-2 text-sm focus-visible:outline-none focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                  />
                  <InputGroupInput
                    v-else
                    :model-value="formatDate(dueDate)"
                    disabled
                    class="bg-muted/60 text-muted-foreground cursor-not-allowed"
                  />
                </InputGroup>
              </FieldContent>
            </Field>
          </div>

          <!-- Admin Contact Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Email Admin -->
            <Field>
              <FieldLabel>Email Administrator</FieldLabel>
              <FieldContent>
                <InputGroup>
                  <InputGroupText>
                    <Mail class="w-4 h-4 text-muted-foreground" />
                  </InputGroupText>
                  <InputGroupInput
                    v-model="form.emailAdmin"
                    type="email"
                    placeholder="admin.sekolah@email.com"
                    disabled
                    class="bg-muted/60 text-muted-foreground cursor-not-allowed"
                  />
                </InputGroup>
              </FieldContent>
            </Field>
            
            <!-- Telepon -->
            <Field>
              <FieldLabel>No. Telepon / WhatsApp</FieldLabel>
              <FieldContent>
                <InputGroup>
                  <InputGroupText>
                    <Phone class="w-4 h-4 text-muted-foreground" />
                  </InputGroupText>
                  <InputGroupInput
                    v-model="form.telepon"
                    type="tel"
                    placeholder="Contoh: 081234567890"
                    disabled
                    class="bg-muted/60 text-muted-foreground cursor-not-allowed"
                  />
                </InputGroup>
              </FieldContent>
            </Field>
          </div>

          <!-- Catatan / Memo -->
          <Field>
            <FieldLabel>Catatan Tambahan (Memo Internal)</FieldLabel>
            <FieldContent>
              <Textarea
                v-model="form.catatan"
                placeholder="Tambahkan informasi khusus seperti nomor referensi transfer bank, penanggung jawab yayasan, dll..."
                class="min-h-[100px] resize-none bg-background/50 border-border focus-visible:ring-primary/20 text-sm"
              />
            </FieldContent>
          </Field>

          <!-- Form Buttons -->
          <div class="border-t border-border/80 pt-6 flex items-center justify-end gap-3.5">
            <Button
              type="button"
              variant="outline"
              class="px-5 h-11 rounded-lg font-semibold border-2 hover:bg-muted/50"
              @click="router.push('/keuangan/subscription')"
            >
              Batal
            </Button>
            
            <Button
              type="submit"
              class="px-6 h-11 rounded-lg font-bold shadow-md bg-primary text-primary-foreground hover:bg-primary/90 transition-all hover:-translate-y-0.5 flex items-center gap-2"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting" class="flex items-center gap-1.5">
                <span class="w-4 h-4 border-2 border-primary-foreground border-t-transparent animate-spin rounded-full"></span>
                Menyimpan...
              </span>
              <span v-else class="flex items-center gap-2">
                {{ isEditMode ? 'Simpan Perubahan' : 'Simpan Langganan' }}
                <CheckCircle2 class="w-4.5 h-4.5" />
              </span>
            </Button>
          </div>
        </form>
      </Card>

      <!-- Right Column: Card Preview -->
      <div class="space-y-6">
        <!-- Live Subscription Preview Card -->
        <div 
          class="relative overflow-hidden rounded-2xl shadow-xl border border-primary/20 bg-gradient-to-br from-primary via-primary/90 to-primary/75 p-6 text-primary-foreground transition-all duration-300 hover:scale-[1.02]"
        >
          <!-- Ambient lighting/glow inside card -->
          <div class="absolute -right-16 -top-16 w-40 h-40 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
          <div class="absolute -left-12 -bottom-12 w-36 h-36 rounded-full bg-black/10 blur-xl pointer-events-none"></div>

          <!-- Card Content -->
          <div class="relative z-10 space-y-6 flex flex-col justify-between min-h-[260px]">
            <div class="flex justify-between items-start gap-4">
              <div class="space-y-1.5 flex-1 min-w-0 pr-2">
                <span class="text-[9px] tracking-widest font-black uppercase text-primary-foreground/70">KARTU ANGGOTA LISENSI</span>
                <h3 class="text-lg font-bold tracking-tight leading-tight truncate">
                  {{ form.namaInstitusi || 'Nama Institusi / Sekolah' }}
                </h3>
                <div class="inline-flex items-center gap-1 bg-white/10 px-2 py-0.5 rounded text-[10px] font-mono border border-white/15">
                  ID: {{ form.tenantId || 'T-PROSES-DAFTAR' }}
                </div>
              </div>
              
              <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center border border-white/20 shadow-inner shrink-0">
                <Building2 class="w-5.5 h-5.5 text-primary-foreground" />
              </div>
            </div>

            <!-- Middle section: Price & Package -->
            <div class="py-4 border-y border-white/10 flex justify-between items-center">
              <div>
                <p class="text-[9px] uppercase tracking-wider text-primary-foreground/75 font-semibold">Tipe Paket</p>
                <div class="mt-1 flex items-center">
                  <Badge :class="packageBadgeStyle(form.paket)">
                    {{ form.paket ? form.paket.toUpperCase() : 'BELUM DIPILIH' }}
                  </Badge>
                </div>
              </div>
              <div class="text-right">
                <p class="text-[9px] uppercase tracking-wider text-primary-foreground/75 font-semibold">Tarif Paket</p>
                <p class="text-lg font-bold mt-0.5 tracking-tight">
                  {{ formatCurrency(form.nilaiKontrak || 0) }}
                </p>
              </div>
            </div>

            <!-- Bottom section: Dates & Status -->
            <div class="flex justify-between items-end gap-4">
              <div class="space-y-3">
                <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                  <div>
                    <span class="text-[8px] uppercase text-primary-foreground/60 block">Aktivasi</span>
                    <span class="text-[11px] font-bold text-white/95 flex items-center gap-1 mt-0.5">
                      {{ formatDate(activeDate) }}
                    </span>
                  </div>
                  <div>
                    <span class="text-[8px] uppercase text-primary-foreground/60 block">Jatuh Tempo</span>
                    <span class="text-[11px] font-bold text-white/95 flex items-center gap-1 mt-0.5">
                      {{ formatDate(dueDate) }}
                    </span>
                  </div>
                </div>
                <div class="border-t border-white/10 pt-2 min-w-0">
                  <span class="text-[8px] uppercase text-primary-foreground/60 block">Admin Sekolah</span>
                  <span class="text-[10px] font-semibold truncate block mt-0.5 text-white/90">
                    {{ form.emailAdmin || 'admin.sekolah@gerbangsekolah.id' }}
                  </span>
                </div>
              </div>
              
              <Badge :class="statusBadgeStyle(form.status)" class="shrink-0 mb-0.5 shadow-sm">
                ● {{ form.status.toUpperCase() }}
              </Badge>
            </div>
          </div>
        </div>

        <!-- Dynamic Package Feature Information -->
        <div v-if="packageFeatures" class="bg-card/40 border border-border/60 p-5 rounded-xl shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-lg border" :class="packageFeatures.color">
              <component :is="packageFeatures.icon" class="w-5 h-5" />
            </div>
            <div>
              <h4 class="font-bold text-sm text-foreground">{{ packageFeatures.title }}</h4>
              <p class="text-xs text-muted-foreground leading-relaxed">{{ packageFeatures.desc }}</p>
            </div>
          </div>
          
          <ul class="space-y-2 border-t border-border/80 pt-4">
            <li v-for="(benefit, index) in packageFeatures.benefits" :key="index" class="text-xs font-semibold flex items-center gap-2.5 text-muted-foreground/90">
              <div class="w-4 h-4 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-500 flex items-center justify-center text-[10px] shrink-0 font-bold">
                ✓
              </div>
              {{ benefit }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>