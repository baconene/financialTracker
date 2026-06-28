import { createApp, h, DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import VueApexCharts from 'vue3-apexcharts'

import '../css/app.css'

const pinia = createPinia()

createInertiaApp({
  title: (title) => title ? `${title} - FinanceTracker` : 'FinanceTracker',
  resolve: (name) => resolvePageComponent(
    `./Pages/${name}.vue`,
    import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
  ),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(pinia)
      .use(ZiggyVue)
      .use(VueApexCharts)
      .mount(el)
  },
  progress: {
    color: '#7C3AED',
    showSpinner: true,
  },
})
