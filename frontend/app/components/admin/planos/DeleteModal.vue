<script setup lang="ts">
import type { Plan } from '~/types'

const props = defineProps<{ plan: Plan }>()
const emit = defineEmits<{ deleted: [] }>()

const open = defineModel<boolean>('open', { default: false })
const loading = ref(false)
const { del } = useApiMutation()
const { handleError } = useApiError()

async function onSubmit() {
  loading.value = true
  try {
    await del(`/admin/plans/${props.plan.id}`)
    useAppToast().success('Plano removido com sucesso')
    open.value = false
    emit('deleted')
  } catch (e: unknown) {
    handleError(e, 'Erro ao remover plano')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Confirmar exclusão"
    description="Esta ação não pode ser desfeita."
  >
    <template #body>
      <p>
        Deseja excluir o plano <strong>{{ plan?.name }}</strong>? Esta ação não pode ser desfeita.
      </p>
    </template>
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
