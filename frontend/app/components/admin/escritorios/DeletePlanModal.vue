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
  if (!props.office?.subscription?.plan?.id) return
  loading.value = true
  try {
    await del(`/admin/offices/${props.office.id}/plan`)
    useAppToast().success('Plano excluído com sucesso')
    open.value = false
    emit('deleted')
  } catch (e: unknown) {
    handleError(e, 'Erro ao excluir plano')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Excluir plano"
    @update:open="(v) => { if (!v) emit('deleted') }"
  >
    <template #body>
      <p class="text-muted">
        Deseja excluir o plano <strong>{{ office?.subscription?.plan?.name }}</strong> do escritório <strong>{{ office?.name }}</strong>?
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
