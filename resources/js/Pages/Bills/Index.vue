<template>
  <AppLayout title="Bills">
    <!-- Header -->
    <div class="flex items-center justify-between mb-5">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Bills</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Recurring bills & subscriptions</p>
      </div>
      <button @click="showCreateModal = true"
        class="flex items-center gap-2 gradient-primary text-white rounded-xl font-medium hover:opacity-90 transition-all shadow-lg px-3 py-2.5 sm:px-4">
        <PlusIcon class="w-4 h-4 shrink-0" />
        <span class="hidden sm:inline text-sm">Add Bill</span>
      </button>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-3 gap-3 mb-5">
      <div class="bg-red-50 dark:bg-red-500/10 rounded-2xl border border-red-200 dark:border-red-500/30 p-3 sm:p-4">
        <p class="text-xs text-red-600 dark:text-red-400 font-medium mb-0.5">Overdue</p>
        <p class="text-sm sm:text-xl font-bold text-red-700 dark:text-red-300 truncate">{{ formatPHP(statusTotal('overdue')) }}</p>
        <p class="text-xs text-red-500 mt-0.5">{{ filterCount('overdue') }} bill{{ filterCount('overdue') !== 1 ? 's' : '' }}</p>
      </div>
      <div class="bg-amber-50 dark:bg-amber-500/10 rounded-2xl border border-amber-200 dark:border-amber-500/30 p-3 sm:p-4">
        <p class="text-xs text-amber-600 dark:text-amber-400 font-medium mb-0.5">Due Soon</p>
        <p class="text-sm sm:text-xl font-bold text-amber-700 dark:text-amber-300 truncate">{{ formatPHP(statusTotal('due_soon')) }}</p>
        <p class="text-xs text-amber-500 mt-0.5">{{ filterCount('due_soon') }} bill{{ filterCount('due_soon') !== 1 ? 's' : '' }}</p>
      </div>
      <div class="bg-emerald-50 dark:bg-emerald-500/10 rounded-2xl border border-emerald-200 dark:border-emerald-500/30 p-3 sm:p-4">
        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mb-0.5">Upcoming</p>
        <p class="text-sm sm:text-xl font-bold text-emerald-700 dark:text-emerald-300 truncate">{{ formatPHP(statusTotal('upcoming')) }}</p>
        <p class="text-xs text-emerald-500 mt-0.5">{{ filterCount('upcoming') }} bill{{ filterCount('upcoming') !== 1 ? 's' : '' }}</p>
      </div>
    </div>

    <!-- Filter chips - horizontal scroll on mobile -->
    <div class="flex gap-2 mb-5 overflow-x-auto no-scrollbar pb-1 -mx-4 px-4 sm:mx-0 sm:px-0">
      <button v-for="f in filters" :key="f.value" @click="activeFilter = f.value"
        class="px-3.5 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap shrink-0"
        :class="activeFilter === f.value
          ? 'gradient-primary text-white shadow-lg'
          : 'bg-white dark:bg-[#1A1A2E] text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-white/10'">
        {{ f.label }}
        <span class="ml-1 text-xs opacity-60">({{ filterCount(f.value) }})</span>
      </button>
    </div>

    <!-- ── MOBILE list view ── -->
    <div class="sm:hidden">
      <div v-if="filteredBills.length === 0" class="py-14 text-center text-sm text-gray-400">
        No bills here
      </div>
      <div v-else class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden divide-y divide-gray-100 dark:divide-white/10">
        <div
          v-for="bill in filteredBills"
          :key="bill.id"
          class="flex items-center gap-3 px-4 py-3.5"
          :class="bill.status === 'overdue' ? 'bg-red-50/50 dark:bg-red-500/5' : ''">
          <!-- Emoji icon -->
          <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0"
            :style="{ backgroundColor: bill.color + '20' }">
            {{ billEmoji(bill) }}
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-1.5 mb-0.5">
              <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ bill.name }}</p>
              <span v-if="bill.auto_pay"
                class="shrink-0 text-xs px-1.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-medium">
                Auto
              </span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="text-xs font-medium" :class="statusTextClass(bill.status)">{{ statusLabel(bill.status) }}</span>
              <span class="text-gray-300 dark:text-white/20">·</span>
              <span class="text-xs text-gray-400">{{ formatDate(bill.next_due_date) }}</span>
            </div>
          </div>

          <!-- Amount + actions -->
          <div class="flex items-center gap-2 shrink-0">
            <div class="text-right">
              <p class="text-sm font-bold" :style="{ color: bill.color }">{{ formatPHP(bill.amount) }}</p>
              <p class="text-xs text-gray-400 capitalize">{{ bill.frequency }}</p>
            </div>
            <button
              @click="openMarkPaidModal(bill)"
              class="w-9 h-9 flex items-center justify-center rounded-xl text-white shrink-0"
              :class="bill.status === 'overdue' ? 'bg-red-500' : 'gradient-primary'">
              <CheckIcon class="w-4 h-4" />
            </button>
            <button
              @click="deleteBill(bill)"
              class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors shrink-0">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── DESKTOP card grid ── -->
    <div class="hidden sm:grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-if="filteredBills.length === 0" class="col-span-3 py-16 text-center text-sm text-gray-400">
        No bills here
      </div>
      <div
        v-for="bill in filteredBills"
        :key="bill.id"
        class="bg-white dark:bg-[#1A1A2E] rounded-2xl border overflow-hidden card-hover"
        :class="borderClass(bill.status)">
        <div class="p-5">
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl" :style="{ backgroundColor: bill.color + '20' }">
                {{ billEmoji(bill) }}
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
              :class="bill.status === 'overdue' ? 'bg-red-500 text-white hover:bg-red-600' : 'gradient-primary text-white hover:opacity-90'">
              Mark as Paid
            </button>
            <button
              @click="deleteBill(bill)"
              class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300 hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-500 transition-colors">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Modals ── -->
    <Teleport to="body">

      <!-- Full-screen Add Bill panel -->
      <Transition name="fullscreen-slide">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-gray-50 dark:bg-[#0F0F1A] flex flex-col">

          <!-- Sticky header -->
          <div class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-[#1A1A2E] border-b border-gray-200 dark:border-white/10 shrink-0">
            <button type="button" @click="showCreateModal = false"
              class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-white">
              <ArrowLeftIcon class="w-5 h-5" />
            </button>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">New Bill</h2>
          </div>

          <!-- Scrollable form body -->
          <div class="flex-1 overflow-y-auto">
            <form @submit.prevent="createBill" id="bill-form" class="p-4 space-y-3 pb-6">

              <!-- Name, Payee, Category -->
              <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 divide-y divide-gray-100 dark:divide-white/10">
                <div class="px-4 py-3.5">
                  <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1.5">Bill Name</label>
                  <input v-model="billForm.name" type="text" required
                    class="w-full bg-transparent text-gray-900 dark:text-white outline-none text-base font-semibold placeholder-gray-300 dark:placeholder-white/20"
                    placeholder="e.g. Electric Bill" />
                </div>
                <div class="px-4 py-3.5">
                  <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1.5">Payee</label>
                  <input v-model="billForm.payee" type="text"
                    class="w-full bg-transparent text-gray-900 dark:text-white outline-none text-sm placeholder-gray-300 dark:placeholder-white/20"
                    placeholder="e.g. Meralco" />
                </div>
                <div class="px-4 py-3.5">
                  <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1.5">Category</label>
                  <input v-model="billForm.category" type="text"
                    class="w-full bg-transparent text-gray-900 dark:text-white outline-none text-sm placeholder-gray-300 dark:placeholder-white/20"
                    placeholder="e.g. Utilities" />
                </div>
              </div>

              <!-- Amount -->
              <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 px-4 py-3.5">
                <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1.5">Amount</label>
                <div class="flex items-baseline gap-1.5">
                  <span class="text-xl font-bold text-gray-400">₱</span>
                  <input v-model.number="billForm.amount" type="number" min="0" step="0.01" required
                    class="flex-1 bg-transparent text-gray-900 dark:text-white outline-none text-2xl font-bold placeholder-gray-300 dark:placeholder-white/20"
                    placeholder="0.00" />
                </div>
              </div>

              <!-- Frequency & Due Date -->
              <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 divide-y divide-gray-100 dark:divide-white/10">
                <div class="px-4 py-3.5 flex items-center justify-between gap-4">
                  <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide shrink-0">Frequency</label>
                  <select v-model="billForm.frequency"
                    class="bg-transparent text-gray-900 dark:text-white outline-none text-sm font-medium text-right flex-1">
                    <option value="weekly">Weekly</option>
                    <option value="biweekly">Biweekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="annually">Annually</option>
                    <option value="one-time">One-time</option>
                  </select>
                </div>
                <div class="px-4 py-3.5 flex items-center justify-between gap-4">
                  <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide shrink-0">Next Due Date</label>
                  <input v-model="billForm.next_due_date" type="date" required
                    class="bg-transparent text-gray-900 dark:text-white outline-none text-sm font-medium text-right flex-1" />
                </div>
              </div>

              <!-- Color & Auto Pay -->
              <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 divide-y divide-gray-100 dark:divide-white/10">
                <div class="px-4 py-3.5 flex items-center justify-between gap-4">
                  <div class="min-w-0">
                    <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Accent Color</label>
                    <div class="flex items-center gap-2">
                      <div class="w-4 h-4 rounded-full shrink-0" :style="{ backgroundColor: billForm.color }" />
                      <span class="text-sm text-gray-900 dark:text-white font-mono truncate">{{ billForm.color }}</span>
                    </div>
                  </div>
                  <input v-model="billForm.color" type="color"
                    class="w-10 h-10 rounded-xl border-0 cursor-pointer bg-transparent shrink-0" />
                </div>
                <div class="px-4 py-3.5 flex items-center justify-between gap-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Auto Pay</p>
                    <p class="text-xs text-gray-400 mt-0.5">Bill is paid automatically</p>
                  </div>
                  <!-- Toggle switch -->
                  <button type="button" @click="billForm.auto_pay = !billForm.auto_pay"
                    class="relative w-12 h-6 rounded-full transition-colors duration-200 shrink-0"
                    :class="billForm.auto_pay ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-white/20'">
                    <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                      :class="billForm.auto_pay ? 'translate-x-6' : 'translate-x-0'" />
                  </button>
                </div>
              </div>

            </form>
          </div>

          <!-- Sticky footer -->
          <div class="shrink-0 px-4 py-4 bg-white dark:bg-[#1A1A2E] border-t border-gray-200 dark:border-white/10">
            <button type="submit" form="bill-form"
              class="w-full py-4 gradient-primary text-white rounded-2xl text-base font-semibold hover:opacity-90 transition-all shadow-lg">
              Add Bill
            </button>
          </div>

        </div>
      </Transition>

      <!-- Mark Paid bottom sheet -->
      <Transition name="fade">
        <div v-if="showMarkPaidModal && selectedBill"
          class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 modal-backdrop"
          @click.self="showMarkPaidModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md border border-gray-200 dark:border-white/10">

            <!-- Drag handle -->
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
              <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-white/20" />
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-white/10">
              <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Mark as Paid</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ selectedBill.name }}</p>
              </div>
              <button @click="showMarkPaidModal = false"
                class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <div class="p-5 space-y-4">
              <!-- Amount paid - large input -->
              <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-emerald-50 dark:bg-emerald-500/10 px-4 py-3.5">
                <label class="block text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wide mb-1.5">Amount Paid</label>
                <div class="flex items-baseline gap-1.5">
                  <span class="text-xl font-bold text-emerald-400">₱</span>
                  <input v-model.number="markPaidForm.amount" type="number" min="0.01" step="0.01" required
                    class="flex-1 bg-transparent text-gray-900 dark:text-white outline-none text-2xl font-bold placeholder-gray-400"
                    placeholder="0.00" />
                </div>
              </div>

              <!-- Payment Date & Reference -->
              <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-[#1A1A2E] divide-y divide-gray-100 dark:divide-white/10">
                <div class="px-4 py-3.5 flex items-center justify-between gap-4">
                  <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide shrink-0">Payment Date</label>
                  <input v-model="markPaidForm.payment_date" type="date" required
                    class="bg-transparent text-gray-900 dark:text-white outline-none text-sm font-medium text-right flex-1" />
                </div>
                <div class="px-4 py-3.5">
                  <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1.5">Reference Number</label>
                  <input v-model="markPaidForm.reference_number" type="text"
                    class="w-full bg-transparent text-gray-900 dark:text-white outline-none text-sm placeholder-gray-300 dark:placeholder-white/20"
                    placeholder="Optional" />
                </div>
              </div>

              <button @click="submitMarkPaid"
                class="w-full py-4 gradient-success text-white rounded-2xl text-base font-semibold hover:opacity-90 transition-all shadow-lg">
                Confirm Payment
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
  PlusIcon, XMarkIcon, CheckCircleIcon, TrashIcon,
  CheckIcon, ArrowLeftIcon,
} from '@heroicons/vue/24/outline'
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

function statusTextClass(status: string): string {
  return {
    overdue: 'text-red-600 dark:text-red-400',
    due_soon: 'text-amber-600 dark:text-amber-400',
    upcoming: 'text-emerald-600 dark:text-emerald-400',
  }[status] || 'text-gray-500'
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
  router.post('/bills', billForm.value, {
    onSuccess: () => {
      showCreateModal.value = false
      billForm.value = {
        name: '', amount: 0, frequency: 'monthly', due_day: null,
        next_due_date: '', category: '', payee: '', color: '#7C3AED', icon: 'bill', auto_pay: false,
      }
    },
  })
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

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.fullscreen-slide-enter-active {
  transition: transform 0.32s cubic-bezier(0.32, 0.72, 0, 1);
}
.fullscreen-slide-leave-active {
  transition: transform 0.22s cubic-bezier(0.4, 0, 1, 1);
}
.fullscreen-slide-enter-from,
.fullscreen-slide-leave-to {
  transform: translateY(100%);
}
</style>
