<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { Row } from '@tanstack/table-core'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { AppUser, AuthUser, PaginatedResponse } from '~/types'

import { UAvatar, UButton, UBadge, UDropdownMenu } from '#components'

function getInitials(name: string) {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(w => w[0].toUpperCase())
    .join('')
}

const { user } = useSanctumAuth<AuthUser>()
const isAdmin = computed(() => user.value?.roles?.some(r => r.name === 'admin') ?? false)

const toast = useToast()
const { handleError } = useApiError()
const table = useTemplateRef('table')

const columnFilters = ref([{
  id: 'name',
  value: ''
}])
const columnVisibility = ref()

const roleFilter = ref('all')
const searchInput = ref('')

const queryParams = computed(() => ({
  search: searchInput.value || undefined,
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

const roleConfig: Record<string, { label: string, color: 'success' | 'warning' | 'neutral' }> = {
  admin: { label: 'Admin', color: 'success' },
  office_user: { label: 'Contador', color: 'warning' },
  accountant: { label: 'Contador', color: 'warning' },
  company_user: { label: 'Empresário', color: 'neutral' }
}

function getRowItems(row: Row<AppUser>) {
  return [
    {
      type: 'label' as const,
      label: 'Ações'
    },
    {
      label: 'Editar usuário',
      icon: 'i-lucide-pencil',
      onSelect() {
        editingUser.value = row.original
      }
    },
    {
      type: 'separator' as const
    },
    {
      label: 'Copiar e-mail',
      icon: 'i-lucide-copy',
      onSelect() {
        navigator.clipboard.writeText(row.original.email)
        toast.add({
          title: 'E-mail copiado',
          description: row.original.email
        })
      }
    },
    {
      type: 'separator' as const
    },
    {
      label: 'Excluir usuário',
      icon: 'i-lucide-trash',
      color: 'error' as const,
      onSelect() {
        deletingUser.value = row.original
      }
    }
  ]
}

const columns: TableColumn<AppUser>[] = [
  {
    accessorKey: 'name',
    header: 'Nome',
    cell: ({ row }) => {
      return h('div', { class: 'flex items-center gap-3' }, [
        h(UAvatar, {
          text: getInitials(row.original.name),
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
    header: 'Perfil',
    filterFn: 'equals',
    cell: ({ row }) => {
      const roleName = row.original.roles?.[0]?.name || 'company_user'
      const config = roleConfig[roleName] || { label: roleName, color: 'neutral' as const }
      return h(UBadge, { class: 'capitalize', variant: 'subtle', color: config.color }, () => config.label)
    }
  },
  {
    accessorKey: 'phone',
    header: 'Telefone',
    cell: ({ row }) => row.original.phone || '—'
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    cell: ({ row }) => {
      const color = row.original.is_active ? 'success' as const : 'error' as const
      return h(UBadge, { variant: 'subtle', color }, () => row.original.is_active ? 'Ativo' : 'Inativo')
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

watch(() => roleFilter.value, (newVal) => {
  if (!table?.value?.tableApi) return
  const col = table.value.tableApi.getColumn('role')
  if (!col) return
  col.setFilterValue(newVal === 'all' ? undefined : newVal)
})

const pagination = ref({
  pageIndex: 0,
  pageSize: 10
})

const roleOptions = computed(() => {
  const base = [
    { label: 'Todos', value: 'all' },
    { label: 'Empresário', value: 'company_user' }
  ]
  if (isAdmin.value) {
    base.push(
      { label: 'Contador', value: 'accountant' },
      { label: 'Admin', value: 'admin' }
    )
  }
  return base
})

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
</script>

<template>
  <UDashboardPanel id="usuarios">
    <template #header>
      <UDashboardNavbar title="Usuários">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UsuariosAddModal @created="refresh()" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="searchInput"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Buscar usuário..."
        />

        <div class="flex flex-wrap items-center gap-1.5">
          <USelect
            v-model="roleFilter"
            :items="roleOptions"
            :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
            placeholder="Perfil"
            class="min-w-28"
          />
          <UDropdownMenu
            :items="
              table?.tableApi
                ?.getAllColumns()
                .filter((column: any) => column.getCanHide())
                .map((column: any) => ({
                  label: upperFirst(column.id),
                  type: 'checkbox' as const,
                  checked: column.getIsVisible(),
                  onUpdateChecked(checked: boolean) {
                    table?.tableApi?.getColumn(column.id)?.toggleVisibility(!!checked)
                  },
                  onSelect(e?: Event) {
                    e?.preventDefault()
                  }
                }))
            "
            :content="{ align: 'end' }"
          >
            <UButton
              label="Exibir"
              color="neutral"
              variant="outline"
              trailing-icon="i-lucide-settings-2"
            />
          </UDropdownMenu>
        </div>
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
        <div class="flex items-center gap-1.5">
          <UPagination
            :default-page="(table?.tableApi?.getState().pagination.pageIndex || 0) + 1"
            :items-per-page="table?.tableApi?.getState().pagination.pageSize"
            :total="table?.tableApi?.getFilteredRowModel().rows.length"
            @update:page="(p: number) => table?.tableApi?.setPageIndex(p - 1)"
          />
        </div>
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
      <p>Tem certeza que deseja excluir <strong>{{ deletingUser?.name }}</strong>?</p>
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
