import { computed } from 'vue'
import type { Cte, PaginatedResponse } from '~/types'

export function useCte() {
  const { currentCompany } = useCurrentCompany()
  const { post, put, del } = useApiMutation()
  const { handleError, extractMessage } = useApiError()

  function listCtes() {
    return useApi<PaginatedResponse<Cte>>('/ctes', {
      lazy: true,
      watch: [computed(() => currentCompany.value?.id)]
    })
  }

  async function createCte(data: Record<string, unknown>) {
    return await post<Cte>('/ctes', data)
  }

  async function updateCte(id: number, data: Record<string, unknown>) {
    return await put<Cte>(`/ctes/${id}`, data)
  }

  async function deleteCte(id: number) {
    return await del(`/ctes/${id}`)
  }

  async function signCte(id: number) {
    return await post(`/ctes/${id}/sign`)
  }

  async function transmitCte(id: number) {
    return await post(`/ctes/${id}/transmit`)
  }

  async function cancelCte(id: number, justificativa: string) {
    return await post(`/ctes/${id}/cancel`, { justificativa })
  }

  return {
    listCtes,
    createCte,
    updateCte,
    deleteCte,
    signCte,
    transmitCte,
    cancelCte,
    handleError,
    extractMessage
  }
}
