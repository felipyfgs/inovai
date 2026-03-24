<script setup lang="ts">
import type { Company } from '~/types'

const props = defineProps<{ company: Company }>()
const emit = defineEmits<{ deleted: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { del } = useApiMutation()

async function onSubmit() {
  loading.value = true
  try {
    await del(`/companies/${props.company.id}`)
    toast.add({ title: 'Empresa excluída', description: props.company.razao_social, color: 'success' })
    open.value = false
    emit('deleted')
  } catch {
    toast.add({ title: 'Erro', description: 'Erro ao excluir.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Excluir empresa"
    :description="`Tem certeza que deseja excluir ${company.razao_social}? Esta ação não pode ser desfeita.`"
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
