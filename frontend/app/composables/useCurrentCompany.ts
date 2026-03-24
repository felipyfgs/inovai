import type { Company } from '~/types'

const currentCompany = ref<Company | null>(null)
const initialized = ref(false)

export function useCurrentCompany() {
  const setCompany = (company: Company | null) => {
    currentCompany.value = company
    if (import.meta.client) {
      if (company) {
        localStorage.setItem('current_company_id', String(company.id))
      } else {
        localStorage.removeItem('current_company_id')
      }
    }
  }

  const getStoredCompanyId = (): number | null => {
    if (import.meta.server) return null
    const id = localStorage.getItem('current_company_id')
    return id ? Number(id) : null
  }

  const initializeFromCompanies = (companies: Company[]) => {
    if (initialized.value || companies.length === 0) return

    const storedId = getStoredCompanyId()
    const storedCompany = storedId ? companies.find(c => c.id === storedId) : null

    if (storedCompany) {
      currentCompany.value = storedCompany
    } else if (companies.length === 1) {
      const singleCompany = companies[0]
      currentCompany.value = singleCompany
      if (import.meta.client && singleCompany) {
        localStorage.setItem('current_company_id', String(singleCompany.id))
      }
    }

    initialized.value = true
  }

  return {
    currentCompany: readonly(currentCompany),
    setCompany,
    getStoredCompanyId,
    initializeFromCompanies
  }
}
