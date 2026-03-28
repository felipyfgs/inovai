<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Company, OfficePlan } from '~/types'

const props = defineProps<{ company: Company }>()
const emit = defineEmits<{ assigned: [] }>()

const open = ref(false)
const loading = ref(false)
const formRef = useTemplateRef('formRef')
const { post } = useApiMutation()
const { extractMessage } = useApiError()

const { data: plans } = useApi<OfficePlan[]>('/office-plans', { lazy: true })

const schema = z.object({
  office_plan_id: z.number().min(1, 'Selecione um plano')
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  office_plan_id: undefined
})

watch(open, (isOpen) => {
  if (isOpen && props.company.office_plan_id) {
    state.office_plan_id = props.company.office_plan_id
  } else {
    state.office_plan_id = undefined
  }
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await post(`/companies/${props.company.id}/assign-plan`, { office_plan_id: event.data.office_plan_id })
    useAppToast().success('Plano atribuído com sucesso')
    open.value = false
    emit('assigned')
  } catch (e: unknown) {
    useAppToast().error(extractMessage(e))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Atribuir Plano"
    :description="`Selecionar plano para ${company.fantasia || company.razao_social}`"
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
        <UFormField label="Plano" name="office_plan_id" required>
          <USelect
            v-model="state.office_plan_id"
            :items="(plans || []).filter((p: OfficePlan) => p.is_active).map((p: OfficePlan) => ({ label: `${p.name} - ${formatCurrency(p.price)}/mês`, value: p.id }))"
            placeholder="Selecione um plano"
            class="w-full"
          />
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
        label="Atribuir Plano"
        color="primary"
        :loading="loading"
        @click="formRef?.submit()"
      />
    </template>
  </UModal>
</template>
