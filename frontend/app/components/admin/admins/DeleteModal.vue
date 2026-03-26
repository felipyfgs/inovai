<script setup lang="ts">
import type { AdminUser } from '~/types'

const props = defineProps<{ admin: AdminUser }>()
const emit = defineEmits<{ deleted: [] }>()

const open = ref(false)
const loading = ref(false)
const { del } = useApiMutation()
const { extractMessage } = useApiError()

watch(() => props.admin, (admin) => {
  if (admin) {
    open.value = true
  }
}, { immediate: true })

async function onDelete() {
  loading.value = true
  try {
    await del(`/admin/admins/${props.admin.id}`)
    useAppToast().success('Administrador removido com sucesso')
    open.value = false
    emit('deleted')
  } catch (error) {
    useAppToast().error(extractMessage(error))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Excluir Administrador">
    <slot />

    <template #body>
      <div class="space-y-4">
        <p class="text-sm text-muted">
          Tem certeza que deseja excluir o administrador <span class="font-medium text-highlighted">{{ admin.name }}</span>?
          Esta ação não pode ser desfeita.
        </p>

        <div class="flex justify-end gap-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="outline"
            @click="open = false"
          />
          <UButton
            label="Excluir"
            color="error"
            :loading="loading"
            @click="onDelete"
          />
        </div>
      </div>
    </template>
  </UModal>
</template>
