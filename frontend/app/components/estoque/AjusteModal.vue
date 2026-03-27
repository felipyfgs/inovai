<script setup lang="ts">
import { z } from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Produto, PaginatedResponse } from '~/types'

const emit = defineEmits<{ created: [] }>()

const open = defineModel<boolean>('open', { default: false })

const schema = z.object({
  produto_id: z.number().min(1, 'Selecione um produto'),
  tipo: z.enum(['entrada', 'saida', 'ajuste']),
  quantidade: z.coerce.number().min(0.0001, 'Quantidade deve ser maior que zero'),
  custo_unitario: z.coerce.number().min(0).optional().default(0),
  localizacao: z.string().max(255).optional().default(''),
  observacoes: z.string().max(500).optional().default('')
})

type Schema = z.output<typeof schema>

const formRef = useTemplateRef('formRef')
const loading = ref(false)
const state = reactive<Partial<Schema>>({})

const { data: produtos } = useApi<PaginatedResponse<Produto>>('/produtos', {
  query: { per_page: 999, active_only: true },
  lazy: true
})

const produtoOptions = computed(() => {
  return (produtos.value?.data ?? []).map(p => ({
    value: p.id,
    label: `${p.codigo ? p.codigo + ' - ' : ''}${p.descricao}`
  }))
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    const { ajuste } = useEstoque()
    await ajuste({
      produto_id: event.data.produto_id,
      tipo: event.data.tipo,
      quantidade: event.data.quantidade,
      custo_unitario: event.data.custo_unitario || undefined,
      localizacao: event.data.localizacao || undefined,
      observacoes: event.data.observacoes || undefined
    })
    open.value = false
    Object.assign(state, {})
    emit('created')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open">
    <template #header>
      <h3 class="font-semibold">
        Ajuste de Estoque
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
        <UFormField label="Produto" name="produto_id" required>
          <USelectMenu
            v-model="state.produto_id"
            :items="produtoOptions"
            placeholder="Selecione um produto"
            value-key="value"
            option-attribute="label"
          />
        </UFormField>

        <UFormField label="Tipo de Movimentação" name="tipo" required>
          <USelect
            v-model="state.tipo"
            :items="[
              { value: 'entrada', label: 'Entrada' },
              { value: 'saida', label: 'Saída' },
              { value: 'ajuste', label: 'Ajuste' }
            ]"
            value-key="value"
            label-key="label"
          />
        </UFormField>

        <div class="grid grid-cols-2 gap-4">
          <UFormField label="Quantidade" name="quantidade" required>
            <UInput v-model="state.quantidade" type="number" step="0.0001" />
          </UFormField>
          <UFormField label="Custo Unitário" name="custo_unitario">
            <UInput v-model="state.custo_unitario" type="number" step="0.01" />
          </UFormField>
        </div>

        <UFormField label="Localização" name="localizacao">
          <UInput v-model="state.localizacao" placeholder="Ex: Prateleira A3" />
        </UFormField>

        <UFormField label="Observações" name="observacoes">
          <UTextarea v-model="state.observacoes" placeholder="Motivo do ajuste..." />
        </UFormField>

        <div class="flex justify-end gap-3">
          <UButton variant="ghost" label="Cancelar" @click="open = false" />
          <UButton type="submit" :loading="loading" label="Realizar Ajuste" />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
