import type { Office } from '~/types'

const currentOffice = ref<Office | null>(null)
const initialized = ref(false)

export function useCurrentOffice() {
  const setOffice = (office: Office | null) => {
    currentOffice.value = office
    if (import.meta.client) {
      if (office) {
        localStorage.setItem('current_office_id', String(office.id))
      } else {
        localStorage.removeItem('current_office_id')
      }
    }
  }

  const getStoredOfficeId = (): number | null => {
    if (import.meta.server) return null
    const id = localStorage.getItem('current_office_id')
    return id ? Number(id) : null
  }

  const initializeFromOffices = (offices: Office[]) => {
    if (initialized.value || offices.length === 0) return

    const storedId = getStoredOfficeId()
    const storedOffice = storedId ? offices.find(o => o.id === storedId) : null

    if (storedOffice) {
      currentOffice.value = storedOffice
    }
    // Admin users: stay without office if nothing stored
    // Non-admin users already have their office from user.office

    initialized.value = true
  }

  return {
    currentOffice: readonly(currentOffice),
    setOffice,
    getStoredOfficeId,
    initializeFromOffices
  }
}
