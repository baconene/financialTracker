<template>
  <AppLayout title="Markets">

    <!-- Summary card -->
    <div class="rounded-2xl p-6 mb-5 text-white shadow-2xl relative overflow-hidden gradient-primary">
      <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent" />
      <div class="absolute -right-8 -top-8 w-36 h-36 rounded-full bg-white/10" />
      <div class="relative flex items-start justify-between gap-4">
        <div class="min-w-0">
          <p class="text-sm text-white/70 mb-1">{{ portfolio.length ? 'Holdings Value' : 'Watchlist' }}</p>
          <template v-if="portfolio.length">
            <div v-for="p in portfolio" :key="p.currency" class="mb-1">
              <p class="text-3xl font-bold tracking-tight">{{ formatMoney(p.value, p.currency) }}</p>
              <p class="text-xs text-white/80">
                Today <span class="font-semibold">{{ signed(formatMoney(p.dayChange, p.currency), p.dayChange) }}</span>
                <template v-if="p.hasCost">
                  · P/L <span class="font-semibold">{{ signed(formatMoney(p.pl, p.currency), p.pl) }}</span>
                  ({{ signedPct(p.cost ? p.pl / p.cost * 100 : 0) }})
                </template>
              </p>
            </div>
          </template>
          <p v-else class="text-4xl font-bold tracking-tight">{{ items.length }} <span class="text-lg font-medium text-white/80">symbol{{ items.length !== 1 ? 's' : '' }}</span></p>
          <p class="text-xs text-white/70 mt-2 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full" :class="loading ? 'bg-amber-300 animate-pulse' : (fetchError ? 'bg-red-300' : 'bg-emerald-300')" />
            <template v-if="fetchError">{{ fetchError }}</template>
            <template v-else-if="lastUpdated">Updated {{ lastUpdated }} · auto-refresh every {{ refreshSeconds }}s</template>
            <template v-else>Loading prices…</template>
          </p>
        </div>
        <div class="flex gap-2 shrink-0">
          <button @click="loadQuotes" :disabled="loading" title="Refresh prices"
            class="w-12 h-12 rounded-2xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-all active:scale-95 disabled:opacity-60">
            <ArrowPathIcon class="w-6 h-6 text-white" :class="loading ? 'animate-spin' : ''" />
          </button>
          <button @click="openCreate" title="Add symbol"
            class="w-12 h-12 rounded-2xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-all active:scale-95">
            <PlusIcon class="w-6 h-6 text-white" />
          </button>
        </div>
      </div>
    </div>

    <!-- Triggered alerts -->
    <div v-if="triggered.length" class="mb-4 space-y-2">
      <div v-for="t in triggered" :key="t.key"
        class="flex items-center gap-3 px-4 py-3 rounded-xl border bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30">
        <BellAlertIcon class="w-5 h-5 text-amber-500 shrink-0" />
        <p class="text-sm text-amber-800 dark:text-amber-300 flex-1">{{ t.message }}</p>
      </div>
    </div>

    <!-- Toolbar -->
    <div v-if="items.length" class="flex items-center gap-2 mb-4 flex-wrap">
      <div class="flex p-1 rounded-xl bg-gray-100 dark:bg-white/5">
        <button v-for="f in filters" :key="f.value" @click="filter = f.value"
          class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          :class="filter === f.value ? 'bg-white dark:bg-[#1A1A2E] text-violet-700 dark:text-violet-300 shadow-sm' : 'text-gray-500 dark:text-gray-400'">
          {{ f.label }}
        </button>
      </div>
      <div class="flex-1" />
      <button v-if="notificationsSupported && notifyPermission !== 'granted'" @click="enableNotifications"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5"
        :disabled="notifyPermission === 'denied'"
        :title="notifyPermission === 'denied' ? 'Notifications are blocked in your browser settings' : ''">
        <BellIcon class="w-4 h-4" /> {{ notifyPermission === 'denied' ? 'Notifications blocked' : 'Enable price alerts' }}
      </button>
      <span v-else-if="notifyPermission === 'granted'" class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
        <BellIcon class="w-4 h-4" /> Alerts on
      </span>
    </div>

    <!-- Watchlist -->
    <div v-if="filteredItems.length" class="space-y-3">
      <div v-for="item in filteredItems" :key="item.id"
        class="bg-white dark:bg-[#1A1A2E] rounded-2xl border p-4 transition-colors"
        :class="alertState(item) ? 'border-amber-300 dark:border-amber-500/40' : 'border-gray-200 dark:border-white/10'">
        <div class="flex items-center gap-3">
          <!-- Icon -->
          <button @click="openChart(item)" class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 overflow-hidden"
            :class="item.asset_type === 'crypto' ? 'bg-amber-50 dark:bg-amber-500/10' : 'bg-violet-50 dark:bg-violet-500/10'">
            <img v-if="quotes[item.id]?.image" :src="quotes[item.id]!.image!" :alt="item.ticker" class="w-7 h-7" />
            <span v-else class="text-[11px] font-bold" :class="item.asset_type === 'crypto' ? 'text-amber-600' : 'text-violet-600 dark:text-violet-400'">
              {{ item.ticker.replace(/[\^=]/g, '').slice(0, 4) }}
            </span>
          </button>

          <!-- Name -->
          <button @click="openChart(item)" class="flex-1 min-w-0 text-left">
            <div class="flex items-center gap-2">
              <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ item.ticker }}</p>
              <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                :class="item.asset_type === 'crypto' ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300' : 'bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-300'">
                {{ item.asset_type === 'crypto' ? 'Crypto' : 'Market' }}
              </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ quotes[item.id]?.name ?? item.name ?? item.symbol }}</p>
          </button>

          <!-- Sparkline -->
          <svg v-if="quotes[item.id]?.sparkline?.length" viewBox="0 0 100 32" preserveAspectRatio="none"
            class="hidden sm:block w-24 h-8 shrink-0" :aria-label="`${quotes[item.id]!.sparkline_range} trend`">
            <polyline :points="sparkPoints(quotes[item.id]!.sparkline)" fill="none" stroke-width="1.5"
              vector-effect="non-scaling-stroke" :class="trendClass(item, 'stroke')" />
          </svg>

          <!-- Price -->
          <div class="text-right shrink-0 min-w-[5.5rem]">
            <template v-if="quotes[item.id]">
              <p class="text-sm font-semibold text-gray-900 dark:text-white tabular-nums">
                {{ formatMoney(quotes[item.id]!.price, quotes[item.id]!.currency) }}
              </p>
              <p class="text-xs font-medium tabular-nums" :class="trendClass(item, 'text')">
                {{ quotes[item.id]!.change_pct !== null ? signedPct(quotes[item.id]!.change_pct!) : '—' }}
                <span class="text-gray-400 font-normal">{{ quotes[item.id]!.sparkline_range }}</span>
              </p>
            </template>
            <p v-else-if="loading && !loadedOnce" class="text-xs text-gray-400">Loading…</p>
            <p v-else class="text-xs text-gray-400">No data</p>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-0.5 shrink-0">
            <button @click="openEdit(item)" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-500/10 transition-all" title="Edit">
              <PencilIcon class="w-4 h-4" />
            </button>
            <button @click="deleteTarget = item" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all" title="Remove">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Holding + alert details -->
        <div v-if="item.quantity || item.target_above || item.target_below"
          class="mt-3 pt-3 border-t border-gray-100 dark:border-white/5 flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
          <template v-if="item.quantity && quotes[item.id]">
            <span>{{ formatQty(item.quantity) }} units · <strong class="text-gray-700 dark:text-gray-200">{{ formatMoney(item.quantity * quotes[item.id]!.price, quotes[item.id]!.currency) }}</strong></span>
            <span v-if="item.avg_cost">
              P/L <strong :class="pl(item)! >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                {{ signed(formatMoney(pl(item)!, quotes[item.id]!.currency), pl(item)!) }}
                ({{ signedPct(pl(item)! / (item.quantity * item.avg_cost) * 100) }})
              </strong>
            </span>
          </template>
          <span v-else-if="item.quantity">{{ formatQty(item.quantity) }} units</span>
          <span v-if="item.target_above" :class="alertState(item) === 'above' ? 'text-amber-600 dark:text-amber-400 font-semibold' : ''">
            ▲ Alert ≥ {{ formatPrice(item.target_above) }}
          </span>
          <span v-if="item.target_below" :class="alertState(item) === 'below' ? 'text-amber-600 dark:text-amber-400 font-semibold' : ''">
            ▼ Alert ≤ {{ formatPrice(item.target_below) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="!items.length" class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 py-16 px-6 text-center">
      <div class="w-16 h-16 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center mx-auto mb-4">
        <ArrowTrendingUpIcon class="w-8 h-8 text-violet-500" />
      </div>
      <p class="text-base font-semibold text-gray-800 dark:text-white mb-1">Start monitoring the markets</p>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
        Track stocks, ETFs, indices, forex and crypto with live prices, holdings P/L and price alerts — powered by free market data.
      </p>
      <button @click="openCreate" class="inline-flex items-center gap-2 px-5 py-2.5 gradient-primary text-white text-sm font-semibold rounded-xl shadow-lg hover:opacity-90 transition-all">
        <PlusIcon class="w-4 h-4" /> Add Symbol
      </button>
    </div>
    <p v-else class="text-sm text-center text-gray-500 dark:text-gray-400 py-10">Nothing in this filter.</p>

    <p class="text-[11px] text-gray-400 dark:text-gray-500 text-center mt-6">
      Market data from Yahoo Finance and CoinGecko. Quotes may be delayed and are for monitoring only — not investment advice.
    </p>

    <Teleport to="body">
      <!-- Add / Edit modal -->
      <Transition name="fade">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 modal-backdrop" @click.self="showModal = false">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md border-t sm:border border-gray-200 dark:border-white/10 max-h-[92vh] overflow-y-auto">
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
              <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-white/20" />
            </div>
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-white/10">
              <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ editTarget ? `Edit ${editTarget.ticker}` : 'Add to Watchlist' }}
              </h3>
              <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10 text-gray-500 hover:bg-gray-200 dark:hover:bg-white/20 transition-colors">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>

            <form @submit.prevent="submitForm" class="p-5 space-y-4">
              <template v-if="!editTarget">
                <!-- Asset type -->
                <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-gray-100 dark:bg-white/5">
                  <button v-for="t in assetTypes" :key="t.value" type="button" @click="setAssetType(t.value)"
                    class="py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="form.asset_type === t.value ? 'bg-white dark:bg-[#1A1A2E] text-violet-700 dark:text-violet-300 shadow-sm' : 'text-gray-500 dark:text-gray-400'">
                    {{ t.label }}
                  </button>
                </div>

                <!-- Selected symbol -->
                <div v-if="form.symbol" class="flex items-center gap-3 p-3 rounded-xl bg-violet-50 dark:bg-violet-500/10">
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-violet-800 dark:text-violet-200">{{ form.ticker || form.symbol }}</p>
                    <p class="text-xs text-violet-600/80 dark:text-violet-300/70 truncate">{{ form.name || form.symbol }}</p>
                  </div>
                  <button type="button" @click="clearSelection" class="text-xs font-medium text-violet-600 dark:text-violet-300 hover:underline">Change</button>
                </div>

                <!-- Search -->
                <div v-else>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Search</label>
                  <div class="relative">
                    <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input v-model="searchQuery" type="text" class="input-field pl-9" autofocus
                      :placeholder="form.asset_type === 'crypto' ? 'e.g. bitcoin, solana' : 'e.g. AAPL, VOO, ^GSPC, USDPHP=X'" />
                  </div>
                  <div v-if="searching" class="text-xs text-gray-400 mt-2">Searching…</div>
                  <div v-else-if="searchResults.length" class="mt-2 max-h-56 overflow-y-auto rounded-xl border border-gray-100 dark:border-white/10 divide-y divide-gray-100 dark:divide-white/5">
                    <button v-for="r in searchResults" :key="r.symbol" type="button" @click="selectResult(r)"
                      class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-white/5">
                      <span class="text-sm font-semibold text-gray-900 dark:text-white w-24 truncate shrink-0">{{ r.ticker }}</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400 flex-1 truncate">{{ r.name }}</span>
                      <span class="text-[10px] text-gray-400 shrink-0">{{ [r.kind, r.exchange].filter(Boolean).join(' · ') }}</span>
                    </button>
                  </div>
                  <button v-if="searchQuery.trim() && !searching && symbolPattern.test(searchQuery.trim())" type="button"
                    @click="selectResult({ symbol: searchQuery.trim(), ticker: searchQuery.trim().toUpperCase(), name: '', kind: '' })"
                    class="mt-2 text-xs text-violet-600 dark:text-violet-400 hover:underline">
                    Use “{{ searchQuery.trim() }}” as the {{ form.asset_type === 'crypto' ? 'CoinGecko coin id' : 'symbol' }}
                  </button>
                </div>
                <p v-if="formErrors.symbol" class="text-xs text-red-500">{{ formErrors.symbol }}</p>
              </template>

              <!-- Holdings -->
              <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Holdings <span class="normal-case font-normal">(optional)</span></p>
                <div class="grid grid-cols-2 gap-3">
                  <input v-model.number="form.quantity" type="number" min="0" step="any" class="input-field" placeholder="Quantity" />
                  <input v-model.number="form.avg_cost" type="number" min="0" step="any" class="input-field" placeholder="Avg cost / unit" />
                </div>
              </div>

              <!-- Alerts -->
              <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Price alerts <span class="normal-case font-normal">(optional)</span></p>
                <div class="grid grid-cols-2 gap-3">
                  <input v-model.number="form.target_above" type="number" min="0" step="any" class="input-field" placeholder="Rises to ≥" />
                  <input v-model.number="form.target_below" type="number" min="0" step="any" class="input-field" placeholder="Falls to ≤" />
                </div>
                <p class="text-[11px] text-gray-400 mt-1.5">Prices are in the instrument's quote currency{{ form.asset_type === 'crypto' ? ' (set by MARKET_CRYPTO_CURRENCY)' : '' }}.</p>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Notes <span class="normal-case font-normal">(optional)</span></label>
                <input v-model="form.notes" type="text" class="input-field" placeholder="e.g. Long-term hold, DCA monthly" />
              </div>

              <div class="flex gap-3 pt-1 pb-safe">
                <button type="button" @click="showModal = false"
                  class="flex-1 py-3 border border-gray-200 dark:border-white/20 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition-all">
                  Cancel
                </button>
                <button type="submit" :disabled="saving || (!editTarget && !form.symbol)"
                  class="flex-1 py-3 gradient-primary text-white rounded-xl text-sm font-semibold hover:opacity-90 disabled:opacity-50 transition-all">
                  {{ saving ? 'Saving…' : (editTarget ? 'Update' : 'Add') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>

      <!-- Chart modal -->
      <Transition name="fade">
        <div v-if="chartItem" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 modal-backdrop" @click.self="chartItem = null">
          <div class="bg-white dark:bg-[#1A1A2E] rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-2xl border-t sm:border border-gray-200 dark:border-white/10">
            <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100 dark:border-white/10">
              <div class="min-w-0">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ chartItem.ticker }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                  {{ quotes[chartItem.id]?.name ?? chartItem.name }}
                  <template v-if="quotes[chartItem.id]?.exchange"> · {{ quotes[chartItem.id]!.exchange }}</template>
                </p>
              </div>
              <div class="text-right ml-4" v-if="quotes[chartItem.id]">
                <p class="text-lg font-bold text-gray-900 dark:text-white tabular-nums">{{ formatMoney(quotes[chartItem.id]!.price, quotes[chartItem.id]!.currency) }}</p>
                <p class="text-xs font-medium" :class="rangeChangeClass">{{ rangeChangeLabel }}</p>
              </div>
            </div>
            <div class="px-5 pt-3 flex gap-1">
              <button v-for="r in ranges" :key="r" @click="loadHistory(r)"
                class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors"
                :class="chartRange === r ? 'bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-300' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5'">
                {{ r.toUpperCase() }}
              </button>
            </div>
            <div class="px-3 pb-4 min-h-[280px] flex items-center justify-center">
              <p v-if="chartLoading" class="text-sm text-gray-400">Loading chart…</p>
              <p v-else-if="!chartPoints.length" class="text-sm text-gray-400">No chart data available.</p>
              <apexchart v-else class="w-full" type="area" height="280" :options="chartOptions" :series="chartSeries" />
            </div>
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
            <h3 class="text-base font-semibold text-gray-900 dark:text-white text-center mb-1">Remove {{ deleteTarget.ticker }}?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">Its holdings and alerts will be removed too.</p>
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
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useDebounceFn, useDocumentVisibility } from '@vueuse/core'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useThemeStore } from '@/stores/theme'
import {
  PlusIcon, PencilIcon, TrashIcon, XMarkIcon, ArrowPathIcon, BellIcon, BellAlertIcon,
  MagnifyingGlassIcon, ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline'
import type { WatchlistItem, MarketQuote, MarketSearchResult } from '@/types'

const props = defineProps<{
  items: WatchlistItem[]
  refreshSeconds: number
}>()

const themeStore = useThemeStore()

// ── Formatting ────────────────────────────────────────────────────
function decimalsFor(value: number): number {
  const abs = Math.abs(value)
  if (abs === 0 || abs >= 1) return 2
  if (abs >= 0.01) return 4
  return 8
}

function formatMoney(value: number, currency: string): string {
  const digits = decimalsFor(value)
  try {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency, minimumFractionDigits: 2, maximumFractionDigits: digits }).format(value)
  } catch {
    return `${value.toLocaleString('en-US', { maximumFractionDigits: digits })} ${currency}`
  }
}

function formatPrice(value: number): string {
  return value.toLocaleString('en-US', { maximumFractionDigits: decimalsFor(value) })
}

function formatQty(value: number): string {
  return value.toLocaleString('en-US', { maximumFractionDigits: 8 })
}

function signed(label: string, value: number): string {
  return value > 0 ? `+${label}` : label
}

function signedPct(value: number): string {
  return `${value > 0 ? '+' : ''}${value.toFixed(2)}%`
}

// ── Quotes & polling ──────────────────────────────────────────────
const quotes      = ref<Record<number, MarketQuote | null>>({})
const loading     = ref(false)
const loadedOnce  = ref(false)
const fetchError  = ref('')
const lastUpdated = ref('')

async function loadQuotes() {
  if (!props.items.length || loading.value) return
  loading.value = true
  try {
    const res = await fetch('/markets/quotes', { headers: { Accept: 'application/json' } })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()
    // Keep the last known quote if a provider hiccups on this round
    const next: Record<number, MarketQuote | null> = {}
    for (const item of props.items) next[item.id] = data.quotes?.[item.id] ?? quotes.value[item.id] ?? null
    quotes.value = next
    fetchError.value = ''
    lastUpdated.value = new Date(data.fetched_at ?? Date.now()).toLocaleTimeString()
    checkAlerts(!loadedOnce.value)
    loadedOnce.value = true
  } catch {
    fetchError.value = 'Could not reach market data — retrying'
  } finally {
    loading.value = false
  }
}

const visibility = useDocumentVisibility()
let timer: ReturnType<typeof setInterval> | undefined

onMounted(() => {
  loadQuotes()
  timer = setInterval(() => { if (visibility.value === 'visible') loadQuotes() }, props.refreshSeconds * 1000)
})
onUnmounted(() => clearInterval(timer))
watch(visibility, v => { if (v === 'visible' && loadedOnce.value) loadQuotes() })

// Refetch when the watchlist changes (add/remove)
watch(() => props.items.map(i => i.id).join(','), () => loadQuotes())

// ── Filters ───────────────────────────────────────────────────────
const filters = [
  { value: 'all', label: 'All' },
  { value: 'holdings', label: 'Holdings' },
  { value: 'stock', label: 'Markets' },
  { value: 'crypto', label: 'Crypto' },
] as const
const filter = ref<(typeof filters)[number]['value']>('all')

const filteredItems = computed(() => props.items.filter(i =>
  filter.value === 'all' ? true
    : filter.value === 'holdings' ? !!i.quantity
      : i.asset_type === filter.value,
))

// ── Portfolio math ────────────────────────────────────────────────
function pl(item: WatchlistItem): number | null {
  const q = quotes.value[item.id]
  if (!q || !item.quantity || !item.avg_cost) return null
  return item.quantity * (q.price - item.avg_cost)
}

const portfolio = computed(() => {
  const byCurrency: Record<string, { currency: string, value: number, dayChange: number, cost: number, pl: number, hasCost: boolean }> = {}
  for (const item of props.items) {
    const q = quotes.value[item.id]
    if (!q || !item.quantity) continue
    const row = byCurrency[q.currency] ??= { currency: q.currency, value: 0, dayChange: 0, cost: 0, pl: 0, hasCost: false }
    row.value += item.quantity * q.price
    row.dayChange += item.quantity * (q.change ?? 0)
    if (item.avg_cost) {
      row.hasCost = true
      row.cost += item.quantity * item.avg_cost
      row.pl += item.quantity * (q.price - item.avg_cost)
    }
  }
  return Object.values(byCurrency).sort((a, b) => b.value - a.value)
})

function trendClass(item: WatchlistItem, kind: 'text' | 'stroke'): string {
  const pct = quotes.value[item.id]?.change_pct ?? 0
  if (kind === 'stroke') return pct >= 0 ? 'stroke-emerald-500' : 'stroke-red-500'
  return pct >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'
}

function sparkPoints(values: number[]): string {
  const min = Math.min(...values)
  const max = Math.max(...values)
  const span = max - min || 1
  const step = values.length > 1 ? 100 / (values.length - 1) : 0
  return values.map((v, i) => `${(i * step).toFixed(2)},${(30 - ((v - min) / span) * 28).toFixed(2)}`).join(' ')
}

// ── Alerts ────────────────────────────────────────────────────────
const notificationsSupported = typeof window !== 'undefined' && 'Notification' in window
const notifyPermission = ref<NotificationPermission>(notificationsSupported ? Notification.permission : 'denied')
let notifiedKeys = new Set<string>()

async function enableNotifications() {
  if (!notificationsSupported) return
  notifyPermission.value = await Notification.requestPermission()
}

function alertState(item: WatchlistItem): 'above' | 'below' | null {
  const q = quotes.value[item.id]
  if (!q) return null
  if (item.target_above && q.price >= item.target_above) return 'above'
  if (item.target_below && q.price <= item.target_below) return 'below'
  return null
}

const triggered = computed(() => props.items.flatMap(item => {
  const state = alertState(item)
  const q = quotes.value[item.id]
  if (!state || !q) return []
  const target = state === 'above' ? item.target_above! : item.target_below!
  return [{
    key: `${item.id}:${state}:${target}`,
    item,
    message: `${item.ticker} is ${formatMoney(q.price, q.currency)} — ${state === 'above' ? 'at or above' : 'at or below'} your ${formatPrice(target)} alert.`,
  }]
}))

// Notify only for alerts that newly crossed since the last refresh (not ones already active on page load)
function checkAlerts(initial: boolean) {
  const current = new Set(triggered.value.map(t => t.key))
  if (!initial && notifyPermission.value === 'granted') {
    for (const t of triggered.value) {
      if (!notifiedKeys.has(t.key)) new Notification(`Price alert: ${t.item.ticker}`, { body: t.message, tag: t.key })
    }
  }
  notifiedKeys = current
}

// ── Add / Edit ────────────────────────────────────────────────────
const assetTypes = [
  { value: 'stock', label: 'Stocks · ETFs · FX' },
  { value: 'crypto', label: 'Crypto' },
] as const

const symbolPattern = /^[A-Za-z0-9.\-^=_]+$/

const showModal  = ref(false)
const editTarget = ref<WatchlistItem | null>(null)
const saving     = ref(false)
const formErrors = ref<Record<string, string>>({})

const defaultForm = () => ({
  asset_type: 'stock' as WatchlistItem['asset_type'],
  symbol: '',
  ticker: '',
  name: '',
  quantity: null as number | null | '',
  avg_cost: null as number | null | '',
  target_above: null as number | null | '',
  target_below: null as number | null | '',
  notes: '',
})
const form = ref(defaultForm())

const searchQuery   = ref('')
const searchResults = ref<MarketSearchResult[]>([])
const searching     = ref(false)

const runSearch = useDebounceFn(async () => {
  const q = searchQuery.value.trim()
  if (!q) { searchResults.value = []; searching.value = false; return }
  try {
    const params = new URLSearchParams({ q, type: form.value.asset_type })
    const res = await fetch(`/markets/search?${params}`, { headers: { Accept: 'application/json' } })
    const data = res.ok ? await res.json() : { results: [] }
    if (q === searchQuery.value.trim()) searchResults.value = data.results ?? []
  } catch {
    searchResults.value = []
  } finally {
    searching.value = false
  }
}, 350)

watch(searchQuery, q => {
  searching.value = !!q.trim()
  runSearch()
})

function setAssetType(type: WatchlistItem['asset_type']) {
  if (form.value.asset_type === type) return
  form.value.asset_type = type
  clearSelection()
  if (searchQuery.value.trim()) { searching.value = true; runSearch() }
}

function selectResult(r: MarketSearchResult) {
  form.value.symbol = r.symbol
  form.value.ticker = r.ticker
  form.value.name = r.name
  formErrors.value = {}
}

function clearSelection() {
  form.value.symbol = ''
  form.value.ticker = ''
  form.value.name = ''
  searchResults.value = []
}

function openCreate() {
  editTarget.value = null
  form.value = defaultForm()
  searchQuery.value = ''
  searchResults.value = []
  formErrors.value = {}
  showModal.value = true
}

function openEdit(item: WatchlistItem) {
  editTarget.value = item
  form.value = {
    asset_type: item.asset_type,
    symbol: item.symbol,
    ticker: item.ticker,
    name: item.name ?? '',
    quantity: item.quantity ?? null,
    avg_cost: item.avg_cost ?? null,
    target_above: item.target_above ?? null,
    target_below: item.target_below ?? null,
    notes: item.notes ?? '',
  }
  formErrors.value = {}
  showModal.value = true
}

function payload() {
  // Cleared number inputs come through as '' — send null instead
  const num = (v: number | null | '') => (v === '' || v === null ? null : v)
  return {
    ...form.value,
    quantity: num(form.value.quantity),
    avg_cost: num(form.value.avg_cost),
    target_above: num(form.value.target_above),
    target_below: num(form.value.target_below),
  }
}

function submitForm() {
  saving.value = true
  const options = {
    preserveScroll: true,
    onSuccess: () => { showModal.value = false },
    onError: (errors: Record<string, string>) => { formErrors.value = errors },
    onFinish: () => { saving.value = false },
  }
  if (editTarget.value) router.put(`/markets/${editTarget.value.id}`, payload(), options)
  else router.post('/markets', payload(), options)
}

// ── Delete ────────────────────────────────────────────────────────
const deleteTarget = ref<WatchlistItem | null>(null)
const deleting     = ref(false)

function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  router.delete(`/markets/${deleteTarget.value.id}`, {
    preserveScroll: true,
    onSuccess: () => { deleteTarget.value = null },
    onFinish: () => { deleting.value = false },
  })
}

// ── Chart ─────────────────────────────────────────────────────────
const ranges = ['1d', '5d', '1mo', '6mo', '1y']
const chartItem    = ref<WatchlistItem | null>(null)
const chartRange   = ref('1d')
const chartPoints  = ref<[number, number][]>([])
const chartLoading = ref(false)

function openChart(item: WatchlistItem) {
  chartItem.value = item
  loadHistory('1d')
}

async function loadHistory(range: string) {
  if (!chartItem.value) return
  const id = chartItem.value.id
  chartRange.value = range
  chartLoading.value = true
  try {
    const res = await fetch(`/markets/${id}/history?range=${range}`, { headers: { Accept: 'application/json' } })
    const data = res.ok ? await res.json() : { points: [] }
    if (chartItem.value?.id === id && chartRange.value === range) chartPoints.value = data.points ?? []
  } catch {
    chartPoints.value = []
  } finally {
    chartLoading.value = false
  }
}

const rangeChange = computed(() => {
  const pts = chartPoints.value
  if (pts.length < 2) return null
  const first = pts[0][1]
  const last = pts[pts.length - 1][1]
  return first ? (last - first) / first * 100 : null
})
const rangeChangeLabel = computed(() => rangeChange.value === null ? '' : `${signedPct(rangeChange.value)} · ${chartRange.value.toUpperCase()}`)
const rangeChangeClass = computed(() => (rangeChange.value ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500')

const chartSeries = computed(() => [{ name: chartItem.value?.ticker ?? '', data: chartPoints.value }])
const chartOptions = computed(() => {
  const up = (rangeChange.value ?? 0) >= 0
  const color = up ? '#10B981' : '#EF4444'
  const currency = chartItem.value ? quotes.value[chartItem.value.id]?.currency ?? 'USD' : 'USD'
  const intraday = chartRange.value === '1d' || chartRange.value === '5d'
  return {
    chart: { toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent', animations: { enabled: false } },
    theme: { mode: themeStore.isDark ? 'dark' : 'light' },
    colors: [color],
    stroke: { curve: 'straight', width: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02 } },
    dataLabels: { enabled: false },
    grid: { borderColor: themeStore.isDark ? 'rgba(255,255,255,0.06)' : '#F3F4F6' },
    xaxis: { type: 'datetime', labels: { datetimeUTC: false } },
    yaxis: { labels: { formatter: (v: number) => formatPrice(v) } },
    tooltip: {
      theme: themeStore.isDark ? 'dark' : 'light',
      x: { format: intraday ? 'MMM dd, HH:mm' : 'MMM dd, yyyy' },
      y: { formatter: (v: number) => formatMoney(v, currency) },
    },
  }
})
</script>
