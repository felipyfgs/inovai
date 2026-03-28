<script setup lang="ts">
import type { Produto } from '~/types'

const props = defineProps<{ produto: Produto }>()
const emit = defineEmits<{ deleted: [] }>()

const open = ref(true)
const loading = ref(false)
const toast = useToast()
const { del } = useApiMutation()
const { extractMessage } = useApiError()

async function onSubmit() {
  loading.value = true
  try {
    await del(`/produtos/${props.produto.id}`)
    toast.add({ title: 'Produto excluído', description: props.produto.descricao, color: 'success' })
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
    title="Excluir produto"
    :description="`Tem certeza que deseja excluir ${produto.descricao}? Esta ação não pode ser desfeita.`"
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
