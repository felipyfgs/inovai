<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Plan } from '~/types'

const props = defineProps<{ plan: Plan }>()
const emit = defineEmits<{ updated: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { put } = useApiMutation()
const formRef = useTemplateRef('formRef')

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  description: z.string().optional(),
  price: z.coerce.number().min(0, 'Preço deve ser maior ou igual a 0'),
  max_companies: z.coerce.number().int().min(1, 'Mínimo 1 empresa'),
  max_nfs_month: z.coerce.number().int().min(0, 'Mínimo 0'),
  is_active: z.boolean()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({})

watch(() => props.plan, (plan) => {
  if (plan) {
    Object.assign(state, {
      name: plan.name,
      description: plan.description || '',
      price: plan.price,
      max_companies: plan.max_companies,
      max_nfs_month: plan.max_nfs_month,
      is_active: plan.is_active
    })
    open.value = true
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await put(`/admin/plans/${props.plan.id}`, event.data)
    toast.add({ title: 'Plano atualizado com sucesso', color: 'success' })
    open.value = false
    emit('updated')
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao atualizar plano.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model="open"
    title="Editar Plano"
    description="Atualize os dados do plano."
    :ui="{ content: 'w-full sm:max-w-lg', footer: 'justify-end' }"
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
        <UFormField label="Nome do Plano" name="name" required>
          <UInput v-model="state.name" class="w-full" />
        </UFormField>

        <UFormField label="Descrição" name="description">
          <UTextarea v-model="state.description" class="w-full" />
        </UFormField>

        <div class="grid grid-cols-2 gap-4">
          <UFormField label="Preço (R$)" name="price" required>
            <UInput
              v-model="state.price"
              type="number"
              step="0.01"
              min="0"
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
