<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Cte } from '~/types'

const props = defineProps<{ cte: Cte }>()
const emit = defineEmits<{ cancelled: [] }>()

const schema = z.object({
  justificativa: z.string().min(15, 'Mínimo 15 caracteres').max(255, 'Máximo 255 caracteres')
})

type Schema = z.output<typeof schema>

const open = ref(false)
const loading = ref(false)

function openModal() {
  open.value = true
}

defineExpose({ openModal })
const toast = useToast()
const { cancelCte, extractMessage } = useCte()
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
    await cancelCte(props.cte.id, event.data.justificativa)
    toast.add({ title: 'CT-e cancelado', description: `CT-e nº ${props.cte.numero} cancelado com sucesso.`, color: 'success' })
    open.value = false
    emit('cancelled')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao cancelar CT-e.', color: 'error' })
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
    title="Cancelar CT-e"
    :description="`Cancelar CT-e nº ${cte.numero}`"
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
        <p class="text-sm text-muted">
          Informe a justificativa do cancelamento (mínimo 15 caracteres).
        </p>
        <UFormField label="Justificativa" name="justificativa" required>
          <UTextarea
            v-model="state.justificativa"
            class="w-full"
            placeholder="Informe o motivo do cancelamento..."
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
