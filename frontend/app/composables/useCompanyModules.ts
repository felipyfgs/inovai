import type { AvailableModule } from '~/types'

const activeModules = ref<string[]>([])
const availableModules = ref<AvailableModule[]>([])
const loaded = ref(false)

export function useCompanyModules() {
  const { currentCompany } = useCurrentCompany()

  async function fetchModules() {
    if (!currentCompany.value) {
      activeModules.value = []
      availableModules.value = []
      loaded.value = false
      return
    }

    const { $sanctumClient } = useNuxtApp()
    try {
      const data = await $sanctumClient<AvailableModule[]>(`/api/companies/${currentCompany.value.id}/modules`)
      availableModules.value = data
      activeModules.value = data.filter(m => m.is_active).map(m => m.id)
      loaded.value = true
    } catch {
      activeModules.value = []
      availableModules.value = []
      loaded.value = false
    }
  }

  function hasModule(module: string): boolean {
    return activeModules.value.includes(module)
  }

  watch(() => currentCompany.value?.id, () => {
    loaded.value = false
    fetchModules()
  })

  return {
    activeModules: readonly(activeModules),
    availableModules: readonly(availableModules),
    loaded: readonly(loaded),
    hasModule,
    fetchModules
  }
}
