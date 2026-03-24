<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const emit = defineEmits<{ created: [] }>()

const schema = z.object({
  razao_social: z.string().min(2, 'Mínimo 2 caracteres'),
  fantasia: z.string().optional(),
  cnpj: z.string().optional(),
  ie: z.string().optional(),
  rntrc: z.string().optional(),
  logradouro: z.string().optional(),
  numero: z.string().optional(),
  bairro: z.string().optional(),
  municipio: z.string().optional(),
  municipio_ibge: z.string().optional(),
  uf: z.string().max(2).optional(),
  cep: z.string().optional(),
  telefone: z.string().optional(),
  email: z.string().email('E-mail inválido').optional().or(z.literal(''))
})

type Schema = z.output<typeof schema>

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const formRef = useTemplateRef('formRef')

const state = reactive<Partial<Schema>>({
  razao_social: '', fantasia: '', cnpj: '', ie: '', rntrc: '',
  logradouro: '', numero: '', bairro: '', municipio: '', municipio_ibge: '',
  uf: '', cep: '', telefone: '', email: ''
})

function resetForm() {
  Object.assign(state, {
    razao_social: '', fantasia: '', cnpj: '', ie: '', rntrc: '',
    logradouro: '', numero: '', bairro: '', municipio: '', municipio_ibge: '',
    uf: '', cep: '', telefone: '', email: ''
  })
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await post('/transportadoras', event.data)
    toast.add({ title: 'Transportadora criada', description: event.data.razao_social, color: 'success' })
    open.value = false
    resetForm()
    emit('created')
  } catch (error) {
    toast.add({ title: 'Erro', description: error?.response?._data?.message || 'Erro ao criar.', color: 'error' })
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
    title="Nova Transportadora"
    description="Adicionar transportadora"
    :ui="{ content: 'w-full sm:max-w-4xl', footer: 'justify-end' }"
  >
    <UButton label="Nova Transportadora" icon="i-lucide-plus" />

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-6"
        @submit="onSubmit"
      >
        <!-- Seção: Dados Cadastrais -->
        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-truck text-muted" />
            Dados Cadastrais
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField
              label="Razão Social"
              name="razao_social"
              required
              class="sm:col-span-2"
            >
              <UInput v-model="state.razao_social" class="w-full" placeholder="Razão Social da transportadora" />
            </UFormField>
            <UFormField label="Nome Fantasia" name="fantasia">
              <UInput v-model="state.fantasia" class="w-full" placeholder="Nome comercial" />
            </UFormField>
            <UFormField label="CNPJ" name="cnpj">
              <UInput v-model="state.cnpj" class="w-full" placeholder="00.000.000/0001-00" />
            </UFormField>
            <UFormField label="Inscrição Estadual" name="ie">
              <UInput v-model="state.ie" class="w-full" placeholder="IE" />
            </UFormField>
            <UFormField label="RNTRC" name="rntrc" description="Registro Nacional de Transportadores Rodoviários de Cargas">
              <UInput v-model="state.rntrc" class="w-full" placeholder="RNTRC" />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <!-- Seção: Endereço -->
        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-map-pin text-muted" />
            Endereço
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
            <UFormField label="Logradouro" name="logradouro" class="sm:col-span-4">
              <UInput v-model="state.logradouro" class="w-full" placeholder="Rua, Avenida, etc." />
            </UFormField>
            <UFormField label="Número" name="numero" class="sm:col-span-2">
              <UInput v-model="state.numero" class="w-full" placeholder="Nº" />
            </UFormField>
            <UFormField label="Bairro" name="bairro" class="sm:col-span-2">
              <UInput v-model="state.bairro" class="w-full" placeholder="Bairro" />
            </UFormField>
            <UFormField label="Município" name="municipio" class="sm:col-span-2">
              <UInput v-model="state.municipio" class="w-full" placeholder="Cidade" />
            </UFormField>
            <div class="sm:col-span-6 grid grid-cols-2 gap-4">
              <UFormField label="UF" name="uf">
                <UInput
                  v-model="state.uf"
                  class="w-full"
                  maxlength="2"
                  placeholder="SP"
                />
              </UFormField>
              <UFormField label="CEP" name="cep">
                <UInput v-model="state.cep" class="w-full" placeholder="00000-000" />
              </UFormField>
            </div>
          </div>
        </div>

        <USeparator />

        <!-- Seção: Contato -->
        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-phone text-muted" />
            Contato
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Telefone" name="telefone">
              <UInput v-model="state.telefone" class="w-full" placeholder="(00) 0000-0000" />
            </UFormField>
            <UFormField label="E-mail" name="email">
              <UInput
                v-model="state.email"
                class="w-full"
                type="email"
                placeholder="email@transportadora.com.br"
              />
            </UFormField>
          </div>
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
        label="Criar Transportadora"
        color="primary"
        :loading="loading"
        @click="handleSubmit"
      />
    </template>
  </UModal>
</template>
