<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Row } from '@tanstack/table-core'
import type { OfficePlan } from '~/types'

const UButton = resolveComponent('UButton')
const UBadge = resolveComponent('UBadge')
const UDropdownMenu = resolveComponent('UDropdownMenu')
const UCheckbox = resolveComponent('UCheckbox')

definePageMeta({ middleware: 'escritorio' })

const toast = useToast()
const { handleError } = useApiError()
const { patch } = useApiMutation()
const table = useTemplateRef('table')

const editingPlan = ref<OfficePlan | null>(null)
const deletingPlan = ref<OfficePlan | null>(null)

const columnFilters = ref([{
  id: 'name',
  value: ''
}])
const columnVisibility = ref()
const rowSelection = ref({})

const { data, status, refresh } = useApi<OfficePlan[]>('/office-plans', {
  lazy: true
})

const plans = computed(() => data.value || [])

const allModules: Record<string, string> = {
  nfe: 'NF-e',
  nfce: 'NFC-e',
  cte: 'CT-e',
  mdfe: 'MDF-e',
  nfse: 'NFS-e',
  orcamento: 'Orçamentos',
  estoque: 'Estoque',
  financeiro: 'Financeiro',
  restaurante: 'Restaurante',
  relatorios: 'Relatórios'
}

function formatPrice(price: string | number) {
  return `R$ ${Number(price).toFixed(2).replace('.', ',')}`
}

function getDropdownItems(row: Row<OfficePlan>) {
  const plan = row.original
  return [
    {
      type: 'label' as const,
      label: 'Ações'
    },
    {
      label: 'Editar',
      icon: 'i-lucide-pencil',
      onSelect() { editingPlan.value = plan }
    },
    {
      label: plan.is_active ? 'Desativar' : 'Ativar',
      icon: plan.is_active ? 'i-lucide-eye-off' : 'i-lucide-eye',
      color: plan.is_active ? 'warning' as const : 'success' as const,
      onSelect() { toggleActive(plan) }
    },
    {
      type: 'separator' as const
    },
    {
      label: 'Excluir',
      icon: 'i-lucide-trash',
      color: 'error' as const,
      onSelect() { deletingPlan.value = plan }
    }
  ]
}

async function toggleActive(plan: OfficePlan) {
  try {
    await patch(`/office-plans/${plan.id}`, { is_active: !plan.is_active })
    toast.add({
      title: plan.is_active ? 'Plano desativado' : 'Plano ativado',
      description: plan.name,
      color: 'success'
    })
    refresh()
  } catch (e: unknown) {
    handleError(e, 'Erro ao alterar status do plano')
  }
}

const columns: TableColumn<OfficePlan>[] = [
  {
    id: 'select',
    header: ({ table }) =>
      h(UCheckbox, {
        'modelValue': table.getIsSomePageRowsSelected()
          ? 'indeterminate'
          : table.getIsAllPageRowsSelected(),
        'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
          table.toggleAllPageRowsSelected(!!value),
        'ariaLabel': 'Selecionar todos'
      }),
    cell: ({ row }) =>
      h(UCheckbox, {
        'modelValue': row.getIsSelected(),
        'onUpdate:modelValue': (value: boolean | 'indeterminate') => row.toggleSelected(!!value),
        'ariaLabel': 'Selecionar linha'
      })
  },
  {
    accessorKey: 'name',
    header: 'Nome',
    cell: ({ row }) => {
      return h('div', { class: 'flex items-center gap-2' }, [
        h('p', { class: 'font-medium text-highlighted' }, row.original.name),
        row.original.is_default
          ? h(UBadge, { variant: 'subtle', size: 'xs', color: 'primary' }, () => 'Padrão')
          : null
      ])
    }
  },
  {
    accessorKey: 'description',
    header: 'Descrição',
    cell: ({ row }) =>
      h('p', { class: 'text-sm text-muted max-w-48 truncate' }, row.original.description || '—')
  },
  {
    accessorKey: 'price',
    header: 'Valor',
    cell: ({ row }) =>
      h('div', { class: 'flex items-baseline gap-1' }, [
        h('span', { class: 'font-semibold' }, formatPrice(row.original.price)),
        h('span', { class: 'text-xs text-muted' }, '/mês')
      ])
  },
  {
    accessorKey: 'max_nfs_month',
    header: 'Limite NFs',
    cell: ({ row }) =>
      h('span', { class: 'text-sm' }, row.original.max_nfs_month ? `Até ${row.original.max_nfs_month}/mês` : 'Ilimitado')
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    filterFn: 'equals',
    cell: ({ row }) => {
      const color = row.original.is_active ? 'success' as const : 'neutral' as const
      return h(UBadge, { variant: row.original.is_active ? 'subtle' : 'outline', color }, () =>
        row.original.is_active ? 'Ativo' : 'Inativo'
      )
    }
  },
  {
    accessorKey: 'modules',
    header: 'Módulos',
    cell: ({ row }) =>
      h('div', { class: 'flex flex-wrap gap-1' },
        row.original.modules.slice(0, 3).map(mod =>
          h(UBadge, { variant: 'subtle', size: 'xs', color: 'primary' }, () => allModules[mod] || mod)
        ).concat(
          row.original.modules.length > 3
            ? [h(UBadge, { variant: 'outline', size: 'xs', color: 'neutral' }, () => `+${row.original.modules.length - 3}`)]
            : []
        )
      )
  },
  {
    id: 'actions',
    cell: ({ row }) =>
      h(
        'div',
        { class: 'text-right' },
        h(
          UDropdownMenu,
          {
            content: { align: 'end' },
            items: getDropdownItems(row)
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
]

const statusFilter = ref('all')

watch(() => statusFilter.value, (newVal) => {
  if (!table?.value?.tableApi) return
  const statusColumn = table.value.tableApi.getColumn('is_active')
  if (!statusColumn) return
  if (newVal === 'all') {
    statusColumn.setFilterValue(undefined)
  } else {
    statusColumn.setFilterValue(newVal === 'active')
  }
})

const name = computed({
  get: (): string => {
    return (table.value?.tableApi?.getColumn('name')?.getFilterValue() as string) || ''
  },
  set: (value: string) => {
    table.value?.tableApi?.getColumn('name')?.setFilterValue(value || undefined)
  }
})

const pagination = ref({
  pageIndex: 0,
  pageSize: 10
})
</script>

<template>
  <UDashboardPanel id="escritorio-planos">
    <template #header>
      <UDashboardNavbar title="Planos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <EscritorioPlanosAddModal @created="refresh" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="name"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar por nome..."
        />

        <div class="flex flex-wrap items-center gap-1.5">
          <USelect
            v-model="statusFilter"
            :items="[
              { label: 'Todos', value: 'all' },
              { label: 'Ativos', value: 'active' },
              { label: 'Inativos', value: 'inactive' }
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
        :data="plans"
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
          {{ table?.tableApi?.getFilteredRowModel().rows.length || 0 }} plano(s) selecionado(s).
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

      <div v-if="status !== 'pending' && plans.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
        <UIcon name="i-lucide-package" class="size-12 text-dimmed mb-3" />
        <p class="text-sm text-muted">
          Nenhum plano cadastrado
        </p>
        <p class="text-xs text-dimmed">
          Crie planos para oferecer às suas empresas
        </p>
      </div>
    </template>
  </UDashboardPanel>

  <EscritorioPlanosEditModal
    v-if="editingPlan"
    :plan="editingPlan"
    @updated="() => { editingPlan = null; refresh() }"
  />

  <EscritorioPlanosDeleteModal
    v-if="deletingPlan"
    :plan="deletingPlan"
    @deleted="() => { deletingPlan = null; refresh() }"
  />
</template>
