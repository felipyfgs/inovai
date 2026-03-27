<script setup lang="ts">
import * as z from 'zod'
import type { BreadcrumbItem, FormSubmitEvent } from '@nuxt/ui'
import type { Company, OfficePlan } from '~/types'

import { UButton } from '#components'

const router = useRouter()
const toast = useToast()
const { post } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const loading = ref(false)

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
  ambiente: z.enum(['homologacao', 'producao']).default('homologacao'),
  office_plan_id: z.coerce.number().optional(),
  owner_name: z.string().min(2, 'Mínimo 2 caracteres'),
  owner_email: z.string().email('E-mail inválido'),
  owner_password: z.string().min(8, 'Mínimo 8 caracteres'),
  owner_password_confirmation: z.string().min(8, 'Confirme a senha'),
  owner_phone: z.string().optional()
}).refine(data => data.owner_password === data.owner_password_confirmation, {
  message: 'As senhas não conferem',
  path: ['owner_password_confirmation']
})

type Schema = z.output<typeof schema>

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
  ambiente: 'homologacao',
  office_plan_id: undefined,
  owner_name: '',
  owner_email: '',
  owner_password: '',
  owner_password_confirmation: '',
  owner_phone: ''
})

const { search: searchCnpj, loading: cnpjLoading, error: cnpjError } = useCnpjSearch()
const { search: searchCep, loading: cepLoading, error: cepError } = useCepSearch()

const { data: plansData } = useApi<OfficePlan[]>('/office-plans', { lazy: true })
const plans = computed(() => plansData.value || [])
const planItems = computed(() => plans.value.map(p => ({
  label: `${p.name} — R$ ${p.price.toFixed(2).replace('.', ',')}/mês`,
  value: p.id,
  description: p.description || undefined
})))
const selectedPlan = computed(() => plans.value.find(p => p.id === state.office_plan_id))

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

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'Empresas', icon: 'i-lucide-building', to: '/empresas' },
  { label: 'Nova Empresa' }
]

async function handleSearchCnpj() {
  const cleanCnpj = state.cnpj?.replace(/\D/g, '') || ''
  if (cleanCnpj.length !== 14) {
    toast.add({ title: 'CNPJ inválido', description: 'Digite 14 dígitos', color: 'error' })
    return
  }
  const data = await searchCnpj(state.cnpj!)
  if (data) {
    Object.assign(state, {
      razao_social: data.razao_social, fantasia: data.fantasia, ie: data.ie, crt: data.crt,
      logradouro: data.logradouro, numero: data.numero, complemento: data.complemento,
      bairro: data.bairro, municipio: data.municipio, municipio_ibge: data.municipio_ibge,
      uf: data.uf, cep: data.cep, telefone: data.telefone, email: data.email
    })
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
    const response = await post<Company>('/companies', event.data)
    toast.add({ title: 'Empresa criada', description: event.data.razao_social, color: 'success' })
    router.push(`/empresas/${response.id}`)
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao criar empresa.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}
</script>

<template>
  <UDashboardPanel id="empresa-novo">
    <template #header>
      <UDashboardNavbar title="Nova Empresa">
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
            @click="router.push('/empresas')"
          />
          <UButton
            label="Criar Empresa"
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
              <span class="i-lucide-building text-muted" />
              Dados da Empresa
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField
              label="CNPJ"
              name="cnpj"
              required
              class="sm:col-span-2"
            >
              <UInput v-model="state.cnpj" placeholder="00.000.000/0001-00" class="w-full">
                <template #trailing>
                  <UButton
                    v-if="!cnpjLoading"
                    variant="link"
                    icon="i-lucide-search"
                    color="neutral"
                    size="xs"
                    @click="handleSearchCnpj"
                  />
                  <UIcon v-else name="i-lucide-loader-2" class="animate-spin text-muted size-4" />
                </template>
              </UInput>
            </UFormField>

            <UFormField
              label="Razão Social"
              name="razao_social"
              required
              class="sm:col-span-2"
            >
              <UInput v-model="state.razao_social" placeholder="Nome empresarial" class="w-full" />
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

            <UFormField label="Plano de Serviço" name="office_plan_id" class="sm:col-span-2">
              <USelect
                v-model="state.office_plan_id"
                :items="planItems"
                placeholder="Selecione um plano (opcional)"
                class="w-full"
              />
            </UFormField>

            <div v-if="selectedPlan" class="sm:col-span-2">
              <p class="text-xs text-muted mb-1.5">
                Módulos inclusos no plano:
              </p>
              <div class="flex flex-wrap gap-1.5">
                <UBadge
                  v-for="mod in selectedPlan.modules"
                  :key="mod"
                  variant="subtle"
                  size="xs"
                  color="primary"
                >
                  {{ mod }}
                </UBadge>
              </div>
            </div>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-map-pin text-muted" />
              Endereço
            </h3>
          </template>

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
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-phone text-muted" />
              Contato
            </h3>
          </template>

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
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-user text-muted" />
              Acesso do Proprietário
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Nome do Proprietário" name="owner_name" required>
              <UInput v-model="state.owner_name" placeholder="Nome completo" class="w-full" />
            </UFormField>
            <UFormField label="E-mail de Acesso" name="owner_email" required>
              <UInput
                v-model="state.owner_email"
                type="email"
                placeholder="email@exemplo.com"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Senha de Acesso" name="owner_password" required>
              <UInput
                v-model="state.owner_password"
                type="password"
                placeholder="Mínimo 8 caracteres"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Confirmar Senha" name="owner_password_confirmation" required>
              <UInput
                v-model="state.owner_password_confirmation"
                type="password"
                placeholder="Repita a senha"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Telefone do Proprietário" name="owner_phone" class="sm:col-span-2">
              <UInput v-model="state.owner_phone" placeholder="(00) 00000-0000" class="w-full" />
            </UFormField>
          </div>
        </UCard>
      </UForm>
    </template>
  </UDashboardPanel>
</template>
