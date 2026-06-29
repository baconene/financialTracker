<template>
  <div class="min-h-screen flex bg-gray-50 dark:bg-[#0F0F23] transition-colors duration-300">

    <!-- Mobile sidebar overlay -->
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
        'fixed inset-y-0 left-0 z-50 flex flex-col w-64',
        'bg-white dark:bg-[#1A1A2E] border-r border-gray-200 dark:border-white/10',
        'transition-transform duration-300 ease-in-out',
        sidebarTransform,
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-200 dark:border-white/10 shrink-0">
        <div class="w-9 h-9 rounded-xl gradient-primary flex items-center justify-center shadow-lg shrink-0">
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <h1 class="text-lg font-bold gradient-text">FinanceTracker</h1>
          <p class="text-xs text-gray-500 dark:text-gray-400">Personal Finance</p>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-4">
        <div>
          <p class="px-3 text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Main</p>
          <Link href="/dashboard" :class="navClass('/dashboard')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors mb-0.5">
            <HomeIcon class="w-5 h-5 shrink-0" /><span>Dashboard</span>
          </Link>
          <Link href="/transactions" :class="navClass('/transactions')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors mb-0.5">
            <CreditCardIcon class="w-5 h-5 shrink-0" /><span>Transactions</span>
          </Link>
          <Link href="/accounts" :class="navClass('/accounts')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors mb-0.5">
            <BanknotesIcon class="w-5 h-5 shrink-0" /><span>Accounts</span>
          </Link>
        </div>

        <div>
          <p class="px-3 text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Finance</p>
          <Link href="/savings-goals" :class="navClass('/savings-goals')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors mb-0.5">
            <ArchiveBoxIcon class="w-5 h-5 shrink-0" /><span>Savings Goals</span>
          </Link>
          <Link href="/loans" :class="navClass('/loans')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors mb-0.5">
            <DocumentTextIcon class="w-5 h-5 shrink-0" /><span>Loans</span>
          </Link>
          <Link href="/bills" :class="navClass('/bills')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors mb-0.5">
            <CalendarIcon class="w-5 h-5 shrink-0" /><span>Bills</span>
          </Link>
          <Link href="/budgets" :class="navClass('/budgets')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors mb-0.5">
            <ChartPieIcon class="w-5 h-5 shrink-0" /><span>Budgets</span>
          </Link>
        </div>

        <div>
          <p class="px-3 text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Analytics</p>
          <Link href="/reports" :class="navClass('/reports')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors mb-0.5">
            <ChartBarIcon class="w-5 h-5 shrink-0" /><span>Reports</span>
          </Link>
        </div>

        <div>
          <p class="px-3 text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Profile</p>
          <Link href="/income-sources" :class="navClass('/income-sources')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors mb-0.5">
            <BriefcaseIcon class="w-5 h-5 shrink-0" /><span>Income Sources</span>
          </Link>
        </div>

        <div>
          <p class="px-3 text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Settings</p>
          <Link href="/settings/financial" :class="navClass('/settings/financial')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors mb-0.5">
            <AdjustmentsHorizontalIcon class="w-5 h-5 shrink-0" /><span>Financial Settings</span>
          </Link>
        </div>
      </nav>

      <!-- User section -->
      <div class="p-4 border-t border-gray-200 dark:border-white/10 shrink-0">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-100 dark:bg-white/5">
          <div class="w-9 h-9 rounded-full gradient-primary flex items-center justify-center text-white font-semibold text-sm shrink-0">
            {{ userInitial }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ authUser?.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ authUser?.email }}</p>
          </div>
          <button @click="logout" class="text-gray-400 hover:text-red-500 transition-colors shrink-0" title="Logout">
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main content area -->
    <div
      :class="[
        'flex-1 flex flex-col min-w-0 transition-all duration-300',
        isDesktop && appStore.sidebarOpen ? 'ml-64' : '',
      ]"
    >
      <!-- Top bar -->
      <header class="sticky top-0 z-30 flex items-center gap-3 px-4 lg:px-6 py-3 bg-white/90 dark:bg-[#0F0F23]/90 backdrop-blur-md border-b border-gray-200 dark:border-white/10">
        <!-- Sidebar toggle -->
        <button
          class="w-9 h-9 rounded-xl flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:hover:bg-white/10 hover:text-gray-700 dark:hover:text-white transition-colors"
          @click="isDesktop ? appStore.toggleSidebar() : appStore.toggleMobileSidebar()"
          aria-label="Toggle menu"
        >
          <Bars3Icon class="w-5 h-5" />
        </button>

        <!-- Page title -->
        <h2 class="text-base font-semibold text-gray-900 dark:text-white flex-1 truncate">{{ title }}</h2>

        <!-- Right actions -->
        <div class="flex items-center gap-2">
          <!-- Theme toggle -->
          <button
            @click="themeStore.toggleTheme()"
            class="w-9 h-9 rounded-xl flex items-center justify-center bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors text-gray-600 dark:text-gray-300"
            :aria-label="themeStore.isDark ? 'Light mode' : 'Dark mode'"
          >
            <SunIcon v-if="themeStore.isDark" class="w-4.5 h-4.5" />
            <MoonIcon v-else class="w-4.5 h-4.5" />
          </button>

          <!-- Profile dropdown -->
          <div class="relative" ref="profileRef">
            <button
              @click="profileOpen = !profileOpen"
              class="w-9 h-9 rounded-full gradient-primary flex items-center justify-center text-white font-semibold text-sm hover:opacity-90 transition-opacity"
            >
              {{ userInitial }}
            </button>

            <Transition name="fade">
              <div
                v-if="profileOpen"
                class="absolute right-0 top-11 w-56 bg-white dark:bg-[#1A1A2E] rounded-xl shadow-xl border border-gray-200 dark:border-white/10 z-50 overflow-hidden"
              >
                <div class="px-4 py-3 border-b border-gray-100 dark:border-white/10">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ authUser?.name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ authUser?.email }}</p>
                </div>
                <div class="p-2">
                  <button
                    @click="themeStore.toggleTheme(); profileOpen = false"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                  >
                    <SunIcon v-if="themeStore.isDark" class="w-4 h-4" />
                    <MoonIcon v-else class="w-4 h-4" />
                    {{ themeStore.isDark ? 'Light Mode' : 'Dark Mode' }}
                  </button>
                  <button
                    @click="logout"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                  >
                    <ArrowRightOnRectangleIcon class="w-4 h-4" />
                    Sign Out
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </header>

      <!-- Flash message -->
      <Transition name="slide-up">
        <div v-if="flashSuccess" class="mx-4 lg:mx-6 mt-4">
          <div class="flex items-center gap-3 px-4 py-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 rounded-xl">
            <CheckCircleIcon class="w-5 h-5 text-emerald-500 shrink-0" />
            <p class="text-sm text-emerald-700 dark:text-emerald-400 flex-1">{{ flashSuccess }}</p>
            <button @click="flashSuccess = ''" class="text-emerald-500 hover:text-emerald-700 shrink-0">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </Transition>

      <!-- Page content -->
      <main class="flex-1 p-4 lg:p-6 pb-28 lg:pb-6">
        <slot />
      </main>
    </div>

    <!-- Desktop floating Add Transaction button (hidden on mobile — handled by bottom nav) -->
    <Link
      href="/transactions?create=1"
      class="hidden lg:flex fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full gradient-primary shadow-lg items-center justify-center text-white hover:opacity-90 hover:scale-105 active:scale-95 transition-all duration-150"
      aria-label="Add transaction"
    >
      <PlusIcon class="w-7 h-7 stroke-2" />
    </Link>

    <!-- Mobile bottom nav -->
    <nav
      v-if="!isDesktop"
      class="fixed bottom-0 inset-x-0 z-40 bg-white dark:bg-[#1A1A2E] border-t border-gray-200 dark:border-white/10"
    >
      <div class="grid grid-cols-7 items-center py-1.5">
        <Link href="/dashboard" class="flex flex-col items-center gap-0.5 py-1 transition-colors"
          :class="isActive('/dashboard') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-400 dark:text-gray-500'">
          <HomeIcon class="w-5 h-5" />
          <span class="text-[9px] font-medium">Home</span>
        </Link>
        <Link href="/transactions" class="flex flex-col items-center gap-0.5 py-1 transition-colors"
          :class="isActive('/transactions') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-400 dark:text-gray-500'">
          <CreditCardIcon class="w-5 h-5" />
          <span class="text-[9px] font-medium">Txns</span>
        </Link>
        <Link href="/bills" class="flex flex-col items-center gap-0.5 py-1 transition-colors"
          :class="isActive('/bills') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-400 dark:text-gray-500'">
          <CalendarIcon class="w-5 h-5" />
          <span class="text-[9px] font-medium">Bills</span>
        </Link>

        <!-- Center + (col 4 of 7 = exact center) -->
        <Link href="/transactions?create=1"
          class="flex flex-col items-center gap-0.5 py-1"
          aria-label="Add transaction">
          <div class="w-11 h-11 rounded-full gradient-primary shadow-md flex items-center justify-center text-white active:scale-90 transition-transform">
            <PlusIcon class="w-6 h-6 stroke-2" />
          </div>
        </Link>

        <Link href="/accounts" class="flex flex-col items-center gap-0.5 py-1 transition-colors"
          :class="isActive('/accounts') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-400 dark:text-gray-500'">
          <BanknotesIcon class="w-5 h-5" />
          <span class="text-[9px] font-medium">Acct</span>
        </Link>
        <Link href="/savings-goals" class="flex flex-col items-center gap-0.5 py-1 transition-colors"
          :class="isActive('/savings-goals') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-400 dark:text-gray-500'">
          <ArchiveBoxIcon class="w-5 h-5" />
          <span class="text-[9px] font-medium">Svngs</span>
        </Link>
        <Link href="/reports" class="flex flex-col items-center gap-0.5 py-1 transition-colors"
          :class="isActive('/reports') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-400 dark:text-gray-500'">
          <ChartBarIcon class="w-5 h-5" />
          <span class="text-[9px] font-medium">Rprt</span>
        </Link>
      </div>
    </nav>

  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useMediaQuery, onClickOutside } from '@vueuse/core'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import {
  HomeIcon, CreditCardIcon, BanknotesIcon, ArchiveBoxIcon,
  DocumentTextIcon, CalendarIcon, ChartPieIcon, ChartBarIcon,
  Bars3Icon, SunIcon, MoonIcon, ArrowRightOnRectangleIcon,
  CheckCircleIcon, XMarkIcon, AdjustmentsHorizontalIcon, BriefcaseIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'

withDefaults(defineProps<{ title?: string }>(), { title: 'Dashboard' })

const appStore = useAppStore()
const themeStore = useThemeStore()
const page = usePage()

// Reactive desktop breakpoint — avoids Tailwind lg: class conflicts
const isDesktop = useMediaQuery('(min-width: 1024px)')

// Compute one transform class based on current breakpoint state
const sidebarTransform = computed(() =>
  (isDesktop.value ? appStore.sidebarOpen : appStore.sidebarMobileOpen)
    ? 'translate-x-0'
    : '-translate-x-full'
)

// Close mobile sidebar on navigation and on resize to desktop
onMounted(() => {
  const off = router.on('navigate', () => appStore.closeMobileSidebar())
  onUnmounted(off)
})
watch(isDesktop, (val) => { if (val) appStore.closeMobileSidebar() })

// Auth user
const authUser = computed(() => (page.props.auth as any)?.user)
const userInitial = computed(() => (authUser.value?.name ?? 'U').charAt(0).toUpperCase())

// Active nav helper
function navClass(href: string): string {
  const path = page.url?.split('?')[0] ?? ''
  const active = href === '/dashboard' ? path === '/dashboard' : path.startsWith(href)
  return active
    ? 'bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-400'
    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 hover:text-gray-900 dark:hover:text-white'
}

function isActive(href: string): boolean {
  const path = page.url?.split('?')[0] ?? ''
  return href === '/dashboard' ? path === '/dashboard' : path.startsWith(href)
}

// Profile dropdown
const profileOpen = ref(false)
const profileRef = ref<HTMLElement>()
onClickOutside(profileRef, () => { profileOpen.value = false })

// Flash message
const flashSuccess = ref('')
watch(
  () => (page.props as any).flash?.success,
  (val: string) => {
    if (val) {
      flashSuccess.value = val
      setTimeout(() => { flashSuccess.value = '' }, 5000)
    }
  },
  { immediate: true }
)

function logout() {
  profileOpen.value = false
  router.post('/logout')
}
</script>
