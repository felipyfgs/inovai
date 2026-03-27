<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Office } from '~/types'

const props = defineProps<{ office: Office }>()
const emit = defineEmits<{ updated: [] }>()

const open = defineModel<boolean>('open', { default: false })
const loading = ref(false)
const { put } = useApiMutation()
const { handleError } = useApiError()
const formRef = useTemplateRef('formRef')

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  cnpj: z.string().min(11, 'CNPJ inválido'),
  email: z.string().email('E-mail inválido').optional().or(z.literal('')),
  phone: z.string().optional(),
  ie: z.string().optional(),
  logradouro: z.string().optional(),
  numero: z.string().optional(),
  complemento: z.string().optional(),
  bairro: z.string().optional(),
  municipio: z.string().optional(),
  uf: z.string().optional(),
  cep: z.string().optional(),
  notes: z.string().optional()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: props.office.name || '',
  cnpj: props.office.cnpj || '',
  email: props.office.email || '',
  phone: props.office.phone || '',
  ie: props.office.ie || '',
  logradouro: props.office.logradouro || '',
  numero: props.office.numero || '',
  complemento: props.office.complemento || '',
  bairro: props.office.bairro || '',
  municipio: props.office.municipio || '',
  uf: props.office.uf || '',
  cep: props.office.cep || '',
  notes: props.office.notes || ''
})

const ufOptions = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
  'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
  'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
].map(uf => ({ label: uf, value: uf }))

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    const _updated = await put<Office>('/office/profile', event.data)
    useAppToast().success('Perfil atualizado com sucesso')
    open.value = false
    emit('updated')
  } catch (e: unknown) {
    handleError(e, 'Erro ao atualizar perfil')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Editar Escritório"
    description="Atualize os dados cadastrais do escritório."
    :ui="{ content: 'w-full sm:max-w-lg', body: 'max-h-[80vh] overflow-y-auto', footer: 'justify-end shrink-0' }"
  >
    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-4"
        @submit="onSubmit"
      >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField
            label="Nome / Razão Social"
            name="name"
            required
            class="sm:col-span-2"
          >
            <UInput v-model="state.name" class="w-full" />
          </UFormField>

          <UFormField label="CNPJ" name="cnpj" required>
            <UInput v-model="state.cnpj" class="w-full" />
          </UFormField>

          <UFormField label="Inscrição Estadual" name="ie">
            <UInput v-model="state.ie" class="w-full" />
          </UFormField>

          <UFormField label="E-mail" name="email">
            <UInput
              v-model="state.email"
              type="email"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Telefone" name="phone">
            <UInput v-model="state.phone" class="w-full" />
          </UFormField>
        </div>

        <USeparator label="Endereço" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Logradouro" name="logradouro" class="sm:col-span-2">
            <UInput v-model="state.logradouro" class="w-full" />
          </UFormField>

          <UFormField label="Número" name="numero">
            <UInput v-model="state.numero" class="w-full" />
          </UFormField>

          <UFormField label="Complemento" name="complemento">
            <UInput v-model="state.complemento" class="w-full" />
          </UFormField>

          <UFormField label="Bairro" name="bairro">
            <UInput v-model="state.bairro" class="w-full" />
          </UFormField>

          <UFormField label="Município" name="municipio">
            <UInput v-model="state.municipio" class="w-full" />
          </UFormField>

          <UFormField label="UF" name="uf">
            <USelect
              v-model="state.uf"
              :items="ufOptions"
              placeholder="UF"
              class="w-full"
            />
          </UFormField>

          <UFormField label="CEP" name="cep">
            <UInput v-model="state.cep" class="w-full" />
          </UFormField>
        </div>

        <USeparator label="Observações" />

        <UFormField label="Observações" name="notes">
          <UTextarea v-model="state.notes" class="w-full" />
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
        label="Salvar"
        color="primary"
        :loading="loading"
        @click="formRef?.submit()"
      />
    </template>
  </UModal>
</template>
