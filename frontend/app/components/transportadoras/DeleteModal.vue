<script setup lang="ts">
import type { Transportadora } from '~/types'

const props = defineProps<{ transportadora: Transportadora }>()
const emit = defineEmits<{ deleted: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { del } = useApiMutation()
const { extractMessage } = useApiError()

async function onSubmit() {
  loading.value = true
  try {
    await del(`/transportadoras/${props.transportadora.id}`)
    toast.add({ title: 'Transportadora excluída', description: props.transportadora.razao_social, color: 'success' })
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
    title="Excluir transportadora"
    :description="`Tem certeza que deseja excluir ${transportadora.razao_social}? Esta ação não pode ser desfeita.`"
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
