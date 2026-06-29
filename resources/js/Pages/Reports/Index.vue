<template>
  <AppLayout title="Reports & Analytics">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Reports & Analytics</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Financial insights for {{ year }}</p>
      </div>
      <div class="flex items-center gap-2">
        <select v-model="selectedYear" @change="changeYear" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-white/20 bg-white dark:bg-[#1A1A2E] text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
          <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
    </div>

    <!-- Financial Health Score -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
      <div class="col-span-2 sm:col-span-1 bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4 flex items-center gap-4">
        <div class="relative w-16 h-16 shrink-0">
          <svg class="w-16 h-16 -rotate-90" viewBox="0 0 64 64">
            <circle cx="32" cy="32" r="26" fill="none" stroke="currentColor" stroke-width="6" class="text-gray-100 dark:text-white/10" />
            <circle cx="32" cy="32" r="26" fill="none" :stroke="healthScoreColor" stroke-width="6"
              stroke-linecap="round"
              :stroke-dasharray="`${healthMetrics.healthScore * 1.634} 163.4`" />
          </svg>
          <span class="absolute inset-0 flex items-center justify-center text-base font-bold" :style="{ color: healthScoreColor }">{{ healthMetrics.healthScore }}</span>
        </div>
        <div>
          <p class="text-xs text-gray-500 dark:text-gray-400">Health Score</p>
          <p class="text-sm font-bold" :style="{ color: healthScoreColor }">{{ healthScoreLabel }}</p>
        </div>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">This Month Income</p>
        <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ formatPHP(healthMetrics.currentIncome) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">This Month Expenses</p>
        <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ formatPHP(healthMetrics.currentExpenses) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Disposable Income</p>
        <p class="text-lg font-bold" :class="healthMetrics.disposableIncome >= 0 ? 'text-violet-600 dark:text-violet-400' : 'text-red-600'">{{ formatPHP(healthMetrics.disposableIncome) }}</p>
      </div>
    </div>

    <!-- Debt & Bills Health Ratios -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
      <!-- Debt-to-Income -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Debt-to-Income</p>
        <p class="text-2xl font-bold" :class="ratioColor(healthMetrics.debtToIncome, 20, 35, 50)">{{ healthMetrics.debtToIncome }}%</p>
        <span :class="['mt-1.5 inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full', ratioBadge(healthMetrics.debtToIncome, 20, 35, 50).bg]">
          {{ ratioBadge(healthMetrics.debtToIncome, 20, 35, 50).label }}
        </span>
        <div class="mt-2 h-1.5 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
          <div class="h-full rounded-full transition-all" :class="ratioBar(healthMetrics.debtToIncome, 20, 35, 50)" :style="{ width: Math.min(100, healthMetrics.debtToIncome) + '%' }" />
        </div>
      </div>

      <!-- Bills Stress -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Bills Stress Score</p>
        <p class="text-2xl font-bold" :class="ratioColor(healthMetrics.billsStressScore, 30, 50, 70)">{{ healthMetrics.billsStressScore }}%</p>
        <span :class="['mt-1.5 inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full', ratioBadge(healthMetrics.billsStressScore, 30, 50, 70).bg]">
          {{ ratioBadge(healthMetrics.billsStressScore, 30, 50, 70).label }}
        </span>
        <div class="mt-2 h-1.5 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
          <div class="h-full rounded-full transition-all" :class="ratioBar(healthMetrics.billsStressScore, 30, 50, 70)" :style="{ width: Math.min(100, healthMetrics.billsStressScore) + '%' }" />
        </div>
      </div>

      <!-- Savings Rate -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Savings Rate</p>
        <p class="text-2xl font-bold" :class="savingsRateColor(healthMetrics.savingsRate)">{{ healthMetrics.savingsRate }}%</p>
        <span :class="['mt-1.5 inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full', savingsRateBadge(healthMetrics.savingsRate).bg]">
          {{ savingsRateBadge(healthMetrics.savingsRate).label }}
        </span>
        <div class="mt-2 h-1.5 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
          <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: Math.min(100, Math.max(0, healthMetrics.savingsRate)) + '%' }" />
        </div>
      </div>

      <!-- Emergency Fund -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Emergency Fund</p>
        <p class="text-2xl font-bold" :class="emergencyColor(healthMetrics.emergencyFundCoverage)">{{ healthMetrics.emergencyFundCoverage }}mo</p>
        <span :class="['mt-1.5 inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full', emergencyBadge(healthMetrics.emergencyFundCoverage).bg]">
          {{ emergencyBadge(healthMetrics.emergencyFundCoverage).label }}
        </span>
        <div class="mt-2 h-1.5 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
          <div class="h-full rounded-full bg-blue-500 transition-all" :style="{ width: Math.min(100, (healthMetrics.emergencyFundCoverage / 6) * 100) + '%' }" />
        </div>
      </div>
    </div>

    <!-- Insights Panel -->
    <div v-if="insights.length" class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-5 mb-6">
      <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">💡 Intelligent Insights</h3>
      <div class="space-y-2">
        <div v-for="(insight, i) in insights" :key="i"
          :class="[
            'flex items-start gap-3 px-4 py-3 rounded-xl text-sm',
            insight.type === 'success' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-300' :
            insight.type === 'warning' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300' :
            'bg-red-50 dark:bg-red-500/10 text-red-800 dark:text-red-300'
          ]">
          <span class="shrink-0 text-base leading-snug">{{ insight.icon }}</span>
          <p class="leading-snug">{{ insight.message }}</p>
        </div>
      </div>
      <div class="mt-3 pt-3 border-t border-gray-100 dark:border-white/10">
        <a href="/settings/financial" class="text-xs text-violet-600 dark:text-violet-400 font-medium hover:underline">Configure thresholds in Financial Settings →</a>
      </div>
    </div>

    <!-- Year Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ year }} Total Income</p>
        <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatPHP(totalIncome) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ year }} Total Expenses</p>
        <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ formatPHP(totalExpenses) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Net Savings</p>
        <p class="text-xl font-bold" :class="netSavings >= 0 ? 'gradient-text' : 'text-red-600'">{{ formatPHP(netSavings) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Avg Savings Rate</p>
        <p class="text-xl font-bold gradient-text">{{ yearSavingsRate.toFixed(1) }}%</p>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
      <!-- Income vs Expenses Bar Chart -->
      <div class="xl:col-span-2 bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Income vs Expenses</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Monthly cash flow for {{ year }}</p>
        <apexchart type="bar" height="280" :options="barChartOptions" :series="barChartSeries" />
      </div>

      <!-- Category Donut Chart -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Expense Breakdown</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">By category</p>
        <apexchart v-if="categoryBreakdown.length > 0" type="donut" height="230" :options="donutChartOptions" :series="donutSeries" />
        <p v-else class="text-center text-gray-400 py-12">No expense data</p>
        <div class="mt-4 space-y-2">
          <div v-for="cat in categoryBreakdown.slice(0, 6)" :key="cat.category" class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: cat.color }" />
              <span class="text-xs text-gray-600 dark:text-gray-300">{{ cat.category }}</span>
            </div>
            <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ formatPHP(cat.amount) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Savings Trend -->
    <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Savings Trend</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Monthly contributions to savings goals</p>
      <apexchart type="area" height="200" :options="savingsChartOptions" :series="savingsSeries" />
    </div>

    <!-- Net Cash Flow Table -->
    <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Monthly Net Cash Flow</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-white/10">
              <th class="text-left pb-3 font-medium text-gray-500 dark:text-gray-400">Month</th>
              <th class="text-right pb-3 font-medium text-gray-500 dark:text-gray-400">Income</th>
              <th class="text-right pb-3 font-medium text-gray-500 dark:text-gray-400">Expenses</th>
              <th class="text-right pb-3 font-medium text-gray-500 dark:text-gray-400">Net</th>
              <th class="text-right pb-3 font-medium text-gray-500 dark:text-gray-400">Rate</th>
              <th class="pb-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-white/5">
            <tr v-for="d in monthlyData" :key="d.month" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
              <td class="py-3 font-medium text-gray-900 dark:text-white">{{ d.month }}</td>
              <td class="py-3 text-right text-emerald-600 dark:text-emerald-400">{{ formatPHP(d.income) }}</td>
              <td class="py-3 text-right text-red-600 dark:text-red-400">{{ formatPHP(d.expenses) }}</td>
              <td class="py-3 text-right font-semibold" :class="d.net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                {{ d.net >= 0 ? '+' : '' }}{{ formatPHP(d.net) }}
              </td>
              <td class="py-3 text-right text-xs font-medium" :class="d.income > 0 && ((d.income - d.expenses) / d.income * 100) >= 10 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400'">
                {{ d.income > 0 ? ((d.income - d.expenses) / d.income * 100).toFixed(1) + '%' : '—' }}
              </td>
              <td class="py-3 pl-4">
                <div class="w-20 h-1.5 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
                  <div class="h-full rounded-full" :class="d.net >= 0 ? 'bg-emerald-500' : 'bg-red-500'"
                    :style="{ width: maxNetAbs > 0 ? Math.min(100, Math.abs(d.net) / maxNetAbs * 100) + '%' : '0%' }" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useCurrency } from '@/composables/useCurrency'
import type { Insight } from '@/types'

interface MonthlyData { month: string; income: number; expenses: number; net: number }
interface CategoryBreakdown { category_id: number; category: string; color: string; amount: number }
interface SavingsTrend { month: string; amount: number }
interface HealthMetrics {
  currentIncome: number; currentExpenses: number; disposableIncome: number
  debtToIncome: number; billsStressScore: number; savingsRate: number
  emergencyFundCoverage: number; totalSavings: number; outstandingLoans: number
  monthlyLoanPayments: number; healthScore: number
}

const props = defineProps<{
  monthlyData: MonthlyData[]
  categoryBreakdown: CategoryBreakdown[]
  savingsTrend: SavingsTrend[]
  year: number
  availableYears: number[]
  healthMetrics: HealthMetrics
  insights: Insight[]
  settings: Record<string, any> | null
}>()

const { formatPHP } = useCurrency()
const selectedYear = ref(props.year)

function changeYear() {
  router.get('/reports', { year: selectedYear.value }, { preserveState: false })
}

// Year totals
const totalIncome   = computed(() => props.monthlyData.reduce((s, d) => s + d.income, 0))
const totalExpenses = computed(() => props.monthlyData.reduce((s, d) => s + d.expenses, 0))
const netSavings    = computed(() => totalIncome.value - totalExpenses.value)
const yearSavingsRate = computed(() => totalIncome.value > 0 ? (netSavings.value / totalIncome.value) * 100 : 0)
const maxNetAbs     = computed(() => Math.max(...props.monthlyData.map(d => Math.abs(d.net)), 1))

// Health score display
const healthScoreColor = computed(() => {
  const s = props.healthMetrics.healthScore
  if (s >= 80) return '#10B981'
  if (s >= 60) return '#3B82F6'
  if (s >= 40) return '#F59E0B'
  return '#EF4444'
})
const healthScoreLabel = computed(() => {
  const s = props.healthMetrics.healthScore
  if (s >= 80) return 'Excellent'
  if (s >= 60) return 'Good'
  if (s >= 40) return 'Moderate'
  return 'Needs Work'
})

// Ratio helpers
function ratioColor(val: number, good: number, warn: number, danger: number) {
  if (val <= good) return 'text-emerald-600 dark:text-emerald-400'
  if (val <= warn) return 'text-amber-600 dark:text-amber-400'
  if (val <= danger) return 'text-orange-600 dark:text-orange-400'
  return 'text-red-600 dark:text-red-400'
}
function ratioBadge(val: number, good: number, warn: number, danger: number) {
  if (val <= good) return { label: 'Healthy', bg: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' }
  if (val <= warn) return { label: 'Moderate', bg: 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300' }
  if (val <= danger) return { label: 'Warning', bg: 'bg-orange-100 dark:bg-orange-500/20 text-orange-700 dark:text-orange-300' }
  return { label: 'Critical', bg: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300' }
}
function ratioBar(val: number, good: number, warn: number, danger: number) {
  if (val <= good) return 'bg-emerald-500'
  if (val <= warn) return 'bg-amber-500'
  if (val <= danger) return 'bg-orange-500'
  return 'bg-red-500'
}
function savingsRateColor(val: number) {
  if (val >= 20) return 'text-emerald-600 dark:text-emerald-400'
  if (val >= 10) return 'text-blue-600 dark:text-blue-400'
  if (val >= 0) return 'text-amber-600 dark:text-amber-400'
  return 'text-red-600 dark:text-red-400'
}
function savingsRateBadge(val: number) {
  if (val >= 20) return { label: 'Excellent', bg: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' }
  if (val >= 10) return { label: 'Good', bg: 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300' }
  if (val >= 5)  return { label: 'Moderate', bg: 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300' }
  return { label: 'Low', bg: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300' }
}
function emergencyColor(val: number) {
  if (val >= 6) return 'text-emerald-600 dark:text-emerald-400'
  if (val >= 3) return 'text-blue-600 dark:text-blue-400'
  if (val >= 1) return 'text-amber-600 dark:text-amber-400'
  return 'text-red-600 dark:text-red-400'
}
function emergencyBadge(val: number) {
  if (val >= 6) return { label: 'Excellent', bg: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' }
  if (val >= 3) return { label: 'Good', bg: 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300' }
  if (val >= 1) return { label: 'Low', bg: 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300' }
  return { label: 'Critical', bg: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300' }
}

// Charts
const barChartOptions = computed(() => ({
  chart: { background: 'transparent', toolbar: { show: false } },
  colors: ['#10B981', '#EF4444'],
  plotOptions: { bar: { borderRadius: 6, columnWidth: '60%', grouped: true } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.monthlyData.map(d => d.month),
    labels: { style: { colors: '#9CA3AF', fontSize: '11px' } },
    axisBorder: { show: false }, axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#9CA3AF', fontSize: '11px' }, formatter: (v: number) => `₱${(v/1000).toFixed(0)}K` } },
  grid: { borderColor: 'rgba(156,163,175,0.1)' },
  tooltip: { theme: 'dark', y: { formatter: (v: number) => `₱${v.toLocaleString('en-PH')}` } },
  legend: { labels: { colors: '#9CA3AF' } },
}))
const barChartSeries = computed(() => [
  { name: 'Income', data: props.monthlyData.map(d => d.income) },
  { name: 'Expenses', data: props.monthlyData.map(d => d.expenses) },
])
const donutChartOptions = computed(() => ({
  chart: { background: 'transparent' },
  colors: props.categoryBreakdown.map(c => c.color),
  labels: props.categoryBreakdown.map(c => c.category),
  dataLabels: { enabled: false },
  plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Total', formatter: () => `₱${(totalExpenses.value/1000).toFixed(0)}K`, color: '#9CA3AF' } } } } },
  tooltip: { theme: 'dark', y: { formatter: (v: number) => `₱${v.toLocaleString('en-PH')}` } },
  legend: { show: false },
}))
const donutSeries = computed(() => props.categoryBreakdown.map(c => c.amount))
const savingsChartOptions = computed(() => ({
  chart: { background: 'transparent', toolbar: { show: false } },
  colors: ['#7C3AED'],
  fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2.5 },
  xaxis: {
    categories: props.savingsTrend.map(d => d.month),
    labels: { style: { colors: '#9CA3AF', fontSize: '11px' } },
    axisBorder: { show: false }, axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#9CA3AF', fontSize: '11px' }, formatter: (v: number) => `₱${(v/1000).toFixed(0)}K` } },
  grid: { borderColor: 'rgba(156,163,175,0.1)' },
  tooltip: { theme: 'dark', y: { formatter: (v: number) => `₱${v.toLocaleString('en-PH')}` } },
}))
const savingsSeries = computed(() => [{ name: 'Savings', data: props.savingsTrend.map(d => d.amount) }])
</script>
