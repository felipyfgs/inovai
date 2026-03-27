<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import * as z from 'zod'
import type { BreadcrumbItem, FormSubmitEvent } from '@nuxt/ui'
import type { Mdfe } from '~/types'
import { formatCurrency } from '~/utils'

import { UButton, UBadge } from '#components'

const props = defineProps<{ id: string }>()

const ufOptions = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
  'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
  'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
].map(uf => ({ label: uf, value: uf }))

const documentoSchema = z.object({
  tipo: z.enum(['nfe', 'cte']),
  chave: z.string().length(44, 'Chave deve ter 44 caracteres')
})

const schema = z.object({
  modal: z.number(),
  data_emissao: z.string(),
  uf_carregamento: z.string().min(1, 'Obrigatório'),
  uf_descarregamento: z.string().min(1, 'Obrigatório'),
  veiculo_placa: z.string().max(8, 'Máximo 8 caracteres'),
  motorista_cpf: z.string(),
  motorista_nome: z.string(),
  uf_percurso: z.array(z.string()).optional(),
  valor_carga: z.coerce.number().min(0),
  peso_bruto: z.coerce.number().min(0),
  documentos: z.array(documentoSchema).min(1, 'Adicione pelo menos um documento'),
  informacoes_adicionais: z.string().optional()
})

type Schema = z.output<typeof schema>

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const isEditing = ref(false)
const formRef = useTemplateRef('formRef')
const { updateMdfe, extractMessage } = useMdfe()
const deleteModal = useTemplateRef('deleteModal')
const cancelModal = useTemplateRef('cancelModal')

const { data: mdfe, status, refresh } = useApi<Mdfe>(`/mdfes/${props.id}`, {
  lazy: false
})

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { label: 'Fiscal', icon: 'i-lucide-file-text', to: '/fiscal/mdfe' },
  { label: 'MDF-e', icon: 'i-lucide-truck', to: '/fiscal/mdfe' },
  { label: mdfe.value ? `#${mdfe.value.numero}` : '...' }
])

const statusColor = computed(() => {
  const map: Record<string, 'neutral' | 'info' | 'success' | 'error'> = {
    rascunho: 'neutral',
    assinada: 'info',
    transmitida: 'info',
    autorizada: 'success',
    rejeitada: 'error',
    cancelada: 'error',
    encerrada: 'neutral'
  }
  return map[mdfe.value?.status || ''] || 'neutral'
})

const state = reactive<Partial<Schema>>({})

watch(isEditing, (val) => {
  if (val && mdfe.value) {
    Object.assign(state, {
      modal: mdfe.value.modal,
      data_emissao: mdfe.value.data_emissao?.split('T')[0] || '',
      uf_carregamento: mdfe.value.uf_carregamento,
      uf_descarregamento: mdfe.value.uf_descarregamento,
      veiculo_placa: mdfe.value.veiculo_placa || '',
      motorista_cpf: mdfe.value.motorista_cpf || '',
      motorista_nome: mdfe.value.motorista_nome || '',
      uf_percurso: mdfe.value.uf_percurso ? [...mdfe.value.uf_percurso] : [],
      valor_carga: mdfe.value.valor_carga,
      peso_bruto: mdfe.value.peso_bruto,
      documentos: mdfe.value.documentos?.length
        ? mdfe.value.documentos.map(d => ({ tipo: d.tipo, chave: d.chave }))
        : [{ tipo: 'nfe' as const, chave: '' }],
      informacoes_adicionais: mdfe.value.informacoes_adicionais || ''
    })
  }
})

function addDocumento() {
  state.documentos!.push({ tipo: 'nfe', chave: '' })
}

function removeDocumento(index: number) {
  if (state.documentos!.length > 1) {
    state.documentos!.splice(index, 1)
  }
}

function addUfPercurso() {
  state.uf_percurso!.push('')
}

function removeUfPercurso(index: number) {
  state.uf_percurso!.splice(index, 1)
}

function copyChave() {
  if (mdfe.value?.chave) {
    navigator.clipboard.writeText(mdfe.value.chave)
    toast.add({ title: 'Copiado', description: 'Chave copiada' })
  }
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await updateMdfe(Number(props.id), event.data)
    toast.add({ title: 'MDF-e atualizado', description: 'MDF-e atualizado com sucesso', color: 'success' })
    isEditing.value = false
    refresh()
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao atualizar MDF-e.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}

function onDeleted() {
  router.push('/fiscal/mdfe')
}

function onCancelled() {
  refresh()
}
</script>

<template>
  <UDashboardPanel id="mdfe-detalhe">
    <template #header>
      <UDashboardNavbar title="MDF-e">
        <template #leading>
          <UButton
            icon="i-lucide-arrow-left"
            color="neutral"
            variant="ghost"
            @click="router.push('/fiscal/mdfe')"
          />
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UBreadcrumb :items="breadcrumbs" />

          <template v-if="mdfe && !isEditing">
            <UDropdownMenu
              :items="[
                {
                  label: 'Copiar Chave',
                  icon: 'i-lucide-copy',
                  onSelect: copyChave
                },
                {
                  label: 'Ver XML',
                  icon: 'i-lucide-code'
                },
                ...(mdfe.status === 'autorizada' ? [
                  { type: 'separator' as const },
                  {
                    label: 'Cancelar MDF-e',
                    icon: 'i-lucide-x-circle',
                    color: 'error' as const,
                    onSelect: () => cancelModal?.open?.()
                  }
                ] : []),
                { type: 'separator' as const },
                {
                  label: 'Excluir',
                  icon: 'i-lucide-trash-2',
                  color: 'error' as const,
                  onSelect: () => deleteModal?.open?.()
                }
              ]"
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
              @click="isEditing = true"
            />
          </template>

          <template v-if="isEditing">
            <UButton
              label="Cancelar"
              color="neutral"
              variant="outline"
              @click="isEditing = false"
            />
            <UButton
              label="Salvar Alterações"
              color="primary"
              :loading="loading"
              @click="handleSubmit"
            />
          </template>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="animate-spin text-muted text-2xl" />
      </div>

      <template v-else-if="mdfe">
        <UForm
          v-if="isEditing"
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
                Dados do MDF-e
              </h3>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <UFormField label="Modal" name="modal">
                <USelect
                  :model-value="String(state.modal)"
                  :items="[{ label: '1 - Rodoviário', value: '1' }]"
                  class="w-full"
                  disabled
                />
              </UFormField>
              <UFormField label="Data de Emissão" name="data_emissao" required>
                <UInput v-model="state.data_emissao" type="date" class="w-full" />
              </UFormField>
              <UFormField label="UF Carregamento" name="uf_carregamento" required>
                <USelect
                  v-model="state.uf_carregamento"
                  :items="ufOptions"
                  class="w-full"
                  placeholder="Selecione"
                />
              </UFormField>
              <UFormField label="UF Descarregamento" name="uf_descarregamento" required>
                <USelect
                  v-model="state.uf_descarregamento"
                  :items="ufOptions"
                  class="w-full"
                  placeholder="Selecione"
                />
              </UFormField>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-truck text-muted" />
                Veículo e Motorista
              </h3>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <UFormField label="Placa do Veículo" name="veiculo_placa">
                <UInput v-model="state.veiculo_placa" class="w-full" maxlength="8" />
              </UFormField>
              <UFormField label="CPF do Motorista" name="motorista_cpf">
                <UInput v-model="state.motorista_cpf" class="w-full" />
              </UFormField>
              <UFormField label="Nome do Motorista" name="motorista_nome" class="sm:col-span-2">
                <UInput v-model="state.motorista_nome" class="w-full" />
              </UFormField>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-route text-muted" />
                Percurso
              </h3>
            </template>

            <div class="space-y-2">
              <div
                v-for="(uf, index) in state.uf_percurso"
                :key="index"
                class="flex items-center gap-2"
              >
                <UFormField :name="`uf_percurso.${index}`" class="flex-1">
                  <USelect
                    v-model="state.uf_percurso![index]"
                    :items="ufOptions"
                    class="w-full"
                    placeholder="UF"
                  />
                </UFormField>
                <UButton
                  icon="i-lucide-trash-2"
                  color="error"
                  variant="ghost"
                  @click="removeUfPercurso(index)"
                />
              </div>
              <UButton
                label="Adicionar UF"
                icon="i-lucide-plus"
                color="neutral"
                variant="outline"
                size="sm"
                @click="addUfPercurso"
              />
            </div>
          </UCard>

          <UCard>
            <template #header>
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-package text-muted" />
                Valores
              </h3>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <UFormField label="Valor da Carga (R$)" name="valor_carga">
                <UInput
                  v-model="state.valor_carga"
                  type="number"
                  step="0.01"
                  class="w-full"
                />
              </UFormField>
              <UFormField label="Peso Bruto (kg)" name="peso_bruto">
                <UInput
                  v-model="state.peso_bruto"
                  type="number"
                  step="0.01"
                  class="w-full"
                />
              </UFormField>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                  <span class="i-lucide-paperclip text-muted" />
                  Documentos Vinculados
                </h3>
                <UButton
                  label="Adicionar Documento"
                  icon="i-lucide-plus"
                  size="xs"
                  variant="outline"
                  @click="addDocumento"
                />
              </div>
            </template>

            <div class="space-y-3">
              <div
                v-for="(doc, index) in state.documentos"
                :key="index"
                class="grid grid-cols-1 sm:grid-cols-[1fr_2fr_auto] gap-2 items-end"
              >
                <UFormField :name="`documentos.${index}.tipo`" label="Tipo">
                  <USelect
                    :model-value="state.documentos?.[index]?.tipo"
                    :on-update:model-value="(v: string) => { if (state.documentos?.[index]) state.documentos[index].tipo = v as 'nfe' | 'cte' }"
                    :items="[
                      { label: 'NF-e', value: 'nfe' },
                      { label: 'CT-e', value: 'cte' }
                    ]"
                    class="w-full"
                  />
                </UFormField>
                <UFormField :name="`documentos.${index}.chave`" label="Chave">
                  <UInput
                    :model-value="state.documentos?.[index]?.chave"
                    :on-update:model-value="(v: string) => { if (state.documentos?.[index]) state.documentos[index].chave = v }"
                    class="w-full"
                    maxlength="44"
                    placeholder="Chave de acesso (44 caracteres)"
                  />
                </UFormField>
                <UButton
                  icon="i-lucide-trash-2"
                  color="error"
                  variant="ghost"
                  :disabled="state.documentos!.length <= 1"
                  @click="removeDocumento(index)"
                />
              </div>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-message-square text-muted" />
                Informações Adicionais
              </h3>
            </template>

            <UFormField name="informacoes_adicionais">
              <UTextarea
                v-model="state.informacoes_adicionais"
                class="w-full"
                :rows="3"
              />
            </UFormField>
          </UCard>
        </UForm>

        <div v-else class="space-y-6">
          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                  <span class="i-lucide-file-text text-muted" />
                  Dados do MDF-e
                </h3>
                <UBadge :color="statusColor" variant="subtle" class="capitalize">
                  {{ mdfe.status }}
                </UBadge>
              </div>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div>
                <p class="text-xs text-muted mb-1">
                  Número
                </p>
                <p class="text-sm font-medium">
                  {{ mdfe.numero }}
                </p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">
                  Série
                </p>
                <p class="text-sm font-medium">
                  {{ mdfe.serie }}
                </p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">
                  Modal
                </p>
                <p class="text-sm font-medium">
                  Rodoviário
                </p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">
                  Data de Emissão
                </p>
                <p class="text-sm font-medium">
                  {{ mdfe.data_emissao ? new Date(mdfe.data_emissao).toLocaleDateString('pt-BR') : '—' }}
                </p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">
                  UF Carregamento
                </p>
                <p class="text-sm font-medium">
                  {{ mdfe.uf_carregamento }}
                </p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">
                  UF Descarregamento
                </p>
                <p class="text-sm font-medium">
                  {{ mdfe.uf_descarregamento }}
                </p>
              </div>
              <div v-if="mdfe.protocolo">
                <p class="text-xs text-muted mb-1">
                  Protocolo
                </p>
                <p class="text-sm font-medium">
                  {{ mdfe.protocolo }}
                </p>
              </div>
              <div v-if="mdfe.motivo">
                <p class="text-xs text-muted mb-1">
                  Motivo
                </p>
                <p class="text-sm font-medium">
                  {{ mdfe.motivo }}
                </p>
              </div>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-truck text-muted" />
                Veículo e Motorista
              </h3>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div>
                <p class="text-xs text-muted mb-1">
                  Placa do Veículo
                </p>
                <p class="text-sm font-medium">
                  {{ mdfe.veiculo_placa || '—' }}
                </p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">
                  CPF do Motorista
                </p>
                <p class="text-sm font-medium">
                  {{ mdfe.motorista_cpf || '—' }}
                </p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">
                  Nome do Motorista
                </p>
                <p class="text-sm font-medium">
                  {{ mdfe.motorista_nome || '—' }}
                </p>
              </div>
            </div>
          </UCard>

          <UCard v-if="mdfe.uf_percurso && mdfe.uf_percurso.length > 0">
            <template #header>
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-route text-muted" />
                Percurso
              </h3>
            </template>

            <div class="flex flex-wrap gap-2">
              <UBadge
                v-for="uf in mdfe.uf_percurso"
                :key="uf"
                color="neutral"
                variant="outline"
              >
                {{ uf }}
              </UBadge>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-package text-muted" />
                Valores
              </h3>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <p class="text-xs text-muted mb-1">
                  Valor da Carga
                </p>
                <p class="text-sm font-medium">
                  {{ formatCurrency(mdfe.valor_carga) }}
                </p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">
                  Peso Bruto
                </p>
                <p class="text-sm font-medium">
                  {{ mdfe.peso_bruto }} kg
                </p>
              </div>
            </div>
          </UCard>

          <UCard v-if="mdfe.documentos && mdfe.documentos.length > 0">
            <template #header>
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-paperclip text-muted" />
                Documentos Vinculados
              </h3>
            </template>

            <div class="space-y-2">
              <div
                v-for="doc in mdfe.documentos"
                :key="doc.id"
                class="flex items-center justify-between py-2 border-b border-default last:border-b-0"
              >
                <div class="flex items-center gap-3">
                  <UBadge
                    :color="doc.tipo === 'nfe' ? 'primary' : 'info'"
                    variant="subtle"
                  >
                    {{ doc.tipo === 'nfe' ? 'NF-e' : 'CT-e' }}
                  </UBadge>
                  <span class="text-sm font-mono">{{ doc.chave }}</span>
                </div>
              </div>
            </div>
          </UCard>

          <UCard v-if="mdfe.informacoes_adicionais">
            <template #header>
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-message-square text-muted" />
                Informações Adicionais
              </h3>
            </template>

            <p class="text-sm whitespace-pre-wrap">
              {{ mdfe.informacoes_adicionais }}
            </p>
          </UCard>
        </div>

        <MdfeDeleteModal
          v-if="mdfe"
          ref="deleteModal"
          :mdfe="mdfe"
          @deleted="onDeleted"
        />
        <MdfeCancelModal
          v-if="mdfe"
          ref="cancelModal"
          :mdfe="mdfe"
          @cancelled="onCancelled"
        />
      </template>
    </template>
  </UDashboardPanel>
</template>
