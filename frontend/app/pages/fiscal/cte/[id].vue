<script setup lang="ts">
import * as z from 'zod'
import type { BreadcrumbItem, FormSubmitEvent } from '@nuxt/ui'
import type { Cte, Pessoa, PaginatedResponse } from '~/types'
import { formatCurrency } from '~/utils'

import { UButton, UBadge, UDropdownMenu } from '#components'

const nfeSchema = z.object({
  chave_nfe: z.string().length(44, 'Deve ter 44 caracteres').optional().or(z.literal(''))
})

const schema = z.object({
  natureza_operacao: z.string().min(1, 'Obrigatório'),
  cfop: z.string().optional(),
  modal: z.coerce.number().min(1).max(5),
  data_emissao: z.string().min(1, 'Obrigatório'),
  remetente_id: z.coerce.number().min(1, 'Obrigatório'),
  destinatario_id: z.coerce.number().min(1, 'Obrigatório'),
  expedidor_id: z.coerce.number().nullable().optional(),
  recebedor_id: z.coerce.number().nullable().optional(),
  tomador_id: z.coerce.number().nullable().optional(),
  tomador_tipo: z.coerce.number().min(0).max(3),
  valor_servico: z.coerce.number().min(0).default(0),
  valor_receber: z.coerce.number().min(0).default(0),
  valor_icms: z.coerce.number().min(0).default(0),
  valor_total: z.coerce.number().min(0).default(0),
  uf_inicio: z.string().min(2, 'Obrigatório').max(2),
  uf_fim: z.string().min(2, 'Obrigatório').max(2),
  nfes: z.array(nfeSchema).optional(),
  informacoes_adicionais: z.string().optional()
})

type Schema = z.output<typeof schema>

const UFS = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
  'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
  'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
]

const MODAL_OPTIONS = [
  { label: '1 - Rodoviário', value: 1 },
  { label: '2 - Aéreo', value: 2 },
  { label: '3 - Aquaviário', value: 3 },
  { label: '4 - Ferroviário', value: 4 },
  { label: '5 - Dutoviário', value: 5 }
]

const MODAL_LABELS: Record<number, string> = {
  1: 'Rodoviário',
  2: 'Aéreo',
  3: 'Aquaviário',
  4: 'Ferroviário',
  5: 'Dutoviário'
}

const TOMADOR_OPTIONS = [
  { label: '0 - Remetente', value: 0 },
  { label: '1 - Expedidor', value: 1 },
  { label: '2 - Recebedor', value: 2 },
  { label: '3 - Destinatário', value: 3 }
]

const TOMADOR_LABELS: Record<number, string> = {
  0: 'Remetente',
  1: 'Expedidor',
  2: 'Recebedor',
  3: 'Destinatário'
}

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { updateCte } = useCte()
const { currentCompany } = useCurrentCompany()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const cteId = computed(() => Number(route.params.id))
const url = computed(() => `/ctes/${cteId.value}`)

const { data: cte, status, refresh } = useApi<Cte>(url, { lazy: false })

const isEditing = ref(false)
const loading = ref(false)
const deletingCte = ref<Cte | null>(null)
const cancellingCte = ref<Cte | null>(null)

const { data: pessoasData } = useApi<PaginatedResponse<Pessoa>>('/pessoas', {
  lazy: true,
  watch: [computed(() => currentCompany.value?.id)]
})

const pessoaOptions = computed(() =>
  (pessoasData.value?.data || []).map(p => ({
    label: `${p.razao_social}${p.cpf_cnpj ? ` - ${p.cpf_cnpj}` : ''}`,
    value: p.id
  }))
)

const ufOptions = computed(() => UFS.map(uf => ({ label: uf, value: uf })))

const nfes = ref<Array<{ chave_nfe: string }>>([])

function addNfe() {
  nfes.value.push({ chave_nfe: '' })
}

function removeNfe(index: number) {
  nfes.value.splice(index, 1)
}

const state = reactive<Partial<Schema>>({})

const valorTotal = computed(() => {
  const servico = Number(state.valor_servico) || 0
  const icms = Number(state.valor_icms) || 0
  return servico - icms
})

watch(valorTotal, (val) => {
  state.valor_total = Number(val.toFixed(2))
})

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { label: 'Fiscal', icon: 'i-lucide-file-text', to: '/fiscal/cte' },
  { label: 'CT-e', icon: 'i-lucide-truck', to: '/fiscal/cte' },
  { label: cte.value ? `#${cte.value.numero}` : '...' }
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

function populateState(val: Cte) {
  Object.assign(state, {
    natureza_operacao: val.natureza_operacao || 'Prestação de Serviço de Transporte',
    cfop: val.cfop || '',
    modal: val.modal || 1,
    data_emissao: val.data_emissao?.split('T')[0] || new Date().toISOString().split('T')[0],
    remetente_id: val.remetente_id,
    destinatario_id: val.destinatario_id,
    expedidor_id: val.expedidor_id || null,
    recebedor_id: val.recebedor_id || null,
    tomador_id: val.tomador_id || null,
    tomador_tipo: val.tomador_tipo || 0,
    valor_servico: Number(val.valor_servico) || 0,
    valor_receber: Number(val.valor_receber) || 0,
    valor_icms: Number(val.valor_icms) || 0,
    valor_total: Number(val.valor_total) || 0,
    uf_inicio: val.uf_inicio || '',
    uf_fim: val.uf_fim || '',
    informacoes_adicionais: val.informacoes_adicionais || ''
  })
  nfes.value = (val.nfes || []).map(n => ({ chave_nfe: n.chave_nfe }))
}

watch(cte, (val) => {
  if (val && !isEditing.value) {
    populateState(val)
  }
}, { immediate: true })

function startEditing() {
  if (cte.value) {
    populateState(cte.value)
  }
  isEditing.value = true
}

function cancelEditing() {
  if (cte.value) {
    populateState(cte.value)
  }
  isEditing.value = false
}

function buildPayload(data: Schema) {
  const payload = { ...data }
  if (nfes.value.length > 0) {
    payload.nfes = nfes.value.filter(n => n.chave_nfe)
  }
  return payload
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    const payload = buildPayload(event.data)
    await updateCte(cteId.value, payload)
    toast.add({ title: 'CT-e atualizado', color: 'success' })
    isEditing.value = false
    refresh()
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao atualizar CT-e.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}

const actions = computed(() => {
  const items = [
    {
      type: 'label' as const,
      label: 'Ações'
    },
    {
      label: 'Copiar Chave',
      icon: 'i-lucide-copy',
      color: 'neutral' as 'neutral' | 'error' | 'warning' | 'info' | 'success' | undefined,
      onSelect() {
        if (!cte.value?.chave) return
        navigator.clipboard.writeText(cte.value.chave)
        toast.add({ title: 'Copiado', description: 'Chave copiada' })
      }
    },
    {
      label: 'Ver XML',
      icon: 'i-lucide-code',
      color: 'neutral' as 'neutral' | 'error' | 'warning' | 'info' | 'success' | undefined,
      onSelect() {
        toast.add({ title: 'Em desenvolvimento', description: 'A visualização de XML será implementada em breve.' })
      }
    },
    { type: 'separator' as const }
  ]

  if (cte.value?.status === 'autorizada') {
    items.push({
      label: 'Cancelar CT-e',
      icon: 'i-lucide-x-circle',
      color: 'error' as const,
      onSelect() {
        if (cte.value) cancellingCte.value = cte.value
      }
    })
  }

  items.push({
    label: 'Excluir',
    icon: 'i-lucide-trash',
    color: 'error' as const,
    onSelect() {
      if (cte.value) deletingCte.value = cte.value
    }
  })

  return items
})

function formatDate(date: string | null | undefined) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('pt-BR')
}

function onDeleted() {
  deletingCte.value = null
  router.push('/fiscal/cte')
}

function onCancelled() {
  cancellingCte.value = null
  refresh()
}
</script>

<template>
  <UDashboardPanel id="cte-detalhe">
    <template #header>
      <UDashboardNavbar title="CT-e">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UBadge
            v-if="cte"
            :color="statusColor[cte.status] || 'neutral'"
            variant="subtle"
            class="capitalize"
          >
            {{ cte.status }}
          </UBadge>

          <template v-if="!isEditing">
            <UDropdownMenu
              v-if="cte"
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
              :disabled="!cte || cte.status !== 'rascunho'"
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
            @click="router.push('/fiscal/cte')"
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

      <div v-else-if="!cte" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-truck" class="size-12 text-muted mb-4" />
        <p class="text-muted">
          CT-e não encontrado.
        </p>
        <UButton
          label="Voltar"
          variant="link"
          class="mt-2"
          @click="router.push('/fiscal/cte')"
        />
      </div>

      <div v-else-if="!isEditing" class="space-y-6">
        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-file-text text-muted" />
              Dados do CT-e
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Número
              </p>
              <p class="font-medium">
                {{ cte.numero }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Série
              </p>
              <p class="font-medium">
                {{ cte.serie }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Status
              </p>
              <UBadge
                :color="statusColor[cte.status] || 'neutral'"
                variant="subtle"
                class="capitalize"
              >
                {{ cte.status }}
              </UBadge>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Natureza da Operação
              </p>
              <p class="font-medium">
                {{ cte.natureza_operacao || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                CFOP
              </p>
              <p class="font-medium">
                {{ cte.cfop || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Modal
              </p>
              <p class="font-medium">
                {{ MODAL_LABELS[cte.modal ?? 0] || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Data de Emissão
              </p>
              <p class="font-medium">
                {{ formatDate(cte.data_emissao) }}
              </p>
            </div>
            <div v-if="cte.chave">
              <p class="text-sm text-muted mb-1">
                Chave
              </p>
              <p class="font-mono text-xs font-medium break-all">
                {{ cte.chave }}
              </p>
            </div>
            <div v-if="cte.protocolo">
              <p class="text-sm text-muted mb-1">
                Protocolo
              </p>
              <p class="font-mono text-xs font-medium break-all">
                {{ cte.protocolo }}
              </p>
            </div>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-users text-muted" />
              Participantes
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Remetente
              </p>
              <p class="font-medium">
                {{ cte.remetente?.razao_social || '—' }}
              </p>
              <p v-if="cte.remetente?.cpf_cnpj" class="text-xs text-muted">
                {{ cte.remetente.cpf_cnpj }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Destinatário
              </p>
              <p class="font-medium">
                {{ cte.destinatario?.razao_social || '—' }}
              </p>
              <p v-if="cte.destinatario?.cpf_cnpj" class="text-xs text-muted">
                {{ cte.destinatario.cpf_cnpj }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Expedidor
              </p>
              <p class="font-medium">
                {{ cte.expedidor?.razao_social || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Recebedor
              </p>
              <p class="font-medium">
                {{ cte.recebedor?.razao_social || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Tomador
              </p>
              <p class="font-medium">
                {{ cte.tomador?.razao_social || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Tipo do Tomador
              </p>
              <p class="font-medium">
                {{ TOMADOR_LABELS[cte.tomador_tipo ?? 0] || '—' }}
              </p>
            </div>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-map text-muted" />
              Percurso
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                UF Início
              </p>
              <p class="font-medium">
                {{ cte.uf_inicio || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                UF Fim
              </p>
              <p class="font-medium">
                {{ cte.uf_fim || '—' }}
              </p>
            </div>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-link text-muted" />
              Documentos (NF-e)
            </h3>
          </template>

          <div v-if="!cte.nfes?.length" class="text-center py-8 text-muted">
            Nenhuma NF-e vinculada.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="nfe in cte.nfes"
              :key="nfe.id"
              class="flex items-center justify-between bg-muted/30 rounded-lg px-4 py-3"
            >
              <div class="flex items-center gap-3 min-w-0">
                <UIcon name="i-lucide-file-text" class="size-4 text-muted shrink-0" />
                <p class="font-mono text-xs font-medium truncate">
                  {{ nfe.chave_nfe }}
                </p>
              </div>
            </div>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-calculator text-muted" />
              Valores
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Valor do Serviço
              </p>
              <p class="font-medium">
                {{ formatCurrency(cte.valor_servico) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Valor a Receber
              </p>
              <p class="font-medium">
                {{ formatCurrency(cte.valor_receber) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Valor ICMS
              </p>
              <p class="font-medium">
                {{ formatCurrency(cte.valor_icms) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Valor Total
              </p>
              <p class="font-bold text-lg text-primary">
                {{ formatCurrency(cte.valor_total) }}
              </p>
            </div>
          </div>
        </UCard>

        <UCard v-if="cte.informacoes_adicionais">
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-message-square text-muted" />
              Informações Adicionais
            </h3>
          </template>

          <p class="text-sm whitespace-pre-wrap">
            {{ cte.informacoes_adicionais }}
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
              Dados do CT-e
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField
              label="Natureza da Operação"
              name="natureza_operacao"
              required
              class="sm:col-span-2"
            >
              <UInput v-model="state.natureza_operacao" class="w-full" />
            </UFormField>
            <UFormField label="CFOP" name="cfop">
              <UInput v-model="state.cfop" class="w-full" />
            </UFormField>
            <UFormField label="Modal" name="modal" required>
              <USelect
                v-model="state.modal"
                :items="MODAL_OPTIONS"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Data de Emissão" name="data_emissao" required>
              <UInput v-model="state.data_emissao" type="date" class="w-full" />
            </UFormField>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-users text-muted" />
              Participantes
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Remetente" name="remetente_id" required>
              <USelect
                v-model="state.remetente_id"
                :items="pessoaOptions"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Destinatário" name="destinatario_id" required>
              <USelect
                v-model="state.destinatario_id"
                :items="pessoaOptions"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Expedidor" name="expedidor_id">
              <USelect
                v-model="state.expedidor_id"
                :items="[{ label: '(Nenhum)', value: null }, ...pessoaOptions]"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Recebedor" name="recebedor_id">
              <USelect
                v-model="state.recebedor_id"
                :items="[{ label: '(Nenhum)', value: null }, ...pessoaOptions]"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Tomador" name="tomador_id">
              <USelect
                v-model="state.tomador_id"
                :items="[{ label: '(Nenhum)', value: null }, ...pessoaOptions]"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Tipo do Tomador" name="tomador_tipo">
              <USelect
                v-model="state.tomador_tipo"
                :items="TOMADOR_OPTIONS"
                class="w-full"
              />
            </UFormField>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-map text-muted" />
              Percurso
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="UF Início" name="uf_inicio" required>
              <USelect
                v-model="state.uf_inicio"
                :items="ufOptions"
                class="w-full"
              />
            </UFormField>
            <UFormField label="UF Fim" name="uf_fim" required>
              <USelect
                v-model="state.uf_fim"
                :items="ufOptions"
                class="w-full"
              />
            </UFormField>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-link text-muted" />
                Documentos
              </h3>
              <UButton
                label="Adicionar NF-e"
                icon="i-lucide-plus"
                size="xs"
                variant="outline"
                @click="addNfe"
              />
            </div>
          </template>

          <div v-if="nfes.length === 0" class="text-sm text-muted text-center py-4">
            Nenhuma NF-e vinculada
          </div>

          <div class="space-y-3">
            <div v-for="(nfe, idx) in nfes" :key="idx" class="bg-muted/30 rounded-lg p-4">
              <div class="flex items-center gap-3">
                <UFormField :label="`NF-e ${Number(idx) + 1}`" :name="`nfes.${idx}.chave_nfe`" class="flex-1">
                  <UInput v-model="nfe.chave_nfe" class="w-full" maxlength="44" />
                </UFormField>
                <UButton
                  icon="i-lucide-trash"
                  size="xs"
                  color="error"
                  variant="ghost"
                  class="mt-5"
                  @click="removeNfe(Number(idx))"
                />
              </div>
            </div>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-calculator text-muted" />
              Valores
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <UFormField label="Valor do Serviço (R$)" name="valor_servico">
              <UInput
                v-model="state.valor_servico"
                type="number"
                step="0.01"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Valor a Receber (R$)" name="valor_receber">
              <UInput
                v-model="state.valor_receber"
                type="number"
                step="0.01"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Valor ICMS (R$)" name="valor_icms">
              <UInput
                v-model="state.valor_icms"
                type="number"
                step="0.01"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Valor Total (R$)" name="valor_total">
              <UInput
                :model-value="state.valor_total?.toString()"
                type="number"
                step="0.01"
                class="w-full"
                disabled
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

          <UFormField name="informacoes_adicionais">
            <UTextarea v-model="state.informacoes_adicionais" class="w-full" :rows="3" />
          </UFormField>
        </UCard>
      </UForm>
    </template>
  </UDashboardPanel>

  <CtesDeleteModal
    v-if="deletingCte"
    :cte="deletingCte"
    @deleted="onDeleted"
  />
  <CtesCancelModal
    v-if="cancellingCte"
    :cte="cancellingCte"
    @cancelled="onCancelled"
  />
</template>
