import type { Company } from '~/types'

const currentCompany = ref<Company | null>(null)

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

  return {
    currentCompany: readonly(currentCompany),
    setCompany,
    getStoredCompanyId
  }
}
