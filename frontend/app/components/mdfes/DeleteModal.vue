<script setup lang="ts">
import type { Mdfe } from '~/types'

const props = defineProps<{ mdfe: Mdfe }>()
const emit = defineEmits<{ deleted: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { del } = useApiMutation()
const { extractMessage } = useApiError()

async function onSubmit() {
  loading.value = true
  try {
    await del(`/mdfes/${props.mdfe.id}`)
    toast.add({ title: 'MDF-e excluído', description: `MDF-e nº ${props.mdfe.numero} excluído com sucesso`, color: 'success' })
    open.value = false
    emit('deleted')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao excluir MDF-e.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Excluir MDF-e"
    :description="`Tem certeza que deseja excluir o MDF-e nº ${mdfe.numero}? Esta ação não pode ser desfeita.`"
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
