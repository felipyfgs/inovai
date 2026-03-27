<script setup lang="ts">
import type { BreadcrumbItem, FormSubmitEvent } from '@nuxt/ui'
import type { Pessoa } from '~/types'
import { UButton, UBadge, UDropdownMenu } from '#components'
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

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { put } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const pessoaId = computed(() => Number(route.params.id))
const url = computed(() => `/pessoas/${pessoaId.value}`)

const { data: pessoa, status, refresh } = useApi<Pessoa>(url, { lazy: false })

const isEditing = ref(false)
const loading = ref(false)
const deletingPessoa = ref<Pessoa | null>(null)

const state = reactive<Partial<Schema>>({})

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { label: 'Cadastros', icon: 'i-lucide-folder', to: '/cadastros/pessoas' },
  { label: 'Pessoas', icon: 'i-lucide-users', to: '/cadastros/pessoas' },
  { label: pessoa.value?.razao_social || '...' }
])

const tipoLabel: Record<string, string> = {
  cliente: 'Cliente',
  fornecedor: 'Fornecedor',
  ambos: 'Ambos'
}

const tipoColor: Record<string, 'success' | 'warning' | 'info'> = {
  cliente: 'success',
  fornecedor: 'warning',
  ambos: 'info'
}

const indIeLabel: Record<number, string> = {
  1: 'Contribuinte',
  2: 'Isento',
  9: 'Não contribuinte'
}

function populateState(p: Pessoa) {
  Object.assign(state, {
    tipo: p.tipo,
    tipo_pessoa: p.tipo_pessoa,
    razao_social: p.razao_social,
    fantasia: p.fantasia || '',
    cpf_cnpj: p.cpf_cnpj || '',
    ie: p.ie || '',
    ind_ie: p.ind_ie,
    logradouro: p.logradouro || '',
    numero: p.numero || '',
    complemento: p.complemento || '',
    bairro: p.bairro || '',
    municipio: p.municipio || '',
    municipio_ibge: p.municipio_ibge || '',
    uf: p.uf || '',
    cep: p.cep || '',
    telefone: p.telefone || '',
    celular: p.celular || '',
    email: p.email || '',
    observacoes: p.observacoes || ''
  })
}

watch(pessoa, (val) => {
  if (val && !isEditing.value) {
    populateState(val)
  }
}, { immediate: true })

function startEditing() {
  if (pessoa.value) {
    populateState(pessoa.value)
  }
  isEditing.value = true
}

function cancelEditing() {
  if (pessoa.value) {
    populateState(pessoa.value)
  }
  isEditing.value = false
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await put(url.value, event.data)
    toast.add({ title: 'Pessoa atualizada', description: event.data.razao_social, color: 'success' })
    isEditing.value = false
    refresh()
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao atualizar.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}

const actions = computed(() => [
  {
    type: 'label' as const,
    label: 'Ações'
  },
  {
    label: 'Excluir pessoa',
    icon: 'i-lucide-trash',
    color: 'error' as const,
    onSelect() {
      if (pessoa.value) deletingPessoa.value = pessoa.value
    }
  }
])

function onDeleted() {
  deletingPessoa.value = null
  router.push('/cadastros/pessoas')
}
</script>

<template>
  <UDashboardPanel id="pessoa-detalhe">
    <template #header>
      <UDashboardNavbar title="Pessoa">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <template v-if="!isEditing">
            <UDropdownMenu
              v-if="pessoa"
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
              :disabled="!pessoa"
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
            @click="router.push('/cadastros/pessoas')"
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

      <div v-else-if="!pessoa" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-users" class="size-12 text-muted mb-4" />
        <p class="text-muted">
          Pessoa não encontrada.
        </p>
        <UButton
          label="Voltar"
          variant="link"
          class="mt-2"
          @click="router.push('/cadastros/pessoas')"
        />
      </div>

      <div v-else-if="!isEditing" class="space-y-6">
        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                <span class="i-lucide-tag text-muted" />
                Tipo e Classificação
              </h3>
              <UBadge :color="pessoa.is_active ? 'success' : 'error'" variant="subtle">
                {{ pessoa.is_active ? 'Ativo' : 'Inativo' }}
              </UBadge>
            </div>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Tipo
              </p>
              <UBadge
                :color="tipoColor[pessoa.tipo] || 'neutral'"
                variant="subtle"
                class="capitalize"
              >
                {{ tipoLabel[pessoa.tipo] || pessoa.tipo }}
              </UBadge>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Tipo de Pessoa
              </p>
              <p class="font-medium">
                {{ pessoa.tipo_pessoa === 'PJ' ? 'Pessoa Jurídica (PJ)' : 'Pessoa Física (PF)' }}
              </p>
            </div>
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
            <div class="sm:col-span-2">
              <p class="text-sm text-muted mb-1">
                Razão Social / Nome Completo
              </p>
              <p class="font-medium">
                {{ pessoa.razao_social }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Nome Fantasia
              </p>
              <p class="font-medium">
                {{ pessoa.fantasia || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                CPF / CNPJ
              </p>
              <p class="font-medium font-mono">
                {{ pessoa.cpf_cnpj || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Inscrição Estadual
              </p>
              <p class="font-medium">
                {{ pessoa.ie || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Indicador de IE
              </p>
              <p class="font-medium">
                {{ indIeLabel[pessoa.ind_ie] || '—' }}
              </p>
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

          <div v-if="pessoa.logradouro || pessoa.municipio">
            <p class="font-medium text-sm">
              {{ [pessoa.logradouro, pessoa.numero, pessoa.complemento].filter(Boolean).join(', ') || '—' }}
            </p>
            <p class="text-sm text-muted">
              {{ [pessoa.bairro, pessoa.municipio, pessoa.uf, pessoa.cep].filter(Boolean).join(' — ') }}
            </p>
          </div>
          <p v-else class="text-sm text-muted">
            Endereço não informado.
          </p>
        </UCard>

        <UCard>
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-phone text-muted" />
              Contato
            </h3>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-muted mb-1">
                Telefone
              </p>
              <p class="font-medium">
                {{ pessoa.telefone || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                Celular
              </p>
              <p class="font-medium">
                {{ pessoa.celular || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted mb-1">
                E-mail
              </p>
              <p class="font-medium truncate" :title="pessoa.email || undefined">
                {{ pessoa.email || '—' }}
              </p>
            </div>
          </div>
        </UCard>

        <UCard v-if="pessoa.observacoes">
          <template #header>
            <h3 class="text-sm font-semibold text-highlighted flex items-center gap-2">
              <span class="i-lucide-message-square text-muted" />
              Observações
            </h3>
          </template>

          <p class="text-sm whitespace-pre-wrap">
            {{ pessoa.observacoes }}
          </p>
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
              <UInput v-model="state.razao_social" class="w-full" />
            </UFormField>
            <UFormField label="Nome Fantasia" name="fantasia">
              <UInput v-model="state.fantasia" class="w-full" />
            </UFormField>
            <UFormField label="CPF / CNPJ" name="cpf_cnpj">
              <UInput v-model="state.cpf_cnpj" class="w-full" />
            </UFormField>
            <UFormField label="Inscrição Estadual" name="ie">
              <UInput v-model="state.ie" class="w-full" />
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
              <UInput v-model="state.telefone" class="w-full" />
            </UFormField>
            <UFormField label="Celular" name="celular">
              <UInput v-model="state.celular" class="w-full" />
            </UFormField>
            <UFormField label="E-mail" name="email">
              <UInput v-model="state.email" class="w-full" type="email" />
            </UFormField>
          </div>
        </UCard>

        <UCard>
          <UFormField label="Observações" name="observacoes">
            <UTextarea v-model="state.observacoes" class="w-full" :rows="3" />
          </UFormField>
        </UCard>
      </UForm>
    </template>
  </UDashboardPanel>

  <PessoasDeleteModal
    v-if="deletingPessoa"
    :pessoa="deletingPessoa"
    @deleted="onDeleted"
  />
</template>
