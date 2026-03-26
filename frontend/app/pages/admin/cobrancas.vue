<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Invoice, PaginatedResponse } from '~/types'

const UBadge = resolveComponent('UBadge')
const UButton = resolveComponent('UButton')
const UDropdownMenu = resolveComponent('UDropdownMenu')

definePageMeta({ middleware: 'admin' })

const table = useTemplateRef('table')

const statusFilter = ref('all')
const columnVisibility = ref()
const rowSelection = ref({})
const pagination = ref({ pageIndex: 0, pageSize: 15 })

const queryParams = computed(() => ({
  status: statusFilter.value !== 'all' ? statusFilter.value : undefined
}))

const { data, status, refresh } = useApi<PaginatedResponse<Invoice>>('/admin/invoices', {
  lazy: true,
  query: queryParams
})

const { data: dashboard, refresh: refreshDashboard } = useApi<{
  mrr: number
  pending: number
  overdue: number
  inadimplentes: number
}>('/admin/invoices/dashboard', { lazy: true })

const invoices = computed(() => data.value?.data || [])

const statusConfig: Record<string, { label: string, color: 'success' | 'warning' | 'error' | 'neutral' }> = {
  paid: { label: 'Pago', color: 'success' },
  pending: { label: 'Pendente', color: 'warning' },
  overdue: { label: 'Vencido', color: 'error' },
  cancelled: { label: 'Cancelado', color: 'neutral' }
}

const { put } = useApiMutation()

async function markAsPaid(invoice: Invoice) {
  try {
    await put(`/admin/invoices/${invoice.id}`, { status: 'paid' })
    useAppToast().success('Fatura marcada como paga')
    refresh()
    refreshDashboard()
  } catch {
    useAppToast().error('Erro ao atualizar fatura')
  }
}

const columns: TableColumn<Invoice>[] = [
  {
    id: 'cliente',
    header: 'Cliente',
    cell: ({ row }) => h('div', [
      h('p', { class: 'font-medium text-highlighted' }, row.original.office?.name || '—'),
      h('p', { class: 'text-sm text-muted' }, row.original.reference || '')
    ])
  },
  {
    id: 'plano',
    header: 'Plano',
    cell: ({ row }) => row.original.plan?.name || h('span', { class: 'text-muted' }, '—')
  },
  {
    accessorKey: 'amount',
    header: 'Valor',
    cell: ({ row }) => h('span', { class: 'font-medium' }, formatCurrency(row.original.amount))
  },
  {
    accessorKey: 'due_at',
    header: 'Vencimento',
    cell: ({ row }) => new Date(row.original.due_at).toLocaleDateString('pt-BR')
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const cfg = statusConfig[row.original.status] || { label: row.original.status, color: 'neutral' as const }
      return h(UBadge, { variant: 'subtle', color: cfg.color }, () => cfg.label)
    }
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      const items = [
        { type: 'label' as const, label: 'Ações' },
        ...(row.original.status === 'pending' || row.original.status === 'overdue'
          ? [{
              label: 'Marcar como pago',
              icon: 'i-lucide-check-circle',
              onSelect: () => markAsPaid(row.original)
            }]
          : [])
      ]
      return h('div', { class: 'text-right' }, h(UDropdownMenu, {
        content: { align: 'end' },
        items
      }, () => h(UButton, { icon: 'i-lucide-ellipsis-vertical', color: 'neutral', variant: 'ghost', class: 'ml-auto' })))
    }
  }
]
</script>

<template>
  <UDashboardPanel id="admin-cobrancas">
    <template #header>
      <UDashboardNavbar title="Cobranças">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <AdminCobrancasGenerateModal @generated="refresh(); refreshDashboard()" />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <template #right>
          <div class="flex flex-wrap items-center gap-1.5">
            <USelect
              v-model="statusFilter"
              :items="[
                { label: 'Todos', value: 'all' },
                { label: 'Pendentes', value: 'pending' },
                { label: 'Pagos', value: 'paid' },
                { label: 'Vencidos', value: 'overdue' },
                { label: 'Cancelados', value: 'cancelled' }
              ]"
              class="min-w-36"
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
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <UCard>
          <p class="text-sm text-muted">
            Receita do Mês
          </p>
          <p class="text-xl font-bold text-success mt-1">
            {{ formatCurrency(dashboard?.mrr ?? 0) }}
          </p>
        </UCard>
        <UCard>
          <p class="text-sm text-muted">
            A Receber
          </p>
          <p class="text-xl font-bold text-warning mt-1">
            {{ formatCurrency(dashboard?.pending ?? 0) }}
          </p>
        </UCard>
        <UCard>
          <p class="text-sm text-muted">
            Vencido
          </p>
          <p class="text-xl font-bold text-error mt-1">
            {{ formatCurrency(dashboard?.overdue ?? 0) }}
          </p>
        </UCard>
        <UCard>
          <p class="text-sm text-muted">
            Inadimplentes
          </p>
          <p class="text-xl font-bold text-error mt-1">
            {{ dashboard?.inadimplentes ?? 0 }}
          </p>
        </UCard>
      </div>

      <UTable
        ref="table"
        v-model:column-visibility="columnVisibility"
        v-model:row-selection="rowSelection"
        v-model:pagination="pagination"
        :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
        class="shrink-0"
        :data="invoices"
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
</template>
