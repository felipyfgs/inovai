<script setup lang="ts">
import type { Orcamento } from '~/types'

const props = defineProps<{ orcamento: Orcamento }>()
const emit = defineEmits<{ deleted: [] }>()

const open = ref(true)
const loading = ref(false)
const toast = useToast()
const { del } = useApiMutation()
const { extractMessage } = useApiError()

async function onSubmit() {
  loading.value = true
  try {
    await del(`/orcamentos/${props.orcamento.id}`)
    toast.add({ title: 'Orçamento excluído', description: `Orçamento #${props.orcamento.numero}`, color: 'success' })
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
    title="Excluir orçamento"
    :description="`Tem certeza que deseja excluir o orçamento #${orcamento.numero}? Esta ação não pode ser desfeita.`"
  >
    <slot />

    <template #body>
      <div class="flex justify-end gap-2">
        <UButton
          label="Cancelar"
          color="neutral"
          variant="subtle"
          @click="open = false"
        />
        <UButton
          label="Excluir"
          color="error"
          variant="solid"
          :loading="loading"
          @click="onSubmit"
        />
      </div>
    </template>
  </UModal>
</template>
