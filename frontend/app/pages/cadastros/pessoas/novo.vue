<script setup lang="ts">
import type { BreadcrumbItem, FormSubmitEvent } from '@nuxt/ui'
import type { Pessoa } from '~/types'
import { UButton } from '#components'
import * as z from 'zod'

const schema = z.object({
  tipo: z.enum(['cliente', 'fornecedor', 'ambos']),
  tipo_pessoa: z.enum(['PF', 'PJ']),
  razao_social: z.string().min(2, 'Mínimo 2 caracteres'),
  fantasia: z.string().optional(),
  cpf_cnpj: z.string().optional(),
  ie: z.string().optional(),
  ind_ie: z.coerce.number().optional(),
  logradouro: z.string().optional(),
  numero: z.string().optional(),
  complemento: z.string().optional(),
  bairro: z.string().optional(),
  municipio: z.string().optional(),
  municipio_ibge: z.string().optional(),
  uf: z.string().max(2).optional(),
  cep: z.string().optional(),
  telefone: z.string().optional(),
  celular: z.string().optional(),
  email: z.string().email('E-mail inválido').optional().or(z.literal('')),
  observacoes: z.string().optional()
})

type Schema = z.output<typeof schema>

const router = useRouter()
const toast = useToast()
const { post } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const loading = ref(false)
const state = reactive<Partial<Schema>>({
  tipo: 'cliente',
  tipo_pessoa: 'PJ',
  razao_social: '',
  fantasia: '',
  cpf_cnpj: '',
  ie: '',
  ind_ie: 1,
  logradouro: '',
  numero: '',
  complemento: '',
  bairro: '',
  municipio: '',
  municipio_ibge: '',
  uf: '',
  cep: '',
  telefone: '',
  celular: '',
  email: '',
  observacoes: ''
})

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'Cadastros', icon: 'i-lucide-folder', to: '/cadastros/pessoas' },
  { label: 'Pessoas', icon: 'i-lucide-users', to: '/cadastros/pessoas' },
  { label: 'Nova Pessoa' }
]

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    const pessoa = await post<Pessoa>('/pessoas', event.data)
    toast.add({ title: 'Pessoa criada', description: event.data.razao_social, color: 'success' })
    router.push(`/cadastros/pessoas/${pessoa.id}`)
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao criar.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}
</script>

<template>
  <UDashboardPanel id="pessoa-novo">
    <template #header>
      <UDashboardNavbar title="Nova Pessoa">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UBreadcrumb :items="breadcrumbs" />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <div />

        <template #right>
          <UButton
            label="Cancelar"
            color="neutral"
            variant="outline"
            @click="router.push('/cadastros/pessoas')"
          />
          <UButton
            label="Criar Pessoa"
            color="primary"
            :loading="loading"
            @click="handleSubmit"
          />
        </template>
      </UDashboardToolbar>
    </template>

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-6"
        @submit="onSubmit"
      >
        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-tag text-muted" />
              Tipo e Classificação
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Tipo" name="tipo" required>
              <USelect
                v-model="state.tipo"
                :items="[
                  { label: 'Cliente', value: 'cliente' },
                  { label: 'Fornecedor', value: 'fornecedor' },
                  { label: 'Ambos', value: 'ambos' }
                ]"
                class="w-full"
                placeholder="Selecione o tipo"
              />
            </UFormField>
            <UFormField label="Tipo de Pessoa" name="tipo_pessoa" required>
              <USelect
                v-model="state.tipo_pessoa"
                :items="[
                  { label: 'Pessoa Jurídica (PJ)', value: 'PJ' },
                  { label: 'Pessoa Física (PF)', value: 'PF' }
                ]"
                class="w-full"
                placeholder="Selecione o tipo"
              />
            </UFormField>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-user text-muted" />
              Dados Pessoais
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField
              label="Razão Social / Nome Completo"
              name="razao_social"
              required
              class="sm:col-span-2"
            >
              <UInput v-model="state.razao_social" class="w-full" placeholder="Nome completo ou razão social" />
            </UFormField>
            <UFormField label="Nome Fantasia" name="fantasia">
              <UInput v-model="state.fantasia" class="w-full" placeholder="Nome comercial" />
            </UFormField>
            <UFormField label="CPF / CNPJ" name="cpf_cnpj">
              <UInput v-model="state.cpf_cnpj" class="w-full" placeholder="000.000.000-00 ou 00.000.000/0001-00" />
            </UFormField>
            <UFormField label="Inscrição Estadual" name="ie">
              <UInput v-model="state.ie" class="w-full" placeholder="IE" />
            </UFormField>
            <UFormField label="Indicador de IE" name="ind_ie">
              <USelect
                v-model="state.ind_ie"
                :items="[
                  { label: '1 - Contribuinte', value: 1 },
                  { label: '2 - Isento', value: 2 },
                  { label: '9 - Não contribuinte', value: 9 }
                ]"
                class="w-full"
                placeholder="Selecione"
              />
            </UFormField>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-map-pin text-muted" />
              Endereço
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
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
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-phone text-muted" />
              Contato
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <UFormField label="Telefone" name="telefone">
              <UInput v-model="state.telefone" class="w-full" placeholder="(00) 0000-0000" />
            </UFormField>
            <UFormField label="Celular" name="celular">
              <UInput v-model="state.celular" class="w-full" placeholder="(00) 00000-0000" />
            </UFormField>
            <UFormField label="E-mail" name="email">
              <UInput
                v-model="state.email"
                class="w-full"
                type="email"
                placeholder="email@exemplo.com.br"
              />
            </UFormField>
          </div>
        </UCard>

        <UCard>
          <UFormField label="Observações" name="observacoes">
            <UTextarea
              v-model="state.observacoes"
              class="w-full"
              placeholder="Informações adicionais..."
              :rows="3"
            />
          </UFormField>
        </UCard>
      </UForm>
    </template>
  </UDashboardPanel>
</template>
