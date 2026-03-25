<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { AuthUser, Company, Office, PaginatedResponse } from '~/types'

const emit = defineEmits<{ created: [] }>()

const { user } = useSanctumAuth<AuthUser>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const formRef = useTemplateRef('formRef')

const isAdmin = computed(() => user.value?.roles?.some(r => r.name === 'admin') ?? false)

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  email: z.string().email('E-mail inválido'),
  password: z.string().min(6, 'Mínimo 6 caracteres'),
  password_confirmation: z.string(),
  phone: z.string().optional(),
  role: z.enum(['admin', 'office_user', 'accountant', 'company_user']),
  office_id: z.coerce.number().optional(),
  is_active: z.boolean().default(true),
  company_ids: z.array(z.coerce.number()).optional()
}).refine(data => data.password === data.password_confirmation, {
  message: 'Senhas não conferem',
  path: ['password_confirmation']
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  role: 'company_user',
  is_active: true,
  company_ids: []
})

const { data: companiesData } = useApi<PaginatedResponse<Company>>('/companies', {
  lazy: true,
  query: { per_page: 200 }
})

const companies = computed(() => companiesData.value?.data ?? [])

const offices = ref<Office[]>([])

if (isAdmin.value) {
  const { data: officesData } = useApi<PaginatedResponse<Office>>('/admin/offices', {
    lazy: true,
    query: { per_page: 200 }
  })
  watch(officesData, (data) => {
    offices.value = data?.data ?? []
  })
}

const availableRoles = computed(() => {
  if (isAdmin.value) {
    return [
      { label: 'Admin', value: 'admin' },
      { label: 'Contador', value: 'accountant' },
      { label: 'Empresário', value: 'company_user' }
    ]
  }
  return [{ label: 'Empresário', value: 'company_user' }]
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    const payload: Record<string, unknown> = { ...event.data }
    if (!isAdmin.value) {
      delete payload.office_id
    }
    if (event.data.role !== 'company_user') {
      delete payload.company_ids
    }
    await post('/users', payload)
    toast.add({ title: 'Usuário criado com sucesso', color: 'success' })
    open.value = false
    emit('created')
    resetForm()
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao criar usuário.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function resetForm() {
  Object.assign(state, {
    name: '', email: '', password: '', password_confirmation: '',
    phone: '', role: 'company_user', is_active: true, company_ids: []
  })
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Novo Usuário"
    description="Preencha os dados para criar um novo usuário."
    :ui="{ content: 'w-full sm:max-w-lg', body: 'max-h-[75vh] overflow-y-auto', footer: 'justify-end shrink-0' }"
  >
    <UButton
      label="Novo Usuário"
      icon="i-lucide-plus"
      color="neutral"
    />

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
            name="name"
            label="Nome"
            required
            class="sm:col-span-2"
          >
            <UInput
              v-model="state.name"
              placeholder="Nome completo"
              icon="i-lucide-user"
              class="w-full"
            />
          </UFormField>

          <UFormField
            name="email"
            label="E-mail"
            required
            class="sm:col-span-2"
          >
            <UInput
              v-model="state.email"
              type="email"
              placeholder="email@exemplo.com"
              icon="i-lucide-mail"
              class="w-full"
            />
          </UFormField>

          <UFormField name="password" label="Senha" required>
            <UInput
              v-model="state.password"
              type="password"
              placeholder="Mínimo 6 caracteres"
              icon="i-lucide-lock"
              class="w-full"
            />
          </UFormField>

          <UFormField name="password_confirmation" label="Confirmar Senha" required>
            <UInput
              v-model="state.password_confirmation"
              type="password"
              placeholder="Repita a senha"
              icon="i-lucide-lock"
              class="w-full"
            />
          </UFormField>

          <UFormField name="phone" label="Telefone">
            <UInput
              v-model="state.phone"
              placeholder="(00) 00000-0000"
              icon="i-lucide-phone"
              class="w-full"
            />
          </UFormField>

          <UFormField name="role" label="Perfil" required>
            <USelect v-model="state.role" :items="availableRoles" class="w-full" />
          </UFormField>
        </div>

        <UFormField
          v-if="isAdmin && (state.role === 'office_user' || state.role === 'accountant')"
          name="office_id"
          label="Escritório"
          required
        >
          <USelect
            v-model="state.office_id"
            :items="offices.map(o => ({ label: o.name, value: o.id }))"
            placeholder="Selecione o escritório"
            class="w-full"
          />
        </UFormField>

        <UFormField v-if="state.role === 'company_user'" name="company_ids" label="Empresas">
          <USelect
            v-model="state.company_ids"
            :items="companies.map(c => ({ label: c.fantasia || c.razao_social, value: c.id }))"
            multiple
            placeholder="Selecione as empresas"
            class="w-full"
          />
        </UFormField>

        <UFormField name="is_active">
          <UCheckbox v-model="state.is_active" label="Usuário ativo" />
        </UFormField>
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
        label="Criar Usuário"
        color="primary"
        :loading="loading"
        @click="formRef?.submit()"
      />
    </template>
  </UModal>
</template>
