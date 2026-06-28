<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-[#0F0F23] px-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex w-16 h-16 rounded-2xl gradient-primary items-center justify-center shadow-2xl mb-4">
          <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold gradient-text">FinanceTracker</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-2">Your personal finance companion</p>
      </div>

      <!-- Card -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl shadow-xl border border-gray-200 dark:border-white/10 p-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Welcome back</h2>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
            <input
              v-model="form.email"
              type="email"
              placeholder="juan@example.com"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-white/20 bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 dark:focus:ring-violet-400 transition-all"
              :class="{ 'border-red-500': errors.email }"
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-500">{{ errors.email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-200 dark:border-white/20 bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
                :class="{ 'border-red-500': errors.password }"
              />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <EyeIcon v-if="!showPassword" class="w-5 h-5" />
                <EyeSlashIcon v-else class="w-5 h-5" />
              </button>
            </div>
            <p v-if="errors.password" class="mt-1 text-sm text-red-500">{{ errors.password }}</p>
          </div>

          <div class="flex items-center gap-2">
            <input v-model="form.remember" type="checkbox" id="remember" class="w-4 h-4 rounded accent-violet-600" />
            <label for="remember" class="text-sm text-gray-600 dark:text-gray-400">Remember me</label>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-3 px-4 gradient-primary text-white font-semibold rounded-xl shadow-lg hover:opacity-90 disabled:opacity-50 transition-all duration-200 flex items-center justify-center gap-2"
          >
            <span v-if="form.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span>{{ form.processing ? 'Signing in...' : 'Sign In' }}</span>
          </button>
        </form>

        <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
          Don't have an account?
          <Link href="/register" class="text-violet-600 dark:text-violet-400 font-medium hover:underline">Sign up</Link>
        </p>

        <!-- Demo credentials -->
        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-500/10 rounded-xl border border-blue-200 dark:border-blue-500/30">
          <p class="text-xs text-blue-700 dark:text-blue-400 font-medium mb-1">Demo Account</p>
          <p class="text-xs text-blue-600 dark:text-blue-300">Email: juan@example.com</p>
          <p class="text-xs text-blue-600 dark:text-blue-300">Password: password</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'

defineProps<{ errors?: Record<string, string> }>()

const showPassword = ref(false)
const form = useForm({
  email: 'juan@example.com',
  password: 'password',
  remember: false,
})

const errors = ref<Record<string, string>>({})

function submit() {
  form.post('/login', {
    onError: (e) => { errors.value = e },
  })
}
</script>
