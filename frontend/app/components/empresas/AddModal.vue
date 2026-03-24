<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent, TabsItem } from '@nuxt/ui'

const emit = defineEmits<{ created: [] }>()

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
  ambiente: z.enum(['homologacao', 'producao']).default('homologacao')
})

type Schema = z.output<typeof schema>

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const formRef = useTemplateRef('formRef')

const { search: searchCnpj, loading: cnpjLoading, error: cnpjError } = useCnpjSearch()
const { search: searchCep, loading: cepLoading, error: cepError } = useCepSearch()

const state = reactive<Partial<Schema>>({
  razao_social: '',
  fantasia: '',
  cnpj: '',
  ie: '',
  im: '',
  crt: 1,
  logradouro: '',
  numero: '',
  complemento: '',
  bairro: '',
  municipio: '',
  municipio_ibge: '',
  uf: '',
  cep: '',
  telefone: '',
  email: '',
  ambiente: 'homologacao'
})

const ufItems = [
  { label: 'Acre', value: 'AC' },
  { label: 'Alagoas', value: 'AL' },
  { label: 'Amapá', value: 'AP' },
  { label: 'Amazonas', value: 'AM' },
  { label: 'Bahia', value: 'BA' },
  { label: 'Ceará', value: 'CE' },
  { label: 'Distrito Federal', value: 'DF' },
  { label: 'Espírito Santo', value: 'ES' },
  { label: 'Goiás', value: 'GO' },
  { label: 'Maranhão', value: 'MA' },
  { label: 'Mato Grosso', value: 'MT' },
  { label: 'Mato Grosso do Sul', value: 'MS' },
  { label: 'Minas Gerais', value: 'MG' },
  { label: 'Pará', value: 'PA' },
  { label: 'Paraíba', value: 'PB' },
  { label: 'Paraná', value: 'PR' },
  { label: 'Pernambuco', value: 'PE' },
  { label: 'Piauí', value: 'PI' },
  { label: 'Rio de Janeiro', value: 'RJ' },
  { label: 'Rio Grande do Norte', value: 'RN' },
  { label: 'Rio Grande do Sul', value: 'RS' },
  { label: 'Rondônia', value: 'RO' },
  { label: 'Roraima', value: 'RR' },
  { label: 'Santa Catarina', value: 'SC' },
  { label: 'São Paulo', value: 'SP' },
  { label: 'Sergipe', value: 'SE' },
  { label: 'Tocantins', value: 'TO' }
]

const tabItems: TabsItem[] = [
  { label: 'Cadastrais', icon: 'i-lucide-building-2', slot: 'cadastrais' },
  { label: 'Endereço', icon: 'i-lucide-map-pin', slot: 'endereco' },
  { label: 'Contato', icon: 'i-lucide-phone', slot: 'contato' }
]

function resetForm() {
  Object.assign(state, {
    razao_social: '', fantasia: '', cnpj: '', ie: '', im: '', crt: 1,
    logradouro: '', numero: '', complemento: '', bairro: '', municipio: '',
    municipio_ibge: '', uf: '', cep: '', telefone: '', email: '', ambiente: 'homologacao'
  })
}

async function handleSearchCnpj() {
  const cleanCnpj = state.cnpj?.replace(/\D/g, '') || ''
  if (cleanCnpj.length !== 14) {
    toast.add({ title: 'CNPJ inválido', description: 'Digite 14 dígitos', color: 'error' })
    return
  }

  const data = await searchCnpj(state.cnpj!)
  if (data) {
    state.razao_social = data.razao_social
    state.fantasia = data.fantasia
    state.ie = data.ie
    state.crt = data.crt
    state.logradouro = data.logradouro
    state.numero = data.numero
    state.complemento = data.complemento
    state.bairro = data.bairro
    state.municipio = data.municipio
    state.municipio_ibge = data.municipio_ibge
    state.uf = data.uf
    state.cep = data.cep
    state.telefone = data.telefone
    state.email = data.email

    toast.add({ title: 'CNPJ encontrado', description: data.razao_social, color: 'success' })
  } else if (cnpjError.value) {
    toast.add({ title: 'Erro', description: cnpjError.value, color: 'error' })
  }
}

async function handleSearchCep() {
  const cleanCep = state.cep?.replace(/\D/g, '') || ''
  if (cleanCep.length !== 8) return

  const data = await searchCep(state.cep!)
  if (data) {
    state.logradouro = data.logradouro
    state.bairro = data.bairro
    state.municipio = data.municipio
    state.municipio_ibge = data.municipio_ibge
    state.uf = data.uf
  } else if (cepError.value) {
    toast.add({ title: 'Erro', description: cepError.value, color: 'error' })
  }
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await post('/companies', event.data)
    toast.add({ title: 'Empresa criada', description: event.data.razao_social, color: 'success' })
    open.value = false
    resetForm()
    emit('created')
  } catch (error: any) {
    const msg = error?.response?._data?.message || error?.message || 'Erro ao criar empresa.'
    toast.add({ title: 'Erro', description: msg, color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Nova Empresa"
    description="Adicionar uma nova empresa ao escritório"
    :ui="{ content: 'w-full sm:max-w-4xl' }"
    scrollable
  >
    <UButton label="Nova Empresa" icon="i-lucide-plus" />

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-4"
        @submit="onSubmit"
      >
        <UTabs :items="tabItems" class="w-full">
          <template #cadastrais>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
              <UFormField
                label="Razão Social"
                name="razao_social"
                required
                class="sm:col-span-2"
              >
                <UInput v-model="state.razao_social" class="w-full" placeholder="Razão Social da empresa" />
              </UFormField>
              <UFormField label="Nome Fantasia" name="fantasia">
                <UInput v-model="state.fantasia" class="w-full" placeholder="Nome comercial" />
              </UFormField>
              <UFormField label="CNPJ" name="cnpj" required>
                <UInput v-model="state.cnpj" class="w-full" placeholder="00.000.000/0001-00">
                  <template #trailing>
                    <UButton
                      v-if="!cnpjLoading"
                      variant="link"
                      icon="i-lucide-search"
                      color="neutral"
                      @click="handleSearchCnpj"
                    />
                    <UIcon v-else name="i-lucide-loader-2" class="animate-spin text-muted" />
                  </template>
                </UInput>
              </UFormField>
              <UFormField label="Inscrição Estadual" name="ie">
                <UInput v-model="state.ie" class="w-full" placeholder="IE" />
              </UFormField>
              <UFormField label="Inscrição Municipal" name="im">
                <UInput v-model="state.im" class="w-full" placeholder="IM" />
              </UFormField>
              <UFormField label="CRT (Regime Tributário)" name="crt">
                <USelect
                  v-model="state.crt"
                  :items="[
                    { label: '1 - Simples Nacional', value: 1 },
                    { label: '2 - Simples Nacional (excesso)', value: 2 },
                    { label: '3 - Regime Normal', value: 3 }
                  ]"
                  class="w-full"
                  placeholder="Selecione o regime"
                />
              </UFormField>
              <UFormField label="Ambiente de Emissão" name="ambiente">
                <USelect
                  v-model="state.ambiente"
                  :items="[
                    { label: 'Homologação (Testes)', value: 'homologacao' },
                    { label: 'Produção (Real)', value: 'producao' }
                  ]"
                  class="w-full"
                  placeholder="Selecione o ambiente"
                />
              </UFormField>
            </div>
          </template>

          <template #endereco>
            <div class="grid grid-cols-1 sm:grid-cols-6 gap-4 pt-4">
              <UFormField label="CEP" name="cep" class="sm:col-span-2">
                <UInput v-model="state.cep" class="w-full" placeholder="00000-000" @blur="handleSearchCep">
                  <template #trailing>
                    <UIcon v-if="cepLoading" name="i-lucide-loader-2" class="animate-spin text-muted" />
                  </template>
                </UInput>
              </UFormField>
              <UFormField label="UF" name="uf" class="sm:col-span-2">
                <USelect
                  v-model="state.uf"
                  :items="ufItems"
                  class="w-full"
                  placeholder="Selecione"
                />
              </UFormField>
              <UFormField label="Município" name="municipio" class="sm:col-span-2">
                <UInput v-model="state.municipio" class="w-full" placeholder="Cidade" />
              </UFormField>
              <UFormField label="Logradouro" name="logradouro" class="sm:col-span-4">
                <UInput v-model="state.logradouro" class="w-full" placeholder="Rua, Avenida, etc." />
              </UFormField>
              <UFormField label="Número" name="numero" class="sm:col-span-2">
                <UInput v-model="state.numero" class="w-full" placeholder="Nº" />
              </UFormField>
              <UFormField label="Complemento" name="complemento" class="sm:col-span-2">
                <UInput v-model="state.complemento" class="w-full" placeholder="Apto, Sala, etc." />
              </UFormField>
              <UFormField label="Bairro" name="bairro" class="sm:col-span-2">
                <UInput v-model="state.bairro" class="w-full" placeholder="Bairro" />
              </UFormField>
              <UFormField label="Código IBGE" name="municipio_ibge" class="sm:col-span-2">
                <UInput v-model="state.municipio_ibge" class="w-full" placeholder="IBGE" disabled />
              </UFormField>
            </div>
          </template>

          <template #contato>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
              <UFormField label="Telefone" name="telefone">
                <UInput v-model="state.telefone" class="w-full" placeholder="(00) 00000-0000" />
              </UFormField>
              <UFormField label="E-mail" name="email">
                <UInput
                  v-model="state.email"
                  class="w-full"
                  type="email"
                  placeholder="email@empresa.com.br"
                />
              </UFormField>
            </div>
          </template>
        </UTabs>

        <div class="flex justify-end gap-2 pt-4">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="outline"
            @click="open = false"
          />
          <UButton
            label="Criar Empresa"
            color="primary"
            :loading="loading"
            @click="handleSubmit"
          />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
