<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-950 px-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex w-16 h-16 rounded-2xl items-center justify-center shadow-2xl mb-4"
          style="background: linear-gradient(135deg, #1e40af, #1d4ed8)">
          <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Admin Panel</h1>
        <p class="text-slate-400 mt-2 text-sm">FinanceTracker Management Console</p>
      </div>

      <!-- Card -->
      <div class="bg-slate-900 rounded-2xl shadow-2xl border border-slate-700/50 p-8">
        <h2 class="text-lg font-semibold text-white mb-6">Sign in to continue</h2>

        <!-- Flash error -->
        <div v-if="flash?.error" class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
          {{ flash.error }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5">Admin Email</label>
            <input
              v-model="form.email"
              type="email"
              placeholder="admin@example.com"
              autocomplete="email"
              class="w-full px-4 py-3 rounded-xl border bg-slate-800 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
              :class="form.errors.email ? 'border-red-500' : 'border-slate-600'"
            />
            <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-400">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5">Password</label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                autocomplete="current-password"
                class="w-full px-4 py-3 pr-12 rounded-xl border bg-slate-800 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                :class="form.errors.password ? 'border-red-500' : 'border-slate-600'"
              />
              <button type="button" @click="showPassword = !showPassword"
                class="absolute right-3 top-3.5 text-slate-400 hover:text-slate-200 transition-colors">
                <EyeIcon v-if="!showPassword" class="w-5 h-5" />
                <EyeSlashIcon v-else class="w-5 h-5" />
              </button>
            </div>
            <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-400">{{ form.errors.password }}</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-3 px-4 text-white font-semibold rounded-xl shadow-lg hover:opacity-90 disabled:opacity-50 transition-all duration-200 flex items-center justify-center gap-2 mt-2"
            style="background: linear-gradient(135deg, #1e40af, #1d4ed8)"
          >
            <span v-if="form.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span>{{ form.processing ? 'Authenticating…' : 'Sign In' }}</span>
          </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-700">
          <p class="text-xs text-slate-500 text-center">
            Restricted access — authorized personnel only
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const flash = (page.props as any).flash

const showPassword = ref(false)
const form = useForm({ email: '', password: '' })

function submit() {
  form.post('/admin/login')
}
</script>
