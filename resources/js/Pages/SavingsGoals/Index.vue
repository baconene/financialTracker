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
        class="flex items-center gap-2 gradient-primary text-white rounded-xl font-medium hover:opacity-90 transition-all shadow-lg px-3 py-2.5 sm:px-4"
      >
        <PlusIcon class="w-4 h-4 shrink-0" />
        <span class="hidden sm:inline text-sm">Add Goal</span>
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
          <div class="absolute top-3 right-3 flex flex-col items-end gap-1.5">
            <span v-if="goal.status === 'completed'" class="px-2.5 py-1 bg-emerald-500 text-white text-xs font-semibold rounded-full">
              Completed 🎉
            </span>
            <span :class="['px-2 py-0.5 text-[10px] font-semibold rounded-full', priorityBadge(goal.priority ?? 'medium')]">
              {{ priorityLabel(goal.priority ?? 'medium') }}
            </span>
          </div>
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
          <div class="absolute bottom-3 left-4">
            <h3 class="text-white font-bold text-lg">{{ goal.name }}</h3>
            <p v-if="goal.target_date" class="text-white/70 text-xs">Target: {{ formatDate(goal.target_date) }}</p>
          </div>
        </div>

        <div class="p-5">
          <div class="flex items-center gap-4 mb-4">
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

          <div class="grid grid-cols-2 gap-2 mb-3">
            <div class="p-3 bg-gray-50 dark:bg-white/5 rounded-xl">
              <p class="text-[10px] text-gray-400 mb-0.5">Remaining</p>
              <p class="text-sm font-semibold" :style="{ color: goal.color }">
                {{ formatPHP(Math.max(0, goal.target_amount - goal.current_amount)) }}
              </p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-white/5 rounded-xl">
              <p class="text-[10px] text-gray-400 mb-0.5">Monthly Need</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ goal.required_monthly_contribution ? formatPHP(goal.required_monthly_contribution) : '—' }}
              </p>
            </div>
            <div v-if="goal.projected_completion_date" class="col-span-2 p-3 bg-gray-50 dark:bg-white/5 rounded-xl flex items-center gap-2">
              <ClockIcon class="w-3.5 h-3.5 text-gray-400 shrink-0" />
              <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Projected: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ formatDate(goal.projected_completion_date) }}</span>
                <span v-if="goal.months_remaining"> · {{ goal.months_remaining }}mo left</span>
              </p>
            </div>
          </div>

          <!-- Goal insight -->
          <div v-if="goalInsight(goal)" class="mb-3 flex items-start gap-2 px-3 py-2 rounded-xl text-[11px] leading-snug"
            :class="goalInsight(goal)!.type === 'success' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
              : goalInsight(goal)!.type === 'warning' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300'
              : 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300'">
            <CheckCircleIcon v-if="goalInsight(goal)!.type === 'success'" class="w-3.5 h-3.5 shrink-0 mt-0.5" />
            <ExclamationTriangleIcon v-else-if="goalInsight(goal)!.type === 'warning'" class="w-3.5 h-3.5 shrink-0 mt-0.5" />
            <ArrowTrendingUpIcon v-else class="w-3.5 h-3.5 shrink-0 mt-0.5" />
            <span>{{ goalInsight(goal)!.message }}</span>
          </div>

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

    <Teleport to="body">
      <!-- Full-screen Create/Edit Panel -->
      <Transition name="fullscreen-slide">
        <div
          v-if="showCreateModal || showEditModal"
          class="fixed inset-0 z-50 bg-gray-50 dark:bg-[#0F0F1A] flex flex-col"
        >
          <!-- Sticky Header -->
          <div class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-[#1A1A2E] border-b border-gray-100 dark:border-white/10 shrink-0 safe-top">
            <button
              type="button"
              @click="closeModals"
              class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors shrink-0"
            >
              <ArrowLeftIcon class="w-5 h-5" />
            </button>
            <div class="flex-1 min-w-0">
              <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ showEditModal ? 'Edit Goal' : 'New Savings Goal' }}
              </h2>
            </div>
          </div>

          <!-- Scrollable body -->
          <div class="flex-1 overflow-y-auto">
            <form @submit.prevent="showEditModal ? updateGoal() : createGoal()" id="goal-form">

              <!-- Hero image zone -->
              <div class="relative h-52 bg-gray-200 dark:bg-white/5 overflow-hidden">
                <img
                  v-if="imagePreview || form.image_url"
                  :src="imagePreview || form.image_url"
                  alt="Goal image"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex flex-col items-center justify-center gap-2 select-none">
                  <div class="w-14 h-14 rounded-2xl bg-white/30 dark:bg-white/10 flex items-center justify-center">
                    <CameraIcon class="w-7 h-7 text-gray-400 dark:text-gray-500" />
                  </div>
                  <span class="text-sm text-gray-400 dark:text-gray-500">Tap to add a cover photo</span>
                </div>
                <!-- Entire area is the file trigger -->
                <label class="absolute inset-0 cursor-pointer">
                  <input type="file" accept="image/*" class="hidden" @change="handleImageSelect" />
                </label>
                <!-- Bottom bar -->
                <div class="absolute bottom-0 inset-x-0 px-4 py-2.5 bg-gradient-to-t from-black/60 to-transparent flex items-center justify-between">
                  <span class="text-xs text-white/80">
                    {{ imagePreview || form.image_url ? 'Tap to change photo' : '' }}
                  </span>
                  <button
                    v-if="imagePreview || form.image_url"
                    type="button"
                    @click.prevent="removeImage"
                    class="flex items-center gap-1 px-2.5 py-1 bg-black/50 hover:bg-black/70 rounded-lg text-xs text-white transition-colors"
                  >
                    <XMarkIcon class="w-3.5 h-3.5" /> Remove
                  </button>
                </div>
              </div>

              <div class="p-4 space-y-3">

                <!-- Name & Description card -->
                <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden divide-y divide-gray-100 dark:divide-white/10">
                  <div class="px-4 py-3.5">
                    <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 mb-1.5 uppercase tracking-wide">Goal Name</label>
                    <input
                      v-model="form.name"
                      type="text"
                      placeholder="e.g. Emergency Fund"
                      class="w-full bg-transparent text-gray-900 dark:text-white placeholder-gray-300 dark:placeholder-gray-600 outline-none text-base font-semibold"
                      required
                    />
                  </div>
                  <div class="px-4 py-3.5">
                    <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 mb-1.5 uppercase tracking-wide">Description</label>
                    <textarea
                      v-model="form.description"
                      placeholder="What are you saving for?"
                      class="w-full bg-transparent text-gray-900 dark:text-white placeholder-gray-300 dark:placeholder-gray-600 outline-none text-sm resize-none leading-relaxed"
                      rows="2"
                    />
                  </div>
                </div>

                <!-- Amounts card -->
                <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden divide-y divide-gray-100 dark:divide-white/10">
                  <div class="px-4 py-3.5">
                    <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 mb-1.5 uppercase tracking-wide">Target Amount</label>
                    <div class="flex items-baseline gap-1.5">
                      <span class="text-xl font-bold text-gray-400 dark:text-gray-500">₱</span>
                      <input
                        v-model.number="form.target_amount"
                        type="number"
                        min="1"
                        step="0.01"
                        placeholder="0.00"
                        class="flex-1 bg-transparent text-gray-900 dark:text-white placeholder-gray-300 dark:placeholder-gray-600 outline-none text-2xl font-bold"
                        required
                      />
                    </div>
                  </div>
                  <div class="px-4 py-3.5">
                    <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 mb-1.5 uppercase tracking-wide">Already Saved</label>
                    <div class="flex items-baseline gap-1.5">
                      <span class="text-xl font-bold text-gray-400 dark:text-gray-500">₱</span>
                      <input
                        v-model.number="form.current_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        class="flex-1 bg-transparent text-gray-900 dark:text-white placeholder-gray-300 dark:placeholder-gray-600 outline-none text-2xl font-bold"
                      />
                    </div>
                  </div>
                </div>

                <!-- Date & Color card -->
                <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden divide-y divide-gray-100 dark:divide-white/10">
                  <div class="px-4 py-3.5 flex items-center justify-between">
                    <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Target Date</label>
                    <input
                      v-model="form.target_date"
                      type="date"
                      class="bg-transparent text-gray-900 dark:text-white outline-none text-sm font-medium text-right"
                    />
                  </div>
                  <div class="px-4 py-3.5">
                    <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-2">Priority</label>
                    <div class="flex gap-2">
                      <button v-for="p in (['low', 'medium', 'high'] as const)" :key="p" type="button"
                        @click="form.priority = p"
                        :class="[
                          'flex-1 py-2 rounded-xl border text-xs font-semibold transition-colors',
                          form.priority === p
                            ? p === 'high' ? 'bg-red-500 border-red-500 text-white'
                              : p === 'medium' ? 'bg-amber-500 border-amber-500 text-white'
                              : 'bg-emerald-500 border-emerald-500 text-white'
                            : 'border-gray-200 dark:border-white/20 text-gray-600 dark:text-gray-400 hover:border-gray-400',
                        ]">
                        {{ p === 'high' ? '🔴 High' : p === 'medium' ? '🟡 Medium' : '🟢 Low' }}
                      </button>
                    </div>
                  </div>
                  <div class="px-4 py-3.5 flex items-center justify-between">
                    <div>
                      <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Accent Color</label>
                      <div class="flex items-center gap-2 mt-1">
                        <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: form.color }" />
                        <span class="text-sm text-gray-600 dark:text-gray-300 font-mono">{{ form.color }}</span>
                      </div>
                    </div>
                    <input
                      v-model="form.color"
                      type="color"
                      class="w-10 h-10 rounded-xl border border-gray-200 dark:border-white/20 cursor-pointer bg-transparent"
                    />
                  </div>
                </div>

                <!-- Progress preview (edit mode) -->
                <div v-if="showEditModal && selectedGoal" class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
                  <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-3 uppercase tracking-wide">Current Progress</p>
                  <div class="flex items-center gap-3 mb-3">
                    <div class="flex-1 h-2 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all duration-500"
                        :style="{ width: selectedGoal.progress_percentage + '%', backgroundColor: form.color }"
                      />
                    </div>
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 shrink-0">{{ selectedGoal.progress_percentage.toFixed(0) }}%</span>
                  </div>
                  <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ formatPHP(selectedGoal.current_amount) }} saved</span>
                    <span>{{ formatPHP(selectedGoal.target_amount) }} target</span>
                  </div>
                </div>

              </div>
            </form>
          </div>

          <!-- Sticky Footer -->
          <div class="shrink-0 px-4 py-4 bg-white dark:bg-[#1A1A2E] border-t border-gray-100 dark:border-white/10 safe-bottom">
            <button
              type="submit"
              form="goal-form"
              class="w-full py-4 gradient-primary text-white rounded-2xl text-base font-semibold hover:opacity-90 transition-all shadow-lg active:scale-[0.98]"
            >
              {{ showEditModal ? 'Update Goal' : 'Create Goal' }}
            </button>
          </div>
        </div>
      </Transition>

      <!-- Contribute Modal (bottom-sheet) -->
      <Transition name="fade">
        <div v-if="showContributeModal && selectedGoal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 modal-backdrop" @click.self="showContributeModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md border border-gray-200 dark:border-white/10">
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
              <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-white/20" />
            </div>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10">
              <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Add Funds</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ selectedGoal.name }}</p>
              </div>
              <button @click="showContributeModal = false" class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 text-gray-500 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>
            <form @submit.prevent="submitContribution" class="p-6 space-y-4">
              <!-- Large amount input -->
              <div class="rounded-2xl p-4 text-center" :style="{ backgroundColor: selectedGoal.color + '15' }">
                <p class="text-xs font-medium uppercase tracking-wide mb-2" :style="{ color: selectedGoal.color }">Amount</p>
                <div class="flex items-center justify-center gap-1">
                  <span class="text-2xl font-bold text-gray-400">₱</span>
                  <input
                    v-model.number="contributeForm.amount"
                    type="number"
                    min="0.01"
                    step="0.01"
                    placeholder="0.00"
                    class="text-3xl font-bold bg-transparent border-none outline-none w-40 text-center text-gray-900 dark:text-white"
                    required
                  />
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Date</label>
                <input v-model="contributeForm.contribution_date" type="date" class="input-field" required />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Notes</label>
                <input v-model="contributeForm.notes" type="text" placeholder="Optional note" class="input-field" />
              </div>
              <div class="flex gap-3 pb-safe">
                <button type="button" @click="showContributeModal = false" class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">Cancel</button>
                <button type="submit" class="flex-1 py-3 gradient-primary text-white rounded-xl text-sm font-semibold hover:opacity-90 shadow-lg">Add Funds</button>
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
  PlusIcon, PencilIcon, TrashIcon, XMarkIcon, ArchiveBoxIcon,
  ArrowLeftIcon, CameraIcon, ArrowTrendingUpIcon, ClockIcon,
  ExclamationTriangleIcon, CheckCircleIcon,
} from '@heroicons/vue/24/outline'
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

// Image upload
const imageFile = ref<File | null>(null)
const imagePreview = ref<string | null>(null)
const removeImageFlag = ref(false)

function handleImageSelect(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  imageFile.value = file
  imagePreview.value = URL.createObjectURL(file)
  removeImageFlag.value = false
  form.value.image_url = ''
}

function removeImage() {
  imageFile.value = null
  imagePreview.value = null
  removeImageFlag.value = true
  form.value.image_url = ''
}

function priorityLabel(p: string) {
  return p === 'high' ? '🔴 High' : p === 'medium' ? '🟡 Medium' : '🟢 Low'
}
function priorityBadge(p: string) {
  return p === 'high'
    ? 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400'
    : p === 'medium'
    ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400'
    : 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400'
}

function goalInsight(goal: SavingsGoal): { type: 'success' | 'warning' | 'info'; message: string } | null {
  if (goal.status === 'completed') return null
  const remaining = goal.target_amount - goal.current_amount
  if (remaining <= 0) return { type: 'success', message: 'Goal reached! 🎉' }

  const rmc = goal.required_monthly_contribution
  const proj = goal.projected_completion_date
  const mr = goal.months_remaining

  if (mr !== null && mr !== undefined && mr <= 0) {
    return { type: 'warning', message: 'Target date has passed. Update your goal date.' }
  }

  if (proj && mr !== null && mr !== undefined) {
    const projDate = new Date(proj)
    const targetDate = new Date(goal.target_date!)
    if (projDate <= targetDate) {
      return { type: 'success', message: 'On track — projected to complete ahead of schedule! ✅' }
    } else {
      const delay = Math.round((projDate.getTime() - targetDate.getTime()) / (1000 * 60 * 60 * 24 * 30))
      return { type: 'warning', message: `At current pace, goal may be delayed by ~${delay} month${delay !== 1 ? 's' : ''}.` }
    }
  }

  if (!goal.target_date && rmc === null) {
    return { type: 'info', message: 'Set a target date to see monthly contribution guidance.' }
  }

  return null
}

const form = ref({
  name: '',
  description: '',
  target_amount: 0,
  current_amount: 0,
  target_date: '',
  color: '#7C3AED',
  priority: 'medium' as 'low' | 'medium' | 'high',
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
  imageFile.value = null
  imagePreview.value = null
  removeImageFlag.value = false
  resetForm()
}

function resetForm() {
  form.value = { name: '', description: '', target_amount: 0, current_amount: 0, target_date: '', color: '#7C3AED', priority: 'medium', image_url: '' }
}

function openEditModal(goal: SavingsGoal) {
  selectedGoal.value = goal
  imageFile.value = null
  imagePreview.value = null
  removeImageFlag.value = false
  form.value = {
    name: goal.name,
    description: goal.description || '',
    target_amount: goal.target_amount,
    current_amount: goal.current_amount,
    target_date: goal.target_date || '',
    color: goal.color,
    priority: goal.priority ?? 'medium',
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
  router.post('/savings-goals', {
    name: form.value.name,
    description: form.value.description,
    target_amount: form.value.target_amount,
    current_amount: form.value.current_amount,
    target_date: form.value.target_date,
    color: form.value.color,
    priority: form.value.priority,
    image: imageFile.value,
  }, {
    onSuccess: () => closeModals(),
  })
}

function updateGoal() {
  if (!selectedGoal.value) return
  router.put(`/savings-goals/${selectedGoal.value.id}`, {
    name: form.value.name,
    description: form.value.description,
    target_amount: form.value.target_amount,
    target_date: form.value.target_date,
    color: form.value.color,
    priority: form.value.priority,
    image: imageFile.value,
    remove_image: removeImageFlag.value ? '1' : '0',
  }, {
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

<style scoped>
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
