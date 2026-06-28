<template>
  <div class="min-h-screen flex bg-gray-50 dark:bg-[#0F0F23] transition-colors duration-300">
    <!-- Sidebar Overlay (mobile) -->
    <Transition name="fade">
      <div
        v-if="appStore.sidebarMobileOpen"
        class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
        @click="appStore.closeMobileSidebar"
      />
    </Transition>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 flex flex-col w-64 transition-transform duration-300 ease-in-out',
        'bg-white dark:bg-[#1A1A2E] border-r border-gray-200 dark:border-white/10',
        appStore.sidebarMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        !appStore.sidebarOpen ? 'lg:-translate-x-full' : 'lg:translate-x-0',
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 p-6 border-b border-gray-200 dark:border-white/10">
        <div class="w-9 h-9 rounded-xl gradient-primary flex items-center justify-center shadow-lg">
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <h1 class="text-lg font-bold gradient-text">FinanceTracker</h1>
          <p class="text-xs text-gray-500 dark:text-gray-400">Personal Finance</p>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 px-3">
        <div class="mb-4">
          <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Main</p>
          <NavLink href="/dashboard" :icon="HomeIcon" label="Dashboard" />
          <NavLink href="/transactions" :icon="CreditCardIcon" label="Transactions" />
          <NavLink href="/accounts" :icon="BanknotesIcon" label="Accounts" />
        </div>

        <div class="mb-4">
          <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Finance</p>
          <NavLink href="/savings-goals" :icon="ArchiveBoxIcon" label="Savings Goals" />
          <NavLink href="/loans" :icon="DocumentTextIcon" label="Loans" />
          <NavLink href="/bills" :icon="CalendarIcon" label="Bills" />
          <NavLink href="/budgets" :icon="ChartPieIcon" label="Budgets" />
        </div>

        <div class="mb-4">
          <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Analytics</p>
          <NavLink href="/reports" :icon="ChartBarIcon" label="Reports" />
        </div>
      </nav>

      <!-- User Section -->
      <div class="p-4 border-t border-gray-200 dark:border-white/10">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-100 dark:bg-white/5">
          <div class="w-9 h-9 rounded-full gradient-primary flex items-center justify-center text-white font-semibold text-sm shrink-0">
            {{ userInitial }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $page.props.auth.user?.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $page.props.auth.user?.email }}</p>
          </div>
          <button @click="logout" class="text-gray-400 hover:text-red-500 transition-colors">
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div :class="['flex-1 flex flex-col min-w-0 transition-all duration-300', appStore.sidebarOpen ? 'lg:ml-64' : '']">
      <!-- Top bar -->
      <header class="sticky top-0 z-30 flex items-center gap-4 px-4 lg:px-6 py-4 bg-white/80 dark:bg-[#0F0F23]/80 backdrop-blur-md border-b border-gray-200 dark:border-white/10">
        <!-- Mobile menu toggle -->
        <button
          class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white"
          @click="appStore.toggleMobileSidebar"
        >
          <Bars3Icon class="w-6 h-6" />
        </button>

        <!-- Desktop sidebar toggle -->
        <button
          class="hidden lg:block text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white"
          @click="appStore.toggleSidebar"
        >
          <Bars3Icon class="w-6 h-6" />
        </button>

        <!-- Page title -->
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex-1">{{ title }}</h2>

        <!-- Actions -->
        <div class="flex items-center gap-2">
          <!-- Theme toggle -->
          <button
            @click="themeStore.toggleTheme"
            class="w-9 h-9 rounded-xl flex items-center justify-center bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors text-gray-600 dark:text-gray-300"
          >
            <SunIcon v-if="themeStore.isDark" class="w-5 h-5" />
            <MoonIcon v-else class="w-5 h-5" />
          </button>

          <!-- User avatar -->
          <div class="w-9 h-9 rounded-full gradient-primary flex items-center justify-center text-white font-semibold text-sm">
            {{ userInitial }}
          </div>
        </div>
      </header>

      <!-- Flash messages -->
      <Transition name="slide-up">
        <div v-if="flash.success" class="mx-4 lg:mx-6 mt-4">
          <div class="flex items-center gap-3 px-4 py-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 rounded-xl">
            <CheckCircleIcon class="w-5 h-5 text-emerald-500 shrink-0" />
            <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ flash.success }}</p>
            <button @click="dismissFlash" class="ml-auto text-emerald-500 hover:text-emerald-700">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </Transition>

      <!-- Page content -->
      <main class="flex-1 p-4 lg:p-6 pb-24 lg:pb-6">
        <slot />
      </main>
    </div>

    <!-- Mobile Bottom Nav -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-[#1A1A2E] border-t border-gray-200 dark:border-white/10 flex items-center justify-around px-2 py-2 safe-area-pb">
      <Link href="/dashboard" class="flex flex-col items-center gap-1 px-3 py-1 rounded-lg transition-colors" :class="isActive('/dashboard') ? 'text-violet-600' : 'text-gray-500 dark:text-gray-400'">
        <HomeIcon class="w-6 h-6" />
        <span class="text-xs">Home</span>
      </Link>
      <Link href="/transactions" class="flex flex-col items-center gap-1 px-3 py-1 rounded-lg transition-colors" :class="isActive('/transactions') ? 'text-violet-600' : 'text-gray-500 dark:text-gray-400'">
        <CreditCardIcon class="w-6 h-6" />
        <span class="text-xs">Txns</span>
      </Link>
      <Link href="/savings-goals" class="flex flex-col items-center gap-1 px-3 py-1 rounded-lg transition-colors" :class="isActive('/savings-goals') ? 'text-violet-600' : 'text-gray-500 dark:text-gray-400'">
        <ArchiveBoxIcon class="w-6 h-6" />
        <span class="text-xs">Savings</span>
      </Link>
      <Link href="/bills" class="flex flex-col items-center gap-1 px-3 py-1 rounded-lg transition-colors" :class="isActive('/bills') ? 'text-violet-600' : 'text-gray-500 dark:text-gray-400'">
        <CalendarIcon class="w-6 h-6" />
        <span class="text-xs">Bills</span>
      </Link>
      <Link href="/reports" class="flex flex-col items-center gap-1 px-3 py-1 rounded-lg transition-colors" :class="isActive('/reports') ? 'text-violet-600' : 'text-gray-500 dark:text-gray-400'">
        <ChartBarIcon class="w-6 h-6" />
        <span class="text-xs">Reports</span>
      </Link>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import {
  HomeIcon, CreditCardIcon, BanknotesIcon, ArchiveBoxIcon,
  DocumentTextIcon, CalendarIcon, ChartPieIcon, ChartBarIcon,
  Bars3Icon, SunIcon, MoonIcon, ArrowRightOnRectangleIcon,
  CheckCircleIcon, XMarkIcon
} from '@heroicons/vue/24/outline'

// Nav Link Component (inline)
const NavLink = {
  props: ['href', 'icon', 'label'],
  template: `
    <Link :href="href" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group mb-1"
      :class="isCurrentActive(href) ? 'bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 hover:text-gray-900 dark:hover:text-white'"
    >
      <component :is="icon" class="w-5 h-5 shrink-0" />
      <span>{{ label }}</span>
    </Link>
  `,
  setup(props: { href: string }) {
    const page = usePage()
    const isCurrentActive = (href: string) => {
      const path = page.url?.split('?')[0]
      if (href === '/dashboard') return path === '/dashboard'
      return path?.startsWith(href)
    }
    return { isCurrentActive, Link }
  },
  components: { Link },
}

interface Props {
  title?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Dashboard',
})

const appStore = useAppStore()
const themeStore = useThemeStore()
const page = usePage()

const userInitial = computed(() => {
  const name = (page.props.auth as any)?.user?.name || 'U'
  return name.charAt(0).toUpperCase()
})

const flash = computed(() => (page.props as any).flash || {})
const flashVisible = ref(true)

watch(() => flash.value.success, () => {
  flashVisible.value = true
  setTimeout(() => { flashVisible.value = false }, 5000)
})

function dismissFlash() {
  flashVisible.value = false
}

function isActive(href: string): boolean {
  const path = page.url?.split('?')[0]
  if (href === '/dashboard') return path === '/dashboard'
  return path?.startsWith(href)
}

function logout() {
  router.post('/logout')
}
</script>
