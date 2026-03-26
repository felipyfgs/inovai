<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Office, Plan } from '~/types'

const props = defineProps<{ office: Office }>()
const emit = defineEmits<{ updated: [] }>()

const open = defineModel<boolean>('open', { default: false })
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const { data: plans } = useApi<Plan[]>('/admin/plans', { lazy: true })

const schema = z.object({
  plan_id: z.number().min(1, 'Selecione um plano'),
  starts_at: z.string().optional(),
  ends_at: z.string().optional()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  plan_id: undefined,
  starts_at: '',
  ends_at: ''
})

watch(open, (isOpen) => {
  if (isOpen && props.office.subscription) {
    state.plan_id = props.office.subscription.plan?.id
    state.starts_at = props.office.subscription.starts_at
      ? new Date(props.office.subscription.starts_at).toISOString().split('T')[0]
      : ''
    state.ends_at = props.office.subscription.ends_at
      ? new Date(props.office.subscription.ends_at).toISOString().split('T')[0]
      : ''
  }
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    const data: Record<string, unknown> = { plan_id: event.data.plan_id }
    if (event.data.starts_at) data.starts_at = event.data.starts_at
    if (event.data.ends_at) data.ends_at = event.data.ends_at

    await post(`/admin/offices/${props.office.id}/assign-plan`, data)
    toast.add({ title: 'Plano atualizado com sucesso', color: 'success' })
    open.value = false
    emit('updated')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao atualizar plano.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Editar Plano"
    description="Altere o plano deste escritório."
    :ui="{ content: 'w-full sm:max-w-lg', footer: 'justify-end' }"
  >
    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-4"
        @submit="onSubmit"
      >
        <UFormField label="Plano" name="plan_id" required>
          <USelect
            v-model="state.plan_id"
            :items="plans?.map(p => ({ label: p.name, value: p.id })) || []"
            placeholder="Selecione um plano"
            class="w-full"
          />
        </UFormField>

        <div class="grid grid-cols-2 gap-4">
          <UFormField label="Data Início" name="starts_at">
            <UInput v-model="state.starts_at" type="date" class="w-full" />
          </UFormField>

          <UFormField label="Data Término" name="ends_at">
            <UInput v-model="state.ends_at" type="date" class="w-full" />
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
