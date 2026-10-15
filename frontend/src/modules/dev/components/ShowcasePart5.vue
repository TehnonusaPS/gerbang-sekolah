<script setup>
import { defineProps, onMounted, ref } from 'vue'
import {
  AlertCircle,
  BellRing,
  ChevronsUpDown,
  Database,
  FolderOpen,
  GraduationCap,
  LayoutDashboard,
  School,
  Settings,
  Terminal,
  User
} from 'lucide-vue-next'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { RangeCalendar } from '@/components/ui/range-calendar'
import { ResizableHandle, ResizablePanel, ResizablePanelGroup } from '@/components/ui/resizable'
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
  SheetTrigger
} from '@/components/ui/sheet'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem
} from '@/components/ui/sidebar'
import { Skeleton } from '@/components/ui/skeleton'
import { Slider } from '@/components/ui/slider'
import { getLocalTimeZone, today } from '@internationalized/date'
import { toast } from 'vue-sonner'

const props = defineProps({
  searchQuery: {
    type: String,
    default: ''
  }
})

const match = keywords => {
  if (!props.searchQuery) return true
  return keywords.toLowerCase().includes(props.searchQuery.toLowerCase())
}

const sliderValue = ref([50])

const rangeCalendarValue = ref({
  start: today(getLocalTimeZone()),
  end: today(getLocalTimeZone()).add({ days: 7 })
})
</script>

<template>
  <div class="space-y-12">
    <!-- 41. RANGE CALENDAR -->
    <section
      v-show="match('range calendar')"
      class="space-y-4"
    >
      <div class="border-b pb-2">
        <h2 class="text-2xl font-semibold text-primary">41. Range Calendar</h2>
        <p class="text-sm text-muted-foreground mt-1">
          Komponen kalender yang memungkinkan pengguna untuk memilih rentang tanggal (*start* dan
          *end*).
        </p>
      </div>
      <div class="p-6 border rounded-xl bg-card flex justify-center">
        <RangeCalendar
          v-model="rangeCalendarValue"
          class="rounded-md border shadow-sm"
        />
      </div>
    </section>

    <!-- 42. RESIZABLE -->
    <section
      v-show="match('resizable')"
      class="space-y-4"
    >
      <div class="border-b pb-2">
        <h2 class="text-2xl font-semibold text-primary">42. Resizable</h2>
        <p class="text-sm text-muted-foreground mt-1">
          Panel antarmuka yang ukurannya dapat diubah-ubah dengan cara digeser batasnya (*drag and
          drop*).
        </p>
      </div>
      <div class="p-6 border rounded-xl bg-card">
        <ResizablePanelGroup
          direction="horizontal"
          class="min-h-[200px] max-w-md rounded-lg border mx-auto"
        >
          <ResizablePanel :default-size="50">
            <div class="flex h-full items-center justify-center p-6">
              <span class="font-semibold">Panel Kiri</span>
            </div>
          </ResizablePanel>
          <ResizableHandle />
          <ResizablePanel :default-size="50">
            <div class="flex h-full items-center justify-center p-6">
              <span class="font-semibold">Panel Kanan</span>
            </div>
          </ResizablePanel>
        </ResizablePanelGroup>
      </div>
    </section>

    <!-- 43. SCROLL AREA -->
    <section
      v-show="match('scroll area')"
      class="space-y-4"
    >
      <div class="border-b pb-2">
        <h2 class="text-2xl font-semibold text-primary">43. Scroll Area</h2>
        <p class="text-sm text-muted-foreground mt-1">
          Area dengan *scrollbar* kustom yang gayanya konsisten dan mulus di semua browser.
        </p>
      </div>
      <div class="p-6 border rounded-xl bg-card flex justify-center">
        <ScrollArea class="h-[200px] w-[350px] rounded-md border p-4">
          <div class="pr-4">
            <h4 class="mb-4 text-sm font-medium leading-none">Tags</h4>
            <div
              v-for="tag in 50"
              :key="tag"
              class="text-sm"
            >
              Tag {{ tag }}
              <Separator class="my-2" />
            </div>
          </div>
        </ScrollArea>
      </div>
    </section>

    <!-- 44. SELECT -->
    <section
      v-show="match('select')"
      class="space-y-4"
    >
      <div class="border-b pb-2">
        <h2 class="text-2xl font-semibold text-primary">44. Select</h2>
        <p class="text-sm text-muted-foreground mt-1">
          Pilihan (*dropdown*) kustom kaya fitur yang menggantikan elemen &lt;select&gt; bawaan
          browser.
        </p>
      </div>
      <div class="p-6 border rounded-xl bg-card flex justify-center">
        <Select>
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="Pilih buah" />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              <SelectLabel>Fruits</SelectLabel>
              <SelectItem value="apple">Apple</SelectItem>
              <SelectItem value="banana">Banana</SelectItem>
              <SelectItem value="blueberry">Blueberry</SelectItem>
              <SelectItem value="grapes">Grapes</SelectItem>
              <SelectItem value="pineapple">Pineapple</SelectItem>
            </SelectGroup>
          </SelectContent>
        </Select>
      </div>
    </section>

    <!-- 45. SEPARATOR -->
    <section
      v-show="match('separator')"
      class="space-y-4"
    >
      <div class="border-b pb-2">
        <h2 class="text-2xl font-semibold text-primary">45. Separator</h2>
        <p class="text-sm text-muted-foreground mt-1">
          Garis pemisah secara visual atau semantik antar bagian konten.
        </p>
      </div>
      <div class="p-6 border rounded-xl bg-card">
        <div class="max-w-md mx-auto">
          <div class="space-y-1">
            <h4 class="text-sm font-medium leading-none">Radix Primitives</h4>
            <p class="text-sm text-muted-foreground">An open-source UI component library.</p>
          </div>
          <Separator class="my-4" />
          <div class="flex h-5 items-center space-x-4 text-sm">
            <div>Blog</div>
            <Separator orientation="vertical" />
            <div>Docs</div>
            <Separator orientation="vertical" />
            <div>Source</div>
          </div>
        </div>
      </div>
    </section>

    <!-- 46. SHEET -->
    <section
      v-show="match('sheet')"
      class="space-y-4"
    >
      <div class="border-b pb-2">
        <h2 class="text-2xl font-semibold text-primary">46. Sheet</h2>
        <p class="text-sm text-muted-foreground mt-1">
          Panel yang meluncur (*slide*) dari tepi layar, sering digunakan untuk menu *mobile* atau
          form detail.
        </p>
      </div>
      <div class="p-6 border rounded-xl bg-card flex justify-center">
        <Sheet>
          <SheetTrigger as-child>
            <Button variant="outline"> Open </Button>
          </SheetTrigger>
          <SheetContent>
            <SheetHeader>
              <SheetTitle>Edit profile</SheetTitle>
              <SheetDescription>
                Make changes to your profile here. Click save when you're done.
              </SheetDescription>
            </SheetHeader>
            <div class="grid flex-1 auto-rows-min gap-6 px-4 py-6">
              <div class="grid gap-3">
                <Label for="sheet-demo-name">Name</Label>
                <Input
                  id="sheet-demo-name"
                  default-value="Pedro Duarte"
                />
              </div>
              <div class="grid gap-3">
                <Label for="sheet-demo-username">Username</Label>
                <Input
                  id="sheet-demo-username"
                  default-value="@peduarte"
                />
              </div>
            </div>
            <SheetFooter>
              <Button type="submit"> Save changes </Button>
              <SheetClose as-child>
                <Button variant="outline"> Close </Button>
              </SheetClose>
            </SheetFooter>
          </SheetContent>
        </Sheet>
      </div>
    </section>

    <!-- 47. SIDEBAR -->
    <section
      v-show="match('sidebar')"
      class="space-y-4"
    >
      <div class="border-b pb-2">
        <h2 class="text-2xl font-semibold text-primary">47. Sidebar</h2>
        <p class="text-sm text-muted-foreground mt-1">
          Struktur navigasi samping bawaan aplikasi yang memiliki dukungan *state* buka/tutup.
        </p>
      </div>
      <div class="p-6 border rounded-xl bg-card flex h-[400px]">
        <div class="flex h-full w-full overflow-hidden border rounded-md relative">
          <Sidebar
            collapsible="none"
            class="border-r w-64 bg-sidebar"
          >
            <SidebarHeader class="sidebar-brand-header">
              <SidebarMenu>
                <SidebarMenuItem>
                  <SidebarMenuButton
                    size="lg"
                    as-child
                    class="sidebar-brand-btn hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                  >
                    <a href="#">
                      <div class="sidebar-brand-icon">
                        <School class="size-5" />
                      </div>
                      <div class="grid flex-1 text-left text-sm leading-tight">
                        <span class="truncate font-extrabold text-sidebar-foreground tracking-tight"
                          >Gerbang Sekolah</span
                        >
                        <span
                          class="truncate text-[10px] capitalize text-sidebar-foreground/70 font-medium"
                        >
                          Admin
                        </span>
                      </div>
                    </a>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              </SidebarMenu>
            </SidebarHeader>
            <SidebarContent>
              <SidebarGroup>
                <SidebarGroupLabel>Menu Utama</SidebarGroupLabel>
                <SidebarMenu class="gap-1.5 px-2">
                  <SidebarMenuItem>
                    <SidebarMenuButton
                      class="!h-11 px-3 text-sm transition-colors duration-200 glossy-active font-semibold"
                      tooltip="Dashboard"
                    >
                      <LayoutDashboard class="!size-5 shrink-0" />
                      <span>Dashboard</span>
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                  <SidebarMenuItem>
                    <SidebarMenuButton
                      class="!h-11 px-3 text-sm transition-colors duration-200 !bg-transparent text-sidebar-foreground/70 hover:!bg-sidebar-accent hover:!text-sidebar-foreground font-medium"
                      tooltip="Manajemen Data"
                    >
                      <Database class="!size-5 shrink-0" />
                      <span>Manajemen Data</span>
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                  <SidebarMenuItem>
                    <SidebarMenuButton
                      class="!h-11 px-3 text-sm transition-colors duration-200 !bg-transparent text-sidebar-foreground/70 hover:!bg-sidebar-accent hover:!text-sidebar-foreground font-medium"
                      tooltip="Akademik"
                    >
                      <GraduationCap class="!size-5 shrink-0" />
                      <span>Akademik</span>
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                </SidebarMenu>
              </SidebarGroup>
            </SidebarContent>
            <SidebarFooter>
              <SidebarMenu>
                <SidebarMenuItem>
                  <SidebarMenuButton
                    size="lg"
                    class="hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                  >
                    <Avatar class="h-8 w-8 rounded-lg">
                      <AvatarFallback class="rounded-lg"> AD </AvatarFallback>
                    </Avatar>
                    <div class="grid flex-1 text-left text-sm leading-tight">
                      <span class="truncate font-medium">Admin Sekolah</span>
                      <span class="truncate text-xs text-muted-foreground uppercase font-semibold"
                        >admin</span
                      >
                    </div>
                    <ChevronsUpDown class="ml-auto size-4" />
                  </SidebarMenuButton>
                </SidebarMenuItem>
              </SidebarMenu>
            </SidebarFooter>
          </Sidebar>
          <main class="flex-1 p-4 bg-background">
            <div class="text-sm">
              Ini adalah contoh konten utama yang berdampingan dengan sidebar di sebelah kiri.
            </div>
          </main>
        </div>
      </div>
    </section>

    <!-- 48. SKELETON -->
    <section
      v-show="match('skeleton')"
      class="space-y-4"
    >
      <div class="border-b pb-2">
        <h2 class="text-2xl font-semibold text-primary">48. Skeleton</h2>
        <p class="text-sm text-muted-foreground mt-1">
          Indikator visual berbentuk kerangka untuk menampilkan status *loading* dari sebuah
          komponen.
        </p>
      </div>
      <div class="p-6 border rounded-xl bg-card flex items-center space-x-4">
        <Skeleton class="h-12 w-12 rounded-full" />
        <div class="space-y-2">
          <Skeleton class="h-4 w-[250px]" />
          <Skeleton class="h-4 w-[200px]" />
        </div>
      </div>
    </section>

    <!-- 49. SLIDER -->
    <section
      v-show="match('slider')"
      class="space-y-4"
    >
      <div class="border-b pb-2">
        <h2 class="text-2xl font-semibold text-primary">49. Slider</h2>
        <p class="text-sm text-muted-foreground mt-1">
          Input interaktif (*slider*) tempat pengguna memilih rentang atau nilai tunggal di
          sepanjang batang horizontal/vertikal.
        </p>
      </div>
      <div class="p-6 border rounded-xl bg-card flex flex-col gap-6 items-center">
        <Slider
          v-model="sliderValue"
          :max="100"
          :step="1"
          class="w-[60%]"
        />
        <div class="text-sm font-medium">Nilai Terpilih: {{ sliderValue[0] }}</div>
      </div>
    </section>

    <!-- 50. SONNER -->
    <section
      v-show="match('sonner')"
      class="space-y-4"
    >
      <div class="border-b pb-2">
        <h2 class="text-2xl font-semibold text-primary">50. Sonner</h2>
        <p class="text-sm text-muted-foreground mt-1">
          Komponen notifikasi (*toast*) yang dirancang khusus untuk kenyamanan developer, dilengkapi
          *stacking* otomatis.
        </p>
      </div>
      <div class="p-6 border rounded-xl bg-card flex justify-center">
        <Button
          variant="outline"
          @click="toast('Event telah dibuat', { description: 'Senin, 18 Mei 2026 pukul 09:00' })"
        >
          Tampilkan Notifikasi (Toast)
        </Button>
      </div>
    </section>
  </div>
</template>
