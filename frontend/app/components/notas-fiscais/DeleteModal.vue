<script setup lang="ts">
import type { Pessoa } from '~/types'

const props = defineProps<{ nota: { id: number, numero: number } }>()
const emit = defineEmits<{ deleted: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { del } = useApiMutation()
const { extractMessage } = useApiError()

async function onSubmit() {
  loading.value = true
  try {
    await del(`/notas-fiscais/${props.nota.id}`)
    toast.add({ title: 'NF-e excluída', description: `NF-e nº ${props.nota.numero}`, color: 'success' })
    open.value = false
    emit('deleted')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao excluir.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Excluir NF-e"
    :description="`Tem certeza que deseja excluir a NF-e nº ${nota.numero}? Esta ação não pode ser desfeita.`"
    :ui="{ footer: 'justify-end' }"
  >
    <slot />

    <template #footer="{ close }">
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="close"
      />
      <UButton
        label="Excluir"
        color="error"
        :loading="loading"
        @click="onSubmit"
      />
    </template>
  </UModal>
</template>
