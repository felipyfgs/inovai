<script setup lang="ts">
import * as z from 'zod'
import type { BreadcrumbItem, FormSubmitEvent } from '@nuxt/ui'
import type { Company } from '~/types'

import { UButton, UBadge, UDropdownMenu } from '#components'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { put } = useApiMutation()
const { extractMessage } = useApiError()
const { setCompany } = useCurrentCompany()
const formRef = useTemplateRef('formRef')

const companyId = computed(() => route.params.id as string)
const { data: company, status, refresh } = useApi<Company>(`/companies/${companyId.value}`, { lazy: false })

const isEditing = ref(false)
const loading = ref(false)
const deletingCompany = ref<Company | null>(null)

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

const crtLabels: Record<number, string> = {
  1: 'Simples Nacional',
  2: 'Simples Nacional (excesso)',
  3: 'Regime Normal'
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { label: 'Empresas', icon: 'i-lucide-building', to: '/empresas' },
  { label: company.value ? (company.value.fantasia || company.value.razao_social) : '...' }
])

function populateState(c: Company) {
  Object.assign(state, {
    razao_social: c.razao_social,
    fantasia: c.fantasia || '',
    cnpj: c.cnpj,
    ie: c.ie || '',
    im: c.im || '',
    crt: c.crt,
    logradouro: c.logradouro || '',
    numero: c.numero || '',
    complemento: c.complemento || '',
    bairro: c.bairro || '',
    municipio: c.municipio || '',
    municipio_ibge: c.municipio_ibge || '',
    uf: c.uf || '',
    cep: c.cep || '',
    telefone: c.telefone || '',
    email: c.email || '',
    ambiente: c.ambiente
  })
}

watch(company, (val) => {
  if (val && !isEditing.value) {
    populateState(val)
  }
}, { immediate: true })

function startEditing() {
  if (company.value) populateState(company.value)
  isEditing.value = true
}

function cancelEditing() {
  if (company.value) populateState(company.value)
  isEditing.value = false
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
    await put(`/companies/${companyId.value}`, event.data)
    toast.add({ title: 'Empresa atualizada', description: event.data.razao_social, color: 'success' })
    isEditing.value = false
    refresh()
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao atualizar empresa.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}

async function accessAsCompany() {
  if (!company.value) return
  setCompany(company.value)
  toast.add({
    title: 'Acessando como empresa',
    description: company.value.fantasia || company.value.razao_social
  })
  await clearNuxtData()
  await navigateTo('/')
}

const actions = computed(() => [
  {
    type: 'label' as const,
    label: 'Ações'
  },
  {
    label: 'Acessar como empresa',
    icon: 'i-lucide-log-in',
    onSelect: accessAsCompany
  },
  {
    type: 'separator' as const
  },
  {
    label: 'Excluir empresa',
    icon: 'i-lucide-trash',
    color: 'error' as const,
    onSelect() {
      if (company.value) deletingCompany.value = company.value
    }
  }
])

function onDeleted() {
  deletingCompany.value = null
  router.push('/empresas')
}

function formatAddress(c: Company) {
  const parts = [
    c.logradouro, c.numero, c.bairro,
    c.municipio ? `${c.municipio} - ${c.uf || ''}` : '',
    c.cep
  ].filter(Boolean)
  return parts.join(', ') || '—'
}
</script>

<template>
  <UDashboardPanel id="empresa-detalhe">
    <template #header>
      <UDashboardNavbar title="Empresa">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UBadge
            v-if="company"
            :color="company.ambiente === 'producao' ? 'success' : 'warning'"
            variant="subtle"
          >
            {{ company.ambiente === 'producao' ? 'Produção' : 'Homologação' }}
          </UBadge>

          <template v-if="!isEditing">
            <UDropdownMenu
              v-if="company"
              :items="actions"
              :content="{ align: 'end' }"
            >
              <UButton
                icon="i-lucide-ellipsis-vertical"
                color="neutral"
                variant="ghost"
              />
            </UDropdownMenu>

            <UButton
              label="Editar"
              icon="i-lucide-pencil"
              color="primary"
              :disabled="!company"
              @click="startEditing"
            />
          </template>

          <template v-else>
            <UButton
              label="Cancelar"
              color="neutral"
              variant="outline"
              @click="cancelEditing"
            />
            <UButton
              label="Salvar Alterações"
              color="primary"
              :loading="loading"
              @click="handleSubmit"
            />
          </template>

          <UButton
            color="neutral"
            variant="ghost"
            icon="i-lucide-arrow-left"
            @click="router.push('/empresas')"
          />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <UBreadcrumb :items="breadcrumbs" />
      </UDashboardToolbar>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <div v-else-if="!company" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-building" class="size-12 text-muted mb-4" />
        <p class="text-muted">
          Empresa não encontrada.
        </p>
        <UButton
          label="Voltar"
          variant="link"
          class="mt-2"
          @click="router.push('/empresas')"
        />
      </div>

      <div v-else-if="!isEditing" class="space-y-6">
        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-building text-muted" />
              Dados da Empresa
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                CNPJ
              </p>
              <p class="font-medium">
                {{ company.cnpj }}
              </p>
            </div>
            <div class="sm:col-span-2">
              <p class="text-sm text-muted mb-1">
                Razão Social
              </p>
              <p class="font-medium">
                {{ company.razao_social }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Nome Fantasia
              </p>
              <p class="font-medium">
                {{ company.fantasia || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Inscrição Estadual
              </p>
              <p class="font-medium">
                {{ company.ie || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Inscrição Municipal
              </p>
              <p class="font-medium">
                {{ company.im || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Regime Tributário
              </p>
              <p class="font-medium">
                {{ crtLabels[company.crt] || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Ambiente
              </p>
              <UBadge
                :color="company.ambiente === 'producao' ? 'success' : 'warning'"
                variant="subtle"
              >
                {{ company.ambiente === 'producao' ? 'Produção' : 'Homologação' }}
              </UBadge>
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

          <p class="text-sm">
            {{ formatAddress(company) }}
          </p>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-phone text-muted" />
              Contato
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Telefone
              </p>
              <p class="font-medium">
                {{ company.telefone || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                E-mail
              </p>
              <p class="font-medium">
                {{ company.email || '—' }}
              </p>
            </div>
          </div>
        </UCard>
      </div>

      <UForm
        v-else
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
      </UForm>
    </template>
  </UDashboardPanel>

  <EmpresasDeleteModal
    v-if="deletingCompany"
    :company="deletingCompany"
    @deleted="onDeleted"
  />
</template>
