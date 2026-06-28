<template>
  <AppLayout title="Budgets">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Budget</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ monthName }} {{ year }} spending plan</p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Month Navigator -->
        <div class="flex items-center gap-1 bg-white dark:bg-[#1A1A2E] border border-gray-200 dark:border-white/10 rounded-xl px-2">
          <button @click="changeMonth(-1)" class="p-2 text-gray-500 hover:text-gray-900 dark:hover:text-white">
            <ChevronLeftIcon class="w-4 h-4" />
          </button>
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300 px-2">{{ monthName }} {{ year }}</span>
          <button @click="changeMonth(1)" class="p-2 text-gray-500 hover:text-gray-900 dark:hover:text-white">
            <ChevronRightIcon class="w-4 h-4" />
          </button>
        </div>
        <button @click="showCreateModal = true" class="flex items-center gap-2 px-4 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90 transition-all shadow-lg">
          <PlusIcon class="w-4 h-4" />
          Set Budget
        </button>
      </div>
    </div>

    <div v-if="budget">
      <!-- Overview -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-5">
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Budget</p>
          <p class="text-2xl font-bold gradient-text">{{ formatPHP(budget.total_budget) }}</p>
        </div>
        <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-5">
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Spent</p>
          <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ formatPHP(totalSpent) }}</p>
        </div>
        <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-5">
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Remaining</p>
          <p class="text-2xl font-bold" :class="remaining >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
            {{ formatPHP(remaining) }}
          </p>
        </div>
      </div>

      <!-- Overall Progress Bar -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6 mb-6">
        <div class="flex justify-between items-center mb-3">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Overall Progress</h3>
          <span class="text-sm font-bold" :class="overallPercent > 90 ? 'text-red-600 dark:text-red-400' : 'text-violet-600 dark:text-violet-400'">
            {{ overallPercent.toFixed(1) }}% used
          </span>
        </div>
        <div class="h-4 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-700"
            :class="overallPercent > 90 ? 'bg-red-500' : overallPercent > 70 ? 'bg-amber-500' : 'gradient-primary'"
            :style="{ width: Math.min(100, overallPercent) + '%' }"
          />
        </div>
        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
          <span>₱0</span>
          <span>{{ formatPHP(budget.total_budget) }}</span>
        </div>
      </div>

      <!-- Category Budgets -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="bc in budget.budget_categories"
          :key="bc.id"
          class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-5"
        >
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl flex items-center justify-center" :style="{ backgroundColor: bc.category?.color + '20' }">
                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: bc.category?.color }" />
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ bc.category?.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatPHP(getCategorySpent(bc.category_id)) }} / {{ formatPHP(bc.allocated_amount) }}</p>
              </div>
            </div>
            <span class="text-sm font-bold" :class="getCategoryPercent(bc) > 90 ? 'text-red-600' : 'text-gray-900 dark:text-white'">
              {{ getCategoryPercent(bc).toFixed(0) }}%
            </span>
          </div>
          <div class="h-2.5 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-700"
              :class="getCategoryPercent(bc) > 90 ? 'bg-red-500' : getCategoryPercent(bc) > 70 ? 'bg-amber-500' : ''"
              :style="{
                width: Math.min(100, getCategoryPercent(bc)) + '%',
                backgroundColor: getCategoryPercent(bc) <= 70 ? bc.category?.color : undefined,
              }"
            />
          </div>
          <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1.5">
            <span>Spent: {{ formatPHP(getCategorySpent(bc.category_id)) }}</span>
            <span :class="getRemainingCategory(bc) < 0 ? 'text-red-500' : 'text-emerald-600'">
              {{ getRemainingCategory(bc) < 0 ? 'Over by ' : 'Left: ' }}{{ formatPHP(Math.abs(getRemainingCategory(bc))) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- No Budget -->
    <div v-else class="flex flex-col items-center justify-center py-16 text-center">
      <div class="w-20 h-20 rounded-2xl gradient-primary flex items-center justify-center mb-4 opacity-60">
        <ChartPieIcon class="w-10 h-10 text-white" />
      </div>
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Budget for {{ monthName }} {{ year }}</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-4">Create a budget to track your spending</p>
      <button @click="showCreateModal = true" class="px-6 py-2.5 gradient-primary text-white rounded-xl font-medium hover:opacity-90">
        Create Budget
      </button>
    </div>

    <!-- Create Budget Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop" @click.self="showCreateModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl shadow-2xl w-full max-w-xl border border-gray-200 dark:border-white/10 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10 sticky top-0 bg-white dark:bg-[#1A1A2E]">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Set Budget</h3>
              <button @click="showCreateModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <form @submit.prevent="createBudget" class="p-6 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Budget Name</label>
                <input v-model="budgetForm.name" type="text" class="input-field" :placeholder="`${monthName} ${year} Budget`" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Total Budget (₱)</label>
                <input v-model.number="budgetForm.total_budget" type="number" min="0" step="0.01" class="input-field" required />
              </div>

              <div class="border-t border-gray-100 dark:border-white/10 pt-4">
                <p class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Category Allocations</p>
                <div class="space-y-3">
                  <div v-for="cat in categories" :key="cat.id" class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" :style="{ backgroundColor: cat.color + '20' }">
                      <div class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: cat.color }" />
                    </div>
                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ cat.name }}</span>
                    <input
                      v-model.number="catAllocations[cat.id]"
                      type="number"
                      min="0"
                      step="0.01"
                      placeholder="0"
                      class="w-32 px-3 py-2 rounded-lg border border-gray-200 dark:border-white/20 bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                    />
                  </div>
                </div>
              </div>

              <div class="flex gap-3 pt-2">
                <button type="button" @click="showCreateModal = false" class="flex-1 py-2.5 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90">Save Budget</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { PlusIcon, XMarkIcon, ChevronLeftIcon, ChevronRightIcon, ChartPieIcon } from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { Budget, BudgetCategory, Category } from '@/types'

const props = defineProps<{
  budget: Budget | null
  categories: Category[]
  categorySpending: Record<number, number>
  month: number
  year: number
}>()

const { formatPHP } = useCurrency()

const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const monthName = computed(() => months[props.month - 1])

function changeMonth(delta: number) {
  let m = props.month + delta
  let y = props.year
  if (m > 12) { m = 1; y++ }
  if (m < 1) { m = 12; y-- }
  router.get('/budgets', { month: m, year: y }, { preserveState: false })
}

function getCategorySpent(categoryId: number): number {
  return props.categorySpending[categoryId] || 0
}

function getCategoryPercent(bc: BudgetCategory): number {
  if (bc.allocated_amount <= 0) return 0
  return (getCategorySpent(bc.category_id) / bc.allocated_amount) * 100
}

function getRemainingCategory(bc: BudgetCategory): number {
  return bc.allocated_amount - getCategorySpent(bc.category_id)
}

const totalSpent = computed(() => {
  if (!props.budget?.budget_categories) return 0
  return props.budget.budget_categories.reduce((sum, bc) => sum + getCategorySpent(bc.category_id), 0)
})

const remaining = computed(() => (props.budget?.total_budget || 0) - totalSpent.value)
const overallPercent = computed(() => {
  if (!props.budget?.total_budget) return 0
  return (totalSpent.value / props.budget.total_budget) * 100
})

// Modal
const showCreateModal = ref(false)
const catAllocations = ref<Record<number, number>>({})
const budgetForm = ref({
  name: `${monthName.value} ${props.year} Budget`,
  month: props.month,
  year: props.year,
  total_budget: 0,
})

function createBudget() {
  const categories = Object.entries(catAllocations.value)
    .filter(([, amount]) => amount > 0)
    .map(([category_id, allocated_amount]) => ({ category_id: Number(category_id), allocated_amount }))

  router.post('/budgets', { ...budgetForm.value, categories }, {
    onSuccess: () => { showCreateModal.value = false },
  })
}
</script>

