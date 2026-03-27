<script setup lang="ts">
import type { BreadcrumbItem, FormSubmitEvent } from '@nuxt/ui'
import type { Orcamento } from '~/types'
import type { OrcamentoSchema } from '~/composables/useOrcamentoForm'
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
} = useOrcamentoForm()

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { put, post: apiPost } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const orcamentoId = computed(() => Number(route.params.id))
const url = computed(() => `/orcamentos/${orcamentoId.value}`)

const { data: orcamento, status, refresh } = useApi<Orcamento>(url, { lazy: false })

const isEditing = ref(false)
const loading = ref(false)
const deletingOrcamento = ref<Orcamento | null>(null)

const state = reactive(defaultState())

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { label: 'Comercial', icon: 'i-lucide-briefcase', to: '/comercial/orcamentos' },
  { label: 'Orçamentos', icon: 'i-lucide-file-text', to: '/comercial/orcamentos' },
  { label: orcamento.value ? `#${orcamento.value.numero}` : '...' }
])

const statusColor: Record<string, 'neutral' | 'info' | 'success' | 'error' | 'warning'> = {
  rascunho: 'neutral',
  enviado: 'info',
  aprovado: 'success',
  recusado: 'error',
  convertido: 'warning'
}

const totalItens = computed(() => calcTotalItens(state))
const totalGeral = computed(() => calcTotalGeral(state))

watch(orcamento, (val) => {
  if (val && !isEditing.value) {
    populateFromEntity(state, val)
  }
}, { immediate: true })

function startEditing() {
  if (orcamento.value) {
    populateFromEntity(state, orcamento.value)
  }
  isEditing.value = true
}

function cancelEditing() {
  if (orcamento.value) {
    populateFromEntity(state, orcamento.value)
  }
  isEditing.value = false
}

async function onSubmit(event: FormSubmitEvent<OrcamentoSchema>) {
  loading.value = true
  try {
    await put(url.value, event.data)
    toast.add({ title: 'Orçamento atualizado', color: 'success' })
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

async function convertToPedido() {
  if (!orcamento.value) return
  try {
    await apiPost(`/orcamentos/${orcamento.value.id}/converter`)
    toast.add({ title: 'Convertido', description: `Orçamento #${orcamento.value.numero} convertido em pedido.`, color: 'success' })
    refresh()
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao converter.', color: 'error' })
  }
}

const actions = computed(() => [
  {
    type: 'label' as const,
    label: 'Ações'
  },
  {
    label: 'Converter em pedido',
    icon: 'i-lucide-arrow-right-circle',
    disabled: orcamento.value?.status === 'convertido',
    onSelect: convertToPedido
  },
  {
    type: 'separator' as const
  },
  {
    label: 'Excluir orçamento',
    icon: 'i-lucide-trash',
    color: 'error' as const,
    onSelect() {
      if (orcamento.value) deletingOrcamento.value = orcamento.value
    }
  }
])

function formatDate(date: string | null | undefined) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('pt-BR')
}

function onDeleted() {
  deletingOrcamento.value = null
  router.push('/comercial/orcamentos')
}
</script>

<template>
  <UDashboardPanel id="orcamento-detalhe">
    <template #header>
      <UDashboardNavbar title="Orçamento">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UBadge
            v-if="orcamento"
            :color="statusColor[orcamento.status] || 'neutral'"
            variant="subtle"
            class="capitalize"
          >
            {{ orcamento.status }}
          </UBadge>

          <template v-if="!isEditing">
            <UDropdownMenu
              v-if="orcamento"
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
              :disabled="!orcamento || orcamento.status === 'convertido'"
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
            @click="router.push('/comercial/orcamentos')"
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

      <div v-else-if="!orcamento" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-file-text" class="size-12 text-muted mb-4" />
        <p class="text-muted">
          Orçamento não encontrado.
        </p>
        <UButton
          label="Voltar"
          variant="link"
          class="mt-2"
          @click="router.push('/comercial/orcamentos')"
        />
      </div>

      <div v-else-if="!isEditing" class="space-y-6">
        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-file-text text-muted" />
              Dados do Orçamento
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Nº Orçamento
              </p>
              <p class="font-medium">
                {{ orcamento.numero }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Cliente
              </p>
              <p class="font-medium">
                {{ orcamento.pessoa?.razao_social || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Data
              </p>
              <p class="font-medium">
                {{ formatDate(orcamento.data) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Validade
              </p>
              <p class="font-medium">
                {{ formatDate(orcamento.validade) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Status
              </p>
              <UBadge
                :color="statusColor[orcamento.status] || 'neutral'"
                variant="subtle"
                class="capitalize"
              >
                {{ orcamento.status }}
              </UBadge>
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

          <div v-if="!orcamento.itens?.length" class="text-center py-8 text-muted">
            Nenhum item.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="item in orcamento.itens"
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
                {{ formatCurrency(orcamento.desconto) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Subtotal
              </p>
              <p class="font-medium">
                {{ formatCurrency(Number(orcamento.valor_total) + Number(orcamento.desconto)) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Total
              </p>
              <p class="font-bold text-lg text-primary">
                {{ formatCurrency(orcamento.valor_total) }}
              </p>
            </div>
          </div>
        </UCard>

        <UCard v-if="orcamento.observacoes">
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-message-square text-muted" />
              Observações
            </h3>
          </template>

          <p class="text-sm whitespace-pre-wrap">
            {{ orcamento.observacoes }}
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
              Dados do Orçamento
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
            <UFormField label="Validade" name="validade">
              <UInput v-model="state.validade" type="date" class="w-full" />
            </UFormField>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-list text-muted" />
                Itens do Orçamento
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

  <OrcamentosDeleteModal
    v-if="deletingOrcamento"
    :orcamento="deletingOrcamento"
    @deleted="onDeleted"
  />
</template>
