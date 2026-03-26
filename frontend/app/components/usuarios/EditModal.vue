<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { AppUser, Company, Office, PaginatedResponse } from '~/types'

const props = defineProps<{ user: AppUser | null }>()
const emit = defineEmits<{ updated: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { put } = useApiMutation()
const formRef = useTemplateRef('formRef')
const { isPlatformAdmin } = useAccessContext()
const isEffectiveAdmin = computed(() => isPlatformAdmin.value)

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  email: z.string().email('E-mail inválido'),
  phone: z.string().optional(),
  is_active: z.boolean(),
  role: z.enum(['admin', 'office_user', 'accountant', 'company_user']).optional(),
  office_id: z.coerce.number().optional()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({})

// Load companies for company_user role
const { data: companiesData } = useApi<PaginatedResponse<Company>>('/companies', {
  lazy: true,
  query: { per_page: 200 }
})

const companies = computed(() => companiesData.value?.data ?? [])

const offices = ref<Office[]>([])

if (isEffectiveAdmin.value) {
  const { data: officesData } = useApi<PaginatedResponse<Office>>('/admin/offices', {
    lazy: true,
    query: { per_page: 200 }
  })
  watch(officesData, (data) => {
    offices.value = data?.data ?? []
  })
}

const userCompanies = ref<Company[]>([])

async function fetchUserCompanies(userId: number) {
  try {
    const { $sanctumClient } = useNuxtApp()
    const data = await $sanctumClient<Company[]>(`/users/${userId}/companies`)
    userCompanies.value = data ?? []
  } catch {
    userCompanies.value = []
  }
}

watch(() => props.user, (u) => {
  if (u) {
    Object.assign(state, {
      name: u.name,
      email: u.email,
      phone: u.phone ?? '',
      is_active: u.is_active,
      role: u.roles?.[0]?.name ?? 'company_user',
      office_id: u.office_id ?? undefined
    })
    fetchUserCompanies(u.id)
    open.value = true
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  if (!props.user) return

  loading.value = true
  try {
    const payload: Record<string, unknown> = { ...event.data }
    if (!isEffectiveAdmin.value) {
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
    fetchUserCompanies(props.user!.id)
    toast.add({ title: attach ? 'Empresa vinculada' : 'Empresa desvinculada', color: 'success' })
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro.', color: 'error' })
  }
}

const availableRoles = computed(() => {
  if (isEffectiveAdmin.value) {
    return [
      { label: 'Admin', value: 'admin' },
      { label: 'Contador', value: 'accountant' },
      { label: 'Empresário', value: 'company_user' }
    ]
  }
  return [{ label: 'Empresário', value: 'company_user' }]
})
</script>

<template>
  <UModal
    v-model:open="open"
    title="Editar Usuário"
    description="Atualize os dados do usuário."
    :ui="{ content: 'w-full sm:max-w-lg', body: 'max-h-[75vh] overflow-y-auto', footer: 'justify-end shrink-0' }"
  >
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

          <UFormField name="phone" label="Telefone">
            <UInput
              v-model="state.phone"
              placeholder="(00) 00000-0000"
              icon="i-lucide-phone"
              class="w-full"
            />
          </UFormField>

          <UFormField v-if="isEffectiveAdmin" name="role" label="Perfil">
            <USelect v-model="state.role" :items="availableRoles" class="w-full" />
          </UFormField>
        </div>

        <UFormField
          v-if="isEffectiveAdmin && (state.role === 'office_user' || state.role === 'accountant')"
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

        <UFormField name="is_active">
          <UCheckbox v-model="state.is_active" label="Usuário ativo" />
        </UFormField>

        <div v-if="state.role === 'company_user'" class="space-y-3">
          <USeparator label="Empresas Vinculadas" />
          <div class="space-y-1 max-h-48 overflow-y-auto">
            <div
              v-for="company in companies"
              :key="company.id"
              class="flex items-center justify-between p-2 rounded-lg bg-elevated/50"
            >
              <span class="text-sm">{{ company.fantasia || company.razao_social }}</span>
              <UCheckbox
                :model-value="userCompanies.some(c => c.id === company.id)"
                @update:model-value="(v: boolean | string) => toggleCompany(company.id, v === true || v === 'true')"
              />
            </div>
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
        label="Salvar"
        color="primary"
        :loading="loading"
        @click="formRef?.submit()"
      />
    </template>
  </UModal>
</template>
