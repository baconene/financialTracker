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

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
      <!-- Cash Flow Chart -->
      <div class="xl:col-span-2 bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cash Flow</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Income vs Expenses (6 months)</p>
          </div>
          <div class="flex items-center gap-4 text-xs">
            <div class="flex items-center gap-1.5">
              <div class="w-3 h-3 rounded-full bg-emerald-500" />
              <span class="text-gray-500 dark:text-gray-400">Income</span>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="w-3 h-3 rounded-full bg-red-500" />
              <span class="text-gray-500 dark:text-gray-400">Expenses</span>
            </div>
          </div>
        </div>
        <apexchart
          type="area"
          height="250"
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
}

interface CashFlowMonth {
  month: string
  income: number
  expenses: number
}

const props = defineProps<{
  stats: Stats
  accounts: Account[]
  savingsGoals: SavingsGoal[]
  upcomingBills: Bill[]
  loans: Loan[]
  recentTransactions: Transaction[]
  cashFlowData: CashFlowMonth[]
}>()

const { formatPHP } = useCurrency()

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

// ApexCharts config
const cashFlowOptions = computed(() => ({
  chart: {
    background: 'transparent',
    toolbar: { show: false },
    sparkline: { enabled: false },
  },
  colors: ['#10B981', '#EF4444'],
  fill: {
    type: 'gradient',
    gradient: {
      opacityFrom: 0.4,
      opacityTo: 0.05,
    },
  },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2.5 },
  xaxis: {
    categories: props.cashFlowData.map(d => d.month),
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
    y: { formatter: (val: number) => `₱${val.toLocaleString('en-PH')}` },
  },
  legend: { show: false },
}))

const cashFlowSeries = computed(() => [
  { name: 'Income', data: props.cashFlowData.map(d => d.income) },
  { name: 'Expenses', data: props.cashFlowData.map(d => d.expenses) },
])

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
