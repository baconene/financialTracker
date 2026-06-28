<template>
  <AppLayout title="Savings Goals">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Savings Goals</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track your financial goals and milestones</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="flex items-center gap-2 px-4 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90 transition-all shadow-lg"
      >
        <PlusIcon class="w-4 h-4" />
        Add Goal
      </button>
    </div>

    <!-- Summary Bar -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4 text-center">
        <p class="text-2xl font-bold gradient-text">{{ goals.length }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Active Goals</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4 text-center">
        <p class="text-2xl font-bold gradient-text">{{ formatShort(totalSaved) }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Saved</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-xl border border-gray-200 dark:border-white/10 p-4 text-center">
        <p class="text-2xl font-bold gradient-text">{{ formatShort(totalTarget) }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Target</p>
      </div>
    </div>

    <!-- Goals Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="goal in goals"
        :key="goal.id"
        class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden card-hover group"
      >
        <!-- Image -->
        <div class="relative h-40 overflow-hidden">
          <img
            v-if="goal.image_url"
            :src="goal.image_url"
            :alt="goal.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
          <div v-else class="w-full h-full flex items-center justify-center" :style="{ background: `linear-gradient(135deg, ${goal.color}40, ${goal.color}20)` }">
            <span class="text-5xl">{{ goalEmoji(goal) }}</span>
          </div>
          <!-- Status badge -->
          <div class="absolute top-3 right-3">
            <span v-if="goal.status === 'completed'" class="px-2.5 py-1 bg-emerald-500 text-white text-xs font-semibold rounded-full">
              Completed 🎉
            </span>
          </div>
          <!-- Gradient overlay -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
          <div class="absolute bottom-3 left-4">
            <h3 class="text-white font-bold text-lg">{{ goal.name }}</h3>
            <p v-if="goal.target_date" class="text-white/70 text-xs">Target: {{ formatDate(goal.target_date) }}</p>
          </div>
        </div>

        <div class="p-5">
          <!-- Progress Ring + Amounts -->
          <div class="flex items-center gap-4 mb-4">
            <!-- SVG Progress Ring -->
            <div class="relative w-20 h-20 shrink-0">
              <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80">
                <circle cx="40" cy="40" r="34" fill="none" stroke="currentColor" class="text-gray-100 dark:text-white/10" stroke-width="8" />
                <circle
                  cx="40" cy="40" r="34" fill="none"
                  :stroke="goal.color"
                  stroke-width="8"
                  stroke-linecap="round"
                  :stroke-dasharray="circumference"
                  :stroke-dashoffset="dashOffset(goal.progress_percentage)"
                  class="transition-all duration-1000"
                />
              </svg>
              <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ goal.progress_percentage.toFixed(0) }}%</span>
              </div>
            </div>

            <div class="flex-1">
              <div class="mb-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">Saved</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatPHP(goal.current_amount) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Target</p>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ formatPHP(goal.target_amount) }}</p>
              </div>
            </div>
          </div>

          <!-- Remaining -->
          <div class="flex items-center justify-between mb-4 p-3 bg-gray-50 dark:bg-white/5 rounded-xl">
            <span class="text-xs text-gray-500 dark:text-gray-400">Remaining</span>
            <span class="text-sm font-semibold" :style="{ color: goal.color }">
              {{ formatPHP(Math.max(0, goal.target_amount - goal.current_amount)) }}
            </span>
          </div>

          <!-- Actions -->
          <div class="flex gap-2">
            <button
              @click="openContributeModal(goal)"
              class="flex-1 py-2 text-sm font-medium rounded-xl transition-all text-white"
              :style="{ backgroundColor: goal.color }"
            >
              + Add Funds
            </button>
            <button
              @click="openEditModal(goal)"
              class="w-10 h-9 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors"
            >
              <PencilIcon class="w-4 h-4" />
            </button>
            <button
              @click="deleteGoal(goal)"
              class="w-10 h-9 flex items-center justify-center rounded-xl bg-red-50 dark:bg-red-500/10 text-red-500 hover:bg-red-100 dark:hover:bg-red-500/20 transition-colors"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="goals.length === 0" class="col-span-full flex flex-col items-center justify-center py-16 text-center">
        <div class="w-20 h-20 rounded-2xl gradient-primary flex items-center justify-center mb-4 opacity-60">
          <ArchiveBoxIcon class="w-10 h-10 text-white" />
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Savings Goals Yet</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">Start by creating your first savings goal</p>
        <button @click="showCreateModal = true" class="px-6 py-2.5 gradient-primary text-white rounded-xl font-medium hover:opacity-90">
          Create Goal
        </button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop" @click.self="closeModals">
          <Transition name="slide-up">
            <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl shadow-2xl w-full max-w-lg border border-gray-200 dark:border-white/10" @click.stop>
              <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ showEditModal ? 'Edit Goal' : 'New Savings Goal' }}</h3>
                <button @click="closeModals" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500">
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>

              <form @submit.prevent="showEditModal ? updateGoal() : createGoal()" class="p-6 space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Goal Name</label>
                  <input v-model="form.name" type="text" placeholder="e.g. Emergency Fund" class="input-field" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                  <textarea v-model="form.description" class="input-field" rows="2" placeholder="What are you saving for?" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Target Amount (₱)</label>
                    <input v-model.number="form.target_amount" type="number" min="1" step="0.01" class="input-field" required />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Current Amount (₱)</label>
                    <input v-model.number="form.current_amount" type="number" min="0" step="0.01" class="input-field" />
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Target Date</label>
                    <input v-model="form.target_date" type="date" class="input-field" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Color</label>
                    <input v-model="form.color" type="color" class="h-10 w-full rounded-xl border border-gray-200 dark:border-white/20 cursor-pointer" />
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Image URL (optional)</label>
                  <input v-model="form.image_url" type="url" placeholder="https://..." class="input-field" />
                </div>
                <div class="flex gap-3 pt-2">
                  <button type="button" @click="closeModals" class="flex-1 py-2.5 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">Cancel</button>
                  <button type="submit" class="flex-1 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90 transition-all">
                    {{ showEditModal ? 'Update Goal' : 'Create Goal' }}
                  </button>
                </div>
              </form>
            </div>
          </Transition>
        </div>
      </Transition>

      <!-- Contribute Modal -->
      <Transition name="fade">
        <div v-if="showContributeModal && selectedGoal" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop" @click.self="showContributeModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl shadow-2xl w-full max-w-md border border-gray-200 dark:border-white/10">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Contribution</h3>
              <button @click="showContributeModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>
            <div class="p-6">
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Adding to: <strong class="text-gray-900 dark:text-white">{{ selectedGoal.name }}</strong></p>
              <form @submit.prevent="submitContribution" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount (₱)</label>
                  <input v-model.number="contributeForm.amount" type="number" min="0.01" step="0.01" class="input-field" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Date</label>
                  <input v-model="contributeForm.contribution_date" type="date" class="input-field" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes</label>
                  <input v-model="contributeForm.notes" type="text" placeholder="Optional note" class="input-field" />
                </div>
                <div class="flex gap-3">
                  <button type="button" @click="showContributeModal = false" class="flex-1 py-2.5 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">Cancel</button>
                  <button type="submit" class="flex-1 py-2.5 gradient-primary text-white rounded-xl text-sm font-medium hover:opacity-90 transition-all">Add Funds</button>
                </div>
              </form>
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
import { PlusIcon, PencilIcon, TrashIcon, XMarkIcon, ArchiveBoxIcon } from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { SavingsGoal } from '@/types'
import dayjs from 'dayjs'

const props = defineProps<{ goals: SavingsGoal[] }>()
const { formatPHP, formatShort } = useCurrency()

const circumference = 2 * Math.PI * 34

function dashOffset(percentage: number): number {
  return circumference - (percentage / 100) * circumference
}

function formatDate(date: string): string {
  return dayjs(date).format('MMM D, YYYY')
}

function goalEmoji(goal: SavingsGoal): string {
  const icons: Record<string, string> = {
    'shield-check': '🛡️', 'computer-desktop': '💻', 'map': '🗺️',
    'home': '🏠', 'car': '🚗', 'savings': '💰', 'archive-box': '📦',
  }
  return icons[goal.icon] || '💰'
}

const totalSaved = computed(() => props.goals.reduce((sum, g) => sum + g.current_amount, 0))
const totalTarget = computed(() => props.goals.reduce((sum, g) => sum + g.target_amount, 0))

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showContributeModal = ref(false)
const selectedGoal = ref<SavingsGoal | null>(null)

const form = ref({
  name: '',
  description: '',
  target_amount: 0,
  current_amount: 0,
  target_date: '',
  color: '#7C3AED',
  image_url: '',
})

const contributeForm = ref({
  amount: 0,
  contribution_date: new Date().toISOString().split('T')[0],
  notes: '',
})

function closeModals() {
  showCreateModal.value = false
  showEditModal.value = false
  selectedGoal.value = null
  resetForm()
}

function resetForm() {
  form.value = { name: '', description: '', target_amount: 0, current_amount: 0, target_date: '', color: '#7C3AED', image_url: '' }
}

function openEditModal(goal: SavingsGoal) {
  selectedGoal.value = goal
  form.value = {
    name: goal.name,
    description: goal.description || '',
    target_amount: goal.target_amount,
    current_amount: goal.current_amount,
    target_date: goal.target_date || '',
    color: goal.color,
    image_url: goal.image_url || '',
  }
  showEditModal.value = true
}

function openContributeModal(goal: SavingsGoal) {
  selectedGoal.value = goal
  contributeForm.value = { amount: 0, contribution_date: new Date().toISOString().split('T')[0], notes: '' }
  showContributeModal.value = true
}

function createGoal() {
  router.post('/savings-goals', form.value, {
    onSuccess: () => closeModals(),
  })
}

function updateGoal() {
  if (!selectedGoal.value) return
  router.put(`/savings-goals/${selectedGoal.value.id}`, form.value, {
    onSuccess: () => closeModals(),
  })
}

function deleteGoal(goal: SavingsGoal) {
  if (!confirm(`Delete "${goal.name}"?`)) return
  router.delete(`/savings-goals/${goal.id}`)
}

function submitContribution() {
  if (!selectedGoal.value) return
  router.post(`/savings-goals/${selectedGoal.value.id}/contribute`, contributeForm.value, {
    onSuccess: () => { showContributeModal.value = false },
  })
}
</script>

