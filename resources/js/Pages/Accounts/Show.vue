<template>
  <AppLayout :title="account.name">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <Link href="/accounts" class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors text-gray-600 dark:text-gray-300">
        <ArrowLeftIcon class="w-5 h-5" />
      </Link>
      <div class="flex-1 min-w-0">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white truncate">{{ account.name }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ account.type }}<span v-if="account.bank_name"> · {{ account.bank_name }}</span></p>
      </div>
      <button @click="showEditModal = true" class="flex items-center gap-1.5 px-3 py-2 bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium transition-colors">
        <PencilIcon class="w-4 h-4" />
        <span class="hidden sm:inline">Edit</span>
      </button>
    </div>

    <!-- Account Card -->
    <div class="rounded-2xl p-5 mb-6 text-white shadow-xl relative overflow-hidden" :style="{ backgroundColor: account.color }">
      <div class="absolute inset-0 opacity-20 bg-gradient-to-br from-white/30 to-transparent" />
      <div class="relative">
        <div class="flex items-start justify-between mb-4">
          <div>
            <p class="text-sm text-white/70 mb-1">Current Balance</p>
            <p class="text-3xl font-bold">{{ formatPHP(account.balance) }}</p>
          </div>
          <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
            <component :is="accountIcon(account.type)" class="w-6 h-6 text-white" />
          </div>
        </div>
        <div class="flex gap-4 text-sm text-white/80">
          <span v-if="account.account_number" class="font-mono">••••{{ account.account_number.slice(-4) }}</span>
          <span>{{ account.currency || 'PHP' }}</span>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-3 gap-3 mb-6">
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Income</p>
        <p class="text-base font-bold text-emerald-600 dark:text-emerald-400">{{ formatPHP(summary.income) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Expenses</p>
        <p class="text-base font-bold text-red-600 dark:text-red-400">{{ formatPHP(summary.expenses) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Transactions</p>
        <p class="text-base font-bold text-gray-900 dark:text-white">{{ summary.total }}</p>
      </div>
    </div>

    <!-- Transactions -->
    <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-white/10">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Transactions</h3>
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ transactions.meta.total }} total</span>
      </div>

      <!-- Empty state -->
      <div v-if="transactions.data.length === 0" class="py-16 text-center">
        <CreditCardIcon class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
        <p class="text-sm text-gray-500 dark:text-gray-400">No transactions in this account yet.</p>
      </div>

      <!-- Transaction list -->
      <div v-else class="divide-y divide-gray-50 dark:divide-white/5">
        <div
          v-for="txn in transactions.data"
          :key="txn.id"
          class="flex items-center gap-3 p-4 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
        >
          <!-- Category icon -->
          <div class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center"
            :style="{ backgroundColor: (txn.category?.color || '#6B7280') + '20' }">
            <span class="text-sm">{{ txnEmoji(txn) }}</span>
          </div>

          <!-- Details -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ txn.description }}</p>
            <div class="flex items-center gap-2 mt-0.5">
              <span v-if="txn.category" class="text-xs px-1.5 py-0.5 rounded-full" :style="{ backgroundColor: txn.category.color + '20', color: txn.category.color }">
                {{ txn.category.name }}
              </span>
              <span class="text-xs text-gray-400">{{ formatDate(txn.transaction_date) }}</span>
            </div>
          </div>

          <!-- Amount -->
          <div class="text-right shrink-0">
            <p class="text-sm font-semibold" :class="txn.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
              {{ txn.type === 'income' ? '+' : '-' }}{{ formatPHP(txn.amount) }}
            </p>
            <p class="text-xs text-gray-400 capitalize">{{ txn.type }}</p>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="transactions.meta.last_page > 1" class="flex items-center justify-between p-4 border-t border-gray-100 dark:border-white/10">
        <Link
          v-if="transactions.links.prev"
          :href="transactions.links.prev"
          class="px-4 py-2 text-sm bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-white/20 transition-colors"
        >
          Previous
        </Link>
        <span v-else class="px-4 py-2 text-sm text-gray-400 opacity-50">Previous</span>
        <span class="text-sm text-gray-500 dark:text-gray-400">
          Page {{ transactions.meta.current_page }} of {{ transactions.meta.last_page }}
        </span>
        <Link
          v-if="transactions.links.next"
          :href="transactions.links.next"
          class="px-4 py-2 text-sm bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-white/20 transition-colors"
        >
          Next
        </Link>
        <span v-else class="px-4 py-2 text-sm text-gray-400 opacity-50">Next</span>
      </div>
    </div>

    <!-- Edit Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 modal-backdrop" @click.self="showEditModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md border-t sm:border border-gray-200 dark:border-white/10 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-white/10 sticky top-0 bg-white dark:bg-[#1A1A2E]">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Account</h3>
              <button @click="showEditModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>
            <form @submit.prevent="updateAccount" class="p-5 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account Name</label>
                <input v-model="editForm.name" type="text" class="input-field" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account Type</label>
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
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bank Name</label>
                  <input v-model="editForm.bank_name" type="text" class="input-field" placeholder="BDO" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account #</label>
                  <input v-model="editForm.account_number" type="text" class="input-field" placeholder="1234-5678" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Color</label>
                <input v-model="editForm.color" type="color" class="h-10 w-full rounded-xl border border-gray-200 dark:border-white/20 cursor-pointer" />
              </div>
              <div class="flex gap-3 pt-2 pb-2">
                <button type="button" @click="showEditModal = false" class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium">
                  Cancel
                </button>
                <button type="submit" :disabled="saving" class="flex-1 py-3 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90 disabled:opacity-50">
                  {{ saving ? 'Saving…' : 'Update Account' }}
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
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  ArrowLeftIcon, PencilIcon, XMarkIcon, CreditCardIcon,
  BanknotesIcon, WalletIcon, DevicePhoneMobileIcon, ChartBarIcon
} from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { Account, Transaction } from '@/types'
import dayjs from 'dayjs'

interface Paginated<T> {
  data: T[]
  links: { first: string; last: string; prev: string | null; next: string | null }
  meta: { current_page: number; last_page: number; total: number; per_page: number; from: number; to: number }
}

interface Summary {
  income: number
  expenses: number
  total: number
}

const props = defineProps<{
  account: Account
  transactions: Paginated<Transaction>
  summary: Summary
}>()

const { formatPHP } = useCurrency()

function formatDate(date: string): string {
  return dayjs(date).format('MMM D, YYYY')
}

function accountIcon(type: string) {
  const icons: Record<string, unknown> = {
    bank: BanknotesIcon, cash: WalletIcon, 'e-wallet': DevicePhoneMobileIcon,
    credit: CreditCardIcon, investment: ChartBarIcon,
  }
  return icons[type] || BanknotesIcon
}

function txnEmoji(txn: Transaction): string {
  const map: Record<string, string> = {
    Salary: '💼', Freelance: '💻', Food: '🍽️', Transportation: '🚗',
    Utilities: '⚡', Healthcare: '🏥', Shopping: '🛍️', Entertainment: '🎬',
    Education: '📚', Savings: '💰', Business: '🏢', Investment: '📈',
  }
  if (txn.category) {
    for (const [key, emoji] of Object.entries(map)) {
      if (txn.category.name.includes(key)) return emoji
    }
  }
  return txn.type === 'income' ? '💵' : '💸'
}

// Edit
const showEditModal = ref(false)
const saving = ref(false)
const editForm = ref({
  name: props.account.name,
  type: props.account.type,
  bank_name: props.account.bank_name || '',
  account_number: props.account.account_number || '',
  color: props.account.color,
})

function updateAccount() {
  saving.value = true
  router.put(`/accounts/${props.account.id}`, editForm.value, {
    onSuccess: () => { showEditModal.value = false },
    onFinish: () => { saving.value = false },
  })
}
</script>
