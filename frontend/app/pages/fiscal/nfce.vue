<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Row } from '@tanstack/table-core'
import type { Nfe } from '~/types'
import { formatCurrency } from '~/utils'

import { UButton, UBadge, UDropdownMenu, UCheckbox } from '#components'

const toast = useToast()
const table = useTemplateRef('table')
const { listNfes } = useNfe()

const columnFilters = ref([])
const columnVisibility = ref()
const rowSelection = ref({})

const { data, status, refresh } = listNfes(65)

const notas = computed(() => data.value?.data || [])

const cancelModalRef = useTemplateRef('cancelModalRef')
const cancelTarget = ref<Nfe | null>(null)

function openCancel(row: Row<Nfe>) {
  cancelTarget.value = row.original
  cancelModalRef.value?.openModal()
}

function getRowItems(row: Row<Nfe>) {
  return [
    {
      type: 'label' as const,
      label: 'Ações'
    },
    {
      label: 'Copiar chave',
      icon: 'i-lucide-copy',
      onSelect() {
        navigator.clipboard.writeText(row.original.chave || '')
        toast.add({ title: 'Copiado', description: 'Chave copiada' })
      }
    },
    {
      label: 'Ver XML',
      icon: 'i-lucide-code'
    },
    { type: 'separator' as const },
    {
      label: 'Cancelar NFC-e',
      icon: 'i-lucide-x-circle',
      color: 'error' as const,
      disabled: row.original.status !== 'autorizada',
      onSelect() {
        openCancel(row)
      }
    }
  ]
}

const columns: TableColumn<Nfe>[] = [
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
    accessorKey: 'serie',
    header: 'Série'
  },
  {
    accessorKey: 'data_emissao',
    header: 'Emissão',
    cell: ({ row }) => row.original.data_emissao ? new Date(row.original.data_emissao).toLocaleDateString('pt-BR') : '—'
  },
  {
    accessorKey: 'natureza_operacao',
    header: 'Natureza'
  },
  {
    accessorKey: 'status',
    header: 'Status',
    filterFn: 'equals',
    cell: ({ row }) => {
      const color = {
        rascunho: 'neutral' as const,
        assinada: 'info' as const,
        transmitida: 'info' as const,
        autorizada: 'success' as const,
        rejeitada: 'error' as const,
        cancelada: 'error' as const,
        inutilizada: 'warning' as const,
        denegada: 'error' as const
      }[row.original.status] || 'neutral' as const

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
          { content: { align: 'end' }, items: getRowItems(row) },
          () => h(UButton, { icon: 'i-lucide-ellipsis-vertical', color: 'neutral', variant: 'ghost', class: 'ml-auto' })
        )
      )
    }
  }
]

const statusFilter = ref('all')

watch(() => statusFilter.value, (newVal) => {
  if (!table?.value?.tableApi) return
  const col = table.value.tableApi.getColumn('status')
  if (!col) return
  col.setFilterValue(newVal === 'all' ? undefined : newVal)
})

const pagination = ref({ pageIndex: 0, pageSize: 10 })
</script>

<template>
  <UDashboardPanel id="nfce">
    <template #header>
      <UDashboardNavbar title="NFC-e (Nota Fiscal ao Consumidor)">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <NotasFiscaisNfceAddModal @created="refresh" />
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
              { label: 'Autorizada', value: 'autorizada' },
              { label: 'Rejeitada', value: 'rejeitada' },
              { label: 'Cancelada', value: 'cancelada' }
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
        :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
        class="shrink-0"
        :data="notas"
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

      <NotasFiscaisCancelModal
        v-if="cancelTarget"
        ref="cancelModalRef"
        :nota="cancelTarget"
        @cancelled="refresh"
      />
    </template>
  </UDashboardPanel>
</template>
