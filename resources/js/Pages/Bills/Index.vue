<template>
  <AppLayout title="Bills">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Bills & Subscriptions</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your recurring bills and subscriptions</p>
      </div>
      <button @click="showCreateModal = true" class="flex items-center gap-2 gradient-primary text-white rounded-xl font-medium hover:opacity-90 transition-all shadow-lg px-3 py-2.5 sm:px-4">
        <PlusIcon class="w-4 h-4 shrink-0" />
        <span class="hidden sm:inline text-sm">Add Bill</span>
      </button>
    </div>

    <!-- Status Filters -->
    <div class="flex gap-2 mb-6 flex-wrap">
      <button v-for="f in filters" :key="f.value" @click="activeFilter = f.value"
        class="px-4 py-2 rounded-xl text-sm font-medium transition-all"
        :class="activeFilter === f.value ? 'gradient-primary text-white shadow-lg' : 'bg-white dark:bg-[#1A1A2E] text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/5'">
        {{ f.label }}
        <span class="ml-1 text-xs opacity-70">({{ filterCount(f.value) }})</span>
      </button>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-red-50 dark:bg-red-500/10 rounded-xl border border-red-200 dark:border-red-500/30 p-4">
        <p class="text-xs text-red-600 dark:text-red-400 mb-1">Overdue</p>
        <p class="text-xl font-bold text-red-700 dark:text-red-300">{{ formatPHP(statusTotal('overdue')) }}</p>
        <p class="text-xs text-red-500 mt-0.5">{{ filterCount('overdue') }} bill(s)</p>
      </div>
      <div class="bg-amber-50 dark:bg-amber-500/10 rounded-xl border border-amber-200 dark:border-amber-500/30 p-4">
        <p class="text-xs text-amber-600 dark:text-amber-400 mb-1">Due Soon</p>
        <p class="text-xl font-bold text-amber-700 dark:text-amber-300">{{ formatPHP(statusTotal('due_soon')) }}</p>
        <p class="text-xs text-amber-500 mt-0.5">{{ filterCount('due_soon') }} bill(s)</p>
      </div>
      <div class="bg-emerald-50 dark:bg-emerald-500/10 rounded-xl border border-emerald-200 dark:border-emerald-500/30 p-4">
        <p class="text-xs text-emerald-600 dark:text-emerald-400 mb-1">Upcoming</p>
        <p class="text-xl font-bold text-emerald-700 dark:text-emerald-300">{{ formatPHP(statusTotal('upcoming')) }}</p>
        <p class="text-xs text-emerald-500 mt-0.5">{{ filterCount('upcoming') }} bill(s)</p>
      </div>
    </div>

    <!-- Bills Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="bill in filteredBills"
        :key="bill.id"
        class="bg-white dark:bg-[#1A1A2E] rounded-2xl border overflow-hidden card-hover"
        :class="borderClass(bill.status)"
      >
        <div class="p-5">
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-xl flex items-center justify-center" :style="{ backgroundColor: bill.color + '20' }">
                <span class="text-xl">{{ billEmoji(bill) }}</span>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ bill.name }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ bill.payee }}</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="statusClass(bill.status)">
              {{ statusLabel(bill.status) }}
            </span>
          </div>

          <div class="space-y-2 mb-4">
            <div class="flex justify-between">
              <span class="text-xs text-gray-500 dark:text-gray-400">Amount</span>
              <span class="text-sm font-bold" :style="{ color: bill.color }">{{ formatPHP(bill.amount) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-xs text-gray-500 dark:text-gray-400">Next Due</span>
              <span class="text-xs font-medium text-gray-900 dark:text-white">{{ formatDate(bill.next_due_date) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-xs text-gray-500 dark:text-gray-400">Frequency</span>
              <span class="text-xs text-gray-600 dark:text-gray-300 capitalize">{{ bill.frequency }}</span>
            </div>
            <div v-if="bill.auto_pay" class="flex items-center gap-1">
              <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-500" />
              <span class="text-xs text-emerald-600 dark:text-emerald-400">Auto Pay</span>
            </div>
          </div>

          <div class="flex gap-2">
            <button
              @click="openMarkPaidModal(bill)"
              class="flex-1 py-2 text-sm font-medium rounded-xl transition-all"
              :class="bill.status === 'overdue' ? 'bg-red-500 text-white hover:bg-red-600' : 'gradient-primary text-white hover:opacity-90'"
            >
              Mark as Paid
            </button>
            <button @click="deleteBill(bill)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300 hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-500 transition-colors">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <Teleport to="body">
      <!-- Add Bill Modal (bottom-sheet on mobile) -->
      <Transition name="fade">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 modal-backdrop" @click.self="showCreateModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-lg border border-gray-200 dark:border-white/10 max-h-[92vh] overflow-y-auto">
            <!-- Drag handle -->
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
              <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-white/20" />
            </div>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10 sticky top-0 bg-white dark:bg-[#1A1A2E] z-10">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Bill</h3>
              <button @click="showCreateModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <form @submit.prevent="createBill" class="p-6 space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Bill Name</label>
                  <input v-model="billForm.name" type="text" class="input-field" placeholder="Electric Bill" required />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Payee</label>
                  <input v-model="billForm.payee" type="text" class="input-field" placeholder="Meralco" />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Amount (₱)</label>
                  <input v-model.number="billForm.amount" type="number" min="0" step="0.01" class="input-field" required />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Frequency</label>
                  <select v-model="billForm.frequency" class="input-field">
                    <option value="weekly">Weekly</option>
                    <option value="biweekly">Biweekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="annually">Annually</option>
                    <option value="one-time">One-time</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Next Due Date</label>
                  <input v-model="billForm.next_due_date" type="date" class="input-field" required />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Category</label>
                  <input v-model="billForm.category" type="text" class="input-field" placeholder="Utilities" />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Color</label>
                  <input v-model="billForm.color" type="color" class="h-10 w-full rounded-xl border border-gray-200 dark:border-white/20 cursor-pointer" />
                </div>
                <div class="flex items-end pb-1">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="billForm.auto_pay" type="checkbox" class="w-4 h-4 rounded accent-violet-600" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">Auto Pay</span>
                  </label>
                </div>
              </div>
              <div class="flex gap-3 pt-2 pb-safe">
                <button type="button" @click="showCreateModal = false" class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">Cancel</button>
                <button type="submit" class="flex-1 py-3 gradient-primary text-white rounded-xl text-sm font-semibold hover:opacity-90 shadow-lg">Add Bill</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>

      <!-- Mark Paid Modal (bottom-sheet on mobile) -->
      <Transition name="fade">
        <div v-if="showMarkPaidModal && selectedBill" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 modal-backdrop" @click.self="showMarkPaidModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md border border-gray-200 dark:border-white/10">
            <!-- Drag handle -->
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
              <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-white/20" />
            </div>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mark as Paid</h3>
              <button @click="showMarkPaidModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <form @submit.prevent="submitMarkPaid" class="p-6 space-y-4">
              <p class="text-sm text-gray-500 dark:text-gray-400">Marking paid: <strong class="text-gray-900 dark:text-white">{{ selectedBill.name }}</strong></p>
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Amount Paid (₱)</label>
                <input v-model.number="markPaidForm.amount" type="number" min="0.01" step="0.01" class="input-field" required />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Payment Date</label>
                <input v-model="markPaidForm.payment_date" type="date" class="input-field" required />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Reference Number</label>
                <input v-model="markPaidForm.reference_number" type="text" class="input-field" placeholder="Optional" />
              </div>
              <div class="flex gap-3 pb-safe">
                <button type="button" @click="showMarkPaidModal = false" class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">Cancel</button>
                <button type="submit" class="flex-1 py-3 gradient-success text-white rounded-xl text-sm font-semibold hover:opacity-90 shadow-lg">Confirm Payment</button>
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
import { PlusIcon, XMarkIcon, CheckCircleIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { Bill } from '@/types'
import dayjs from 'dayjs'

const props = defineProps<{ bills: Bill[] }>()
const { formatPHP } = useCurrency()

const activeFilter = ref('all')
const filters = [
  { value: 'all', label: 'All' },
  { value: 'overdue', label: 'Overdue' },
  { value: 'due_soon', label: 'Due Soon' },
  { value: 'upcoming', label: 'Upcoming' },
]

const filteredBills = computed(() =>
  activeFilter.value === 'all' ? props.bills : props.bills.filter(b => b.status === activeFilter.value)
)

function filterCount(status: string): number {
  return status === 'all' ? props.bills.length : props.bills.filter(b => b.status === status).length
}

function statusTotal(status: string): number {
  return props.bills.filter(b => b.status === status).reduce((sum, b) => sum + b.amount, 0)
}

function formatDate(date: string): string {
  return dayjs(date).format('MMM D, YYYY')
}

function billEmoji(bill: Bill): string {
  const map: Record<string, string> = {
    wifi: '📶', bolt: '⚡', 'device-phone-mobile': '📱', tv: '📺',
    'building-office': '🏢', heart: '❤️', bill: '📋',
  }
  return map[bill.icon] || '📋'
}

function statusClass(status: string): string {
  return {
    overdue: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400',
    due_soon: 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400',
    upcoming: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400',
  }[status] || ''
}

function statusLabel(status: string): string {
  return { overdue: 'Overdue', due_soon: 'Due Soon', upcoming: 'Upcoming' }[status] || status
}

function borderClass(status: string): string {
  return {
    overdue: 'border-red-300 dark:border-red-500/40',
    due_soon: 'border-amber-300 dark:border-amber-500/40',
    upcoming: 'border-gray-200 dark:border-white/10',
  }[status] || 'border-gray-200 dark:border-white/10'
}

// Modals
const showCreateModal = ref(false)
const showMarkPaidModal = ref(false)
const selectedBill = ref<Bill | null>(null)

const billForm = ref({
  name: '', amount: 0, frequency: 'monthly', due_day: null as number | null,
  next_due_date: '', category: '', payee: '', color: '#7C3AED', icon: 'bill', auto_pay: false,
})

const markPaidForm = ref({
  amount: 0, payment_date: new Date().toISOString().split('T')[0], reference_number: '',
})

function createBill() {
  router.post('/bills', billForm.value, { onSuccess: () => { showCreateModal.value = false } })
}

function openMarkPaidModal(bill: Bill) {
  selectedBill.value = bill
  markPaidForm.value = { amount: bill.amount, payment_date: new Date().toISOString().split('T')[0], reference_number: '' }
  showMarkPaidModal.value = true
}

function submitMarkPaid() {
  if (!selectedBill.value) return
  router.post(`/bills/${selectedBill.value.id}/mark-paid`, markPaidForm.value, {
    onSuccess: () => { showMarkPaidModal.value = false },
  })
}

function deleteBill(bill: Bill) {
  if (!confirm(`Delete "${bill.name}"?`)) return
  router.delete(`/bills/${bill.id}`)
}
</script>
