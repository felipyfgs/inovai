<script setup lang="ts">
import { z } from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Fornecedor } from '~/types'

const props = defineProps<{
  fornecedor: Fornecedor | null
}>()

const emit = defineEmits<{
  updated: []
  close: []
}>()

const schema = z.object({
  condicao_pagamento: z.string().max(100).optional().default(''),
  prazo_entrega: z.coerce.number().min(0).optional().default(0),
  avaliacao: z.coerce.number().min(1).max(5).optional().default(0)
})

type Schema = z.output<typeof schema>

const formRef = useTemplateRef('formRef')
const loading = ref(false)
const open = ref(false)
const state = reactive<Partial<Schema>>({})

watch(() => props.fornecedor, (f) => {
  if (f) {
    state.condicao_pagamento = f.condicao_pagamento ?? ''
    state.prazo_entrega = f.prazo_entrega ?? 0
    state.avaliacao = f.avaliacao ?? 0
    open.value = true
  }
}, { immediate: true })

const { put } = useApiMutation()
const { extractMessage } = useApiError()

async function onSubmit(event: FormSubmitEvent<Schema>) {
  if (!props.fornecedor) return
  loading.value = true
  try {
    await put(`/fornecedores/${props.fornecedor.id}`, event.data)
    useAppToast().success('Fornecedor atualizado com sucesso.')
    open.value = false
    emit('updated')
  } catch (error) {
    useAppToast().error(extractMessage(error))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" @update:open="emit('close')">
    <template #header>
      <h3 class="font-semibold">
        Dados do Fornecedor
      </h3>
    </template>

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-4"
        @submit="onSubmit"
      >
        <UFormField label="Condição de Pagamento" name="condicao_pagamento">
          <UInput v-model="state.condicao_pagamento" placeholder="Ex: 30/60/90 dias" />
        </UFormField>

        <UFormField label="Prazo de Entrega (dias)" name="prazo_entrega">
          <UInput v-model="state.prazo_entrega" type="number" placeholder="Dias úteis" />
        </UFormField>

        <UFormField label="Avaliação (1-5 estrelas)" name="avaliacao">
          <UInput
            v-model="state.avaliacao"
            type="number"
            min="1"
            max="5"
            placeholder="1 a 5"
          />
        </UFormField>

        <div class="flex justify-end gap-3">
          <UButton variant="ghost" label="Cancelar" @click="open = false" />
          <UButton type="submit" :loading="loading" label="Salvar" />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
