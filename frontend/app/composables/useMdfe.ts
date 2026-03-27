import { computed } from 'vue'
import type { Mdfe, PaginatedResponse } from '~/types'

export function useMdfe() {
  const { currentCompany } = useCurrentCompany()
  const { post, put, del } = useApiMutation()
  const { handleError, extractMessage } = useApiError()

  function listMdfes() {
    return useApi<PaginatedResponse<Mdfe>>('/mdfes', {
      lazy: true,
      watch: [computed(() => currentCompany.value?.id)]
    })
  }

  async function createMdfe(data: Record<string, unknown>) {
    return await post<Mdfe>('/mdfes', data)
  }

  async function updateMdfe(id: number, data: Record<string, unknown>) {
    return await put<Mdfe>(`/mdfes/${id}`, data)
  }

  async function deleteMdfe(id: number) {
    return await del(`/mdfes/${id}`)
  }

  async function signMdfe(id: number) {
    return await post(`/mdfes/${id}/sign`)
  }

  async function transmitMdfe(id: number) {
    return await post(`/mdfes/${id}/transmit`)
  }

  async function cancelMdfe(id: number, justificativa: string) {
    return await post(`/mdfes/${id}/cancel`, { justificativa })
  }

  async function encerrarMdfe(id: number, data: { uf: string, municipio: string, municipio_ibge: string }) {
    return await post(`/mdfes/${id}/encerrar`, data)
  }

  return {
    listMdfes,
    createMdfe,
    updateMdfe,
    deleteMdfe,
    signMdfe,
    transmitMdfe,
    cancelMdfe,
    encerrarMdfe,
    handleError,
    extractMessage
  }
}
