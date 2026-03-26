<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Pessoa, Produto, PaginatedResponse } from '~/types'
import { formatCurrency } from '~/utils'

const emit = defineEmits<{ created: [] }>()

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

interface PedidoItem {
  produto_id: number | null
  descricao: string
  quantidade: number
  valor_unitario: number
  desconto: number
}

interface PedidoState {
  pessoa_id: number | null
  data: string
  observacoes: string
  desconto: number
  itens: PedidoItem[]
}

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const { currentCompany } = useCurrentCompany()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const { data: pessoasData } = useApi<PaginatedResponse<Pessoa>>('/pessoas', { lazy: true, watch: [computed(() => currentCompany.value?.id)] })
const { data: produtosData } = useApi<PaginatedResponse<Produto>>('/produtos', { lazy: true, watch: [computed(() => currentCompany.value?.id)] })

const pessoaOptions = computed(() => (pessoasData.value?.data || []).map(p => ({ label: p.razao_social, value: p.id })))
const produtoOptions = computed(() => (produtosData.value?.data || []).map(p => ({ label: p.descricao, value: p.id })))

const state = reactive<PedidoState>({
  pessoa_id: null,
  data: new Date().toISOString().split('T')[0] as string,
  observacoes: '',
  desconto: 0,
  itens: [{ produto_id: null, descricao: '', quantidade: 1, valor_unitario: 0, desconto: 0 }]
})

function addItem() {
  state.itens.push({ produto_id: null, descricao: '', quantidade: 1, valor_unitario: 0, desconto: 0 })
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
    item.valor_unitario = Number(produto.preco_venda)
  }
}

const totalItens = computed(() => {
  return state.itens.reduce((sum: number, item: PedidoItem) => {
    return sum + (item.quantidade * item.valor_unitario) - (item.desconto || 0)
  }, 0)
})

const totalGeral = computed(() => totalItens.value - (state.desconto || 0))

function resetForm() {
  Object.assign(state, {
    pessoa_id: null, data: new Date().toISOString().split('T')[0],
    observacoes: '', desconto: 0,
    itens: [{ produto_id: null, descricao: '', quantidade: 1, valor_unitario: 0, desconto: 0 }]
  })
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await post('/pedidos', event.data)
    toast.add({ title: 'Pedido criado', color: 'success' })
    open.value = false
    resetForm()
    emit('created')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao criar.', color: 'error' })
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
    title="Novo Pedido"
    description="Criar um novo pedido com itens"
    :ui="{ content: 'w-full sm:max-w-5xl', footer: 'justify-end' }"
  >
    <UButton label="Novo Pedido" icon="i-lucide-plus" />

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-6"
        @submit="onSubmit"
      >
        <!-- Seção: Dados do Pedido -->
        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-shopping-cart text-muted" />
            Dados do Pedido
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Cliente" name="pessoa_id">
              <USelect
                v-model="state.pessoa_id"
                :items="[{ label: '(Nenhum)', value: null }, ...pessoaOptions]"
                class="w-full"
                placeholder="Selecione o cliente..."
              />
            </UFormField>
            <UFormField label="Data" name="data" required>
              <UInput v-model="state.data" type="date" class="w-full" />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <!-- Seção: Itens -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-list text-muted" />
              Itens do Pedido
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
              <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
                <UFormField label="Produto" :name="`itens.${idx}.produto_id`" class="sm:col-span-2">
                  <USelect
                    v-model="item.produto_id"
                    :items="[{ label: '(Manual)', value: null }, ...produtoOptions]"
                    class="w-full"
                    placeholder="Selecione..."
                    @update:model-value="(v: any) => onProdutoSelect(Number(idx), v)"
                  />
                </UFormField>
                <UFormField
                  label="Descrição"
                  :name="`itens.${idx}.descricao`"
                  required
                  class="sm:col-span-2"
                >
                  <UInput v-model="item.descricao" class="w-full" />
                </UFormField>
                <UFormField label="Quantidade" :name="`itens.${idx}.quantidade`">
                  <UInput
                    v-model="item.quantidade"
                    type="number"
                    step="0.01"
                    class="w-full"
                  />
                </UFormField>
                <UFormField label="Valor Unit." :name="`itens.${idx}.valor_unitario`">
                  <UInput
                    v-model="item.valor_unitario"
                    type="number"
                    step="0.01"
                    class="w-full"
                  />
                </UFormField>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
                <div class="sm:col-span-4" />
                <UFormField label="Desconto (R$)" :name="`itens.${idx}.desconto`" class="sm:col-span-2">
                  <UInput
                    v-model="item.desconto"
                    type="number"
                    step="0.01"
                    class="w-full"
                  />
                </UFormField>
              </div>
            </div>
          </div>
        </div>

        <USeparator />

        <!-- Seção: Totais -->
        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-calculator text-muted" />
            Totais
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <UFormField label="Desconto Geral (R$)" name="desconto">
              <UInput
                v-model="state.desconto"
                type="number"
                step="0.01"
                class="w-full"
              />
            </UFormField>
            <div class="flex items-end pb-2">
              <p class="text-sm text-muted">
                Subtotal: <span class="font-medium text-highlighted">{{ formatCurrency(totalItens) }}</span>
              </p>
            </div>
            <div class="flex items-end pb-2">
              <p class="text-sm">
                Total: <span class="font-bold text-lg text-primary">{{ formatCurrency(totalGeral) }}</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Seção: Observações -->
        <UFormField label="Observações" name="observacoes">
          <UTextarea
            v-model="state.observacoes"
            class="w-full"
            placeholder="Informações adicionais..."
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
        label="Criar Pedido"
        color="primary"
        :loading="loading"
        @click="handleSubmit"
      />
    </template>
  </UModal>
</template>
