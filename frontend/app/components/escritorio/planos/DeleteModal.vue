<script setup lang="ts">
import type { OfficePlan } from '~/types'

const props = defineProps<{ plan: OfficePlan }>()
const emit = defineEmits<{ deleted: [] }>()

const open = ref(true)
const { del } = useApiMutation()
const { extractMessage } = useApiError()

async function onSubmit() {
  try {
    await del(`/office-plans/${props.plan.id}`)
    useAppToast().success('Plano removido com sucesso')
    open.value = false
    emit('deleted')
  } catch (error: unknown) {
    useAppToast().error(extractMessage(error))
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Excluir Plano"
    description="Esta ação não pode ser desfeita."
  >
    <template #body>
      <p>
        Tem certeza que deseja excluir o plano <strong>{{ plan.name }}</strong>?
      </p>

      <div class="flex justify-end gap-2 mt-4">
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
          loading-auto
          @click="onSubmit"
        />
      </div>
    </template>
  </UModal>
</template>
