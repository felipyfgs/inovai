<script setup lang="ts">
import { z } from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Pessoa, PaginatedResponse } from '~/types'

const props = defineProps<{
  tipo: 'pagar' | 'receber'
}>()

const emit = defineEmits<{ created: [] }>()

const open = defineModel<boolean>('open', { default: false })

const schema = z.object({
  descricao: z.string().min(1, 'Obrigatório').max(255),
  pessoa_id: z.number().min(1, 'Selecione uma pessoa').optional().default(0),
  documento: z.string().max(50).optional().default(''),
  categoria: z.string().max(100).optional().default(''),
  data_emissao: z.string().min(1, 'Obrigatório'),
  data_vencimento: z.string().min(1, 'Obrigatório'),
  valor_original: z.coerce.number().min(0.01, 'Valor deve ser maior que zero'),
  observacoes: z.string().max(500).optional().default('')
})

type Schema = z.output<typeof schema>

const formRef = useTemplateRef('formRef')
const loading = ref(false)
const state = reactive<Partial<Schema>>({
  data_emissao: new Date().toISOString().split('T')[0]
})

const pessoaLabel = props.tipo === 'pagar' ? 'Fornecedor' : 'Cliente'
const pessoaTipo = props.tipo === 'pagar' ? 'fornecedor' : 'cliente'

const { data: pessoas } = useApi<PaginatedResponse<Pessoa>>('/pessoas', {
  query: { per_page: 999, tipo: pessoaTipo, active_only: true },
  lazy: true
})

const pessoaOptions = computed(() => {
  return (pessoas.value?.data ?? []).map(p => ({
    value: p.id,
    label: `${p.razao_social}${p.cpf_cnpj ? ' - ' + p.cpf_cnpj : ''}`
  }))
})

const { createConta } = useContas()

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await createConta({
      tipo: props.tipo,
      descricao: event.data.descricao,
      pessoa_id: event.data.pessoa_id || null,
      documento: event.data.documento || null,
      categoria: event.data.categoria || null,
      data_emissao: event.data.data_emissao,
      data_vencimento: event.data.data_vencimento,
      valor_original: event.data.valor_original,
      observacoes: event.data.observacoes || null
    })
    open.value = false
    Object.assign(state, { data_emissao: new Date().toISOString().split('T')[0] })
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
        Nova Conta {{ tipo === 'pagar' ? 'a Pagar' : 'a Receber' }}
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
        <UFormField label="Descrição" name="descricao" required>
          <UInput v-model="state.descricao" placeholder="Descrição da conta" />
        </UFormField>

        <UFormField :label="pessoaLabel" name="pessoa_id">
          <USelectMenu
            v-model="state.pessoa_id"
            :items="pessoaOptions"
            :placeholder="`Selecione ${pessoaLabel.toLowerCase()}`"
            value-key="value"
            option-attribute="label"
          />
        </UFormField>

        <div class="grid grid-cols-2 gap-4">
          <UFormField label="Documento" name="documento">
            <UInput v-model="state.documento" placeholder="Nº documento" />
          </UFormField>
          <UFormField label="Categoria" name="categoria">
            <UInput v-model="state.categoria" placeholder="Ex: Aluguel, Fornecedor..." />
          </UFormField>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <UFormField label="Data Emissão" name="data_emissao" required>
            <UInput v-model="state.data_emissao" type="date" />
          </UFormField>
          <UFormField label="Data Vencimento" name="data_vencimento" required>
            <UInput v-model="state.data_vencimento" type="date" />
          </UFormField>
        </div>

        <UFormField label="Valor (R$)" name="valor_original" required>
          <UInput v-model="state.valor_original" type="number" step="0.01" />
        </UFormField>

        <UFormField label="Observações" name="observacoes">
          <UTextarea v-model="state.observacoes" />
        </UFormField>

        <div class="flex justify-end gap-3">
          <UButton variant="ghost" label="Cancelar" @click="open = false" />
          <UButton type="submit" :loading="loading" label="Criar Conta" />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
