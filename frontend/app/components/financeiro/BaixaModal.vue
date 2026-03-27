<script setup lang="ts">
import { z } from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { ContaParcela } from '~/types'

const props = defineProps<{
  parcela: ContaParcela | null
}>()

const emit = defineEmits<{
  baixado: [data: {
    valor: number
    valor_desconto?: number
    valor_juros?: number
    valor_multa?: number
    forma_pagamento?: string
    observacoes?: string
  }]
}>()

const open = defineModel<boolean>('open', { default: false })

const schema = z.object({
  valor: z.coerce.number().min(0.01, 'Valor deve ser maior que zero'),
  valor_desconto: z.coerce.number().min(0).optional().default(0),
  valor_juros: z.coerce.number().min(0).optional().default(0),
  valor_multa: z.coerce.number().min(0).optional().default(0),
  forma_pagamento: z.string().max(50).optional().default(''),
  observacoes: z.string().max(500).optional().default('')
})

type Schema = z.output<typeof schema>

const formRef = useTemplateRef('formRef')
const loading = ref(false)
const state = reactive<Partial<Schema>>({})

watch(() => props.parcela, (p) => {
  if (p) {
    state.valor = Number(p.valor) - Number(p.valor_baixado)
    state.valor_desconto = 0
    state.valor_juros = 0
    state.valor_multa = 0
    state.forma_pagamento = ''
    state.observacoes = ''
  }
}, { immediate: true })

const formaPagamentoOptions = [
  { value: 'dinheiro', label: 'Dinheiro' },
  { value: 'pix', label: 'PIX' },
  { value: 'boleto', label: 'Boleto' },
  { value: 'cartao_credito', label: 'Cartão de Crédito' },
  { value: 'cartao_debito', label: 'Cartão de Débito' },
  { value: 'transferencia', label: 'Transferência Bancária' },
  { value: 'cheque', label: 'Cheque' }
]

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    emit('baixado', {
      valor: event.data.valor,
      valor_desconto: event.data.valor_desconto || undefined,
      valor_juros: event.data.valor_juros || undefined,
      valor_multa: event.data.valor_multa || undefined,
      forma_pagamento: event.data.forma_pagamento || undefined,
      observacoes: event.data.observacoes || undefined
    })
    open.value = false
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open">
    <template #header>
      <h3 class="font-semibold">
        Baixa de Parcela
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
        <div v-if="parcela" class="p-3 rounded-lg bg-muted/50 text-sm space-y-1">
          <div class="flex justify-between">
            <span class="text-muted">Valor da Parcela:</span>
            <span class="font-medium">{{ formatCurrency(Number(parcela.valor)) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted">Já Baixado:</span>
            <span class="font-medium">{{ formatCurrency(Number(parcela.valor_baixado)) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted">Restante:</span>
            <span class="font-medium">{{ formatCurrency(Number(parcela.valor) - Number(parcela.valor_baixado)) }}</span>
          </div>
        </div>

        <UFormField label="Valor da Baixa (R$)" name="valor" required>
          <UInput v-model="state.valor" type="number" step="0.01" />
        </UFormField>

        <div class="grid grid-cols-3 gap-4">
          <UFormField label="Desconto" name="valor_desconto">
            <UInput v-model="state.valor_desconto" type="number" step="0.01" />
          </UFormField>
          <UFormField label="Juros" name="valor_juros">
            <UInput v-model="state.valor_juros" type="number" step="0.01" />
          </UFormField>
          <UFormField label="Multa" name="valor_multa">
            <UInput v-model="state.valor_multa" type="number" step="0.01" />
          </UFormField>
        </div>

        <UFormField label="Forma de Pagamento" name="forma_pagamento">
          <USelect
            v-model="state.forma_pagamento"
            :items="formaPagamentoOptions"
            placeholder="Selecione"
            value-key="value"
            label-key="label"
          />
        </UFormField>

        <UFormField label="Observações" name="observacoes">
          <UTextarea v-model="state.observacoes" />
        </UFormField>

        <div class="flex justify-end gap-3">
          <UButton variant="ghost" label="Cancelar" @click="open = false" />
          <UButton type="submit" :loading="loading" label="Confirmar Baixa" />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
