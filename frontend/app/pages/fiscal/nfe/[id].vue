<script setup lang="ts">
import type { BreadcrumbItem, FormSubmitEvent } from '@nuxt/ui'
import type { Nfe, NfeItem, Pessoa, Produto, Transportadora, PaginatedResponse } from '~/types'
import { formatCurrency } from '~/utils'

import { UButton, UBadge, UDropdownMenu } from '#components'

import * as z from 'zod'

const cstIcmsOptions = [
  { label: '00 - Tributada integralmente', value: '00' },
  { label: '10 - Tributada com cobrança de ICMS por ST', value: '10' },
  { label: '20 - Com redução de BC', value: '20' },
  { label: '30 - Isenta ou não tributada com cobrança de ICMS por ST', value: '30' },
  { label: '40 - Isenta', value: '40' },
  { label: '41 - Não tributada', value: '41' },
  { label: '50 - Suspensão', value: '50' },
  { label: '51 - Diferimento', value: '51' },
  { label: '60 - ICMS cobrado por ST', value: '60' },
  { label: '70 - Com redução de BC e cobrança de ICMS por ST', value: '70' },
  { label: '90 - Outras', value: '90' },
  { label: 'SN102 - Tributada pelo Simples Nacional', value: 'SN102' },
  { label: 'SN201 - Tributada pelo Simples Nacional com permissão de crédito', value: 'SN201' },
  { label: 'SN202 - Tributada pelo Simples Nacional sem permissão de crédito', value: 'SN202' },
  { label: 'SN500 - ICMS cobrado por ST pelo Simples Nacional', value: 'SN500' },
  { label: 'SN900 - Outros pelo Simples Nacional', value: 'SN900' }
]

const origemOptions = [
  { label: '0 - Nacional', value: 0 },
  { label: '1 - Estrangeira - Importação direta', value: 1 },
  { label: '2 - Estrangeira - Adquirida no mercado interno', value: 2 },
  { label: '3 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 40%', value: 3 },
  { label: '4 - Nacional, cuja produção tenha sido feita em conformidade com processos produtivos básicos', value: 4 },
  { label: '5 - Nacional, mercadoria ou bem com Conteúdo de Importação inferior ou igual a 40%', value: 5 },
  { label: '6 - Estrangeira - Importação direta, sem similar nacional', value: 6 },
  { label: '7 - Estrangeira - Adquirida no mercado interno, sem similar nacional', value: 7 },
  { label: '8 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 70%', value: 8 }
]

const itemSchema = z.object({
  produto_id: z.coerce.number().nullable().optional(),
  descricao: z.string().optional(),
  ncm: z.string().optional(),
  cfop: z.string().optional(),
  unidade: z.string().optional(),
  quantidade: z.coerce.number().min(0.0001, 'Mín. 0.0001'),
  valor_unitario: z.coerce.number().min(0, 'Mín. 0'),
  cst_icms: z.string().optional(),
  origem: z.coerce.number().optional(),
  bc_icms: z.coerce.number().optional(),
  aliq_icms: z.coerce.number().min(0).max(100).optional(),
  valor_icms: z.coerce.number().optional()
})

const schema = z.object({
  natureza_operacao: z.string().min(1, 'Obrigatório'),
  tipo_operacao: z.coerce.number().min(1).max(2),
  finalidade: z.coerce.number().min(1).max(4),
  pessoa_id: z.coerce.number().nullable().optional(),
  data_emissao: z.string().optional(),
  data_saida: z.string().optional(),
  transportadora_id: z.coerce.number().nullable().optional(),
  frete_por: z.coerce.number().optional(),
  valor_frete: z.coerce.number().min(0).default(0),
  valor_seguro: z.coerce.number().min(0).default(0),
  valor_desconto: z.coerce.number().min(0).default(0),
  informacoes_adicionais: z.string().optional(),
  itens: z.array(itemSchema).min(1, 'Adicione pelo menos 1 item')
})

type Schema = z.output<typeof schema>

interface NotaItem {
  produto_id: number | null
  descricao: string
  ncm: string
  cfop: string
  unidade: string
  quantidade: number
  valor_unitario: number
  cst_icms: string
  origem: number
  bc_icms: number
  aliq_icms: number
  valor_icms: number
}

interface NotaState {
  natureza_operacao: string
  tipo_operacao: number
  finalidade: number
  pessoa_id: number | null
  data_emissao: string
  data_saida: string
  transportadora_id: number | null
  frete_por: number
  valor_frete: number
  valor_seguro: number
  valor_desconto: number
  informacoes_adicionais: string
  itens: NotaItem[]
}

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { updateNfe } = useNfe()
const { currentCompany } = useCurrentCompany()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const nfeId = computed(() => Number(route.params.id))
const url = computed(() => `/nfes/${nfeId.value}`)

const { data: nota, status, refresh } = useApi<Nfe>(url, { lazy: false })

const isEditing = ref(false)
const loading = ref(false)
const cancelModalRef = useTemplateRef('cancelModalRef')
const deleteModalRef = useTemplateRef('deleteModalRef')

const pessoaSearch = ref('')
const transportadoraSearch = ref('')
const produtoSearch = ref('')

const { data: pessoasData } = useApi<PaginatedResponse<Pessoa>>('/pessoas', {
  lazy: true,
  watch: [computed(() => currentCompany.value?.id)]
})
const { data: transportadorasData } = useApi<PaginatedResponse<Transportadora>>('/transportadoras', {
  lazy: true,
  watch: [computed(() => currentCompany.value?.id)]
})
const { data: produtosData } = useApi<PaginatedResponse<Produto>>('/produtos', {
  lazy: true,
  watch: [computed(() => currentCompany.value?.id)]
})

const pessoaOptions = computed(() => {
  const items = (pessoasData.value?.data || []).filter((p) => {
    if (!pessoaSearch.value) return true
    const s = pessoaSearch.value.toLowerCase()
    return p.razao_social.toLowerCase().includes(s) || (p.cpf_cnpj && p.cpf_cnpj.includes(s))
  })
  return items.map(p => ({ label: `${p.razao_social}${p.cpf_cnpj ? ` - ${p.cpf_cnpj}` : ''}`, value: p.id }))
})

const transportadoraOptions = computed(() => {
  const items = (transportadorasData.value?.data || []).filter((t) => {
    if (!transportadoraSearch.value) return true
    const s = transportadoraSearch.value.toLowerCase()
    return t.razao_social.toLowerCase().includes(s) || (t.cnpj && t.cnpj.includes(s))
  })
  return items.map(t => ({ label: `${t.razao_social}${t.cnpj ? ` - ${t.cnpj}` : ''}`, value: t.id }))
})

const produtoOptions = computed(() => {
  const items = (produtosData.value?.data || []).filter((p) => {
    if (!produtoSearch.value) return true
    const s = produtoSearch.value.toLowerCase()
    return p.descricao.toLowerCase().includes(s) || (p.codigo && p.codigo.toLowerCase().includes(s))
  })
  return items.map(p => ({ label: `${p.codigo ? `${p.codigo} - ` : ''}${p.descricao}`, value: p.id }))
})

function createEmptyItem(): NotaItem {
  return {
    produto_id: null, descricao: '', ncm: '', cfop: '', unidade: '',
    quantidade: 1, valor_unitario: 0, cst_icms: '00', origem: 0,
    bc_icms: 0, aliq_icms: 0, valor_icms: 0
  }
}

const state = reactive<NotaState>({
  natureza_operacao: '',
  tipo_operacao: 2,
  finalidade: 1,
  pessoa_id: null,
  data_emissao: '',
  data_saida: '',
  transportadora_id: null,
  frete_por: 9,
  valor_frete: 0,
  valor_seguro: 0,
  valor_desconto: 0,
  informacoes_adicionais: '',
  itens: [createEmptyItem()]
})

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { label: 'Fiscal', icon: 'i-lucide-file-text', to: '/fiscal/nfe' },
  { label: 'NF-e', icon: 'i-lucide-file-text', to: '/fiscal/nfe' },
  { label: nota.value ? `#${nota.value.numero}` : '...' }
])

const statusColor: Record<string, 'neutral' | 'info' | 'success' | 'error' | 'warning'> = {
  rascunho: 'neutral',
  assinada: 'info',
  transmitida: 'info',
  autorizada: 'success',
  rejeitada: 'error',
  cancelada: 'error',
  inutilizada: 'warning',
  denegada: 'error'
}

const valorProdutos = computed(() => {
  return state.itens.reduce((sum, item) => sum + (item.quantidade * item.valor_unitario), 0)
})

const valorIcms = computed(() => {
  return state.itens.reduce((sum, item) => sum + (item.valor_icms || 0), 0)
})

const valorTotal = computed(() => {
  return valorProdutos.value + state.valor_frete + state.valor_seguro - state.valor_desconto
})

function populateFromEntity() {
  if (!nota.value) return
  const n = nota.value as unknown as Record<string, unknown>
  state.natureza_operacao = (n.natureza_operacao as string) || ''
  state.tipo_operacao = (n.tipo_operacao as number) || 2
  state.finalidade = (n.finalidade as number) || 1
  state.pessoa_id = (n.pessoa_id as number) || null
  state.data_emissao = (n.data_emissao as string) || ''
  state.data_saida = (n.data_saida as string) || ''
  state.transportadora_id = (n.transportadora_id as number) || null
  state.frete_por = (n.frete_por as number) ?? 9
  state.valor_frete = (n.valor_frete as number) || 0
  state.valor_seguro = (n.valor_seguro as number) || 0
  state.valor_desconto = (n.valor_desconto as number) || 0
  state.informacoes_adicionais = (n.informacoes_adicionais as string) || ''
  state.itens = ((n.itens as NfeItem[]) || []).map(i => ({
    produto_id: i.produto_id || null,
    descricao: i.descricao || '',
    ncm: i.ncm || '',
    cfop: i.cfop || '',
    unidade: i.unidade || '',
    quantidade: i.quantidade || 1,
    valor_unitario: i.valor_unitario || 0,
    cst_icms: i.cst_icms || '00',
    origem: i.origem || 0,
    bc_icms: i.bc_icms || 0,
    aliq_icms: i.aliq_icms || 0,
    valor_icms: i.valor_icms || 0
  }))
  if (state.itens.length === 0) {
    state.itens = [createEmptyItem()]
  }
  pessoaSearch.value = ''
  transportadoraSearch.value = ''
  produtoSearch.value = ''
}

watch(nota, (val) => {
  if (val && !isEditing.value) {
    populateFromEntity()
  }
}, { immediate: true })

function startEditing() {
  populateFromEntity()
  isEditing.value = true
}

function cancelEditing() {
  populateFromEntity()
  isEditing.value = false
}

function addItem() {
  state.itens.push(createEmptyItem())
}

function removeItem(index: number) {
  if (state.itens.length > 1) {
    state.itens.splice(index, 1)
  }
}

function onProdutoSelect(index: number, produtoId: number | null) {
  if (!produtoId) return
  const produto = (produtosData.value?.data || []).find(p => p.id === produtoId)
  const item = state.itens[index]
  if (produto && item) {
    item.descricao = produto.descricao
    item.ncm = produto.ncm || ''
    item.cfop = produto.cfop || ''
    item.unidade = produto.unidade
    item.valor_unitario = Number(produto.preco_venda)
    item.origem = produto.origem
    recalcItemIcms(index)
  }
}

function recalcItemIcms(index: number) {
  const item = state.itens[index]
  if (!item) return
  const valorTotal = item.quantidade * item.valor_unitario
  item.bc_icms = valorTotal
  item.valor_icms = Number((item.bc_icms * (item.aliq_icms / 100)).toFixed(2))
}

function onAliqChange(index: number) {
  recalcItemIcms(index)
}

function onQtyOrPriceChange(index: number) {
  const item = state.itens[index]
  if (!item) return
  recalcItemIcms(index)
}

function buildPayload(event: FormSubmitEvent<Schema>) {
  const data: Record<string, unknown> = { ...event.data }
  data.valor_produtos = Number(valorProdutos.value.toFixed(2))
  data.valor_icms = Number(valorIcms.value.toFixed(2))
  data.valor_total = Number(valorTotal.value.toFixed(2))
  data.itens = (data.itens as unknown[]).map((item, idx) => {
    const nfItem: Record<string, unknown> = { ...(item as Record<string, unknown>) }
    nfItem.numero_item = idx + 1
    nfItem.valor_total = Number((((item as Record<string, unknown>).quantidade as number) * ((item as Record<string, unknown>).valor_unitario as number)).toFixed(4))
    return nfItem
  })
  return data
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await updateNfe(nfeId.value, buildPayload(event))
    toast.add({ title: 'NF-e atualizada', color: 'success' })
    isEditing.value = false
    refresh()
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao atualizar NF-e.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}

function formatDate(date: string | null | undefined) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('pt-BR')
}

function openCancelModal() {
  cancelModalRef.value?.openModal()
}

function openDeleteModal() {
  deleteModalRef.value?.openModal()
}

function copyChave() {
  if (!nota.value?.chave) return
  navigator.clipboard.writeText(nota.value.chave)
  toast.add({ title: 'Copiado', description: 'Chave copiada' })
}

const actions = computed(() => [
  {
    type: 'label' as const,
    label: 'Ações'
  },
  {
    label: 'Copiar Chave',
    icon: 'i-lucide-copy',
    disabled: !nota.value?.chave,
    onSelect: copyChave
  },
  {
    label: 'Ver DANFE',
    icon: 'i-lucide-file-text'
  },
  {
    label: 'Ver XML',
    icon: 'i-lucide-code'
  },
  { type: 'separator' as const },
  {
    label: 'Cancelar NF-e',
    icon: 'i-lucide-x-circle',
    color: 'error' as const,
    disabled: nota.value?.status !== 'autorizada',
    onSelect: openCancelModal
  },
  {
    label: 'Excluir',
    icon: 'i-lucide-trash',
    color: 'error' as const,
    onSelect: openDeleteModal
  }
])

function onCancelled() {
  refresh()
}

function onDeleted() {
  router.push('/fiscal/nfe')
}
</script>

<template>
  <UDashboardPanel id="nfe-detalhe">
    <template #header>
      <UDashboardNavbar title="NF-e">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UBadge
            v-if="nota"
            :color="statusColor[nota.status] || 'neutral'"
            variant="subtle"
            class="capitalize"
          >
            {{ nota.status }}
          </UBadge>

          <template v-if="!isEditing">
            <UDropdownMenu
              v-if="nota"
              :items="actions"
              :content="{ align: 'end' }"
            >
              <UButton
                icon="i-lucide-ellipsis-vertical"
                color="neutral"
                variant="ghost"
              />
            </UDropdownMenu>

            <UButton
              label="Editar"
              icon="i-lucide-pencil"
              color="primary"
              :disabled="!nota || nota.status === 'autorizada' || nota.status === 'cancelada'"
              @click="startEditing"
            />
          </template>

          <template v-else>
            <UButton
              label="Cancelar"
              color="neutral"
              variant="outline"
              @click="cancelEditing"
            />
            <UButton
              label="Salvar Alterações"
              color="primary"
              :loading="loading"
              @click="handleSubmit"
            />
          </template>

          <UButton
            color="neutral"
            variant="ghost"
            icon="i-lucide-arrow-left"
            @click="router.push('/fiscal/nfe')"
          />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <UBreadcrumb :items="breadcrumbs" />
      </UDashboardToolbar>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <div v-else-if="!nota" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-file-text" class="size-12 text-muted mb-4" />
        <p class="text-muted">
          NF-e não encontrada.
        </p>
        <UButton
          label="Voltar"
          variant="link"
          class="mt-2"
          @click="router.push('/fiscal/nfe')"
        />
      </div>

      <div v-else-if="!isEditing" class="space-y-6">
        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-file-text text-muted" />
              Dados da Nota
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Número
              </p>
              <p class="font-medium">
                {{ nota.numero }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Série
              </p>
              <p class="font-medium">
                {{ nota.serie }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Status
              </p>
              <UBadge
                :color="statusColor[nota.status] || 'neutral'"
                variant="subtle"
                class="capitalize"
              >
                {{ nota.status }}
              </UBadge>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Natureza da Operação
              </p>
              <p class="font-medium">
                {{ nota.natureza_operacao }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Tipo de Operação
              </p>
              <p class="font-medium">
                {{ nota.tipo_operacao === '1' ? '1 - Entrada' : '2 - Saída' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Finalidade
              </p>
              <p class="font-medium">
                {{ nota.finalidade === '1' ? '1 - NF-e normal' : nota.finalidade === '2' ? '2 - NF-e complementar' : nota.finalidade === '3' ? '3 - NF-e ajuste' : '4 - Devolução' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Destinatário
              </p>
              <p class="font-medium">
                {{ nota.pessoa?.razao_social || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Data de Emissão
              </p>
              <p class="font-medium">
                {{ formatDate(nota.data_emissao) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Data de Saída/Entrada
              </p>
              <p class="font-medium">
                {{ formatDate(nota.data_saida) }}
              </p>
            </div>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-list text-muted" />
              Itens
            </h3>
          </template>

          <div v-if="!nota.itens?.length" class="text-center py-8 text-muted">
            Nenhum item.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="item in nota.itens"
              :key="item.id"
              class="bg-muted/30 rounded-lg px-4 py-3 space-y-2"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                  <p class="font-medium truncate">
                    {{ item.descricao }}
                  </p>
                  <p class="text-sm text-muted">
                    {{ item.cfop }} | NCM: {{ item.ncm || '—' }} | {{ item.unidade }}
                  </p>
                </div>
                <div class="flex items-center gap-4 text-sm ml-4 shrink-0">
                  <span>{{ item.quantidade }}x {{ formatCurrency(item.valor_unitario) }}</span>
                  <span class="font-medium">{{ formatCurrency(item.valor_total) }}</span>
                </div>
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                <div>
                  <span class="text-muted">CST: </span>
                  <span>{{ item.cst_icms }}</span>
                </div>
                <div>
                  <span class="text-muted">BC ICMS: </span>
                  <span>{{ formatCurrency(item.bc_icms) }}</span>
                </div>
                <div>
                  <span class="text-muted">Alíq: </span>
                  <span>{{ item.aliq_icms }}%</span>
                </div>
                <div>
                  <span class="text-muted">Valor ICMS: </span>
                  <span>{{ formatCurrency(item.valor_icms) }}</span>
                </div>
              </div>
            </div>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-calculator text-muted" />
              Totais
            </h3>
          </template>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Valor Produtos
              </p>
              <p class="font-medium">
                {{ formatCurrency(nota.valor_produtos) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Valor Frete
              </p>
              <p class="font-medium">
                {{ formatCurrency(nota.valor_frete) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Valor Seguro
              </p>
              <p class="font-medium">
                {{ formatCurrency(nota.valor_seguro) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Valor Desconto
              </p>
              <p class="font-medium">
                {{ formatCurrency(nota.valor_desconto) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Valor ICMS
              </p>
              <p class="font-medium">
                {{ formatCurrency(nota.valor_icms) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Valor IPI
              </p>
              <p class="font-medium">
                {{ formatCurrency(nota.valor_ipi) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Valor PIS
              </p>
              <p class="font-medium">
                {{ formatCurrency(nota.valor_pis) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Valor COFINS
              </p>
              <p class="font-medium">
                {{ formatCurrency(nota.valor_cofins) }}
              </p>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-default">
            <p class="text-sm text-muted mb-1">
              Valor Total
            </p>
            <p class="font-bold text-lg text-primary">
              {{ formatCurrency(nota.valor_total) }}
            </p>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-truck text-muted" />
              Transporte
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Forma de Frete
              </p>
              <p class="font-medium">
                {{ nota.frete_por === 0 ? '0 - Remetente' : nota.frete_por === 1 ? '1 - Destinatário' : nota.frete_por === 2 ? '2 - Terceiros' : '9 - Sem frete' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Transportadora
              </p>
              <p class="font-medium">
                {{ nota.transportadora?.razao_social || '—' }}
              </p>
            </div>
          </div>
        </UCard>

        <UCard v-if="nota.informacoes_adicionais">
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-message-square text-muted" />
              Informações Adicionais
            </h3>
          </template>

          <p class="text-sm whitespace-pre-wrap">
            {{ nota.informacoes_adicionais }}
          </p>
        </UCard>
      </div>

      <UForm
        v-else
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-6"
        @submit="onSubmit"
      >
        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-file-text text-muted" />
              Dados da Nota
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Natureza da Operação" name="natureza_operacao" required>
              <UInput v-model="state.natureza_operacao" class="w-full" placeholder="Ex: Venda de mercadoria" />
            </UFormField>
            <UFormField label="Tipo de Operação" name="tipo_operacao" required>
              <USelect
                v-model="state.tipo_operacao"
                :items="[
                  { label: '1 - Entrada', value: 1 },
                  { label: '2 - Saída', value: 2 }
                ]"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Finalidade" name="finalidade" required>
              <USelect
                v-model="state.finalidade"
                :items="[
                  { label: '1 - NF-e normal', value: 1 },
                  { label: '2 - NF-e complementar', value: 2 },
                  { label: '3 - NF-e ajuste', value: 3 },
                  { label: '4 - Devolução', value: 4 }
                ]"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Destinatário" name="pessoa_id">
              <USelect
                v-model="state.pessoa_id"
                :items="[{ label: '(Nenhum)', value: null }, ...pessoaOptions]"
                class="w-full"
                placeholder="Buscar por nome ou CPF/CNPJ..."
                @update:model-value="() => {}"
              />
            </UFormField>
            <UFormField label="Data de Emissão" name="data_emissao">
              <UInput v-model="state.data_emissao" type="date" class="w-full" />
            </UFormField>
            <UFormField label="Data de Saída/Entrada" name="data_saida">
              <UInput v-model="state.data_saida" type="date" class="w-full" />
            </UFormField>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-list text-muted" />
                Itens da Nota
              </h3>
              <UButton
                label="Adicionar Item"
                icon="i-lucide-plus"
                size="xs"
                variant="outline"
                @click="addItem"
              />
            </div>
          </template>

          <div class="space-y-3">
            <div v-for="(item, idx) in state.itens" :key="idx" class="bg-muted/30 rounded-lg p-4 space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-highlighted">Item {{ Number(idx) + 1 }}</span>
                <UButton
                  v-if="state.itens.length > 1"
                  icon="i-lucide-trash"
                  size="xs"
                  color="error"
                  variant="ghost"
                  @click="removeItem(Number(idx))"
                />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <UFormField label="Produto" :name="`itens.${idx}.produto_id`" class="sm:col-span-2">
                  <USelect
                    v-model="item.produto_id"
                    :items="[{ label: '(Manual)', value: null }, ...produtoOptions]"
                    class="w-full"
                    placeholder="Buscar por código ou descrição..."
                    @update:model-value="(v: any) => onProdutoSelect(Number(idx), v)"
                  />
                </UFormField>
                <UFormField label="Quantidade" :name="`itens.${idx}.quantidade`">
                  <UInput
                    v-model="item.quantidade"
                    type="number"
                    step="0.0001"
                    class="w-full"
                    @update:model-value="() => onQtyOrPriceChange(Number(idx))"
                  />
                </UFormField>
                <UFormField label="Unidade" :name="`itens.${idx}.unidade`">
                  <UInput v-model="item.unidade" class="w-full" placeholder="UN" />
                </UFormField>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <UFormField label="Valor Unitário" :name="`itens.${idx}.valor_unitario`">
                  <UInput
                    v-model="item.valor_unitario"
                    type="number"
                    step="0.01"
                    class="w-full"
                    @update:model-value="() => onQtyOrPriceChange(Number(idx))"
                  />
                </UFormField>
                <UFormField label="Valor Total">
                  <UInput
                    :model-value="(item.quantidade * item.valor_unitario).toFixed(2)"
                    class="w-full"
                    disabled
                  />
                </UFormField>
                <UFormField label="CFOP" :name="`itens.${idx}.cfop`">
                  <UInput v-model="item.cfop" class="w-full" placeholder="5102" />
                </UFormField>
                <UFormField label="NCM" :name="`itens.${idx}.ncm`">
                  <UInput v-model="item.ncm" class="w-full" placeholder="0000.00.00" />
                </UFormField>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <UFormField label="CST ICMS" :name="`itens.${idx}.cst_icms`">
                  <USelect
                    v-model="item.cst_icms"
                    :items="cstIcmsOptions"
                    class="w-full"
                    placeholder="Selecione..."
                  />
                </UFormField>
                <UFormField label="Origem" :name="`itens.${idx}.origem`">
                  <USelect
                    v-model="item.origem"
                    :items="origemOptions"
                    class="w-full"
                    placeholder="Selecione..."
                  />
                </UFormField>
                <UFormField label="Alíquota ICMS (%)" :name="`itens.${idx}.aliq_icms`">
                  <UInput
                    v-model="item.aliq_icms"
                    type="number"
                    step="0.01"
                    class="w-full"
                    @update:model-value="() => onAliqChange(Number(idx))"
                  />
                </UFormField>
                <UFormField label="Valor ICMS">
                  <UInput
                    :model-value="item.valor_icms.toFixed(2)"
                    class="w-full"
                    disabled
                  />
                </UFormField>
              </div>
            </div>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-calculator text-muted" />
              Totais
            </h3>
          </template>

          <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <UFormField label="Valor Produtos">
              <UInput :model-value="formatCurrency(valorProdutos)" class="w-full" disabled />
            </UFormField>
            <UFormField label="Valor Frete" name="valor_frete">
              <UInput
                v-model="state.valor_frete"
                type="number"
                step="0.01"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Valor Seguro" name="valor_seguro">
              <UInput
                v-model="state.valor_seguro"
                type="number"
                step="0.01"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Valor Desconto" name="valor_desconto">
              <UInput
                v-model="state.valor_desconto"
                type="number"
                step="0.01"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Valor Total">
              <UInput :model-value="formatCurrency(valorTotal)" class="w-full" disabled />
            </UFormField>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-calculator text-muted" />
              Transporte
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Forma de Frete" name="frete_por">
              <USelect
                v-model="state.frete_por"
                :items="[
                  { label: '0 - Remetente', value: 0 },
                  { label: '1 - Destinatário', value: 1 },
                  { label: '2 - Terceiros', value: 2 },
                  { label: '9 - Sem frete', value: 9 }
                ]"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Transportadora" name="transportadora_id">
              <USelect
                v-model="state.transportadora_id"
                :items="[{ label: '(Nenhuma)', value: null }, ...transportadoraOptions]"
                class="w-full"
                placeholder="Buscar por nome ou CNPJ..."
                @update:model-value="() => {}"
              />
            </UFormField>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-message-square text-muted" />
              Informações Adicionais
            </h3>
          </template>

          <UFormField label="Informações Adicionais" name="informacoes_adicionais">
            <UTextarea
              v-model="state.informacoes_adicionais"
              class="w-full"
              placeholder="Informações complementares da nota fiscal..."
              :rows="3"
            />
          </UFormField>
        </UCard>
      </UForm>
    </template>
  </UDashboardPanel>

  <NotasFiscaisCancelModal
    v-if="nota"
    ref="cancelModalRef"
    :nota="nota"
    @cancelled="onCancelled"
  />
  <NotasFiscaisDeleteModal
    v-if="nota"
    ref="deleteModalRef"
    :nota="nota"
    @deleted="onDeleted"
  />
</template>
