<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { Row } from '@tanstack/table-core'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { AppUser, PaginatedResponse } from '~/types'

import { UAvatar, UBadge, UButton, UDropdownMenu } from '#components'

definePageMeta({ middleware: 'escritorio' })

const { currentOffice } = useCurrentOffice()
const toast = useToast()
const { handleError } = useApiError()
const table = useTemplateRef('table')

const searchInput = ref('')
const columnFilters = ref([{ id: 'name', value: '' }])
const columnVisibility = ref()
const pagination = ref({ pageIndex: 0, pageSize: 20 })

const queryParams = computed(() => ({
  search: searchInput.value || undefined,
  office_id: currentOffice.value?.id,
  per_page: pagination.value.pageSize
}))

const { data, status, refresh } = useApi<PaginatedResponse<AppUser>>('/users', {
  lazy: true,
  query: queryParams
})

const users = computed(() => data.value?.data ?? [])

const editingUser = ref<AppUser | null>(null)
const deletingUser = ref<AppUser | null>(null)

const roleConfig: Record<string, { label: string, color: 'success' | 'warning' | 'neutral' }> = {
  accountant: { label: 'Contador', color: 'warning' },
  office_user: { label: 'Contador', color: 'warning' },
  company_user: { label: 'Empresário', color: 'neutral' },
  admin: { label: 'Admin', color: 'success' }
}

function getRowItems(row: Row<AppUser>) {
  const items = []

  if (row.original.is_active !== false) {
    items.push({
      label: 'Bloquear',
      icon: 'i-lucide-ban',
      color: 'warning' as const,
      onSelect() { toggleUserActive(row.original) }
    })
  } else {
    items.push({
      label: 'Desbloquear',
      icon: 'i-lucide-check-circle',
      color: 'success' as const,
      onSelect() { toggleUserActive(row.original) }
    })
  }

  items.push({ type: 'separator' as const, label: '' })

  items.push({
    label: 'Editar',
    icon: 'i-lucide-pencil',
    onSelect() { editingUser.value = row.original }
  })

  items.push({ type: 'separator' as const, label: '' })

  items.push({
    label: 'Excluir',
    icon: 'i-lucide-trash',
    color: 'error' as const,
    onSelect() { deletingUser.value = row.original }
  })

  return [items]
}

async function toggleUserActive(user: AppUser) {
  const { patch } = useApiMutation()
  try {
    const response = await patch<{ message: string }>(`/users/${user.id}/toggle-active`)
    toast.add({ title: response.message, color: 'success' })
    refresh()
  } catch (e: unknown) {
    handleError(e, 'Erro ao alterar status')
  }
}

async function confirmDelete() {
  if (!deletingUser.value) return
  try {
    const { del } = useApiMutation()
    await del(`/users/${deletingUser.value.id}`)
    toast.add({ title: 'Usuário removido', color: 'success' })
    refresh()
  } catch (e: unknown) {
    handleError(e, 'Erro ao remover usuário')
  } finally {
    deletingUser.value = null
  }
}

const columns: TableColumn<AppUser>[] = [
  {
    accessorKey: 'name',
    header: 'Nome',
    cell: ({ row }) => {
      return h('div', { class: 'flex items-center gap-3' }, [
        h(UAvatar, {
          text: row.original.name.charAt(0).toUpperCase(),
          alt: row.original.name,
          size: 'md'
        }),
        h('div', undefined, [
          h('p', { class: 'font-medium text-highlighted' }, row.original.name),
          h('p', { class: 'text-sm text-muted' }, row.original.email)
        ])
      ])
    }
  },
  {
    accessorKey: 'role',
    header: 'Cargo',
    cell: ({ row }) => {
      const roleName = row.original.roles?.[0]?.name || 'company_user'
      const config = roleConfig[roleName] || { label: roleName, color: 'neutral' as const }
      return h(UBadge, { class: 'capitalize', variant: 'subtle', color: config.color }, () => config.label)
    }
  },
  {
    accessorKey: 'phone',
    header: 'Telefone',
    cell: ({ row }) => row.original.phone || '-'
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    cell: ({ row }) => {
      const color = row.original.is_active !== false ? 'success' as const : 'error' as const
      return h(UBadge, { variant: 'subtle', color }, () => row.original.is_active !== false ? 'Ativo' : 'Bloqueado')
    }
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'text-right' },
        h(
          UDropdownMenu,
          {
            content: { align: 'end' },
            items: getRowItems(row)
          },
          () =>
            h(UButton, {
              icon: 'i-lucide-ellipsis-vertical',
              color: 'neutral',
              variant: 'ghost',
              class: 'ml-auto'
            })
        )
      )
    }
  }
]
</script>

<template>
  <UDashboardPanel id="escritorio-equipe">
    <template #header>
      <UDashboardNavbar title="Equipe">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UsuariosAddModal :office-id="currentOffice?.id" @created="refresh()" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="searchInput"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Buscar membro..."
        />
      </div>

      <UTable
        ref="table"
        v-model:column-filters="columnFilters"
        v-model:column-visibility="columnVisibility"
        v-model:pagination="pagination"
        :pagination-options="{
          getPaginationRowModel: getPaginationRowModel()
        }"
        class="shrink-0"
        :data="users"
        :columns="columns"
        :loading="status === 'pending'"
        :ui="{
          base: 'table-fixed border-separate border-spacing-0',
          thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
          tbody: '[&>tr]:last:[&>td]:border-b-0',
          th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
          td: 'border-b border-default',
          separator: 'h-0'
        }"
      />

      <div class="flex items-center justify-end gap-3 border-t border-default pt-4 mt-auto">
        <UPagination
          :default-page="(table?.tableApi?.getState().pagination.pageIndex || 0) + 1"
          :items-per-page="table?.tableApi?.getState().pagination.pageSize"
          :total="table?.tableApi?.getFilteredRowModel().rows.length"
          @update:page="(p: number) => table?.tableApi?.setPageIndex(p - 1)"
        />
      </div>
    </template>
  </UDashboardPanel>

  <UsuariosEditModal
    v-if="editingUser"
    :user="editingUser"
    @updated="() => { editingUser = null; refresh() }"
  />

  <UModal
    :open="!!deletingUser"
    title="Confirmar exclusão"
    description="Esta ação não pode ser desfeita."
    :ui="{ footer: 'justify-end' }"
    @update:open="(v: boolean) => !v && (deletingUser = null)"
  >
    <template #body>
      <p>
        Tem certeza que deseja excluir <strong>{{ deletingUser?.name }}</strong>?
      </p>
    </template>

    <template #footer>
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="deletingUser = null"
      />
      <UButton
        label="Excluir"
        color="error"
        @click="confirmDelete"
      />
    </template>
  </UModal>
</template>
