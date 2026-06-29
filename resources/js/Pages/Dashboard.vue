<template>
  <AppLayout title="Dashboard">
    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard
        label="Total Balance"
        :value="formatPHP(stats.totalBalance)"
        :icon="BanknotesIcon"
        gradient="gradient-primary"
        change="+₱5,200"
        positive
      />
      <StatCard
        label="Monthly Income"
        :value="formatPHP(stats.monthlyIncome)"
        :icon="ArrowTrendingUpIcon"
        gradient="gradient-success"
        change="+12%"
        positive
      />
      <StatCard
        label="Monthly Expenses"
        :value="formatPHP(stats.monthlyExpenses)"
        :icon="ArrowTrendingDownIcon"
        gradient="gradient-danger"
        change="-3%"
        :positive="false"
      />
      <StatCard
        label="Total Savings"
        :value="formatPHP(stats.totalSavings)"
        :icon="ArchiveBoxIcon"
        gradient="gradient-warning"
        change="+₱3,000"
        positive
      />
    </div>

    <!-- Financial Health Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
      <!-- Health Score -->
      <div class="col-span-2 sm:col-span-1 bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4 flex items-center gap-3">
        <div class="relative w-14 h-14 shrink-0">
          <svg class="w-14 h-14 -rotate-90" viewBox="0 0 56 56">
            <circle cx="28" cy="28" r="22" fill="none" stroke="currentColor" stroke-width="5" class="text-gray-100 dark:text-white/10" />
            <circle cx="28" cy="28" r="22" fill="none" :stroke="healthColor" stroke-width="5" stroke-linecap="round"
              :stroke-dasharray="`${stats.healthScore * 1.382} 138.2`" />
          </svg>
          <span class="absolute inset-0 flex items-center justify-center text-xs font-bold" :style="{ color: healthColor }">{{ stats.healthScore }}</span>
        </div>
        <div>
          <p class="text-xs text-gray-500 dark:text-gray-400">Health Score</p>
          <p class="text-sm font-bold" :style="{ color: healthColor }">{{ healthLabel }}</p>
          <a href="/reports" class="text-[10px] text-violet-500 hover:underline">See insights →</a>
        </div>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Disposable Income</p>
        <p class="text-base font-bold" :class="stats.disposableIncome >= 0 ? 'text-violet-600 dark:text-violet-400' : 'text-red-500'">{{ formatPHP(stats.disposableIncome) }}</p>
        <p class="text-[10px] text-gray-400 mt-0.5">After expenses</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Outstanding Loans</p>
        <p class="text-base font-bold text-red-600 dark:text-red-400">{{ formatPHP(stats.outstandingLoans) }}</p>
        <p class="text-[10px] text-gray-400 mt-0.5">{{ stats.debtToIncome.toFixed(1) }}% of income</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Emergency Fund</p>
        <p class="text-base font-bold text-blue-600 dark:text-blue-400">{{ stats.emergencyFundMonths }}mo</p>
        <p class="text-[10px] text-gray-400 mt-0.5">{{ stats.savingsRate.toFixed(1) }}% savings rate</p>
      </div>
    </div>

    <!-- Quick Insights -->
    <div v-if="quickInsights.length" class="flex flex-col sm:flex-row gap-2 mb-6">
      <div v-for="(insight, i) in quickInsights" :key="i"
        :class="[
          'flex-1 flex items-start gap-2 px-3 py-2.5 rounded-xl text-xs',
          insight.type === 'success' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
          : insight.type === 'warning' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300'
          : 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300'
        ]">
        <span class="shrink-0 text-sm">{{ insight.icon }}</span>
        <p class="leading-snug">{{ insight.message }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
      <!-- Cash Flow Chart -->
      <div class="xl:col-span-2 bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cash Flow</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">6 months actual + 6 months projected</p>
          </div>
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs justify-end">
            <div class="flex items-center gap-1.5">
              <div class="w-3 h-3 rounded-full bg-emerald-500" />
              <span class="text-gray-500 dark:text-gray-400">Income</span>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="w-3 h-3 rounded-full bg-red-500" />
              <span class="text-gray-500 dark:text-gray-400">Expenses</span>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="w-5 h-0.5 border-t-2 border-dashed border-emerald-400" />
              <span class="text-gray-400 dark:text-gray-500">Projected</span>
            </div>
          </div>
        </div>
        <!-- Projection info strip -->
        <div v-if="cashFlowProjection.length" class="flex items-center gap-2 mb-3 px-3 py-2 rounded-xl bg-violet-50 dark:bg-violet-500/10 text-xs text-violet-700 dark:text-violet-300">
          <span>📊</span>
          <span>Projected based on <strong>{{ formatPHP(cashFlowProjection[0].expenses) }}/mo</strong> in scheduled bills &amp; loans · avg income <strong>{{ formatPHP(cashFlowProjection[0].income) }}/mo</strong></span>
        </div>
        <apexchart
          type="area"
          height="240"
          :options="cashFlowOptions"
          :series="cashFlowSeries"
        />
      </div>

      <!-- Accounts Summary -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Accounts</h3>
        <div class="space-y-3">
          <div
            v-for="account in accounts"
            :key="account.id"
            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors cursor-pointer"
            @click="router.visit(`/accounts/${account.id}`)"
          >
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" :style="{ backgroundColor: account.color + '20' }">
              <CreditCardIcon class="w-5 h-5" :style="{ color: account.color }" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ account.name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ account.type }}</p>
            </div>
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPHP(account.balance) }}</p>
          </div>
        </div>
        <Link href="/accounts" class="mt-4 flex items-center justify-center gap-1 text-sm text-violet-600 dark:text-violet-400 hover:underline">
          View all accounts <ArrowRightIcon class="w-4 h-4" />
        </Link>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
      <!-- Savings Goals -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Savings Goals</h3>
          <Link href="/savings-goals" class="text-xs text-violet-600 dark:text-violet-400 hover:underline">View all</Link>
        </div>
        <div class="space-y-4">
          <div v-for="goal in savingsGoals" :key="goal.id" class="group">
            <div class="flex items-center gap-3 mb-2">
              <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :style="{ backgroundColor: goal.color + '20' }">
                <span class="text-base">{{ goalEmoji(goal) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ goal.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatPHP(goal.current_amount) }} / {{ formatPHP(goal.target_amount) }}</p>
              </div>
              <span class="text-sm font-semibold" :style="{ color: goal.color }">{{ goal.progress_percentage.toFixed(0) }}%</span>
            </div>
            <div class="h-2 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-700"
                :style="{ width: goal.progress_percentage + '%', backgroundColor: goal.color }"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Bills -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Upcoming Bills</h3>
          <Link href="/bills" class="text-xs text-violet-600 dark:text-violet-400 hover:underline">View all</Link>
        </div>
        <div class="space-y-3">
          <div v-for="bill in upcomingBills" :key="bill.id" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/5">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :style="{ backgroundColor: bill.color + '20' }">
              <span class="text-sm">📋</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ bill.name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Due {{ formatDate(bill.next_due_date) }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPHP(bill.amount) }}</p>
              <span class="text-xs px-2 py-0.5 rounded-full" :class="billStatusClass(bill.status)">
                {{ billStatusLabel(bill.status) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Loans -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Active Loans</h3>
          <Link href="/loans" class="text-xs text-violet-600 dark:text-violet-400 hover:underline">View all</Link>
        </div>
        <div class="space-y-4">
          <div v-for="loan in loans" :key="loan.id" class="p-4 rounded-xl bg-gray-50 dark:bg-white/5">
            <div class="flex items-start justify-between mb-3">
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ loan.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ loan.lender }}</p>
              </div>
              <span class="text-xs px-2 py-1 bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded-full">Active</span>
            </div>
            <div class="space-y-1.5">
              <div class="flex justify-between text-xs">
                <span class="text-gray-500 dark:text-gray-400">Remaining</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ formatPHP(loan.remaining_balance) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-gray-500 dark:text-gray-400">Monthly Payment</span>
                <span class="font-medium text-violet-600 dark:text-violet-400">{{ formatPHP(loan.monthly_payment) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-gray-500 dark:text-gray-400">Next Payment</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ loan.next_payment_date ? formatDate(loan.next_payment_date) : 'N/A' }}</span>
              </div>
              <!-- Progress bar -->
              <div class="h-1.5 bg-gray-200 dark:bg-white/10 rounded-full mt-2">
                <div class="h-full gradient-primary rounded-full" :style="{ width: loanProgress(loan) + '%' }" />
              </div>
              <p class="text-xs text-gray-400 text-right">{{ loanProgress(loan).toFixed(0) }}% paid</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-white/10">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Transactions</h3>
        <Link href="/transactions" class="text-sm text-violet-600 dark:text-violet-400 hover:underline">View all</Link>
      </div>

      <!-- Mobile: card list -->
      <div class="sm:hidden divide-y divide-gray-50 dark:divide-white/5">
        <div v-for="txn in recentTransactions" :key="txn.id" class="flex items-center gap-3 p-4">
          <div class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center"
            :style="{ backgroundColor: (txn.category?.color || '#6B7280') + '20' }">
            <span class="text-sm">{{ txn.type === 'income' ? '💵' : '💸' }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ txn.description }}</p>
            <div class="flex items-center gap-2 mt-0.5">
              <span v-if="txn.category" class="text-xs px-1.5 py-0.5 rounded-full" :style="{ backgroundColor: txn.category.color + '20', color: txn.category.color }">
                {{ txn.category.name }}
              </span>
              <span class="text-xs text-gray-400">{{ formatDate(txn.transaction_date) }}</span>
            </div>
          </div>
          <span class="text-sm font-semibold shrink-0" :class="txn.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
            {{ txn.type === 'income' ? '+' : '-' }}{{ formatPHP(txn.amount) }}
          </span>
        </div>
      </div>

      <!-- Desktop: table -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="text-left border-b border-gray-100 dark:border-white/10">
              <th class="px-5 pb-3 pt-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
              <th class="pb-3 pt-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
              <th class="pb-3 pt-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase hidden md:table-cell">Account</th>
              <th class="pb-3 pt-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
              <th class="px-5 pb-3 pt-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase text-right">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-white/5">
            <tr v-for="txn in recentTransactions" :key="txn.id" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
              <td class="py-3 pl-5 pr-4">
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ txn.description }}</p>
              </td>
              <td class="py-3 pr-4">
                <span v-if="txn.category" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium" :style="{ backgroundColor: txn.category.color + '20', color: txn.category.color }">
                  {{ txn.category.name }}
                </span>
                <span v-else class="text-xs text-gray-400">Uncategorized</span>
              </td>
              <td class="py-3 pr-4 hidden md:table-cell">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ txn.account?.name }}</span>
              </td>
              <td class="py-3 pr-4">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(txn.transaction_date) }}</span>
              </td>
              <td class="py-3 pr-5 text-right">
                <span class="text-sm font-semibold" :class="txn.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                  {{ txn.type === 'income' ? '+' : '-' }}{{ formatPHP(txn.amount) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  BanknotesIcon, ArrowTrendingUpIcon, ArrowTrendingDownIcon,
  ArchiveBoxIcon, CreditCardIcon, ArrowRightIcon
} from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { Account, SavingsGoal, Loan, Bill, Transaction } from '@/types'
import dayjs from 'dayjs'

interface Stats {
  totalBalance: number
  monthlyIncome: number
  monthlyExpenses: number
  totalSavings: number
  netWorth: number
  disposableIncome: number
  outstandingLoans: number
  upcomingBillsTotal: number
  healthScore: number
  savingsRate: number
  debtToIncome: number
  emergencyFundMonths: number
}

interface CashFlowMonth { month: string; income: number; expenses: number }
interface QuickInsight { type: 'success' | 'warning' | 'danger'; icon: string; message: string }

const props = defineProps<{
  stats: Stats
  accounts: Account[]
  savingsGoals: SavingsGoal[]
  upcomingBills: Bill[]
  loans: Loan[]
  recentTransactions: Transaction[]
  cashFlowData: CashFlowMonth[]
  cashFlowProjection: CashFlowMonth[]
  quickInsights: QuickInsight[]
}>()

const { formatPHP } = useCurrency()

const healthColor = computed(() => {
  const s = props.stats.healthScore
  if (s >= 80) return '#10B981'
  if (s >= 60) return '#3B82F6'
  if (s >= 40) return '#F59E0B'
  return '#EF4444'
})
const healthLabel = computed(() => {
  const s = props.stats.healthScore
  if (s >= 80) return 'Excellent'
  if (s >= 60) return 'Good'
  if (s >= 40) return 'Moderate'
  return 'Needs Work'
})

function formatDate(date: string): string {
  return dayjs(date).format('MMM D, YYYY')
}

function goalEmoji(goal: SavingsGoal): string {
  const icons: Record<string, string> = {
    'shield-check': '🛡️', 'computer-desktop': '💻', 'map': '🗺️',
    'home': '🏠', 'car': '🚗', 'savings': '💰',
  }
  return icons[goal.icon] || '💰'
}

function loanProgress(loan: Loan): number {
  const paid = loan.principal_amount - loan.remaining_balance
  return Math.min(100, (paid / loan.principal_amount) * 100)
}

function billStatusClass(status: string): string {
  return {
    overdue: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400',
    due_soon: 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400',
    upcoming: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400',
  }[status] || ''
}

function billStatusLabel(status: string): string {
  return { overdue: 'Overdue', due_soon: 'Due Soon', upcoming: 'Upcoming' }[status] || status
}

// ApexCharts config — 4 series: actual income/expenses (solid) + projected (dashed)
const allChartMonths = computed(() => [
  ...props.cashFlowData.map(d => d.month),
  ...props.cashFlowProjection.map(d => d.month),
])

const cashFlowOptions = computed(() => ({
  chart: {
    background: 'transparent',
    toolbar: { show: false },
    sparkline: { enabled: false },
  },
  colors: ['#10B981', '#EF4444', '#34D399', '#F87171'],
  fill: {
    type: ['gradient', 'gradient', 'solid', 'solid'],
    gradient: { opacityFrom: 0.35, opacityTo: 0.05 },
    opacity: [1, 1, 0, 0],
  },
  dataLabels: { enabled: false },
  stroke: {
    curve: 'smooth',
    width: [2.5, 2.5, 2, 2],
    dashArray: [0, 0, 6, 6],
  },
  xaxis: {
    categories: allChartMonths.value,
    labels: { style: { colors: '#9CA3AF', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { colors: '#9CA3AF', fontSize: '11px' },
      formatter: (val: number) => `₱${(val / 1000).toFixed(0)}K`,
    },
  },
  grid: {
    borderColor: 'rgba(156, 163, 175, 0.1)',
    padding: { left: 0, right: 0 },
  },
  tooltip: {
    theme: 'dark',
    y: { formatter: (val: number | null) => val != null ? `₱${val.toLocaleString('en-PH')}` : '—' },
  },
  legend: { show: false },
  annotations: {
    xaxis: props.cashFlowData.length > 0 ? [{
      x: props.cashFlowData[props.cashFlowData.length - 1].month,
      borderColor: '#6366F1',
      borderWidth: 1,
      strokeDashArray: 4,
      label: {
        text: 'Today',
        style: { color: '#6366F1', background: 'transparent', fontSize: '10px' },
        position: 'top',
        offsetY: -5,
      },
    }] : [],
  },
}))

const cashFlowSeries = computed(() => {
  const actual = props.cashFlowData
  const proj   = props.cashFlowProjection
  const aLen   = actual.length
  const pLen   = proj.length
  const lastIncome   = actual[aLen - 1]?.income   ?? 0
  const lastExpenses = actual[aLen - 1]?.expenses ?? 0

  return [
    {
      name: 'Income',
      data: [...actual.map(d => d.income), ...Array(pLen).fill(null)],
    },
    {
      name: 'Expenses',
      data: [...actual.map(d => d.expenses), ...Array(pLen).fill(null)],
    },
    {
      name: 'Proj. Income',
      data: [...Array(aLen - 1).fill(null), lastIncome, ...proj.map(d => d.income)],
    },
    {
      name: 'Proj. Expenses',
      data: [...Array(aLen - 1).fill(null), lastExpenses, ...proj.map(d => d.expenses)],
    },
  ]
})

// Inline StatCard component
const StatCard = {
  props: ['label', 'value', 'icon', 'gradient', 'change', 'positive'],
  template: `
    <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-5 card-hover">
      <div class="flex items-start justify-between mb-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="gradient">
          <component :is="icon" class="w-5 h-5 text-white" />
        </div>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full"
          :class="positive ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10 dark:text-emerald-400' : 'text-red-600 bg-red-50 dark:bg-red-500/10 dark:text-red-400'">
          {{ change }}
        </span>
      </div>
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ label }}</p>
      <p class="text-xl font-bold text-gray-900 dark:text-white">{{ value }}</p>
    </div>
  `,
}
</script>
