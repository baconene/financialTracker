<template>
  <AppLayout title="Dashboard">
    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard
        label="Total Balance"
        :value="formatPHP(stats.totalBalance)"
        :icon="BanknotesIcon"
        gradient="gradient-primary"
        change="+₱5,200"
        positive
      />
      <StatCard
        label="Monthly Income"
        :value="formatPHP(stats.monthlyIncome)"
        :icon="ArrowTrendingUpIcon"
        gradient="gradient-success"
        change="+12%"
        positive
      />
      <StatCard
        label="Monthly Expenses"
        :value="formatPHP(stats.monthlyExpenses)"
        :icon="ArrowTrendingDownIcon"
        gradient="gradient-danger"
        change="-3%"
        :positive="false"
      />
      <StatCard
        label="Total Savings"
        :value="formatPHP(stats.totalSavings)"
        :icon="ArchiveBoxIcon"
        gradient="gradient-warning"
        change="+₱3,000"
        positive
      />
    </div>

    <!-- Financial Health Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
      <!-- Health Score -->
      <div class="col-span-2 sm:col-span-1 bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4 flex items-center gap-3">
        <div class="relative w-14 h-14 shrink-0">
          <svg class="w-14 h-14 -rotate-90" viewBox="0 0 56 56">
            <circle cx="28" cy="28" r="22" fill="none" stroke="currentColor" stroke-width="5" class="text-gray-100 dark:text-white/10" />
            <circle cx="28" cy="28" r="22" fill="none" :stroke="healthColor" stroke-width="5" stroke-linecap="round"
              :stroke-dasharray="`${stats.healthScore * 1.382} 138.2`" />
          </svg>
          <span class="absolute inset-0 flex items-center justify-center text-xs font-bold" :style="{ color: healthColor }">{{ stats.healthScore }}</span>
        </div>
        <div>
          <p class="text-xs text-gray-500 dark:text-gray-400">Health Score</p>
          <p class="text-sm font-bold" :style="{ color: healthColor }">{{ healthLabel }}</p>
          <a href="/reports" class="text-[10px] text-violet-500 hover:underline">See insights →</a>
        </div>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Disposable Income</p>
        <p class="text-base font-bold" :class="stats.disposableIncome >= 0 ? 'text-violet-600 dark:text-violet-400' : 'text-red-500'">{{ formatPHP(stats.disposableIncome) }}</p>
        <p class="text-[10px] text-gray-400 mt-0.5">After expenses</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Outstanding Loans</p>
        <p class="text-base font-bold text-red-600 dark:text-red-400">{{ formatPHP(stats.outstandingLoans) }}</p>
        <p class="text-[10px] text-gray-400 mt-0.5">{{ stats.debtToIncome.toFixed(1) }}% of income</p>
      </div>
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Emergency Fund</p>
        <p class="text-base font-bold text-blue-600 dark:text-blue-400">{{ stats.emergencyFundMonths }}mo</p>
        <p class="text-[10px] text-gray-400 mt-0.5">{{ stats.savingsRate.toFixed(1) }}% savings rate</p>
      </div>
    </div>

    <!-- Quick Insights -->
    <div v-if="quickInsights.length" class="flex flex-col sm:flex-row gap-2 mb-6">
      <div v-for="(insight, i) in quickInsights" :key="i"
        :class="[
          'flex-1 flex items-start gap-2 px-3 py-2.5 rounded-xl text-xs',
          insight.type === 'success' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
          : insight.type === 'warning' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300'
          : 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300'
        ]">
        <span class="shrink-0 text-sm">{{ insight.icon }}</span>
        <p class="leading-snug">{{ insight.message }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
      <!-- Cash Flow Chart -->
      <div class="xl:col-span-2 bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <div class="flex items-start justify-between mb-3 gap-3 flex-wrap">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cash Flow</h3>
              <!-- Help tooltip -->
              <div class="relative group">
                <button class="w-5 h-5 rounded-full bg-gray-100 dark:bg-white/10 text-gray-400 flex items-center justify-center text-xs font-bold hover:bg-gray-200 dark:hover:bg-white/20 transition-colors">?</button>
                <div class="absolute left-0 bottom-full mb-2 w-72 bg-gray-900 text-white text-xs rounded-xl p-3 shadow-2xl z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity leading-relaxed">
                  <p class="font-semibold mb-1.5">About this chart</p>
                  <p class="text-gray-300 mb-1">Solid lines show <strong>actual recorded</strong> income, expenses, and net savings from your transactions.</p>
                  <p class="text-gray-300 mb-1">Dashed lines are a <strong>6-month forecast</strong>: income from your income sources (or 3-month avg), expenses from scheduled bills and loan payment dates.</p>
                  <p class="text-gray-300"><strong>Savings</strong> = Income − Expenses.</p>
                  <p class="text-gray-400 mt-1.5 text-[10px]">Tap any data point to see the month's full breakdown.</p>
                </div>
              </div>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Actual history + 6-month projection</p>
          </div>
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs justify-end shrink-0">
            <div class="flex items-center gap-1.5">
              <div class="w-3 h-3 rounded-full bg-emerald-500" />
              <span class="text-gray-500 dark:text-gray-400">Income</span>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="w-3 h-3 rounded-full bg-red-500" />
              <span class="text-gray-500 dark:text-gray-400">Expenses</span>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="w-3 h-3 rounded-full bg-violet-600" />
              <span class="text-gray-500 dark:text-gray-400">Savings</span>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="w-5 h-0.5 border-t-2 border-dashed border-gray-400 dark:border-gray-500" />
              <span class="text-gray-400 dark:text-gray-500">Projected</span>
            </div>
          </div>
        </div>

        <!-- Date range filters -->
        <div class="flex items-center gap-2 mb-3 flex-wrap">
          <div class="flex items-center gap-1.5">
            <label class="text-xs text-gray-500 dark:text-gray-400 shrink-0">From</label>
            <input
              type="month"
              v-model="cfFrom"
              :max="cfTo"
              @change="reloadCashFlow"
              class="text-xs px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-white/20 bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
            />
          </div>
          <div class="flex items-center gap-1.5">
            <label class="text-xs text-gray-500 dark:text-gray-400 shrink-0">To</label>
            <input
              type="month"
              v-model="cfTo"
              :min="cfFrom"
              :max="currentMonth"
              @change="reloadCashFlow"
              class="text-xs px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-white/20 bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
            />
          </div>
          <button
            @click="resetCashFlowRange"
            class="text-xs px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-white/20 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
          >Reset</button>
        </div>

        <!-- Projection info strip -->
        <div v-if="cashFlowProjection.length" class="flex items-center gap-2 mb-3 px-3 py-2 rounded-xl bg-violet-50 dark:bg-violet-500/10 text-xs text-violet-700 dark:text-violet-300">
          <span class="shrink-0">📊</span>
          <span class="flex-1">
            Income <strong>{{ formatPHP(incomeProjectionMonthly) }}/mo</strong>
            <span v-if="incomeProjectionBasis === 'sources'"> from income sources</span>
            <span v-else> (3-month avg — <a href="/income-sources" class="underline hover:text-violet-500">add income sources</a>)</span>
            · Expenses from bills &amp; loan schedule
            <span class="text-violet-500">
              <span class="relative group inline-block">
                <button class="w-3.5 h-3.5 rounded-full bg-violet-200 dark:bg-violet-500/40 text-violet-700 dark:text-violet-300 inline-flex items-center justify-center text-[9px] font-bold align-middle">?</button>
                <span class="absolute left-1/2 -translate-x-1/2 bottom-full mb-1.5 w-64 bg-gray-900 text-white text-[11px] rounded-xl p-2.5 shadow-xl z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity leading-relaxed normal-case font-normal">
                  Projected expenses are computed from your bills' <strong>next due dates + frequency</strong> (how many times each bill falls in that month) and your loans' <strong>next payment date + payment frequency</strong>. Loans drop off once their balance reaches zero.
                </span>
              </span>
            </span>
          </span>
        </div>
        <apexchart
          type="area"
          height="230"
          :options="cashFlowOptions"
          :series="cashFlowSeries"
          @dataPointSelection="onDataPointSelect"
          @markerClick="onDataPointSelect"
        />

        <!-- Month breakdown panel (shown on chart point click) -->
        <div v-if="selectedMonthData" class="mt-4 border-t border-gray-100 dark:border-white/10 pt-4">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ selectedMonthData.month }}</h4>
              <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                :class="selectedMonthData.isActual
                  ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300'
                  : 'bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-300'">
                {{ selectedMonthData.isActual ? 'Actual' : 'Projected' }}
              </span>
            </div>
            <button @click="selectedDataIndex = null"
              class="w-6 h-6 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/10 transition-all">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>

          <div class="grid grid-cols-3 gap-2 sm:gap-3">
            <!-- Income tile -->
            <div class="bg-emerald-50 dark:bg-emerald-500/10 rounded-xl p-3">
              <div class="flex items-center gap-1 mb-1.5">
                <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Income</span>
                <div class="relative group shrink-0">
                  <button class="w-3.5 h-3.5 rounded-full bg-emerald-200 dark:bg-emerald-500/40 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-[9px] font-bold">?</button>
                  <div class="absolute bottom-full left-0 mb-1.5 w-52 bg-gray-900 text-white text-[11px] rounded-xl p-2.5 shadow-xl z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity leading-relaxed">
                    <template v-if="selectedMonthData.isActual">Sum of all income transactions recorded for this month.</template>
                    <template v-else-if="incomeProjectionBasis === 'sources'">Based on your active income sources — total monthly equivalent of all defined income streams.</template>
                    <template v-else>Estimated from a 3-month rolling average. <a href="/income-sources" class="underline pointer-events-auto">Add income sources</a> for better accuracy.</template>
                  </div>
                </div>
              </div>
              <p class="text-sm sm:text-base font-bold text-emerald-800 dark:text-emerald-200 truncate">{{ formatPHP(selectedMonthData.income) }}</p>
              <p v-if="!selectedMonthData.isActual" class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">
                {{ incomeProjectionBasis === 'sources' ? 'From income sources' : '3-month avg' }}
              </p>
            </div>

            <!-- Expenses tile -->
            <div class="bg-red-50 dark:bg-red-500/10 rounded-xl p-3">
              <div class="flex items-center gap-1 mb-1.5">
                <span class="text-xs font-medium text-red-700 dark:text-red-300">Expenses</span>
                <div class="relative group shrink-0">
                  <button class="w-3.5 h-3.5 rounded-full bg-red-200 dark:bg-red-500/40 text-red-700 dark:text-red-300 flex items-center justify-center text-[9px] font-bold">?</button>
                  <div class="absolute bottom-full left-0 mb-1.5 w-56 bg-gray-900 text-white text-[11px] rounded-xl p-2.5 shadow-xl z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity leading-relaxed">
                    <template v-if="selectedMonthData.isActual">Sum of all expense transactions recorded for this month.</template>
                    <template v-else>Bills scheduled by their <strong>next due date + frequency</strong> (counts each occurrence in this month), plus loan payments from the <strong>next payment date + payment frequency</strong>. Loans stop once their balance reaches zero.</template>
                  </div>
                </div>
              </div>
              <p class="text-sm sm:text-base font-bold text-red-800 dark:text-red-200 truncate">{{ formatPHP(selectedMonthData.expenses) }}</p>
              <p v-if="!selectedMonthData.isActual" class="text-[10px] text-red-600 dark:text-red-400 mt-0.5">Bills + loan schedule</p>
            </div>

            <!-- Net Savings tile -->
            <div class="rounded-xl p-3"
              :class="selectedMonthData.savings >= 0
                ? 'bg-violet-50 dark:bg-violet-500/10'
                : 'bg-orange-50 dark:bg-orange-500/10'">
              <div class="flex items-center gap-1 mb-1.5">
                <span class="text-xs font-medium"
                  :class="selectedMonthData.savings >= 0
                    ? 'text-violet-700 dark:text-violet-300'
                    : 'text-orange-700 dark:text-orange-300'">
                  Net Savings
                </span>
                <div class="relative group shrink-0">
                  <button class="w-3.5 h-3.5 rounded-full flex items-center justify-center text-[9px] font-bold"
                    :class="selectedMonthData.savings >= 0
                      ? 'bg-violet-200 dark:bg-violet-500/40 text-violet-700 dark:text-violet-300'
                      : 'bg-orange-200 dark:bg-orange-500/40 text-orange-700 dark:text-orange-300'">?</button>
                  <div class="absolute bottom-full right-0 mb-1.5 w-52 bg-gray-900 text-white text-[11px] rounded-xl p-2.5 shadow-xl z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity leading-relaxed">
                    <strong>Possible savings = Income − Expenses.</strong> Positive means money left over after all obligations; negative means expenses exceed income that month.
                  </div>
                </div>
              </div>
              <p class="text-sm sm:text-base font-bold truncate"
                :class="selectedMonthData.savings >= 0
                  ? 'text-violet-800 dark:text-violet-200'
                  : 'text-orange-800 dark:text-orange-200'">
                {{ selectedMonthData.savings >= 0 ? '+' : '' }}{{ formatPHP(selectedMonthData.savings) }}
              </p>
              <p class="text-[10px] mt-0.5"
                :class="selectedMonthData.savings >= 0
                  ? 'text-violet-600 dark:text-violet-400'
                  : 'text-orange-600 dark:text-orange-400'">
                Income − Expenses
              </p>
            </div>
          </div>

          <!-- Footer: link or note -->
          <div class="mt-3">
            <a v-if="selectedMonthData.isActual && selectedMonthData.transactionsUrl"
              :href="selectedMonthData.transactionsUrl"
              class="inline-flex items-center gap-1 text-xs text-violet-600 dark:text-violet-400 hover:underline">
              View transactions for {{ selectedMonthData.month }}
              <ArrowRightIcon class="w-3 h-3" />
            </a>
            <p v-else class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
              <span>📅</span>
              Forecast based on your bill schedules and loan payment calendar.
            </p>
          </div>
        </div>
      </div>

      <!-- Accounts Summary -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Accounts</h3>
        <div class="space-y-3">
          <div
            v-for="account in accounts"
            :key="account.id"
            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors cursor-pointer"
            @click="router.visit(`/accounts/${account.id}`)"
          >
            <div class="w-10 h-10 rounded-xl flex items-center justify-center overflow-hidden shrink-0"
              :style="account.icon_url ? { background: `linear-gradient(135deg, ${account.color}ee, ${account.color}88)` } : { backgroundColor: account.color + '20' }">
              <img v-if="account.icon_url" :src="account.icon_url" class="w-full h-full object-contain p-1" :alt="account.name" />
              <CreditCardIcon v-else class="w-5 h-5" :style="{ color: account.color }" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ account.name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ account.type }}</p>
            </div>
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPHP(account.balance) }}</p>
          </div>
        </div>
        <Link href="/accounts" class="mt-4 flex items-center justify-center gap-1 text-sm text-violet-600 dark:text-violet-400 hover:underline">
          View all accounts <ArrowRightIcon class="w-4 h-4" />
        </Link>
      </div>
    </div>

    <!-- Cash Flow Projection Breakdown Table -->
    <div v-if="cashFlowBreakdown.months.length" class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6 mb-6">
      <div class="flex items-center gap-2 mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Projected Cash Flow Breakdown</h3>
        <div class="relative group">
          <button class="w-5 h-5 rounded-full bg-gray-100 dark:bg-white/10 text-gray-400 flex items-center justify-center text-xs font-bold hover:bg-gray-200 dark:hover:bg-white/20 transition-colors">?</button>
          <div class="absolute left-0 bottom-full mb-2 w-80 bg-gray-900 text-white text-xs rounded-xl p-3 shadow-2xl z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity leading-relaxed">
            <p class="font-semibold mb-1.5">How this table works</p>
            <p class="text-gray-300 mb-1">Each row shows a specific income source, bill, or loan and how much it contributes each of the next 6 projected months.</p>
            <p class="text-gray-300">Bills are placed in months when their schedule falls due. Loan rows drop to <strong>—</strong> once the balance is paid off.</p>
          </div>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-sm border-separate border-spacing-0">
          <thead>
            <tr class="border-b border-gray-100 dark:border-white/10">
              <th class="sticky left-0 z-10 bg-white dark:bg-[#1A1A2E] text-left py-2 pr-4 pl-0 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-44 min-w-[176px]">Item</th>
              <th v-for="m in cashFlowBreakdown.months" :key="m" class="text-right py-2 px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase min-w-[100px] whitespace-nowrap">{{ m }}</th>
            </tr>
          </thead>
          <tbody>

            <!-- ── Income ── -->
            <tr>
              <td :colspan="cashFlowBreakdown.months.length + 1" class="pt-4 pb-1.5 pl-0">
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Income</span>
              </td>
            </tr>
            <tr v-for="(src, si) in cashFlowBreakdown.income_sources" :key="'inc-' + si" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
              <td class="sticky left-0 z-10 bg-white dark:bg-[#1A1A2E] py-2 pr-4 pl-0 group-hover:bg-gray-50">
                <div class="flex items-center gap-2">
                  <div class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: src.color || '#10B981' }" />
                  <div>
                    <p class="text-xs font-medium text-gray-900 dark:text-white truncate max-w-[130px]">{{ src.name }}</p>
                    <p class="text-[10px] text-gray-400">{{ freqLabel(src.frequency) }}</p>
                  </div>
                </div>
              </td>
              <td v-for="(amt, mi) in src.months" :key="mi" class="text-right py-2 px-3 text-xs text-gray-700 dark:text-gray-300">
                <span v-if="amt > 0">{{ formatPHP(amt) }}</span>
                <span v-else class="text-gray-300 dark:text-gray-600">—</span>
              </td>
            </tr>
            <tr>
              <td class="sticky left-0 z-10 bg-emerald-50 dark:bg-emerald-500/10 py-2 pr-4 pl-2 rounded-l-lg">
                <p class="text-xs font-bold text-emerald-700 dark:text-emerald-300">Total Income</p>
              </td>
              <td v-for="(amt, mi) in cashFlowBreakdown.total_income" :key="'ti-' + mi"
                class="text-right py-2 px-3 text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-500/10 last:rounded-r-lg">
                {{ formatPHP(amt) }}
              </td>
            </tr>

            <!-- ── Bills ── -->
            <tr>
              <td :colspan="cashFlowBreakdown.months.length + 1" class="pt-5 pb-1.5 pl-0">
                <span class="text-xs font-bold text-red-500 dark:text-red-400 uppercase tracking-wide">Recurring Bills</span>
              </td>
            </tr>
            <template v-if="cashFlowBreakdown.bills.length">
              <tr v-for="(bill, bi) in cashFlowBreakdown.bills" :key="'bill-' + bi" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <td class="sticky left-0 z-10 bg-white dark:bg-[#1A1A2E] py-2 pr-4 pl-0">
                  <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: bill.color || '#EF4444' }" />
                    <div>
                      <p class="text-xs font-medium text-gray-900 dark:text-white truncate max-w-[130px]">{{ bill.name }}</p>
                      <p class="text-[10px] text-gray-400">{{ freqLabel(bill.frequency) }}</p>
                    </div>
                  </div>
                </td>
                <td v-for="(amt, mi) in bill.months" :key="mi" class="text-right py-2 px-3 text-xs text-gray-700 dark:text-gray-300">
                  <span v-if="amt > 0">{{ formatPHP(amt) }}</span>
                  <span v-else class="text-gray-300 dark:text-gray-600">—</span>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td :colspan="cashFlowBreakdown.months.length + 1" class="py-2 pl-0 text-xs text-gray-400 italic">No active bills</td>
            </tr>
            <tr>
              <td class="sticky left-0 z-10 bg-red-50 dark:bg-red-500/10 py-2 pr-4 pl-2 rounded-l-lg">
                <p class="text-xs font-bold text-red-700 dark:text-red-300">Total Bills</p>
              </td>
              <td v-for="(amt, mi) in cashFlowBreakdown.total_bills" :key="'tb-' + mi"
                class="text-right py-2 px-3 text-xs font-bold text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-500/10 last:rounded-r-lg">
                <span v-if="amt > 0">{{ formatPHP(amt) }}</span>
                <span v-else class="text-gray-300 dark:text-gray-600">—</span>
              </td>
            </tr>

            <!-- ── Loans ── -->
            <tr>
              <td :colspan="cashFlowBreakdown.months.length + 1" class="pt-5 pb-1.5 pl-0">
                <span class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wide">Loan Payments</span>
              </td>
            </tr>
            <template v-if="cashFlowBreakdown.loans.length">
              <tr v-for="(loan, li) in cashFlowBreakdown.loans" :key="'loan-' + li" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <td class="sticky left-0 z-10 bg-white dark:bg-[#1A1A2E] py-2 pr-4 pl-0">
                  <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full shrink-0 bg-amber-500" />
                    <div>
                      <p class="text-xs font-medium text-gray-900 dark:text-white truncate max-w-[130px]">{{ loan.name }}</p>
                      <p class="text-[10px] text-gray-400">{{ loan.lender ? loan.lender + ' · ' : '' }}{{ freqLabel(loan.payment_frequency) }}</p>
                    </div>
                  </div>
                </td>
                <td v-for="(amt, mi) in loan.months" :key="mi" class="text-right py-2 px-3 text-xs text-gray-700 dark:text-gray-300">
                  <span v-if="amt > 0">{{ formatPHP(amt) }}</span>
                  <span v-else class="text-gray-300 dark:text-gray-600">—</span>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td :colspan="cashFlowBreakdown.months.length + 1" class="py-2 pl-0 text-xs text-gray-400 italic">No active loans</td>
            </tr>
            <tr>
              <td class="sticky left-0 z-10 bg-amber-50 dark:bg-amber-500/10 py-2 pr-4 pl-2 rounded-l-lg">
                <p class="text-xs font-bold text-amber-700 dark:text-amber-300">Total Loans</p>
              </td>
              <td v-for="(amt, mi) in cashFlowBreakdown.total_loans" :key="'tl-' + mi"
                class="text-right py-2 px-3 text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-500/10 last:rounded-r-lg">
                <span v-if="amt > 0">{{ formatPHP(amt) }}</span>
                <span v-else class="text-gray-300 dark:text-gray-600">—</span>
              </td>
            </tr>

            <!-- ── Totals ── -->
            <tr class="border-t border-gray-200 dark:border-white/10">
              <td class="sticky left-0 z-10 bg-gray-100 dark:bg-white/10 py-2.5 pr-4 pl-2 rounded-l-lg">
                <p class="text-xs font-bold text-gray-700 dark:text-gray-200">Total Expenses</p>
                <p class="text-[10px] text-gray-500">Bills + Loans</p>
              </td>
              <td v-for="(amt, mi) in cashFlowBreakdown.total_expenses" :key="'te-' + mi"
                class="text-right py-2.5 px-3 text-xs font-bold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-white/10 last:rounded-r-lg">
                {{ formatPHP(amt) }}
              </td>
            </tr>
            <tr>
              <td class="sticky left-0 z-10 py-3 pr-4 pl-2 rounded-l-xl"
                :class="cashFlowBreakdown.net_savings.some(v => v >= 0) ? 'bg-violet-50 dark:bg-violet-500/10' : 'bg-orange-50 dark:bg-orange-500/10'">
                <p class="text-xs font-bold text-violet-700 dark:text-violet-300">Net Savings</p>
                <p class="text-[10px] text-violet-500 dark:text-violet-400">Income − Expenses</p>
              </td>
              <td v-for="(amt, mi) in cashFlowBreakdown.net_savings" :key="'ns-' + mi"
                class="text-right py-3 px-3 text-xs font-bold last:rounded-r-xl"
                :class="amt >= 0
                  ? 'text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-500/10'
                  : 'text-orange-700 dark:text-orange-300 bg-orange-50 dark:bg-orange-500/10'">
                {{ amt >= 0 ? '+' : '' }}{{ formatPHP(amt) }}
              </td>
            </tr>

          </tbody>
        </table>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
      <!-- Savings Goals -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Savings Goals</h3>
          <Link href="/savings-goals" class="text-xs text-violet-600 dark:text-violet-400 hover:underline">View all</Link>
        </div>
        <div class="space-y-4">
          <div v-for="goal in savingsGoals" :key="goal.id" class="group">
            <div class="flex items-center gap-3 mb-2">
              <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :style="{ backgroundColor: goal.color + '20' }">
                <span class="text-base">{{ goalEmoji(goal) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ goal.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatPHP(goal.current_amount) }} / {{ formatPHP(goal.target_amount) }}</p>
              </div>
              <span class="text-sm font-semibold" :style="{ color: goal.color }">{{ goal.progress_percentage.toFixed(0) }}%</span>
            </div>
            <div class="h-2 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-700"
                :style="{ width: goal.progress_percentage + '%', backgroundColor: goal.color }"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Bills -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Upcoming Bills</h3>
          <Link href="/bills" class="text-xs text-violet-600 dark:text-violet-400 hover:underline">View all</Link>
        </div>
        <div class="space-y-3">
          <div v-for="bill in upcomingBills" :key="bill.id" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/5">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :style="{ backgroundColor: bill.color + '20' }">
              <span class="text-sm">📋</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ bill.name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Due {{ formatDate(bill.next_due_date) }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPHP(bill.amount) }}</p>
              <span class="text-xs px-2 py-0.5 rounded-full" :class="billStatusClass(bill.status)">
                {{ billStatusLabel(bill.status) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Loans -->
      <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Active Loans</h3>
          <Link href="/loans" class="text-xs text-violet-600 dark:text-violet-400 hover:underline">View all</Link>
        </div>
        <div class="space-y-4">
          <div v-for="loan in loans" :key="loan.id" class="p-4 rounded-xl bg-gray-50 dark:bg-white/5">
            <div class="flex items-start justify-between mb-3">
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ loan.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ loan.lender }}</p>
              </div>
              <span class="text-xs px-2 py-1 bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded-full">Active</span>
            </div>
            <div class="space-y-1.5">
              <div class="flex justify-between text-xs">
                <span class="text-gray-500 dark:text-gray-400">Remaining</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ formatPHP(loan.remaining_balance) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-gray-500 dark:text-gray-400">Monthly Payment</span>
                <span class="font-medium text-violet-600 dark:text-violet-400">{{ formatPHP(loan.monthly_payment) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-gray-500 dark:text-gray-400">Next Payment</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ loan.next_payment_date ? formatDate(loan.next_payment_date) : 'N/A' }}</span>
              </div>
              <!-- Progress bar -->
              <div class="h-1.5 bg-gray-200 dark:bg-white/10 rounded-full mt-2">
                <div class="h-full gradient-primary rounded-full" :style="{ width: loanProgress(loan) + '%' }" />
              </div>
              <p class="text-xs text-gray-400 text-right">{{ loanProgress(loan).toFixed(0) }}% paid</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-white/10">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Transactions</h3>
        <Link href="/transactions" class="text-sm text-violet-600 dark:text-violet-400 hover:underline">View all</Link>
      </div>

      <!-- Mobile: card list -->
      <div class="sm:hidden divide-y divide-gray-50 dark:divide-white/5">
        <div v-for="txn in recentTransactions" :key="txn.id" class="flex items-center gap-3 p-4">
          <div class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center"
            :style="{ backgroundColor: (txn.category?.color || '#6B7280') + '20' }">
            <span class="text-sm">{{ txn.type === 'income' ? '💵' : '💸' }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ txn.description }}</p>
            <div class="flex items-center gap-2 mt-0.5">
              <span v-if="txn.category" class="text-xs px-1.5 py-0.5 rounded-full" :style="{ backgroundColor: txn.category.color + '20', color: txn.category.color }">
                {{ txn.category.name }}
              </span>
              <span class="text-xs text-gray-400">{{ formatDate(txn.transaction_date) }}</span>
            </div>
          </div>
          <span class="text-sm font-semibold shrink-0" :class="txn.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
            {{ txn.type === 'income' ? '+' : '-' }}{{ formatPHP(txn.amount) }}
          </span>
        </div>
      </div>

      <!-- Desktop: table -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="text-left border-b border-gray-100 dark:border-white/10">
              <th class="px-5 pb-3 pt-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
              <th class="pb-3 pt-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
              <th class="pb-3 pt-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase hidden md:table-cell">Account</th>
              <th class="pb-3 pt-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
              <th class="px-5 pb-3 pt-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase text-right">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-white/5">
            <tr v-for="txn in recentTransactions" :key="txn.id" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
              <td class="py-3 pl-5 pr-4">
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ txn.description }}</p>
              </td>
              <td class="py-3 pr-4">
                <span v-if="txn.category" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium" :style="{ backgroundColor: txn.category.color + '20', color: txn.category.color }">
                  {{ txn.category.name }}
                </span>
                <span v-else class="text-xs text-gray-400">Uncategorized</span>
              </td>
              <td class="py-3 pr-4 hidden md:table-cell">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ txn.account?.name }}</span>
              </td>
              <td class="py-3 pr-4">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(txn.transaction_date) }}</span>
              </td>
              <td class="py-3 pr-5 text-right">
                <span class="text-sm font-semibold" :class="txn.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                  {{ txn.type === 'income' ? '+' : '-' }}{{ formatPHP(txn.amount) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  BanknotesIcon, ArrowTrendingUpIcon, ArrowTrendingDownIcon,
  ArchiveBoxIcon, CreditCardIcon, ArrowRightIcon, XMarkIcon
} from '@heroicons/vue/24/outline'
import { useCurrency } from '@/composables/useCurrency'
import type { Account, SavingsGoal, Loan, Bill, Transaction } from '@/types'
import dayjs from 'dayjs'

interface Stats {
  totalBalance: number
  monthlyIncome: number
  monthlyExpenses: number
  totalSavings: number
  netWorth: number
  disposableIncome: number
  outstandingLoans: number
  upcomingBillsTotal: number
  healthScore: number
  savingsRate: number
  debtToIncome: number
  emergencyFundMonths: number
}

interface CashFlowMonth { month: string; income: number; expenses: number }
interface CashFlowRange { from: string; to: string }
interface QuickInsight { type: 'success' | 'warning' | 'danger'; icon: string; message: string }

interface CashFlowBreakdownRow {
  name: string; color?: string; frequency?: string; months: number[]
}
interface CashFlowLoanRow {
  name: string; lender?: string; payment_frequency?: string; months: number[]
}
interface CashFlowBreakdown {
  months: string[]
  income_sources: CashFlowBreakdownRow[]
  total_income: number[]
  bills: CashFlowBreakdownRow[]
  total_bills: number[]
  loans: CashFlowLoanRow[]
  total_loans: number[]
  total_expenses: number[]
  net_savings: number[]
}

const props = defineProps<{
  stats: Stats
  accounts: Account[]
  savingsGoals: SavingsGoal[]
  upcomingBills: Bill[]
  loans: Loan[]
  recentTransactions: Transaction[]
  cashFlowData: CashFlowMonth[]
  cashFlowProjection: CashFlowMonth[]
  cashFlowBreakdown: CashFlowBreakdown
  cashFlowRange: CashFlowRange
  incomeProjectionBasis: 'sources' | 'average'
  incomeProjectionMonthly: number
  quickInsights: QuickInsight[]
}>()

const { formatPHP } = useCurrency()

// Cash flow date range filter
const cfFrom = ref(props.cashFlowRange.from)
const cfTo   = ref(props.cashFlowRange.to)
const currentMonth = dayjs().format('YYYY-MM')

// Chart point selection → breakdown panel
const selectedDataIndex = ref<number | null>(null)

watch(() => props.cashFlowData, () => { selectedDataIndex.value = null })

function onDataPointSelect(_e: unknown, _ctx: unknown, config: any) {
  const idx: number = config?.dataPointIndex ?? -1
  if (idx < 0) return
  selectedDataIndex.value = selectedDataIndex.value === idx ? null : idx
}

const selectedMonthData = computed(() => {
  if (selectedDataIndex.value === null) return null
  const idx  = selectedDataIndex.value
  const aLen = props.cashFlowData.length

  if (idx < aLen) {
    const d      = props.cashFlowData[idx]
    const parsed = dayjs(d.month, 'MMM YYYY')
    return {
      isActual: true,
      month:    d.month,
      income:   d.income,
      expenses: d.expenses,
      savings:  d.income - d.expenses,
      transactionsUrl: `/transactions?date_from=${parsed.format('YYYY-MM-01')}&date_to=${parsed.endOf('month').format('YYYY-MM-DD')}`,
    }
  }

  const projIdx = idx - aLen
  if (projIdx < 0 || projIdx >= props.cashFlowProjection.length) return null
  const d = props.cashFlowProjection[projIdx]
  return {
    isActual: false,
    month:    d.month,
    income:   d.income,
    expenses: d.expenses,
    savings:  d.income - d.expenses,
    transactionsUrl: null,
  }
})

function reloadCashFlow() {
  router.reload({
    data: { cf_from: cfFrom.value, cf_to: cfTo.value },
    only: ['cashFlowData', 'cashFlowProjection', 'cashFlowBreakdown', 'cashFlowRange'],
    preserveState: true,
    preserveScroll: true,
  })
}

function resetCashFlowRange() {
  cfFrom.value = dayjs().subtract(5, 'month').format('YYYY-MM')
  cfTo.value   = dayjs().format('YYYY-MM')
  reloadCashFlow()
}

const healthColor = computed(() => {
  const s = props.stats.healthScore
  if (s >= 80) return '#10B981'
  if (s >= 60) return '#3B82F6'
  if (s >= 40) return '#F59E0B'
  return '#EF4444'
})
const healthLabel = computed(() => {
  const s = props.stats.healthScore
  if (s >= 80) return 'Excellent'
  if (s >= 60) return 'Good'
  if (s >= 40) return 'Moderate'
  return 'Needs Work'
})

function formatDate(date: string): string {
  return dayjs(date).format('MMM D, YYYY')
}

function goalEmoji(goal: SavingsGoal): string {
  const icons: Record<string, string> = {
    'shield-check': '🛡️', 'computer-desktop': '💻', 'map': '🗺️',
    'home': '🏠', 'car': '🚗', 'savings': '💰',
  }
  return icons[goal.icon] || '💰'
}

function loanProgress(loan: Loan): number {
  const paid = loan.principal_amount - loan.remaining_balance
  return Math.min(100, (paid / loan.principal_amount) * 100)
}

function billStatusClass(status: string): string {
  return {
    overdue: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400',
    due_soon: 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400',
    upcoming: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400',
  }[status] || ''
}

function billStatusLabel(status: string): string {
  return { overdue: 'Overdue', due_soon: 'Due Soon', upcoming: 'Upcoming' }[status] || status
}

function freqLabel(freq?: string): string {
  return ({ weekly: 'Weekly', biweekly: 'Bi-weekly', monthly: 'Monthly', quarterly: 'Quarterly', annually: 'Annually', 'one-time': 'One-time' } as Record<string, string>)[freq ?? ''] ?? (freq ?? '')
}

// ApexCharts config — 4 series: actual income/expenses (solid) + projected (dashed)
const allChartMonths = computed(() => [
  ...props.cashFlowData.map(d => d.month),
  ...props.cashFlowProjection.map(d => d.month),
])

const cashFlowOptions = computed(() => ({
  chart: {
    background: 'transparent',
    toolbar: { show: false },
    events: {
      // click fires for any click on the chart area, not just exact marker hits
      click: onDataPointSelect,
    },
  },
  colors: ['#10B981', '#EF4444', '#7C3AED', '#34D399', '#F87171', '#A78BFA'],
  fill: {
    type: 'gradient',
    gradient: { opacityFrom: 0.35, opacityTo: 0.05 },
    opacity: [1, 1, 0, 0, 0, 0],
  },
  dataLabels: { enabled: false },
  markers: { size: 5, hover: { size: 8 }, strokeWidth: 0, fillOpacity: 1 },
  stroke: {
    curve: 'smooth',
    width: [2.5, 2.5, 2, 2.5, 2.5, 2],
    dashArray: [0, 0, 0, 6, 6, 6],
  },
  xaxis: {
    categories: allChartMonths.value,
    labels: { style: { colors: '#9CA3AF', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { colors: '#9CA3AF', fontSize: '11px' },
      formatter: (val: number) => `₱${(val / 1000).toFixed(0)}K`,
    },
  },
  grid: {
    borderColor: 'rgba(156, 163, 175, 0.1)',
    padding: { left: 0, right: 0 },
  },
  tooltip: {
    theme: 'dark',
    shared: true,
    y: { formatter: (val: number | null) => val != null ? `₱${val.toLocaleString('en-PH')}` : '—' },
  },
  legend: { show: false },
  annotations: {
    xaxis: props.cashFlowData.length > 0 ? [{
      x: props.cashFlowData[props.cashFlowData.length - 1].month,
      borderColor: '#6366F1',
      borderWidth: 1,
      strokeDashArray: 4,
      label: {
        text: 'Today',
        style: { color: '#6366F1', background: 'transparent', fontSize: '10px' },
        position: 'top',
        offsetY: -5,
      },
    }] : [],
  },
}))

const cashFlowSeries = computed(() => {
  const actual = props.cashFlowData
  const proj   = props.cashFlowProjection
  const aLen   = actual.length
  const pLen   = proj.length
  const lastIncome   = actual[aLen - 1]?.income   ?? 0
  const lastExpenses = actual[aLen - 1]?.expenses ?? 0
  const lastSavings  = lastIncome - lastExpenses

  return [
    {
      name: 'Income',
      data: [...actual.map(d => d.income), ...Array(pLen).fill(null)],
    },
    {
      name: 'Expenses',
      data: [...actual.map(d => d.expenses), ...Array(pLen).fill(null)],
    },
    {
      name: 'Savings',
      data: [...actual.map(d => d.income - d.expenses), ...Array(pLen).fill(null)],
    },
    {
      name: 'Proj. Income',
      data: [...Array(aLen - 1).fill(null), lastIncome, ...proj.map(d => d.income)],
    },
    {
      name: 'Proj. Expenses',
      data: [...Array(aLen - 1).fill(null), lastExpenses, ...proj.map(d => d.expenses)],
    },
    {
      name: 'Proj. Savings',
      data: [...Array(aLen - 1).fill(null), lastSavings, ...proj.map(d => d.income - d.expenses)],
    },
  ]
})

// Inline StatCard component
const StatCard = {
  props: ['label', 'value', 'icon', 'gradient', 'change', 'positive'],
  template: `
    <div class="bg-white dark:bg-[#1A1A2E] rounded-2xl border border-gray-200 dark:border-white/10 p-5 card-hover">
      <div class="flex items-start justify-between mb-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="gradient">
          <component :is="icon" class="w-5 h-5 text-white" />
        </div>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full"
          :class="positive ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10 dark:text-emerald-400' : 'text-red-600 bg-red-50 dark:bg-red-500/10 dark:text-red-400'">
          {{ change }}
        </span>
      </div>
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ label }}</p>
      <p class="text-xl font-bold text-gray-900 dark:text-white">{{ value }}</p>
    </div>
  `,
}
</script>
