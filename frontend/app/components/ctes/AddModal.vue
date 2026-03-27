<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Pessoa, PaginatedResponse } from '~/types'

const emit = defineEmits<{ created: [] }>()

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

const TOMADOR_OPTIONS = [
  { label: '0 - Remetente', value: 0 },
  { label: '1 - Expedidor', value: 1 },
  { label: '2 - Recebedor', value: 2 },
  { label: '3 - Destinatário', value: 3 }
]

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const { currentCompany } = useCurrentCompany()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

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

const state = reactive<Partial<Schema>>({
  natureza_operacao: 'Prestação de Serviço de Transporte',
  cfop: '',
  modal: 1,
  data_emissao: new Date().toISOString().split('T')[0] as string,
  remetente_id: undefined,
  destinatario_id: undefined,
  expedidor_id: null,
  recebedor_id: null,
  tomador_id: null,
  tomador_tipo: 0,
  valor_servico: 0,
  valor_receber: 0,
  valor_icms: 0,
  valor_total: 0,
  uf_inicio: '',
  uf_fim: '',
  informacoes_adicionais: ''
})

const valorTotal = computed(() => {
  const servico = Number(state.valor_servico) || 0
  const icms = Number(state.valor_icms) || 0
  return servico - icms
})

watch(valorTotal, (val) => {
  state.valor_total = Number(val.toFixed(2))
})

function resetForm() {
  Object.assign(state, {
    natureza_operacao: 'Prestação de Serviço de Transporte',
    cfop: '', modal: 1,
    data_emissao: new Date().toISOString().split('T')[0] as string,
    remetente_id: undefined, destinatario_id: undefined,
    expedidor_id: null, recebedor_id: null, tomador_id: null,
    tomador_tipo: 0,
    valor_servico: 0, valor_receber: 0, valor_icms: 0, valor_total: 0,
    uf_inicio: '', uf_fim: '', informacoes_adicionais: ''
  })
  nfes.value = []
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    const payload = { ...event.data }
    if (nfes.value.length > 0) {
      payload.nfes = nfes.value.filter(n => n.chave_nfe)
    }
    await post('/ctes', payload)
    toast.add({ title: 'CT-e criado', color: 'success' })
    open.value = false
    resetForm()
    emit('created')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao criar CT-e.', color: 'error' })
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
    title="Novo CT-e"
    description="Criar um novo conhecimento de transporte eletrônico"
    :ui="{ content: 'w-full sm:max-w-5xl', footer: 'justify-end' }"
  >
    <UButton label="Novo CT-e" icon="i-lucide-plus" />

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
            <UFormField
              label="Natureza da Operação"
              name="natureza_operacao"
              required
              class="sm:col-span-2"
            >
              <UInput v-model="state.natureza_operacao" class="w-full" placeholder="Prestação de Serviço de Transporte" />
            </UFormField>
            <UFormField label="CFOP" name="cfop">
              <UInput v-model="state.cfop" class="w-full" placeholder="Ex: 5351" />
            </UFormField>
            <UFormField label="Modal" name="modal" required>
              <USelect
                v-model="state.modal"
                :items="MODAL_OPTIONS"
                class="w-full"
                placeholder="Selecione..."
              />
            </UFormField>
            <UFormField label="Data de Emissão" name="data_emissao" required>
              <UInput v-model="state.data_emissao" type="date" class="w-full" />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-users text-muted" />
            Participantes
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Remetente" name="remetente_id" required>
              <USelect
                v-model="state.remetente_id"
                :items="pessoaOptions"
                class="w-full"
                placeholder="Selecione..."
              />
            </UFormField>
            <UFormField label="Destinatário" name="destinatario_id" required>
              <USelect
                v-model="state.destinatario_id"
                :items="pessoaOptions"
                class="w-full"
                placeholder="Selecione..."
              />
            </UFormField>
            <UFormField label="Expedidor" name="expedidor_id">
              <USelect
                v-model="state.expedidor_id"
                :items="[{ label: '(Nenhum)', value: null }, ...pessoaOptions]"
                class="w-full"
                placeholder="Opcional"
              />
            </UFormField>
            <UFormField label="Recebedor" name="recebedor_id">
              <USelect
                v-model="state.recebedor_id"
                :items="[{ label: '(Nenhum)', value: null }, ...pessoaOptions]"
                class="w-full"
                placeholder="Opcional"
              />
            </UFormField>
            <UFormField label="Tomador" name="tomador_id">
              <USelect
                v-model="state.tomador_id"
                :items="[{ label: '(Nenhum)', value: null }, ...pessoaOptions]"
                class="w-full"
                placeholder="Opcional"
              />
            </UFormField>
            <UFormField label="Tipo do Tomador" name="tomador_tipo">
              <USelect
                v-model="state.tomador_tipo"
                :items="TOMADOR_OPTIONS"
                class="w-full"
                placeholder="Selecione..."
              />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-calculator text-muted" />
            Valores
          </h3>
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
        </div>

        <USeparator />

        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-map text-muted" />
            Percurso
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="UF Início" name="uf_inicio" required>
              <USelect
                v-model="state.uf_inicio"
                :items="ufOptions"
                class="w-full"
                placeholder="Selecione..."
              />
            </UFormField>
            <UFormField label="UF Fim" name="uf_fim" required>
              <USelect
                v-model="state.uf_fim"
                :items="ufOptions"
                class="w-full"
                placeholder="Selecione..."
              />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <div>
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-link text-muted" />
              Notas Fiscais Vinculadas
            </h3>
            <UButton
              label="Adicionar NF-e"
              icon="i-lucide-plus"
              size="xs"
              variant="outline"
              @click="addNfe"
            />
          </div>

          <div v-if="nfes.length === 0" class="text-sm text-muted text-center py-4">
            Nenhuma NF-e vinculada
          </div>

          <div class="space-y-3">
            <div v-for="(nfe, idx) in nfes" :key="idx" class="bg-muted/30 rounded-lg p-4">
              <div class="flex items-center gap-3">
                <UFormField :label="`NF-e ${Number(idx) + 1}`" :name="`nfes.${idx}.chave_nfe`" class="flex-1">
                  <UInput
                    v-model="nfe.chave_nfe"
                    class="w-full"
                    maxlength="44"
                    placeholder="Chave de 44 caracteres"
                  />
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
        </div>

        <USeparator />

        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-message-square text-muted" />
            Informações Adicionais
          </h3>
          <UFormField name="informacoes_adicionais">
            <UTextarea
              v-model="state.informacoes_adicionais"
              class="w-full"
              placeholder="Observações adicionais..."
              :rows="3"
            />
          </UFormField>
        </div>
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
        label="Criar CT-e"
        color="primary"
        :loading="loading"
        @click="handleSubmit"
      />
    </template>
  </UModal>
</template>
