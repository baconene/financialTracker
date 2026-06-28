<template>
  <AppLayout title="Transactions">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Transactions</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ transactions.total }} transactions found</p>
      </div>
      <button @click="showCreateModal = true" class="flex items-center gap-2 px-4 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90 transition-all shadow-lg">
        <PlusIcon class="w-4 h-4" />
        Add Transaction
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4 mb-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-3 top-3 w-4 h-4 text-gray-400" />
          <input v-model="localFilters.search" type="text" placeholder="Search..." class="input-field pl-9" @input="applyFilters" />
        </div>
        <select v-model="localFilters.type" class="input-field" @change="applyFilters">
          <option value="">All Types</option>
          <option value="income">Income</option>
          <option value="expense">Expense</option>
        </select>
        <select v-model="localFilters.category_id" class="input-field" @change="applyFilters">
          <option value="">All Categories</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
        <input v-model="localFilters.date_from" type="date" class="input-field" placeholder="From" @change="applyFilters" />
        <input v-model="localFilters.date_to" type="date" class="input-field" placeholder="To" @change="applyFilters" />
      </div>
      <button v-if="hasFilters" @click="clearFilters" class="mt-3 text-xs text-red-500 hover:underline flex items-center gap-1">
        <XMarkIcon class="w-3.5 h-3.5" /> Clear filters
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-white/5">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
              <th class="px-4 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Category</th>
              <th class="px-4 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">Account</th>
              <th class="px-4 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden sm:table-cell">Date</th>
              <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
              <th class="px-4 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-white/5">
            <tr v-for="txn in transactions.data" :key="txn.id" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                    :class="txn.type === 'income' ? 'bg-emerald-100 dark:bg-emerald-500/20' : 'bg-red-100 dark:bg-red-500/20'">
                    <ArrowTrendingUpIcon v-if="txn.type === 'income'" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    <ArrowTrendingDownIcon v-else class="w-4 h-4 text-red-600 dark:text-red-400" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ txn.description }}</p>
                    <p v-if="txn.notes" class="text-xs text-gray-400 truncate max-w-48">{{ txn.notes }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-4 hidden md:table-cell">
                <span v-if="txn.category" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium" :style="{ backgroundColor: txn.category.color + '20', color: txn.category.color }">
                  {{ txn.category.name }}
                </span>
                <span v-else class="text-xs text-gray-400">-</span>
              </td>
              <td class="px-4 py-4 hidden lg:table-cell">
                <span class="text-sm text-gray-600 dark:text-gray-300">{{ txn.account?.name }}</span>
              </td>
              <td class="px-4 py-4 hidden sm:table-cell">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(txn.transaction_date) }}</span>
              </td>
              <td class="px-6 py-4 text-right">
                <span class="text-sm font-bold" :class="txn.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                  {{ txn.type === 'income' ? '+' : '-' }}{{ formatPHP(txn.amount) }}
                </span>
              </td>
              <td class="px-4 py-4 text-center">
                <button @click="deleteTransaction(txn)" class="opacity-0 group-hover:opacity-100 transition-opacity text-gray-400 hover:text-red-500">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </td>
            </tr>
            <tr v-if="transactions.data.length === 0">
              <td colspan="6" class="px-6 py-12 text-center">
                <p class="text-gray-500 dark:text-gray-400">No transactions found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-6 py-4 border-t border-gray-100 dark:border-white/10 flex items-center justify-between">
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Showing {{ transactions.from }}–{{ transactions.to }} of {{ transactions.total }}
        </p>
        <div class="flex gap-2">
          <Link
            v-for="link in transactions.links"
            :key="link.label"
            :href="link.url || ''"
            :class="[
              'px-3 py-1.5 text-sm rounded-lg transition-colors',
              link.active ? 'gradient-primary text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10',
              !link.url ? 'opacity-40 pointer-events-none' : '',
            ]"
            v-html="link.label"
          />
        </div>
      </div>
    </div>

    <!-- Create Transaction Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop" @click.self="showCreateModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl shadow-2xl w-full max-w-lg border border-gray-200 dark:border-white/10 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10 sticky top-0 bg-white dark:bg-[#1A1A2E]">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">New Transaction</h3>
              <button @click="showCreateModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <form @submit.prevent="createTransaction" class="p-6 space-y-4">
              <!-- Type Selector -->
              <div class="flex gap-2">
                <button v-for="t in ['income', 'expense']" :key="t" type="button"
                  @click="txnForm.type = t as 'income' | 'expense'"
                  class="flex-1 py-2 rounded-xl text-sm font-medium transition-all capitalize"
                  :class="txnForm.type === t ? (t === 'income' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white') : 'bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300'"
                >
                  {{ t }}
                </button>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                <input v-model="txnForm.description" type="text" class="input-field" placeholder="What was this for?" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount (₱)</label>
                <input v-model.number="txnForm.amount" type="number" min="0.01" step="0.01" class="input-field" required />
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account</label>
                  <select v-model="txnForm.account_id" class="input-field" required>
                    <option value="">Select account</option>
                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category</label>
                  <select v-model="txnForm.category_id" class="input-field">
                    <option value="">No category</option>
                    <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Date</label>
                <input v-model="txnForm.transaction_date" type="date" class="input-field" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes (optional)</label>
                <textarea v-model="txnForm.notes" class="input-field" rows="2" placeholder="Additional notes..." />
              </div>
              <div class="flex gap-3 pt-2">
                <button type="button" @click="showCreateModal = false" class="flex-1 py-2.5 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90">Add Transaction</button>
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
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  PlusIcon, XMarkIcon, MagnifyingGlassIcon, TrashIcon,
  ArrowTrendingUpIcon, ArrowTrendingDownIcon
} from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { Transaction, Account, Category, PaginatedData } from '@/types'
import dayjs from 'dayjs'

const props = defineProps<{
  transactions: PaginatedData<Transaction>
  accounts: Account[]
  categories: Category[]
  filters: Record<string, string>
}>()

const { formatPHP } = useCurrency()

const localFilters = ref({
  search: props.filters.search || '',
  type: props.filters.type || '',
  category_id: props.filters.category_id || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
})

const hasFilters = computed(() => Object.values(localFilters.value).some(v => v !== ''))

function applyFilters() {
  router.get('/transactions', localFilters.value as Record<string, string>, { preserveState: true, replace: true })
}

function clearFilters() {
  localFilters.value = { search: '', type: '', category_id: '', date_from: '', date_to: '' }
  applyFilters()
}

function formatDate(date: string): string {
  return dayjs(date).format('MMM D, YYYY')
}

// Modal
const showCreateModal = ref(false)
const txnForm = ref({
  type: 'expense' as 'income' | 'expense',
  account_id: '' as string | number,
  category_id: '' as string | number,
  amount: 0,
  description: '',
  notes: '',
  transaction_date: new Date().toISOString().split('T')[0],
})

const filteredCategories = computed(() =>
  props.categories.filter(c => c.type === txnForm.value.type || c.type === 'both')
)

function createTransaction() {
  router.post('/transactions', txnForm.value as unknown as Record<string, unknown>, {
    onSuccess: () => { showCreateModal.value = false },
  })
}

function deleteTransaction(txn: Transaction) {
  if (!confirm('Delete this transaction?')) return
  router.delete(`/transactions/${txn.id}`)
}
</script>

<style scoped>
.input-field {
  @apply w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-white/20 bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all text-sm;
}
</style>
