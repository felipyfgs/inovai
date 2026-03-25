<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'
import { UBadge, UButton, UDropdownMenu } from '#components'
import type { Invoice, PaginatedResponse } from '~/types'

definePageMeta({ middleware: 'admin' })

const toast = useToast()
const { handleError } = useApiError()
const table = useTemplateRef('table')

const statusFilter = ref('all')
const pagination = ref({ pageIndex: 0, pageSize: 15 })
const showGenerateModal = ref(false)
const generatingMonthly = ref(false)
const generateForm = reactive({ reference: '', due_at: '' })

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
    toast.add({ title: 'Fatura marcada como paga', color: 'success' })
    refresh()
    refreshDashboard()
  } catch {
    toast.add({ title: 'Erro ao atualizar fatura', color: 'error' })
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

async function handleGenerateMonthly() {
  generatingMonthly.value = true
  const { post } = useApiMutation()
  try {
    const res = await post<{ message: string, created: number }>('/admin/invoices/generate-monthly', generateForm)
    toast.add({ title: res.message, color: 'success' })
    showGenerateModal.value = false
    refresh()
    refreshDashboard()
  } catch (e: unknown) {
    handleError(e, 'Erro ao gerar faturas')
  } finally {
    generatingMonthly.value = false
  }
}
</script>

<template>
  <UDashboardPanel id="admin-cobrancas">
    <template #header>
      <UDashboardNavbar title="Cobranças">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            label="Gerar Mensalidades"
            icon="i-lucide-zap"
            @click="showGenerateModal = true"
          />
        </template>
      </UDashboardNavbar>
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

      <div class="flex flex-wrap items-center gap-1.5 mb-4">
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
      </div>

      <UTable
        ref="table"
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

  <UModal
    v-model:open="showGenerateModal"
    title="Gerar Mensalidades"
    description="Gera faturas mensais para todos os clientes ativos com assinatura."
    :ui="{ footer: 'justify-end' }"
  >
    <template #body>
      <div class="space-y-4">
        <UFormField label="Referência (ex: 2026-03)" name="reference">
          <UInput v-model="generateForm.reference" placeholder="2026-03" class="w-full" />
        </UFormField>
        <UFormField label="Data de Vencimento" name="due_at">
          <UInput v-model="generateForm.due_at" type="date" class="w-full" />
        </UFormField>
      </div>
    </template>
    <template #footer="{ close }">
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="close"
      />
      <UButton
        label="Gerar Faturas"
        icon="i-lucide-zap"
        :loading="generatingMonthly"
        @click="handleGenerateMonthly"
      />
    </template>
  </UModal>
</template>
