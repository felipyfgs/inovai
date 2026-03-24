<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Row } from '@tanstack/table-core'
import type { Orcamento, PaginatedResponse } from '~/types'
import { formatCurrency } from '~/utils'

import { UButton, UBadge, UDropdownMenu, UCheckbox } from '#components'

const toast = useToast()
const table = useTemplateRef('table')
const { currentCompany } = useCurrentCompany()

const columnFilters = ref([])
const columnVisibility = ref()
const rowSelection = ref({})

const { data, status, refresh } = useApi<PaginatedResponse<Orcamento>>('/orcamentos', {
  lazy: true,
  watch: [() => currentCompany.value?.id]
})

const orcamentos = computed(() => data.value?.data || [])

const deletingOrcamento = ref<Orcamento | null>(null)
const { post: apiPost } = useApiMutation()

async function convertToPedido(orcamento: Orcamento) {
  try {
    await apiPost(`/orcamentos/${orcamento.id}/converter`)
    toast.add({ title: 'Convertido', description: `Orçamento #${orcamento.numero} convertido em pedido.`, color: 'success' })
    refresh()
  } catch (e: any) {
    toast.add({ title: 'Erro', description: e?.response?._data?.message || 'Erro ao converter.', color: 'error' })
  }
}

function getRowItems(row: Row<Orcamento>) {
  return [
    {
      type: 'label' as const,
      label: 'Ações'
    },
    {
      label: 'Converter em pedido',
      icon: 'i-lucide-arrow-right-circle',
      disabled: row.original.status === 'convertido',
      onSelect() {
        convertToPedido(row.original)
      }
    },
    {
      type: 'separator' as const
    },
    {
      label: 'Excluir orçamento',
      icon: 'i-lucide-trash',
      color: 'error' as const,
      onSelect() {
        deletingOrcamento.value = row.original
      }
    }
  ]
}

const columns: TableColumn<Orcamento>[] = [
  {
    id: 'select',
    header: ({ table }) =>
      h(UCheckbox, {
        'modelValue': table.getIsSomePageRowsSelected()
          ? 'indeterminate'
          : table.getIsAllPageRowsSelected(),
        'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
          table.toggleAllPageRowsSelected(!!value),
        'ariaLabel': 'Select all'
      }),
    cell: ({ row }) =>
      h(UCheckbox, {
        'modelValue': row.getIsSelected(),
        'onUpdate:modelValue': (value: boolean | 'indeterminate') => row.toggleSelected(!!value),
        'ariaLabel': 'Select row'
      })
  },
  {
    accessorKey: 'numero',
    header: 'Nº'
  },
  {
    accessorKey: 'data',
    header: 'Data',
    cell: ({ row }) => new Date(row.original.data).toLocaleDateString('pt-BR')
  },
  {
    accessorFn: row => row.pessoa?.razao_social || '—',
    id: 'cliente',
    header: 'Cliente'
  },
  {
    accessorKey: 'status',
    header: 'Status',
    filterFn: 'equals',
    cell: ({ row }) => {
      const color = {
        rascunho: 'neutral' as const,
        enviado: 'info' as const,
        aprovado: 'success' as const,
        recusado: 'error' as const,
        convertido: 'warning' as const
      }[row.original.status]

      return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () =>
        row.original.status
      )
    }
  },
  {
    accessorKey: 'valor_total',
    header: 'Valor Total',
    cell: ({ row }) => formatCurrency(Number(row.original.valor_total))
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

const statusFilter = ref('all')

watch(() => statusFilter.value, (newVal) => {
  if (!table?.value?.tableApi) return
  const statusColumn = table.value.tableApi.getColumn('status')
  if (!statusColumn) return
  if (newVal === 'all') {
    statusColumn.setFilterValue(undefined)
  } else {
    statusColumn.setFilterValue(newVal)
  }
})

const pagination = ref({
  pageIndex: 0,
  pageSize: 10
})
</script>

<template>
  <UDashboardPanel id="orcamentos">
    <template #header>
      <UDashboardNavbar title="Orçamentos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <OrcamentosAddModal @created="refresh()" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <div />

        <div class="flex flex-wrap items-center gap-1.5">
          <USelect
            v-model="statusFilter"
            :items="[
              { label: 'Todos', value: 'all' },
              { label: 'Rascunho', value: 'rascunho' },
              { label: 'Enviado', value: 'enviado' },
              { label: 'Aprovado', value: 'aprovado' },
              { label: 'Recusado', value: 'recusado' },
              { label: 'Convertido', value: 'convertido' }
            ]"
            :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
            placeholder="Filtrar status"
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
        v-model:row-selection="rowSelection"
        v-model:pagination="pagination"
        :pagination-options="{
          getPaginationRowModel: getPaginationRowModel()
        }"
        class="shrink-0"
        :data="orcamentos"
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

    <OrcamentosDeleteModal
      v-if="deletingOrcamento"
      :orcamento="deletingOrcamento"
      @deleted="() => { deletingOrcamento = null; refresh() }"
    />
  </UDashboardPanel>
</template>
