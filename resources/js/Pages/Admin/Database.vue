<template>
  <div class="min-h-screen bg-slate-950 text-white flex flex-col">

    <!-- Top nav -->
    <header class="sticky top-0 z-30 bg-slate-900 border-b border-slate-700/50 shadow-lg shrink-0">
      <div class="max-w-screen-xl mx-auto px-4 h-14 flex items-center justify-between gap-3">
        <!-- Brand -->
        <div class="flex items-center gap-3 shrink-0">
          <div class="w-8 h-8 rounded-xl flex items-center justify-center shadow"
            style="background: linear-gradient(135deg, #1e40af, #1d4ed8)">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
          </div>
          <!-- Nav links -->
          <nav class="flex items-center gap-1">
            <a href="/admin/dashboard"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-medium transition-all">
              <UsersIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Users</span>
            </a>
            <a href="/admin/database"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600/20 text-blue-400 text-sm font-medium border border-blue-500/30">
              <CircleStackIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Database</span>
            </a>
          </nav>
        </div>

        <!-- Right: user + logout -->
        <div class="flex items-center gap-2">
          <p class="text-sm font-medium text-white hidden sm:block">{{ auth.user?.name }}</p>
          <form @submit.prevent="logout">
            <button type="submit"
              class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-800 hover:bg-red-600/80 text-slate-300 hover:text-white text-sm font-medium transition-all border border-slate-700/50">
              <ArrowRightOnRectangleIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Sign out</span>
            </button>
          </form>
        </div>
      </div>
    </header>

    <main class="flex-1 max-w-screen-xl mx-auto w-full px-4 py-5 flex flex-col gap-4" style="min-height: 0">

      <!-- Mobile: table selector -->
      <div class="lg:hidden">
        <select @change="onMobileSelect"
          class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700/50 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">Select a table…</option>
          <option v-for="t in tables" :key="t" :value="t">{{ t }}</option>
        </select>
      </div>

      <!-- Desktop two-column layout -->
      <div class="flex gap-4 flex-1" style="min-height: 0">

        <!-- Sidebar: table list -->
        <aside class="hidden lg:flex flex-col w-52 shrink-0 bg-slate-900 rounded-2xl border border-slate-700/50 overflow-hidden">
          <div class="px-4 py-3 border-b border-slate-700/50 shrink-0">
            <div class="flex items-center gap-2">
              <CircleStackIcon class="w-4 h-4 text-blue-400" />
              <h2 class="text-sm font-semibold text-white">Tables</h2>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">{{ tables.length }} tables</p>
          </div>
          <div class="overflow-y-auto flex-1 py-1.5">
            <button v-for="t in tables" :key="t" @click="selectTable(t)"
              class="w-full text-left px-4 py-2 text-xs transition-colors flex items-center gap-2 group"
              :class="activeTable === t
                ? 'text-blue-400 bg-blue-600/10 border-l-2 border-blue-500'
                : 'text-slate-400 hover:text-white hover:bg-slate-800 border-l-2 border-transparent'">
              <TableCellsIcon class="w-3.5 h-3.5 shrink-0" />
              <span class="truncate font-mono">{{ t }}</span>
            </button>
          </div>
        </aside>

        <!-- Main query area -->
        <div class="flex-1 min-w-0 flex flex-col gap-4" style="min-height: 0">

          <!-- SQL editor card -->
          <div class="bg-slate-900 rounded-2xl border border-slate-700/50 p-4 shrink-0">
            <div class="flex items-center justify-between mb-2.5">
              <div class="flex items-center gap-2">
                <CommandLineIcon class="w-4 h-4 text-blue-400" />
                <span class="text-sm font-semibold text-white">SQL Query</span>
              </div>
              <span class="text-xs text-slate-600">SELECT only · auto-limited to 500 rows · Ctrl+Enter to run</span>
            </div>
            <textarea
              v-model="sql"
              rows="5"
              spellcheck="false"
              placeholder="SELECT * FROM users LIMIT 10"
              class="w-full bg-slate-800 text-green-300 font-mono text-sm rounded-xl px-4 py-3 border border-slate-700/50 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y leading-relaxed"
              @keydown.ctrl.enter.prevent="runQuery"
              @keydown.meta.enter.prevent="runQuery"
            />
            <div class="flex items-center justify-between mt-3">
              <button v-if="sql" @click="sql = ''; result = null; error = null; activeTable = null"
                class="text-xs text-slate-500 hover:text-slate-300 transition-colors">
                Clear
              </button>
              <div v-else />
              <button @click="runQuery" :disabled="!sql.trim() || isLoading"
                class="flex items-center gap-2 px-5 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white transition-all shadow-lg shadow-blue-900/30">
                <span v-if="isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                <PlayIcon v-else class="w-4 h-4" />
                {{ isLoading ? 'Running…' : 'Execute' }}
              </button>
            </div>
          </div>

          <!-- Results -->
          <div class="flex-1 bg-slate-900 rounded-2xl border border-slate-700/50 flex flex-col overflow-hidden" style="min-height: 0">

            <!-- Error state -->
            <div v-if="error" class="p-5">
              <div class="flex items-start gap-3 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30">
                <ExclamationCircleIcon class="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
                <div>
                  <p class="text-sm font-semibold text-red-400 mb-1">Query Error</p>
                  <p class="text-xs text-red-300 font-mono leading-relaxed">{{ error }}</p>
                </div>
              </div>
            </div>

            <!-- Results table -->
            <template v-else-if="result">
              <!-- Stats bar -->
              <div class="px-4 py-3 border-b border-slate-700/50 shrink-0 flex items-center gap-3 flex-wrap">
                <span class="text-sm font-semibold text-white">{{ result.count.toLocaleString() }} row{{ result.count !== 1 ? 's' : '' }}</span>
                <span class="text-xs text-slate-500">{{ result.time_ms }}ms</span>
                <span v-if="result.count >= 500" class="text-xs px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-400 border border-amber-500/30">
                  Limited to 500 rows
                </span>
                <span v-if="activeTable" class="text-xs px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 font-mono">
                  {{ activeTable }}
                </span>
              </div>

              <!-- Empty result -->
              <div v-if="result.count === 0"
                class="flex-1 flex flex-col items-center justify-center gap-2 text-slate-600 py-16">
                <TableCellsIcon class="w-10 h-10" />
                <p class="text-sm">Query returned no rows</p>
              </div>

              <!-- Data table -->
              <div v-else class="overflow-auto flex-1">
                <table class="w-full text-xs border-collapse">
                  <thead>
                    <tr class="bg-slate-800 sticky top-0 z-10">
                      <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap border-b border-slate-700 w-10 text-right">#</th>
                      <th v-for="col in result.columns" :key="col"
                        class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap border-b border-slate-700">
                        {{ col }}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, i) in result.rows" :key="i"
                      class="border-b border-slate-800/60 hover:bg-slate-800/40 transition-colors">
                      <td class="px-3 py-2 text-slate-600 text-right select-none tabular-nums">{{ i + 1 }}</td>
                      <td v-for="col in result.columns" :key="col" class="px-3 py-2 font-mono whitespace-nowrap">
                        <span v-if="row[col] === null" class="text-slate-600 italic not-italic">NULL</span>
                        <span v-else
                          class="text-slate-300"
                          :title="String(row[col]).length > 60 ? String(row[col]) : undefined">
                          {{ truncate(String(row[col]), 60) }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>

            <!-- Empty state (no query yet) -->
            <div v-else class="flex-1 flex flex-col items-center justify-center gap-3 text-slate-700 py-16">
              <CircleStackIcon class="w-14 h-14" />
              <p class="text-sm">Select a table from the sidebar or write a query above</p>
            </div>

          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  ArrowRightOnRectangleIcon, CircleStackIcon, TableCellsIcon,
  CommandLineIcon, PlayIcon, ExclamationCircleIcon, UsersIcon,
} from '@heroicons/vue/24/outline'

defineProps<{ tables: string[] }>()

const page   = usePage()
const auth   = (page.props as any).auth

const sql         = ref('')
const isLoading   = ref(false)
const activeTable = ref<string | null>(null)
const error       = ref<string | null>(null)

interface QueryResult {
  columns: string[]
  rows: Record<string, unknown>[]
  count: number
  time_ms: number
}
const result = ref<QueryResult | null>(null)

function selectTable(table: string) {
  activeTable.value = table
  sql.value = `SELECT * FROM \`${table}\` LIMIT 100`
  runQuery()
}

function onMobileSelect(e: Event) {
  const t = (e.target as HTMLSelectElement).value
  if (t) selectTable(t)
}

async function runQuery() {
  const query = sql.value.trim()
  if (!query || isLoading.value) return

  isLoading.value = true
  error.value     = null
  result.value    = null

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const res  = await fetch('/admin/database/query', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body:    JSON.stringify({ sql: query }),
    })
    const data = await res.json()

    if (!res.ok) {
      error.value = data.error ?? 'Query failed.'
    } else {
      result.value = data as QueryResult
    }
  } catch (e: any) {
    error.value = e.message ?? 'Network error.'
  } finally {
    isLoading.value = false
  }
}

function truncate(str: string, max: number): string {
  return str.length > max ? str.slice(0, max) + '…' : str
}

function logout() { router.post('/admin/logout') }
</script>
