<template>
  <AppLayout title="Accounts">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Accounts</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your bank accounts and wallets</p>
      </div>
      <button @click="showCreateModal = true" class="flex items-center gap-2 px-4 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90 transition-all shadow-lg">
        <PlusIcon class="w-4 h-4" />
        Add Account
      </button>
    </div>

    <!-- Total Balance Card -->
    <div class="gradient-primary rounded-2xl p-6 mb-6 text-white shadow-xl">
      <p class="text-sm text-white/70 mb-1">Total Balance</p>
      <p class="text-4xl font-bold mb-4">{{ formatPHP(totalBalance) }}</p>
      <div class="flex items-center gap-6 text-sm text-white/70">
        <span>{{ accounts.length }} account(s)</span>
        <span>PHP</span>
      </div>
    </div>

    <!-- Accounts Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="account in accounts"
        :key="account.id"
        class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6 card-hover relative overflow-hidden group"
      >
        <!-- Background accent -->
        <div class="absolute top-0 right-0 w-32 h-32 rounded-bl-full opacity-5" :style="{ backgroundColor: account.color }" />

        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" :style="{ backgroundColor: account.color + '20' }">
              <component :is="accountIcon(account.type)" class="w-6 h-6" :style="{ color: account.color }" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white">{{ account.name }}</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ account.type }}</p>
            </div>
          </div>
          <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click="openEditModal(account)" class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-white/10 text-gray-500 hover:text-violet-600 transition-colors">
              <PencilIcon class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <div class="mb-3">
          <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ formatPHP(account.balance) }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ account.currency }}</p>
        </div>

        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
          <span v-if="account.bank_name">{{ account.bank_name }}</span>
          <span v-if="account.account_number">••••{{ account.account_number.slice(-4) }}</span>
          <span v-if="account.transactions_count !== undefined">{{ account.transactions_count }} txns</span>
        </div>

        <div class="mt-3 h-1 rounded-full" :style="{ backgroundColor: account.color + '40' }">
          <div class="h-full rounded-full" :style="{ width: accountPercent(account) + '%', backgroundColor: account.color }" />
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop" @click.self="closeModals">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl shadow-2xl w-full max-w-md border border-gray-200 dark:border-white/10">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ showEditModal ? 'Edit Account' : 'Add Account' }}</h3>
              <button @click="closeModals" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <form @submit.prevent="showEditModal ? updateAccount() : createAccount()" class="p-6 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account Name</label>
                <input v-model="accountForm.name" type="text" class="input-field" placeholder="BDO Savings" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account Type</label>
                <select v-model="accountForm.type" class="input-field">
                  <option value="bank">Bank</option>
                  <option value="cash">Cash</option>
                  <option value="e-wallet">E-Wallet</option>
                  <option value="credit">Credit</option>
                  <option value="investment">Investment</option>
                </select>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bank Name</label>
                  <input v-model="accountForm.bank_name" type="text" class="input-field" placeholder="BDO" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account #</label>
                  <input v-model="accountForm.account_number" type="text" class="input-field" placeholder="1234-5678" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Current Balance (₱)</label>
                <input v-model.number="accountForm.balance" type="number" step="0.01" class="input-field" :disabled="showEditModal" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Color</label>
                <input v-model="accountForm.color" type="color" class="h-10 w-full rounded-xl border border-gray-200 dark:border-white/20 cursor-pointer" />
              </div>
              <div class="flex gap-3 pt-2">
                <button type="button" @click="closeModals" class="flex-1 py-2.5 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90">{{ showEditModal ? 'Update' : 'Add Account' }}</button>
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
import {
  PlusIcon, XMarkIcon, PencilIcon,
  BanknotesIcon, CreditCardIcon, DevicePhoneMobileIcon,
  ChartBarIcon, WalletIcon
} from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { Account } from '@/types'

const props = defineProps<{ accounts: Account[] }>()
const { formatPHP } = useCurrency()

const totalBalance = computed(() => props.accounts.reduce((sum, a) => sum + a.balance, 0))
const maxBalance = computed(() => Math.max(...props.accounts.map(a => a.balance), 1))

function accountPercent(account: Account): number {
  return Math.min(100, (account.balance / maxBalance.value) * 100)
}

function accountIcon(type: string) {
  const icons: Record<string, unknown> = {
    bank: BanknotesIcon, cash: WalletIcon, 'e-wallet': DevicePhoneMobileIcon,
    credit: CreditCardIcon, investment: ChartBarIcon,
  }
  return icons[type] || BanknotesIcon
}

const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedAccount = ref<Account | null>(null)

const accountForm = ref({
  name: '', type: 'bank', bank_name: '', account_number: '', balance: 0, color: '#7C3AED',
})

function openEditModal(account: Account) {
  selectedAccount.value = account
  accountForm.value = {
    name: account.name, type: account.type,
    bank_name: account.bank_name || '',
    account_number: account.account_number || '',
    balance: account.balance,
    color: account.color,
  }
  showEditModal.value = true
}

function closeModals() {
  showCreateModal.value = false
  showEditModal.value = false
  selectedAccount.value = null
  accountForm.value = { name: '', type: 'bank', bank_name: '', account_number: '', balance: 0, color: '#7C3AED' }
}

function createAccount() {
  router.post('/accounts', accountForm.value, { onSuccess: () => closeModals() })
}

function updateAccount() {
  if (!selectedAccount.value) return
  router.put(`/accounts/${selectedAccount.value.id}`, accountForm.value, { onSuccess: () => closeModals() })
}
</script>

