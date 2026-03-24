<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { AppUser, AuthUser, Company, PaginatedResponse } from '~/types'

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
  role: z.enum(['admin', 'office_user', 'company_user']),
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

// Load companies for company_user role
const { data: companiesData } = useApi<PaginatedResponse<Company>>('/companies', {
  lazy: true,
  query: { per_page: 200 }
})

const companies = computed(() => companiesData.value?.data ?? [])

const availableRoles = computed(() => {
  if (isAdmin.value) {
    return [
      { label: 'Admin', value: 'admin' },
      { label: 'Contador', value: 'office_user' },
      { label: 'Empresário', value: 'company_user' }
    ]
  }
  return [{ label: 'Empresário', value: 'company_user' }]
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    const payload: Record<string, any> = { ...event.data }
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
  <UModal v-model:open="open">
    <UButton
      label="Novo Usuário"
      icon="i-lucide-plus"
      color="neutral"
    />

    <template #content>
      <UModalHeader title="Novo Usuário" />

      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="p-4 space-y-4"
        @submit="onSubmit"
      >
        <UFormField name="name" label="Nome" required>
          <UInput v-model="state.name" />
        </UFormField>

        <UFormField name="email" label="E-mail" required>
          <UInput v-model="state.email" type="email" />
        </UFormField>

        <UFormField name="password" label="Senha" required>
          <UInput v-model="state.password" type="password" />
        </UFormField>

        <UFormField name="password_confirmation" label="Confirmar Senha" required>
          <UInput v-model="state.password_confirmation" type="password" />
        </UFormField>

        <UFormField name="phone" label="Telefone">
          <UInput v-model="state.phone" />
        </UFormField>

        <UFormField name="role" label="Perfil" required>
          <USelect v-model="state.role" :items="availableRoles" />
        </UFormField>

        <UFormField v-if="isAdmin && state.role !== 'admin'" name="office_id" label="Escritório" required>
          <UInput v-model="state.office_id" type="number" />
        </UFormField>

        <UFormField v-if="state.role === 'company_user'" name="company_ids" label="Empresas">
          <USelect
            v-model="state.company_ids"
            :items="companies.map(c => ({ label: c.fantasia || c.razao_social, value: c.id }))"
            multiple
            placeholder="Selecione as empresas"
          />
        </UFormField>

        <UFormField name="is_active">
          <UCheckbox v-model="state.is_active" label="Usuário ativo" />
        </UFormField>

        <div class="flex justify-end gap-2 pt-2">
          <UButton label="Cancelar" variant="ghost" @click="open = false" />
          <UButton type="submit" label="Criar" :loading="loading" />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
