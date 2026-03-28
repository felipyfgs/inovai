<script setup lang="ts">
import type { Cte } from '~/types'

const props = defineProps<{ cte: Cte }>()
const emit = defineEmits<{ deleted: [] }>()

const open = ref(true)
const loading = ref(false)

function openModal() {
  open.value = true
}

defineExpose({ openModal })
const toast = useToast()
const { del } = useApiMutation()
const { extractMessage } = useApiError()

async function onSubmit() {
  loading.value = true
  try {
    await del(`/ctes/${props.cte.id}`)
    toast.add({ title: 'CT-e excluído', description: `CT-e nº ${props.cte.numero}`, color: 'success' })
    open.value = false
    emit('deleted')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao excluir CT-e.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Excluir CT-e"
    :description="`Tem certeza que deseja excluir o CT-e nº ${cte.numero}? Esta ação não pode ser desfeita.`"
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
