<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Mdfe } from '~/types'

const props = defineProps<{ mdfe: Mdfe }>()
const emit = defineEmits<{ cancelled: [] }>()

const schema = z.object({
  justificativa: z.string().min(15, 'Mínimo 15 caracteres').max(255, 'Máximo 255 caracteres')
})

type Schema = z.output<typeof schema>

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const formRef = useTemplateRef('formRef')
const { cancelMdfe, extractMessage } = useMdfe()

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
    await cancelMdfe(props.mdfe.id, event.data.justificativa)
    toast.add({ title: 'MDF-e cancelado', description: `MDF-e nº ${props.mdfe.numero} cancelado com sucesso`, color: 'success' })
    open.value = false
    emit('cancelled')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao cancelar MDF-e.', color: 'error' })
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
    title="Cancelar MDF-e"
    :description="`Cancelar MDF-e nº ${mdfe.numero}`"
    :ui="{ footer: 'justify-end' }"
  >
    <slot />

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        @submit="onSubmit"
      >
        <UFormField
          label="Justificativa"
          name="justificativa"
          required
          description="Informe o motivo do cancelamento (mínimo 15 caracteres)"
        >
          <UTextarea
            v-model="state.justificativa"
            class="w-full"
            placeholder="Informe o motivo do cancelamento..."
            :rows="4"
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
