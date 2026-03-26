<script setup lang="ts">
import type { Office } from '~/types'

const props = defineProps<{ office: Office }>()
const emit = defineEmits<{ deleted: [] }>()

const open = ref(false)
const loading = ref(false)
const { del } = useApiMutation()
const { handleError } = useApiError()

watch(() => props.office, (val) => {
  if (val) open.value = true
}, { immediate: true })

async function onSubmit() {
  loading.value = true
  try {
    await del(`/admin/offices/${props.office.id}`)
    useAppToast().success('Removido com sucesso')
    open.value = false
    emit('deleted')
  } catch (e: unknown) {
    handleError(e, 'Erro ao remover')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Confirmar exclusão"
    :description="`Esta ação não pode ser desfeita.`"
    @update:open="(v) => { if (!v) emit('deleted') }"
  >
    <template #body>
      <p>
        Deseja excluir <strong>{{ office?.name }}</strong>? Esta ação não pode ser desfeita.
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
