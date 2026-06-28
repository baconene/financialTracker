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

    <!-- Year Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Income</p>
        <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatPHP(totalIncome) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Expenses</p>
        <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ formatPHP(totalExpenses) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Net Savings</p>
        <p class="text-xl font-bold" :class="netSavings >= 0 ? 'gradient-text' : 'text-red-600'">{{ formatPHP(netSavings) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Savings Rate</p>
        <p class="text-xl font-bold gradient-text">{{ savingsRate.toFixed(1) }}%</p>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
      <!-- Income vs Expenses Bar Chart -->
      <div class="xl:col-span-2 bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Income vs Expenses</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Monthly breakdown for {{ year }}</p>
        <apexchart
          type="bar"
          height="280"
          :options="barChartOptions"
          :series="barChartSeries"
        />
      </div>

      <!-- Category Donut Chart -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Expense Breakdown</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">By category</p>
        <apexchart
          v-if="categoryBreakdown.length > 0"
          type="donut"
          height="230"
          :options="donutChartOptions"
          :series="donutSeries"
        />
        <p v-else class="text-center text-gray-400 py-12">No expense data</p>

        <!-- Legend -->
        <div class="mt-4 space-y-2">
          <div v-for="(cat, index) in categoryBreakdown.slice(0, 6)" :key="cat.category" class="flex items-center justify-between">
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
      <apexchart
        type="area"
        height="200"
        :options="savingsChartOptions"
        :series="savingsSeries"
      />
    </div>

    <!-- Net Cash Flow -->
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
              <td class="py-3 pl-4">
                <div class="w-24 h-2 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
                  <div class="h-full rounded-full" :class="d.net >= 0 ? 'bg-emerald-500' : 'bg-red-500'" :style="{ width: Math.min(100, Math.abs(d.net) / Math.max(...monthlyData.map(m => Math.abs(m.net))) * 100) + '%' }" />
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

interface MonthlyData {
  month: string
  income: number
  expenses: number
  net: number
}

interface CategoryBreakdown {
  category: string
  color: string
  amount: number
}

interface SavingsTrend {
  month: string
  amount: number
}

const props = defineProps<{
  monthlyData: MonthlyData[]
  categoryBreakdown: CategoryBreakdown[]
  savingsTrend: SavingsTrend[]
  year: number
  availableYears: number[]
}>()

const { formatPHP } = useCurrency()
const selectedYear = ref(props.year)

function changeYear() {
  router.get('/reports', { year: selectedYear.value }, { preserveState: false })
}

const totalIncome = computed(() => props.monthlyData.reduce((sum, d) => sum + d.income, 0))
const totalExpenses = computed(() => props.monthlyData.reduce((sum, d) => sum + d.expenses, 0))
const netSavings = computed(() => totalIncome.value - totalExpenses.value)
const savingsRate = computed(() => totalIncome.value > 0 ? (netSavings.value / totalIncome.value) * 100 : 0)

// Bar Chart
const barChartOptions = computed(() => ({
  chart: { background: 'transparent', toolbar: { show: false } },
  colors: ['#10B981', '#EF4444'],
  plotOptions: {
    bar: { borderRadius: 6, columnWidth: '60%', grouped: true },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.monthlyData.map(d => d.month),
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
  grid: { borderColor: 'rgba(156, 163, 175, 0.1)' },
  tooltip: {
    theme: 'dark',
    y: { formatter: (val: number) => `₱${val.toLocaleString('en-PH')}` },
  },
  legend: {
    labels: { colors: '#9CA3AF' },
  },
}))

const barChartSeries = computed(() => [
  { name: 'Income', data: props.monthlyData.map(d => d.income) },
  { name: 'Expenses', data: props.monthlyData.map(d => d.expenses) },
])

// Donut Chart
const donutChartOptions = computed(() => ({
  chart: { background: 'transparent' },
  colors: props.categoryBreakdown.map(c => c.color),
  labels: props.categoryBreakdown.map(c => c.category),
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Total',
            formatter: () => `₱${(totalExpenses.value / 1000).toFixed(0)}K`,
            color: '#9CA3AF',
          },
        },
      },
    },
  },
  tooltip: {
    theme: 'dark',
    y: { formatter: (val: number) => `₱${val.toLocaleString('en-PH')}` },
  },
  legend: { show: false },
}))

const donutSeries = computed(() => props.categoryBreakdown.map(c => c.amount))

// Savings Chart
const savingsChartOptions = computed(() => ({
  chart: { background: 'transparent', toolbar: { show: false } },
  colors: ['#7C3AED'],
  fill: {
    type: 'gradient',
    gradient: { opacityFrom: 0.4, opacityTo: 0.05 },
  },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2.5 },
  xaxis: {
    categories: props.savingsTrend.map(d => d.month),
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
  grid: { borderColor: 'rgba(156, 163, 175, 0.1)' },
  tooltip: {
    theme: 'dark',
    y: { formatter: (val: number) => `₱${val.toLocaleString('en-PH')}` },
  },
}))

const savingsSeries = computed(() => [
  { name: 'Savings', data: props.savingsTrend.map(d => d.amount) },
])
</script>
