<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Office } from '~/types'

const props = defineProps<{ office: Office }>()
const emit = defineEmits<{ updated: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { put } = useApiMutation()

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  cnpj: z.string().min(11, 'CPF/CNPJ inválido'),
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
  is_active: z.boolean(),
  notes: z.string().optional()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({})

const ufOptions = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
  'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
  'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
].map(uf => ({ label: uf, value: uf }))

watch(() => props.office, (office) => {
  if (office) {
    Object.assign(state, {
      name: office.name,
      cnpj: office.cnpj || '',
      email: office.email || '',
      phone: office.phone || '',
      ie: office.ie || '',
      logradouro: office.logradouro || '',
      numero: office.numero || '',
      complemento: office.complemento || '',
      bairro: office.bairro || '',
      municipio: office.municipio || '',
      uf: office.uf || '',
      cep: office.cep || '',
      is_active: office.is_active,
      notes: office.notes || ''
    })
    open.value = true
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await put(`/admin/offices/${props.office.id}`, event.data)
    toast.add({ title: 'Atualizado com sucesso', color: 'success' })
    open.value = false
    emit('updated')
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao atualizar.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Editar Escritório" description="Atualize os dados do escritório.">
    <slot />

    <template #body>
      <UForm
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
            <UInput v-model="state.name" placeholder="Nome do contador ou escritório" class="w-full" />
          </UFormField>

          <UFormField label="CPF/CNPJ" name="cnpj" required>
            <UInput v-model="state.cnpj" placeholder="000.000.000-00 ou 00.000.000/0001-00" class="w-full" />
          </UFormField>

          <UFormField label="Inscrição Estadual" name="ie">
            <UInput v-model="state.ie" placeholder="Inscrição estadual" class="w-full" />
          </UFormField>

          <UFormField label="E-mail" name="email">
            <UInput
              v-model="state.email"
              type="email"
              placeholder="contato@escritorio.com.br"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Telefone" name="phone">
            <UInput v-model="state.phone" placeholder="(00) 00000-0000" class="w-full" />
          </UFormField>

          <UFormField label="Status" name="is_active">
            <USwitch v-model="state.is_active" label="Ativo" />
          </UFormField>
        </div>

        <USeparator label="Endereço" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Logradouro" name="logradouro" class="sm:col-span-2">
            <UInput v-model="state.logradouro" placeholder="Rua, Avenida..." class="w-full" />
          </UFormField>

          <UFormField label="Número" name="numero">
            <UInput v-model="state.numero" placeholder="Nº" class="w-full" />
          </UFormField>

          <UFormField label="Complemento" name="complemento">
            <UInput v-model="state.complemento" placeholder="Sala, Bloco..." class="w-full" />
          </UFormField>

          <UFormField label="Bairro" name="bairro">
            <UInput v-model="state.bairro" placeholder="Bairro" class="w-full" />
          </UFormField>

          <UFormField label="Município" name="municipio">
            <UInput v-model="state.municipio" placeholder="Cidade" class="w-full" />
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
            <UInput v-model="state.cep" placeholder="00000-000" class="w-full" />
          </UFormField>
        </div>

        <UFormField label="Observações" name="notes">
          <UTextarea v-model="state.notes" placeholder="Observações internas..." class="w-full" />
        </UFormField>

        <div class="flex justify-end gap-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="subtle"
            @click="open = false"
          />
          <UButton
            label="Salvar"
            color="primary"
            variant="solid"
            type="submit"
            :loading="loading"
          />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
