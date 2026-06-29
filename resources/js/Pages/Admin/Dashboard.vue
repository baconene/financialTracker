<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <!-- Top nav -->
    <header class="sticky top-0 z-30 bg-slate-900 border-b border-slate-700/50 shadow-lg">
      <div class="max-w-screen-xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <!-- Brand -->
        <div class="flex items-center gap-3 shrink-0">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center shadow"
            style="background: linear-gradient(135deg, #1e40af, #1d4ed8)">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
          </div>
          <div class="hidden sm:block">
            <p class="text-sm font-bold text-white leading-none">FinanceTracker</p>
            <p class="text-xs text-blue-400 font-medium">Admin Panel</p>
          </div>
        </div>

        <!-- Right -->
        <div class="flex items-center gap-3">
          <div class="hidden sm:block text-right">
            <p class="text-xs text-slate-400">Logged in as</p>
            <p class="text-sm font-semibold text-white">{{ auth.user?.name }}</p>
          </div>
          <form @submit.prevent="logout">
            <button type="submit"
              class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-700 hover:bg-red-600/80 text-slate-300 hover:text-white text-sm font-medium transition-all">
              <ArrowRightOnRectangleIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Sign out</span>
            </button>
          </form>
        </div>
      </div>
    </header>

    <main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-8 space-y-8">

      <!-- Stats row -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="stat in statCards" :key="stat.label"
          class="bg-slate-900 rounded-2xl p-5 border border-slate-700/50 flex flex-col gap-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-400 uppercase tracking-wide">{{ stat.label }}</span>
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" :style="{ background: stat.bg }">
              <component :is="stat.icon" class="w-5 h-5 text-white" />
            </div>
          </div>
          <p class="text-2xl font-bold text-white">{{ stat.value }}</p>
        </div>
      </div>

      <!-- Two-column grid: Users table + Activity log -->
      <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

        <!-- Users Table (wider) -->
        <div class="xl:col-span-3 bg-slate-900 rounded-2xl border border-slate-700/50 overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-slate-700/50">
            <div>
              <h2 class="text-base font-semibold text-white">Registered Users</h2>
              <p class="text-xs text-slate-400 mt-0.5">{{ stats.total_users }} user{{ stats.total_users !== 1 ? 's' : '' }}</p>
            </div>
            <!-- Search -->
            <div class="relative">
              <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" />
              <input v-model="search" type="text" placeholder="Search…"
                class="pl-9 pr-3 py-2 text-sm bg-slate-800 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 w-40" />
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-700/50">
                  <th class="text-left px-5 py-3 text-xs font-medium text-slate-400 uppercase tracking-wide">User</th>
                  <th class="text-right px-4 py-3 text-xs font-medium text-slate-400 uppercase tracking-wide hidden sm:table-cell">Accounts</th>
                  <th class="text-right px-4 py-3 text-xs font-medium text-slate-400 uppercase tracking-wide">Balance</th>
                  <th class="text-center px-4 py-3 text-xs font-medium text-slate-400 uppercase tracking-wide hidden md:table-cell">Last Login</th>
                  <th class="px-2 py-3 w-8"></th>
                </tr>
              </thead>
              <tbody>
                <template v-for="user in filteredUsers" :key="user.id">
                  <!-- Main row -->
                  <tr class="border-b border-slate-800 hover:bg-slate-800/50 transition-colors cursor-pointer"
                    @click="toggleUser(user.id)">
                    <td class="px-5 py-3.5">
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                          :style="{ background: avatarBg(user.name) }">
                          {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0">
                          <div class="flex items-center gap-1.5">
                            <p class="font-medium text-white truncate">{{ user.name }}</p>
                            <span v-if="user.is_admin"
                              class="shrink-0 text-xs px-1.5 py-0.5 rounded-md bg-blue-500/20 text-blue-400 font-medium">Admin</span>
                          </div>
                          <p class="text-xs text-slate-400 truncate">{{ user.email }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3.5 text-right hidden sm:table-cell">
                      <span class="text-slate-300">{{ user.accounts_count }}</span>
                      <span class="text-slate-500 text-xs ml-1">accts</span>
                    </td>
                    <td class="px-4 py-3.5 text-right">
                      <span class="font-semibold text-emerald-400">{{ formatPHP(user.total_balance) }}</span>
                    </td>
                    <td class="px-4 py-3.5 text-center hidden md:table-cell">
                      <span v-if="user.last_login_at" class="text-xs text-slate-400">{{ relativeTime(user.last_login_at) }}</span>
                      <span v-else class="text-xs text-slate-600">Never</span>
                    </td>
                    <td class="px-2 py-3.5">
                      <ChevronDownIcon class="w-4 h-4 text-slate-500 transition-transform duration-200"
                        :class="{ 'rotate-180': expandedUser === user.id }" />
                    </td>
                  </tr>
                  <!-- Expanded accounts -->
                  <tr v-if="expandedUser === user.id && user.accounts.length" class="bg-slate-800/40">
                    <td colspan="5" class="px-5 pb-3 pt-2">
                      <div class="space-y-1.5 pl-11">
                        <p class="text-xs text-slate-500 uppercase tracking-wide mb-2">Accounts</p>
                        <div v-for="acct in user.accounts" :key="acct.id"
                          class="flex items-center justify-between rounded-xl px-4 py-2.5 border border-slate-700/50"
                          :style="{ background: acct.color + '18' }">
                          <div class="flex items-center gap-2.5">
                            <div class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: acct.color }" />
                            <div>
                              <p class="text-sm font-medium text-white">{{ acct.name }}</p>
                              <p class="text-xs text-slate-400 capitalize">{{ acct.type }} · {{ acct.currency }}</p>
                            </div>
                          </div>
                          <span class="font-semibold text-sm" :style="{ color: acct.color }">
                            {{ formatPHP(acct.balance) }}
                          </span>
                        </div>
                        <div v-if="!user.accounts.length" class="text-xs text-slate-500 py-1">No accounts</div>
                      </div>
                    </td>
                  </tr>
                </template>
                <tr v-if="filteredUsers.length === 0">
                  <td colspan="5" class="px-5 py-10 text-center text-slate-500 text-sm">No users found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Activity Log -->
        <div class="xl:col-span-2 bg-slate-900 rounded-2xl border border-slate-700/50 overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-slate-700/50 shrink-0">
            <h2 class="text-base font-semibold text-white">Visiting Hours</h2>
            <p class="text-xs text-slate-400 mt-0.5">Recent login activity</p>
          </div>

          <div class="overflow-y-auto flex-1" style="max-height: 520px">
            <div v-if="recentActivity.length === 0" class="flex items-center justify-center h-40 text-slate-500 text-sm">
              No activity recorded yet
            </div>
            <div v-for="log in recentActivity" :key="log.id"
              class="flex items-start gap-3 px-5 py-3.5 border-b border-slate-800 hover:bg-slate-800/40 transition-colors">
              <!-- Avatar dot -->
              <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5"
                :style="{ background: log.user ? avatarBg(log.user.name) : '#334155' }">
                {{ log.user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <p class="text-sm font-medium text-white truncate">{{ log.user?.name ?? 'Unknown' }}</p>
                  <span v-if="log.duration" class="shrink-0 text-xs text-slate-400 font-mono">{{ log.duration }}</span>
                  <span v-else class="shrink-0 w-2 h-2 rounded-full bg-emerald-400 animate-pulse" title="Online" />
                </div>
                <p class="text-xs text-slate-500 truncate">{{ log.user?.email }}</p>
                <div class="flex items-center gap-2 mt-1 flex-wrap">
                  <span class="text-xs text-slate-400">
                    <ClockIcon class="w-3 h-3 inline mr-0.5 -mt-0.5" />
                    {{ formatTime(log.logged_in_at) }}
                  </span>
                  <span v-if="log.logged_out_at" class="text-xs text-slate-600">→ {{ formatTime(log.logged_out_at) }}</span>
                  <span v-else class="text-xs text-emerald-500 font-medium">Active</span>
                  <span v-if="log.ip_address" class="text-xs text-slate-600 font-mono">{{ log.ip_address }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <p class="text-center text-xs text-slate-600 pb-4">
        FinanceTracker Admin · {{ new Date().getFullYear() }}
      </p>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  UsersIcon, CreditCardIcon, BanknotesIcon, ClockIcon,
  ArrowRightOnRectangleIcon, ChevronDownIcon, MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline'

interface AccountRow {
  id: number
  name: string
  type: string
  balance: number
  currency: string
  color: string
}

interface UserRow {
  id: number
  name: string
  email: string
  is_admin: boolean
  accounts_count: number
  transactions_count: number
  total_balance: number
  accounts: AccountRow[]
  last_login_at: string | null
  created_at: string
}

interface ActivityLog {
  id: number
  user: { id: number; name: string; email: string } | null
  logged_in_at: string
  logged_out_at: string | null
  duration: string | null
  ip_address: string | null
}

interface Stats {
  total_users: number
  total_accounts: number
  total_balance: number
  active_today: number
}

const props = defineProps<{
  users: UserRow[]
  stats: Stats
  recentActivity: ActivityLog[]
}>()

const page = usePage()
const auth = (page.props as any).auth

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
  {
    label: 'Total Users',
    value: props.stats.total_users,
    icon: UsersIcon,
    bg: 'linear-gradient(135deg, #1e40af, #2563eb)',
  },
  {
    label: 'Total Accounts',
    value: props.stats.total_accounts,
    icon: CreditCardIcon,
    bg: 'linear-gradient(135deg, #065f46, #059669)',
  },
  {
    label: 'System Balance',
    value: formatPHP(props.stats.total_balance),
    icon: BanknotesIcon,
    bg: 'linear-gradient(135deg, #7c2d12, #ea580c)',
  },
  {
    label: 'Active Today',
    value: props.stats.active_today,
    icon: ClockIcon,
    bg: 'linear-gradient(135deg, #4c1d95, #7c3aed)',
  },
])

// Formatting
function formatPHP(amount: number): string {
  return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatTime(iso: string): string {
  const d = new Date(iso)
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
    + ' ' + d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })
}

function relativeTime(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'Just now'
  if (mins < 60) return `${mins}m ago`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs}h ago`
  const days = Math.floor(hrs / 24)
  return `${days}d ago`
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
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length]
}

// Logout
function logout() {
  router.post('/admin/logout')
}
</script>
