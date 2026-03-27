import * as z from 'zod'
import type { Pessoa, Produto, PaginatedResponse, PedidoItem } from '~/types'

const itemSchema = z.object({
  produto_id: z.coerce.number().nullable().optional(),
  descricao: z.string().min(1, 'Obrigatório'),
  quantidade: z.coerce.number().min(0.0001, 'Mín. 0.0001'),
  valor_unitario: z.coerce.number().min(0, 'Mín. 0'),
  desconto: z.coerce.number().min(0).default(0)
})

const schema = z.object({
  pessoa_id: z.coerce.number().nullable().optional(),
  data: z.string().min(1, 'Data obrigatória'),
  observacoes: z.string().optional(),
  desconto: z.coerce.number().min(0).default(0),
  itens: z.array(itemSchema).min(1, 'Adicione pelo menos 1 item')
})

type Schema = z.output<typeof schema>

export function usePedidoForm() {
  const { currentCompany } = useCurrentCompany()

  const { data: pessoasData } = useApi<PaginatedResponse<Pessoa>>('/pessoas', {
    lazy: true,
    watch: [computed(() => currentCompany.value?.id)]
  })
  const { data: produtosData } = useApi<PaginatedResponse<Produto>>('/produtos', {
    lazy: true,
    watch: [computed(() => currentCompany.value?.id)]
  })

  const pessoaOptions = computed(() =>
    (pessoasData.value?.data || []).map(p => ({ label: p.razao_social, value: p.id }))
  )
  const produtoOptions = computed(() =>
    (produtosData.value?.data || []).map(p => ({ label: p.descricao, value: p.id }))
  )

  function defaultState() {
    return {
      pessoa_id: null as number | null,
      data: new Date().toISOString().split('T')[0] as string,
      observacoes: '' as string,
      desconto: 0 as number,
      itens: [{ produto_id: null, descricao: '', quantidade: 1, valor_unitario: 0, desconto: 0 }] as PedidoItem[]
    }
  }

  function addItem(state: { itens: PedidoItem[] }) {
    state.itens.push({ produto_id: null, descricao: '', quantidade: 1, valor_unitario: 0, desconto: 0 })
  }

  function removeItem(state: { itens: PedidoItem[] }, index: number) {
    if (state.itens.length > 1) {
      state.itens.splice(index, 1)
    }
  }

  function onProdutoSelect(index: number, produtoId: number | null, state: { itens: PedidoItem[] }) {
    if (!produtoId) return
    const produto = (produtosData.value?.data || []).find(p => p.id === produtoId)
    const item = state.itens[index]
    if (produto && item) {
      item.descricao = produto.descricao
      item.valor_unitario = Number(produto.preco_venda)
    }
  }

  function calcTotalItens(state: { itens: PedidoItem[] }) {
    return state.itens.reduce((sum: number, item: PedidoItem) => {
      return sum + (item.quantidade * item.valor_unitario) - (item.desconto || 0)
    }, 0)
  }

  function calcTotalGeral(state: { itens: PedidoItem[], desconto: number | undefined }) {
    return calcTotalItens(state) - (state.desconto || 0)
  }

  function populateFromEntity(state: ReturnType<typeof defaultState>, entity: {
    pessoa_id?: number | null
    data?: string
    observacoes?: string | null
    desconto?: number
    itens?: PedidoItem[]
  }) {
    state.pessoa_id = entity.pessoa_id ?? null
    state.data = entity.data || new Date().toISOString().slice(0, 10)
    state.observacoes = entity.observacoes || ''
    state.desconto = entity.desconto || 0
    state.itens = (entity.itens || []).map(item => ({
      produto_id: item.produto_id ?? null,
      descricao: item.descricao,
      quantidade: item.quantidade,
      valor_unitario: item.valor_unitario,
      desconto: item.desconto || 0
    }))
    if (state.itens.length === 0) {
      state.itens = [{ produto_id: null, descricao: '', quantidade: 1, valor_unitario: 0, desconto: 0 }]
    }
  }

  return {
    schema,
    itemSchema,
    pessoaOptions,
    produtoOptions,
    defaultState,
    addItem,
    removeItem,
    onProdutoSelect,
    calcTotalItens,
    calcTotalGeral,
    populateFromEntity
  }
}

export type { Schema as PedidoSchema }
