<script setup lang="ts">
import { z } from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const { emitente, updateDados } = useEmitente()
const { search } = useCepSearch()

const schema = z.object({
  razao_social: z.string().min(1, 'Obrigatório').max(255),
  fantasia: z.string().max(255).optional().default(''),
  cnpj: z.string().min(1, 'Obrigatório').max(18),
  ie: z.string().max(20).optional().default(''),
  im: z.string().max(20).optional().default(''),
  crt: z.coerce.number().min(1).max(3),
  logradouro: z.string().max(255).optional().default(''),
  numero: z.string().max(10).optional().default(''),
  complemento: z.string().max(255).optional().default(''),
  bairro: z.string().max(255).optional().default(''),
  municipio: z.string().max(255).optional().default(''),
  municipio_ibge: z.string().max(7).optional().default(''),
  uf: z.string().max(2).optional().default(''),
  cep: z.string().max(9).optional().default(''),
  telefone: z.string().max(20).optional().default(''),
  email: z.string().email('E-mail inválido').max(255).optional().default('')
})

type Schema = z.output<typeof schema>

const formRef = useTemplateRef('formRef')
const loading = ref(false)

const state = reactive<Partial<Schema>>({})

watch(emitente, (e) => {
  if (e) {
    state.razao_social = e.razao_social
    state.fantasia = e.fantasia ?? ''
    state.cnpj = e.cnpj
    state.ie = e.ie ?? ''
    state.im = e.im ?? ''
    state.crt = e.crt
    state.logradouro = e.logradouro ?? ''
    state.numero = e.numero ?? ''
    state.complemento = e.complemento ?? ''
    state.bairro = e.bairro ?? ''
    state.municipio = e.municipio ?? ''
    state.municipio_ibge = e.municipio_ibge ?? ''
    state.uf = e.uf ?? ''
    state.cep = e.cep ?? ''
    state.telefone = e.telefone ?? ''
    state.email = e.email ?? ''
  }
}, { immediate: true })

async function onCepSearch() {
  const cep = state.cep?.replace(/\D/g, '')
  if (!cep || cep.length !== 8) return

  const endereco = await search(cep)
  if (endereco) {
    state.logradouro = endereco.logradouro
    state.bairro = endereco.bairro
    state.municipio = endereco.municipio
    state.uf = endereco.uf
    state.municipio_ibge = endereco.municipio_ibge
  }
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await updateDados(event.data)
  } finally {
    loading.value = false
  }
}

const crtOptions = [
  { value: 1, label: '1 - Simples Nacional' },
  { value: 2, label: '2 - Simples Nacional (Excesso Sublimite)' },
  { value: 3, label: '3 - Regime Normal' }
]
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
    <UPageCard title="Dados do Emitente" variant="subtle">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Razão Social" name="razao_social" required>
            <UInput v-model="state.razao_social" />
          </UFormField>
          <UFormField label="Nome Fantasia" name="fantasia">
            <UInput v-model="state.fantasia" />
          </UFormField>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UFormField label="CNPJ" name="cnpj" required>
            <UInput v-model="state.cnpj" />
          </UFormField>
          <UFormField label="Inscrição Estadual" name="ie">
            <UInput v-model="state.ie" />
          </UFormField>
          <UFormField label="Inscrição Municipal" name="im">
            <UInput v-model="state.im" />
          </UFormField>
        </div>

        <UFormField label="Regime Tributário (CRT)" name="crt" required>
          <USelect
            v-model="state.crt"
            :items="crtOptions"
            value-key="value"
            label-key="label"
          />
        </UFormField>
      </div>
    </UPageCard>

    <UPageCard title="Endereço" variant="subtle">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UFormField label="CEP" name="cep">
            <UInput v-model="state.cep">
              <template #trailing>
                <UButton
                  icon="i-lucide-search"
                  variant="ghost"
                  size="xs"
                  :loading="loading"
                  @click="onCepSearch"
                />
              </template>
            </UInput>
          </UFormField>
          <UFormField label="UF" name="uf">
            <UInput v-model="state.uf" />
          </UFormField>
          <UFormField label="Município" name="municipio">
            <UInput v-model="state.municipio" />
          </UFormField>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UFormField label="Logradouro" name="logradouro" class="sm:col-span-2">
            <UInput v-model="state.logradouro" />
          </UFormField>
          <UFormField label="Número" name="numero">
            <UInput v-model="state.numero" />
          </UFormField>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Complemento" name="complemento">
            <UInput v-model="state.complemento" />
          </UFormField>
          <UFormField label="Bairro" name="bairro">
            <UInput v-model="state.bairro" />
          </UFormField>
        </div>

        <UFormField label="Código IBGE Município" name="municipio_ibge">
          <UInput v-model="state.municipio_ibge" />
        </UFormField>
      </div>
    </UPageCard>

    <UPageCard title="Contato" variant="subtle">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UFormField label="Telefone" name="telefone">
          <UInput v-model="state.telefone" />
        </UFormField>
        <UFormField label="E-mail" name="email">
          <UInput v-model="state.email" type="email" />
        </UFormField>
      </div>
    </UPageCard>

    <div class="flex justify-end">
      <UButton type="submit" :loading="loading" label="Salvar dados" />
    </div>
  </UForm>
</template>
