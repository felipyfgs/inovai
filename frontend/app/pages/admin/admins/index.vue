<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Row } from '@tanstack/table-core'
import type { AdminUser, PaginatedResponse } from '~/types'

const UBadge = resolveComponent('UBadge')
const UButton = resolveComponent('UButton')
const UDropdownMenu = resolveComponent('UDropdownMenu')

definePageMeta({ middleware: 'admin' })

const table = useTemplateRef('table')
const { user: currentUser } = useSanctumAuth<{ id: number }>()

const search = ref('')
const columnVisibility = ref()
const rowSelection = ref({})
const pagination = ref({ pageIndex: 0, pageSize: 15 })

const queryParams = computed(() => ({
  search: search.value || undefined
}))

const { data, status, refresh } = useApi<PaginatedResponse<AdminUser>>('/admin/admins', {
  lazy: true,
  query: queryParams
})

const admins = computed(() => data.value?.data || [])

const editingAdmin = ref<AdminUser | null>(null)
const deletingAdmin = ref<AdminUser | null>(null)

const { put } = useApiMutation()

async function toggleActive(admin: AdminUser) {
  try {
    await put(`/admin/admins/${admin.id}`, { is_active: !admin.is_active })
    useAppToast().success(admin.is_active ? 'Administrador desativado' : 'Administrador ativado')
    refresh()
  } catch {
    useAppToast().error('Erro ao alterar status')
  }
}

function getRowItems(row: Row<AdminUser>) {
  const isAdmin = row.original.id === currentUser.value?.id
  return [
    { type: 'label' as const, label: 'Ações' },
    {
      label: row.original.is_active ? 'Desativar' : 'Ativar',
      icon: row.original.is_active ? 'i-lucide-user-x' : 'i-lucide-user-check',
      disabled: isAdmin,
      onSelect() { toggleActive(row.original) }
    },
    { type: 'separator' as const },
    {
      label: 'Editar',
      icon: 'i-lucide-pencil',
      disabled: isAdmin,
      onSelect() { editingAdmin.value = row.original }
    },
    { type: 'separator' as const },
    {
      label: 'Excluir',
      icon: 'i-lucide-trash',
      color: 'error' as const,
      disabled: isAdmin,
      onSelect() { deletingAdmin.value = row.original }
    }
  ]
}

const columns: TableColumn<AdminUser>[] = [
  {
    accessorKey: 'name',
    header: 'Nome',
    cell: ({ row }) => h('div', { class: 'flex items-center gap-3' }, [
      h('div', { class: 'inline-flex items-center justify-center shrink-0 select-none rounded-full align-middle bg-elevated size-8 text-xs' }, [
        h('span', { class: 'font-medium leading-none text-muted truncate' }, row.original.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase())
      ]),
      h('div', [
        h('p', { class: 'font-medium text-highlighted' }, row.original.name),
        h('p', { class: 'text-sm text-muted' }, row.original.email)
      ])
    ])
  },
  {
    accessorKey: 'phone',
    header: 'Telefone',
    cell: ({ row }) => h('span', row.original.phone || '—')
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    cell: ({ row }) => h(UBadge, { variant: 'subtle', color: row.original.is_active ? 'success' : 'error' }, () => row.original.is_active ? 'Ativo' : 'Inativo')
  },
  {
    id: 'actions',
    cell: ({ row }) => h('div', { class: 'text-right' }, h(UDropdownMenu, {
      content: { align: 'end' },
      items: getRowItems(row)
    }, () => h(UButton, { icon: 'i-lucide-ellipsis-vertical', color: 'neutral', variant: 'ghost', class: 'ml-auto' })))
  }
]
</script>

<template>
  <UDashboardPanel id="admin-admins">
    <template #header>
      <UDashboardNavbar title="Administradores">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <AdminAdminsAddModal @created="refresh()" />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Buscar por nome, e-mail..."
        />
        <template #right>
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
        </template>
      </UDashboardToolbar>
    </template>

    <template #body>
      <UTable
        ref="table"
        v-model:column-visibility="columnVisibility"
        v-model:row-selection="rowSelection"
        v-model:pagination="pagination"
        :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
        class="shrink-0"
        :data="admins"
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

      <div class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-auto">
        <div class="text-sm text-muted">
          {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} de
          {{ table?.tableApi?.getFilteredRowModel().rows.length || 0 }} registro(s) selecionado(s).
        </div>
        <UPagination
          :default-page="(table?.tableApi?.getState().pagination.pageIndex || 0) + 1"
          :items-per-page="table?.tableApi?.getState().pagination.pageSize"
          :total="table?.tableApi?.getFilteredRowModel().rows.length"
          @update:page="(p: number) => table?.tableApi?.setPageIndex(p - 1)"
        />
      </div>
    </template>
  </UDashboardPanel>

  <AdminAdminsEditModal
    v-if="editingAdmin"
    :admin="editingAdmin"
    @updated="() => { editingAdmin = null; refresh() }"
  />

  <AdminAdminsDeleteModal
    v-if="deletingAdmin"
    :admin="deletingAdmin"
    @deleted="() => { deletingAdmin = null; refresh() }"
  />
</template>
