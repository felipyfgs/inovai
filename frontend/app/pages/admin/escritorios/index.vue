<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Row } from '@tanstack/table-core'
import { UBadge, UButton, UDropdownMenu } from '#components'
import type { Office, PaginatedResponse } from '~/types'

definePageMeta({ middleware: 'admin' })

const toast = useToast()
const { handleError } = useApiError()
const table = useTemplateRef('table')

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
const router = useRouter()

const pagination = ref({ pageIndex: 0, pageSize: 15 })

function getRowItems(row: Row<Office>) {
  return [
    { type: 'label' as const, label: 'Ações' },
    {
      label: 'Ver',
      icon: 'i-lucide-eye',
      onSelect() {
        console.log('[Office list] Navigating to office details, id:', row.original.id)
        router.push(`/admin/escritorios/${row.original.id}`)
          .then(() => console.log('[Office list] Navigation successful'))
          .catch(err => console.error('[Office list] Navigation error:', err))
      }
    },
    { type: 'separator' as const },
    {
      label: 'Editar',
      icon: 'i-lucide-pencil',
      onSelect() { editingOffice.value = row.original }
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

async function handleDelete() {
  if (!deletingOffice.value) return
  const { del } = useApiMutation()
  try {
    await del(`/admin/offices/${deletingOffice.value.id}`)
    toast.add({ title: 'Removido com sucesso', color: 'success' })
    deletingOffice.value = null
    refresh()
  } catch (e: unknown) {
    handleError(e, 'Erro ao remover')
  }
}
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
    </template>

    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Buscar por nome, CNPJ..."
        />
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
        </div>
      </div>

      <UTable
        ref="table"
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
          {{ offices.length }} registro(s)
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
    :office="editingOffice"
    @updated="() => { editingOffice = null; refresh() }"
  />

  <UModal
    :open="!!deletingOffice"
    title="Confirmar exclusão"
    description="Esta ação não pode ser desfeita."
    @update:open="(v) => { if (!v) deletingOffice = null }"
  >
    <template #body>
      <p>
        Deseja excluir <strong>{{ deletingOffice?.name }}</strong>? Esta ação não pode ser desfeita.
      </p>
    </template>
    <template #footer="{ close }">
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="close(); deletingOffice = null"
      />
      <UButton label="Excluir" color="error" @click="handleDelete" />
    </template>
  </UModal>
</template>
