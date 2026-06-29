<template>
  <AppLayout title="Income Sources">

    <!-- Summary card -->
    <div class="rounded-2xl p-6 mb-5 text-white shadow-2xl relative overflow-hidden gradient-primary">
      <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent" />
      <div class="absolute -right-8 -top-8 w-36 h-36 rounded-full bg-white/10" />
      <div class="relative flex items-center justify-between">
        <div>
          <p class="text-sm text-white/70 mb-1">Total Monthly Income</p>
          <p class="text-4xl font-bold tracking-tight">{{ formatPHP(totalMonthly) }}</p>
          <p class="text-sm text-white/70 mt-1">{{ activeSources.length }} active source{{ activeSources.length !== 1 ? 's' : '' }}</p>
        </div>
        <button @click="openCreate" class="w-12 h-12 rounded-2xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-all active:scale-95">
          <PlusIcon class="w-6 h-6 text-white" />
        </button>
      </div>
    </div>

    <!-- Source list -->
    <div v-if="sources.length" class="space-y-3">
      <div
        v-for="source in sources"
        :key="source.id"
        class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4 flex items-center gap-4"
        :class="!source.is_active ? 'opacity-60' : ''"
      >
        <!-- Color dot / icon -->
        <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" :style="{ backgroundColor: source.color + '20' }">
          <BriefcaseIcon class="w-6 h-6" :style="{ color: source.color }" />
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ source.name }}</p>
            <span v-if="!source.is_active" class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-400">Inactive</span>
            <span v-if="source.category" class="text-[10px] px-1.5 py-0.5 rounded-full font-medium" :style="{ backgroundColor: source.category.color + '20', color: source.category.color }">
              {{ source.category.name }}
            </span>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            {{ formatPHP(source.amount) }} / {{ freqLabel(source.frequency) }}
            <span class="text-gray-400 dark:text-gray-500"> · {{ formatPHP(source.monthly_amount) }}/mo</span>
          </p>
          <p v-if="source.description" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">{{ source.description }}</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-1 shrink-0">
          <button @click="openEdit(source)" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-500/10 transition-all">
            <PencilIcon class="w-4 h-4" />
          </button>
          <button @click="confirmDelete(source)" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 py-16 text-center">
      <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center mx-auto mb-4">
        <BriefcaseIcon class="w-8 h-8 text-emerald-500" />
      </div>
      <p class="text-base font-semibold text-gray-800 dark:text-white mb-1">No income sources yet</p>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Add your salary, freelance, or any regular income to power cash flow projections.</p>
      <button @click="openCreate" class="inline-flex items-center gap-2 px-5 py-2.5 gradient-primary text-white text-sm font-semibold rounded-xl shadow-lg hover:opacity-90 transition-all">
        <PlusIcon class="w-4 h-4" /> Add Income Source
      </button>
    </div>

    <Teleport to="body">
      <!-- Create / Edit Modal -->
      <Transition name="fade">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 modal-backdrop" @click.self="showModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md border-t sm:border border-gray-200 dark:border-white/10 max-h-[92vh] overflow-y-auto">
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
              <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-white/20" />
            </div>
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-white/10">
              <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ editTarget ? 'Edit Income Source' : 'Add Income Source' }}
              </h3>
              <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 text-gray-500 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>

            <form @submit.prevent="submitForm" class="p-5 space-y-4">
              <!-- Color + Name row -->
              <div class="flex gap-3 items-end">
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Color</label>
                  <input v-model="form.color" type="color" class="h-10 w-14 rounded-xl border border-gray-200 dark:border-white/20 cursor-pointer" />
                </div>
                <div class="flex-1">
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Name</label>
                  <input v-model="form.name" type="text" class="input-field" placeholder="e.g. Monthly Salary" required />
                </div>
              </div>

              <!-- Amount + Frequency -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Amount</label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">₱</span>
                    <input v-model.number="form.amount" type="number" min="0" step="0.01" class="input-field pl-7" placeholder="0.00" required />
                  </div>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Frequency</label>
                  <select v-model="form.frequency" class="input-field">
                    <option value="weekly">Weekly</option>
                    <option value="biweekly">Bi-weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="annually">Annually</option>
                  </select>
                </div>
              </div>

              <!-- Monthly equivalent preview -->
              <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-xs text-emerald-700 dark:text-emerald-300">
                <span>📅</span>
                <span>Monthly equivalent: <strong>{{ formatPHP(computedMonthly) }}</strong></span>
              </div>

              <!-- Category -->
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Category <span class="normal-case font-normal">(optional)</span></label>
                <select v-model="form.category_id" class="input-field">
                  <option :value="null">No category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>

              <!-- Description -->
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Description <span class="normal-case font-normal">(optional)</span></label>
                <input v-model="form.description" type="text" class="input-field" placeholder="e.g. Regular day job salary" />
              </div>

              <!-- Active toggle -->
              <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-white/5">
                <div>
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Include in cash flow projections</p>
                </div>
                <button type="button" @click="form.is_active = !form.is_active"
                  class="relative w-11 h-6 rounded-full transition-colors shrink-0"
                  :class="form.is_active ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-white/20'">
                  <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                    :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" />
                </button>
              </div>

              <div class="flex gap-3 pt-1 pb-safe">
                <button type="button" @click="showModal = false"
                  class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">
                  Cancel
                </button>
                <button type="submit" :disabled="saving"
                  class="flex-1 py-3 gradient-primary text-white rounded-xl text-sm font-semibold hover:opacity-90 disabled:opacity-50 transition-all">
                  {{ saving ? 'Saving…' : (editTarget ? 'Update' : 'Add Source') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>

      <!-- Delete confirm -->
      <Transition name="fade">
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop" @click.self="deleteTarget = null">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl shadow-2xl w-full max-w-sm border border-gray-200 dark:border-white/10 p-6">
            <div class="w-12 h-12 bg-red-100 dark:bg-red-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <TrashIcon class="w-6 h-6 text-red-500" />
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white text-center mb-1">Remove Income Source?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">
              "<span class="font-medium text-gray-700 dark:text-gray-300">{{ deleteTarget.name }}</span>" will be removed from projections.
            </p>
            <div class="flex gap-3">
              <button @click="deleteTarget = null" class="flex-1 py-2.5 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium">Cancel</button>
              <button @click="doDelete" :disabled="deleting" class="flex-1 py-2.5 bg-red-500 text-white rounded-xl text-sm font-semibold hover:bg-red-600 disabled:opacity-50">
                {{ deleting ? 'Removing…' : 'Remove' }}
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
import { PlusIcon, PencilIcon, TrashIcon, XMarkIcon, BriefcaseIcon } from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { IncomeSource, Category } from '@/types'

const props = defineProps<{
  sources: IncomeSource[]
  categories: Category[]
  totalMonthly: number
}>()

const { formatPHP } = useCurrency()

const activeSources = computed(() => props.sources.filter(s => s.is_active))

function freqLabel(f: string): string {
  return { weekly: 'week', biweekly: '2 weeks', monthly: 'month', quarterly: 'quarter', annually: 'year' }[f] ?? f
}

// ── Form ──────────────────────────────────────────────────────────
const showModal  = ref(false)
const editTarget = ref<IncomeSource | null>(null)
const saving     = ref(false)

const defaultForm = () => ({
  name: '',
  amount: null as number | null,
  frequency: 'monthly' as IncomeSource['frequency'],
  description: '',
  color: '#10B981',
  category_id: null as number | null,
  is_active: true,
})

const form = ref(defaultForm())

const computedMonthly = computed(() => {
  const amt = form.value.amount ?? 0
  return Math.round(({
    weekly: amt * 4.33,
    biweekly: amt * 2.17,
    monthly: amt,
    quarterly: amt / 3,
    annually: amt / 12,
  }[form.value.frequency] ?? amt) * 100) / 100
})

function openCreate() {
  editTarget.value = null
  form.value = defaultForm()
  showModal.value = true
}

function openEdit(source: IncomeSource) {
  editTarget.value = source
  form.value = {
    name: source.name,
    amount: source.amount,
    frequency: source.frequency,
    description: source.description ?? '',
    color: source.color,
    category_id: source.category_id ?? null,
    is_active: source.is_active,
  }
  showModal.value = true
}

function submitForm() {
  saving.value = true
  if (editTarget.value) {
    router.put(`/income-sources/${editTarget.value.id}`, form.value, {
      onSuccess: () => { showModal.value = false },
      onFinish: () => { saving.value = false },
    })
  } else {
    router.post('/income-sources', form.value, {
      onSuccess: () => { showModal.value = false },
      onFinish: () => { saving.value = false },
    })
  }
}

// ── Delete ────────────────────────────────────────────────────────
const deleteTarget = ref<IncomeSource | null>(null)
const deleting     = ref(false)

function confirmDelete(source: IncomeSource) { deleteTarget.value = source }
function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  router.delete(`/income-sources/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null },
    onFinish: () => { deleting.value = false },
  })
}
</script>
