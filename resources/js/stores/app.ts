import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAppStore = defineStore('app', () => {
  const sidebarOpen = ref(true)
  const sidebarMobileOpen = ref(false)

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function toggleMobileSidebar() {
    sidebarMobileOpen.value = !sidebarMobileOpen.value
  }

  function closeMobileSidebar() {
    sidebarMobileOpen.value = false
  }

  return { sidebarOpen, sidebarMobileOpen, toggleSidebar, toggleMobileSidebar, closeMobileSidebar }
})
