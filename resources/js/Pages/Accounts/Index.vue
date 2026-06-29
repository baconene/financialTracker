<template>
  <AppLayout title="Accounts">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Accounts</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your bank accounts and wallets</p>
      </div>
      <button @click="showCreateModal = true" class="flex items-center gap-2 px-4 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90 transition-all shadow-lg">
        <PlusIcon class="w-4 h-4" />
        <span class="hidden sm:inline">Add Account</span>
        <span class="sm:hidden">Add</span>
      </button>
    </div>

    <!-- Total Balance Card -->
    <div class="gradient-primary rounded-2xl p-5 mb-6 text-white shadow-xl">
      <p class="text-sm text-white/70 mb-1">Total Balance</p>
      <p class="text-3xl font-bold mb-3">{{ formatPHP(totalBalance) }}</p>
      <div class="flex items-center gap-4 text-sm text-white/70">
        <span>{{ accounts.length }} account{{ accounts.length !== 1 ? 's' : '' }}</span>
        <span>•</span>
        <span>PHP</span>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="accounts.length === 0" class="text-center py-16">
      <div class="w-16 h-16 gradient-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
        <BanknotesIcon class="w-8 h-8 text-white" />
      </div>
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No accounts yet</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Add your first account to start tracking your finances.</p>
      <button @click="showCreateModal = true" class="px-6 py-3 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90">
        Add Your First Account
      </button>
    </div>

    <!-- Accounts Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="account in accounts"
        :key="account.id"
        class="rounded-2xl p-5 relative overflow-hidden cursor-pointer transition-all duration-200 group shadow-lg hover:shadow-xl hover:scale-[1.01]"
        :style="{ background: `linear-gradient(135deg, ${account.color}ee 0%, ${account.color}99 100%)` }"
        @click="goToAccount(account)"
      >
        <!-- Decorative circles -->
        <div class="absolute -top-6 -right-6 w-32 h-32 rounded-full bg-white/10" />
        <div class="absolute -bottom-8 -right-2 w-24 h-24 rounded-full bg-white/5" />

        <!-- Header -->
        <div class="flex items-start justify-between mb-5 relative">
          <div class="flex items-center gap-3">
            <!-- Logo or default icon -->
            <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 flex items-center justify-center bg-white/20 backdrop-blur-sm">
              <img v-if="account.icon_url" :src="account.icon_url" class="w-full h-full object-contain p-1" :alt="account.name" />
              <component v-else :is="accountIcon(account.type)" class="w-6 h-6 text-white" />
            </div>
            <div>
              <h3 class="font-bold text-white leading-tight text-base">{{ account.name }}</h3>
              <p class="text-xs text-white/70 capitalize">{{ account.type }}</p>
            </div>
          </div>
          <!-- Action buttons -->
          <div class="flex gap-1.5" @click.stop>
            <button @click="openEditModal(account)"
              class="w-7 h-7 flex items-center justify-center rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors" title="Edit">
              <PencilIcon class="w-3.5 h-3.5" />
            </button>
            <button @click="confirmDelete(account)"
              class="w-7 h-7 flex items-center justify-center rounded-lg bg-white/20 text-white hover:bg-red-400/60 transition-colors" title="Delete">
              <TrashIcon class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Balance -->
        <div class="mb-4 relative">
          <p class="text-2xl font-bold text-white">{{ formatPHP(account.balance) }}</p>
          <p class="text-xs text-white/60 mt-0.5">{{ account.currency || 'PHP' }}</p>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between text-xs text-white/70 relative">
          <span v-if="account.bank_name">{{ account.bank_name }}</span>
          <span v-if="account.account_number" class="font-mono">••••{{ account.account_number.slice(-4) }}</span>
          <span v-if="account.transactions_count !== undefined" class="flex items-center gap-1">
            <CreditCardIcon class="w-3 h-3" />
            {{ account.transactions_count }} txns
          </span>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 modal-backdrop" @click.self="closeModals">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md border-t sm:border border-gray-200 dark:border-white/10 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-white/10 sticky top-0 bg-white dark:bg-[#1A1A2E]">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ showEditModal ? 'Edit Account' : 'Add Account' }}</h3>
              <button @click="closeModals" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>
            <form @submit.prevent="showEditModal ? updateAccount() : createAccount()" class="p-5 space-y-4">
              <!-- Icon upload -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account Logo</label>
                <div class="flex items-center gap-4">
                  <!-- Preview -->
                  <div class="w-16 h-16 rounded-xl flex items-center justify-center shrink-0 overflow-hidden border-2 border-dashed border-gray-200 dark:border-white/20 transition-colors"
                    :style="iconPreviewUrl ? { borderColor: accountForm.color } : {}">
                    <img v-if="iconPreviewUrl" :src="iconPreviewUrl" class="w-full h-full object-contain p-1" />
                    <component v-else :is="accountIcon(accountForm.type)" class="w-7 h-7 text-gray-400" />
                  </div>
                  <div class="flex-1 space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-xl border border-gray-200 dark:border-white/20 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                      <ArrowUpTrayIcon class="w-4 h-4 shrink-0" />
                      <span>{{ iconFile ? iconFile.name : 'Upload logo…' }}</span>
                      <input type="file" accept="image/*" class="hidden" @change="onIconChange" />
                    </label>
                    <button v-if="iconPreviewUrl" type="button" @click="removeIcon"
                      class="text-xs text-red-500 hover:underline">Remove logo</button>
                  </div>
                </div>
              </div>

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
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bank Name</label>
                  <input v-model="accountForm.bank_name" type="text" class="input-field" placeholder="BDO" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account #</label>
                  <input v-model="accountForm.account_number" type="text" class="input-field" placeholder="1234-5678" />
                </div>
              </div>
              <div v-if="!showEditModal">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Starting Balance (₱)</label>
                <input v-model.number="accountForm.balance" type="number" step="0.01" min="0" class="input-field" placeholder="0.00" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Card Background Color</label>
                <div class="flex items-center gap-3">
                  <!-- Live mini-card preview -->
                  <div class="w-16 h-10 rounded-xl shrink-0 shadow-md transition-all duration-300"
                    :style="{ background: `linear-gradient(135deg, ${accountForm.color}ee, ${accountForm.color}88)` }" />
                  <input v-model="accountForm.color" type="color" class="h-10 w-12 rounded-xl border border-gray-200 dark:border-white/20 cursor-pointer shrink-0" />
                  <div class="flex gap-2 flex-wrap">
                    <button v-for="c in colorPresets" :key="c" type="button" @click="accountForm.color = c"
                      class="w-7 h-7 rounded-full border-2 transition-all shadow-sm"
                      :style="{ backgroundColor: c, borderColor: accountForm.color === c ? '#fff' : 'transparent' }"
                    />
                  </div>
                </div>
              </div>
              <div class="flex gap-3 pt-2 pb-2">
                <button type="button" @click="closeModals" class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">
                  Cancel
                </button>
                <button type="submit" :disabled="saving" class="flex-1 py-3 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90 disabled:opacity-50">
                  {{ saving ? 'Saving…' : (showEditModal ? 'Update' : 'Add Account') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>

      <!-- Delete Confirm Modal -->
      <Transition name="fade">
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop" @click.self="deleteTarget = null">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl shadow-2xl w-full max-w-sm border border-gray-200 dark:border-white/10 p-6">
            <div class="w-12 h-12 bg-red-100 dark:bg-red-500/20 rounded-xl flex items-center justify-center mx-auto mb-4">
              <TrashIcon class="w-6 h-6 text-red-500" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white text-center mb-2">Delete Account?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">
              <strong class="text-gray-900 dark:text-white">{{ deleteTarget?.name }}</strong> and all its transactions will be permanently deleted.
            </p>
            <div class="flex gap-3">
              <button @click="deleteTarget = null" class="flex-1 py-2.5 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5">
                Cancel
              </button>
              <button @click="deleteAccount" :disabled="deleting" class="flex-1 py-2.5 bg-red-500 text-white rounded-xl text-sm font-medium hover:bg-red-600 disabled:opacity-50">
                {{ deleting ? 'Deleting…' : 'Delete' }}
              </button>
            </div>
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
  PlusIcon, XMarkIcon, PencilIcon, TrashIcon, ArrowRightIcon,
  BanknotesIcon, CreditCardIcon, DevicePhoneMobileIcon,
  ChartBarIcon, WalletIcon, ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { Account } from '@/types'

const props = defineProps<{ accounts: Account[] }>()
const { formatPHP } = useCurrency()

const totalBalance = computed(() =>
  props.accounts.reduce((sum, a) => sum + parseFloat(String(a.balance ?? 0)), 0)
)
const maxBalance = computed(() =>
  Math.max(...props.accounts.map(a => parseFloat(String(a.balance ?? 0))), 1)
)

function accountPercent(account: Account): number {
  return Math.min(100, (parseFloat(String(account.balance ?? 0)) / maxBalance.value) * 100)
}

function accountIcon(type: string) {
  const icons: Record<string, unknown> = {
    bank: BanknotesIcon, cash: WalletIcon, 'e-wallet': DevicePhoneMobileIcon,
    credit: CreditCardIcon, investment: ChartBarIcon,
  }
  return icons[type] || BanknotesIcon
}

const colorPresets = ['#7C3AED', '#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#EC4899']

function goToAccount(account: Account) {
  router.visit(`/accounts/${account.id}`)
}

// Create / Edit
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedAccount = ref<Account | null>(null)
const saving = ref(false)

const accountForm = ref({
  name: '', type: 'bank', bank_name: '', account_number: '', balance: 0, color: '#7C3AED',
})

// Icon upload state
const iconFile = ref<File | null>(null)
const iconPreviewUrl = ref<string | null>(null)
const removeIconFlag = ref(false)

function onIconChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  iconFile.value = file
  removeIconFlag.value = false
  iconPreviewUrl.value = URL.createObjectURL(file)
}

function removeIcon() {
  iconFile.value = null
  iconPreviewUrl.value = null
  removeIconFlag.value = true
}

function openEditModal(account: Account) {
  selectedAccount.value = account
  accountForm.value = {
    name: account.name, type: account.type,
    bank_name: account.bank_name || '',
    account_number: account.account_number || '',
    balance: parseFloat(String(account.balance ?? 0)),
    color: account.color,
  }
  iconFile.value = null
  removeIconFlag.value = false
  iconPreviewUrl.value = (account as any).icon_url || null
  showEditModal.value = true
}

function closeModals() {
  showCreateModal.value = false
  showEditModal.value = false
  selectedAccount.value = null
  accountForm.value = { name: '', type: 'bank', bank_name: '', account_number: '', balance: 0, color: '#7C3AED' }
  iconFile.value = null
  iconPreviewUrl.value = null
  removeIconFlag.value = false
}

function createAccount() {
  saving.value = true
  const data: Record<string, unknown> = { ...accountForm.value }
  if (iconFile.value) data.icon = iconFile.value
  router.post('/accounts', data, {
    forceFormData: true,
    onSuccess: () => closeModals(),
    onFinish: () => { saving.value = false },
  })
}

function updateAccount() {
  if (!selectedAccount.value) return
  saving.value = true
  const data: Record<string, unknown> = { ...accountForm.value, _method: 'PUT' }
  if (iconFile.value) data.icon = iconFile.value
  if (removeIconFlag.value) data.remove_icon = '1'
  router.post(`/accounts/${selectedAccount.value.id}`, data, {
    forceFormData: true,
    onSuccess: () => closeModals(),
    onFinish: () => { saving.value = false },
  })
}

// Delete
const deleteTarget = ref<Account | null>(null)
const deleting = ref(false)

function confirmDelete(account: Account) {
  deleteTarget.value = account
}

function deleteAccount() {
  if (!deleteTarget.value) return
  deleting.value = true
  router.delete(`/accounts/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null },
    onFinish: () => { deleting.value = false },
  })
}
</script>
