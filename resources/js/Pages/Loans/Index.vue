<template>
  <AppLayout title="Loans">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Loans</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track your loans and payment schedules</p>
      </div>
      <button @click="showCreateModal = true" class="flex items-center gap-2 px-4 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90 transition-all shadow-lg">
        <PlusIcon class="w-4 h-4" />
        Add Loan
      </button>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-5">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Debt</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ formatPHP(totalRemaining) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-5">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Paid</p>
        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatPHP(totalPaid) }}</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-5">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Monthly Obligations</p>
        <p class="text-2xl font-bold gradient-text">{{ formatPHP(monthlyTotal) }}</p>
      </div>
    </div>

    <!-- Loan Cards -->
    <div class="space-y-4">
      <div v-for="loan in loans" :key="loan.id" class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden">
        <div class="p-6">
          <div class="flex items-start justify-between mb-4">
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ loan.name }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ loan.lender }}</p>
            </div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium"
                :class="loan.status === 'active' ? 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400' : loan.status === 'paid' ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400'">
                {{ loan.status.charAt(0).toUpperCase() + loan.status.slice(1) }}
              </span>
              <button @click="openPaymentModal(loan)" class="px-3 py-1.5 bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-400 rounded-lg text-xs font-medium hover:bg-violet-200 dark:hover:bg-violet-500/30 transition-colors">
                Pay Now
              </button>
            </div>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Principal</p>
              <p class="font-semibold text-gray-900 dark:text-white">{{ formatPHP(loan.principal_amount) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Remaining</p>
              <p class="font-semibold text-red-600 dark:text-red-400">{{ formatPHP(loan.remaining_balance) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Interest Rate</p>
              <p class="font-semibold text-gray-900 dark:text-white">{{ loan.interest_rate }}% p.a.</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Monthly Payment</p>
              <p class="font-semibold text-violet-600 dark:text-violet-400">{{ formatPHP(loan.monthly_payment) }}</p>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mb-3">
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
              <span>{{ paidPercent(loan).toFixed(1) }}% paid off</span>
              <span>Next payment: {{ loan.next_payment_date ? formatDate(loan.next_payment_date) : 'N/A' }}</span>
            </div>
            <div class="h-3 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
              <div class="h-full gradient-primary rounded-full transition-all duration-700" :style="{ width: paidPercent(loan) + '%' }" />
            </div>
          </div>

          <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>Start: {{ formatDate(loan.start_date) }}</span>
            <span>End: {{ formatDate(loan.end_date) }}</span>
          </div>
        </div>

        <!-- Amortization Schedule (expandable) -->
        <div v-if="expandedLoan === loan.id" class="border-t border-gray-100 dark:border-white/10 p-6">
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Payment History</h4>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-100 dark:border-white/10">
                  <th class="text-left pb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Date</th>
                  <th class="text-right pb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Amount</th>
                  <th class="text-right pb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Principal</th>
                  <th class="text-right pb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Interest</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                <tr v-for="payment in loan.payments" :key="payment.id">
                  <td class="py-2 text-gray-600 dark:text-gray-300">{{ formatDate(payment.payment_date) }}</td>
                  <td class="py-2 text-right font-medium text-gray-900 dark:text-white">{{ formatPHP(payment.amount) }}</td>
                  <td class="py-2 text-right text-emerald-600 dark:text-emerald-400">{{ formatPHP(payment.principal_portion) }}</td>
                  <td class="py-2 text-right text-red-600 dark:text-red-400">{{ formatPHP(payment.interest_portion) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="px-6 pb-4 flex items-center gap-3">
          <button @click="expandedLoan = expandedLoan === loan.id ? null : loan.id" class="text-xs text-violet-600 dark:text-violet-400 hover:underline">
            {{ expandedLoan === loan.id ? 'Hide' : 'Show' }} payment history
          </button>
          <button @click="deleteLoan(loan)" class="text-xs text-red-500 hover:underline ml-auto">Delete</button>
        </div>
      </div>
    </div>

    <!-- Create Loan Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop" @click.self="showCreateModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl shadow-2xl w-full max-w-xl border border-gray-200 dark:border-white/10 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10 sticky top-0 bg-white dark:bg-[#1A1A2E]">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Loan</h3>
              <button @click="showCreateModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <form @submit.prevent="createLoan" class="p-6 space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Loan Name</label>
                  <input v-model="loanForm.name" type="text" class="input-field" placeholder="Car Loan" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Lender</label>
                  <input v-model="loanForm.lender" type="text" class="input-field" placeholder="BDO" required />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Principal (₱)</label>
                  <input v-model.number="loanForm.principal_amount" type="number" min="1" class="input-field" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Remaining Balance (₱)</label>
                  <input v-model.number="loanForm.remaining_balance" type="number" min="0" class="input-field" required />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Interest Rate (%)</label>
                  <input v-model.number="loanForm.interest_rate" type="number" min="0" max="100" step="0.01" class="input-field" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Monthly Payment (₱)</label>
                  <input v-model.number="loanForm.monthly_payment" type="number" min="0" class="input-field" required />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Term (months)</label>
                  <input v-model.number="loanForm.term_months" type="number" min="1" class="input-field" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Interest Type</label>
                  <select v-model="loanForm.interest_type" class="input-field">
                    <option value="simple">Simple</option>
                    <option value="compound">Compound</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Start Date</label>
                  <input v-model="loanForm.start_date" type="date" class="input-field" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">End Date</label>
                  <input v-model="loanForm.end_date" type="date" class="input-field" required />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Next Payment Date</label>
                <input v-model="loanForm.next_payment_date" type="date" class="input-field" />
              </div>
              <div class="flex gap-3 pt-2">
                <button type="button" @click="showCreateModal = false" class="flex-1 py-2.5 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90">Add Loan</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>

      <!-- Payment Modal -->
      <Transition name="fade">
        <div v-if="showPaymentModal && selectedLoan" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop" @click.self="showPaymentModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl shadow-2xl w-full max-w-md border border-gray-200 dark:border-white/10">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Record Payment</h3>
              <button @click="showPaymentModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <form @submit.prevent="submitPayment" class="p-6 space-y-4">
              <p class="text-sm text-gray-500 dark:text-gray-400">For: <strong class="text-gray-900 dark:text-white">{{ selectedLoan.name }}</strong></p>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Total Payment (₱)</label>
                <input v-model.number="paymentForm.amount" type="number" min="0.01" step="0.01" class="input-field" :placeholder="selectedLoan.monthly_payment.toString()" required />
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Principal (₱)</label>
                  <input v-model.number="paymentForm.principal_portion" type="number" min="0" step="0.01" class="input-field" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Interest (₱)</label>
                  <input v-model.number="paymentForm.interest_portion" type="number" min="0" step="0.01" class="input-field" required />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Payment Date</label>
                <input v-model="paymentForm.payment_date" type="date" class="input-field" required />
              </div>
              <div class="flex gap-3">
                <button type="button" @click="showPaymentModal = false" class="flex-1 py-2.5 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90">Record Payment</button>
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
import { PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { Loan } from '@/types'
import dayjs from 'dayjs'

const props = defineProps<{ loans: Loan[] }>()
const { formatPHP } = useCurrency()

const expandedLoan = ref<number | null>(null)
const showCreateModal = ref(false)
const showPaymentModal = ref(false)
const selectedLoan = ref<Loan | null>(null)

const totalRemaining = computed(() => props.loans.filter(l => l.status === 'active').reduce((sum, l) => sum + l.remaining_balance, 0))
const totalPaid = computed(() => props.loans.reduce((sum, l) => sum + (l.paid_amount || 0), 0))
const monthlyTotal = computed(() => props.loans.filter(l => l.status === 'active').reduce((sum, l) => sum + l.monthly_payment, 0))

function paidPercent(loan: Loan): number {
  const paid = loan.principal_amount - loan.remaining_balance
  return Math.min(100, (paid / loan.principal_amount) * 100)
}

function formatDate(date: string): string {
  return dayjs(date).format('MMM D, YYYY')
}

const loanForm = ref({
  name: '', lender: '', principal_amount: 0, remaining_balance: 0,
  interest_rate: 0, interest_type: 'compound', payment_frequency: 'monthly',
  monthly_payment: 0, term_months: 12, start_date: '', end_date: '',
  next_payment_date: '',
})

const paymentForm = ref({
  amount: 0, principal_portion: 0, interest_portion: 0,
  payment_date: new Date().toISOString().split('T')[0],
})

function createLoan() {
  router.post('/loans', loanForm.value, { onSuccess: () => { showCreateModal.value = false } })
}

function openPaymentModal(loan: Loan) {
  selectedLoan.value = loan
  paymentForm.value = {
    amount: loan.monthly_payment,
    principal_portion: loan.monthly_payment * 0.8,
    interest_portion: loan.monthly_payment * 0.2,
    payment_date: new Date().toISOString().split('T')[0],
  }
  showPaymentModal.value = true
}

function submitPayment() {
  if (!selectedLoan.value) return
  router.post(`/loans/${selectedLoan.value.id}/payments`, paymentForm.value, {
    onSuccess: () => { showPaymentModal.value = false },
  })
}

function deleteLoan(loan: Loan) {
  if (!confirm(`Delete "${loan.name}"?`)) return
  router.delete(`/loans/${loan.id}`)
}
</script>

