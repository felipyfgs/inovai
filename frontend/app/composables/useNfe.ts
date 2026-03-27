import { computed } from 'vue'
import type { Nfe, PaginatedResponse } from '~/types'

export function useNfe() {
  const { currentCompany } = useCurrentCompany()
  const { post, put, del } = useApiMutation()
  const { handleError, extractMessage } = useApiError()

  function listNfes(modelo: 55 | 65 = 55) {
    return useApi<PaginatedResponse<Nfe>>(`/nfes?modelo=${modelo}`, {
      lazy: true,
      watch: [computed(() => currentCompany.value?.id)]
    })
  }

  async function createNfe(data: Record<string, unknown>) {
    return await post<Nfe>('/nfes', data)
  }

  async function updateNfe(id: number, data: Record<string, unknown>) {
    return await put<Nfe>(`/nfes/${id}`, data)
  }

  async function deleteNfe(id: number) {
    return await del(`/nfes/${id}`)
  }

  async function signNfe(id: number) {
    return await post(`/nfes/${id}/sign`)
  }

  async function transmitNfe(id: number) {
    return await post(`/nfes/${id}/transmit`)
  }

  async function cancelNfe(id: number, justificativa: string) {
    return await post(`/nfes/${id}/cancel`, { justificativa })
  }

  async function cartaCorrecao(id: number, correcao: Array<{ grupo: string, campo: string, valor_anterior: string, valor_novo: string }>) {
    return await post(`/nfes/${id}/carta-correcao`, { correcao })
  }

  return {
    listNfes,
    createNfe,
    updateNfe,
    deleteNfe,
    signNfe,
    transmitNfe,
    cancelNfe,
    cartaCorrecao,
    handleError,
    extractMessage
  }
}
