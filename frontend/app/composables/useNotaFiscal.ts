import { computed } from 'vue'
import type { NotaFiscal, PaginatedResponse } from '~/types'

export function useNotaFiscal() {
  const { currentCompany } = useCurrentCompany()
  const { post, put, del } = useApiMutation()
  const { handleError, extractMessage } = useApiError()

  function listNotasFiscais(modelo: 55 | 65 = 55) {
    return useApi<PaginatedResponse<NotaFiscal>>(`/notas-fiscais?modelo=${modelo}`, {
      lazy: true,
      watch: [computed(() => currentCompany.value?.id)]
    })
  }

  async function createNotaFiscal(data: Record<string, unknown>) {
    return await post<NotaFiscal>('/notas-fiscais', data)
  }

  async function updateNotaFiscal(id: number, data: Record<string, unknown>) {
    return await put<NotaFiscal>(`/notas-fiscais/${id}`, data)
  }

  async function deleteNotaFiscal(id: number) {
    return await del(`/notas-fiscais/${id}`)
  }

  async function signNotaFiscal(id: number) {
    return await post(`/notas-fiscais/${id}/sign`)
  }

  async function transmitNotaFiscal(id: number) {
    return await post(`/notas-fiscais/${id}/transmit`)
  }

  async function cancelNotaFiscal(id: number, justificativa: string) {
    return await post(`/notas-fiscais/${id}/cancel`, { justificativa })
  }

  async function cartaCorrecao(id: number, correcao: Array<{ grupo: string, campo: string, valor_anterior: string, valor_novo: string }>) {
    return await post(`/notas-fiscais/${id}/carta-correcao`, { correcao })
  }

  return {
    listNotasFiscais,
    createNotaFiscal,
    updateNotaFiscal,
    deleteNotaFiscal,
    signNotaFiscal,
    transmitNotaFiscal,
    cancelNotaFiscal,
    cartaCorrecao,
    handleError,
    extractMessage
  }
}
