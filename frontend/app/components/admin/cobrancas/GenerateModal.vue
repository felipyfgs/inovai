<script setup lang="ts">
const emit = defineEmits<{ generated: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const { handleError } = useApiError()

const form = reactive({ reference: '', due_at: '' })

async function onSubmit() {
  loading.value = true
  try {
    const res = await post<{ message: string, created: number }>('/admin/invoices/generate-monthly', form)
    toast.add({ title: res.message, color: 'success' })
    open.value = false
    emit('generated')
    Object.assign(form, { reference: '', due_at: '' })
  } catch (e: unknown) {
    handleError(e, 'Erro ao gerar faturas')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Gerar Mensalidades"
    description="Gera faturas mensais para todos os clientes ativos com assinatura."
    :ui="{ footer: 'justify-end' }"
  >
    <UButton
      label="Gerar Mensalidades"
      icon="i-lucide-zap"
    />

    <template #body>
      <div class="space-y-4">
        <UFormField label="Referência (ex: 2026-03)" name="reference">
          <UInput v-model="form.reference" placeholder="2026-03" class="w-full" />
        </UFormField>
        <UFormField label="Data de Vencimento" name="due_at">
          <UInput v-model="form.due_at" type="date" class="w-full" />
        </UFormField>
      </div>
    </template>
    <template #footer="{ close }">
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="close"
      />
      <UButton
        label="Gerar Faturas"
        icon="i-lucide-zap"
        :loading="loading"
        @click="onSubmit"
      />
    </template>
  </UModal>
</template>
