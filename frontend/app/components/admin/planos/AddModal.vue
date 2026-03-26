<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const emit = defineEmits<{ created: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  description: z.string().optional(),
  price: z.coerce.number().min(0, 'Preço deve ser maior ou igual a 0'),
  max_companies: z.coerce.number().int().min(1, 'Mínimo 1 empresa'),
  max_nfs_month: z.coerce.number().int().min(0, 'Mínimo 0'),
  is_active: z.boolean().default(true)
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  description: '',
  price: 0,
  max_companies: 1,
  max_nfs_month: 0,
  is_active: true
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await post('/admin/plans', event.data)
    toast.add({ title: 'Plano criado com sucesso', color: 'success' })
    open.value = false
    emit('created')
    Object.assign(state, { name: '', description: '', price: 0, max_companies: 1, max_nfs_month: 0, is_active: true })
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao criar plano.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Novo Plano" description="Crie um novo plano de assinatura.">
    <UButton label="Novo Plano" icon="i-lucide-plus" />

    <template #body>
      <UForm
        :schema="schema"
        :state="state"
        class="space-y-4"
        @submit="onSubmit"
      >
        <UFormField label="Nome do Plano" name="name" required>
          <UInput v-model="state.name" placeholder="Ex: Plano Básico" class="w-full" />
        </UFormField>

        <UFormField label="Descrição" name="description">
          <UTextarea v-model="state.description" placeholder="Descrição do plano..." class="w-full" />
        </UFormField>

        <div class="grid grid-cols-2 gap-4">
          <UFormField label="Preço (R$)" name="price" required>
            <UInput
              v-model="state.price"
              type="number"
              step="0.01"
              min="0"
              placeholder="0,00"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Status" name="is_active">
            <USwitch v-model="state.is_active" label="Ativo" />
          </UFormField>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <UFormField label="Máx. Empresas" name="max_companies" required>
            <UInput
              v-model="state.max_companies"
              type="number"
              min="1"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Máx. NFes/Mês" name="max_nfs_month" required>
            <UInput
              v-model="state.max_nfs_month"
              type="number"
              min="0"
              class="w-full"
            />
          </UFormField>
        </div>

        <div class="flex justify-end gap-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="subtle"
            @click="open = false"
          />
          <UButton
            label="Criar Plano"
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
