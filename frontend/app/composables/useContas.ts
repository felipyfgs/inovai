import type { Conta, ContaParcela, FinanceiroResumo, PaginatedResponse } from '~/types'

export function useContas() {
  const { data: resumo, status: resumoStatus, refresh: refreshResumo } = useApi<FinanceiroResumo>('/contas/resumo', {
    lazy: true
  })

  function getContas(params?: Record<string, unknown>) {
    const { data, status, refresh } = useApi<PaginatedResponse<Conta>>('/contas', {
      lazy: true,
      query: params as Record<string, unknown> | undefined
    })
    return { data, status, refresh }
  }

  const { post, put, del } = useApiMutation()
  const { extractMessage } = useApiError()

  async function createConta(data: Partial<Conta>) {
    try {
      const result = await post<Conta>('/contas', data)
      useAppToast().success('Conta criada com sucesso.')
      await refreshResumo()
      return result
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  async function updateConta(id: number, data: Partial<Conta>) {
    try {
      const result = await put<Conta>(`/contas/${id}`, data)
      useAppToast().success('Conta atualizada com sucesso.')
      return result
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  async function deleteConta(id: number) {
    try {
      await del(`/contas/${id}`)
      useAppToast().success('Conta removida com sucesso.')
      await refreshResumo()
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  async function baixarParcela(parcelaId: number, data: {
    valor: number
    valor_desconto?: number
    valor_juros?: number
    valor_multa?: number
    forma_pagamento?: string
    observacoes?: string
  }) {
    try {
      const result = await post<ContaParcela>(`/contas-parcelas/${parcelaId}/baixar`, data)
      useAppToast().success('Baixa realizada com sucesso.')
      await refreshResumo()
      return result
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  async function estornarParcela(parcelaId: number) {
    try {
      const result = await post<ContaParcela>(`/contas-parcelas/${parcelaId}/estornar`)
      useAppToast().success('Baixa estornada com sucesso.')
      await refreshResumo()
      return result
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  async function cancelarConta(id: number) {
    try {
      const result = await post<Conta>(`/contas/${id}/cancelar`)
      useAppToast().success('Conta cancelada com sucesso.')
      await refreshResumo()
      return result
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  return {
    resumo,
    resumoStatus,
    refreshResumo,
    getContas,
    createConta,
    updateConta,
    deleteConta,
    baixarParcela,
    estornarParcela,
    cancelarConta
  }
}
