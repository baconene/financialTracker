<template>
  <AppLayout :title="account.name">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-4">
      <Link href="/accounts" class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors text-gray-600 dark:text-gray-300 shrink-0">
        <ArrowLeftIcon class="w-5 h-5" />
      </Link>
      <div class="flex-1 min-w-0">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white truncate">{{ account.name }}</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ account.type }}<span v-if="account.bank_name"> · {{ account.bank_name }}</span></p>
      </div>
    </div>

    <!-- Account Card -->
    <div class="rounded-2xl p-6 mb-5 text-white shadow-2xl relative overflow-hidden" :style="{ backgroundColor: account.color }">
      <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent" />
      <div class="absolute -right-8 -top-8 w-36 h-36 rounded-full bg-white/10" />
      <div class="absolute -right-4 top-12 w-20 h-20 rounded-full bg-white/5" />
      <div class="relative">
        <p class="text-sm text-white/70 mb-1">Current Balance</p>
        <p class="text-4xl font-bold tracking-tight mb-4">{{ formatPHP(account.balance) }}</p>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3 text-sm text-white/80">
            <span v-if="account.account_number" class="font-mono tracking-widest">••••{{ account.account_number.slice(-4) }}</span>
            <span class="px-2 py-0.5 rounded-full bg-white/20 text-xs font-medium">{{ account.currency || 'PHP' }}</span>
          </div>
          <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center">
            <component :is="accountIcon(account.type)" class="w-6 h-6 text-white" />
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions — banking-style circular buttons -->
    <div class="flex justify-center gap-10 mb-6">
      <button @click="openAddModal('income')" class="flex flex-col items-center gap-1.5 group">
        <div class="w-14 h-14 rounded-full bg-emerald-500 group-hover:bg-emerald-400 flex items-center justify-center shadow-lg shadow-emerald-500/30 transition-all group-active:scale-95">
          <ArrowDownTrayIcon class="w-6 h-6 text-white" />
        </div>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Receive</span>
      </button>

      <button @click="openAddModal('expense')" class="flex flex-col items-center gap-1.5 group">
        <div class="w-14 h-14 rounded-full bg-red-500 group-hover:bg-red-400 flex items-center justify-center shadow-lg shadow-red-500/30 transition-all group-active:scale-95">
          <ArrowUpTrayIcon class="w-6 h-6 text-white" />
        </div>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Pay</span>
      </button>

      <button @click="showEditModal = true" class="flex flex-col items-center gap-1.5 group">
        <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-white/10 group-hover:bg-gray-200 dark:group-hover:bg-white/20 flex items-center justify-center transition-all group-active:scale-95">
          <PencilIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
        </div>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Manage</span>
      </button>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-3 gap-3 mb-5">
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-3 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Income</p>
        <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400 truncate">{{ formatShort(summary.income) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-3 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Expenses</p>
        <p class="text-sm font-bold text-red-600 dark:text-red-400 truncate">{{ formatShort(summary.expenses) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-3 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Transactions</p>
        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ summary.total }}</p>
      </div>
    </div>

    <!-- Transactions -->
    <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-white/10">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Transaction History</h3>
        <span class="text-xs text-gray-400 dark:text-gray-500">{{ transactions.meta.total }} records</span>
      </div>

      <!-- Empty state -->
      <div v-if="transactions.data.length === 0" class="py-14 text-center px-6">
        <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-3">
          <CreditCardIcon class="w-7 h-7 text-gray-400 dark:text-gray-600" />
        </div>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No transactions yet</p>
        <p class="text-xs text-gray-500 dark:text-gray-500 mb-5">Use the Receive or Pay buttons above to add your first record.</p>
      </div>

      <!-- Transaction list -->
      <div v-else class="divide-y divide-gray-50 dark:divide-white/5">
        <div
          v-for="txn in transactions.data"
          :key="txn.id"
          class="flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group"
        >
          <!-- Icon -->
          <div class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center"
            :class="txn.type === 'income' ? 'bg-emerald-50 dark:bg-emerald-500/10' : 'bg-red-50 dark:bg-red-500/10'">
            <span class="text-base leading-none">{{ txnEmoji(txn) }}</span>
          </div>

          <!-- Details -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ txn.description }}</p>
            <div class="flex items-center gap-1.5 mt-0.5">
              <span v-if="txn.category" class="text-[10px] px-1.5 py-0.5 rounded-full font-medium" :style="{ backgroundColor: txn.category.color + '20', color: txn.category.color }">
                {{ txn.category.name }}
              </span>
              <span class="text-[10px] text-gray-400">{{ formatDate(txn.transaction_date) }}</span>
            </div>
          </div>

          <!-- Amount + delete -->
          <div class="flex items-center gap-1.5 shrink-0">
            <div class="text-right">
              <p class="text-sm font-bold" :class="txn.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                {{ txn.type === 'income' ? '+' : '-' }}{{ formatPHP(txn.amount) }}
              </p>
            </div>
            <button
              @click="confirmDelete(txn)"
              class="opacity-0 group-hover:opacity-100 w-7 h-7 flex items-center justify-center rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all"
            >
              <TrashIcon class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="transactions.meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-white/10">
        <Link
          v-if="transactions.links.prev" :href="transactions.links.prev"
          class="px-4 py-2 text-xs font-medium bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-white/20 transition-colors"
        >← Previous</Link>
        <span v-else class="px-4 py-2 text-xs text-gray-300 dark:text-gray-600">← Previous</span>

        <span class="text-xs text-gray-500 dark:text-gray-400">{{ transactions.meta.current_page }} / {{ transactions.meta.last_page }}</span>

        <Link
          v-if="transactions.links.next" :href="transactions.links.next"
          class="px-4 py-2 text-xs font-medium bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-white/20 transition-colors"
        >Next →</Link>
        <span v-else class="px-4 py-2 text-xs text-gray-300 dark:text-gray-600">Next →</span>
      </div>
    </div>

    <Teleport to="body">
      <!-- Add Transaction Modal -->
      <Transition name="fade">
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 modal-backdrop" @click.self="showAddModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md border-t sm:border border-gray-200 dark:border-white/10 max-h-[92vh] overflow-y-auto">

            <!-- Drag handle (mobile) -->
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
              <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-white/20" />
            </div>

            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-white/10">
              <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                  {{ txnForm.type === 'income' ? 'Add Income' : 'Add Expense' }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ account.name }}</p>
              </div>
              <button @click="showAddModal = false" class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 text-gray-500 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>

            <form @submit.prevent="submitTransaction" class="p-5 space-y-4">
              <!-- Type toggle -->
              <div class="flex gap-2 p-1 bg-gray-100 dark:bg-white/5 rounded-xl">
                <button v-for="t in ['expense', 'income']" :key="t" type="button"
                  @click="txnForm.type = t as 'income' | 'expense'"
                  class="flex-1 py-2 rounded-lg text-sm font-medium transition-all capitalize"
                  :class="txnForm.type === t
                    ? (t === 'income' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-red-500 text-white shadow-sm')
                    : 'text-gray-500 dark:text-gray-400'"
                >{{ t }}</button>
              </div>

              <!-- Amount — prominent input -->
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-gray-400">₱</span>
                <input
                  v-model.number="txnForm.amount"
                  type="number" min="0.01" step="0.01"
                  class="w-full pl-10 pr-4 py-4 text-2xl font-bold rounded-xl border transition-all outline-none"
                  :class="txnForm.type === 'income'
                    ? 'border-emerald-300 dark:border-emerald-500/40 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 focus:ring-2 focus:ring-emerald-500/30'
                    : 'border-red-300 dark:border-red-500/40 bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300 focus:ring-2 focus:ring-red-500/30'"
                  placeholder="0.00" required
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Description</label>
                <input v-model="txnForm.description" type="text" class="input-field" placeholder="What was this for?" required />
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Category</label>
                <select v-model="txnForm.category_id" class="input-field">
                  <option value="">No category</option>
                  <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Date</label>
                <input v-model="txnForm.transaction_date" type="date" class="input-field" required />
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Notes <span class="normal-case font-normal">(optional)</span></label>
                <textarea v-model="txnForm.notes" class="input-field" rows="2" placeholder="Additional notes..." />
              </div>

              <div class="flex gap-3 pt-1 pb-safe">
                <button type="button" @click="showAddModal = false"
                  class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">
                  Cancel
                </button>
                <button type="submit" :disabled="saving"
                  class="flex-1 py-3 text-white rounded-xl text-sm font-semibold disabled:opacity-50 transition-all"
                  :class="txnForm.type === 'income' ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-red-500 hover:bg-red-600'">
                  {{ saving ? 'Saving…' : (txnForm.type === 'income' ? '+ Add Income' : '- Add Expense') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>

      <!-- Delete Confirm -->
      <Transition name="fade">
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop" @click.self="deleteTarget = null">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl shadow-2xl w-full max-w-sm border border-gray-200 dark:border-white/10 p-6">
            <div class="w-12 h-12 bg-red-100 dark:bg-red-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <TrashIcon class="w-6 h-6 text-red-500" />
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white text-center mb-1">Delete Transaction?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">
              "<span class="font-medium text-gray-700 dark:text-gray-300">{{ deleteTarget.description }}</span>" will be removed and the balance reversed.
            </p>
            <div class="flex gap-3">
              <button @click="deleteTarget = null" class="flex-1 py-2.5 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium">Cancel</button>
              <button @click="doDelete" :disabled="deleting" class="flex-1 py-2.5 bg-red-500 text-white rounded-xl text-sm font-semibold hover:bg-red-600 disabled:opacity-50">
                {{ deleting ? 'Deleting…' : 'Delete' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Edit Account Modal -->
      <Transition name="fade">
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 modal-backdrop" @click.self="showEditModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md border-t sm:border border-gray-200 dark:border-white/10 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
              <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-white/20" />
            </div>
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-white/10">
              <h3 class="text-base font-semibold text-gray-900 dark:text-white">Edit Account</h3>
              <button @click="showEditModal = false" class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 text-gray-500 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>
            <form @submit.prevent="updateAccount" class="p-5 space-y-4">
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Account Name</label>
                <input v-model="editForm.name" type="text" class="input-field" required />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Account Type</label>
                <select v-model="editForm.type" class="input-field">
                  <option value="bank">Bank</option>
                  <option value="cash">Cash</option>
                  <option value="e-wallet">E-Wallet</option>
                  <option value="credit">Credit</option>
                  <option value="investment">Investment</option>
                </select>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Bank Name</label>
                  <input v-model="editForm.bank_name" type="text" class="input-field" placeholder="BDO" />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Account No.</label>
                  <input v-model="editForm.account_number" type="text" class="input-field" placeholder="1234-5678" />
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Card Color</label>
                <input v-model="editForm.color" type="color" class="h-10 w-full rounded-xl border border-gray-200 dark:border-white/20 cursor-pointer" />
              </div>
              <div class="flex gap-3 pt-1 pb-2">
                <button type="button" @click="showEditModal = false" class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium">Cancel</button>
                <button type="submit" :disabled="editSaving" class="flex-1 py-3 gradient-primary text-white rounded-xl text-sm font-semibold hover:opacity-90 disabled:opacity-50">
                  {{ editSaving ? 'Saving…' : 'Update' }}
                </button>
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
  ArrowLeftIcon, PencilIcon, XMarkIcon, CreditCardIcon,
  BanknotesIcon, WalletIcon, DevicePhoneMobileIcon, ChartBarIcon,
  ArrowDownTrayIcon, ArrowUpTrayIcon, TrashIcon,
} from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { Account, Transaction, Category } from '@/types'
import dayjs from 'dayjs'

interface Paginated<T> {
  data: T[]
  links: { prev: string | null; next: string | null }
  meta: { current_page: number; last_page: number; total: number; per_page: number; from: number | null; to: number | null }
}
interface Summary { income: number; expenses: number; total: number }

const props = defineProps<{
  account: Account
  transactions: Paginated<Transaction>
  summary: Summary
  categories: Category[]
}>()

const { formatPHP, formatShort } = useCurrency()

function formatDate(d: string) { return dayjs(d).format('MMM D, YYYY') }

function accountIcon(type: string) {
  const m: Record<string, unknown> = {
    bank: BanknotesIcon, cash: WalletIcon, 'e-wallet': DevicePhoneMobileIcon,
    credit: CreditCardIcon, investment: ChartBarIcon,
  }
  return m[type] || BanknotesIcon
}

function txnEmoji(txn: Transaction): string {
  const map: Record<string, string> = {
    Salary: '💼', Freelance: '💻', Food: '🍽️', Transportation: '🚗',
    Utilities: '⚡', Healthcare: '🏥', Shopping: '🛍️', Entertainment: '🎬',
    Education: '📚', Savings: '💰', Business: '🏢', Investment: '📈',
  }
  if (txn.category) {
    for (const [k, e] of Object.entries(map)) {
      if (txn.category.name.includes(k)) return e
    }
  }
  return txn.type === 'income' ? '💵' : '💸'
}

// ── Add Transaction ──────────────────────────────────────────────
const showAddModal = ref(false)
const saving = ref(false)
const txnForm = ref({
  type: 'expense' as 'income' | 'expense',
  description: '',
  amount: null as number | null,
  category_id: '' as string | number,
  transaction_date: new Date().toISOString().split('T')[0],
  notes: '',
})

const filteredCategories = computed(() =>
  props.categories.filter(c => c.type === txnForm.value.type || c.type === 'both')
)

function openAddModal(type: 'income' | 'expense') {
  txnForm.value = { type, description: '', amount: null, category_id: '', transaction_date: new Date().toISOString().split('T')[0], notes: '' }
  showAddModal.value = true
}

function submitTransaction() {
  saving.value = true
  router.post('/transactions', { ...txnForm.value, account_id: props.account.id }, {
    onSuccess: () => { showAddModal.value = false },
    onFinish: () => { saving.value = false },
  })
}

// ── Delete Transaction ───────────────────────────────────────────
const deleteTarget = ref<Transaction | null>(null)
const deleting = ref(false)
function confirmDelete(txn: Transaction) { deleteTarget.value = txn }
function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  router.delete(`/transactions/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null },
    onFinish: () => { deleting.value = false },
  })
}

// ── Edit Account ─────────────────────────────────────────────────
const showEditModal = ref(false)
const editSaving = ref(false)
const editForm = ref({
  name: props.account.name,
  type: props.account.type,
  bank_name: props.account.bank_name || '',
  account_number: props.account.account_number || '',
  color: props.account.color,
})

function updateAccount() {
  editSaving.value = true
  router.put(`/accounts/${props.account.id}`, editForm.value, {
    onSuccess: () => { showEditModal.value = false },
    onFinish: () => { editSaving.value = false },
  })
}
</script>
