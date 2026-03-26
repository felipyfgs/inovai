<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Row } from '@tanstack/table-core'
import type { Office, PaginatedResponse } from '~/types'

const UBadge = resolveComponent('UBadge')
const UButton = resolveComponent('UButton')
const UDropdownMenu = resolveComponent('UDropdownMenu')

definePageMeta({ middleware: 'admin' })

const table = useTemplateRef('table')
const router = useRouter()

const search = ref('')
const typeFilter = ref('all')
const statusFilter = ref('all')

const queryParams = computed(() => ({
  search: search.value || undefined,
  type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
  status: statusFilter.value !== 'all' ? statusFilter.value : undefined
}))

const { data, status, refresh } = useApi<PaginatedResponse<Office>>('/admin/offices', {
  lazy: true,
  query: queryParams
})

const offices = computed(() => data.value?.data || [])

const editingOffice = ref<Office | null>(null)
const deletingOffice = ref<Office | null>(null)
const editOpen = ref(false)

const columnVisibility = ref()
const rowSelection = ref({})
const pagination = ref({ pageIndex: 0, pageSize: 15 })

const { put } = useApiMutation()

async function toggleActive(office: Office) {
  try {
    const payload: Record<string, unknown> = { is_active: !office.is_active }
    if (office.is_active) {
      payload.inactivation_reason = 'Desativação manual pelo administrador'
    } else {
      payload.inactivated_at = null
      payload.inactivation_reason = null
    }
    await put(`/admin/offices/${office.id}`, payload)
    useAppToast().success(office.is_active ? 'Escritório desativado' : 'Escritório reativado')
    refresh()
  } catch {
    useAppToast().error('Erro ao alterar status do escritório')
  }
}

function getRowItems(row: Row<Office>) {
  return [
    { type: 'label' as const, label: 'Ações' },
    {
      label: 'Ver',
      icon: 'i-lucide-eye',
      onSelect() {
        router.push(`/admin/escritorios/${row.original.id}`)
      }
    },
    {
      label: row.original.is_active ? 'Desativar' : 'Ativar',
      icon: row.original.is_active ? 'i-lucide-user-x' : 'i-lucide-user-check',
      onSelect() { toggleActive(row.original) }
    },
    { type: 'separator' as const },
    {
      label: 'Editar',
      icon: 'i-lucide-pencil',
      onSelect() {
        editingOffice.value = row.original
        editOpen.value = true
      }
    },
    { type: 'separator' as const },
    {
      label: 'Excluir',
      icon: 'i-lucide-trash',
      color: 'error' as const,
      onSelect() { deletingOffice.value = row.original }
    }
  ]
}

const columns: TableColumn<Office>[] = [
  {
    accessorKey: 'name',
    header: 'Nome',
    cell: ({ row }) => h('div', [
      h('p', { class: 'font-medium text-highlighted' }, row.original.name),
      h('p', { class: 'text-sm text-muted' }, row.original.cnpj || '—')
    ])
  },
  {
    accessorKey: 'type',
    header: 'Tipo',
    cell: ({ row }) => {
      const labels: Record<string, string> = { contador: 'Contador', direct: 'Direto' }
      const colors: Record<string, 'primary' | 'secondary'> = { contador: 'primary', direct: 'secondary' }
      return h(UBadge, { variant: 'subtle', color: colors[row.original.type] || 'primary' }, () => labels[row.original.type] || row.original.type)
    }
  },
  {
    accessorKey: 'companies_count',
    header: 'Empresas',
    cell: ({ row }) => h('span', { class: 'font-medium' }, row.original.companies_count ?? 0)
  },
  {
    id: 'plano',
    header: 'Plano',
    cell: ({ row }) => {
      const plan = row.original.subscription?.plan
      if (!plan) return h('span', { class: 'text-muted' }, '—')
      return h(UBadge, { variant: 'subtle', color: 'success' }, () => plan.name)
    }
  },
  {
    id: 'status_assinatura',
    header: 'Assinatura',
    cell: ({ row }) => {
      const sub = row.original.subscription
      if (!sub) return h('span', { class: 'text-muted' }, 'Sem plano')
      const colors: Record<string, 'success' | 'warning' | 'error'> = {
        active: 'success',
        trial: 'warning',
        cancelled: 'error',
        expired: 'error'
      }
      const labels: Record<string, string> = { active: 'Ativo', trial: 'Trial', cancelled: 'Cancelado', expired: 'Expirado' }
      return h(UBadge, { variant: 'subtle', color: colors[sub.status] || 'error' }, () => labels[sub.status] || sub.status)
    }
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
  <UDashboardPanel id="admin-escritorios">
    <template #header>
      <UDashboardNavbar title="Gestão de Escritórios">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <AdminContadoresAddModal @created="refresh()" />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Buscar por nome, CNPJ..."
        />
        <template #right>
          <div class="flex flex-wrap items-center gap-1.5">
            <USelect
              v-model="typeFilter"
              :items="[
                { label: 'Todos os tipos', value: 'all' },
                { label: 'Contadores', value: 'contador' },
                { label: 'Diretos', value: 'direct' }
              ]"
              class="min-w-36"
            />
            <USelect
              v-model="statusFilter"
              :items="[
                { label: 'Todos', value: 'all' },
                { label: 'Ativos', value: 'active' },
                { label: 'Inativos', value: 'inactive' }
              ]"
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
        :data="offices"
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

  <AdminContadoresEditModal
    v-if="editingOffice"
    v-model:open="editOpen"
    :office="editingOffice"
    @updated="() => { editingOffice = null; refresh() }"
  />

  <AdminContadoresDeleteModal
    v-if="deletingOffice"
    :office="deletingOffice"
    @deleted="() => { deletingOffice = null; refresh() }"
  />
</template>
