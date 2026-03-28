<script setup lang="ts">
import type { AppUser } from '~/types'

import { UButton, UBadge, UDropdownMenu } from '#components'

const props = defineProps<{
  companyId: number
}>()

const toast = useToast()
const { del } = useApiMutation()
const { extractMessage } = useApiError()

const { data: users, status, refresh } = useApi<AppUser[]>(`/companies/${props.companyId}/users`, { lazy: true })

const resetUser = ref<AppUser | null>(null)
const editUser = ref<AppUser | null>(null)
const addingUser = ref(false)

const actions = (user: AppUser) => [
  {
    type: 'label' as const,
    label: 'Ações'
  },
  {
    label: 'Editar',
    icon: 'i-lucide-pencil',
    onSelect() {
      editUser.value = user
    }
  },
  {
    label: 'Redefinir Senha',
    icon: 'i-lucide-key-round',
    onSelect() {
      resetUser.value = user
    }
  },
  {
    type: 'separator' as const
  },
  {
    label: 'Desvincular',
    icon: 'i-lucide-user-x',
    color: 'error' as const,
    onSelect() {
      handleDetach(user)
    }
  }
]

async function handleDetach(user: AppUser) {
  try {
    await del(`/companies/${props.companyId}/users/${user.id}`)
    toast.add({ title: 'Usuário desvinculado', description: user.name, color: 'success' })
    refresh()
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error), color: 'error' })
  }
}

function onPasswordReset() {
  resetUser.value = null
}

function onUserUpdated() {
  editUser.value = null
  refresh()
}

function onUserAdded() {
  addingUser.value = false
  refresh()
}
</script>

<template>
  <div>
    <div v-if="status === 'pending'" class="flex items-center justify-center h-24">
      <UIcon name="i-lucide-loader-2" class="animate-spin size-6 text-muted" />
    </div>

    <div v-else-if="!users || users.length === 0" class="text-center py-8">
      <UIcon name="i-lucide-users" class="size-10 text-muted mb-3" />
      <p class="text-sm text-muted">
        Nenhum usuário vinculado a esta empresa.
      </p>
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="user in users"
        :key="user.id"
        class="flex items-center gap-3 p-3 rounded-lg border border-default"
      >
        <div class="flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary text-sm font-semibold shrink-0">
          {{ user.name?.charAt(0)?.toUpperCase() }}
        </div>

        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium truncate">
            {{ user.name }}
          </p>
          <p class="text-xs text-muted truncate">
            {{ user.email }}
          </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <UBadge
            v-if="user.is_active !== undefined"
            :color="user.is_active ? 'success' : 'error'"
            variant="subtle"
            size="xs"
          >
            {{ user.is_active ? 'Ativo' : 'Bloqueado' }}
          </UBadge>

          <UDropdownMenu
            :items="actions(user)"
            :content="{ align: 'end' }"
          >
            <UButton
              icon="i-lucide-ellipsis-vertical"
              color="neutral"
              variant="ghost"
              size="xs"
            />
          </UDropdownMenu>
        </div>
      </div>

      <UButton
        label="Adicionar Usuário"
        icon="i-lucide-user-plus"
        color="neutral"
        variant="outline"
        class="w-full mt-3"
        @click="addingUser = true"
      />
    </div>

    <EmpresasResetPasswordModal
      v-if="resetUser"
      :user="resetUser"
      :company-id="companyId"
      @updated="onPasswordReset"
    />

    <EmpresasEditUserModal
      v-if="editUser"
      :user="editUser"
      @updated="onUserUpdated"
    />

    <EmpresasAddUserModal
      v-if="addingUser"
      :company-id="companyId"
      @added="onUserAdded"
    />
  </div>
</template>
