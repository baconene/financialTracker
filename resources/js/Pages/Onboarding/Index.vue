<template>
  <div class="min-h-screen bg-gray-50 dark:bg-[#0D0D1A] flex flex-col">

    <!-- Gradient Header -->
    <div class="gradient-primary px-5 pt-10 pb-6 text-white shrink-0">
      <div class="max-w-lg mx-auto">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
            <component :is="stepIcon" class="w-5 h-5 text-white" />
          </div>
          <div>
            <p class="text-xs text-white/70 uppercase tracking-wide font-medium">Step {{ step }} of 4</p>
            <h1 class="text-lg font-bold leading-tight">{{ stepTitle }}</h1>
          </div>
        </div>
        <!-- Progress -->
        <div class="flex gap-1.5">
          <div
            v-for="i in 4"
            :key="i"
            class="h-1.5 flex-1 rounded-full transition-all duration-300"
            :class="i <= step ? 'bg-white' : 'bg-white/30'"
          />
        </div>
        <p class="text-xs text-white/70 mt-3">{{ stepSubtitle }}</p>
      </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto">
      <div class="max-w-lg mx-auto px-4 py-5 pb-28 space-y-4">

        <!-- ── Step 1: Income Sources ─────────────────────────────── -->
        <template v-if="step === 1">
          <div
            v-for="(src, idx) in incomeSources"
            :key="idx"
            class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4 space-y-3"
          >
            <div class="flex items-center justify-between">
              <p class="text-sm font-semibold text-gray-800 dark:text-white">Income Source {{ idx + 1 }}</p>
              <button v-if="incomeSources.length > 1" @click="removeItem(incomeSources, idx)"
                class="w-7 h-7 flex items-center justify-center rounded-lg text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>

            <div class="flex gap-3 items-end">
              <div>
                <label class="label-xs">Color</label>
                <input v-model="src.color" type="color" class="h-10 w-14 rounded-xl border border-gray-200 dark:border-white/20 cursor-pointer" />
              </div>
              <div class="flex-1">
                <label class="label-xs">Name</label>
                <input v-model="src.name" type="text" placeholder="e.g. Monthly Salary" class="input-field" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label-xs">Amount (₱)</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">₱</span>
                  <input v-model.number="src.amount" type="number" min="0" step="0.01" placeholder="0.00" class="input-field pl-7" />
                </div>
              </div>
              <div>
                <label class="label-xs">Frequency</label>
                <select v-model="src.frequency" class="input-field">
                  <option value="weekly">Weekly</option>
                  <option value="biweekly">Bi-weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="quarterly">Quarterly</option>
                  <option value="annually">Annually</option>
                </select>
              </div>
            </div>

            <div v-if="src.amount" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-xs text-emerald-700 dark:text-emerald-300">
              <span>📅</span>
              <span>Monthly equivalent: <strong>{{ formatPHP(computeMonthly(src.amount, src.frequency)) }}</strong></span>
            </div>
          </div>

          <button @click="addIncomeSource"
            class="w-full py-3 border-2 border-dashed border-gray-200 dark:border-white/20 rounded-2xl text-sm text-gray-500 dark:text-gray-400 hover:border-violet-400 hover:text-violet-600 dark:hover:text-violet-400 transition-all flex items-center justify-center gap-2">
            <PlusIcon class="w-4 h-4" /> Add Another Income Source
          </button>

          <div class="bg-blue-50 dark:bg-blue-500/10 rounded-2xl p-4 text-xs text-blue-700 dark:text-blue-300">
            💡 Income sources power your cash flow projections and can be used as quick-select when logging transactions.
          </div>
        </template>

        <!-- ── Step 2: Accounts ───────────────────────────────────── -->
        <template v-if="step === 2">
          <div
            v-for="(acc, idx) in accounts"
            :key="idx"
            class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4 space-y-3"
          >
            <div class="flex items-center justify-between">
              <p class="text-sm font-semibold text-gray-800 dark:text-white">Account {{ idx + 1 }}</p>
              <button v-if="accounts.length > 1" @click="removeItem(accounts, idx)"
                class="w-7 h-7 flex items-center justify-center rounded-lg text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>

            <div class="flex gap-3 items-end">
              <div>
                <label class="label-xs">Color</label>
                <input v-model="acc.color" type="color" class="h-10 w-14 rounded-xl border border-gray-200 dark:border-white/20 cursor-pointer" />
              </div>
              <div class="flex-1">
                <label class="label-xs">Account Name</label>
                <input v-model="acc.name" type="text" placeholder="e.g. BDO Savings" class="input-field" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label-xs">Type</label>
                <select v-model="acc.type" class="input-field">
                  <option value="bank">Bank</option>
                  <option value="cash">Cash</option>
                  <option value="e-wallet">E-Wallet</option>
                  <option value="credit">Credit Card</option>
                  <option value="investment">Investment</option>
                </select>
              </div>
              <div>
                <label class="label-xs">Bank / Provider</label>
                <input v-model="acc.bank_name" type="text" placeholder="e.g. BDO, GCash" class="input-field" />
              </div>
            </div>

            <div>
              <label class="label-xs">Current Balance (₱)</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">₱</span>
                <input v-model.number="acc.balance" type="number" step="0.01" placeholder="0.00" class="input-field pl-7" />
              </div>
            </div>
          </div>

          <button @click="addAccount"
            class="w-full py-3 border-2 border-dashed border-gray-200 dark:border-white/20 rounded-2xl text-sm text-gray-500 dark:text-gray-400 hover:border-violet-400 hover:text-violet-600 dark:hover:text-violet-400 transition-all flex items-center justify-center gap-2">
            <PlusIcon class="w-4 h-4" /> Add Another Account
          </button>

          <div class="bg-blue-50 dark:bg-blue-500/10 rounded-2xl p-4 text-xs text-blue-700 dark:text-blue-300">
            💡 Add all accounts where you keep money — bank accounts, e-wallets, cash on hand, and credit cards.
          </div>
        </template>

        <!-- ── Step 3: Loans & Bills ──────────────────────────────── -->
        <template v-if="step === 3">

          <!-- Loans Section -->
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide">Outstanding Loans</h2>
          </div>

          <div v-if="loans.length === 0" class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 py-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">No loans yet.</p>
          </div>

          <div
            v-for="(loan, idx) in loans"
            :key="'loan-' + idx"
            class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4 space-y-3"
          >
            <div class="flex items-center justify-between">
              <p class="text-sm font-semibold text-gray-800 dark:text-white">Loan {{ idx + 1 }}</p>
              <button @click="removeItem(loans, idx)"
                class="w-7 h-7 flex items-center justify-center rounded-lg text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label-xs">Loan Name</label>
                <input v-model="loan.name" type="text" placeholder="e.g. Car Loan" class="input-field" />
              </div>
              <div>
                <label class="label-xs">Lender</label>
                <input v-model="loan.lender" type="text" placeholder="e.g. BDO" class="input-field" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label-xs">Remaining Balance (₱)</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₱</span>
                  <input v-model.number="loan.remaining_balance" type="number" min="0" step="0.01" placeholder="0.00" class="input-field pl-7" />
                </div>
              </div>
              <div>
                <label class="label-xs">Monthly Payment (₱)</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₱</span>
                  <input v-model.number="loan.monthly_payment" type="number" min="0" step="0.01" placeholder="0.00" class="input-field pl-7" />
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label-xs">Payment Frequency</label>
                <select v-model="loan.payment_frequency" class="input-field">
                  <option value="weekly">Weekly</option>
                  <option value="biweekly">Bi-weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="quarterly">Quarterly</option>
                </select>
              </div>
              <div>
                <label class="label-xs">Interest Rate (%)</label>
                <input v-model.number="loan.interest_rate" type="number" min="0" step="0.01" placeholder="0.00" class="input-field" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label-xs">Next Payment Date</label>
                <input v-model="loan.next_payment_date" type="date" class="input-field" />
              </div>
              <div>
                <label class="label-xs">End Date</label>
                <input v-model="loan.end_date" type="date" class="input-field" />
              </div>
            </div>
          </div>

          <button @click="addLoan"
            class="w-full py-3 border-2 border-dashed border-gray-200 dark:border-white/20 rounded-2xl text-sm text-gray-500 dark:text-gray-400 hover:border-orange-400 hover:text-orange-600 dark:hover:text-orange-400 transition-all flex items-center justify-center gap-2">
            <PlusIcon class="w-4 h-4" /> Add a Loan
          </button>

          <!-- Bills Section -->
          <div class="pt-2 flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide">Recurring Bills</h2>
          </div>

          <div v-if="bills.length === 0" class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 py-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">No recurring bills yet.</p>
          </div>

          <div
            v-for="(bill, idx) in bills"
            :key="'bill-' + idx"
            class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4 space-y-3"
          >
            <div class="flex items-center justify-between">
              <p class="text-sm font-semibold text-gray-800 dark:text-white">Bill {{ idx + 1 }}</p>
              <button @click="removeItem(bills, idx)"
                class="w-7 h-7 flex items-center justify-center rounded-lg text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>

            <div class="flex gap-3 items-end">
              <div>
                <label class="label-xs">Color</label>
                <input v-model="bill.color" type="color" class="h-10 w-14 rounded-xl border border-gray-200 dark:border-white/20 cursor-pointer" />
              </div>
              <div class="flex-1">
                <label class="label-xs">Bill Name</label>
                <input v-model="bill.name" type="text" placeholder="e.g. Electricity" class="input-field" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label-xs">Amount (₱)</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₱</span>
                  <input v-model.number="bill.amount" type="number" min="0" step="0.01" placeholder="0.00" class="input-field pl-7" />
                </div>
              </div>
              <div>
                <label class="label-xs">Frequency</label>
                <select v-model="bill.frequency" class="input-field">
                  <option value="weekly">Weekly</option>
                  <option value="biweekly">Bi-weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="quarterly">Quarterly</option>
                  <option value="annually">Annually</option>
                  <option value="one-time">One-time</option>
                </select>
              </div>
            </div>

            <div>
              <label class="label-xs">Next Due Date</label>
              <input v-model="bill.next_due_date" type="date" class="input-field" />
            </div>
          </div>

          <button @click="addBill"
            class="w-full py-3 border-2 border-dashed border-gray-200 dark:border-white/20 rounded-2xl text-sm text-gray-500 dark:text-gray-400 hover:border-violet-400 hover:text-violet-600 dark:hover:text-violet-400 transition-all flex items-center justify-center gap-2">
            <PlusIcon class="w-4 h-4" /> Add a Recurring Bill
          </button>
        </template>

        <!-- ── Step 4: Financial Targets ──────────────────────────── -->
        <template v-if="step === 4">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-5 space-y-5">

            <!-- Emergency Fund -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="text-sm font-semibold text-gray-800 dark:text-white">Emergency Fund Target</label>
                <span class="text-sm font-bold text-violet-600 dark:text-violet-400">{{ settings.emergency_fund_months }} months</span>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">How many months of expenses should your emergency fund cover?</p>
              <input v-model.number="settings.emergency_fund_months" type="range" min="1" max="12" step="1"
                class="w-full accent-violet-600" />
              <div class="flex justify-between text-xs text-gray-400 mt-1">
                <span>1 mo</span><span>6 mo (recommended)</span><span>12 mo</span>
              </div>
            </div>

            <div class="border-t border-gray-100 dark:border-white/10" />

            <!-- Min Savings Rate -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="text-sm font-semibold text-gray-800 dark:text-white">Minimum Savings Rate</label>
                <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ settings.min_savings_rate }}%</span>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Alert me when my monthly savings rate drops below this.</p>
              <input v-model.number="settings.min_savings_rate" type="range" min="0" max="50" step="1"
                class="w-full accent-emerald-500" />
              <div class="flex justify-between text-xs text-gray-400 mt-1">
                <span>0%</span><span>10% (recommended)</span><span>50%</span>
              </div>
            </div>

            <div class="border-t border-gray-100 dark:border-white/10" />

            <!-- Max Debt-to-Income -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="text-sm font-semibold text-gray-800 dark:text-white">Max Debt-to-Income Ratio</label>
                <span class="text-sm font-bold text-orange-600 dark:text-orange-400">{{ settings.max_debt_to_income }}%</span>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Alert me when loan payments exceed this % of monthly income.</p>
              <input v-model.number="settings.max_debt_to_income" type="range" min="10" max="60" step="1"
                class="w-full accent-orange-500" />
              <div class="flex justify-between text-xs text-gray-400 mt-1">
                <span>10%</span><span>35% (recommended)</span><span>60%</span>
              </div>
            </div>

            <div class="border-t border-gray-100 dark:border-white/10" />

            <!-- Bills Stress -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="text-sm font-semibold text-gray-800 dark:text-white">Max Bills Stress Score</label>
                <span class="text-sm font-bold text-red-500 dark:text-red-400">{{ settings.max_bills_stress_score }}%</span>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Alert me when bills + loans exceed this % of income.</p>
              <input v-model.number="settings.max_bills_stress_score" type="range" min="10" max="80" step="1"
                class="w-full accent-red-500" />
              <div class="flex justify-between text-xs text-gray-400 mt-1">
                <span>10%</span><span>50% (recommended)</span><span>80%</span>
              </div>
            </div>
          </div>

          <div class="bg-emerald-50 dark:bg-emerald-500/10 rounded-2xl p-4 text-xs text-emerald-700 dark:text-emerald-300">
            ✅ You're almost done! Click <strong>Finish Setup</strong> to go to your dashboard. You can change these targets anytime in Settings.
          </div>
        </template>

      </div>
    </div>

    <!-- Fixed Footer Navigation -->
    <div class="fixed bottom-0 inset-x-0 bg-white dark:bg-[#0D0D1A] border-t border-gray-200 dark:border-white/10 p-4 pb-safe">
      <div class="max-w-lg mx-auto flex gap-3">
        <button
          v-if="step > 1"
          @click="step--"
          class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all"
        >
          ← Back
        </button>
        <button
          v-if="step === 1"
          @click="skipAndFinish"
          class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-500 dark:text-gray-400 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all"
        >
          Skip Setup
        </button>
        <button
          v-if="step < 4"
          @click="step++"
          class="flex-2 flex-1 py-3 gradient-primary text-white rounded-xl text-sm font-semibold hover:opacity-90 transition-all"
        >
          Next →
        </button>
        <button
          v-if="step === 4"
          @click="finish"
          :disabled="submitting"
          class="flex-1 py-3 gradient-primary text-white rounded-xl text-sm font-semibold hover:opacity-90 disabled:opacity-50 transition-all flex items-center justify-center gap-2"
        >
          <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          {{ submitting ? 'Setting up…' : 'Finish Setup ✓' }}
        </button>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  PlusIcon, XMarkIcon,
  BriefcaseIcon, BuildingLibraryIcon, CreditCardIcon, ChartBarIcon
} from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { Category } from '@/types'

defineProps<{ categories: Category[]; user: { name: string } }>()

const { formatPHP } = useCurrency()

// ── Step state ───────────────────────────────────────────────────
const step = ref(1)
const submitting = ref(false)

const stepConfig = [
  { title: 'Income Profile',     subtitle: 'How do you earn your money?',           icon: BriefcaseIcon },
  { title: 'Your Accounts',      subtitle: 'Where do you keep your money?',         icon: BuildingLibraryIcon },
  { title: 'Loans & Bills',      subtitle: 'What are your regular obligations?',    icon: CreditCardIcon },
  { title: 'Financial Targets',  subtitle: 'What are your financial goals?',        icon: ChartBarIcon },
]

const stepTitle    = computed(() => stepConfig[step.value - 1].title)
const stepSubtitle = computed(() => stepConfig[step.value - 1].subtitle)
const stepIcon     = computed(() => stepConfig[step.value - 1].icon)

// ── Income Sources ───────────────────────────────────────────────
interface IncomeSourceForm {
  name: string; amount: number | null; frequency: string; color: string
}
const incomeSources = ref<IncomeSourceForm[]>([
  { name: '', amount: null, frequency: 'monthly', color: '#10B981' }
])
function addIncomeSource() {
  incomeSources.value.push({ name: '', amount: null, frequency: 'monthly', color: '#7C3AED' })
}

// ── Accounts ─────────────────────────────────────────────────────
interface AccountForm {
  name: string; type: string; bank_name: string; balance: number; color: string
}
const accounts = ref<AccountForm[]>([
  { name: '', type: 'bank', bank_name: '', balance: 0, color: '#7C3AED' }
])
function addAccount() {
  accounts.value.push({ name: '', type: 'bank', bank_name: '', balance: 0, color: '#0EA5E9' })
}

// ── Loans ────────────────────────────────────────────────────────
interface LoanForm {
  name: string; lender: string; remaining_balance: number | null; monthly_payment: number | null
  payment_frequency: string; interest_rate: number | null; next_payment_date: string; end_date: string
}
const loans = ref<LoanForm[]>([])
function addLoan() {
  loans.value.push({
    name: '', lender: '', remaining_balance: null, monthly_payment: null,
    payment_frequency: 'monthly', interest_rate: null,
    next_payment_date: '', end_date: ''
  })
}

// ── Bills ────────────────────────────────────────────────────────
interface BillForm {
  name: string; amount: number | null; frequency: string; next_due_date: string; color: string
}
const bills = ref<BillForm[]>([])
function addBill() {
  bills.value.push({ name: '', amount: null, frequency: 'monthly', next_due_date: '', color: '#7C3AED' })
}

// ── Settings ─────────────────────────────────────────────────────
const settings = ref({
  emergency_fund_months: 6,
  min_savings_rate: 10,
  max_debt_to_income: 35,
  spending_warning_threshold: 80,
  max_bills_stress_score: 50,
})

// ── Helpers ──────────────────────────────────────────────────────
function removeItem<T>(arr: T[], idx: number) {
  arr.splice(idx, 1)
}

function computeMonthly(amount: number | null, frequency: string): number {
  const amt = amount ?? 0
  return Math.round(({
    weekly: amt * 4.33,
    biweekly: amt * 2.17,
    monthly: amt,
    quarterly: amt / 3,
    annually: amt / 12,
  }[frequency] ?? amt) * 100) / 100
}

// ── Submit ───────────────────────────────────────────────────────
function buildPayload() {
  return {
    income_sources: incomeSources.value.filter(s => s.name && s.amount),
    accounts:       accounts.value.filter(a => a.name),
    loans:          loans.value.filter(l => l.name),
    bills:          bills.value.filter(b => b.name),
    settings:       settings.value,
  }
}

function finish() {
  submitting.value = true
  router.post('/onboarding/complete', buildPayload(), {
    onFinish: () => { submitting.value = false },
  })
}

function skipAndFinish() {
  submitting.value = true
  router.post('/onboarding/complete', { income_sources: [], accounts: [], loans: [], bills: [], settings: settings.value }, {
    onFinish: () => { submitting.value = false },
  })
}
</script>

<style scoped>
.label-xs {
  display: block;
  font-size: 0.75rem;
  font-weight: 500;
  color: rgb(107 114 128);
  margin-bottom: 0.375rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.dark .label-xs {
  color: rgb(156 163 175);
}
.pb-safe {
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
}
</style>
