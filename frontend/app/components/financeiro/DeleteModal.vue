<script setup lang="ts">
import type { Conta } from '~/types'

const props = defineProps<{
  conta: Conta | null
}>()

const emit = defineEmits<{ deleted: [] }>()

const open = defineModel<boolean>('open', { default: false })

const loading = ref(false)

async function onConfirm() {
  if (!props.conta) return
  loading.value = true
  try {
    const { deleteConta } = useContas()
    await deleteConta(props.conta.id)
    open.value = false
    emit('deleted')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open">
    <template #header>
      <h3 class="font-semibold">
        Excluir Conta
      </h3>
    </template>

    <template #body>
      <div class="space-y-4">
        <p class="text-sm text-muted">
          Tem certeza que deseja excluir a conta <strong>{{ conta?.descricao }}</strong>?
        </p>

        <div class="flex justify-end gap-3">
          <UButton variant="ghost" label="Cancelar" @click="open = false" />
          <UButton
            color="error"
            :loading="loading"
            label="Excluir"
            @click="onConfirm"
          />
        </div>
      </div>
    </template>
  </UModal>
</template>
