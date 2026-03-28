<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent, SelectMenuItem } from '@nuxt/ui'

import { UButton } from '#components'

const props = defineProps<{
  companyId: number
}>()

const emit = defineEmits<{ added: [] }>()

const open = ref(true)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const mode = ref<'select' | 'create'>('select')

const selectedUserId = ref<number | null>(null)
const { data: usersData } = useApi<{ data: { id: number, name: string, email: string }[] }>('/users', { lazy: true, query: { per_page: 200 } })
const { data: companyUsersData } = useApi<{ id: number }[]>(`/companies/${props.companyId}/users`, { lazy: true })

const userItems = computed<SelectMenuItem[]>(() => {
  const all = Array.isArray(usersData.value?.data) ? usersData.value.data : []
  const attachedIds = new Set(Array.isArray(companyUsersData.value) ? companyUsersData.value.map(u => u.id) : [])
  return all
    .filter(u => !attachedIds.has(u.id))
    .map(u => ({
      label: `${u.name} — ${u.email}`,
      value: u.id
    }))
})

const hasAvailableUsers = computed(() => userItems.value.length > 0)

const createSchema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  email: z.string().email('E-mail inválido'),
  password: z.string().min(8, 'Mínimo 8 caracteres'),
  password_confirmation: z.string().min(8, 'Confirme a senha'),
  phone: z.string().optional()
}).refine(data => data.password === data.password_confirmation, {
  message: 'As senhas não conferem',
  path: ['password_confirmation']
})

type CreateSchema = z.output<typeof createSchema>

const createState = reactive<Partial<CreateSchema>>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: ''
})

async function onAttach() {
  if (!selectedUserId.value) return
  loading.value = true
  try {
    await post(`/companies/${props.companyId}/users`, { user_id: selectedUserId.value })
    toast.add({ title: 'Usuário vinculado', description: 'Usuário adicionado à empresa.', color: 'success' })
    open.value = false
    emit('added')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao vincular usuário.', color: 'error' })
  } finally {
    loading.value = false
  }
}

async function onCreate(event: FormSubmitEvent<CreateSchema>) {
  loading.value = true
  try {
    const userResponse = await post<{ id: number }>('/users', {
      ...event.data,
      role: 'company_user',
      is_active: true
    })
    await post(`/companies/${props.companyId}/users`, { user_id: userResponse.id })
    toast.add({ title: 'Usuário criado e vinculado', description: event.data.name, color: 'success' })
    open.value = false
    emit('added')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao criar usuário.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Adicionar Usuário"
    description="Vincule um usuário existente ou crie um novo."
  >
    <template #body>
      <div class="space-y-4">
        <div v-if="mode === 'select'" class="space-y-4">
          <UFormField label="Usuário" required>
            <USelectMenu
              v-model="selectedUserId"
              :items="userItems"
              placeholder="Selecione um usuário"
              class="w-full"
              value-key="value"
            />
          </UFormField>

          <div class="flex justify-end gap-3">
            <UButton
              label="Cancelar"
              color="neutral"
              variant="outline"
              @click="open = false"
            />
            <UButton
              label="Vincular"
              color="primary"
              :loading="loading"
              :disabled="!selectedUserId"
              @click="onAttach"
            />
          </div>
        </div>

        <UForm
          v-else
          ref="formRef"
          :schema="createSchema"
          :state="createState"
          @submit="onCreate"
        >
          <UFormField label="Nome" name="name" required>
            <UInput v-model="createState.name" placeholder="Nome completo" class="w-full" />
          </UFormField>

          <UFormField label="E-mail" name="email" required>
            <UInput
              v-model="createState.email"
              type="email"
              placeholder="email@exemplo.com"
              class="w-full"
            />
          </UFormField>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Senha" name="password" required>
              <UInput
                v-model="createState.password"
                type="password"
                placeholder="Mínimo 8 caracteres"
                class="w-full"
              />
            </UFormField>

            <UFormField label="Confirmar Senha" name="password_confirmation" required>
              <UInput
                v-model="createState.password_confirmation"
                type="password"
                placeholder="Repita a senha"
                class="w-full"
              />
            </UFormField>
          </div>

          <UFormField label="Telefone" name="phone">
            <UInput v-model="createState.phone" placeholder="(00) 00000-0000" class="w-full" />
          </UFormField>

          <div class="flex justify-end gap-3">
            <UButton
              label="Voltar"
              color="neutral"
              variant="outline"
              @click="mode = 'select'"
            />
            <UButton
              label="Criar e Vincular"
              color="primary"
              :loading="loading"
              type="submit"
            />
          </div>
        </UForm>
      </div>
    </template>

    <template #footer>
      <div v-if="mode === 'select' && !hasAvailableUsers" class="w-full">
        <USeparator class="mb-3" />
        <p class="text-xs text-muted text-center mb-3">
          Nenhum usuário disponível para vincular.
        </p>
        <UButton
          label="Criar Novo Usuário"
          icon="i-lucide-user-plus"
          color="primary"
          block
          @click="mode = 'create'"
        />
      </div>
    </template>
  </UModal>
</template>
