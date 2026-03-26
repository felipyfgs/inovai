<script setup lang="ts">
import type { Pessoa } from '~/types'

const props = defineProps<{ pessoa: Pessoa }>()
const emit = defineEmits<{ deleted: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { del } = useApiMutation()
const { extractMessage } = useApiError()

async function onSubmit() {
  loading.value = true
  try {
    await del(`/pessoas/${props.pessoa.id}`)
    toast.add({ title: 'Pessoa excluída', description: props.pessoa.razao_social, color: 'success' })
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
    title="Excluir pessoa"
    :description="`Tem certeza que deseja excluir ${pessoa.razao_social}? Esta ação não pode ser desfeita.`"
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
