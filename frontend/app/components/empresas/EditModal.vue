<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Company } from '~/types'

const props = defineProps<{ company: Company }>()
const emit = defineEmits<{ updated: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()

const schema = z.object({
  razao_social: z.string().min(2, 'Mínimo 2 caracteres'),
  fantasia: z.string().optional(),
  cnpj: z.string().min(14, 'CNPJ inválido'),
  ie: z.string().optional(),
  im: z.string().optional(),
  crt: z.coerce.number().optional(),
  logradouro: z.string().optional(),
  numero: z.string().optional(),
  complemento: z.string().optional(),
  bairro: z.string().optional(),
  municipio: z.string().optional(),
  municipio_ibge: z.string().optional(),
  uf: z.string().max(2).optional(),
  cep: z.string().optional(),
  telefone: z.string().optional(),
  email: z.string().email('E-mail inválido').optional().or(z.literal('')),
  ambiente: z.enum(['homologacao', 'producao'])
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({})

const { put } = useApiMutation()
const formRef = useTemplateRef('formRef')

const { search: searchCep, loading: cepLoading, error: cepError } = useCepSearch()

const ufItems = [
  { label: 'AC', value: 'AC' }, { label: 'AL', value: 'AL' }, { label: 'AP', value: 'AP' },
  { label: 'AM', value: 'AM' }, { label: 'BA', value: 'BA' }, { label: 'CE', value: 'CE' },
  { label: 'DF', value: 'DF' }, { label: 'ES', value: 'ES' }, { label: 'GO', value: 'GO' },
  { label: 'MA', value: 'MA' }, { label: 'MT', value: 'MT' }, { label: 'MS', value: 'MS' },
  { label: 'MG', value: 'MG' }, { label: 'PA', value: 'PA' }, { label: 'PB', value: 'PB' },
  { label: 'PR', value: 'PR' }, { label: 'PE', value: 'PE' }, { label: 'PI', value: 'PI' },
  { label: 'RJ', value: 'RJ' }, { label: 'RN', value: 'RN' }, { label: 'RS', value: 'RS' },
  { label: 'RO', value: 'RO' }, { label: 'RR', value: 'RR' }, { label: 'SC', value: 'SC' },
  { label: 'SP', value: 'SP' }, { label: 'SE', value: 'SE' }, { label: 'TO', value: 'TO' }
]

// Preencher state e abrir modal quando a empresa for passada
watch(() => props.company, (company) => {
  if (company) {
    Object.assign(state, {
      razao_social: company.razao_social,
      fantasia: company.fantasia || '',
      cnpj: company.cnpj,
      ie: company.ie || '',
      im: company.im || '',
      crt: company.crt,
      logradouro: company.logradouro || '',
      numero: company.numero || '',
      complemento: company.complemento || '',
      bairro: company.bairro || '',
      municipio: company.municipio || '',
      municipio_ibge: company.municipio_ibge || '',
      uf: company.uf || '',
      cep: company.cep || '',
      telefone: company.telefone || '',
      email: company.email || '',
      ambiente: company.ambiente
    })
    open.value = true
  }
}, { immediate: true })

async function handleSearchCep() {
  const cleanCep = state.cep?.replace(/\D/g, '') || ''
  if (cleanCep.length !== 8) return
  const data = await searchCep(state.cep!)
  if (data) {
    Object.assign(state, {
      logradouro: data.logradouro, bairro: data.bairro,
      municipio: data.municipio, municipio_ibge: data.municipio_ibge, uf: data.uf
    })
  } else if (cepError.value) {
    toast.add({ title: 'Erro', description: cepError.value, color: 'error' })
  }
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await put(`/companies/${props.company.id}`, event.data)
    toast.add({ title: 'Empresa atualizada', description: event.data.razao_social, color: 'success' })
    open.value = false
    emit('updated')
  } catch (error: unknown) {
    const err = error as { response?: { _data?: { message?: string } }, message?: string }
    const msg = err?.response?._data?.message || err?.message || 'Erro ao atualizar empresa.'
    toast.add({ title: 'Erro', description: msg, color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Editar Empresa"
    :ui="{ content: 'w-full sm:max-w-4xl mt-4', body: 'max-h-[75vh] overflow-y-auto', footer: 'justify-end shrink-0' }"
  >
    <slot />

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-6 p-1"
        @submit="onSubmit"
      >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField
            label="Razão Social"
            name="razao_social"
            required
            class="sm:col-span-2"
          >
            <UInput v-model="state.razao_social" placeholder="Nome empresarial" class="w-full" />
          </UFormField>

          <UFormField label="CNPJ" name="cnpj" required>
            <UInput v-model="state.cnpj" placeholder="00.000.000/0001-00" class="w-full" />
          </UFormField>
          <UFormField label="Nome Fantasia" name="fantasia">
            <UInput v-model="state.fantasia" placeholder="Nome comercial" class="w-full" />
          </UFormField>

          <UFormField label="Inscrição Estadual" name="ie">
            <UInput v-model="state.ie" placeholder="IE" class="w-full" />
          </UFormField>
          <UFormField label="Inscrição Municipal" name="im">
            <UInput v-model="state.im" placeholder="IM" class="w-full" />
          </UFormField>

          <UFormField label="Regime Tributário" name="crt">
            <USelect
              v-model="state.crt"
              class="w-full"
              :items="[
                { label: 'Simples Nacional', value: 1 },
                { label: 'Simples Nacional (excesso)', value: 2 },
                { label: 'Regime Normal', value: 3 }
              ]"
              placeholder="Selecione o regime"
            />
          </UFormField>
          <UFormField label="Ambiente de Emissão" name="ambiente">
            <USelect
              v-model="state.ambiente"
              class="w-full"
              :items="[
                { label: 'Homologação', value: 'homologacao' },
                { label: 'Produção', value: 'producao' }
              ]"
              placeholder="Selecione o ambiente"
            />
          </UFormField>
        </div>

        <USeparator label="Endereço" />

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <UFormField label="CEP" name="cep" class="sm:col-span-1">
            <UInput
              v-model="state.cep"
              placeholder="00000-000"
              class="w-full"
              @blur="handleSearchCep"
            >
              <template #trailing>
                <UIcon v-if="cepLoading" name="i-lucide-loader-2" class="animate-spin text-muted size-4" />
              </template>
            </UInput>
          </UFormField>
          <UFormField label="UF" name="uf" class="sm:col-span-1">
            <USelect
              v-model="state.uf"
              :items="ufItems"
              placeholder="UF"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Município" name="municipio" class="col-span-2">
            <UInput v-model="state.municipio" placeholder="Cidade" class="w-full" />
          </UFormField>

          <UFormField label="Logradouro" name="logradouro" class="col-span-2 sm:col-span-3">
            <UInput v-model="state.logradouro" placeholder="Rua, Avenida, etc." class="w-full" />
          </UFormField>
          <UFormField label="Número" name="numero">
            <UInput v-model="state.numero" placeholder="Nº" class="w-full" />
          </UFormField>

          <UFormField label="Bairro" name="bairro" class="col-span-2">
            <UInput v-model="state.bairro" placeholder="Bairro" class="w-full" />
          </UFormField>
          <UFormField label="Complemento" name="complemento" class="col-span-2">
            <UInput v-model="state.complemento" placeholder="Apto, Sala, Bloco, etc." class="w-full" />
          </UFormField>
        </div>

        <USeparator label="Contato" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Telefone" name="telefone">
            <UInput v-model="state.telefone" placeholder="(00) 00000-0000" class="w-full" />
          </UFormField>
          <UFormField label="E-mail" name="email">
            <UInput
              v-model="state.email"
              type="email"
              placeholder="email@empresa.com.br"
              class="w-full"
            />
          </UFormField>
        </div>
      </UForm>
    </template>

    <template #footer="{ close }">
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="close"
      />
      <UButton
        label="Salvar Alterações"
        color="primary"
        :loading="loading"
        @click="formRef?.submit()"
      />
    </template>
  </UModal>
</template>
