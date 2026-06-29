<template>
  <AppLayout title="Financial Settings">
    <div class="max-w-3xl mx-auto">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Financial Settings</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure your personal thresholds and targets to unlock intelligent insights.</p>
      </div>

      <form @submit.prevent="save" class="space-y-6">

        <!-- Expense Thresholds -->
        <section class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-5">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Expense Thresholds</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Set limits on how much you want to spend each month.</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Max Monthly Spending (₱)</label>
              <input v-model.number="form.max_monthly_spending" type="number" min="0" step="0.01" placeholder="e.g. 50000" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-white/20 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Max Spending % of Income</label>
              <div class="relative">
                <input v-model.number="form.max_spending_percentage" type="number" min="0" max="100" step="1" placeholder="e.g. 70" class="w-full px-3 py-2.5 pr-8 rounded-xl border border-gray-200 dark:border-white/20 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors" />
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
              </div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Warning Threshold</label>
              <div class="flex gap-2">
                <button v-for="pct in [70, 80, 90]" :key="pct" type="button"
                  @click="form.spending_warning_threshold = pct"
                  :class="form.spending_warning_threshold === pct ? 'bg-violet-600 text-white border-violet-600' : 'bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-white/20 hover:border-violet-400'"
                  class="flex-1 py-2 rounded-xl border text-sm font-medium transition-colors">
                  {{ pct }}%
                </button>
              </div>
            </div>
          </div>

          <!-- Category limits -->
          <div class="mt-4">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Category Spending Limits (₱/month)</label>
            <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
              <div v-for="cat in categories" :key="cat.id" class="flex items-center gap-3">
                <div class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: cat.color }" />
                <span class="text-sm text-gray-700 dark:text-gray-300 w-36 truncate shrink-0">{{ cat.name }}</span>
                <input
                  v-model.number="form.category_limits[cat.id]"
                  type="number" min="0" step="0.01"
                  :placeholder="'No limit'"
                  class="flex-1 px-3 py-2 rounded-xl border border-gray-200 dark:border-white/20 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors"
                />
              </div>
            </div>
          </div>
        </section>

        <!-- Savings Thresholds -->
        <section class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-5">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Savings Thresholds</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Define your savings goals and emergency fund targets.</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Min Monthly Savings (₱)</label>
              <input v-model.number="form.min_monthly_savings" type="number" min="0" step="0.01" placeholder="e.g. 5000" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-white/20 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Desired Savings Rate</label>
              <div class="relative">
                <input v-model.number="form.desired_savings_rate" type="number" min="0" max="100" step="1" placeholder="e.g. 20" class="w-full px-3 py-2.5 pr-8 rounded-xl border border-gray-200 dark:border-white/20 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors" />
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
              </div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Emergency Fund Target (months)</label>
              <div class="flex gap-2">
                <button v-for="m in [3, 6, 9, 12]" :key="m" type="button"
                  @click="form.emergency_fund_months = m"
                  :class="form.emergency_fund_months === m ? 'bg-violet-600 text-white border-violet-600' : 'bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-white/20 hover:border-violet-400'"
                  class="flex-1 py-2 rounded-xl border text-sm font-medium transition-colors">
                  {{ m }}mo
                </button>
              </div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Min Remaining Balance (₱)</label>
              <input v-model.number="form.min_remaining_balance" type="number" min="0" step="0.01" placeholder="e.g. 10000" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-white/20 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors" />
            </div>
          </div>
        </section>

        <!-- Financial Health Targets -->
        <section class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-5">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Financial Health Targets</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Set your own benchmarks for financial ratios and cash flow.</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Max Debt-to-Income Ratio</label>
              <div class="relative">
                <input v-model.number="form.max_debt_to_income" type="number" min="0" max="100" step="1" placeholder="e.g. 35" class="w-full px-3 py-2.5 pr-8 rounded-xl border border-gray-200 dark:border-white/20 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors" />
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
              </div>
              <p class="text-[11px] text-gray-400 mt-1">Recommended: ≤35%</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Max Bills Stress Score</label>
              <div class="relative">
                <input v-model.number="form.max_bills_stress_score" type="number" min="0" max="100" step="1" placeholder="e.g. 50" class="w-full px-3 py-2.5 pr-8 rounded-xl border border-gray-200 dark:border-white/20 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors" />
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
              </div>
              <p class="text-[11px] text-gray-400 mt-1">Recommended: ≤50%</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Minimum Savings Rate</label>
              <div class="relative">
                <input v-model.number="form.min_savings_rate" type="number" min="0" max="100" step="1" placeholder="e.g. 10" class="w-full px-3 py-2.5 pr-8 rounded-xl border border-gray-200 dark:border-white/20 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors" />
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
              </div>
              <p class="text-[11px] text-gray-400 mt-1">Recommended: ≥20%</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Desired Net Cash Flow (₱)</label>
              <input v-model.number="form.desired_net_cash_flow" type="number" step="0.01" placeholder="e.g. 5000" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-white/20 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors" />
              <p class="text-[11px] text-gray-400 mt-1">Minimum net income after expenses</p>
            </div>
          </div>
        </section>

        <!-- Save Button -->
        <div class="flex justify-end pb-4">
          <button type="submit" :disabled="saving"
            class="px-6 py-2.5 bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors">
            {{ saving ? 'Saving…' : 'Save Settings' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { FinancialSetting, Category } from '@/types'

const props = defineProps<{
  settings: FinancialSetting
  categories: Category[]
}>()

const saving = ref(false)

const form = reactive({
  max_monthly_spending:       props.settings.max_monthly_spending ?? null,
  max_spending_percentage:    props.settings.max_spending_percentage ?? null,
  category_limits:            (props.settings.category_limits ?? {}) as Record<number, number | null>,
  spending_warning_threshold: props.settings.spending_warning_threshold ?? 80,
  min_monthly_savings:        props.settings.min_monthly_savings ?? null,
  desired_savings_rate:       props.settings.desired_savings_rate ?? null,
  emergency_fund_months:      props.settings.emergency_fund_months ?? 6,
  min_remaining_balance:      props.settings.min_remaining_balance ?? null,
  max_debt_to_income:         props.settings.max_debt_to_income ?? null,
  max_bills_stress_score:     props.settings.max_bills_stress_score ?? null,
  min_savings_rate:           props.settings.min_savings_rate ?? null,
  desired_net_cash_flow:      props.settings.desired_net_cash_flow ?? null,
})

function save() {
  saving.value = true
  router.put('/settings/financial', form as any, {
    onFinish: () => { saving.value = false },
  })
}
</script>

