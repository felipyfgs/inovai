import type { Estoque, EstoqueMovimentacao, EstoqueResumo, PaginatedResponse } from '~/types'

export function useEstoque() {
  const { data: resumo, status: resumoStatus, refresh: refreshResumo } = useApi<EstoqueResumo>('/estoques/resumo', {
    lazy: true
  })

  function getPositions(params?: Record<string, unknown>) {
    const { data, status, refresh } = useApi<PaginatedResponse<Estoque>>('/estoques', {
      lazy: true,
      query: params as Record<string, unknown> | undefined
    })

    return { data, status, refresh }
  }

  function getMovimentacoes(params?: Record<string, unknown>) {
    const { data, status, refresh } = useApi<PaginatedResponse<EstoqueMovimentacao>>('/estoques/movimentacoes', {
      lazy: true,
      query: params as Record<string, unknown> | undefined
    })

    return { data, status, refresh }
  }

  const { post } = useApiMutation()
  const { extractMessage } = useApiError()

  async function ajuste(data: {
    produto_id: number
    tipo: 'entrada' | 'saida' | 'ajuste'
    quantidade: number
    custo_unitario?: number
    localizacao?: string
    observacoes?: string
  }) {
    try {
      await post('/estoques/ajuste', data)
      useAppToast().success('Ajuste de estoque realizado com sucesso.')
      await refreshResumo()
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  return {
    resumo,
    resumoStatus,
    refreshResumo,
    getPositions,
    getMovimentacoes,
    ajuste
  }
}
