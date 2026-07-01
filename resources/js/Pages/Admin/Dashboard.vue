<template>
  <div class="min-h-screen bg-slate-950 text-white flex flex-col">

    <!-- Top nav -->
    <header class="sticky top-0 z-30 bg-slate-900 border-b border-slate-700/50 shadow-lg shrink-0">
      <div class="max-w-screen-xl mx-auto px-4 h-14 flex items-center justify-between gap-3">
        <!-- Brand + nav -->
        <div class="flex items-center gap-3 shrink-0">
          <div class="w-8 h-8 rounded-xl flex items-center justify-center shadow"
            style="background: linear-gradient(135deg, #1e40af, #1d4ed8)">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
          </div>
          <nav class="flex items-center gap-1">
            <a href="/admin/dashboard"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600/20 text-blue-400 text-sm font-medium border border-blue-500/30">
              <UsersIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Users</span>
            </a>
            <a href="/admin/database"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-medium transition-all">
              <CircleStackIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Database</span>
            </a>
          </nav>
        </div>

        <!-- Right: admin name + logout -->
        <div class="flex items-center gap-2">
          <p class="text-sm font-medium text-white hidden sm:block">{{ auth.user?.name }}</p>
          <form @submit.prevent="logout">
            <button type="submit"
              class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-800 hover:bg-red-600/80 text-slate-300 hover:text-white text-sm font-medium transition-all border border-slate-700/50">
              <ArrowRightOnRectangleIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Sign out</span>
            </button>
          </form>
        </div>
      </div>
    </header>

    <main class="flex-1 max-w-screen-xl mx-auto w-full px-4 py-5 space-y-5 pb-8">

      <!-- Stats grid -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div v-for="stat in statCards" :key="stat.label"
          class="bg-slate-900 rounded-2xl p-4 border border-slate-700/50 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :style="{ background: stat.bg }">
            <component :is="stat.icon" class="w-5 h-5 text-white" />
          </div>
          <div class="min-w-0">
            <p class="text-xs text-slate-400 truncate">{{ stat.label }}</p>
            <p class="text-lg font-bold text-white leading-tight truncate">{{ stat.value }}</p>
          </div>
        </div>
      </div>

      <!-- Mobile tab switcher -->
      <div class="flex rounded-xl bg-slate-900 border border-slate-700/50 p-1 lg:hidden">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
          class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-sm font-medium transition-all"
          :class="activeTab === tab.id
            ? 'bg-blue-600 text-white shadow'
            : 'text-slate-400 hover:text-white'">
          <component :is="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
          <span v-if="tab.id === 'users'"
            class="text-xs px-1.5 py-0.5 rounded-full"
            :class="activeTab === 'users' ? 'bg-white/20 text-white' : 'bg-slate-700 text-slate-400'">
            {{ stats.total_users }}
          </span>
        </button>
      </div>

      <!-- Content area -->
      <div class="lg:grid lg:grid-cols-5 lg:gap-6 space-y-5 lg:space-y-0">

        <!-- ── USERS PANEL ── -->
        <div class="lg:col-span-3 bg-slate-900 rounded-2xl border border-slate-700/50 overflow-hidden"
          :class="activeTab !== 'users' ? 'hidden lg:block' : ''">

          <!-- Panel header -->
          <div class="px-4 py-3.5 border-b border-slate-700/50 flex items-center gap-3">
            <div class="flex-1">
              <h2 class="text-sm font-semibold text-white">Registered Users</h2>
              <p class="text-xs text-slate-400">{{ stats.total_users }} member{{ stats.total_users !== 1 ? 's' : '' }}</p>
            </div>
            <div class="relative">
              <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" />
              <input v-model="search" type="text" placeholder="Search…"
                class="pl-9 pr-3 py-2 text-sm bg-slate-800 border border-slate-600/50 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 w-36 sm:w-44" />
            </div>
          </div>

          <!-- User cards -->
          <div class="divide-y divide-slate-800">
            <div v-if="filteredUsers.length === 0" class="py-12 text-center text-slate-500 text-sm">
              No users found
            </div>

            <div v-for="user in filteredUsers" :key="user.id">
              <!-- User row -->
              <button class="w-full text-left px-4 py-4 hover:bg-slate-800/60 transition-colors active:bg-slate-800"
                @click="toggleUser(user.id)">
                <div class="flex items-center gap-3">
                  <!-- Avatar -->
                  <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                    :style="{ background: avatarBg(user.name) }">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </div>

                  <!-- Name + email -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 mb-0.5">
                      <p class="text-sm font-semibold text-white truncate">{{ user.name }}</p>
                      <span v-if="user.is_admin"
                        class="shrink-0 text-xs px-1.5 py-0.5 rounded-md bg-blue-500/20 text-blue-400 font-medium">
                        Admin
                      </span>
                    </div>
                    <p class="text-xs text-slate-400 truncate">{{ user.email }}</p>
                  </div>

                  <!-- Balance + chevron -->
                  <div class="flex items-center gap-2 shrink-0">
                    <div class="text-right">
                      <p class="text-sm font-bold text-emerald-400">{{ formatPHPShort(user.total_balance) }}</p>
                      <p class="text-xs text-slate-500">{{ user.accounts_count }} acct{{ user.accounts_count !== 1 ? 's' : '' }}</p>
                    </div>
                    <ChevronDownIcon class="w-4 h-4 text-slate-500 transition-transform duration-200 shrink-0"
                      :class="{ 'rotate-180': expandedUser === user.id }" />
                  </div>
                </div>

                <!-- Meta chips -->
                <div class="flex items-center gap-2 mt-2.5 ml-13 flex-wrap" style="margin-left: 52px">
                  <span class="text-xs text-slate-500 flex items-center gap-1">
                    <CalendarDaysIcon class="w-3 h-3" />
                    Joined {{ shortDate(user.created_at) }}
                  </span>
                  <span class="text-slate-700">·</span>
                  <span class="text-xs flex items-center gap-1"
                    :class="user.last_login_at ? 'text-slate-500' : 'text-slate-600'">
                    <ClockIcon class="w-3 h-3" />
                    {{ user.last_login_at ? relativeTime(user.last_login_at) : 'Never logged in' }}
                  </span>
                  <span class="text-slate-700">·</span>
                  <span class="text-xs text-slate-500">{{ user.transactions_count }} txns</span>
                </div>
              </button>

              <!-- Expanded accounts -->
              <div v-if="expandedUser === user.id" class="bg-slate-800/50 px-4 pb-3 pt-0">
                <p class="text-xs text-slate-500 uppercase tracking-wide py-2.5 pl-1">Accounts</p>
                <div v-if="user.accounts.length === 0" class="text-xs text-slate-600 pl-1 pb-1">No accounts</div>
                <div class="space-y-2">
                  <div v-for="acct in user.accounts" :key="acct.id"
                    class="flex items-center justify-between rounded-xl px-3 py-2.5 border border-slate-700/40"
                    :style="{ background: acct.color + '15' }">
                    <div class="flex items-center gap-2.5 min-w-0">
                      <div class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: acct.color }" />
                      <div class="min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ acct.name }}</p>
                        <p class="text-xs text-slate-400 capitalize">{{ acct.type }} · {{ acct.currency }}</p>
                      </div>
                    </div>
                    <span class="font-semibold text-sm shrink-0 ml-2" :style="{ color: acct.color }">
                      {{ formatPHP(acct.balance) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── ACTIVITY PANEL ── -->
        <div class="lg:col-span-2 bg-slate-900 rounded-2xl border border-slate-700/50 overflow-hidden flex flex-col"
          :class="activeTab !== 'activity' ? 'hidden lg:flex' : ''">

          <div class="px-4 py-3.5 border-b border-slate-700/50 shrink-0 flex items-center justify-between">
            <div>
              <h2 class="text-sm font-semibold text-white">Visiting Hours</h2>
              <p class="text-xs text-slate-400">Recent login activity</p>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
              <span class="text-xs text-emerald-400 font-medium">{{ stats.active_today }} today</span>
            </div>
          </div>

          <div class="overflow-y-auto" style="max-height: 60vh; min-height: 200px">
            <div v-if="recentActivity.length === 0"
              class="flex flex-col items-center justify-center h-40 gap-2 text-slate-500 text-sm">
              <ClockIcon class="w-8 h-8 text-slate-700" />
              No activity recorded yet
            </div>

            <div v-for="log in recentActivity" :key="log.id"
              class="flex items-start gap-3 px-4 py-3.5 border-b border-slate-800/80 hover:bg-slate-800/30 transition-colors">

              <!-- Avatar -->
              <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                :style="{ background: log.user ? avatarBg(log.user.name) : '#334155' }">
                {{ log.user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
              </div>

              <div class="flex-1 min-w-0">
                <!-- Name + status -->
                <div class="flex items-center gap-2 justify-between">
                  <p class="text-sm font-semibold text-white truncate">{{ log.user?.name ?? 'Unknown' }}</p>
                  <div class="flex items-center gap-1 shrink-0">
                    <span v-if="!log.logged_out_at"
                      class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                    <span class="text-xs font-medium"
                      :class="log.logged_out_at ? 'text-slate-400' : 'text-emerald-400'">
                      {{ log.logged_out_at ? log.duration ?? '' : 'Online' }}
                    </span>
                  </div>
                </div>

                <p class="text-xs text-slate-500 truncate mb-1.5">{{ log.user?.email }}</p>

                <!-- Time info -->
                <div class="bg-slate-800/60 rounded-lg px-2.5 py-1.5 space-y-1">
                  <div class="flex items-center gap-2 text-xs">
                    <ArrowRightCircleIcon class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                    <span class="text-slate-300">{{ formatTime(log.logged_in_at) }}</span>
                  </div>
                  <div v-if="log.logged_out_at" class="flex items-center gap-2 text-xs">
                    <ArrowLeftCircleIcon class="w-3.5 h-3.5 text-red-400 shrink-0" />
                    <span class="text-slate-400">{{ formatTime(log.logged_out_at) }}</span>
                  </div>
                  <div v-if="log.ip_address" class="flex items-center gap-2 text-xs">
                    <GlobeAltIcon class="w-3.5 h-3.5 text-slate-500 shrink-0" />
                    <span class="text-slate-500 font-mono">{{ log.ip_address }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  UsersIcon, CreditCardIcon, BanknotesIcon, ClockIcon,
  ArrowRightOnRectangleIcon, ChevronDownIcon, MagnifyingGlassIcon,
  CalendarDaysIcon, ArrowRightCircleIcon, ArrowLeftCircleIcon, GlobeAltIcon,
  CircleStackIcon,
} from '@heroicons/vue/24/outline'

interface AccountRow {
  id: number; name: string; type: string; balance: number; currency: string; color: string
}

interface UserRow {
  id: number; name: string; email: string; is_admin: boolean
  accounts_count: number; transactions_count: number; total_balance: number
  accounts: AccountRow[]; last_login_at: string | null; created_at: string
}

interface ActivityLog {
  id: number
  user: { id: number; name: string; email: string } | null
  logged_in_at: string; logged_out_at: string | null
  duration: string | null; ip_address: string | null
}

interface Stats {
  total_users: number; total_accounts: number; total_balance: number; active_today: number
}

const props = defineProps<{ users: UserRow[]; stats: Stats; recentActivity: ActivityLog[] }>()

const page = usePage()
const auth = (page.props as any).auth

// Mobile tab state
const activeTab = ref<'users' | 'activity'>('users')
const tabs = [
  { id: 'users' as const, label: 'Users', icon: UsersIcon },
  { id: 'activity' as const, label: 'Activity', icon: ClockIcon },
]

// Search
const search = ref('')
const filteredUsers = computed(() => {
  if (!search.value.trim()) return props.users
  const q = search.value.toLowerCase()
  return props.users.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q))
})

// Expand user row
const expandedUser = ref<number | null>(null)
function toggleUser(id: number) {
  expandedUser.value = expandedUser.value === id ? null : id
}

// Stats cards
const statCards = computed(() => [
  { label: 'Total Users', value: props.stats.total_users, icon: UsersIcon, bg: 'linear-gradient(135deg,#1e40af,#2563eb)' },
  { label: 'Accounts', value: props.stats.total_accounts, icon: CreditCardIcon, bg: 'linear-gradient(135deg,#065f46,#059669)' },
  { label: 'Total Balance', value: formatPHPShort(props.stats.total_balance), icon: BanknotesIcon, bg: 'linear-gradient(135deg,#7c2d12,#ea580c)' },
  { label: 'Active Today', value: props.stats.active_today, icon: ClockIcon, bg: 'linear-gradient(135deg,#4c1d95,#7c3aed)' },
])

// Formatting
function formatPHP(amount: number): string {
  return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatPHPShort(amount: number): string {
  if (Math.abs(amount) >= 1_000_000) return '₱' + (amount / 1_000_000).toFixed(1) + 'M'
  if (Math.abs(amount) >= 1_000) return '₱' + (amount / 1_000).toFixed(1) + 'k'
  return '₱' + amount.toFixed(2)
}

function formatTime(iso: string): string {
  const d = new Date(iso)
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
    + ' ' + d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })
}

function shortDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

function relativeTime(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'Just now'
  if (mins < 60) return `${mins}m ago`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs}h ago`
  return `${Math.floor(hrs / 24)}d ago`
}

const AVATAR_COLORS = [
  'linear-gradient(135deg,#1e40af,#2563eb)',
  'linear-gradient(135deg,#065f46,#059669)',
  'linear-gradient(135deg,#7c2d12,#dc2626)',
  'linear-gradient(135deg,#4c1d95,#7c3aed)',
  'linear-gradient(135deg,#713f12,#d97706)',
  'linear-gradient(135deg,#831843,#db2777)',
]
function avatarBg(name: string): string {
  let h = 0
  for (let i = 0; i < name.length; i++) h = name.charCodeAt(i) + ((h << 5) - h)
  return AVATAR_COLORS[Math.abs(h) % AVATAR_COLORS.length]
}

function logout() { router.post('/admin/logout') }
</script>
