<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { AppUser, AuthUser, PaginatedResponse } from '~/types'
import { getPaginationRowModel } from '@tanstack/table-core'

definePageMeta({
  layout: 'default'
})

const { user } = useSanctumAuth<AuthUser>()
const isAdmin = computed(() => user.value?.roles?.some(r => r.name === 'admin') ?? false)

const toast = useToast()
const table = useTemplateRef('table')

const search = ref('')
const roleFilter = ref('all')

const queryParams = computed(() => ({
  search: search.value || undefined,
  role: roleFilter.value !== 'all' ? roleFilter.value : undefined,
  per_page: 100
}))

const { data, status, refresh } = useApi<PaginatedResponse<AppUser>>('/users', {
  lazy: true,
  query: queryParams
})

const users = computed(() => data.value?.data ?? [])

const editingUser = ref<AppUser | null>(null)
const deletingUser = ref<AppUser | null>(null)

const pagination = ref({ pageIndex: 0, pageSize: 20 })

const roleConfig: Record<string, { label: string, color: 'success' | 'warning' | 'neutral' }> = {
  admin: { label: 'Admin', color: 'success' },
  office_user: { label: 'Contador', color: 'warning' },
  company_user: { label: 'Empresário', color: 'neutral' }
}

const columns: TableColumn<AppUser>[] = [
  { accessorKey: 'name', header: 'Nome' },
  { accessorKey: 'email', header: 'E-mail' },
  {
    accessorKey: 'role',
    header: 'Perfil',
    cell: ({ row }) => {
      const roleName = row.original.roles?.[0]?.name || 'company_user'
      const config = roleConfig[roleName] || { label: roleName, color: 'neutral' as const }
      return h('span', { class: `text-${config.color}` }, config.label)
    }
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    cell: ({ row }) =>
      h('span', {
        class: row.original.is_active ? 'text-success' : 'text-error'
      }, row.original.is_active ? 'Ativo' : 'Inativo')
  },
  {
    id: 'actions',
    header: ''
  }
]

function getRowItems(row: any) {
  return [
    {
      label: 'Editar',
      icon: 'i-lucide-pencil',
      onSelect() { editingUser.value = row.original }
    },
    { type: 'separator' as const },
    {
      label: 'Excluir',
      icon: 'i-lucide-trash',
      color: 'error' as const,
      onSelect() { deletingUser.value = row.original }
    }
  ]
}

async function confirmDelete() {
  if (!deletingUser.value) return

  try {
    const { del } = useApiMutation()
    await del(`/users/${deletingUser.value!.id}`)
    toast.add({ title: 'Usuário removido', color: 'success' })
    refresh()
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao remover.', color: 'error' })
  } finally {
    deletingUser.value = null
  }
}

const roleOptions = computed(() => {
  const base = [
    { label: 'Todos', value: 'all' },
    { label: 'Empresário', value: 'company_user' }
  ]
  if (isAdmin.value) {
    base.push(
      { label: 'Contador', value: 'office_user' },
      { label: 'Admin', value: 'admin' }
    )
  }
  return base
})
</script>

<template>
  <UPageHeader
    title="Usuários"
    description="Gerencie os usuários do sistema."
  >
    <template #leading>
      <UDashboardSidebarCollapse />
    </template>
  </UPageHeader>

  <div class="mt-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
      <div class="flex items-center gap-2">
        <UInput
          v-model="search"
          icon="i-lucide-search"
          placeholder="Buscar usuário..."
          class="w-64"
        />
        <USelect
          v-model="roleFilter"
          :items="roleOptions"
          class="w-40"
        />
      </div>

      <settingsAddUserModal @created="refresh" />
    </div>

    <UTable
      ref="table"
      v-model:pagination="pagination"
      :data="users"
      :columns="columns"
      :loading="status === 'pending'"
      :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
      class="bg-elevated rounded-lg"
    >
      <template #actions-cell="{ row }">
        <UDropdownMenu :items="getRowItems(row)" :content="{ align: 'end' }">
          <UButton
            icon="i-lucide-ellipsis-vertical"
            color="neutral"
            variant="ghost"
          />
        </UDropdownMenu>
      </template>
    </UTable>

    <div class="text-sm text-muted mt-4">
      {{ users.length }} usuário(s)
    </div>
  </div>

  <settingsEditUserModal
    :user="editingUser"
    @updated="refresh(); editingUser = null"
  />

  <UModal v-model:open="!!deletingUser">
    <template #content>
      <UModalHeader title="Confirmar exclusão" />
      <div class="p-4">
        <p>Tem certeza que deseja excluir <strong>{{ deletingUser?.name }}</strong>?</p>
        <div class="flex justify-end gap-2 mt-4">
          <UButton label="Cancelar" variant="ghost" @click="deletingUser = null" />
          <UButton label="Excluir" color="error" @click="confirmDelete" />
        </div>
      </div>
    </template>
  </UModal>
</template>
