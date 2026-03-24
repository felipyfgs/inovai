<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { AppUser, AuthUser, Company, PaginatedResponse } from '~/types'

const props = defineProps<{ user: AppUser | null }>()
const emit = defineEmits<{ updated: [] }>()

const { user: currentUser } = useSanctumAuth<AuthUser>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { put } = useApiMutation()
const formRef = useTemplateRef('formRef')

const isAdmin = computed(() => currentUser.value?.roles?.some(r => r.name === 'admin') ?? false)

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  email: z.string().email('E-mail inválido'),
  phone: z.string().optional(),
  is_active: z.boolean(),
  role: z.enum(['admin', 'office_user', 'company_user']).optional()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({})

// Load companies for company_user role
const { data: companiesData } = useApi<PaginatedResponse<Company>>('/companies', {
  lazy: true,
  query: { per_page: 200 }
})

const companies = computed(() => companiesData.value?.data ?? [])

const { data: userCompaniesData, refresh: refreshUserCompanies } = useApi<Company[]>(
  computed(() => props.user ? `/users/${props.user.id}/companies` : ''),
  { lazy: true }
)

const userCompanies = computed(() => userCompaniesData.value ?? [])

watch(() => props.user, (u) => {
  if (u) {
    Object.assign(state, {
      name: u.name,
      email: u.email,
      phone: u.phone ?? '',
      is_active: u.is_active,
      role: u.roles?.[0]?.name ?? 'company_user'
    })
    refreshUserCompanies()
    open.value = true
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  if (!props.user) return

  loading.value = true
  try {
    const payload: Record<string, any> = { ...event.data }
    if (!isAdmin.value) {
      delete payload.role
    }
    await put(`/users/${props.user.id}`, payload)
    toast.add({ title: 'Usuário atualizado', color: 'success' })
    open.value = false
    emit('updated')
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao atualizar.', color: 'error' })
  } finally {
    loading.value = false
  }
}

async function toggleCompany(companyId: number, attach: boolean) {
  if (!props.user) return

  try {
    if (attach) {
      const { post } = useApiMutation()
      await post(`/users/${props.user.id}/companies`, { company_ids: [companyId] })
    } else {
      const { del } = useApiMutation()
      await del(`/users/${props.user.id}/companies/${companyId}`)
    }
    refreshUserCompanies()
    toast.add({ title: attach ? 'Empresa vinculada' : 'Empresa desvinculada', color: 'success' })
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro.', color: 'error' })
  }
}

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
</script>

<template>
  <UModal v-model:open="open">
    <template #content>
      <UModalHeader title="Editar Usuário" />

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

        <UFormField name="phone" label="Telefone">
          <UInput v-model="state.phone" />
        </UFormField>

        <UFormField v-if="isAdmin" name="role" label="Perfil">
          <USelect v-model="state.role" :items="availableRoles" />
        </UFormField>

        <UFormField name="is_active">
          <UCheckbox v-model="state.is_active" label="Usuário ativo" />
        </UFormField>

        <!-- Companies section for company_user -->
        <div v-if="state.role === 'company_user'" class="space-y-2">
          <p class="text-sm font-medium">Empresas Vinculadas</p>
          <div class="space-y-1 max-h-48 overflow-y-auto">
            <div
              v-for="company in companies"
              :key="company.id"
              class="flex items-center justify-between p-2 rounded bg-elevated"
            >
              <span class="text-sm">{{ company.fantasia || company.razao_social }}</span>
              <UCheckbox
                :model-value="userCompanies.some(c => c.id === company.id)"
                @update:model-value="(v: boolean | string) => toggleCompany(company.id, v === true || v === 'true')"
              />
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <UButton label="Cancelar" variant="ghost" @click="open = false" />
          <UButton type="submit" label="Salvar" :loading="loading" />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
