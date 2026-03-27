<script setup lang="ts">
import type { BreadcrumbItem, FormSubmitEvent } from '@nuxt/ui'
import type { Pedido } from '~/types'
import type { PedidoSchema } from '~/composables/usePedidoForm'
import { formatCurrency } from '~/utils'

import { UButton, UBadge, UDropdownMenu } from '#components'

const {
  schema,
  pessoaOptions,
  produtoOptions,
  defaultState,
  addItem,
  removeItem,
  onProdutoSelect,
  calcTotalItens,
  calcTotalGeral,
  populateFromEntity
} = usePedidoForm()

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { put } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const pedidoId = computed(() => Number(route.params.id))
const url = computed(() => `/pedidos/${pedidoId.value}`)

const { data: pedido, status, refresh } = useApi<Pedido>(url, { lazy: false })

const isEditing = ref(false)
const loading = ref(false)
const deletingPedido = ref<Pedido | null>(null)

const state = reactive(defaultState())

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { label: 'Comercial', icon: 'i-lucide-briefcase', to: '/comercial/pedidos' },
  { label: 'Pedidos', icon: 'i-lucide-shopping-cart', to: '/comercial/pedidos' },
  { label: pedido.value ? `#${pedido.value.numero}` : '...' }
])

const statusColor: Record<string, 'neutral' | 'info' | 'success' | 'error' | 'warning'> = {
  pendente: 'warning',
  confirmado: 'info',
  faturado: 'success',
  cancelado: 'error'
}

const totalItens = computed(() => calcTotalItens(state))
const totalGeral = computed(() => calcTotalGeral(state))

watch(pedido, (val) => {
  if (val && !isEditing.value) {
    populateFromEntity(state, val)
  }
}, { immediate: true })

function startEditing() {
  if (pedido.value) {
    populateFromEntity(state, pedido.value)
  }
  isEditing.value = true
}

function cancelEditing() {
  if (pedido.value) {
    populateFromEntity(state, pedido.value)
  }
  isEditing.value = false
}

async function onSubmit(event: FormSubmitEvent<PedidoSchema>) {
  loading.value = true
  try {
    await put(url.value, event.data)
    toast.add({ title: 'Pedido atualizado', color: 'success' })
    isEditing.value = false
    refresh()
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao atualizar.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}

const actions = computed(() => [
  {
    type: 'label' as const,
    label: 'Ações'
  },
  {
    label: 'Gerar NF-e',
    icon: 'i-lucide-file-text',
    onSelect() {
      toast.add({ title: 'Em desenvolvimento', description: 'A geração de NF-e será implementada em breve.' })
    }
  },
  {
    type: 'separator' as const
  },
  {
    label: 'Excluir pedido',
    icon: 'i-lucide-trash',
    color: 'error' as const,
    onSelect() {
      if (pedido.value) deletingPedido.value = pedido.value
    }
  }
])

function formatDate(date: string | null | undefined) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('pt-BR')
}

function onDeleted() {
  deletingPedido.value = null
  router.push('/comercial/pedidos')
}
</script>

<template>
  <UDashboardPanel id="pedido-detalhe">
    <template #header>
      <UDashboardNavbar title="Pedido">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UBadge
            v-if="pedido"
            :color="statusColor[pedido.status] || 'neutral'"
            variant="subtle"
            class="capitalize"
          >
            {{ pedido.status }}
          </UBadge>

          <template v-if="!isEditing">
            <UDropdownMenu
              v-if="pedido"
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
              :disabled="!pedido || pedido.status === 'faturado'"
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
            @click="router.push('/comercial/pedidos')"
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

      <div v-else-if="!pedido" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-shopping-cart" class="size-12 text-muted mb-4" />
        <p class="text-muted">
          Pedido não encontrado.
        </p>
        <UButton
          label="Voltar"
          variant="link"
          class="mt-2"
          @click="router.push('/comercial/pedidos')"
        />
      </div>

      <div v-else-if="!isEditing" class="space-y-6">
        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-shopping-cart text-muted" />
              Dados do Pedido
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Nº Pedido
              </p>
              <p class="font-medium">
                {{ pedido.numero }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Cliente
              </p>
              <p class="font-medium">
                {{ pedido.pessoa?.razao_social || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Data
              </p>
              <p class="font-medium">
                {{ formatDate(pedido.data) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Status
              </p>
              <UBadge
                :color="statusColor[pedido.status] || 'neutral'"
                variant="subtle"
                class="capitalize"
              >
                {{ pedido.status }}
              </UBadge>
            </div>
            <div v-if="pedido.orcamento">
              <p class="text-sm text-muted mb-1">
                Orçamento de Origem
              </p>
              <UButton
                :label="`#${pedido.orcamento.numero}`"
                variant="link"
                size="sm"
                @click="router.push(`/comercial/orcamentos/${pedido.orcamento.id}`)"
              />
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

          <div v-if="!pedido.itens?.length" class="text-center py-8 text-muted">
            Nenhum item.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="item in pedido.itens"
              :key="item.id"
              class="flex items-center justify-between bg-muted/30 rounded-lg px-4 py-3"
            >
              <div class="flex-1 min-w-0">
                <p class="font-medium truncate">
                  {{ item.descricao }}
                </p>
                <p v-if="item.produto" class="text-sm text-muted">
                  {{ item.produto.codigo }}
                </p>
              </div>
              <div class="flex items-center gap-6 text-sm ml-4 shrink-0">
                <span>{{ item.quantidade }}x {{ formatCurrency(item.valor_unitario) }}</span>
                <span v-if="item.desconto" class="text-muted">Desc: {{ formatCurrency(item.desconto) }}</span>
                <span class="font-medium">{{ formatCurrency(item.valor_total || (item.quantidade * item.valor_unitario) - (item.desconto || 0)) }}</span>
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

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Desconto
              </p>
              <p class="font-medium">
                {{ formatCurrency(pedido.desconto) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Subtotal
              </p>
              <p class="font-medium">
                {{ formatCurrency(Number(pedido.valor_total) + Number(pedido.desconto)) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Total
              </p>
              <p class="font-bold text-lg text-primary">
                {{ formatCurrency(pedido.valor_total) }}
              </p>
            </div>
          </div>
        </UCard>

        <UCard v-if="pedido.observacoes">
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-message-square text-muted" />
              Observações
            </h3>
          </template>

          <p class="text-sm whitespace-pre-wrap">
            {{ pedido.observacoes }}
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
              <span class="i-lucide-shopping-cart text-muted" />
              Dados do Pedido
            </h3>
          </template>

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
        </UCard>

        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-list text-muted" />
                Itens do Pedido
              </h3>
              <UButton
                label="Adicionar Item"
                icon="i-lucide-plus"
                size="xs"
                variant="outline"
                @click="addItem(state)"
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
                  @click="removeItem(state, Number(idx))"
                />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
                <UFormField label="Produto" :name="`itens.${idx}.produto_id`" class="sm:col-span-2">
                  <USelect
                    v-model="item.produto_id"
                    :items="[{ label: '(Manual)', value: null }, ...produtoOptions]"
                    class="w-full"
                    placeholder="Selecione..."
                    @update:model-value="(v: any) => onProdutoSelect(Number(idx), v, state)"
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
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-calculator text-muted" />
              Totais
            </h3>
          </template>

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
        </UCard>

        <UCard>
          <UFormField label="Observações" name="observacoes">
            <UTextarea
              v-model="state.observacoes"
              class="w-full"
              placeholder="Informações adicionais..."
              :rows="3"
            />
          </UFormField>
        </UCard>
      </UForm>
    </template>
  </UDashboardPanel>

  <PedidosDeleteModal
    v-if="deletingPedido"
    :pedido="deletingPedido"
    @deleted="onDeleted"
  />
</template>
