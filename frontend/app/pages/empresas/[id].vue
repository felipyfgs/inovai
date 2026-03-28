<script setup lang="ts">
import * as z from 'zod'
import type { BreadcrumbItem, FormSubmitEvent, StepperItem, TabsItem } from '@nuxt/ui'
import type { Company } from '~/types'

import { UButton, UBadge, UDropdownMenu } from '#components'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { put } = useApiMutation()
const { extractMessage } = useApiError()
const { setCompany } = useCurrentCompany()
const formRef = useTemplateRef('formRef')
const stepperRef = useTemplateRef('stepperRef')

const companyId = computed(() => route.params.id as string)
const { data: company, status, refresh } = useApi<Company>(`/companies/${companyId.value}`, { lazy: false })

const activeTab = ref('geral')
const isEditing = ref(false)
const editStep = ref('empresa')
const loading = ref(false)
const deletingCompany = ref<Company | null>(null)
const modulesCompany = ref<Company | null>(null)

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

const allModules: Record<string, string> = {
  nfe: 'NF-e',
  nfce: 'NFC-e',
  cte: 'CT-e',
  mdfe: 'MDF-e',
  nfse: 'NFS-e',
  orcamento: 'Orçamentos',
  estoque: 'Estoque',
  financeiro: 'Financeiro',
  restaurante: 'Restaurante',
  relatorios: 'Relatórios'
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { label: 'Empresas', icon: 'i-lucide-building', to: '/empresas' },
  { label: company.value ? (company.value.fantasia || company.value.razao_social) : '...' }
])

const certStatus = computed(() => {
  if (!company.value?.certificado_validade) return { label: 'Sem certificado', color: 'neutral' as const }
  const isExpired = new Date(company.value.certificado_validade) < new Date()
  if (isExpired) return { label: 'Vencido', color: 'error' as const }
  return { label: company.value.certificado_validade, color: 'success' as const }
})

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

function handleNext() {
  stepperRef.value?.next()
}

function handlePrev() {
  stepperRef.value?.prev()
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

const viewTabs = computed<TabsItem[]>(() => [
  { label: 'Geral', icon: 'i-lucide-building', value: 'geral' },
  { label: 'Fiscal', icon: 'i-lucide-file-text', value: 'fiscal' },
  { label: 'Usuários', icon: 'i-lucide-users', value: 'usuarios' },
  { label: 'Módulos', icon: 'i-lucide-puzzle', value: 'modulos' }
])

const editSteps = computed<StepperItem[]>(() => [
  { title: 'Empresa', description: 'Dados cadastrais', icon: 'i-lucide-building', value: 'empresa' },
  { title: 'Endereço e Contato', description: 'Localização e comunicação', icon: 'i-lucide-map-pin', value: 'endereco-contato' }
])
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
            v-if="company && !isEditing"
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

      <div v-else class="space-y-6">
        <div v-if="!isEditing" class="flex items-center gap-4 p-4 rounded-lg bg-elevated/50 border border-default">
          <div class="flex size-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
            <UIcon name="i-lucide-building-2" class="size-6" />
          </div>
          <div class="min-w-0 flex-1">
            <h2 class="text-lg font-semibold truncate">
              {{ company.fantasia || company.razao_social }}
            </h2>
            <p class="text-sm text-muted truncate">
              {{ company.razao_social }}
            </p>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <UBadge
              :color="certStatus.color"
              variant="subtle"
            >
              <UIcon name="i-lucide-shield-check" class="size-3.5 mr-1" />
              {{ certStatus.label }}
            </UBadge>
            <UBadge
              :color="company.ambiente === 'producao' ? 'success' : 'warning'"
              variant="subtle"
            >
              {{ company.ambiente === 'producao' ? 'Produção' : 'Homologação' }}
            </UBadge>
          </div>
        </div>

        <UTabs
          v-if="!isEditing"
          v-model="activeTab"
          :items="viewTabs"
          :content="false"
          class="w-full"
        />

        <template v-if="!isEditing">
          <div v-if="activeTab === 'geral'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <UCard>
                <template #header>
                  <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                    <UIcon name="i-lucide-landmark" class="size-4 text-muted" />
                    Identificação
                  </h3>
                </template>
                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-muted">CNPJ</span>
                    <span class="text-sm font-medium font-mono">{{ company.cnpj }}</span>
                  </div>
                  <USeparator />
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-muted">Razão Social</span>
                    <span class="text-sm font-medium text-right max-w-[60%] truncate">{{ company.razao_social }}</span>
                  </div>
                  <USeparator />
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-muted">Nome Fantasia</span>
                    <span class="text-sm font-medium">{{ company.fantasia || '—' }}</span>
                  </div>
                  <USeparator />
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-muted">Inscrição Estadual</span>
                    <span class="text-sm font-medium">{{ company.ie || '—' }}</span>
                  </div>
                  <USeparator />
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-muted">Inscrição Municipal</span>
                    <span class="text-sm font-medium">{{ company.im || '—' }}</span>
                  </div>
                  <USeparator />
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-muted">Regime Tributário</span>
                    <span class="text-sm font-medium">{{ crtLabels[company.crt] || '—' }}</span>
                  </div>
                </div>
              </UCard>

              <UCard>
                <template #header>
                  <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                    <UIcon name="i-lucide-shield-check" class="size-4 text-muted" />
                    Certificado Digital
                  </h3>
                </template>
                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-muted">Status</span>
                    <UBadge
                      :color="certStatus.color"
                      variant="subtle"
                    >
                      {{ certStatus.label }}
                    </UBadge>
                  </div>
                  <USeparator />
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-muted">Ambiente</span>
                    <UBadge
                      :color="company.ambiente === 'producao' ? 'success' : 'warning'"
                      variant="subtle"
                    >
                      {{ company.ambiente === 'producao' ? 'Produção' : 'Homologação' }}
                    </UBadge>
                  </div>
                </div>
              </UCard>
            </div>

            <UCard>
              <template #header>
                <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                  <UIcon name="i-lucide-map-pin" class="size-4 text-muted" />
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
                  <UIcon name="i-lucide-phone" class="size-4 text-muted" />
                  Contato
                </h3>
              </template>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="flex items-center gap-3">
                  <div class="flex size-8 items-center justify-center rounded-md bg-elevated text-muted">
                    <UIcon name="i-lucide-phone" class="size-4" />
                  </div>
                  <div>
                    <p class="text-xs text-muted">
                      Telefone
                    </p>
                    <p class="text-sm font-medium">
                      {{ company.telefone || '—' }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <div class="flex size-8 items-center justify-center rounded-md bg-elevated text-muted">
                    <UIcon name="i-lucide-mail" class="size-4" />
                  </div>
                  <div>
                    <p class="text-xs text-muted">
                      E-mail
                    </p>
                    <p class="text-sm font-medium">
                      {{ company.email || '—' }}
                    </p>
                  </div>
                </div>
              </div>
            </UCard>
          </div>

          <div v-if="activeTab === 'fiscal'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <UCard>
              <template #header>
                <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                  <UIcon name="i-lucide-file-text" class="size-4 text-muted" />
                  Numeração NF-e
                </h3>
              </template>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted">Série</span>
                  <span class="text-sm font-medium">{{ company.serie_nfe }}</span>
                </div>
                <USeparator />
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted">Próximo Número</span>
                  <span class="text-sm font-medium">{{ company.proximo_numero_nfe }}</span>
                </div>
              </div>
            </UCard>

            <UCard>
              <template #header>
                <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                  <UIcon name="i-lucide-receipt" class="size-4 text-muted" />
                  Numeração NFC-e
                </h3>
              </template>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted">Série</span>
                  <span class="text-sm font-medium">{{ company.serie_nfce }}</span>
                </div>
                <USeparator />
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted">Próximo Número</span>
                  <span class="text-sm font-medium">{{ company.proximo_numero_nfce }}</span>
                </div>
              </div>
            </UCard>

            <UCard>
              <template #header>
                <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                  <UIcon name="i-lucide-truck" class="size-4 text-muted" />
                  Numeração CT-e
                </h3>
              </template>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted">Série</span>
                  <span class="text-sm font-medium">{{ company.serie_cte }}</span>
                </div>
                <USeparator />
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted">Próximo Número</span>
                  <span class="text-sm font-medium">{{ company.proximo_numero_cte }}</span>
                </div>
              </div>
            </UCard>

            <UCard>
              <template #header>
                <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                  <UIcon name="i-lucide-file-stack" class="size-4 text-muted" />
                  Numeração MDF-e
                </h3>
              </template>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted">Série</span>
                  <span class="text-sm font-medium">{{ company.serie_mdfe }}</span>
                </div>
                <USeparator />
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted">Próximo Número</span>
                  <span class="text-sm font-medium">{{ company.proximo_numero_mdfe }}</span>
                </div>
              </div>
            </UCard>
          </div>

          <div v-if="activeTab === 'usuarios'">
            <UCard>
              <template #header>
                <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                  <UIcon name="i-lucide-users" class="size-4 text-muted" />
                  Usuários da Empresa
                </h3>
              </template>
              <EmpresasCompanyUsersTab :company-id="company.id" />
            </UCard>
          </div>

          <UCard v-if="activeTab === 'modulos'">
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                  <UIcon name="i-lucide-puzzle" class="size-4 text-muted" />
                  Módulos Ativos
                </h3>
                <UButton
                  label="Gerenciar Módulos"
                  icon="i-lucide-settings"
                  size="xs"
                  color="neutral"
                  variant="outline"
                  @click="modulesCompany = company!"
                />
              </div>
            </template>
            <div v-if="company.modules && company.modules.length > 0" class="flex flex-wrap gap-2">
              <UBadge
                v-for="mod in company.modules.filter(m => m.is_active)"
                :key="mod.module"
                variant="subtle"
                color="primary"
              >
                {{ allModules[mod.module] || mod.module }}
              </UBadge>
            </div>
            <p v-else class="text-sm text-muted">
              Nenhum módulo ativo.
            </p>
          </UCard>
        </template>

        <template v-else>
          <UStepper
            ref="stepperRef"
            v-model="editStep"
            :items="editSteps"
            disabled
            class="w-full"
          />

          <UForm
            ref="formRef"
            :schema="schema"
            :state="state"
            @submit="onSubmit"
          >
            <UCard v-if="editStep === 'empresa'">
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

              <div class="flex justify-end gap-3 mt-6">
                <UButton
                  label="Cancelar"
                  color="neutral"
                  variant="outline"
                  @click="cancelEditing"
                />
                <UButton
                  label="Próximo"
                  icon="i-lucide-arrow-right"
                  color="primary"
                  @click="handleNext"
                />
              </div>
            </UCard>

            <UCard v-else>
              <div class="space-y-4">
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

                <USeparator />

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
              </div>

              <div class="flex justify-between mt-6">
                <UButton
                  label="Voltar"
                  icon="i-lucide-arrow-left"
                  color="neutral"
                  variant="outline"
                  @click="handlePrev"
                />
                <div class="flex gap-3">
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
                </div>
              </div>
            </UCard>
          </UForm>
        </template>
      </div>
    </template>
  </UDashboardPanel>

  <EmpresasDeleteModal
    v-if="deletingCompany"
    :company="deletingCompany"
    @deleted="onDeleted"
  />
  <EmpresasModulesModal
    v-if="modulesCompany"
    :company="modulesCompany"
    @updated="() => { modulesCompany = null; refresh() }"
  />
</template>
