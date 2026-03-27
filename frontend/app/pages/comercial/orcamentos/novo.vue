<script setup lang="ts">
import type { BreadcrumbItem, FormSubmitEvent } from '@nuxt/ui'
import type { Orcamento } from '~/types'
import type { OrcamentoSchema } from '~/composables/useOrcamentoForm'
import { formatCurrency } from '~/utils'

import { UButton } from '#components'

const {
  schema,
  pessoaOptions,
  produtoOptions,
  defaultState,
  addItem,
  removeItem,
  onProdutoSelect,
  calcTotalItens,
  calcTotalGeral
} = useOrcamentoForm()

const router = useRouter()
const toast = useToast()
const { post } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const loading = ref(false)
const state = reactive(defaultState())

const totalItens = computed(() => calcTotalItens(state))
const totalGeral = computed(() => calcTotalGeral(state))

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'Comercial', icon: 'i-lucide-briefcase', to: '/comercial/orcamentos' },
  { label: 'Orçamentos', icon: 'i-lucide-file-text', to: '/comercial/orcamentos' },
  { label: 'Novo' }
]

async function onSubmit(event: FormSubmitEvent<OrcamentoSchema>) {
  loading.value = true
  try {
    const orcamento = await post<Orcamento>('/orcamentos', event.data)
    toast.add({ title: 'Orçamento criado', description: `Orçamento #${orcamento.numero} criado com sucesso.`, color: 'success' })
    router.push(`/comercial/orcamentos/${orcamento.id}`)
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao criar orçamento.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}
</script>

<template>
  <UDashboardPanel id="orcamento-novo">
    <template #header>
      <UDashboardNavbar title="Novo Orçamento">
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
            @click="router.push('/comercial/orcamentos')"
          />
          <UButton
            label="Criar Orçamento"
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
</template>
