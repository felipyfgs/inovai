<script setup lang="ts">
import { z } from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const { emitente, updateNumeracao } = useEmitente()

const schema = z.object({
  serie_nfe: z.coerce.number().min(0).max(999),
  proximo_numero_nfe: z.coerce.number().min(1).max(999999999),
  serie_nfce: z.coerce.number().min(0).max(999),
  proximo_numero_nfce: z.coerce.number().min(1).max(999999999),
  serie_cte: z.coerce.number().min(0).max(999),
  proximo_numero_cte: z.coerce.number().min(1).max(999999999),
  serie_mdfe: z.coerce.number().min(0).max(999),
  proximo_numero_mdfe: z.coerce.number().min(1).max(999999999)
})

type Schema = z.output<typeof schema>

const formRef = useTemplateRef('formRef')
const loading = ref(false)

const state = reactive<Partial<Schema>>({})

watch(emitente, (e) => {
  if (e) {
    state.serie_nfe = e.serie_nfe
    state.proximo_numero_nfe = e.proximo_numero_nfe
    state.serie_nfce = e.serie_nfce
    state.proximo_numero_nfce = e.proximo_numero_nfce
    state.serie_cte = e.serie_cte
    state.proximo_numero_cte = e.proximo_numero_cte
    state.serie_mdfe = e.serie_mdfe
    state.proximo_numero_mdfe = e.proximo_numero_mdfe
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await updateNumeracao(event.data)
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
    <UPageCard title="NF-e (Nota Fiscal Eletrônica)" variant="subtle">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UFormField label="Série" name="serie_nfe" required>
          <UInput v-model="state.serie_nfe" type="number" />
        </UFormField>
        <UFormField label="Próximo Número" name="proximo_numero_nfe" required>
          <UInput v-model="state.proximo_numero_nfe" type="number" />
        </UFormField>
      </div>
      <p class="text-xs text-muted mt-2">
        Modelo 55 - A última NF-e emitida será: Série {{ state.serie_nfe ?? '-' }}, Número {{ (Number(state.proximo_numero_nfe) || 1) - 1 }}
      </p>
    </UPageCard>

    <UPageCard title="NFC-e (Nota Fiscal de Consumidor Eletrônica)" variant="subtle">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UFormField label="Série" name="serie_nfce" required>
          <UInput v-model="state.serie_nfce" type="number" />
        </UFormField>
        <UFormField label="Próximo Número" name="proximo_numero_nfce" required>
          <UInput v-model="state.proximo_numero_nfce" type="number" />
        </UFormField>
      </div>
      <p class="text-xs text-muted mt-2">
        Modelo 65 - A última NFC-e emitida será: Série {{ state.serie_nfce ?? '-' }}, Número {{ (Number(state.proximo_numero_nfce) || 1) - 1 }}
      </p>
    </UPageCard>

    <UPageCard title="CT-e (Conhecimento de Transporte Eletrônico)" variant="subtle">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UFormField label="Série" name="serie_cte" required>
          <UInput v-model="state.serie_cte" type="number" />
        </UFormField>
        <UFormField label="Próximo Número" name="proximo_numero_cte" required>
          <UInput v-model="state.proximo_numero_cte" type="number" />
        </UFormField>
      </div>
      <p class="text-xs text-muted mt-2">
        Modelo 57 - A última CT-e emitida será: Série {{ state.serie_cte ?? '-' }}, Número {{ (Number(state.proximo_numero_cte) || 1) - 1 }}
      </p>
    </UPageCard>

    <UPageCard title="MDF-e (Manifesto de Documento Fiscal Eletrônico)" variant="subtle">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UFormField label="Série" name="serie_mdfe" required>
          <UInput v-model="state.serie_mdfe" type="number" />
        </UFormField>
        <UFormField label="Próximo Número" name="proximo_numero_mdfe" required>
          <UInput v-model="state.proximo_numero_mdfe" type="number" />
        </UFormField>
      </div>
      <p class="text-xs text-muted mt-2">
        Modelo 58 - A última MDF-e emitida será: Série {{ state.serie_mdfe ?? '-' }}, Número {{ (Number(state.proximo_numero_mdfe) || 1) - 1 }}
      </p>
    </UPageCard>

    <div class="flex justify-end">
      <UButton type="submit" :loading="loading" label="Salvar numeração" />
    </div>
  </UForm>
</template>
