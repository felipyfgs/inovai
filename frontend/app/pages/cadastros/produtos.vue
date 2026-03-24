<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Row } from '@tanstack/table-core'
import type { Produto, PaginatedResponse } from '~/types'
import { formatCurrency } from '~/utils'

import { UButton, UBadge, UDropdownMenu, UCheckbox } from '#components'

const toast = useToast()
const table = useTemplateRef('table')
const { currentCompany } = useCurrentCompany()

const columnFilters = ref([{
  id: 'descricao',
  value: ''
}])
const columnVisibility = ref()
const rowSelection = ref({})

const { data, status, refresh } = useApi<PaginatedResponse<Produto>>('/produtos', {
  lazy: true,
  watch: [() => currentCompany.value?.id]
})

const produtos = computed(() => data.value?.data || [])

const editingProduto = ref<Produto | null>(null)
const deletingProduto = ref<Produto | null>(null)

function getRowItems(row: Row<Produto>) {
  return [
    {
      type: 'label' as const,
      label: 'Ações'
    },
    {
      label: 'Copiar código',
      icon: 'i-lucide-copy',
      onSelect() {
        navigator.clipboard.writeText(row.original.codigo || row.original.id.toString())
        toast.add({
          title: 'Copiado',
          description: 'Código copiado para a área de transferência'
        })
      }
    },
    {
      type: 'separator' as const
    },
    {
      label: 'Editar produto',
      icon: 'i-lucide-pencil',
      onSelect() {
        editingProduto.value = row.original
      }
    },
    {
      type: 'separator' as const
    },
    {
      label: 'Excluir produto',
      icon: 'i-lucide-trash',
      color: 'error' as const,
      onSelect() {
        deletingProduto.value = row.original
      }
    }
  ]
}

const columns: TableColumn<Produto>[] = [
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
    accessorKey: 'id',
    header: 'ID'
  },
  {
    accessorKey: 'codigo',
    header: 'Código',
    cell: ({ row }) => row.original.codigo || '—'
  },
  {
    accessorKey: 'descricao',
    header: ({ column }) => {
      const isSorted = column.getIsSorted()

      return h(UButton, {
        color: 'neutral',
        variant: 'ghost',
        label: 'Descrição',
        icon: isSorted
          ? isSorted === 'asc'
            ? 'i-lucide-arrow-up-narrow-wide'
            : 'i-lucide-arrow-down-wide-narrow'
          : 'i-lucide-arrow-up-down',
        class: '-mx-2.5',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc')
      })
    }
  },
  {
    accessorKey: 'ncm',
    header: 'NCM',
    cell: ({ row }) => row.original.ncm || '—'
  },
  {
    accessorKey: 'unidade',
    header: 'UN'
  },
  {
    accessorKey: 'preco_venda',
    header: 'Preço Venda',
    cell: ({ row }) => formatCurrency(Number(row.original.preco_venda))
  },
  {
    accessorKey: 'preco_custo',
    header: 'Preço Custo',
    cell: ({ row }) => formatCurrency(Number(row.original.preco_custo))
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    filterFn: 'equals',
    cell: ({ row }) => {
      const color = row.original.is_active ? 'success' as const : 'error' as const
      return h(UBadge, { variant: 'subtle', color }, () =>
        row.original.is_active ? 'Ativo' : 'Inativo'
      )
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
            content: {
              align: 'end'
            },
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

const searchDesc = computed({
  get: (): string => {
    return (table.value?.tableApi?.getColumn('descricao')?.getFilterValue() as string) || ''
  },
  set: (value: string) => {
    table.value?.tableApi?.getColumn('descricao')?.setFilterValue(value || undefined)
  }
})

const pagination = ref({
  pageIndex: 0,
  pageSize: 10
})
</script>

<template>
  <UDashboardPanel id="produtos">
    <template #header>
      <UDashboardNavbar title="Produtos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <ProdutosAddModal @created="refresh()" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="searchDesc"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Buscar por descrição..."
        />

        <div class="flex flex-wrap items-center gap-1.5">
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
        :data="produtos"
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

    <ProdutosEditModal
      v-if="editingProduto"
      :produto="editingProduto"
      @updated="() => { editingProduto = null; refresh() }"
    />
    <ProdutosDeleteModal
      v-if="deletingProduto"
      :produto="deletingProduto"
      @deleted="() => { deletingProduto = null; refresh() }"
    />
  </UDashboardPanel>
</template>
