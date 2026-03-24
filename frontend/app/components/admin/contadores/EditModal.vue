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
const formRef = useTemplateRef('formRef')

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  cnpj: z.string().min(14, 'CNPJ inválido'),
  email: z.string().email('E-mail inválido').optional().or(z.literal('')),
  phone: z.string().optional(),
  type: z.enum(['contador', 'direct']),
  is_active: z.boolean(),
  is_reseller: z.boolean(),
  reseller_commission: z.coerce.number().min(0).max(100),
  notes: z.string().optional()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({})

watch(() => props.office, (office) => {
  if (office) {
    Object.assign(state, {
      name: office.name,
      cnpj: office.cnpj || '',
      email: office.email || '',
      phone: office.phone || '',
      type: office.type === 'admin' ? 'contador' : office.type,
      is_active: office.is_active,
      is_reseller: office.is_reseller,
      reseller_commission: office.reseller_commission,
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
  <UModal
    v-model:open="open"
    title="Editar Cadastro"
    description="Atualize os dados do escritório contábil ou cliente direto."
    :ui="{ content: 'w-full sm:max-w-2xl', footer: 'justify-end' }"
  >
    <slot />

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
            <UInput v-model="state.name" placeholder="Nome do escritório ou empresa" class="w-full" />
          </UFormField>

          <UFormField label="CNPJ" name="cnpj" required>
            <UInput v-model="state.cnpj" placeholder="00.000.000/0001-00" class="w-full" />
          </UFormField>

          <UFormField label="Tipo" name="type" required>
            <USelect
              v-model="state.type"
              :items="[
                { label: 'Contador (escritório contábil)', value: 'contador' },
                { label: 'Cliente Direto', value: 'direct' }
              ]"
              class="w-full"
            />
          </UFormField>

          <UFormField label="E-mail" name="email">
            <UInput
              v-model="state.email"
              type="email"
              placeholder="contato@empresa.com.br"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Telefone" name="phone">
            <UInput v-model="state.phone" placeholder="(00) 00000-0000" class="w-full" />
          </UFormField>

          <UFormField label="Status" name="is_active">
            <USwitch v-model="state.is_active" label="Ativo" />
          </UFormField>

          <UFormField label="É revendedor?" name="is_reseller">
            <USwitch v-model="state.is_reseller" label="Permite sub-revenda" />
          </UFormField>

          <UFormField v-if="state.is_reseller" label="Comissão (%)" name="reseller_commission">
            <UInput
              v-model="state.reseller_commission"
              type="number"
              min="0"
              max="100"
              step="0.5"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Observações" name="notes" class="sm:col-span-2">
            <UTextarea v-model="state.notes" placeholder="Observações internas..." class="w-full" />
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
        label="Salvar"
        color="primary"
        :loading="loading"
        @click="formRef?.submit()"
      />
    </template>
  </UModal>
</template>
