<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Company, PaginatedResponse } from '~/types'

import { UButton, UBadge, UDropdownMenu } from '#components'

const toast = useToast()
const table = useTemplateRef('table')
const { currentCompany, setCompany } = useCurrentCompany()

const { data, status, refresh } = useApi<PaginatedResponse<Company>>('/companies', {
  lazy: true
})

const companies = computed(() => data.value?.data || [])

const editingCompany = ref<Company | null>(null)
const deletingCompany = ref<Company | null>(null)

function getRowItems(row: any) {
  return [
    {
      type: 'label' as const,
      label: 'Ações'
    },
    {
      label: 'Selecionar empresa',
      icon: 'i-lucide-check-circle',
      onSelect() {
        setCompany(row.original)
        toast.add({
          title: 'Empresa selecionada',
          description: row.original.fantasia || row.original.razao_social
        })
      }
    },
    {
      type: 'separator' as const
    },
    {
      label: 'Copiar CNPJ',
      icon: 'i-lucide-copy',
      onSelect() {
        navigator.clipboard.writeText(row.original.cnpj)
        toast.add({
          title: 'CNPJ copiado',
          description: row.original.cnpj
        })
      }
    },
    {
      label: 'Editar empresa',
      icon: 'i-lucide-pencil',
      onSelect() {
        editingCompany.value = row.original
      }
    },
    {
      type: 'separator' as const
    },
    {
      label: 'Excluir empresa',
      icon: 'i-lucide-trash',
      color: 'error' as const,
      onSelect() {
        deletingCompany.value = row.original
      }
    }
  ]
}

const columns: TableColumn<Company>[] = [
  {
    accessorKey: 'id',
    header: 'ID'
  },
  {
    accessorKey: 'razao_social',
    header: 'Razão Social',
    cell: ({ row }) => {
      return h('div', undefined, [
        h('p', { class: 'font-medium text-highlighted' }, row.original.razao_social),
        h('p', { class: '' }, row.original.fantasia || '')
      ])
    }
  },
  {
    accessorKey: 'cnpj',
    header: 'CNPJ'
  },
  {
    accessorKey: 'uf',
    header: 'UF',
    cell: ({ row }) => row.original.uf || '—'
  },
  {
    accessorKey: 'ambiente',
    header: 'Ambiente',
    filterFn: 'equals',
    cell: ({ row }) => {
      const color = {
        producao: 'success' as const,
        homologacao: 'warning' as const
      }[row.original.ambiente]

      return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () =>
        row.original.ambiente === 'producao' ? 'Produção' : 'Homologação'
      )
    }
  },
  {
    accessorKey: 'certificado_validade',
    header: 'Certificado',
    cell: ({ row }) => {
      const val = row.original.certificado_validade
      if (!val) return h('span', { class: 'text-muted' }, 'Sem certificado')
      const isExpired = new Date(val) < new Date()
      const color = isExpired ? 'error' as const : 'success' as const
      return h(UBadge, { variant: 'subtle', color }, () => isExpired ? 'Vencido' : val)
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

const ambienteFilter = ref('all')

watch(() => ambienteFilter.value, (newVal) => {
  if (!table?.value?.tableApi) return
  const col = table.value.tableApi.getColumn('ambiente')
  if (!col) return
  col.setFilterValue(newVal === 'all' ? undefined : newVal)
})

const pagination = ref({
  pageIndex: 0,
  pageSize: 10
})
</script>

<template>
  <UDashboardPanel id="empresas">
    <template #header>
      <UDashboardNavbar title="Empresas">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <EmpresasAddModal @created="refresh()" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <div />

        <div class="flex flex-wrap items-center gap-1.5">
          <USelect
            v-model="ambienteFilter"
            :items="[
              { label: 'Todos', value: 'all' },
              { label: 'Homologação', value: 'homologacao' },
              { label: 'Produção', value: 'producao' }
            ]"
            :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
            placeholder="Ambiente"
            class="min-w-28"
          />
        </div>
      </div>

      <UTable
        ref="table"
        v-model:pagination="pagination"
        :pagination-options="{
          getPaginationRowModel: getPaginationRowModel()
        }"
        class="shrink-0"
        :data="companies"
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
          {{ companies.length }} empresa(s)
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

    <EmpresasEditModal
      v-if="editingCompany"
      :company="editingCompany"
      @updated="() => { editingCompany = null; refresh() }"
    />
    <EmpresasDeleteModal
      v-if="deletingCompany"
      :company="deletingCompany"
      @deleted="() => { deletingCompany = null; refresh() }"
    />
  </UDashboardPanel>
</template>
