<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Row } from '@tanstack/table-core'
import type { Pessoa, PaginatedResponse } from '~/types'

import { UButton, UBadge, UDropdownMenu, UCheckbox } from '#components'

const toast = useToast()
const table = useTemplateRef('table')
const { currentCompany } = useCurrentCompany()

const columnFilters = ref([{
  id: 'razao_social',
  value: ''
}])
const columnVisibility = ref()
const rowSelection = ref({})

const { data, status, refresh } = useApi<PaginatedResponse<Pessoa>>('/pessoas', {
  lazy: true,
  watch: [() => currentCompany.value?.id]
})

const pessoas = computed(() => data.value?.data || [])

const editingPessoa = ref<Pessoa | null>(null)
const deletingPessoa = ref<Pessoa | null>(null)

function getRowItems(row: Row<Pessoa>) {
  return [
    {
      type: 'label' as const,
      label: 'Ações'
    },
    {
      label: 'Copiar CPF/CNPJ',
      icon: 'i-lucide-copy',
      onSelect() {
        navigator.clipboard.writeText(row.original.cpf_cnpj || '')
        toast.add({
          title: 'Copiado',
          description: 'CPF/CNPJ copiado para a área de transferência'
        })
      }
    },
    {
      type: 'separator' as const
    },
    {
      label: 'Editar pessoa',
      icon: 'i-lucide-pencil',
      onSelect() {
        editingPessoa.value = row.original
      }
    },
    {
      type: 'separator' as const
    },
    {
      label: 'Excluir pessoa',
      icon: 'i-lucide-trash',
      color: 'error' as const,
      onSelect() {
        deletingPessoa.value = row.original
      }
    }
  ]
}

const columns: TableColumn<Pessoa>[] = [
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
    accessorKey: 'razao_social',
    header: 'Nome / Razão Social',
    cell: ({ row }) => {
      return h('div', undefined, [
        h('p', { class: 'font-medium text-highlighted' }, row.original.razao_social),
        h('p', { class: '' }, row.original.fantasia || '')
      ])
    }
  },
  {
    accessorKey: 'cpf_cnpj',
    header: ({ column }) => {
      const isSorted = column.getIsSorted()

      return h(UButton, {
        color: 'neutral',
        variant: 'ghost',
        label: 'CPF/CNPJ',
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
    accessorKey: 'tipo',
    header: 'Tipo',
    filterFn: 'equals',
    cell: ({ row }) => {
      const color = {
        cliente: 'success' as const,
        fornecedor: 'warning' as const,
        ambos: 'info' as const
      }[row.original.tipo]

      return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () =>
        row.original.tipo
      )
    }
  },
  {
    accessorKey: 'municipio',
    header: 'Município',
    cell: ({ row }) => row.original.municipio || '—'
  },
  {
    accessorKey: 'uf',
    header: 'UF',
    cell: ({ row }) => row.original.uf || '—'
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

const tipoFilter = ref('all')

watch(() => tipoFilter.value, (newVal) => {
  if (!table?.value?.tableApi) return

  const tipoColumn = table.value.tableApi.getColumn('tipo')
  if (!tipoColumn) return

  if (newVal === 'all') {
    tipoColumn.setFilterValue(undefined)
  } else {
    tipoColumn.setFilterValue(newVal)
  }
})

const searchName = computed({
  get: (): string => {
    return (table.value?.tableApi?.getColumn('razao_social')?.getFilterValue() as string) || ''
  },
  set: (value: string) => {
    table.value?.tableApi?.getColumn('razao_social')?.setFilterValue(value || undefined)
  }
})

const pagination = ref({
  pageIndex: 0,
  pageSize: 10
})
</script>

<template>
  <UDashboardPanel id="pessoas">
    <template #header>
      <UDashboardNavbar title="Pessoas">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <PessoasAddModal @created="refresh()" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="searchName"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Buscar por nome..."
        />

        <div class="flex flex-wrap items-center gap-1.5">
          <USelect
            v-model="tipoFilter"
            :items="[
              { label: 'Todos', value: 'all' },
              { label: 'Cliente', value: 'cliente' },
              { label: 'Fornecedor', value: 'fornecedor' },
              { label: 'Ambos', value: 'ambos' }
            ]"
            :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
            placeholder="Filtrar tipo"
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
        :data="pessoas"
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

    <PessoasEditModal
      v-if="editingPessoa"
      :pessoa="editingPessoa"
      @updated="() => { editingPessoa = null; refresh() }"
    />
    <PessoasDeleteModal
      v-if="deletingPessoa"
      :pessoa="deletingPessoa"
      @deleted="() => { deletingPessoa = null; refresh() }"
    />
  </UDashboardPanel>
</template>
