<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Produto, PaginatedResponse } from '~/types'
import { formatCurrency } from '~/utils'

const emit = defineEmits<{ created: [] }>()

const csosnOptions = [
  { label: '00 - Tributada integralmente', value: '00' },
  { label: '40 - Isenta', value: '40' },
  { label: '41 - Não tributada', value: '41' },
  { label: '50 - Suspensão', value: '50' },
  { label: '60 - ICMS cobrado por ST', value: '60' },
  { label: '102 - Tributada pelo SN sem permissão de crédito', value: '102' },
  { label: '103 - Tributada pelo SN com permissão de crédito e cobrança de ICMS por ST', value: '103' },
  { label: '300 - Isenta ou não tributada com cobrança de ICMS por ST', value: '300' },
  { label: '400 - Isenta', value: '400' },
  { label: '500 - ICMS cobrado por ST pelo SN', value: '500' },
  { label: '900 - Outros', value: '900' }
]

const origemOptions = [
  { label: '0 - Nacional', value: 0 },
  { label: '1 - Estrangeira - Importação direta', value: 1 },
  { label: '2 - Estrangeira - Adquirida no mercado interno', value: 2 },
  { label: '3 - Nacional, Conteúdo de Importação superior a 40%', value: 3 },
  { label: '4 - Nacional, processos produtivos básicos', value: 4 },
  { label: '5 - Nacional, Conteúdo de Importação inferior ou igual a 40%', value: 5 },
  { label: '6 - Estrangeira - Importação direta, sem similar nacional', value: 6 },
  { label: '7 - Estrangeira - Mercado interno, sem similar nacional', value: 7 },
  { label: '8 - Nacional, Conteúdo de Importação superior a 70%', value: 8 }
]

const formaPagamentoOptions = [
  { label: '01 - Dinheiro', value: '01' },
  { label: '02 - Cheque', value: '02' },
  { label: '03 - Cartão Crédito', value: '03' },
  { label: '04 - Cartão Débito', value: '04' },
  { label: '05 - Crédito Loja', value: '05' },
  { label: '10 - Vale Alimentação', value: '10' },
  { label: '11 - Vale Refeição', value: '11' },
  { label: '12 - Vale Presente', value: '12' },
  { label: '13 - Vale Combustível', value: '13' },
  { label: '99 - Outros', value: '99' }
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
  cpf_consumidor: z.string().optional(),
  forma_pagamento: z.string().min(1, 'Obrigatório'),
  valor_desconto: z.coerce.number().min(0).default(0),
  informacoes_adicionais: z.string().optional(),
  itens: z.array(itemSchema).min(1, 'Adicione pelo menos 1 item')
})

type Schema = z.output<typeof schema>

interface NfceItem {
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

interface NfceState {
  natureza_operacao: string
  cpf_consumidor: string
  forma_pagamento: string
  valor_desconto: number
  informacoes_adicionais: string
  itens: NfceItem[]
}

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const { currentCompany } = useCurrentCompany()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')
const produtoSearch = ref('')

const { data: produtosData } = useApi<PaginatedResponse<Produto>>('/produtos', {
  lazy: true,
  watch: [computed(() => currentCompany.value?.id)]
})

const produtoOptions = computed(() => {
  const items = (produtosData.value?.data || []).filter((p) => {
    if (!produtoSearch.value) return true
    const s = produtoSearch.value.toLowerCase()
    return p.descricao.toLowerCase().includes(s) || (p.codigo && p.codigo.toLowerCase().includes(s))
  })
  return items.map(p => ({ label: `${p.codigo ? `${p.codigo} - ` : ''}${p.descricao}`, value: p.id }))
})

function createEmptyItem(): NfceItem {
  return {
    produto_id: null, descricao: '', ncm: '', cfop: '5102', unidade: '',
    quantidade: 1, valor_unitario: 0, cst_icms: '102', origem: 0,
    bc_icms: 0, aliq_icms: 0, valor_icms: 0
  }
}

const state = reactive<NfceState>({
  natureza_operacao: 'Venda de Mercadoria',
  cpf_consumidor: '',
  forma_pagamento: '05',
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
  recalcItemIcms(index)
}

const valorProdutos = computed(() => {
  return state.itens.reduce((sum, item) => sum + (item.quantidade * item.valor_unitario), 0)
})

const valorIcms = computed(() => {
  return state.itens.reduce((sum, item) => sum + (item.valor_icms || 0), 0)
})

const valorTotal = computed(() => {
  return valorProdutos.value - state.valor_desconto
})

function resetForm() {
  Object.assign(state, {
    natureza_operacao: 'Venda de Mercadoria',
    cpf_consumidor: '',
    forma_pagamento: '05',
    valor_desconto: 0,
    informacoes_adicionais: '',
    itens: [createEmptyItem()]
  })
  produtoSearch.value = ''
}

function buildPayload(event: FormSubmitEvent<Schema>) {
  const data: Record<string, unknown> = { ...event.data }
  data.modelo = 65
  data.tipo_operacao = 2
  data.finalidade = 1
  data.frete_por = 9
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
    await post('/nfes', buildPayload(event))
    toast.add({ title: 'NFC-e criada', color: 'success' })
    open.value = false
    resetForm()
    emit('created')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao criar NFC-e.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Nova NFC-e"
    description="Emitir uma nova Nota Fiscal ao Consumidor Eletrônica"
    :ui="{ content: 'w-full sm:max-w-5xl', footer: 'justify-end' }"
  >
    <UButton label="Nova NFC-e" icon="i-lucide-plus" />

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-6"
        @submit="onSubmit"
      >
        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-file-text text-muted" />
            Identificação
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Natureza da Operação" name="natureza_operacao" required>
              <UInput v-model="state.natureza_operacao" class="w-full" placeholder="Ex: Venda de Mercadoria" />
            </UFormField>
            <UFormField label="Forma de Pagamento" name="forma_pagamento" required>
              <USelect
                v-model="state.forma_pagamento"
                :items="formaPagamentoOptions"
                class="w-full"
              />
            </UFormField>
            <UFormField label="CPF do Consumidor" name="cpf_consumidor">
              <UInput
                v-model="state.cpf_consumidor"
                class="w-full"
                placeholder="Opcional - CPF do consumidor"
                maxlength="14"
              />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <div>
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-list text-muted" />
              Itens da Venda
            </h3>
            <UButton
              label="Adicionar Item"
              icon="i-lucide-plus"
              size="xs"
              variant="outline"
              @click="addItem"
            />
          </div>

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
                <UFormField label="CSOSN" :name="`itens.${idx}.cst_icms`">
                  <USelect
                    v-model="item.cst_icms"
                    :items="csosnOptions"
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
        </div>

        <USeparator />

        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-calculator text-muted" />
            Totais
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <UFormField label="Valor Produtos">
              <UInput :model-value="formatCurrency(valorProdutos)" class="w-full" disabled />
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
        </div>

        <USeparator />

        <UFormField label="Informações Adicionais" name="informacoes_adicionais">
          <UTextarea
            v-model="state.informacoes_adicionais"
            class="w-full"
            placeholder="Informações complementares da NFC-e..."
            :rows="3"
          />
        </UFormField>
      </UForm>
    </template>

    <template #footer="{ close }">
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="close"
      />
      <UButton
        label="Criar NFC-e"
        color="primary"
        :loading="loading"
        @click="handleSubmit"
      />
    </template>
  </UModal>
</template>
