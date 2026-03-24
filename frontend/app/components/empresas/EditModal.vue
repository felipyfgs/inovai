<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Company } from '~/types'

const props = defineProps<{ company: Company }>()
const emit = defineEmits<{ updated: [] }>()

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

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { put } = useApiMutation()
const formRef = useTemplateRef('formRef')

const state = reactive<Partial<Schema>>({})

watch(open, (val) => {
  if (val) {
    Object.assign(state, {
      razao_social: props.company.razao_social,
      fantasia: props.company.fantasia || '',
      cnpj: props.company.cnpj,
      ie: props.company.ie || '',
      im: props.company.im || '',
      crt: props.company.crt,
      logradouro: props.company.logradouro || '',
      numero: props.company.numero || '',
      complemento: props.company.complemento || '',
      bairro: props.company.bairro || '',
      municipio: props.company.municipio || '',
      municipio_ibge: props.company.municipio_ibge || '',
      uf: props.company.uf || '',
      cep: props.company.cep || '',
      telefone: props.company.telefone || '',
      email: props.company.email || '',
      ambiente: props.company.ambiente
    })
  }
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await put(`/companies/${props.company.id}`, event.data)
    toast.add({ title: 'Empresa atualizada', description: event.data.razao_social, color: 'success' })
    open.value = false
    emit('updated')
  } catch (error) {
    const msg = (error as { response?: { _data?: { message?: string } } })?.response?._data?.message || 'Erro ao atualizar empresa.'
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
    title="Editar Empresa"
    description="Alterar dados da empresa"
    :ui="{ content: 'w-full sm:max-w-4xl', footer: 'justify-end' }"
  >
    <slot />

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
            <span class="i-lucide-building-2 text-muted" />
            Dados Cadastrais
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField
              label="Razão Social"
              name="razao_social"
              required
              class="sm:col-span-2"
            >
              <UInput v-model="state.razao_social" class="w-full" />
            </UFormField>
            <UFormField label="Nome Fantasia" name="fantasia">
              <UInput v-model="state.fantasia" class="w-full" />
            </UFormField>
            <UFormField label="CNPJ" name="cnpj" required>
              <UInput v-model="state.cnpj" class="w-full" />
            </UFormField>
            <UFormField label="Inscrição Estadual" name="ie">
              <UInput v-model="state.ie" class="w-full" />
            </UFormField>
            <UFormField label="Inscrição Municipal" name="im">
              <UInput v-model="state.im" class="w-full" />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <!-- Seção: Regime Tributário e Ambiente -->
        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-receipt text-muted" />
            Regime Tributário e Ambiente
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="CRT (Código de Regime Tributário)" name="crt">
              <USelect
                v-model="state.crt"
                :items="[
                  { label: '1 - Simples Nacional', value: 1 },
                  { label: '2 - Simples Nacional (excesso)', value: 2 },
                  { label: '3 - Regime Normal', value: 3 }
                ]"
                class="w-full"
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
              />
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
              <UInput v-model="state.logradouro" class="w-full" />
            </UFormField>
            <UFormField label="Número" name="numero" class="sm:col-span-2">
              <UInput v-model="state.numero" class="w-full" />
            </UFormField>
            <UFormField label="Complemento" name="complemento" class="sm:col-span-2">
              <UInput v-model="state.complemento" class="w-full" />
            </UFormField>
            <UFormField label="Bairro" name="bairro" class="sm:col-span-2">
              <UInput v-model="state.bairro" class="w-full" />
            </UFormField>
            <UFormField label="Município" name="municipio" class="sm:col-span-2">
              <UInput v-model="state.municipio" class="w-full" />
            </UFormField>
            <div class="sm:col-span-6 grid grid-cols-2 gap-4">
              <UFormField label="UF" name="uf">
                <UInput v-model="state.uf" class="w-full" maxlength="2" />
              </UFormField>
              <UFormField label="CEP" name="cep">
                <UInput v-model="state.cep" class="w-full" />
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
              <UInput v-model="state.telefone" class="w-full" />
            </UFormField>
            <UFormField label="E-mail" name="email">
              <UInput v-model="state.email" class="w-full" type="email" />
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
        label="Salvar Alterações"
        color="primary"
        :loading="loading"
        @click="handleSubmit"
      />
    </template>
  </UModal>
</template>
