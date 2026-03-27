<script setup lang="ts">
import { z } from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const { emitente, updateCsc } = useEmitente()

const schema = z.object({
  csc_id: z.string().max(10).optional().default(''),
  csc_token: z.string().max(100).optional().default('')
})

type Schema = z.output<typeof schema>

const formRef = useTemplateRef('formRef')
const loading = ref(false)

const state = reactive<Partial<Schema>>({})

watch(emitente, (e) => {
  if (e) {
    state.csc_id = e.csc_id ?? ''
    state.csc_token = e.csc_token ?? ''
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await updateCsc({
      csc_id: event.data.csc_id || null,
      csc_token: event.data.csc_token || null
    })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="!emitente" class="flex items-center justify-center h-48">
    <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
  </div>

  <UForm
    v-else
    ref="formRef"
    :schema="schema"
    :state="state"
    class="space-y-6"
    @submit="onSubmit"
  >
    <UPageCard title="CSC - Código de Segurança do Contribuinte" variant="subtle">
      <p class="text-sm text-muted mb-4">
        O CSC é utilizado para autorizar a emissão de NFC-e. Obtido na Secretaria da Fazenda do seu estado.
      </p>

      <div class="space-y-4">
        <UFormField label="CSC ID" name="csc_id">
          <UInput v-model="state.csc_id" placeholder="Identificador do CSC" />
        </UFormField>

        <UFormField label="CSC Token" name="csc_token">
          <UInput
            v-model="state.csc_token"
            type="password"
            placeholder="Token do CSC"
          />
        </UFormField>
      </div>
    </UPageCard>

    <div class="flex justify-end">
      <UButton type="submit" :loading="loading" label="Salvar CSC" />
    </div>
  </UForm>
</template>
