<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Nfe } from '~/types'

const props = defineProps<{ nota: Nfe }>()
const emit = defineEmits<{ cancelled: [] }>()

const schema = z.object({
  justificativa: z.string().min(15, 'Mínimo 15 caracteres').max(255, 'Máximo 255 caracteres')
})

type Schema = z.output<typeof schema>

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const state = reactive<Partial<Schema>>({
  justificativa: ''
})

watch(open, (val) => {
  if (val) {
    state.justificativa = ''
  }
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await post(`/nfes/${props.nota.id}/cancel`, event.data)
    toast.add({ title: 'NF-e cancelada', description: `NF-e nº ${props.nota.numero}`, color: 'success' })
    open.value = false
    emit('cancelled')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao cancelar.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}

function openModal() {
  open.value = true
}

defineExpose({ openModal })
</script>

<template>
  <UModal
    v-model:open="open"
    title="Cancelar NF-e"
    :description="`Cancelar NF-e nº ${nota.numero}. Informe a justificativa do cancelamento.`"
    :ui="{ footer: 'justify-end' }"
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
        <UFormField label="Justificativa" name="justificativa" required>
          <UTextarea
            v-model="state.justificativa"
            class="w-full"
            placeholder="Informe a justificativa do cancelamento (mínimo 15 caracteres)..."
            :rows="4"
            maxlength="255"
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
        label="Confirmar Cancelamento"
        color="error"
        :loading="loading"
        @click="handleSubmit"
      />
    </template>
  </UModal>
</template>
