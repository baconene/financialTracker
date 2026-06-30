<template>
  <AppLayout title="Transactions">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Transactions</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ transactions.total }} transactions found</p>
      </div>
      <div class="flex items-center gap-2">
        <button @click="openScanModal" class="flex items-center gap-2 bg-white dark:bg-[#1A1A2E] border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all px-3 py-2.5">
          <CameraIcon class="w-4 h-4 shrink-0" />
          <span class="hidden sm:inline text-sm">Scan Receipt</span>
        </button>
        <button @click="showCreateModal = true" class="flex items-center gap-2 gradient-primary text-white rounded-xl font-medium hover:opacity-90 transition-all shadow-lg px-3 py-2.5 sm:px-4">
          <PlusIcon class="w-4 h-4 shrink-0" />
          <span class="hidden sm:inline text-sm">Add Transaction</span>
        </button>
      </div>
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

    <!-- Transaction List -->
    <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden">

      <!-- Mobile card rows -->
      <div class="sm:hidden divide-y divide-gray-100 dark:divide-white/10">
        <div
          v-for="txn in transactions.data"
          :key="txn.id"
          class="flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group"
        >
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
            :class="txn.type === 'income' ? 'bg-emerald-100 dark:bg-emerald-500/20' : 'bg-red-100 dark:bg-red-500/20'"
          >
            <ArrowTrendingUpIcon v-if="txn.type === 'income'" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
            <ArrowTrendingDownIcon v-else class="w-5 h-5 text-red-600 dark:text-red-400" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ txn.description }}</p>
            <div class="flex items-center gap-2 mt-0.5">
              <span
                v-if="txn.category"
                class="text-xs px-1.5 py-0.5 rounded-full font-medium"
                :style="{ backgroundColor: txn.category.color + '20', color: txn.category.color }"
              >{{ txn.category.name }}</span>
              <span class="text-xs text-gray-400">{{ formatDate(txn.transaction_date) }}</span>
            </div>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <span
              class="text-sm font-bold"
              :class="txn.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
            >
              {{ txn.type === 'income' ? '+' : '-' }}{{ formatPHP(txn.amount) }}
            </span>
            <button
              @click="deleteTransaction(txn)"
              class="text-gray-300 dark:text-white/20 hover:text-red-500 dark:hover:text-red-400 transition-colors"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
        <div v-if="transactions.data.length === 0" class="px-4 py-12 text-center">
          <p class="text-gray-500 dark:text-gray-400">No transactions found</p>
        </div>
      </div>

      <!-- Desktop table -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-white/5">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
              <th class="px-4 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Category</th>
              <th class="px-4 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">Account</th>
              <th class="px-4 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
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
              <td class="px-4 py-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(txn.transaction_date) }}</span>
              </td>
              <td class="px-6 py-4 text-right">
                <span class="text-sm font-bold" :class="txn.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                  {{ txn.type === 'income' ? '+' : '-' }}{{ formatPHP(txn.amount) }}
                </span>
              </td>
              <td class="px-4 py-4 text-center">
                <button @click="deleteTransaction(txn)" class="text-gray-400 hover:text-red-500">
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

    <!-- Receipt Scanner Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showScanModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 modal-backdrop" @click.self="closeScanModal">
          <div class="bg-[#0F0F23] rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-lg border border-white/10 flex flex-col" style="max-height: 90vh">
            <!-- Drag handle (mobile) -->
            <div class="flex justify-center pt-3 pb-1 sm:hidden shrink-0">
              <div class="w-10 h-1 rounded-full bg-white/20" />
            </div>
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-white/10 shrink-0">
              <div>
                <h3 class="text-base font-semibold text-white">Scan Receipt</h3>
                <p class="text-xs text-white/50 mt-0.5">AI will extract transaction details</p>
              </div>
              <button @click="closeScanModal" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 text-gray-400 transition-colors">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <!-- Camera / Preview area -->
            <div class="relative flex-1 min-h-0 bg-black overflow-hidden">
              <!-- Camera view -->
              <template v-if="!capturedImage">
                <div v-if="cameraError" class="flex flex-col items-center justify-center h-full gap-3 p-8 min-h-[240px]">
                  <CameraIcon class="w-12 h-12 text-white/20" />
                  <p class="text-white/50 text-sm text-center leading-relaxed">{{ cameraError }}</p>
                </div>
                <video v-else ref="videoRef" autoplay playsinline muted class="w-full h-full object-cover min-h-[240px]" />
                <!-- Viewfinder overlay -->
                <div v-if="!cameraError" class="absolute inset-0 pointer-events-none flex items-center justify-center">
                  <div class="relative border border-white/30 rounded-xl w-4/5" style="aspect-ratio: 1.618">
                    <span class="absolute top-0 left-0 w-5 h-5 border-t-2 border-l-2 border-white rounded-tl-lg -translate-x-px -translate-y-px" />
                    <span class="absolute top-0 right-0 w-5 h-5 border-t-2 border-r-2 border-white rounded-tr-lg translate-x-px -translate-y-px" />
                    <span class="absolute bottom-0 left-0 w-5 h-5 border-b-2 border-l-2 border-white rounded-bl-lg -translate-x-px translate-y-px" />
                    <span class="absolute bottom-0 right-0 w-5 h-5 border-b-2 border-r-2 border-white rounded-br-lg translate-x-px translate-y-px" />
                    <p class="absolute -bottom-6 left-0 right-0 text-center text-white/40 text-xs">Align receipt within frame</p>
                  </div>
                </div>
              </template>

              <!-- Captured image preview -->
              <template v-else>
                <img :src="capturedImage" class="w-full h-full object-contain min-h-[240px]" />
                <!-- Analyzing overlay -->
                <div v-if="isAnalyzing" class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center gap-3">
                  <div class="w-10 h-10 border-2 border-white/20 border-t-white rounded-full animate-spin" />
                  <p class="text-white text-sm font-medium">Analyzing receipt…</p>
                  <p class="text-white/50 text-xs">This takes a few seconds</p>
                </div>
              </template>
            </div>

            <!-- Controls -->
            <div class="px-5 py-4 border-t border-white/10 shrink-0">
              <!-- Error -->
              <div v-if="scanError" class="mb-3 px-3 py-2.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm leading-snug">
                {{ scanError }}
              </div>

              <div v-if="!isAnalyzing" class="flex items-center gap-2.5">
                <!-- Retake button (after failed scan) -->
                <button v-if="capturedImage" @click="retake"
                  class="flex-1 py-2.5 rounded-xl border border-white/20 text-white/70 text-sm font-medium hover:bg-white/10 transition-colors">
                  Retake
                </button>
                <!-- Capture button (camera active) -->
                <button v-else-if="!cameraError" @click="capturePhoto"
                  class="flex-1 py-2.5 rounded-xl bg-white text-gray-900 text-sm font-semibold hover:bg-gray-100 transition-colors flex items-center justify-center gap-2">
                  <CameraIcon class="w-4 h-4" />
                  Capture
                </button>
                <!-- Upload button -->
                <label class="flex-1 py-2.5 rounded-xl border border-white/20 text-white/70 text-sm font-medium hover:bg-white/10 transition-colors flex items-center justify-center gap-2 cursor-pointer">
                  <ArrowUpTrayIcon class="w-4 h-4" />
                  Upload Photo
                  <input type="file" accept="image/*" class="sr-only" @change="onFileUpload" />
                </label>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Create Transaction Modal (bottom-sheet on mobile) -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 modal-backdrop" @click.self="showCreateModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-lg border border-gray-200 dark:border-white/10 max-h-[92vh] overflow-y-auto">
            <!-- Drag handle -->
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
              <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-white/20" />
            </div>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10 sticky top-0 bg-white dark:bg-[#1A1A2E] z-10">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">New Transaction</h3>
              <button @click="showCreateModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <form @submit.prevent="createTransaction" class="p-6 space-y-4">
              <!-- Type Selector -->
              <div class="flex gap-2 p-1 bg-gray-100 dark:bg-white/10 rounded-xl">
                <button v-for="t in ['income', 'expense']" :key="t" type="button"
                  @click="txnForm.type = t as 'income' | 'expense'"
                  class="flex-1 py-2 rounded-lg text-sm font-semibold transition-all capitalize"
                  :class="txnForm.type === t
                    ? (t === 'income' ? 'bg-emerald-500 text-white shadow' : 'bg-red-500 text-white shadow')
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                >
                  {{ t }}
                </button>
              </div>

              <!-- Large amount input -->
              <div class="rounded-2xl p-4 text-center transition-colors" :class="txnForm.type === 'income' ? 'bg-emerald-50 dark:bg-emerald-500/10' : 'bg-red-50 dark:bg-red-500/10'">
                <p class="text-xs font-medium uppercase tracking-wide mb-2" :class="txnForm.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">Amount</p>
                <div class="flex items-center justify-center gap-1">
                  <span class="text-2xl font-bold" :class="txnForm.type === 'income' ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'">₱</span>
                  <input
                    v-model.number="txnForm.amount"
                    type="number" min="0.01" step="0.01"
                    class="text-3xl font-bold bg-transparent border-none outline-none w-40 text-center"
                    :class="txnForm.type === 'income' ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'"
                    placeholder="0.00"
                    required
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Description</label>
                <input v-model="txnForm.description" type="text" class="input-field" placeholder="What was this for?" required />
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Account</label>
                  <select v-model="txnForm.account_id" class="input-field" required>
                    <option value="">Select account</option>
                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Category</label>
                  <select v-model="txnForm.category_id" class="input-field">
                    <option value="">No category</option>
                    <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Date</label>
                <input v-model="txnForm.transaction_date" type="date" class="input-field" required />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Notes (optional)</label>
                <textarea v-model="txnForm.notes" class="input-field" rows="2" placeholder="Additional notes..." />
              </div>
              <div class="flex gap-3 pt-2 pb-safe">
                <button type="button" @click="showCreateModal = false" class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">Cancel</button>
                <button type="submit" class="flex-1 py-3 gradient-primary text-white rounded-xl text-sm font-semibold hover:opacity-90 shadow-lg">Add Transaction</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  PlusIcon, XMarkIcon, MagnifyingGlassIcon, TrashIcon,
  ArrowTrendingUpIcon, ArrowTrendingDownIcon, CameraIcon, ArrowUpTrayIcon,
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

onMounted(() => {
  if (new URLSearchParams(window.location.search).get('create') === '1') {
    showCreateModal.value = true
    history.replaceState(null, '', '/transactions')
  }
})
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

// ─── Receipt Scanner ────────────────────────────────────────────────────────

const showScanModal  = ref(false)
const videoRef       = ref<HTMLVideoElement | null>(null)
const capturedImage  = ref<string | null>(null)
const isAnalyzing    = ref(false)
const scanError      = ref('')
const cameraError    = ref('')
let cameraStream: MediaStream | null = null

function openScanModal() {
  showScanModal.value = true
}

function closeScanModal() {
  showScanModal.value = false
}

watch(showScanModal, async (open) => {
  if (open) {
    capturedImage.value = null
    scanError.value     = ''
    cameraError.value   = ''
    await nextTick()
    startCamera()
  } else {
    stopCamera()
  }
})

async function startCamera() {
  try {
    cameraStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
    })
    if (videoRef.value) videoRef.value.srcObject = cameraStream
  } catch {
    try {
      cameraStream = await navigator.mediaDevices.getUserMedia({ video: true })
      if (videoRef.value) videoRef.value.srcObject = cameraStream
    } catch (err: any) {
      cameraError.value = err?.name === 'NotAllowedError'
        ? 'Camera access was denied. You can upload a photo instead.'
        : 'No camera found. You can upload a photo instead.'
    }
  }
}

function stopCamera() {
  cameraStream?.getTracks().forEach(t => t.stop())
  cameraStream = null
}

function capturePhoto() {
  const video = videoRef.value
  if (!video) return
  capturedImage.value = toJpegDataUrl(video, video.videoWidth, video.videoHeight)
  stopCamera()
  analyzeReceipt()
}

function onFileUpload(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (e) => {
    const img = new Image()
    img.onload = () => {
      capturedImage.value = toJpegDataUrl(img, img.naturalWidth, img.naturalHeight)
      stopCamera()
      analyzeReceipt()
    }
    img.src = e.target!.result as string
  }
  reader.readAsDataURL(file)
}

function toJpegDataUrl(source: CanvasImageSource, w: number, h: number): string {
  const maxDim = 1200
  if (w > maxDim || h > maxDim) {
    const ratio = Math.min(maxDim / w, maxDim / h)
    w = Math.round(w * ratio)
    h = Math.round(h * ratio)
  }
  const canvas = document.createElement('canvas')
  canvas.width  = w
  canvas.height = h
  canvas.getContext('2d')!.drawImage(source, 0, 0, w, h)
  return canvas.toDataURL('image/jpeg', 0.85)
}

async function analyzeReceipt() {
  if (!capturedImage.value) return
  isAnalyzing.value = true
  scanError.value   = ''
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const res  = await fetch('/transactions/scan-receipt', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body:    JSON.stringify({ image: capturedImage.value }),
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data.error ?? 'Failed to scan receipt.')

    txnForm.value.description       = data.description       ?? ''
    txnForm.value.amount            = data.amount            ?? 0
    txnForm.value.transaction_date  = data.transaction_date  ?? new Date().toISOString().split('T')[0]
    txnForm.value.type              = data.type              ?? 'expense'
    txnForm.value.notes             = data.notes             ?? ''

    closeScanModal()
    showCreateModal.value = true
  } catch (err: any) {
    scanError.value = err.message ?? 'Failed to analyze receipt. Please try again.'
  } finally {
    isAnalyzing.value = false
  }
}

function retake() {
  capturedImage.value = null
  scanError.value     = ''
  cameraError.value   = ''
  nextTick(() => startCamera())
}
</script>
