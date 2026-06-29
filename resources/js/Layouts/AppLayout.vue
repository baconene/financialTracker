<template>
  <div class="min-h-screen flex bg-gray-50 dark:bg-[#0F0F23] transition-colors duration-300">
    <!-- Sidebar Overlay (mobile) -->
    <Transition name="fade">
      <div
        v-if="appStore.sidebarMobileOpen && !isDesktop"
        class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm"
        @click="appStore.closeMobileSidebar"
      />
    </Transition>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 flex flex-col w-64 transition-transform duration-300 ease-in-out',
        'bg-white dark:bg-[#1A1A2E] border-r border-gray-200 dark:border-white/10',
        sidebarTransform,
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 p-5 border-b border-gray-200 dark:border-white/10">
        <div class="w-9 h-9 rounded-xl gradient-primary flex items-center justify-center shadow-lg shrink-0">
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="min-w-0">
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
          <button @click="logout" class="text-gray-400 hover:text-red-500 transition-colors shrink-0" title="Logout">
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div
      :class="[
        'flex-1 flex flex-col min-w-0 transition-all duration-300',
        isDesktop && appStore.sidebarOpen ? 'ml-64' : '',
      ]"
    >
      <!-- Top bar -->
      <header class="sticky top-0 z-30 flex items-center gap-3 px-4 lg:px-6 py-3.5 bg-white/90 dark:bg-[#0F0F23]/90 backdrop-blur-md border-b border-gray-200 dark:border-white/10">
        <!-- Mobile menu toggle -->
        <button
          v-if="!isDesktop"
          class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white p-1"
          @click="appStore.toggleMobileSidebar"
          aria-label="Toggle menu"
        >
          <Bars3Icon class="w-6 h-6" />
        </button>

        <!-- Desktop sidebar toggle -->
        <button
          v-if="isDesktop"
          class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white p-1"
          @click="appStore.toggleSidebar"
          aria-label="Toggle sidebar"
        >
          <Bars3Icon class="w-6 h-6" />
        </button>

        <!-- Page title -->
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex-1 truncate">{{ title }}</h2>

        <!-- Actions -->
        <div class="flex items-center gap-2">
          <button
            @click="themeStore.toggleTheme"
            class="w-9 h-9 rounded-xl flex items-center justify-center bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors text-gray-600 dark:text-gray-300"
            :aria-label="themeStore.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
          >
            <SunIcon v-if="themeStore.isDark" class="w-5 h-5" />
            <MoonIcon v-else class="w-5 h-5" />
          </button>

          <div class="w-9 h-9 rounded-full gradient-primary flex items-center justify-center text-white font-semibold text-sm shrink-0">
            {{ userInitial }}
          </div>
        </div>
      </header>

      <!-- Flash messages -->
      <Transition name="slide-up">
        <div v-if="flashSuccess" class="mx-4 lg:mx-6 mt-4">
          <div class="flex items-center gap-3 px-4 py-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 rounded-xl">
            <CheckCircleIcon class="w-5 h-5 text-emerald-500 shrink-0" />
            <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ flashSuccess }}</p>
            <button @click="flashSuccess = ''" class="ml-auto text-emerald-500 hover:text-emerald-700">
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
    <nav
      v-if="!isDesktop"
      class="fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-[#1A1A2E] border-t border-gray-200 dark:border-white/10 flex items-center justify-around px-2 py-2"
    >
      <Link href="/dashboard" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-lg transition-colors" :class="isActive('/dashboard') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400'">
        <HomeIcon class="w-5 h-5" />
        <span class="text-[10px] font-medium">Home</span>
      </Link>
      <Link href="/transactions" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-lg transition-colors" :class="isActive('/transactions') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400'">
        <CreditCardIcon class="w-5 h-5" />
        <span class="text-[10px] font-medium">Txns</span>
      </Link>
      <Link href="/accounts" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-lg transition-colors" :class="isActive('/accounts') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400'">
        <BanknotesIcon class="w-5 h-5" />
        <span class="text-[10px] font-medium">Accounts</span>
      </Link>
      <Link href="/savings-goals" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-lg transition-colors" :class="isActive('/savings-goals') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400'">
        <ArchiveBoxIcon class="w-5 h-5" />
        <span class="text-[10px] font-medium">Savings</span>
      </Link>
      <Link href="/reports" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-lg transition-colors" :class="isActive('/reports') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400'">
        <ChartBarIcon class="w-5 h-5" />
        <span class="text-[10px] font-medium">Reports</span>
      </Link>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useMediaQuery } from '@vueuse/core'
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
    <Link :href="href"
      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 mb-0.5"
      :class="isCurrentActive(href)
        ? 'bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-400'
        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 hover:text-gray-900 dark:hover:text-white'"
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

// Use media query to know current breakpoint in JS — avoids Tailwind class conflicts
const isDesktop = useMediaQuery('(min-width: 1024px)')

// Compute the single correct transform class without conflicting lg: variants
const sidebarTransform = computed(() => {
  const visible = isDesktop.value ? appStore.sidebarOpen : appStore.sidebarMobileOpen
  return visible ? 'translate-x-0' : '-translate-x-full'
})

// Close mobile sidebar on every Inertia navigation
onMounted(() => {
  const unsubscribe = router.on('navigate', () => {
    appStore.closeMobileSidebar()
  })
  onUnmounted(unsubscribe)
})

// Also close mobile sidebar when switching to desktop
watch(isDesktop, (desktop) => {
  if (desktop) appStore.closeMobileSidebar()
})

const userInitial = computed(() => {
  const name = (page.props.auth as any)?.user?.name || 'U'
  return name.charAt(0).toUpperCase()
})

// Flash message
const flashSuccess = ref('')
watch(() => (page.props as any).flash?.success, (val) => {
  if (val) {
    flashSuccess.value = val
    setTimeout(() => { flashSuccess.value = '' }, 5000)
  }
}, { immediate: true })

function isActive(href: string): boolean {
  const path = page.url?.split('?')[0]
  if (href === '/dashboard') return path === '/dashboard'
  return path?.startsWith(href) ?? false
}

function logout() {
  router.post('/logout')
}
</script>
