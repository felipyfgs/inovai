<script setup lang="ts">
import type { BreadcrumbItem, FormSubmitEvent } from '@nuxt/ui'
import type { Pessoa, Produto, Transportadora, PaginatedResponse } from '~/types'
import { formatCurrency } from '~/utils'

import { UButton } from '#components'

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

const router = useRouter()
const toast = useToast()
const { createNfe } = useNfe()
const { currentCompany } = useCurrentCompany()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const loading = ref(false)

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
  natureza_operacao: 'Venda de mercadoria',
  tipo_operacao: 2,
  finalidade: 1,
  pessoa_id: null,
  data_emissao: new Date().toISOString().split('T')[0] ?? '',
  data_saida: '',
  transportadora_id: null,
  frete_por: 9,
  valor_frete: 0,
  valor_seguro: 0,
  valor_desconto: 0,
  informacoes_adicionais: '',
  itens: [createEmptyItem()]
})

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

const valorProdutos = computed(() => {
  return state.itens.reduce((sum, item) => sum + (item.quantidade * item.valor_unitario), 0)
})

const valorIcms = computed(() => {
  return state.itens.reduce((sum, item) => sum + (item.valor_icms || 0), 0)
})

const valorTotal = computed(() => {
  return valorProdutos.value + state.valor_frete + state.valor_seguro - state.valor_desconto
})

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

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'Fiscal', icon: 'i-lucide-file-text', to: '/fiscal/nfe' },
  { label: 'NF-e', icon: 'i-lucide-file-text', to: '/fiscal/nfe' },
  { label: 'Nova NF-e' }
]

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    const response = await createNfe(buildPayload(event))
    toast.add({ title: 'NF-e criada', color: 'success' })
    router.push(`/fiscal/nfe/${response.id}`)
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao criar NF-e.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}
</script>

<template>
  <UDashboardPanel id="nfe-novo">
    <template #header>
      <UDashboardNavbar title="Nova NF-e">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UBreadcrumb :items="breadcrumbs" />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <div />

        <template #right>
          <UButton
            label="Cancelar"
            color="neutral"
            variant="outline"
            @click="router.push('/fiscal/nfe')"
          />
          <UButton
            label="Criar NF-e"
            color="primary"
            :loading="loading"
            @click="handleSubmit"
          />
        </template>
      </UDashboardToolbar>
    </template>

    <template #body>
      <UForm
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
</template>
