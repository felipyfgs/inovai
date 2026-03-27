<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const emit = defineEmits<{ created: [] }>()

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

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const today = new Date().toISOString().split('T')[0]!

const state = reactive<Partial<Schema>>({
  modal: 1,
  data_emissao: today,
  uf_carregamento: '',
  uf_descarregamento: '',
  veiculo_placa: '',
  motorista_cpf: '',
  motorista_nome: '',
  uf_percurso: [],
  valor_carga: 0,
  peso_bruto: 0,
  documentos: [{ tipo: 'nfe', chave: '' }],
  informacoes_adicionais: ''
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

function resetForm() {
  Object.assign(state, {
    modal: 1,
    data_emissao: today,
    uf_carregamento: '',
    uf_descarregamento: '',
    veiculo_placa: '',
    motorista_cpf: '',
    motorista_nome: '',
    uf_percurso: [],
    valor_carga: 0,
    peso_bruto: 0,
    documentos: [{ tipo: 'nfe', chave: '' }],
    informacoes_adicionais: ''
  })
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await post('/mdfes', event.data)
    toast.add({ title: 'MDF-e criado', description: `MDF-e criado com sucesso`, color: 'success' })
    open.value = false
    resetForm()
    emit('created')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao criar MDF-e.', color: 'error' })
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
    title="Novo MDF-e"
    description="Manifesto Eletrônico de Documentos Fiscais"
    :ui="{ content: 'w-full sm:max-w-5xl', footer: 'justify-end' }"
  >
    <UButton label="Novo MDF-e" icon="i-lucide-plus" />

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
        </div>

        <USeparator />

        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-truck text-muted" />
            Veículo e Motorista
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Placa do Veículo" name="veiculo_placa">
              <UInput
                v-model="state.veiculo_placa"
                class="w-full"
                maxlength="8"
                placeholder="ABC1D23"
              />
            </UFormField>
            <UFormField label="CPF do Motorista" name="motorista_cpf">
              <UInput
                v-model="state.motorista_cpf"
                class="w-full"
                placeholder="000.000.000-00"
              />
            </UFormField>
            <UFormField label="Nome do Motorista" name="motorista_nome" class="sm:col-span-2">
              <UInput
                v-model="state.motorista_nome"
                class="w-full"
                placeholder="Nome completo"
              />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-route text-muted" />
            Percurso
          </h3>
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
        </div>

        <USeparator />

        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-package text-muted" />
            Carga
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Valor da Carga (R$)" name="valor_carga">
              <UInput
                v-model="state.valor_carga"
                type="number"
                step="0.01"
                class="w-full"
                placeholder="0,00"
              />
            </UFormField>
            <UFormField label="Peso Bruto (kg)" name="peso_bruto">
              <UInput
                v-model="state.peso_bruto"
                type="number"
                step="0.01"
                class="w-full"
                placeholder="0,00"
              />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-paperclip text-muted" />
            Documentos Vinculados
          </h3>
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
            <UButton
              label="Adicionar Documento"
              icon="i-lucide-plus"
              color="neutral"
              variant="outline"
              size="sm"
              @click="addDocumento"
            />
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
              placeholder="Informações adicionais..."
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
        label="Criar MDF-e"
        color="primary"
        :loading="loading"
        @click="handleSubmit"
      />
    </template>
  </UModal>
</template>
